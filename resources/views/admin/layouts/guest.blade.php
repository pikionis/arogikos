<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Διαχείριση') — {{ setting('org_name', 'Αρωγή') }}</title>
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
</head>
<body>
    <main class="a-auth">
        <div class="a-auth-card">
            <div style="text-align:center;margin-bottom:24px">
                <div style="display:inline-flex;align-items:center;gap:9px;font-family:var(--font-head);font-weight:700;font-size:1.15rem">
                    <svg width="30" height="30" viewBox="0 0 40 40" aria-hidden="true">
                        <rect x="2" y="30" width="36" height="5" rx="2.5" fill="#1E9E58"/>
                        <path d="M9 32 A11 11 0 0 1 31 32" fill="none" stroke="#E26D5A" stroke-width="5" stroke-linecap="round"/>
                        <circle cx="20" cy="12" r="5" fill="#F4B62A"/>
                    </svg>
                    {{ setting('org_name', 'Αρωγή') }}
                </div>
                <p style="color:var(--color-ink-400);font-size:.88rem;margin-top:4px">Διαχείριση περιεχομένου</p>
            </div>

            @if (session('status'))
                <div class="a-alert a-alert-ok">{{ session('status') }}</div>
            @endif

            @yield('content')
        </div>

        <p style="margin-top:18px;font-size:.84rem;color:var(--color-ink-400)">
            <a href="{{ route('home') }}" style="color:inherit">← Επιστροφή στο site</a>
        </p>
    </main>
</body>
</html>
