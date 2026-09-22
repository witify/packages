<?php

namespace Witify\Devops\Actions;

use Illuminate\Support\Facades\Route;
use Witify\Devops\ValueObjects\DeveloperToolData;

/**
 * Lists the monitoring tools installed in the application, with the URL each
 * package serves according to its own configuration. `devops.console.links`
 * overrides a URL or adds a tool the detection cannot see.
 */
class ResolveDeveloperToolsAction
{
    public const HEALTH = 'health';

    public const HORIZON = 'horizon';

    public const PULSE = 'pulse';

    public const TELESCOPE = 'telescope';

    public const LOGS = 'logs';

    private const KNOWN_TOOLS = [self::HEALTH, self::HORIZON, self::PULSE, self::TELESCOPE, self::LOGS];

    /**
     * @return list<DeveloperToolData>
     */
    public function handle(): array
    {
        $tools = [];

        foreach (self::KNOWN_TOOLS as $key) {
            $url = $this->detectUrl($key);

            if ($url !== null) {
                $tools[$key] = $this->tool($key, $url);
            }
        }

        foreach ((array) config('devops.console.links', []) as $key => $link) {
            $tools[$key] = $this->overriddenTool((string) $key, $link, $tools[$key] ?? null);
        }

        return array_values(array_filter($tools));
    }

    private function detectUrl(string $key): ?string
    {
        switch ($key) {
            case self::HEALTH:
                return Route::has('devops.health') ? route('devops.health') : null;
            case self::HORIZON:
                return class_exists('Laravel\Horizon\Horizon') ? url((string) config('horizon.path', 'horizon')) : null;
            case self::PULSE:
                return class_exists('Laravel\Pulse\Pulse') ? url((string) config('pulse.path', 'pulse')) : null;
            case self::TELESCOPE:
                return class_exists('Laravel\Telescope\Telescope') ? url((string) config('telescope.path', 'telescope')) : null;
            case self::LOGS:
                return $this->detectLogViewerUrl();
            default:
                return null;
        }
    }

    private function detectLogViewerUrl(): ?string
    {
        if (class_exists('Opcodes\LogViewer\LogViewerServiceProvider')) {
            return url((string) config('log-viewer.route_path', 'log-viewer'));
        }

        if (class_exists('Arcanedev\LogViewer\LogViewerServiceProvider')) {
            return url((string) config('log-viewer.route.attributes.prefix', 'log-viewer'));
        }

        return null;
    }

    /**
     * @param  string|array{label?: string, description?: string, url?: string}|null  $link
     */
    private function overriddenTool(string $key, $link, ?DeveloperToolData $detected): ?DeveloperToolData
    {
        if (is_string($link)) {
            return $detected ? $detected->withUrl($link) : $this->tool($key, $link);
        }

        if (! is_array($link) || empty($link['url'])) {
            return $detected;
        }

        $base = $detected ?? $this->tool($key, $link['url']);

        return new DeveloperToolData(
            $key,
            $link['label'] ?? $base->label,
            $link['description'] ?? $base->description,
            $link['url'],
        );
    }

    private function tool(string $key, string $url): DeveloperToolData
    {
        $isKnown = in_array($key, self::KNOWN_TOOLS, true);

        return new DeveloperToolData(
            $key,
            $isKnown ? (string) __("devops::console.tools.{$key}.label") : ucfirst($key),
            $isKnown ? (string) __("devops::console.tools.{$key}.description") : '',
            $url,
        );
    }
}
