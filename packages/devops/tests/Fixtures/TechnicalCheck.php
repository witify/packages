<?php

namespace Witify\Devops\Tests\Fixtures;

use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

class TechnicalCheck extends Check
{
    public static int $runCount = 0;

    public function getName(): string
    {
        return 'Technical';
    }

    public function run(): Result
    {
        self::$runCount++;

        return Result::make()
            ->warning('Technical warning')
            ->meta(['threshold' => 10]);
    }
}
