<?php

namespace App\Services\Messenger\Channels;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * بله — Bot-API style delivery (docs/starter.md §91). One request per recipient:
 * POST {base_url}/bot{token}/sendMessage with chat_id + text. `chat_id` is the
 * recipient string as supplied (a numeric chat id, an @username, or — when
 * base_url points at an aggregator that accepts them — a mobile number).
 */
class BaleChannel extends AbstractHttpChannel
{
    protected function sendOne(string $to, string $body, array $options): ?string
    {
        $response = Http::asJson()
            ->timeout(15)
            ->post($this->baseUrl().'/bot'.$this->token().'/sendMessage', [
                'chat_id' => $to,
                'text' => $body,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('بله: '.$response->status().' — '.mb_substr($response->body(), 0, 180));
        }

        $data = $response->json();

        if (! ($data['ok'] ?? false)) {
            throw new RuntimeException('بله: '.($data['description'] ?? 'پاسخ نامعتبر'));
        }

        return isset($data['result']['message_id']) ? (string) $data['result']['message_id'] : null;
    }
}
