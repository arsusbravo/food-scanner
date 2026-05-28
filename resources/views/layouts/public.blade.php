<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@php
    // Only load Google Tag Manager when the visitor has explicitly opted into
    // analytics via the cookie-consent banner. The cookie is unencrypted
    // (see bootstrap/app.php encryptCookies except list) so the JS that sets
    // it from the banner and the PHP that reads it agree on the same value.
    $gtmConsent = request()->cookie('kitchenlog_consent') === 'analytics';
@endphp
<head>
    @if($gtmConsent)
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-PG3QTV73');</script>
    <!-- End Google Tag Manager -->

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-HT4N2PNR2D"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'G-HT4N2PNR2D');
    </script>
    @endif
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
    @if($gtmConsent)
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-PG3QTV73"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    @endif

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
            @guest
            <a href="{{ route('register') }}" style="color: #64748b; font-weight: 600;">{{ __('welcome.cta_register') }}</a>
            @endguest
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
                We use essential cookies to keep you signed in and remember your language. With your consent we also load Google Tag Manager to understand how the site is used. No advertising cookies.
                <a href="{{ route('cookies') }}" style="color:#059669; font-weight:600;">Cookie Policy</a>
            </p>
            <div style="display:flex; gap:8px; flex-shrink:0;">
                <button onclick="setConsent('analytics')" style="background:#059669; color:white; border:none; padding:9px 20px; border-radius:10px; font-size:13px; font-weight:700;">Accept all</button>
                <button onclick="setConsent('essential')" style="background:#f1f5f9; color:#374151; border:none; padding:9px 20px; border-radius:10px; font-size:13px; font-weight:600;">Essential only</button>
            </div>
        </div>
    </div>

    <script>
        function setLocale(code) {
            document.cookie = 'locale=' + code + ';path=/;max-age=31536000;SameSite=Lax';
            localStorage.setItem('locale', code);
            location.reload();
        }

        // ── Cookie consent ────────────────────────────────────────────────
        // We store the visitor's choice in a plain (unencrypted) cookie named
        // kitchenlog_consent — see bootstrap/app.php encryptCookies except list.
        // Server-side, the Blade head/body conditionally renders the GTM
        // snippets when the cookie is set to "analytics". Client-side we also
        // inject GTM on the spot when the user clicks "Accept all" so they
        // don't have to reload before tracking begins (or, conversely, never
        // gets loaded if they pick Essential only).
        function readConsentCookie() {
            const m = document.cookie.match(/(?:^|; )kitchenlog_consent=([^;]+)/);
            return m ? decodeURIComponent(m[1]) : null;
        }
        function setConsent(value) {
            document.cookie = 'kitchenlog_consent=' + value + ';path=/;max-age=31536000;SameSite=Lax';
            document.getElementById('cookie-banner').style.display = 'none';
            if (value === 'analytics' && !window.dataLayer) {
                injectAnalytics();
            }
        }
        function injectAnalytics() {
            // Google Tag Manager
            (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
            new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
            j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
            'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
            })(window,document,'script','dataLayer','GTM-PG3QTV73');

            // Google Analytics (gtag.js)
            var ga = document.createElement('script');
            ga.async = true;
            ga.src = 'https://www.googletagmanager.com/gtag/js?id=G-HT4N2PNR2D';
            document.head.appendChild(ga);
            window.gtag = function(){window.dataLayer.push(arguments);};
            window.gtag('js', new Date());
            window.gtag('config', 'G-HT4N2PNR2D');
        }
        if (!readConsentCookie()) {
            document.getElementById('cookie-banner').style.display = 'block';
        }
    </script>
    @stack('scripts')

</body>
</html>
