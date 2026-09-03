<?php

namespace App\Services\Messenger;

use App\Models\Setting;
use App\Services\Messenger\Channels\LogChannel;
use App\Services\Messenger\Channels\NullChannel;
use Throwable;

/**
 * The single entry point the rest of the app uses for messenger sends
 * (docs/starter.md §91) — bulk delivery to بله / ایتا / واتساپ. Mirrors
 * App\Services\Sms\SmsManager: resolves the configured channel driver, tells
 * callers which channels are enabled, resolves the per-recipient tariff, and
 * normalises recipients. Bound as a singleton in AppServiceProvider.
 */
class MessengerManager
{
    /** @param  string  $driver  log | null | http (config('messenger.driver')) */
    public function __construct(private readonly string $driver = 'log') {}

    /** Master switch: config('messenger.enabled') overlaid by the `messenger_enabled` setting. */
    public function enabled(): bool
    {
        return (bool) $this->setting('messenger_enabled', config('messenger.enabled', true));
    }

    /**
     * Is this channel usable for a bulk send right now? Master switch on, the key
     * exists in the registry, its `bulk` capability is set, and its own
     * `messenger_<key>_enabled` toggle is on.
     */
    public function channelEnabled(string $key): bool
    {
        $config = $this->channelConfig($key);

        if ($config === [] || ! $this->enabled() || ! ($config['bulk'] ?? false)) {
            return false;
        }

        return (bool) $this->setting("messenger_{$key}_enabled", true);
    }

    /** @return array<int, string> Enabled channel keys, in registry order. */
    public function availableChannels(): array
    {
        return array_values(array_filter(
            array_keys((array) config('messenger.channels', [])),
            fn (string $key) => $this->channelEnabled($key),
        ));
    }

    /**
     * Build the channel driver for a key. "log"/"null" transports return the
     * credential-free / no-op channels for every key; "http" uses the registry's
     * driver class. Mirrors AppServiceProvider::bindSmsProvider().
     */
    public function channel(string $key): MessengerChannelInterface
    {
        $config = $this->channelConfig($key);

        if ($config === []) {
            throw new MessengerChannelNotConfiguredException("کانال پیام‌رسان «{$key}» تعریف نشده است.");
        }

        return match ($this->driver) {
            'null' => new NullChannel($key, $config),
            'log' => new LogChannel($key, $config),
            default => $this->httpChannel($key, $config),
        };
    }

    public function label(string $key): string
    {
        $config = $this->channelConfig($key);

        return (string) $this->setting("messenger_{$key}_label", $config['label'] ?? $key);
    }

    /** Per-recipient price in Toman: the `messenger_<key>_tariff` setting, else the config default. */
    public function tariffFor(string $key): int
    {
        $fallback = (int) ($this->channelConfig($key)['tariff'] ?? 0);
        $value = $this->setting("messenger_{$key}_tariff", null);

        return is_numeric($value) ? max(0, (int) $value) : $fallback;
    }

    /** `mobile` for an Iranian mobile, `chat` for a numeric chat id / @username. */
    public function classify(string $recipient): string
    {
        return preg_match('/^(0|\+?98)?9\d{9}$/', preg_replace('/[\s-]+/', '', $recipient) ?? '')
            ? 'mobile'
            : 'chat';
    }

    /** Normalise one recipient: mobiles to local 09xxxxxxxxx, chat ids/usernames trimmed. */
    public function normalizeRecipient(string $recipient): string
    {
        $recipient = trim($recipient);

        return $this->classify($recipient) === 'mobile'
            ? normalize_mobile($recipient)
            : $recipient;
    }

    /** @return array<string, mixed> */
    public function channelConfig(string $key): array
    {
        return (array) config("messenger.channels.{$key}", []);
    }

    /** @param  array<string, mixed>  $config */
    private function httpChannel(string $key, array $config): MessengerChannelInterface
    {
        $class = $config['driver'] ?? null;

        if (! $class || ! class_exists($class)) {
            throw new MessengerChannelNotConfiguredException("درایور کانال «{$key}» یافت نشد.");
        }

        return new $class($key, $config);
    }

    private function setting(string $key, mixed $default): mixed
    {
        try {
            $value = Setting::get($key);
        } catch (Throwable) {
            return $default;
        }

        return $value === null || $value === '' ? $default : $value;
    }
}
