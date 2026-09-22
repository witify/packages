<?php

namespace Witify\Devops\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use RuntimeException;

class SentryTestController
{
    public const FLASH_KEY = 'devops.sentry_test_sent';

    public static function isAvailable(): bool
    {
        return app()->bound('sentry');
    }

    public function __invoke(): RedirectResponse
    {
        abort_unless(self::isAvailable(), 404);

        app('sentry')->captureException(
            new RuntimeException('[Sentry Test] Test exception sent from the developer console.')
        );

        return redirect()->route('devops.console')->with(self::FLASH_KEY, true);
    }
}
