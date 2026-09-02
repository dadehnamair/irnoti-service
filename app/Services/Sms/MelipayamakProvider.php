<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Melipayamak REST driver (docs/starter.md §13). Credentials come from
 * config('services.sms.melipayamak') — see .env. Endpoints:
 *   - send/simple/{apiKey}         {from, to, text}          → {recId, status}
 *   - send/shared/{apiKey}         {bodyId, to, args:[...]}   → {recId, status}
 *   - receive/status/{apiKey}      {recId}                    → {status}
 */
class MelipayamakProvider implements SmsProviderInterface
{
    private const BASE = 'https://rest.melipayamak.com/api';

    /** @param  array<string, string|null>  $config */
    public function __construct(private readonly array $config) {}

    public function send(string $to, string $message, ?string $from = null): ?string
    {
        $response = $this->post('send/simple', [
            'from' => $from ?: ($this->config['sender'] ?? ''),
            'to' => $to,
            'text' => $message,
        ]);

        return $this->recId($response);
    }

    public function sendPattern(string $to, string $bodyId, array $variables): ?string
    {
        $response = $this->post('send/shared', [
            'bodyId' => (int) $bodyId,
            'to' => $to,
            'args' => array_values($variables),
        ]);

        return $this->recId($response);
    }

    public function deliveryStatus(string $recId): ?string
    {
        $response = $this->post('receive/status', ['recId' => $recId]);

        return $response['status'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload): array
    {
        $key = $this->config['api_key'] ?? null;

        if (blank($key)) {
            throw new RuntimeException('پیکربندی ملی‌پیامک ناقص است (MELIPAYAMAK_API_KEY تنظیم نشده).');
        }

        $response = Http::asJson()
            ->timeout(15)
            ->post(self::BASE.'/'.$path.'/'.$key, $payload);

        if ($response->failed()) {
            Log::error('[sms:melipayamak] request failed', [
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('ارسال پیامک از طریق ملی‌پیامک ناموفق بود.');
        }

        return (array) $response->json();
    }

    /** @param  array<string, mixed>  $response */
    private function recId(array $response): ?string
    {
        $recId = $response['recId'] ?? $response['value'] ?? null;

        return $recId !== null ? (string) $recId : null;
    }
}
