<?php

namespace Witify\Devops\ValueObjects;

final class PortalHealthCheckData
{
    public string $name;

    public ?LocalizedTextData $clientLabel;

    public string $status;

    public ?LocalizedTextData $message;

    /** @var array<string, mixed> */
    public array $meta;

    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(string $name, ?LocalizedTextData $clientLabel, string $status, ?LocalizedTextData $message, array $meta)
    {
        $this->name = $name;
        $this->clientLabel = $clientLabel;
        $this->status = $status;
        $this->message = $message;
        $this->meta = $meta;
    }

    /**
     * @return array{name: string, label_client: ?array{fr: string, en: string}, status: string, message: ?array{fr: string, en: string}, meta: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label_client' => $this->clientLabel ? $this->clientLabel->toArray() : null,
            'status' => $this->status,
            'message' => $this->message ? $this->message->toArray() : null,
            'meta' => $this->meta,
        ];
    }
}
