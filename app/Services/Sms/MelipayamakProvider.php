<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Melipayamak driver (docs/starter.md §13). Two independent modes:
 *
 *  - api_key mode — config('services.sms.melipayamak.api_key') is set. Uses the
 *    JSON REST host rest.melipayamak.com (send/simple/{key}, …).
 *
 *  - username/password mode — a customer's own panel ({@see UserSmsGateway}),
 *    credentials from the users table. Uses the classic ASMX web service on
 *    api.payamak-panel.com, which takes plain form fields and returns a bare
 *    XML scalar. This host is what the official docs describe and it resolves
 *    even where rest.melipayamak.com does not:
 *      - Send  : /post/Send.asmx/SendSimpleSMS2  (https://www.melipayamak.com/api/sendsimplesms2/)
 *      - Count : /post/Users.asmx/GetUserCredit  (https://www.melipayamak.com/api/getusercredit/)
 *      - Rial  : /post/Users.asmx/GetUserCredit2 (https://www.melipayamak.com/api/getusercredit2/)
 *    All of them answer `-1` for wrong credentials.
 */
class MelipayamakProvider implements SmsProviderInterface
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

    public function deliveryStatus(string $recId): ?string
    {
        if ($this->usesCredentials()) {
            return $this->soap('Send.asmx/GetDeliveries2', ['recId' => $recId]) ?: null;
        }

        $response = $this->postWithKey('receive/status', ['recId' => $recId]);

        return $response['status'] ?? null;
    }

    /**
     * Remaining credit as a number of SMS
     * (https://www.melipayamak.com/api/getusercredit/).
     */
    public function credit(): ?int
    {
        if (! $this->usesCredentials()) {
            return null; // api_key mode has no equivalent here
        }

        $value = $this->soap('Users.asmx/GetUserCredit', [
            'targetUsername' => $this->config['username'] ?? '',
        ]);

        if ((string) $value === '-1') {
            throw new RuntimeException('دریافت اعتبار ناموفق بود: نام کاربری یا رمز عبور پنل پیامک نادرست است.');
        }

        if (! is_numeric($value)) {
            throw new RuntimeException('پاسخ نامعتبر از ملی‌پیامک هنگام دریافت اعتبار: '.$value);
        }

        return (int) round((float) $value);
    }

    /**
     * Remaining credit as a Rial amount
     * (https://www.melipayamak.com/api/getusercredit2/). Best-effort: any failure
     * returns null rather than throwing.
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
            Log::debug('[sms:melipayamak] rial credit read failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    private function usesCredentials(): bool
    {
        return filled($this->config['username'] ?? null) && filled($this->config['password'] ?? null);
    }

    /* -------------------------- username/password (ASMX) -------------------------- */

    /**
     * POST a form to an api.payamak-panel.com ASMX method and return the inner
     * text of the XML scalar it replies with (`<string>…</string>` etc.).
     *
     * @param  array<string, mixed>  $params
     */
    private function soap(string $method, array $params): string
    {
        $payload = array_merge([
            'username' => $this->config['username'] ?? '',
            'password' => $this->config['password'] ?? '',
        ], $params);

        try {
            $response = Http::asForm()->timeout(20)->acceptJson()->post(self::SOAP.'/'.$method, $payload);
        } catch (\Throwable $e) {
            Log::error('[sms:melipayamak] connection failed', ['method' => $method, 'error' => $e->getMessage()]);

            throw new RuntimeException('اتصال به سرور ملی‌پیامک برقرار نشد: '.$e->getMessage());
        }

        $scalar = $this->xmlScalar($response->body());

        Log::debug('[sms:melipayamak] '.$method, [
            'sent' => ['username' => $payload['username']] + array_diff_key($payload, ['username' => 1, 'password' => 1]),
            'http' => $response->status(),
            'value' => $scalar,
        ]);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'ملی‌پیامک با کد %d پاسخ داد: %s',
                $response->status(),
                mb_substr($response->body(), 0, 300) ?: 'بدون بدنه',
            ));
        }

        return $scalar;
    }

    /** Inner text of a .NET ASMX scalar response, or the trimmed body if it isn't wrapped. */
    private function xmlScalar(string $body): string
    {
        if (preg_match('~<(?:string|double|int|long|boolean)[^>]*>(.*?)</(?:string|double|int|long|boolean)>~s', $body, $m)) {
            return trim(html_entity_decode($m[1]));
        }

        return trim(strip_tags($body));
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
            '0' => 'خطای نامشخص در ارسال.',
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
            throw new RuntimeException('پیکربندی ملی‌پیامک ناقص است (MELIPAYAMAK_API_KEY تنظیم نشده).');
        }

        try {
            $response = Http::asJson()->timeout(15)->post(self::REST.'/'.$path.'/'.$key, $payload);
        } catch (\Throwable $e) {
            throw new RuntimeException('اتصال به سرور ملی‌پیامک برقرار نشد: '.$e->getMessage());
        }

        if ($response->failed()) {
            throw new RuntimeException('ارسال از طریق ملی‌پیامک ناموفق بود (کد '.$response->status().').');
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
