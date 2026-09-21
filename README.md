# Witify packages

Monorepo of the Composer packages shared by the Witify Laravel applications. Every directory under `packages/` is split into its own read-only repository on each push to `main` and on each tag, and published on [packagist.org](https://packagist.org/packages/witify/).

| Package | Repository | Purpose |
|---|---|---|
| `witify/devops` | [witify/devops](https://github.com/witify/devops) | Health endpoint read by the Witify portal. Laravel 8 to 12. |

All packages share one version number. A tag `v1.4.0` on this repository produces `v1.4.0` on every split repository.

Install a package in an application with Composer, as usual:

```bash
composer require witify/devops
```

Each package has its own README with the installation steps. See [CONTRIBUTING](CONTRIBUTING.md) to work on the packages and [CHANGELOG](CHANGELOG.md) for the released changes.
