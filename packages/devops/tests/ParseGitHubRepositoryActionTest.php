<?php

namespace Witify\Devops\Tests;

use PHPUnit\Framework\TestCase;
use Witify\Devops\Actions\ParseGitHubRepositoryAction;

class ParseGitHubRepositoryActionTest extends TestCase
{
    public function test_it_parses_the_origin_repository_from_a_git_config_fixture(): void
    {
        $gitConfig = file_get_contents(__DIR__ . '/Fixtures/git-config');

        $this->assertIsString($gitConfig);
        $this->assertSame('witify/sprintify', (new ParseGitHubRepositoryAction($gitConfig))->handle());
    }

    public function test_it_normalizes_supported_github_origin_urls(): void
    {
        $originRemoteAddresses = [
            'HTTPS' => 'https://github.com/witify/sprintify.git',
            'SSH shorthand' => 'git@github.com:witify/sprintify.git',
            'SSH URL' => 'ssh://git@github.com/witify/sprintify.git',
        ];

        foreach ($originRemoteAddresses as $label => $originRemoteAddress) {
            $gitConfig = "[remote \"origin\"]\n\turl = {$originRemoteAddress}\n";

            $this->assertSame('witify/sprintify', (new ParseGitHubRepositoryAction($gitConfig))->handle(), $label);
        }
    }

    public function test_it_returns_null_when_the_origin_is_not_a_github_repository(): void
    {
        $gitConfigs = [
            'missing origin' => "[remote \"upstream\"]\n\turl = git@github.com:witify/sprintify.git\n",
            'non-GitHub origin' => "[remote \"origin\"]\n\turl = git@gitlab.com:witify/sprintify.git\n",
            'malformed config' => 'not git config',
        ];

        foreach ($gitConfigs as $label => $gitConfig) {
            $this->assertNull((new ParseGitHubRepositoryAction($gitConfig))->handle(), $label);
        }
    }
}
