@php
    /** @var list<\Witify\Devops\ValueObjects\DeveloperToolData> $tools */
    /** @var \Witify\Devops\ValueObjects\ApplicationInfoData $application */
    /** @var \Witify\Devops\ValueObjects\HealthSummaryData $health */
    /** @var bool $sentryTestEnabled */
    $onOff = fn (bool $value): string => __('devops::console.application.' . ($value ? 'on' : 'off'));
    $statuses = ['ok', 'warning', 'failed', 'crashed', 'skipped'];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('devops::console.title') }} · {{ $application->name }}</title>
    <style>
        :root { --ink: #1f2937; --muted: #6b7280; --line: #e5e7eb; --bg: #f3f4f6; --card: #ffffff; --accent: #2563eb; --ok: #16a34a; --warning: #d97706; --failed: #dc2626; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 32px 16px 64px; background: var(--bg); color: var(--ink); font: 15px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; }
        main { max-width: 960px; margin: 0 auto; }
        h1 { margin: 0 0 4px; font-size: 28px; }
        h2 { margin: 32px 0 12px; font-size: 18px; }
        .subtitle { margin: 0 0 24px; color: var(--muted); }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
        .card { display: block; padding: 18px 20px; background: var(--card); border: 1px solid var(--line); border-radius: 8px; color: inherit; text-decoration: none; }
        a.card:hover { border-color: var(--accent); }
        .card strong { display: block; margin-bottom: 4px; font-size: 16px; }
        .card span { color: var(--muted); font-size: 14px; }
        table { width: 100%; border-collapse: collapse; background: var(--card); border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
        th, td { padding: 10px 16px; text-align: left; border-bottom: 1px solid var(--line); font-size: 14px; }
        th { width: 40%; color: var(--muted); font-weight: 500; }
        tr:last-child th, tr:last-child td { border-bottom: 0; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 13px; font-weight: 600; background: var(--line); }
        .badge.ok { background: #dcfce7; color: var(--ok); }
        .badge.warning { background: #fef3c7; color: var(--warning); }
        .badge.failed, .badge.crashed { background: #fee2e2; color: var(--failed); }
        .badges { display: flex; flex-wrap: wrap; gap: 8px; margin: 12px 0; }
        .muted { color: var(--muted); }
        .button { display: inline-block; padding: 8px 16px; border: 0; border-radius: 6px; background: var(--accent); color: #fff; font: inherit; font-weight: 600; cursor: pointer; }
        .notice { margin: 0 0 16px; padding: 12px 16px; border-radius: 8px; background: #dcfce7; color: var(--ok); }
        a { color: var(--accent); }
    </style>
</head>
<body>
<main>
    <h1>{{ __('devops::console.title') }}</h1>
    <p class="subtitle">{{ $application->name }} · {{ __('devops::console.subtitle') }}</p>

    @if (session(\Witify\Devops\Http\Controllers\SentryTestController::FLASH_KEY))
        <p class="notice">{{ __('devops::console.sentry.sent') }}</p>
    @endif

    <div class="grid">
        @foreach ($tools as $tool)
            <a class="card" href="{{ $tool->url }}" data-tool="{{ $tool->key }}">
                <strong>{{ $tool->label }}</strong>
                <span>{{ $tool->description }}</span>
            </a>
        @endforeach
    </div>

    <h2>{{ __('devops::console.health.title') }}</h2>
    <div class="card">
        @if ($health->hasResults())
            <span class="muted">{{ __('devops::console.health.checked_at', ['time' => $health->checkedAt->format('Y-m-d H:i:s')]) }}</span>
            <div class="badges">
                @foreach ($statuses as $status)
                    @if ($health->count($status) > 0)
                        <span class="badge {{ $status }}">{{ $health->count($status) }} {{ __('devops::console.health.' . $status) }}</span>
                    @endif
                @endforeach
            </div>
            @if (\Illuminate\Support\Facades\Route::has('devops.health'))
                <a href="{{ route('devops.health') }}">{{ __('devops::console.health.view') }}</a>
            @endif
        @else
            <span class="muted">{{ __('devops::console.health.no_results') }}</span>
        @endif
    </div>

    <h2>{{ __('devops::console.application.title') }}</h2>
    <table>
        <tr><th>{{ __('devops::console.application.environment') }}</th><td>{{ $application->environment }}</td></tr>
        <tr><th>{{ __('devops::console.application.version') }}</th><td>{{ $application->version ?? __('devops::console.application.unknown') }}</td></tr>
        <tr><th>{{ __('devops::console.application.php') }}</th><td>{{ $application->phpVersion }}</td></tr>
        <tr><th>{{ __('devops::console.application.laravel') }}</th><td>{{ $application->laravelVersion }}</td></tr>
        <tr><th>{{ __('devops::console.application.debug') }}</th><td>{{ $onOff($application->debug) }}</td></tr>
        <tr><th>{{ __('devops::console.application.maintenance') }}</th><td>{{ $onOff($application->maintenance) }}</td></tr>
        <tr><th>{{ __('devops::console.application.configuration_cached') }}</th><td>{{ $onOff($application->configurationCached) }}</td></tr>
        <tr><th>{{ __('devops::console.application.routes_cached') }}</th><td>{{ $onOff($application->routesCached) }}</td></tr>
        <tr>
            <th>{{ __('devops::console.application.github') }}</th>
            <td>
                @if ($application->githubUrl())
                    <a href="{{ $application->githubUrl() }}">{{ $application->githubRepository }}</a>
                @else
                    <span class="muted">{{ __('devops::console.application.unknown') }}</span>
                @endif
            </td>
        </tr>
        <tr><th>{{ __('devops::console.application.sentry') }}</th><td>{{ $application->sentryProjectId ?? __('devops::console.application.unknown') }}</td></tr>
    </table>

    @if ($sentryTestEnabled)
        <h2>{{ __('devops::console.sentry.title') }}</h2>
        <div class="card">
            <p class="muted" style="margin-top: 0">{{ __('devops::console.sentry.description') }}</p>
            <form method="POST" action="{{ route('devops.console.sentry_test') }}">
                @csrf
                <button class="button" type="submit">{{ __('devops::console.sentry.button') }}</button>
            </form>
        </div>
    @endif
</main>
</body>
</html>
