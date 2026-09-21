<?php

namespace Witify\Devops\Actions;

class ParseSentryProjectIdAction
{
    private ?string $sentryDsn;

    public function __construct(?string $sentryDsn)
    {
        $this->sentryDsn = $sentryDsn;
    }

    public function handle(): ?int
    {
        if (! $this->sentryDsn) {
            return null;
        }

        $path = parse_url($this->sentryDsn, PHP_URL_PATH);

        if (! is_string($path)) {
            return null;
        }

        $projectId = basename(trim($path, '/'));

        if (! ctype_digit($projectId)) {
            return null;
        }

        $projectId = (int) $projectId;

        return $projectId > 0 ? $projectId : null;
    }
}
