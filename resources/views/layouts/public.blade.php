<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('page_title')</title>
    <meta name="description" content="@yield('meta_description')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="theme-color" content="#059669">

    {{-- Open Graph (Facebook, LinkedIn, WhatsApp, Slack, etc.) --}}
    <meta property="og:site_name" content="KitchenLog">
    <meta property="og:title" content="@yield('page_title')">
    <meta property="og:description" content="@yield('meta_description')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">
    <meta property="og:image" content="@yield('og_image', asset('images/doc/logo-light.png'))">
    <meta property="og:image:secure_url" content="@yield('og_image', secure_asset('images/doc/logo-light.png'))">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="164">
    <meta property="og:image:height" content="229">
    <meta property="og:image:alt" content="KitchenLog logo">

    {{-- Twitter / X card --}}
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="@yield('page_title')">
    <meta name="twitter:description" content="@yield('meta_description')">
    <meta name="twitter:image" content="@yield('og_image', asset('images/doc/logo-light.png'))">
    <meta name="twitter:image:alt" content="KitchenLog logo">
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
        $langs  = [
            'en'    => 'EN',
            'nl'    => 'NL',
            'de'    => 'DE',
            'fr'    => 'FR',
            'es'    => 'ES',
            'zh-TW' => '繁中',
            'zh-CN' => '简中',
            'tr'    => 'TR',
        ];
    @endphp

    <!-- Hero -->
    <div style="background: linear-gradient(160deg, #064e3b 0%, #059669 55%, #0d9488 100%); padding: 56px 24px 64px;">
        <div style="max-width: 480px; margin: 0 auto; text-align: center;">

            <!-- Language switcher -->
            <div style="display: flex; justify-content: center; margin-bottom: 20px;">
                <div style="display: inline-flex; gap: 2px; background: rgba(0,0,0,0.2); border-radius: 999px; padding: 3px;">
                    @foreach($langs as $code => $label)
                    <button
                        onclick="setLocale('{{ $code }}')"
                        style="border: none; font-size: 11px; padding: 4px 9px; border-radius: 999px; letter-spacing: 0.04em; {{ $locale === $code ? 'background: white; color: #059669; font-weight: 700;' : 'background: transparent; color: rgba(209,250,229,0.85); font-weight: 600;' }}"
                    >{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            @yield('hero')

        </div>
    </div>

    @yield('content')

    <!-- Global footer -->
    <div style="border-top: 1px solid #e2e8f0; padding: 24px; text-align: center;">
        <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 16px; font-size: 12px;">
            <a href="{{ route('privacy') }}" style="color: #64748b; font-weight: 600;">Privacy Policy</a>
            <a href="{{ route('terms') }}" style="color: #64748b; font-weight: 600;">Terms &amp; Conditions</a>
            <a href="{{ route('cookies') }}" style="color: #64748b; font-weight: 600;">Cookie Policy</a>
            <a href="{{ route('faq') }}" style="color: #64748b; font-weight: 600;">FAQ</a>
            <a href="{{ route('docs') }}" style="color: #64748b; font-weight: 600;">Docs</a>
        </div>
        <p style="font-size: 11px; color: #94a3b8; margin: 12px 0 0;">&copy; {{ date('Y') }} KitchenLog &mdash; Arsus B.V.</p>
    </div>

    <!-- Cookie consent banner -->
    <div id="cookie-banner" style="display:none; position:fixed; bottom:0; left:0; right:0; z-index:9999; background:white; border-top:1px solid #e2e8f0; padding:16px 20px; box-shadow:0 -4px 24px rgba(0,0,0,0.08);">
        <div style="max-width:680px; margin:0 auto; display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
            <p style="flex:1; font-size:13px; color:#374151; margin:0; min-width:200px;">
                We use essential cookies for login and language preference. No tracking or advertising cookies.
                <a href="{{ route('cookies') }}" style="color:#059669; font-weight:600;">Cookie Policy</a>
            </p>
            <div style="display:flex; gap:8px; flex-shrink:0;">
                <button onclick="acceptCookies()" style="background:#059669; color:white; border:none; padding:9px 20px; border-radius:10px; font-size:13px; font-weight:700;">Accept</button>
                <button onclick="acceptCookies()" style="background:#f1f5f9; color:#374151; border:none; padding:9px 20px; border-radius:10px; font-size:13px; font-weight:600;">Essential only</button>
            </div>
        </div>
    </div>

    <script>
        function setLocale(code) {
            document.cookie = 'locale=' + code + ';path=/;max-age=31536000;SameSite=Lax';
            localStorage.setItem('locale', code);
            location.reload();
        }
        function acceptCookies() {
            localStorage.setItem('cookie_consent', '1');
            document.getElementById('cookie-banner').style.display = 'none';
        }
        if (!localStorage.getItem('cookie_consent')) {
            document.getElementById('cookie-banner').style.display = 'block';
        }
    </script>
    @stack('scripts')

</body>
</html>
