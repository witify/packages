<?php

namespace Witify\Notifications\Herald;

class DatabasePreview
{
    public function __construct(
        public string $text,
        public ?string $url = null,
        public ?string $path = null,
    ) {}
}
