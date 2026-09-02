<?php

namespace App\Marketplace\Handlers\IrPlus;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Real ایرپلاس REST client: bearer-token auth against the agency's account
 * (docs/starter.md §13). Endpoint shape is configurable via
 * config('marketplace.irplus.*'); selected by driver = 'http'.
 */
class HttpIrPlusClient implements IrPlusClient
{
    /** @param array{api_key: string, agency_code?: string, base_url?: string} $config */
    public function __construct(private readonly array $config) {}

    public function groups(): array
    {
        return array_map(fn (array $g) => [
            'external_id' => (string) ($g['id'] ?? $g['code'] ?? ''),
            'name' => (string) ($g['name'] ?? $g['title'] ?? ''),
            'count' => (int) ($g['count'] ?? $g['passengers_count'] ?? 0),
        ], $this->get('/api/v1/groups')['data'] ?? []);
    }

    public function passengers(?string $groupExternalId = null): array
    {
        $rows = $this->get('/api/v1/passengers', array_filter([
            'group' => $groupExternalId,
        ]))['data'] ?? [];

        return array_values(array_filter(array_map(fn (array $p) => [
            'first_name' => $p['first_name'] ?? $p['firstname'] ?? null,
            'last_name' => $p['last_name'] ?? $p['lastname'] ?? null,
            'mobile' => normalize_mobile((string) ($p['mobile'] ?? $p['phone'] ?? '')),
            'group_external_ids' => array_map('strval', (array) ($p['groups'] ?? $p['group_ids'] ?? [])),
            'meta' => array_filter([
                'passport' => $p['passport'] ?? null,
                'national_code' => $p['national_code'] ?? null,
            ]),
        ], $rows), fn (array $p) => $p['mobile'] !== ''));
    }

    private function get(string $path, array $query = []): array
    {
        $base = rtrim($this->config['base_url'] ?? (string) config('marketplace.irplus.base_url'), '/');

        $response = Http::asJson()
            ->timeout((int) config('marketplace.irplus.timeout', 15))
            ->withToken($this->config['api_key'])
            ->withHeaders(array_filter(['X-Agency-Code' => $this->config['agency_code'] ?? null]))
            ->get($base.$path, $query);

        if ($response->failed()) {
            throw new RuntimeException('اتصال به ایرپلاس ناموفق بود (کد '.$response->status().').');
        }

        return (array) $response->json();
    }
}
