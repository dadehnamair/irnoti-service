<?php

namespace App\Services\Sms\Phonebook\Concerns;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Shared plumbing for the api.payamak-panel.com ASMX phonebook web service
 * (docs/starter.md §13). Same host and request style as
 * {@see \App\Services\Sms\MelipayamakProvider} — plain form fields in, a bare
 * XML scalar or a repeating record list back.
 */
trait ParsesAsmxResponses
{
    private const BASE = 'https://api.payamak-panel.com/post';

    /**
     * POST a form to an ASMX method and return its raw XML body.
     *
     * @param  array<string, mixed>  $params
     */
    private function asmxPost(string $method, array $params): string
    {
        return $this->asmxCall('post', $method, $params);
    }

    /**
     * GET an ASMX method (SendSmsToContact only — the docs describe it as GET).
     *
     * @param  array<string, mixed>  $params
     */
    private function asmxGet(string $method, array $params): string
    {
        return $this->asmxCall('get', $method, $params);
    }

    /** @param  array<string, mixed>  $params */
    private function asmxCall(string $verb, string $method, array $params): string
    {
        $payload = array_merge([
            'username' => $this->config['username'] ?? '',
            'password' => $this->config['password'] ?? '',
        ], $params);

        try {
            $request = Http::asForm()->timeout(20)->acceptJson();
            $url = self::BASE.'/'.$method;
            $response = $verb === 'get' ? $request->get($url, $payload) : $request->post($url, $payload);
        } catch (\Throwable $e) {
            Log::error('[sms:phonebook] connection failed', ['method' => $method, 'error' => $e->getMessage()]);

            throw new RuntimeException('اتصال به سرور ملی‌پیامک برقرار نشد: '.$e->getMessage());
        }

        Log::debug('[sms:phonebook] '.$method, [
            'sent' => ['username' => $payload['username']] + array_diff_key($payload, ['username' => 1, 'password' => 1]),
            'http' => $response->status(),
            'value' => mb_substr($response->body(), 0, 300),
        ]);

        if ($response->failed()) {
            throw new RuntimeException(sprintf(
                'ملی‌پیامک با کد %d پاسخ داد: %s',
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
     * Every `<RecordTag>…</RecordTag>` block in an ASMX list response, each
     * flattened to `[field => inner text]` for the requested fields.
     *
     * @param  array<int, string>  $fields
     * @return array<int, array<string, string>>
     */
    private function xmlRecords(string $body, string $recordTag, array $fields): array
    {
        if (! preg_match_all('~<'.$recordTag.'[^>]*>(.*?)</'.$recordTag.'>~s', $body, $blocks)) {
            return [];
        }

        $rows = [];

        foreach ($blocks[1] as $block) {
            $row = [];

            foreach ($fields as $field) {
                $row[$field] = preg_match('~<'.$field.'[^>]*>(.*?)</'.$field.'>~s', $block, $m)
                    ? trim(html_entity_decode($m[1]))
                    : '';
            }

            $rows[] = $row;
        }

        return $rows;
    }
}
