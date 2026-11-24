<?php

namespace Witify\Devops\Actions;

use Illuminate\Support\Facades\Cache;

class GetReleaseVersionAction
{
    public const CACHE_KEY = 'devops.release_version';

    public function handle(): ?string
    {
        $version = Cache::get(self::CACHE_KEY);

        return is_string($version) ? $version : null;
    }
}
