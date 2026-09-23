<?php

namespace Witify\Devops\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Broadcast from the developer console on the private channel of the
 * authenticated user, so the page can watch it come back over the WebSocket.
 */
class EchoTestEvent implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public const NAME = 'devops.echo_test';

    public string $channel;

    public string $message;

    public string $sentAt;

    public function __construct(string $channel, string $message)
    {
        $this->channel = $channel;
        $this->message = $message;
        $this->sentAt = now()->toIso8601String();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel($this->channel);
    }

    public function broadcastAs(): string
    {
        return self::NAME;
    }

    /**
     * @return array{message: string, sent_at: string}
     */
    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'sent_at' => $this->sentAt,
        ];
    }
}
