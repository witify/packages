# Changelog

All notable changes to the packages are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and every package shares the same version number.

## [1.1.1] - 2026-09-22

### Changed

- `witify/devops`: the developer console now matches the Sprintify console (icons, cards, texts) and shows the Sentry event id after a test exception.

## [1.1.0] - 2026-09-22

### Added

- `witify/devops`: developer console at `/devops` listing the installed monitoring tools (Health, Horizon, Pulse, Telescope, Logs) with a health summary, the application information and a Sentry test button, plus the laravel-health results page at `/status`. Both paths and their middleware are configurable, `devops.console.links` overrides or adds tools.

## [1.0.0] - 2026-09-21

### Added

- `witify/devops`: health endpoint `GET /api/devops/health` for the Witify portal, `ClientFacingCheck` base class, `CpuCheck` and `FailedJobsCheck`. Extracted from the Sprintify `Devops` module. Runs on PHP 8.0 to 8.4 and Laravel 8.75 to 12.
