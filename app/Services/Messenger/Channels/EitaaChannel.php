<?php

namespace App\Services\Messenger\Channels;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * ایتا — eitaayar-style delivery (docs/starter.md §91). One request per
 * recipient: POST {base_url}/{token}/sendMessage with chat_id + text. `chat_id`
 * is the recipient string as supplied (a channel/@username, or — when base_url
 * points at an aggregator that accepts them — a mobile number).
 */
class EitaaChannel extends AbstractHttpChannel
{
    protected function sendOne(string $to, string $body, array $options): ?string
    {
        $response = Http::asMultipart()
            ->timeout(15)
            ->post($this->baseUrl().'/'.$this->token().'/sendMessage', [
                'chat_id' => $to,
                'text' => $body,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('ایتا: '.$response->status().' — '.mb_substr($response->body(), 0, 180));
        }

        $data = $response->json();

        if (! ($data['ok'] ?? false)) {
            throw new RuntimeException('ایتا: '.($data['description'] ?? 'پاسخ نامعتبر'));
        }

        return isset($data['result']['message_id']) ? (string) $data['result']['message_id'] : null;
    }
}
