@php
    /** @var list<\Witify\Devops\ValueObjects\DeveloperToolData> $tools */
    /** @var \Witify\Devops\ValueObjects\ApplicationInfoData $application */
    /** @var \Witify\Devops\ValueObjects\HealthSummaryData $health */
    /** @var bool $sentryTestEnabled */
    $onOff = fn (bool $value): string => __('devops::console.application.' . ($value ? 'on' : 'off'));
    $statuses = ['ok', 'warning', 'failed', 'crashed', 'skipped'];
    $sentryEventId = session(\Witify\Devops\Http\Controllers\SentryTestController::FLASH_KEY);
    $icons = [
        'health' => ['color' => 'danger', 'body' => '<path fill="currentColor" d="M2 6.342a3.375 3.375 0 0 1 6-2.088a3.375 3.375 0 0 1 5.997 2.26c-.063 2.134-1.618 3.76-2.955 4.784a14.4 14.4 0 0 1-2.676 1.61q-.031.015-.05.022l-.014.006l-.004.002h-.002a.75.75 0 0 1-.592.001h-.002l-.004-.003l-.015-.006a6 6 0 0 1-.232-.107a14.4 14.4 0 0 1-2.535-1.557C3.564 10.22 1.999 8.558 1.999 6.38z"/>'],
        'pulse' => ['color' => 'info', 'body' => '<path fill="currentColor" fill-rule="evenodd" d="M9.58 1.077a.75.75 0 0 1 .405.82L9.165 6h4.085a.75.75 0 0 1 .567 1.241l-6.5 7.5a.75.75 0 0 1-1.302-.638L6.835 10H2.75a.75.75 0 0 1-.567-1.241l6.5-7.5a.75.75 0 0 1 .897-.182" clip-rule="evenodd"/>'],
        'horizon' => ['color' => 'purple', 'body' => '<path fill="currentColor" d="M8.372 1.349a.75.75 0 0 0-.744 0l-4.81 2.748L8 7.131l5.182-3.034zM14 5.357L8.75 8.43v6.005l4.872-2.784A.75.75 0 0 0 14 11zm-6.75 9.078V8.43L2 5.357V11c0 .27.144.518.378.651z"/>'],
        'telescope' => ['color' => 'indigo', 'body' => '<g fill="currentColor"><path d="M8 9.5a1.5 1.5 0 1 0 0-3a1.5 1.5 0 0 0 0 3"/><path fill-rule="evenodd" d="M1.38 8.28a.87.87 0 0 1 0-.566a7.003 7.003 0 0 1 13.238.006a.87.87 0 0 1 0 .566A7.003 7.003 0 0 1 1.379 8.28M11 8a3 3 0 1 1-6 0a3 3 0 0 1 6 0" clip-rule="evenodd"/></g>'],
        'logs' => ['color' => 'gray', 'body' => '<path fill="currentColor" fill-rule="evenodd" d="M4 2a1.5 1.5 0 0 0-1.5 1.5v9A1.5 1.5 0 0 0 4 14h8a1.5 1.5 0 0 0 1.5-1.5V6.621a1.5 1.5 0 0 0-.44-1.06L9.94 2.439A1.5 1.5 0 0 0 8.878 2zm1 5.75A.75.75 0 0 1 5.75 7h4.5a.75.75 0 0 1 0 1.5h-4.5A.75.75 0 0 1 5 7.75m0 3a.75.75 0 0 1 .75-.75h4.5a.75.75 0 0 1 0 1.5h-4.5a.75.75 0 0 1-.75-.75" clip-rule="evenodd"/>'],
        'default' => ['color' => 'gray', 'body' => '<path fill="currentColor" fill-rule="evenodd" d="M4.78 4.97a.75.75 0 0 1 0 1.06L2.81 8l1.97 1.97a.75.75 0 1 1-1.06 1.06l-2.5-2.5a.75.75 0 0 1 0-1.06l2.5-2.5a.75.75 0 0 1 1.06 0m6.44 0a.75.75 0 0 0 0 1.06L13.19 8l-1.97 1.97a.75.75 0 1 0 1.06 1.06l2.5-2.5a.75.75 0 0 0 0-1.06l-2.5-2.5a.75.75 0 0 0-1.06 0M8.856 2.008a.75.75 0 0 1 .636.848l-1.5 10.5a.75.75 0 0 1-1.484-.212l1.5-10.5a.75.75 0 0 1 .848-.636" clip-rule="evenodd"/>'],
    ];
    $serverIcon = '<path fill="currentColor" d="M3.665 3.588A2 2 0 0 1 5.622 2h4.754a2 2 0 0 1 1.958 1.588l1.098 5.218a3.5 3.5 0 0 0-1.433-.306H4a3.5 3.5 0 0 0-1.433.306z"/><path fill="currentColor" fill-rule="evenodd" d="M4 10a2 2 0 1 0 0 4h8a2 2 0 1 0 0-4zm8 2.75a.75.75 0 1 0 0-1.5a.75.75 0 0 0 0 1.5M9.75 12a.75.75 0 1 1-1.5 0a.75.75 0 0 1 1.5 0" clip-rule="evenodd"/>';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('devops::console.title') }} · {{ $application->name }}</title>
    <style>
        :root { --ink: #111827; --muted: #6b7280; --line: #e5e7eb; --bg: #f9fafb; --card: #ffffff; --link: #2563eb; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 40px 24px 64px; background: var(--bg); color: var(--ink); font: 15px/1.5 -apple-system, BlinkMacSystemFont, "Segoe UI", Inter, system-ui, sans-serif; }
        main { max-width: 1040px; margin: 0 auto; }
        h1 { margin: 0; font-size: 30px; font-weight: 700; letter-spacing: -0.01em; }
        h2 { margin: 0 0 4px; font-size: 17px; font-weight: 600; }
        .subtitle { margin: 4px 0 28px; color: var(--muted); }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 20px 22px; }
        a.card { display: block; color: inherit; text-decoration: none; transition: border-color .15s; }
        a.card:hover { border-color: #9ca3af; }
        .icon { display: inline-flex; width: 36px; height: 36px; margin-bottom: 14px; align-items: center; justify-content: center; border-radius: 8px; }
        .icon svg { width: 18px; height: 18px; }
        .icon.danger { background: #fee2e2; color: #dc2626; }
        .icon.info { background: #dbeafe; color: #2563eb; }
        .icon.purple { background: #ede9fe; color: #7c3aed; }
        .icon.indigo { background: #e0e7ff; color: #4f46e5; }
        .icon.gray { background: #e5e7eb; color: #4b5563; }
        .card strong { display: block; font-size: 16px; }
        .card .description { margin: 2px 0 0; color: var(--muted); }
        hr { border: 0; border-top: 1px solid var(--line); margin: 32px 0; }
        .section { margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 8px 0; text-align: left; border-bottom: 1px solid var(--line); font-size: 14px; }
        th { width: 260px; color: var(--muted); font-weight: 500; }
        tr:last-child th, tr:last-child td { border-bottom: 0; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 13px; font-weight: 600; background: #f3f4f6; color: #374151; }
        .badge.ok { background: #dcfce7; color: #15803d; }
        .badge.warning { background: #fef3c7; color: #b45309; }
        .badge.failed, .badge.crashed { background: #fee2e2; color: #b91c1c; }
        .badges { display: flex; flex-wrap: wrap; gap: 8px; margin: 12px 0; }
        .muted { color: var(--muted); }
        .button { display: inline-flex; align-items: center; gap: 8px; padding: 9px 16px; border: 0; border-radius: 8px; background: #facc15; color: #422006; font: inherit; font-size: 14px; font-weight: 600; cursor: pointer; }
        .button:hover { background: #eab308; }
        .button svg { width: 16px; height: 16px; }
        .result { margin: 14px 0 0; color: var(--muted); font-size: 14px; }
        a { color: var(--link); }
    </style>
</head>
<body>
<main>
    <h1>{{ __('devops::console.title') }}</h1>
    <p class="subtitle">{{ __('devops::console.subtitle') }}</p>

    <div class="grid">
        @foreach ($tools as $tool)
            @php($icon = $icons[$tool->key] ?? $icons['default'])
            <a class="card" href="{{ $tool->url }}" data-tool="{{ $tool->key }}">
                <span class="icon {{ $icon['color'] }}"><svg viewBox="0 0 16 16" aria-hidden="true">{!! $icon['body'] !!}</svg></span>
                <strong>{{ $tool->label }}</strong>
                <p class="description">{{ $tool->description }}</p>
            </a>
        @endforeach
    </div>

    <hr>

    <div class="card section">
        <h2>{{ __('devops::console.health.title') }}</h2>
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
            <p class="muted" style="margin: 0">{{ __('devops::console.health.no_results') }}</p>
        @endif
    </div>

    <div class="card section">
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
    </div>

    @if ($sentryTestEnabled)
        <div class="card section">
            <h2>{{ __('devops::console.sentry.title') }}</h2>
            <p class="muted" style="margin: 0 0 14px">{{ __('devops::console.sentry.description') }}</p>
            <form method="POST" action="{{ route('devops.console.sentry_test') }}">
                @csrf
                <button class="button" type="submit">
                    <svg viewBox="0 0 16 16" aria-hidden="true">{!! $serverIcon !!}</svg>
                    {{ __('devops::console.sentry.button') }}
                </button>
            </form>
            @if ($sentryEventId !== null)
                <p class="result">
                    {{ __('devops::console.sentry.sent') }}
                    @if ($sentryEventId !== '')
                        — {{ __('devops::console.sentry.event_id', ['id' => $sentryEventId]) }}
                    @endif
                </p>
            @endif
        </div>
    @endif
</main>
</body>
</html>
