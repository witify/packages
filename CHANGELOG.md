# Changelog

All notable changes to the packages are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and every package shares the same version number.

## [1.0.0] - 2026-09-21

### Added

- `witify/devops`: health endpoint `GET /api/devops/health` for the Witify portal, `ClientFacingCheck` base class, `CpuCheck` and `FailedJobsCheck`. Extracted from the Sprintify `Devops` module. Runs on PHP 8.0 to 8.4 and Laravel 8.75 to 12.
