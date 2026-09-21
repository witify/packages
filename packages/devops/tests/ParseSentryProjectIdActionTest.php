<?php

namespace Witify\Devops\Tests;

use PHPUnit\Framework\TestCase;
use Witify\Devops\Actions\ParseSentryProjectIdAction;

class ParseSentryProjectIdActionTest extends TestCase
{
    public function test_it_parses_the_numeric_project_id_from_a_sentry_dsn(): void
    {
        $sentryDsns = [
            'standard cloud DSN' => ['https://public-key@organization.ingest.sentry.io/123456', 123456],
            'regional cloud DSN' => ['https://public-key@organization.ingest.us.sentry.io/98765', 98765],
            'self-hosted DSN' => ['https://public-key:secret@sentry.example.com/42', 42],
            'trailing slash' => ['https://public-key@sentry.example.com/42/', 42],
            'missing DSN' => [null, null],
            'empty DSN' => ['', null],
            'missing project ID' => ['https://public-key@sentry.example.com', null],
            'non-numeric project ID' => ['https://public-key@sentry.example.com/project', null],
            'malformed DSN' => ['not a DSN', null],
        ];

        foreach ($sentryDsns as $label => [$sentryDsn, $expectedProjectId]) {
            $this->assertSame($expectedProjectId, (new ParseSentryProjectIdAction($sentryDsn))->handle(), $label);
        }
    }
}
