<?php

namespace Witify\Devops\Actions;

use Illuminate\Support\Facades\Cache;

class StoreReleaseVersionAction
{
    public const HEADER = 'X-Release-Version';

    private const VERSION_PATTERN = '/\Av?\d{1,9}\.\d{1,9}\.\d{1,9}\z/';

    /** The portal sends the version again every few minutes: one it stopped sending expires. */
    private const CACHE_TTL_SECONDS = 7 * 24 * 60 * 60;

    public function handle(?string $version): void
    {
        if ($version === null || preg_match(self::VERSION_PATTERN, $version) !== 1) {
            return;
        }

        Cache::put(GetReleaseVersionAction::CACHE_KEY, $version, self::CACHE_TTL_SECONDS);
    }
}
