<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="referrer" content="no-referrer">
    <title>@yield('title') · {{ $branding->name }}</title>
    <style>
        :root {
            --accent: {{ preg_match('/^#[0-9a-fA-F]{3,8}$|^[a-z]+$/', (string) $branding->color) ? $branding->color : '#2563eb' }};
            --bg: #f5f5f4; --card: #ffffff; --text: #1c1917; --muted: #78716c; --border: #e7e5e4;
            --danger: #b91c1c; --success: #15803d;
        }
        @media (prefers-color-scheme: dark) {
            :root { --bg: #0c0a09; --card: #1c1917; --text: #f5f5f4; --muted: #a8a29e; --border: #292524; --danger: #f87171; --success: #4ade80; }
        }
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text); font: 16px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; -webkit-text-size-adjust: 100%; overflow-wrap: anywhere; }
        main { max-width: 44rem; margin: 0 auto; padding: 1rem 1rem 3rem; display: flex; flex-direction: column; gap: 1rem; }
        header { display: flex; align-items: center; gap: .75rem; padding: .5rem 0; }
        header img { max-height: 2.5rem; max-width: 10rem; object-fit: contain; }
        header strong { font-size: 1.05rem; }
        h1 { font-size: 1.4rem; line-height: 1.25; margin: 0; }
        h2 { font-size: 1.05rem; margin: 0 0 .75rem; }
        p { margin: 0; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: .9rem; padding: 1.1rem; display: flex; flex-direction: column; gap: .85rem; }
        .muted { color: var(--muted); font-size: .9rem; }
        .stack { display: flex; flex-direction: column; gap: .75rem; }
        .row { display: grid; grid-template-columns: 1fr; gap: .75rem; }
        @media (min-width: 34rem) { .row { grid-template-columns: 1fr 1fr; } }
        label { display: flex; flex-direction: column; gap: .3rem; font-size: .9rem; font-weight: 600; }
        input[type=text], textarea { width: 100%; font: inherit; color: inherit; background: transparent; border: 1px solid var(--border); border-radius: .6rem; padding: .7rem .8rem; }
        input:focus, textarea:focus { outline: 2px solid var(--accent); outline-offset: 1px; }
        input.code { font-size: 1.6rem; letter-spacing: .5rem; text-align: center; font-variant-numeric: tabular-nums; }
        .check { flex-direction: row; align-items: flex-start; gap: .6rem; font-weight: 400; }
        .check input { width: 1.25rem; height: 1.25rem; margin-top: .15rem; accent-color: var(--accent); flex: none; }
        .btn { display: inline-flex; justify-content: center; align-items: center; gap: .5rem; min-height: 3rem; padding: .7rem 1.2rem; border-radius: .7rem; border: 1px solid transparent; font: inherit; font-weight: 600; text-decoration: none; cursor: pointer; }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-secondary { background: transparent; color: var(--text); border-color: var(--border); }
        .btn-link { background: none; border: none; color: var(--accent); padding: 0; min-height: auto; font-weight: 600; }
        .btn-block { width: 100%; }
        details > summary { list-style: none; cursor: pointer; }
        details > summary::-webkit-details-marker { display: none; }
        .viewer { width: 100%; height: 70vh; border: 1px solid var(--border); border-radius: .6rem; display: none; }
        @media (min-width: 48rem) { .viewer { display: block; } }
        ul.files { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .4rem; }
        ul.files a { color: var(--accent); }
        .alert { border-radius: .6rem; padding: .75rem .9rem; font-size: .95rem; }
        .alert-error { background: color-mix(in srgb, var(--danger) 12%, transparent); color: var(--danger); }
        .alert-info { background: color-mix(in srgb, var(--accent) 12%, transparent); }
        .done { text-align: center; align-items: center; }
        .done .icon { font-size: 2.5rem; line-height: 1; }
        .success { color: var(--success); }
        footer { font-size: .8rem; color: var(--muted); text-align: center; }
    </style>
</head>
<body>
<main>
    <header>
        @if ($branding->logo_url)
            <img src="{{ $branding->logo_url }}" alt="{{ $branding->name }}">
        @else
            <strong>{{ $branding->name }}</strong>
        @endif
    </header>

    @yield('content')

    <footer>{{ __('oi-laravel-sign::sign.screen.legal') }}</footer>
</main>
</body>
</html>
