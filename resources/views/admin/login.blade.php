<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login — CelebrateMi</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">

    <style>
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --off:    #faf8f5;
            --white:  #ffffff;
            --dark:   #1a1714;
            --muted:  #7a7065;
            --accent: #c8440f;
            --border: #e8e3dd;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--off);
            color: var(--dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            -webkit-font-smoothing: antialiased;
        }

        .login-wrap {
            width: 100%;
            max-width: 420px;
        }

        /* ── CARD ───────────────────────────────────── */
        .login-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 8px 48px rgba(0,0,0,0.07);
        }

        .login-header {
            background: linear-gradient(135deg, #c8440f 0%, #e8623a 100%);
            padding: 2.25rem 2.5rem 2rem;
            position: relative;
            overflow: hidden;
        }
        .login-header::before {
            content: '';
            position: absolute; top: -50px; right: -50px;
            width: 160px; height: 160px; border-radius: 50%;
            background: rgba(255,255,255,0.08);
        }
        .login-header::after {
            content: '';
            position: absolute; bottom: -30px; left: -20px;
            width: 100px; height: 100px; border-radius: 50%;
            background: rgba(255,255,255,0.05);
        }
        .login-header-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(255,255,255,0.18);
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 99px;
            padding: 0.28rem 0.75rem;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.9);
            margin-bottom: 0.85rem;
            position: relative; z-index: 1;
        }
        .login-header h1 {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 2rem;
            letter-spacing: -0.02em;
            color: #fff;
            line-height: 1.1;
            position: relative; z-index: 1;
        }
        .login-header p {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.75);
            margin-top: 0.4rem;
            position: relative; z-index: 1;
        }

        /* ── FORM ───────────────────────────────────── */
        .login-body { padding: 2rem 2.5rem 2.5rem; }

        .field { margin-bottom: 1.1rem; }
        .field label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.45rem;
        }
        .field-inner {
            position: relative;
        }
        .field-icon {
            position: absolute;
            left: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.05rem;
            color: var(--muted);
            pointer-events: none;
        }
        .field input {
            width: 100%;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 0.78rem 0.9rem 0.78rem 2.6rem;
            font-size: 0.875rem;
            font-family: inherit;
            color: var(--dark);
            background: var(--white);
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .field input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(200,68,15,0.1);
        }
        .field input.is-error { border-color: #dc2626; }
        .field-error {
            font-size: 0.75rem;
            color: #dc2626;
            margin-top: 0.35rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .toggle-password {
            position: absolute;
            right: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--muted);
            font-size: 1.05rem;
            padding: 0;
            line-height: 1;
            transition: color 0.15s;
        }
        .toggle-password:hover { color: var(--dark); }

        .remember-row {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        .remember-row input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: var(--accent);
            cursor: pointer;
        }
        .remember-row label {
            font-size: 0.82rem;
            color: var(--muted);
            cursor: pointer;
            user-select: none;
        }

        .btn-login {
            width: 100%;
            padding: 0.9rem;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 99px;
            font-size: 0.95rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            letter-spacing: 0.01em;
        }
        .btn-login:hover { background: #a8380c; }
        .btn-login:active { transform: scale(0.98); }

        /* ── ALERTS ─────────────────────────────────── */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }
        .alert-error {
            background: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
        .alert-info {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
        }
        .alert .mdi { font-size: 1.05rem; flex-shrink: 0; margin-top: 0.05rem; }

        /* ── FOOTER ─────────────────────────────────── */
        .login-footer {
            padding: 1rem 2.5rem 1.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.8rem;
            color: var(--muted);
            text-decoration: none;
            transition: color 0.15s;
        }
        .back-link:hover { color: var(--dark); }
        .brand {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 0.95rem;
            color: var(--dark);
            text-decoration: none;
        }
    </style>
</head>
<body>

    <div class="login-wrap">
        <div class="login-card">

            {{-- Header --}}
            <div class="login-header">
                <div class="login-header-badge">
                    <i class="mdi mdi-shield-crown-outline"></i> Admin panel
                </div>
                <h1>Welcome back</h1>
                <p>Sign in with your admin credentials to continue</p>
            </div>

            {{-- Form --}}
            <div class="login-body">

                {{-- Session error (from IsAdmin redirect) --}}
                @if (session('error'))
                    <div class="alert alert-info">
                        <i class="mdi mdi-information-outline"></i>
                        {{ session('error') }}
                    </div>
                @endif

                {{-- Validation errors --}}
                @if ($errors->any())
                    <div class="alert alert-error">
                        <i class="mdi mdi-alert-circle-outline"></i>
                        <div>{{ $errors->first() }}</div>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.post') }}" x-data="{ showPw: false }">
                    @csrf

                    <div class="field">
                        <label for="email">Email address</label>
                        <div class="field-inner">
                            <i class="mdi mdi-email-outline field-icon"></i>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   autofocus
                                   autocomplete="email"
                                   class="{{ $errors->has('email') ? 'is-error' : '' }}"
                                   required>
                        </div>
                    </div>

                    <div class="field">
                        <label for="password">Password</label>
                        <div class="field-inner">
                            <i class="mdi mdi-lock-outline field-icon"></i>
                            <input :type="showPw ? 'text' : 'password'"
                                   id="password"
                                   name="password"
                                   autocomplete="current-password"
                                   class="{{ $errors->has('password') ? 'is-error' : '' }}"
                                   required>
                            <button type="button" class="toggle-password" @click="showPw = !showPw" tabindex="-1">
                                <i :class="showPw ? 'mdi mdi-eye-off-outline' : 'mdi mdi-eye-outline'" class="mdi"></i>
                            </button>
                        </div>
                    </div>

                    <div class="remember-row">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Keep me signed in</label>
                    </div>

                    <button type="submit" class="btn-login">
                        <i class="mdi mdi-login"></i> Sign in to admin
                    </button>
                </form>
            </div>

            <div class="login-footer">
                <a href="{{ url('/') }}" class="back-link">
                    <i class="mdi mdi-arrow-left"></i> Back to site
                </a>
                <a href="{{ url('/') }}" class="brand">CelebrateMi</a>
            </div>
        </div>
    </div>

    {{-- Alpine.js (loaded via Vite) --}}
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

</body>
</html>
