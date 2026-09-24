<?php

namespace Witify\Notifications\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Witify\Notifications\Herald\HeraldNotification as HeraldNotificationContract;
use Witify\Notifications\Herald\HeraldOptions;

/**
 * @implements Arrayable<string, mixed>
 */
readonly class HeraldNotificationData implements Arrayable, JsonSerializable
{
    /**
     * @param  class-string<HeraldNotificationContract>  $class
     * @param  array<int, string>  $supportedChannels
     */
    public function __construct(
        public string $key,
        public string $class,
        public string $title,
        public ?string $description,
        public string $group,
        public bool $toggleable,
        public bool $customizable,
        public array $supportedChannels,
    ) {}

    /**
     * @param  class-string<HeraldNotificationContract>  $notificationClass
     */
    public static function fromClass(string $key, string $notificationClass): self
    {
        return self::fromOptions(
            key: $key,
            heraldOptions: $notificationClass::herald(),
        );
    }

    public static function fromOptions(string $key, HeraldOptions $heraldOptions): self
    {
        /** @var class-string<HeraldNotificationContract> $notificationClass */
        $notificationClass = $heraldOptions->notificationClass;

        return new self(
            key: $key,
            class: $notificationClass,
            title: $heraldOptions->title,
            description: $heraldOptions->description,
            group: $heraldOptions->group,
            toggleable: $heraldOptions->toggleable,
            customizable: $heraldOptions->customizable,
            supportedChannels: $heraldOptions->supportedChannels(),
        );
    }

    /**
     * @return array{
     *     key: string,
     *     class: class-string<HeraldNotificationContract>,
     *     title: string,
     *     description: string|null,
     *     group: string,
     *     toggleable: bool,
     *     customizable: bool,
     *     supported_channels: array<int, string>
     * }
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'class' => $this->class,
            'title' => $this->title,
            'description' => $this->description,
            'group' => $this->group,
            'toggleable' => $this->toggleable,
            'customizable' => $this->customizable,
            'supported_channels' => $this->supportedChannels,
        ];
    }

    /**
     * @return array{
     *     key: string,
     *     class: class-string<HeraldNotificationContract>,
     *     title: string,
     *     description: string|null,
     *     group: string,
     *     toggleable: bool,
     *     customizable: bool,
     *     supported_channels: array<int, string>
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
