<?php

namespace Witify\Notifications\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * @implements Arrayable<int, array{key: string, channels: array<string, bool>}>
 */
readonly class UserNotificationSettingsData implements Arrayable, JsonSerializable
{
    /**
     * @param  array<int, array{key: string, channels: array<string, bool>}>  $settings
     */
    private function __construct(
        private array $settings,
    ) {}

    /**
     * @param  array<int, array{key: string, channels: array<string, bool>}>  $settings
     */
    public static function fromArray(array $settings): self
    {
        return new self($settings);
    }

    /**
     * @return array<int, array{key: string, channels: array<string, bool>}>
     */
    public function toArray(): array
    {
        return $this->settings;
    }

    /**
     * @return array<int, array{key: string, channels: array<string, bool>}>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
