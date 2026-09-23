<?php

namespace Witify\Devops\Actions;

use Witify\Devops\ValueObjects\EchoClientConfigData;

/**
 * Resolves the Echo test configuration of the console. The test is offered only
 * when `devops.console.echo_channel` is set and the default broadcaster speaks
 * the Pusher protocol (Pusher or Reverb), the two Laravel Echo can reach from
 * a standalone page.
 */
class GetEchoClientConfigAction
{
    private const SUPPORTED_BROADCASTERS = ['pusher', 'reverb'];

    /**
     * @param  int|string  $userId
     */
    public function handle($userId): ?EchoClientConfigData
    {
        $channel = config('devops.console.echo_channel');

        if (! is_string($channel) || $channel === '') {
            return null;
        }

        $connection = $this->defaultConnection();
        $driver = $connection['driver'] ?? null;
        $key = $connection['key'] ?? null;

        if (! in_array($driver, self::SUPPORTED_BROADCASTERS, true) || ! is_string($key) || $key === '') {
            return null;
        }

        /** @var array<string, mixed> $options */
        $options = is_array($connection['options'] ?? null) ? $connection['options'] : [];
        $host = $options['host'] ?? null;
        $port = $options['port'] ?? null;
        $cluster = $options['cluster'] ?? null;

        return new EchoClientConfigData(
            (string) $driver,
            $key,
            is_string($host) && $host !== '' ? $host : null,
            is_numeric($port) ? (int) $port : null,
            $this->forceTls($options),
            is_string($cluster) && $cluster !== '' ? $cluster : null,
            str_replace('{id}', (string) $userId, $channel),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultConnection(): array
    {
        $default = config('broadcasting.default');
        $connection = is_string($default) ? config('broadcasting.connections.' . $default) : null;

        return is_array($connection) ? $connection : [];
    }

    /**
     * Pusher reads `useTLS`, Reverb reads `scheme`; a hosted Pusher connection has neither and is always TLS.
     *
     * @param  array<string, mixed>  $options
     */
    private function forceTls(array $options): bool
    {
        if (array_key_exists('useTLS', $options)) {
            return (bool) $options['useTLS'];
        }

        if (array_key_exists('scheme', $options)) {
            return $options['scheme'] === 'https';
        }

        return true;
    }
}
