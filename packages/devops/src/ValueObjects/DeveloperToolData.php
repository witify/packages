<?php

namespace Witify\Devops\ValueObjects;

/**
 * A monitoring tool reachable from the developer console.
 */
final class DeveloperToolData
{
    public string $key;

    public string $label;

    public string $description;

    public string $url;

    public function __construct(string $key, string $label, string $description, string $url)
    {
        $this->key = $key;
        $this->label = $label;
        $this->description = $description;
        $this->url = $url;
    }

    public function withUrl(string $url): self
    {
        return new self($this->key, $this->label, $this->description, $url);
    }

    /**
     * @return array{key: string, label: string, description: string, url: string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'description' => $this->description,
            'url' => $this->url,
        ];
    }
}
