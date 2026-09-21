<?php

namespace Witify\Devops\Actions;

class ParseGitHubRepositoryAction
{
    private const ORIGIN_REMOTE_NAME = 'origin';

    private const GITHUB_HOST = 'github.com';

    private const GIT_SUFFIX = '.git';

    private const REPOSITORY_PATH_SEGMENT_COUNT = 2;

    private string $gitConfig;

    public function __construct(string $gitConfig)
    {
        $this->gitConfig = $gitConfig;
    }

    public function handle(): ?string
    {
        $originRemoteName = preg_quote(self::ORIGIN_REMOTE_NAME, '/');
        $originPattern = '/^\s*\[remote\s+"' . $originRemoteName . '"\]\s*$([\s\S]*?)(?=^\s*\[|\z)/m';

        if (! preg_match($originPattern, $this->gitConfig, $originMatches)) {
            return null;
        }

        if (! preg_match('/^\s*url\s*=\s*(.+?)\s*$/m', $originMatches[1], $remoteAddressMatches)) {
            return null;
        }

        $remoteAddress = trim($remoteAddressMatches[1]);
        $githubHost = preg_quote(self::GITHUB_HOST, '/');

        if (preg_match('/^(?:ssh:\/\/)?git@' . $githubHost . '[:\/](.+)$/i', $remoteAddress, $secureShellMatches)) {
            return $this->normalizePath($secureShellMatches[1]);
        }

        $host = parse_url($remoteAddress, PHP_URL_HOST);
        $path = parse_url($remoteAddress, PHP_URL_PATH);

        if (! is_string($host) || strtolower($host) !== self::GITHUB_HOST || ! is_string($path)) {
            return null;
        }

        return $this->normalizePath($path);
    }

    private function normalizePath(string $path): ?string
    {
        $path = trim($path, '/');

        if (str_ends_with(strtolower($path), self::GIT_SUFFIX)) {
            $path = substr($path, 0, -strlen(self::GIT_SUFFIX));
        }

        if (count(explode('/', $path)) !== self::REPOSITORY_PATH_SEGMENT_COUNT) {
            return null;
        }

        return $path;
    }
}
