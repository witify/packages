# Contributing

## Layout

```
packages/<name>/        one Composer package, split into github.com/witify/<name>
  composer.json         the package's own dependencies and autoload
  src/                  PSR-4, namespace Witify\<Name>\
  config/ lang/ routes/ tests/
composer.json           root tooling only: Pint, Larastan, Testbench, path repositories to packages/*
phpstan.neon pint.json  root configuration; a package with a higher PHP floor adds its own phpstan.neon
.github/workflows/      tests.yml (matrix per package) and split.yml
```

The root `composer.json` is never published. It requires every package through a `path` repository so Pint, PHPStan and the fast test run see all of them at once.

## Working on a package

```bash
composer install            # root tooling, PHP 8.4, Laravel 12
composer test               # every package's tests on Laravel 12
composer lint               # Pint, fixes in place
composer analyse            # PHPStan
```

The root run covers the newest supported Laravel. A package must also pass on the oldest PHP and Laravel it allows. Run that matrix inside the package directory, with the Composer flag `--with` so the package's `composer.json` stays untouched:

```bash
cd packages/devops
composer update --with "illuminate/support:8.*" --with "orchestra/testbench:^6.23" --with "phpunit/phpunit:^9.5"
vendor/bin/phpunit
```

CI runs this matrix for every package on each push and pull request (`.github/workflows/tests.yml`), one entry per package and pair. `witify/devops` runs on PHP 8.0 + Laravel 8, PHP 8.1 + Laravel 9 and PHP 8.4 + Laravel 12; `witify/support` and `witify/notifications` on PHP 8.2 + Laravel 11 and PHP 8.4 + Laravel 12.

The path repository of the root `composer.json` pins every package to the upcoming version (`options.versions`), so a package can require another one with a caret constraint while both are checked out.

### Code that must run on PHP 8.0

`witify/devops` runs on PHP 8.0, so its code uses no `readonly`, no enums, no typed constants, no `never` and no first-class callable syntax. PHPStan is configured with `phpVersion: 80000` and the PHP 8.0 CI job fails on any newer syntax. Tests must pass on PHPUnit 9 and 11: no data provider attributes, no annotations, loop over the cases instead.

Laravel APIs must exist in Laravel 8: `$casts` property instead of the `casts()` method, `Carbon::setTestNow()` instead of `travelTo()`.

### Testing a package inside an application

Point the application at your checkout with a `path` repository, then require the development version:

```bash
cd ~/www/my-app
composer config repositories.witify path "../packages/packages/*"
composer require "witify/devops:@dev"
```

Composer symlinks `vendor/witify/devops` to `packages/devops`, so edits are visible immediately. Never commit those two changes: restore `composer.json` and `composer.lock` before committing, the application must depend on the released version from Packagist.

## Adding a package

1. Create `packages/<name>` with its `composer.json` (name `witify/<name>`, PSR-4 `Witify\<Name>\`), `README.md`, `LICENSE.md` and tests.
2. Root `composer.json`: add `"witify/<name>": "@dev"` to `require-dev` and the tests namespace to `autoload-dev`.
3. `phpstan.neon`: add `packages/<name>/src` to `paths`, or give the package its own `phpstan.neon` when its PHP floor differs and chain it in the root `analyse` script.
4. `.github/workflows/tests.yml`: add one `include` entry per supported PHP / Laravel pair, each with the `package` key.
5. `.github/workflows/split.yml`: add a matrix entry with the secret name of its deploy key, then create the key (below).
6. Create the empty repository `github.com/witify/<name>` and, after the first split, submit it on packagist.org.

## Split and release

Every push to `main` and every tag pushes each `packages/<name>` directory to `github.com/witify/<name>` with `git subtree split`. Nobody pushes to the split repositories by hand.

Each split repository has its own deploy key, mapped by the key comment:

```bash
ssh-keygen -t ed25519 -N "" -C "git@github.com:witify/<name>.git" -f <name>
gh repo deploy-key add <name>.pub --repo witify/<name> --allow-write --title "witify/packages split"
gh secret set SPLIT_KEY_<NAME> --repo witify/packages < <name>
```

To release:

1. Move the `Unreleased` section of `CHANGELOG.md` under the new version and date.
2. Merge to `main`, then tag: `git tag v1.2.0 && git push origin v1.2.0`.
3. The split workflow creates the same tag on every split repository, and Packagist picks it up through the GitHub hook.

Versions follow semver strictly and are shared by all packages. A change to a public contract of any package is a major version for all of them, with an `UPGRADE.md` describing the migration. A new package or a new feature is a minor. Packages require each other with the caret of the current minor, for example `"witify/support": "^1.2"`.

Commits follow the conventional format used in the applications: `feat(devops): ...`, `fix(devops): ...`, `docs: ...`.
