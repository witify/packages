<?php

namespace Witify\Notifications\Herald;

class MailPreview
{
    public function __construct(
        public string $subject,
        public string $html,
    ) {}
}
