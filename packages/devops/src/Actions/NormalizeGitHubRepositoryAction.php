<?php

namespace Witify\Devops\Actions;

class NormalizeGitHubRepositoryAction
{
    private const REPOSITORY_PATTERN = '/^[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?\/[A-Za-z0-9._-]+$/';

    private const URL_PATTERN = '/^https?:\/\/(?:www\.)?github\.com\/(.+?)\/?$/i';

    private const GIT_SUFFIX = '.git';

    /**
     * @var mixed
     */
    private $repository;

    /**
     * @param  mixed  $repository
     */
    public function __construct($repository)
    {
        $this->repository = $repository;
    }

    /**
     * Returns the repository as `owner/repo`, from that form or from its
     * https://github.com/owner/repo URL, or null when it is neither.
     */
    public function handle(): ?string
    {
        if (! is_string($this->repository)) {
            return null;
        }

        $repository = trim($this->repository);

        if (preg_match(self::URL_PATTERN, $repository, $urlMatches)) {
            $repository = $urlMatches[1];
        }

        if (str_ends_with(strtolower($repository), self::GIT_SUFFIX)) {
            $repository = substr($repository, 0, -strlen(self::GIT_SUFFIX));
        }

        if (! preg_match(self::REPOSITORY_PATTERN, $repository)) {
            return null;
        }

        [, $name] = explode('/', $repository);

        if ($name === '.' || $name === '..') {
            return null;
        }

        return $repository;
    }
}
