<?php

namespace Witify\Devops\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class GetGitHubRepositoryAction
{
    /**
     * Only a repository read from `.git/config` is cached. A configured repository
     * skips the cache, and a missing one is read again on the next request.
     */
    public const CACHE_KEY = 'devops.github_repository';

    private const CACHE_TTL_SECONDS = 7 * 24 * 60 * 60;

    public function handle(): ?string
    {
        $configuredRepository = (new NormalizeGitHubRepositoryAction(config('devops.github_repository')))->handle();

        if ($configuredRepository !== null) {
            return $configuredRepository;
        }

        $cachedRepository = Cache::get(self::CACHE_KEY);

        if (is_string($cachedRepository) && $cachedRepository !== '') {
            return $cachedRepository;
        }

        $repository = $this->readGitConfig();

        if ($repository !== null) {
            Cache::put(self::CACHE_KEY, $repository, self::CACHE_TTL_SECONDS);
        }

        return $repository;
    }

    private function readGitConfig(): ?string
    {
        $gitConfigPath = base_path('.git/config');

        if (! File::isReadable($gitConfigPath)) {
            return null;
        }

        $gitConfig = File::sharedGet($gitConfigPath);

        if ($gitConfig === '') {
            return null;
        }

        return (new ParseGitHubRepositoryAction($gitConfig))->handle();
    }
}
