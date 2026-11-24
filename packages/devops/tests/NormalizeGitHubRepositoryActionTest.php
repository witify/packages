<?php

namespace Witify\Devops\Tests;

use PHPUnit\Framework\TestCase;
use Witify\Devops\Actions\NormalizeGitHubRepositoryAction;

class NormalizeGitHubRepositoryActionTest extends TestCase
{
    public function test_it_normalizes_a_configured_repository_to_owner_and_name(): void
    {
        $repositories = [
            'owner and name' => ['witify/navark', 'witify/navark'],
            'surrounding spaces' => ['  witify/navark  ', 'witify/navark'],
            'dots, dashes and underscores' => ['witify-inc/client_app.v2', 'witify-inc/client_app.v2'],
            'git suffix' => ['witify/navark.git', 'witify/navark'],
            'https URL' => ['https://github.com/witify/navark', 'witify/navark'],
            'https URL with git suffix' => ['https://github.com/witify/navark.git', 'witify/navark'],
            'https URL with trailing slash' => ['https://github.com/witify/navark/', 'witify/navark'],
            'www URL' => ['https://www.github.com/witify/navark', 'witify/navark'],
            'missing value' => [null, null],
            'empty value' => ['', null],
            'not a string' => [['witify/navark'], null],
            'name only' => ['navark', null],
            'three segments' => ['witify/navark/tree', null],
            'URL of a page of the repository' => ['https://github.com/witify/navark/pulls', null],
            'URL of another host' => ['https://gitlab.com/witify/navark', null],
            'SSH remote' => ['git@github.com:witify/navark.git', null],
            'spaces inside' => ['witify/my app', null],
            'dot name' => ['witify/..', null],
            'owner starting with a dash' => ['-witify/navark', null],
        ];

        foreach ($repositories as $label => [$repository, $expectedRepository]) {
            $this->assertSame($expectedRepository, (new NormalizeGitHubRepositoryAction($repository))->handle(), $label);
        }
    }
}
