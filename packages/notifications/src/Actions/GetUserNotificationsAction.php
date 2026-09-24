<?php

namespace Witify\Notifications\Actions;

use Illuminate\Support\Collection;
use Witify\Notifications\Herald\Herald;
use Witify\Notifications\Herald\HeraldNotification;
use Witify\Notifications\Herald\HeraldUser;
use Witify\Support\Action\Action;

class GetUserNotificationsAction implements Action
{
    public const CHANNEL_MAIL = 'mail';

    public const CHANNEL_DATABASE = 'database';

    public const CHANNELS = [
        self::CHANNEL_MAIL,
        self::CHANNEL_DATABASE,
    ];

    public function __construct(
        private HeraldUser $user,
    ) {}

    /**
     * @return array<int, string>
     */
    public function enabledChannels(string $name): array
    {
        $notification = $this->notification($name);

        if (! $notification) {
            return [];
        }

        $enabledChannels = [];

        foreach ($this->notificationData($notification[0], $notification[1])['channels'] as $channel => $enabled) {
            if ($enabled) {
                $enabledChannels[] = $channel;
            }
        }

        return $enabledChannels;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function toggleableList(): Collection
    {
        return $this->handle()
            ->filter(fn (array $notification): bool => $notification['class']::herald()->toggleable)
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function handle(): Collection
    {
        return collect(Herald::notifications())
            ->map(fn (string $class, string $key): array => $this->notificationData($key, $class))
            ->values();
    }

    /**
     * @return array<string, bool>
     */
    private function defaultChannels(): array
    {
        return [
            self::CHANNEL_MAIL => true,
            self::CHANNEL_DATABASE => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $channels
     * @return array<string, bool>
     */
    private function normalizeChannels(array $channels): array
    {
        return collect(self::CHANNELS)
            ->mapWithKeys(fn (string $channel): array => [
                $channel => filter_var($channels[$channel] ?? true, FILTER_VALIDATE_BOOLEAN),
            ])
            ->toArray();
    }

    /**
     * @return array{0: string, 1: class-string<HeraldNotification>}|null
     */
    private function notification(string $name): ?array
    {
        foreach (Herald::notifications() as $key => $class) {
            if ($key === $name || $class === $name) {
                return [$key, $class];
            }
        }

        return null;
    }

    /**
     * @param  class-string<HeraldNotification>  $class
     * @return array<string, mixed>
     */
    private function notificationData(string $key, string $class): array
    {
        $herald = $class::herald();
        $storedSetting = collect($this->user->getNotificationSettings())
            ->firstWhere('key', $key);
        $storedChannels = is_array($storedSetting) ? $storedSetting['channels'] : null;
        $channels = is_array($storedChannels) ? $storedChannels : $this->defaultChannels();

        if (! $herald->toggleable) {
            $channels = $this->defaultChannels();
        }

        return [
            'key' => $key,
            'class' => $class,
            'title' => $herald->title,
            'description' => $herald->description,
            'group' => $herald->group,
            'channels' => $this->normalizeChannels($channels),
            'supported_channels' => $herald->supportedChannels(),
        ];
    }
}
