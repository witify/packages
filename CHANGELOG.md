# Changelog

All notable changes to the packages are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and every package shares the same version number.

## [Unreleased]

### Fixed

- `witify/notifications`: the two migrations carry the names the applications already recorded when the module was local (`2023_04_20_173507_create_notifications_table`, `2025_08_05_191344_create_notification_messages`). Dated 2026-09-24, they ran after any application migration that touches the `notifications` table, which broke every fresh database (tests, CI, new installs); named as before, they sort first and an existing database counts them as run.

## [1.4.0] - 2026-09-24

### Added

- `witify/support`: `IsResourceTrait` gives a default `getResourceColor()` of `gray`, so a model only declares its color when it has one. `BelongsToSearchConstraint` accepts `Expression` columns.

### Changed

- `witify/support`: `search()` treats the keyword as a literal (its `%` and `_` are escaped, with the escape character bound so SQLite behaves like MySQL), no longer wraps the columns in `LOWER()` (the collation already ignores case and `LOWER()` defeated the indexes) and no longer replaces the builder with the return value of a callback. Reconciled from agnico.
- `witify/support`: the password confirmation counts the wrong attempts per user and per action, and a right password wipes the count. Reconciled from agnico.

## [1.3.1] - 2026-09-24

### Fixed

- `witify/notifications`: marking a notification as read failed with a mass assignment exception; the controller now uses `markAsRead()`.
- `witify/devops`: the Echo test of the console threw `Echo is not a constructor` with the browser build of laravel-echo 2, which exposes the class as its default export.

## [1.3.0] - 2026-09-24

### Added

- `witify/notifications`: Herald, extracted from the `Notification` and `NotificationPreview` modules of Sprintify, PHP and Vue in one package: `HeraldNotification` and `HeraldOptions`, the previews and the editable texts, the per-user channel settings, the inbox with its API, the daily schedule command, `HeraldNotificationDispatch`. The application registers its notifications with `Herald::register()` instead of an enum, its user model implements `HeraldUser`, and the Vue pages are consumed from `vendor/` through the `@witify/notifications` alias with the host services injected by `configureNotifications()`. Requires `witify/support`.

- `witify/support`: the base classes of the Sprintify applications, extracted from `src/Support` and `modules/Utils`: `Action`, `Controller` with `confirmRequest()` and `rateLimit()`, `BaseResource`, `BaseQueryBuilder` and the search classes, the JSON exceptions, `IsResource` with `resource_data`, `SanitizesHtmlTrait` with `RichTextSanitizer` and its published allow-list, `MailMessage`, `DatabaseChannel` with the `NewNotification` broadcast, `ModuleServiceProvider` with the shared-data and user-notification registries. The values the package needs from the application go through `Host` resolvers and the `support` config; the texts through the `support::` translations. Runs on PHP 8.2 to 8.4 and Laravel 11 and 12.

## [1.2.0] - 2026-09-23

### Added

- `witify/devops`: Echo test in the developer console. Set `devops.console.echo_channel` to the private channel of the authenticated user (e.g. `user.{id}`); the console then broadcasts a test event from `POST /devops/echo-test` and shows it when it comes back over the WebSocket. Hidden while the key is null or while the default broadcaster is neither Pusher nor Reverb.
- `witify/devops`: `Witify\Devops\Notifications\CheckFailedNotification`, the laravel-health failure notification with the application URL in the mail subject. Register it in `config/health.php`.

## [1.1.1] - 2026-09-22

### Changed

- `witify/devops`: the developer console now matches the Sprintify console (icons, cards, texts) and shows the Sentry event id after a test exception.

## [1.1.0] - 2026-09-22

### Added

- `witify/devops`: developer console at `/devops` listing the installed monitoring tools (Health, Horizon, Pulse, Telescope, Logs) with a health summary, the application information and a Sentry test button, plus the laravel-health results page at `/status`. Both paths and their middleware are configurable, `devops.console.links` overrides or adds tools.

## [1.0.0] - 2026-09-21

### Added

- `witify/devops`: health endpoint `GET /api/devops/health` for the Witify portal, `ClientFacingCheck` base class, `CpuCheck` and `FailedJobsCheck`. Extracted from the Sprintify `Devops` module. Runs on PHP 8.0 to 8.4 and Laravel 8.75 to 12.
