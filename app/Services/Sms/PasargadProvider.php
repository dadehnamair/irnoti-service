<?php

namespace App\Services\Sms;

use App\Models\SmsMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Upstream SMS driver (docs/starter.md §13), referred to internally by the
 * opaque codename "pasargad" — the real vendor name is never exposed to
 * customers. Two independent modes:
 *
 *  - api_key mode — config('sms.providers.pasargad.api_key') is set. Uses the
 *    provider's JSON REST host (send/simple/{key}, …).
 *
 *  - username/password mode — a customer's own panel ({@see UserSmsGateway}),
 *    credentials from the users table. Uses the classic ASMX web service, which
 *    takes plain form fields and returns a bare XML scalar:
 *      - Send  : /post/Send.asmx/SendSimpleSMS2
 *      - Count : /post/Users.asmx/GetUserCredit
 *      - Rial  : /post/Users.asmx/GetUserCredit2
 *    All of them answer `-1` for wrong credentials.
 */
class PasargadProvider implements SmsProviderInterface
{
    private const REST = 'https://rest.melipayamak.com/api';

    private const SOAP = 'https://api.payamak-panel.com/post';

    /** @param  array<string, string|null>  $config */
    public function __construct(private readonly array $config) {}

    public function send(string $to, string $message, ?string $from = null): ?string
    {
        if ($this->usesCredentials()) {
            $value = $this->soap('Send.asmx/SendSimpleSMS2', [
                'to' => $to,
                'from' => $from ?: ($this->config['sender'] ?? ''),
                'text' => $message,
                'isflash' => 'false',
            ]);

            return $this->sendResult($value);
        }

        $response = $this->postWithKey('send/simple', [
            'from' => $from ?: ($this->config['sender'] ?? ''),
            'to' => $to,
            'text' => $message,
        ]);

        return $this->restRecId($response);
    }

    public function sendPattern(string $to, string $bodyId, array $variables): ?string
    {
        if ($this->usesCredentials()) {
            $value = $this->soap('Send.asmx/SendByBaseNumber', [
                'text' => implode(';', array_values($variables)),
                'to' => $to,
                'bodyId' => (int) $bodyId,
            ]);

            return $this->sendResult($value);
        }

        $response = $this->postWithKey('send/shared', [
            'bodyId' => (int) $bodyId,
            'to' => $to,
            'args' => array_values($variables),
        ]);

        return $this->restRecId($response);
    }

    /**
     * Delivery receipt for one previously sent message. Returns the raw provider
     * code as a string — "0"/"8" = still in transit, "1" = delivered, "2"/"16" =
     * undelivered, "3"/"5"/"100" = carrier error; null when the panel has no
     * report yet or reporting is disabled. {@see SmsMessage::mapDeliveryStatus()}.
     */
    public function deliveryStatus(string $recId): ?string
    {
        if ($this->usesCredentials()) {
            $value = $this->soap('Send.asmx/GetDelivery2', ['recId' => $recId]);

            return $value === '' || strcasecmp($value, 'null') === 0 ? null : $value;
        }

        $response = $this->postWithKey('receive/status', ['recId' => $recId]);

        $status = $response['status'] ?? $response['value'] ?? null;

        return $status !== null ? (string) $status : null;
    }

    /**
     * A page of the account's message archive — the «پیام‌ها» menu
     * (docs/starter.md §14). Only the username/password mode can report it;
     * api_key mode has no equivalent and returns [].
     *
     * Send.asmx/getMessages (the ASMX method name is lower-camel) takes `location`
     * (1 = inbox, 2 = sent, -1 = both), an optional `from` sender-line filter and
     * an `index`/`count` window, and replies with an ArrayOfMessagesBL — one
     * `<MessagesBL>` element per row.
     *
     * @return array<int, array{msg_id: string, body: string, sender: string, receiver: string, date: string, parts: int, rec_count: int, rec_success: int, rec_failed: int}>
     */
    public function messages(int $location, int $index = 0, int $count = 100, ?string $from = null): array
    {
        if (! $this->usesCredentials()) {
            return [];
        }

        $body = $this->soapBody('Send.asmx/getMessages', [
            'location' => $location,
            'from' => $from ?? '',
            'index' => max(0, $index),
            'count' => max(1, min($count, 500)),
        ]);

        $rows = [];

        foreach ($this->xmlRecords($body, 'MessagesBL') as $row) {
            // Keys are lower-cased by xmlRecords() so panel casing drift is moot.
            $rows[] = [
                'msg_id' => (string) ($row['msgid'] ?? ''),
                'body' => (string) ($row['body'] ?? ''),
                'sender' => (string) ($row['sender'] ?? ''),
                'receiver' => (string) ($row['receiver'] ?? ''),
                'date' => (string) ($row['senddate'] ?? ''),
                'parts' => (int) ($row['parts'] ?? 1),
                'rec_count' => (int) ($row['reccount'] ?? 0),
                'rec_success' => (int) ($row['recsuccess'] ?? 0),
                'rec_failed' => (int) ($row['recfailed'] ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * The dedicated sender numbers on this account. Only the username/password
     * mode can report them; api_key mode has no equivalent and returns [].
     *
     * @return array<int, string>
     */
    public function numbers(): array
    {
        if (! $this->usesCredentials()) {
            return [];
        }

        // Users.asmx/GetUserNumbers replies with an ArrayOfString, not a scalar.
        $strings = $this->xmlStrings($this->soapBody('Users.asmx/GetUserNumbers', []));

        $numbers = [];

        foreach ($strings as $value) {
            $digits = preg_replace('/\D+/', '', $value) ?? '';

            if ($digits !== '') {
                $numbers[$digits] = $digits; // de-dupe
            }
        }

        return array_values($numbers);
    }

    /**
     * Remaining credit as a number of SMS. Send.asmx/GetCredit returns the count
     * for valid credentials and "0" for invalid ones — so a 0 is cross-checked
     * against GetUserCredit2 (which answers -1 on a bad login) before we decide
     * it's really an error.
     */
    public function credit(): ?int
    {
        if (! $this->usesCredentials()) {
            return null; // api_key mode has no equivalent here
        }

        $value = $this->soap('Send.asmx/GetCredit', []);

        if (! is_numeric($value)) {
            throw new RuntimeException('پاسخ نامعتبر از '.sms_provider_label().' هنگام دریافت اعتبار: '.$value);
        }

        $count = (float) $value;

        if ($count <= 0 && (string) $this->soap('Users.asmx/GetUserCredit2', []) === '-1') {
            throw new RuntimeException('دریافت اعتبار ناموفق بود: نام کاربری یا رمز عبور پنل پیامک نادرست است.');
        }

        return (int) round(max($count, 0));
    }

    /**
     * Remaining credit as a Rial amount. Best-effort: -1 (bad login) or any
     * failure returns null rather than throwing.
     */
    public function creditRial(): ?int
    {
        if (! $this->usesCredentials()) {
            return null;
        }

        try {
            $value = $this->soap('Users.asmx/GetUserCredit2', []);

            if (is_numeric($value) && (float) $value >= 0) {
                return (int) round((float) $value);
            }
        } catch (\Throwable $e) {
            Log::debug('[sms:pasargad] rial credit read failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function usesCredentials(): bool
    {
        return filled($this->config['username'] ?? null) && filled($this->config['password'] ?? null);
    }

    /* -------------------------- username/password (ASMX) -------------------------- */

    /**
     * POST a form to an ASMX method and return the inner text of the XML scalar
     * it replies with (`<string>…</string>` etc.).
     *
     * @param  array<string, mixed>  $params
     */
    private function soap(string $method, array $params): string
    {
        return $this->xmlScalar($this->soapBody($method, $params));
    }

    /**
     * POST a form to an ASMX method and return its raw response body. Used
     * directly when the reply is a list rather than a scalar ({@see xmlStrings()}).
     *
     * @param  array<string, mixed>  $params
     */
    private function soapBody(string $method, array $params): string
    {
        $payload = array_merge([
            'username' => $this->config['username'] ?? '',
            'password' => $this->config['password'] ?? '',
        ], $params);

        try {
            // Kept short: sends run in a queued job that retries, and the
            // customer panel reads credit/numbers on page load synchronously.
            $response = Http::asForm()->connectTimeout(5)->timeout(10)->acceptJson()->post(self::SOAP.'/'.$method, $payload);
        } catch (\Throwable $e) {
            Log::error('[sms:pasargad] connection failed', ['method' => $method, 'error' => $e->getMessage()]);

            throw new RuntimeException('اتصال به '.sms_provider_label().' برقرار نشد: '.$e->getMessage());
        }

        Log::debug('[sms:pasargad] '.$method, [
            'sent' => ['username' => $payload['username']] + array_diff_key($payload, ['username' => 1, 'password' => 1]),
            'http' => $response->status(),
            'value' => mb_substr($response->body(), 0, 300),
        ]);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                '%s با کد %d پاسخ داد: %s',
                sms_provider_label(),
                $response->status(),
                mb_substr($response->body(), 0, 300) ?: 'بدون بدنه',
            ));
        }

        return $response->body();
    }

    /** Inner text of a .NET ASMX scalar response, or the trimmed body if it isn't wrapped. */
    private function xmlScalar(string $body): string
    {
        if (preg_match('~<(?:string|double|int|long|boolean)[^>]*>(.*?)</(?:string|double|int|long|boolean)>~s', $body, $m)) {
            return trim(html_entity_decode($m[1]));
        }

        return trim(strip_tags($body));
    }

    /**
     * Inner text of every `<string>…</string>` node in an ASMX ArrayOfString
     * response (Users.asmx/GetUserNumbers).
     *
     * @return array<int, string>
     */
    private function xmlStrings(string $body): array
    {
        if (! preg_match_all('~<string[^>]*>(.*?)</string>~s', $body, $m)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn ($v) => trim(html_entity_decode($v)),
            $m[1],
        ), static fn ($v) => $v !== ''));
    }

    /**
     * Every list element of an ASMX list response, each flattened to an
     * associative array of its child elements with **lower-cased keys**
     * (getMessages ArrayOfMessagesBL). The preferred `$node` name is tried first;
     * failing that, every direct child of the root is treated as a record, so a
     * differently-cased or renamed wrapper element still parses. The .NET default
     * namespace is stripped so plain node access works.
     *
     * @return array<int, array<string, string>>
     */
    private function xmlRecords(string $body, string $node): array
    {
        $clean = preg_replace('/\s+xmlns(:\w+)?="[^"]*"/', '', $body) ?? $body;

        $xml = @simplexml_load_string($clean);

        if ($xml === false) {
            return [];
        }

        $records = isset($xml->{$node}) ? $xml->{$node} : $xml->children();

        $rows = [];

        foreach ($records as $record) {
            if (! $record instanceof \SimpleXMLElement || $record->count() === 0) {
                continue; // a scalar/text node, not a record
            }

            $row = [];

            foreach ($record->children() as $field) {
                $row[strtolower($field->getName())] = trim(html_entity_decode((string) $field));
            }

            if ($row !== []) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /** SendSimpleSMS2 returns a long recId on success, or a small/zero/negative status code. */
    private function sendResult(string $value): ?string
    {
        // A real message id is a long run of digits; anything short/≤0 is a status code.
        if (preg_match('/^\d{6,}$/', $value)) {
            return $value;
        }

        throw new RuntimeException($this->sendError($value));
    }

    private function sendError(string $code): string
    {
        return match ($code) {
            '0' => 'نام کاربری یا رمز عبور پنل پیامک نادرست است، یا خطای نامشخص.',
            '-1' => 'نام کاربری یا رمز عبور پنل پیامک نادرست است.',
            '-2' => 'اعتبار پنل پیامک کافی نیست.',
            '-3' => 'محدودیت تعداد پیامک روزانه.',
            '-4' => 'محدودیت حجم پیامک روزانه.',
            '-5' => 'شمارهٔ فرستنده معتبر نیست یا به این حساب تعلق ندارد.',
            '-6' => 'سامانه در حال بروزرسانی است؛ کمی بعد دوباره تلاش کنید.',
            '-7' => 'متن پیام حاوی کلمات فیلترشده است.',
            '-8' => 'شمارهٔ گیرنده نامعتبر است.',
            '-9' => 'ارسال از خطوط عمومی از طریق وب‌سرویس ممکن نیست.',
            '-10' => 'کاربر پنل پیامک فعال نیست یا مدارکش کامل نیست.',
            '-11' => 'ارسال انجام نشد.',
            '-12' => 'اعتبار ریالی پنل پیامک کافی نیست.',
            default => 'ارسال پیامک ناموفق بود (کد: '.$code.').',
        };
    }

    /* ------------------------------ api_key (REST) ------------------------------ */

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function postWithKey(string $path, array $payload): array
    {
        $key = $this->config['api_key'] ?? null;

        if (blank($key)) {
            throw new RuntimeException('پیکربندی '.sms_provider_label().' ناقص است (SMS_PASARGAD_API_KEY تنظیم نشده).');
        }

        try {
            $response = Http::asJson()->connectTimeout(5)->timeout(12)->post(self::REST.'/'.$path.'/'.$key, $payload);
        } catch (\Throwable $e) {
            throw new RuntimeException('اتصال به '.sms_provider_label().' برقرار نشد: '.$e->getMessage());
        }

        if ($response->failed()) {
            throw new RuntimeException('ارسال از طریق '.sms_provider_label().' ناموفق بود (کد '.$response->status().').');
        }

        return (array) $response->json();
    }

    /** @param  array<string, mixed>  $response */
    private function restRecId(array $response): ?string
    {
        $recId = $response['recId'] ?? $response['value'] ?? null;

        return $recId !== null ? (string) $recId : null;
    }
}
