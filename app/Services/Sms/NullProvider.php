<?php

namespace App\Services\Sms;

use App\Jobs\SendSmsJob;

/**
 * Does nothing. Bound in the test environment so the suite never touches a
 * gateway; assertions go through Bus::fake() on {@see SendSmsJob}
 * or a spy swapped in for this binding.
 */
class NullProvider implements SmsProviderInterface
{
    public function send(string $to, string $message, ?string $from = null): ?string
    {
        return null;
    }

    public function sendPattern(string $to, string $bodyId, array $variables): ?string
    {
        return null;
    }

    public function deliveryStatus(string $recId): ?string
    {
        return null;
    }

    public function messages(int $location, int $index = 0, int $count = 100, ?string $from = null): array
    {
        return [];
    }

    public function numbers(): array
    {
        return [];
    }

    public function credit(): ?int
    {
        return null;
    }

    public function creditRial(): ?int
    {
        return null;
    }
}
