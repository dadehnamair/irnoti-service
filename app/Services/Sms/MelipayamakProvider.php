<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Melipayamak REST driver (docs/starter.md §13). Works in two modes:
 *
 *  - api_key mode  — config('services.sms.melipayamak.api_key') is set. Uses the
 *    key-in-URL endpoints: send/simple/{key}, send/shared/{key}, receive/status/{key}.
 *
 *  - username/password mode — used for a customer's own panel ({@see UserSmsGateway}),
 *    where credentials come from the users table, not config. Uses the classic
 *    SendSMS/* endpoints that take {username, password, ...} in the body and can
 *    also report the panel credit (SendSMS/GetCredit).
 */
class MelipayamakProvider implements SmsProviderInterface
{
    private const BASE = 'https://rest.melipayamak.com/api';

    /** @param  array<string, string|null>  $config */
    public function __construct(private readonly array $config) {}

    public function send(string $to, string $message, ?string $from = null): ?string
    {
        if ($this->usesCredentials()) {
            $response = $this->postWithCredentials('SendSMS/SendSMS', [
                'to' => $to,
                'from' => $from ?: ($this->config['sender'] ?? ''),
                'text' => $message,
                'isFlash' => false,
            ]);

            return $this->recId($response);
        }

        $response = $this->postWithKey('send/simple', [
            'from' => $from ?: ($this->config['sender'] ?? ''),
            'to' => $to,
            'text' => $message,
        ]);

        return $this->recId($response);
    }

    public function sendPattern(string $to, string $bodyId, array $variables): ?string
    {
        if ($this->usesCredentials()) {
            $response = $this->postWithCredentials('SendSMS/BaseServiceNumber', [
                'text' => implode(';', array_values($variables)),
                'to' => $to,
                'bodyId' => (int) $bodyId,
            ]);

            return $this->recId($response);
        }

        $response = $this->postWithKey('send/shared', [
            'bodyId' => (int) $bodyId,
            'to' => $to,
            'args' => array_values($variables),
        ]);

        return $this->recId($response);
    }

    public function deliveryStatus(string $recId): ?string
    {
        if ($this->usesCredentials()) {
            $response = $this->postWithCredentials('SendSMS/GetDeliveries2', ['recId' => $recId]);

            return isset($response['Value']) ? (string) $response['Value'] : null;
        }

        $response = $this->postWithKey('receive/status', ['recId' => $recId]);

        return $response['status'] ?? null;
    }

    public function credit(): ?int
    {
        if (! $this->usesCredentials()) {
            return null; // the api_key endpoints don't expose credit here
        }

        $response = $this->postWithCredentials('SendSMS/GetCredit', []);

        if (! array_key_exists('Value', $response)) {
            return null;
        }

        return (int) round((float) $response['Value']);
    }

    private function usesCredentials(): bool
    {
        return filled($this->config['username'] ?? null) && filled($this->config['password'] ?? null);
    }

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

        return $this->request($path.'/'.$key, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function postWithCredentials(string $path, array $payload): array
    {
        return $this->request($path, array_merge([
            'username' => $this->config['username'] ?? '',
            'password' => $this->config['password'] ?? '',
        ], $payload));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function request(string $path, array $payload): array
    {
        $response = Http::asJson()
            ->timeout(15)
            ->post(self::BASE.'/'.$path, $payload);

        if ($response->failed()) {
            Log::error('[sms:melipayamak] request failed', [
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('ارتباط با ملی‌پیامک ناموفق بود.');
        }

        return (array) $response->json();
    }

    /** @param  array<string, mixed>  $response */
    private function recId(array $response): ?string
    {
        // username/password mode returns {Value: <recId|errorCode>, RetStatus: 1}
        if (array_key_exists('RetStatus', $response)) {
            $status = (int) $response['RetStatus'];
            $value = $response['Value'] ?? null;

            if ($status !== 1) {
                throw new RuntimeException($this->credentialError($value));
            }

            return $value !== null ? (string) $value : null;
        }

        $recId = $response['recId'] ?? $response['value'] ?? null;

        return $recId !== null ? (string) $recId : null;
    }

    private function credentialError(mixed $value): string
    {
        return match ((string) $value) {
            '0' => 'نام کاربری یا رمز عبور پنل پیامک نادرست است.',
            '2' => 'اعتبار پنل پیامک کافی نیست.',
            '3' => 'محدودیت ارسال روزانه.',
            '6' => 'سامانه در حال بروزرسانی است؛ کمی بعد دوباره تلاش کنید.',
            '7' => 'متن پیام حاوی کلمات فیلترشده است.',
            '10' => 'شمارهٔ خط فرستنده معتبر نیست.',
            '11' => 'ارسال ناموفق بود.',
            default => 'ارسال پیامک از طریق ملی‌پیامک ناموفق بود (کد '.$value.').',
        };
    }
}
