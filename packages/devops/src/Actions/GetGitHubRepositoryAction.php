<?php

namespace Witify\Devops\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class GetGitHubRepositoryAction
{
    public const CACHE_KEY = 'devops.github_repository';

    private const CACHE_TTL_SECONDS = 7 * 24 * 60 * 60;

    public function handle(): ?string
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function (): ?string {
            $gitConfigPath = base_path('.git/config');

            if (! File::isReadable($gitConfigPath)) {
                return null;
            }

            $gitConfig = File::sharedGet($gitConfigPath);

            if ($gitConfig === '') {
                return null;
            }

            return (new ParseGitHubRepositoryAction($gitConfig))->handle();
        });
    }
}
