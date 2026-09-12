{{--
    The shell every error page uses.

    Deliberately self-contained: an error page has to render when the database
    is down, the cache is cold and the Vite build is missing, so it pulls in no
    stylesheet and no script. Only the brand tokens, which are inline.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — CelebrateMi</title>
    <meta name="robots" content="noindex, follow">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="32x32" href="/icon-32.png">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <x-brand-tokens />

    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        html, body { height: 100%; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--surface-2);
            color: var(--ink);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
            -webkit-font-smoothing: antialiased;
        }

        .card {
            width: 100%;
            max-width: 520px;
            text-align: center;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 2.5rem;
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 1.25rem;
            color: var(--ink);
            text-decoration: none;
        }

        .badge {
            display: inline-block;
            padding: 0.3rem 0.85rem;
            border-radius: 999px;
            background: var(--primary-100);
            color: var(--primary-700);
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        h1 {
            margin-top: 1.25rem;
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: clamp(1.9rem, 6vw, 2.5rem);
            line-height: 1.12;
            letter-spacing: -0.025em;
        }

        p {
            margin-top: 0.9rem;
            font-size: 1rem;
            line-height: 1.7;
            color: var(--muted);
        }

        .actions {
            margin-top: 2.25rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.8rem 1.5rem;
            border-radius: 999px;
            font-size: 0.92rem;
            font-weight: 600;
            text-decoration: none;
            border: 1.5px solid transparent;
            transition: background 0.16s, border-color 0.16s;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-600); }
        .btn-quiet { border-color: var(--line); color: var(--ink); background: var(--surface); }
        .btn-quiet:hover { border-color: var(--ink-300); }

        .ref {
            margin-top: 2rem;
            font-size: 0.78rem;
            color: var(--muted-2);
        }
        .ref code {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 6px;
            padding: 0.15rem 0.4rem;
        }
    </style>
</head>
<body>
    <div class="card">
        <a href="{{ url('/') }}" class="brand">CelebrateMi</a>

        <span class="badge">@yield('code')</span>
        <h1>@yield('heading')</h1>
        <p>@yield('body')</p>

        <div class="actions">
            <a href="{{ url('/') }}" class="btn btn-primary">Back to the home page</a>
            @hasSection('secondary')
                @yield('secondary')
            @endif
        </div>

        @hasSection('reference')
            <p class="ref">@yield('reference')</p>
        @endif
    </div>
</body>
</html>
