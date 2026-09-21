<?php

namespace Witify\Devops\ValueObjects;

final class PortalHealthBackupData
{
    public ?string $bucket;

    public ?string $prefix;

    public function __construct(?string $bucket, ?string $prefix)
    {
        $this->bucket = $bucket;
        $this->prefix = $prefix;
    }

    /**
     * @return array{bucket: ?string, prefix: ?string}
     */
    public function toArray(): array
    {
        return [
            'bucket' => $this->bucket,
            'prefix' => $this->prefix,
        ];
    }
}
