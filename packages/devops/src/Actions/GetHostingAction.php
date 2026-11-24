<?php

namespace Witify\Devops\Actions;

use Witify\Devops\ValueObjects\HostingData;

class GetHostingAction
{
    private const LARAVEL_CLOUD_VARIABLE = 'LARAVEL_CLOUD';

    private const LARAVEL_CLOUD_ENABLED = '1';

    private const ENVIRONMENT_ID_VARIABLE = 'LARAVEL_CLOUD_ENV_UUID';

    private const COMMIT_SHA_VARIABLES = ['LARAVEL_CLOUD_COMMIT_SHA', 'LARAVEL_CLOUD_COMMIT'];

    /**
     * Laravel Cloud injects its variables in the environment of the process. They are
     * read from there, like the `laravel_cloud()` helper of Laravel 11 and later, so
     * they survive a cached configuration.
     */
    public function handle(): HostingData
    {
        $environmentId = $this->environmentVariable(self::ENVIRONMENT_ID_VARIABLE);
        $isLaravelCloud = $this->environmentVariable(self::LARAVEL_CLOUD_VARIABLE) === self::LARAVEL_CLOUD_ENABLED
            || $environmentId !== null;

        $commitSha = null;

        foreach (self::COMMIT_SHA_VARIABLES as $variable) {
            $commitSha = $this->environmentVariable($variable);

            if ($commitSha !== null) {
                break;
            }
        }

        return new HostingData(
            $isLaravelCloud ? HostingData::LARAVEL_CLOUD_PROVIDER : null,
            $environmentId,
            $commitSha,
        );
    }

    private function environmentVariable(string $name): ?string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
