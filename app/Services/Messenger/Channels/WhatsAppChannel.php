<?php

namespace App\Services\Messenger\Channels;

use App\Services\Messenger\MessengerChannelNotConfiguredException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * واتساپ — WhatsApp Cloud API (docs/starter.md §91). One request per recipient:
 * POST {base_url}/{phone_id}/messages, Bearer token, a plain text message. `to`
 * is an E.164-ish number without the leading «+»; mobiles are handed over in the
 * local 09xxxxxxxxx form, so 0 -> 98 here.
 */
class WhatsAppChannel extends AbstractHttpChannel
{
    protected function sendOne(string $to, string $body, array $options): ?string
    {
        $response = Http::withToken($this->token())
            ->asJson()
            ->timeout(15)
            ->post($this->baseUrl().'/'.$this->phoneId().'/messages', [
                'messaging_product' => 'whatsapp',
                'to' => $this->e164($to),
                'type' => 'text',
                'text' => ['body' => $body],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('واتساپ: '.$response->status().' — '.mb_substr($response->body(), 0, 180));
        }

        return $response->json('messages.0.id');
    }

    protected function assertConfigured(): void
    {
        parent::assertConfigured();

        if (blank($this->phoneId())) {
            throw new MessengerChannelNotConfiguredException('کانال «واتساپ» پیکربندی نشده است (شناسهٔ شماره تنظیم نشده).');
        }
    }

    private function phoneId(): ?string
    {
        return $this->config['phone_id'] ?? null;
    }

    private function e164(string $to): string
    {
        if (str_starts_with($to, '09') && strlen($to) === 11) {
            return '98'.substr($to, 1);
        }

        return ltrim($to, '+');
    }
}
