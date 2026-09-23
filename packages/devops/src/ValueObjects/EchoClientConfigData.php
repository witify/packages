<?php

namespace Witify\Devops\ValueObjects;

use Witify\Devops\Events\EchoTestEvent;

/**
 * What the console page needs to open a Laravel Echo connection with the
 * same broadcaster as the application and listen on the user's channel.
 */
final class EchoClientConfigData
{
    public string $broadcaster;

    public string $key;

    public ?string $host;

    public ?int $port;

    public bool $forceTls;

    public ?string $cluster;

    public string $channel;

    public function __construct(
        string $broadcaster,
        string $key,
        ?string $host,
        ?int $port,
        bool $forceTls,
        ?string $cluster,
        string $channel
    ) {
        $this->broadcaster = $broadcaster;
        $this->key = $key;
        $this->host = $host;
        $this->port = $port;
        $this->forceTls = $forceTls;
        $this->cluster = $cluster;
        $this->channel = $channel;
    }

    /**
     * @return array{broadcaster: string, key: string, host: ?string, port: ?int, force_tls: bool, cluster: ?string, channel: string, event: string}
     */
    public function toArray(): array
    {
        return [
            'broadcaster' => $this->broadcaster,
            'key' => $this->key,
            'host' => $this->host,
            'port' => $this->port,
            'force_tls' => $this->forceTls,
            'cluster' => $this->cluster,
            'channel' => $this->channel,
            'event' => EchoTestEvent::NAME,
        ];
    }
}
