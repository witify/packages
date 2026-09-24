<?php

namespace Witify\Notifications\Herald;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Registry of the Herald notifications of the application and resolvers for
 * the values the package takes from the host. The application fills it in a
 * service provider:
 *
 *     Herald::register([
 *         'order_shipped' => OrderShippedNotification::class,
 *         InvoicePaidNotification::class, // key derived: invoice_paid
 *     ]);
 *
 * The key names the notification in the user settings, so it must not change
 * once settings are stored.
 */
final class Herald
{
    /** @var array<string, class-string<HeraldNotification>> */
    private static array $notifications = [];

    private static ?Closure $timezoneResolver = null;

    /**
     * @param  array<int|string, class-string<HeraldNotification>>  $notifications
     */
    public static function register(array $notifications): void
    {
        foreach ($notifications as $key => $class) {
            if (! is_string($key)) {
                $key = self::deriveKey($class);
            }

            if (isset(self::$notifications[$key]) && self::$notifications[$key] !== $class) {
                throw new InvalidArgumentException("Herald notification key already registered: {$key}");
            }

            self::$notifications[$key] = $class;
        }
    }

    /**
     * @return array<string, class-string<HeraldNotification>>
     */
    public static function notifications(): array
    {
        return self::$notifications;
    }

    /**
     * @return list<class-string<HeraldNotification>>
     */
    public static function classes(): array
    {
        return array_values(self::$notifications);
    }

    public static function keyOf(string $class): ?string
    {
        $key = array_search($class, self::$notifications, true);

        return is_string($key) ? $key : null;
    }

    /**
     * @return class-string<HeraldNotification>|null
     */
    public static function classOf(string $key): ?string
    {
        return self::$notifications[$key] ?? null;
    }

    public static function forget(): void
    {
        self::$notifications = [];
        self::$timezoneResolver = null;
    }

    public static function forgetResolvers(): void
    {
        self::$timezoneResolver = null;
    }

    /**
     * @param  Closure(): (string|null)  $resolver
     */
    public static function resolveTimezoneUsing(Closure $resolver): void
    {
        self::$timezoneResolver = $resolver;
    }

    /**
     * Timezone of the recipients that do not carry their own.
     */
    public static function timezone(): string
    {
        $resolved = self::$timezoneResolver === null ? null : (self::$timezoneResolver)();

        if (is_string($resolved) && $resolved !== '') {
            return $resolved;
        }

        return (string) config('notifications.timezone', 'UTC');
    }

    /**
     * Locales the notifications are written in.
     *
     * @return list<string>
     */
    public static function locales(): array
    {
        $locales = config('notifications.locales') ?? config('app.locales') ?? [config('app.locale', 'en')];

        if (! is_array($locales)) {
            return [(string) config('app.locale', 'en')];
        }

        // `app.locales` maps codes to labels; a plain list is accepted too.
        return array_map(
            fn ($key, $value): string => is_string($key) ? $key : (string) $value,
            array_keys($locales),
            $locales,
        );
    }

    /**
     * @return class-string<Model>
     */
    public static function userModel(): string
    {
        $model = config('notifications.user_model') ?? config('auth.providers.users.model');

        if (! is_string($model) || ! is_subclass_of($model, Model::class)) {
            throw new InvalidArgumentException('notifications.user_model must name an Eloquent model.');
        }

        return $model;
    }

    private static function deriveKey(string $class): string
    {
        return Str::snake((string) preg_replace('/Notification$/', '', class_basename($class)));
    }
}
