<?php

namespace Witify\Notifications\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use Witify\Notifications\Herald\HeraldNotification as HeraldNotificationContract;
use Witify\Notifications\Herald\HeraldOptions;

/**
 * @implements Arrayable<string, mixed>
 */
readonly class HeraldNotificationDetailsData implements Arrayable, JsonSerializable
{
    public function __construct(
        public HeraldNotificationData $notification,
        public HeraldOptions $heraldOptions,
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
        return new self(
            notification: HeraldNotificationData::fromOptions($key, $heraldOptions),
            heraldOptions: $heraldOptions,
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
     *     supported_channels: array<int, string>,
     *     previews: array<string, mixed>,
     *     variables: array<string, mixed>,
     *     notification_messages: mixed
     * }
     */
    public function toArray(): array
    {
        return [
            'key' => $this->notification->key,
            'class' => $this->notification->class,
            'title' => $this->notification->title,
            'description' => $this->notification->description,
            'group' => $this->notification->group,
            'toggleable' => $this->notification->toggleable,
            'customizable' => $this->notification->customizable,
            'supported_channels' => $this->notification->supportedChannels,
            'previews' => $this->heraldOptions->getPreviewData(),
            'variables' => $this->heraldOptions->getPreviewVariables(),
            'notification_messages' => $this->heraldOptions->getNotificationMessages(),
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
     *     supported_channels: array<int, string>,
     *     previews: array<string, mixed>,
     *     variables: array<string, mixed>,
     *     notification_messages: mixed
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
