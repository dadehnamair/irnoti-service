<?php

namespace App\Marketplace\Handlers\IrPlus;

/**
 * Credential-free ایرپلاس driver for local dev and tests (the real service has no
 * public sandbox). Returns a small, stable sample directory so the whole install
 * → sync → group-SMS flow is exercisable. Selected by
 * config('marketplace.irplus.driver') = 'fake'.
 */
class FakeIrPlusClient implements IrPlusClient
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config = []) {}

    public function groups(): array
    {
        return [
            ['external_id' => 'vip', 'name' => 'مسافران ویژه', 'count' => 2],
            ['external_id' => 'hajj', 'name' => 'کاروان عتبات', 'count' => 3],
            ['external_id' => 'europe', 'name' => 'تور اروپا', 'count' => 1],
        ];
    }

    public function passengers(?string $groupExternalId = null): array
    {
        $all = [
            ['first_name' => 'مریم', 'last_name' => 'کریمی', 'mobile' => '09121110001', 'group_external_ids' => ['vip'], 'meta' => ['passport' => 'A1234567']],
            ['first_name' => 'رضا', 'last_name' => 'موسوی', 'mobile' => '09121110002', 'group_external_ids' => ['vip', 'europe'], 'meta' => ['passport' => 'B7654321']],
            ['first_name' => 'زهرا', 'last_name' => 'احمدی', 'mobile' => '09121110003', 'group_external_ids' => ['hajj'], 'meta' => []],
            ['first_name' => 'حسین', 'last_name' => 'نجفی', 'mobile' => '09121110004', 'group_external_ids' => ['hajj'], 'meta' => []],
            ['first_name' => 'فاطمه', 'last_name' => 'رحیمی', 'mobile' => '09121110005', 'group_external_ids' => ['hajj'], 'meta' => []],
        ];

        return $groupExternalId === null
            ? $all
            : array_values(array_filter(
                $all,
                fn (array $p) => in_array($groupExternalId, $p['group_external_ids'], true),
            ));
    }
}
