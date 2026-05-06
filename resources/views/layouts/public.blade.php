<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('page_title')</title>
    <meta name="description" content="@yield('meta_description')">
    <meta property="og:title" content="@yield('page_title')">
    <meta property="og:description" content="@yield('meta_description')">
    <meta property="og:type" content="website">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body   { margin: 0; }
        button { cursor: pointer; font-family: inherit; }
        a      { text-decoration: none; }
        img    { max-width: 100%; height: auto; display: block; }
    </style>
    @stack('styles')
</head>
<body style="min-height: 100dvh; background: #f8fafc; font-family: Inter, system-ui, sans-serif;">

    @php
        $locale = app()->getLocale();
        $langs  = ['en', 'nl', 'de', 'fr', 'es'];
    @endphp

    <!-- Hero -->
    <div style="background: linear-gradient(160deg, #064e3b 0%, #059669 55%, #0d9488 100%); padding: 56px 24px 64px;">
        <div style="max-width: 480px; margin: 0 auto; text-align: center;">

            <!-- Language switcher -->
            <div style="display: flex; justify-content: flex-end; margin-bottom: 20px;">
                <div style="display: inline-flex; gap: 2px; background: rgba(0,0,0,0.2); border-radius: 999px; padding: 3px;">
                    @foreach($langs as $code)
                    <button
                        onclick="setLocale('{{ $code }}')"
                        style="border: none; font-size: 11px; padding: 4px 9px; border-radius: 999px; letter-spacing: 0.04em; {{ $locale === $code ? 'background: white; color: #059669; font-weight: 700;' : 'background: transparent; color: rgba(209,250,229,0.85); font-weight: 600;' }}"
                    >{{ strtoupper($code) }}</button>
                    @endforeach
                </div>
            </div>

            @yield('hero')

        </div>
    </div>

    @yield('content')

    <script>
        function setLocale(code) {
            document.cookie = 'locale=' + code + ';path=/;max-age=31536000;SameSite=Lax';
            location.reload();
        }
    </script>
    @stack('scripts')

</body>
</html>
