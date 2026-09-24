# witify/notifications

Herald, the notification layer of the Witify Laravel applications: notifications described once with `HeraldOptions` (title, group, channels, variables, texts, previews, schedule), previewed and edited by the administrators, filtered by the channel settings of each user, delivered by mail and to an in-app inbox. PHP and Vue ship in the same package: the host consumes the Vue sources from `vendor/` through a Vite alias.

Depends on [witify/support](https://github.com/witify/support) for the base classes, `MailMessage` and `DatabaseChannel`.

## Requirements

| | |
|---|---|
| PHP | 8.2 to 8.4 |
| Laravel | 11 and 12 |
| Database | the `notifications` and `notification_messages` tables (package migrations), plus `notification_settings` (json) and `timezone` columns on the users table (application migration) |
| Frontend | Vue 3, vue-router, pinia, vue-i18n, luxon, lodash and sprintify-ui from the host's `package.json` |

`spatie/laravel-query-builder` is installed with the package.

## Installation

```bash
composer require witify/notifications
php artisan migrate
```

The service provider is discovered automatically. It loads the migrations (skipped when the tables already exist), the `notifications::` translations, the API routes, the `herald:notification-schedule` command and the policy of the `Notification` model.

### 1. The user model

The user implements `HeraldUser`: how Herald addresses them and where their channel settings live.

```php
use Witify\Notifications\Herald\HeraldNotifiable;
use Witify\Notifications\Herald\HeraldUser;

class User extends Authenticatable implements HeraldUser
{
    protected $casts = ['notification_settings' => 'array'];

    public function heraldNotifiable(): HeraldNotifiable
    {
        return new HeraldNotifiable(
            id: 'user' . $this->id,
            full_name: $this->full_name,
            first_name: $this->first_name,
            last_name: $this->last_name,
            email: $this->email,
            phone: $this->phone,
            locale: $this->locale ?? 'en',
            timezone: $this->timezone,
            user: $this,
        );
    }

    public function getNotificationSettings(): array
    {
        return $this->notification_settings ?? [];
    }

    public function setNotificationSettings(array $settings): void
    {
        $this->notification_settings = $settings;
        $this->save();
    }
}
```

The package reads the model from `notifications.user_model`, or from the default auth provider.

### 2. Register the notifications

The application lists its Herald notifications in a service provider. The key names the notification in the user settings: keep it stable once settings are stored.

```php
use Witify\Notifications\Herald\Herald;

Herald::register([
    'reset_password' => ResetPasswordNotification::class,
    'order_shipped' => OrderShippedNotification::class,
    InvoicePaidNotification::class, // key derived from the class name: invoice_paid
]);

// Recipients without a timezone take the one of the application settings.
Herald::resolveTimezoneUsing(fn (): ?string => settings()->timezone);
```

### 3. Routes and middleware

The API routes are registered under `api/` with the middleware of the `notifications.routes` config. **Restrict the editor to your administrators** before deploying:

```php
'routes' => [
    'prefix' => 'api',
    'middleware' => ['web', 'auth'],
    'admin_middleware' => ['web', 'auth', 'role:admin'],
],
```

| Route | Name | Group |
|---|---|---|
| `GET api/notifications` | `api.notifications.index` | inbox of the authenticated user, `filter[read]`, `sort`, `per_page` |
| `PATCH api/notifications/{notification}` | `api.notifications.update` | mark as read |
| `GET api/herald-notifications` | `api.herald_notifications.index` | admin |
| `POST api/herald-notifications/{class}` | `api.herald_notifications.show` | admin, previews and texts |
| `PATCH api/notification-messages/batch` | `api.notification_messages.update.batch` | admin |
| `DELETE api/notification-messages` | `api.notification_messages.destroy` | admin |

### 4. Schedule

Herald notifications with a `schedule()` are sent by the command, once a day per timezone found on the users table:

```php
Schedule::command('herald:notification-schedule')->everyMinute();
```

### 5. The Vue side

The pages live in `vendor/witify/notifications/resources/js`. The host resolves them through an alias and injects the services the package cannot import.

`vite.config.ts`:

```ts
resolve: {
  alias: {
    "@witify/notifications": path.resolve(__dirname, "./vendor/witify/notifications/resources/js"),
  },
},
// vue-i18n plugin
include: [
  path.resolve(__dirname, "./lang/**"),
  path.resolve(__dirname, "./vendor/witify/notifications/resources/js/lang/**"),
],
```

`tsconfig.json`:

```json
"paths": {
  "@witify/notifications": ["./vendor/witify/notifications/resources/js"],
  "@witify/notifications/*": ["./vendor/witify/notifications/resources/js/*"]
}
```

`tailwind.config.js`, or the classes of the pages are purged:

```js
content: ["./vendor/witify/*/resources/js/**/*.vue"],
```

`main.ts`:

```ts
import {
  configureNotifications,
  notificationModule,
  notificationPreviewModule,
} from "@witify/notifications";

configureNotifications({
  http,
  echo,
  currentUserId: () => useUserStore().user?.id ?? null,
});

createSprintifyApp(App, { router, i18n, modules: [notificationModule, notificationPreviewModule, ...] });
```

The package exports `useAppNotificationsStore` for the bell of the layout, `NotificationPreviewIndex` for the settings tab of the admin router, and `HeraldNotificationDispatcher` for the screens that send a notification to chosen recipients.

The Vue sources use the globals the host auto-imports (`ref`, `computed`, `useI18n`, `useHead`, `useRouter`, `window.route`) and the `Base*` components of sprintify-ui, resolved by the host's Vite plugins. Deploy with `composer install` before `npm run build`.

## Writing a notification

```php
use Illuminate\Notifications\Notification;
use Witify\Notifications\Herald\HeraldNotifiable;
use Witify\Notifications\Herald\HeraldNotification;
use Witify\Notifications\Herald\HeraldNotificationTrait;
use Witify\Notifications\Herald\HeraldOptions;
use Witify\Notifications\Herald\NotificationMessageBuilder;
use Witify\Notifications\Herald\NotificationPreview;

class OrderShippedNotification extends Notification implements HeraldNotification
{
    use HeraldNotificationTrait;

    public function __construct(public Order $order) {}

    public static function herald(): HeraldOptions
    {
        return HeraldOptions::make(self::class)
            ->title(__('notifications.order_shipped.title'))
            ->description(__('notifications.order_shipped.description'))
            ->group(__('notifications.groups.sales'))
            ->toggleable(true)
            ->customizable(true)
            ->variables(fn (?HeraldNotification $notification, HeraldNotifiable $notifiable): array => [
                'order' => ['number' => $notification?->order->number ?? 'ORD-1'],
            ])
            ->mail(fn () => NotificationMessageBuilder::mail()
                ->subject('Order :order.number shipped')
                ->message('<p>Hello :first_name, your order :order.number is on its way.</p>'))
            ->database(fn (?HeraldNotification $notification, HeraldNotifiable $notifiable) => NotificationMessageBuilder::database()
                ->message('Order :order.number shipped')
                ->model($notification?->order)
                ->path('orders/:order.number'))
            ->previews(fn (): array => [
                new NotificationPreview(new self(Order::factory()->make()), 'Shipped'),
            ]);
    }
}
```

- `variables()` feeds the `:placeholders` of the texts; `first_name`, `full_name`, `email`, `company_name` and `me` (the sender) are always available.
- `toggleable(true)` lets each user enable or disable the channels; `customizable(true)` lets the administrators rewrite the texts per locale and channel from the settings page.
- `throttle()` and `uniqueKey()` limit a notification to one per day and per key; `attachments()` adds files to the mail; `schedule()->at('08:00')->items(...)` sends it daily at a time in the recipient's timezone.

Send it as any Laravel notification: `$user->notify(new OrderShippedNotification($order))`, or through `HeraldNotificationDispatch` from a controller when the user picks the recipients and previews the texts first.

## Configuration

```bash
php artisan vendor:publish --tag="notifications-config"
php artisan vendor:publish --tag="notifications-translations"
```

| Key | Default | Purpose |
|---|---|---|
| `user_model` | `null`, then the auth provider | the `HeraldUser` model |
| `timezone` | `env('HERALD_TIMEZONE', 'UTC')` | fallback timezone, after `Herald::resolveTimezoneUsing()` |
| `locales` | `null`, then `app.locales` | locales the texts are written in |
| `developer.*` | `DEVELOPER_*` env | recipient of `DeveloperNotifiable` |
| `routes.enabled`, `routes.prefix`, `routes.middleware`, `routes.admin_middleware` | see above | the API routes |

## Migrating an application from the modules

1. Delete `modules/Notification` and `modules/NotificationPreview`, their two migrations in `database/migrations` (the package ships them, guarded by `Schema::hasTable`) and the `Notifications` enum.
2. `Herald::register([...])` with the cases of the enum as keys, in a service provider.
3. The user model implements `HeraldUser`.
4. Replace the imports: `Modules\NotificationPreview\Herald\*` and `Modules\Notification\*` become `Witify\Notifications\*` (`Actions\User\GetUserNotificationsAction` becomes `Actions\GetUserNotificationsAction`).
5. Wire the Vue side as described above; the admin router imports `NotificationPreviewIndex` from `@witify/notifications` and the layout imports `useAppNotificationsStore` and `notificationModule`.
6. `role:admin` or the equivalent middleware goes into `notifications.routes.admin_middleware`.

## Changelog and upgrades

See the [CHANGELOG](https://github.com/witify/packages/blob/main/CHANGELOG.md) of the monorepo. Every package shares the same version number.

## Contributing

This repository is a read-only split of [witify/packages](https://github.com/witify/packages). Open pull requests there, in `packages/notifications`.
