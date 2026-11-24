<?php

namespace Witify\Devops\Tests\Concerns;

trait InteractsWithEnvironmentVariables
{
    /**
     * @var array<string, array{env: mixed, server: mixed, process: string|false}>
     */
    private array $originalEnvironmentVariables = [];

    /**
     * Sets the variable in `putenv()`, `$_ENV` and `$_SERVER`, or removes it from
     * the three when the value is null. `restoreEnvironmentVariables()` undoes it.
     */
    protected function setEnvironmentVariable(string $name, ?string $value): void
    {
        if (! array_key_exists($name, $this->originalEnvironmentVariables)) {
            $this->originalEnvironmentVariables[$name] = [
                'env' => $_ENV[$name] ?? null,
                'server' => $_SERVER[$name] ?? null,
                'process' => getenv($name),
            ];
        }

        $this->writeEnvironmentVariable($name, $value, $value, $value);
    }

    protected function restoreEnvironmentVariables(): void
    {
        foreach ($this->originalEnvironmentVariables as $name => $original) {
            $this->writeEnvironmentVariable(
                $name,
                $original['env'],
                $original['server'],
                $original['process'] === false ? null : $original['process'],
            );
        }

        $this->originalEnvironmentVariables = [];
    }

    /**
     * @param  mixed  $env
     * @param  mixed  $server
     */
    private function writeEnvironmentVariable(string $name, $env, $server, ?string $process): void
    {
        if ($env === null) {
            unset($_ENV[$name]);
        } else {
            $_ENV[$name] = $env;
        }

        if ($server === null) {
            unset($_SERVER[$name]);
        } else {
            $_SERVER[$name] = $server;
        }

        putenv($process === null ? $name : "{$name}={$process}");
    }
}
