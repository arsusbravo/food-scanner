@extends('layouts.public')

@section('page_title')KitchenLog — {{ __('docs.hero_title') }}@endsection
@section('meta_description', __('docs.hero_sub'))

@php
    $sections = [
        ['label' => '01', 'titleKey' => 'docs.nav_title',      'bodyKey' => null,                'images' => [1],             'extra' => true],
        ['label' => '02', 'titleKey' => 'docs.home_title',     'bodyKey' => 'docs.home_body',    'images' => [2],             'extra' => false],
        ['label' => '03', 'titleKey' => 'docs.log_title',      'bodyKey' => 'docs.log_body',     'images' => [3, 4],          'extra' => false],
        ['label' => '04', 'titleKey' => 'docs.scan_title',     'bodyKey' => 'docs.scan_body',    'images' => [5, 6, 7, 8],    'extra' => false],
        ['label' => '05', 'titleKey' => 'docs.entries_title',  'bodyKey' => 'docs.entries_body', 'images' => [9],             'extra' => false],
        ['label' => '06', 'titleKey' => 'docs.report_title',   'bodyKey' => 'docs.report_body',  'images' => [10, 11, 12],    'extra' => false],
        ['label' => '07', 'titleKey' => 'docs.insights_title', 'bodyKey' => 'docs.insights_body','images' => [13, 14],        'extra' => false],
        ['label' => '08', 'titleKey' => 'docs.settings_title', 'bodyKey' => 'docs.settings_body','images' => [15, 16, 17, 18, 19], 'extra' => false],
    ];
    $navItems = ['docs.nav_home', 'docs.nav_log', 'docs.nav_scan', 'docs.nav_report', 'docs.nav_insights'];
@endphp

@section('hero')
    <!-- Logo -->
    <div style="display: inline-flex; align-items: center; justify-content: center; width: 64px; height: 64px; background: rgba(255,255,255,0.15); border-radius: 20px; margin-bottom: 20px; border: 1.5px solid rgba(255,255,255,0.25);">
        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m16 2-2.3 2.3a3 3 0 0 0 0 4.2l1.8 1.8a3 3 0 0 0 4.2 0L22 8"/>
            <path d="M15 15 3.3 3.3a4.2 4.2 0 0 0 0 6l7.3 7.3c.7.7 2 .7 2.8 0L15 15Zm0 0 7 7"/>
            <path d="m2.1 21.8 6.4-6.3"/>
            <path d="m19 5-7 7"/>
        </svg>
    </div>

    <p style="color: rgba(167,243,208,0.85); font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; margin: 0 0 8px;">
        KitchenLog · User Guide
    </p>
    <h1 style="color: white; font-size: 34px; font-weight: 800; letter-spacing: -0.02em; margin: 0 0 12px; line-height: 1.15;">
        {{ __('docs.hero_title') }}
    </h1>
    <p style="color: rgba(209,250,229,0.9); font-size: 15px; line-height: 1.6; margin: 0 0 28px;">
        {{ __('docs.hero_sub') }}
    </p>

    <!-- Badges -->
    <div style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
        <span style="background: rgba(255,255,255,0.15); color: rgba(209,250,229,0.95); font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 999px; border: 1px solid rgba(255,255,255,0.2);">EU Directive 2018/851</span>
        <span style="background: rgba(255,255,255,0.15); color: rgba(209,250,229,0.95); font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 999px; border: 1px solid rgba(255,255,255,0.2);">FLW Protocol</span>
        <span style="background: rgba(255,255,255,0.15); color: rgba(209,250,229,0.95); font-size: 11px; font-weight: 700; padding: 4px 12px; border-radius: 999px; border: 1px solid rgba(255,255,255,0.2);">HORECA</span>
    </div>
@endsection

@section('content')

    <!-- CTA buttons -->
    <div style="max-width: 480px; margin: -28px auto 0; padding: 0 24px; position: relative; z-index: 10;">
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <a href="{{ route('register') }}" style="display: flex; align-items: center; justify-content: center; height: 54px; border-radius: 16px; background: linear-gradient(135deg, #059669, #047857); color: white; font-size: 16px; font-weight: 700; box-shadow: 0 8px 24px rgba(5,150,105,0.4);">
                {{ __('docs.cta_register') }}
            </a>
            <a href="{{ route('login') }}" style="display: flex; align-items: center; justify-content: center; height: 54px; border-radius: 16px; background: white; color: #059669; font-size: 16px; font-weight: 700; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 2px solid #d1fae5;">
                {{ __('docs.cta_login') }}
            </a>
        </div>
    </div>

    <!-- Why KitchenLog -->
    <div style="max-width: 480px; margin: 40px auto 0; padding: 0 24px;">
        <h2 style="font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.1em; margin: 0 0 12px;">
            {{ __('docs.why_title') }}
        </h2>
        <div style="background: white; border-radius: 18px; padding: 20px 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 12px;">
            @foreach([1, 2, 3] as $i)
            <div style="display: flex; gap: 12px; align-items: flex-start;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 1px;">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="m9 12 2 2 4-4"/>
                </svg>
                <p style="font-size: 14px; color: #374151; line-height: 1.5; margin: 0;">{{ __('docs.why_' . $i) }}</p>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Sections -->
    <div style="max-width: 480px; margin: 0 auto; padding: 0 24px;">
        @foreach($sections as $section)
        <div style="margin-top: 40px;">

            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                <span style="font-size: 11px; font-weight: 700; color: #94a3b8; letter-spacing: 0.08em;">{{ $section['label'] }}</span>
                <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0; letter-spacing: -0.01em;">{{ __($section['titleKey']) }}</h2>
            </div>

            @if($section['extra'])
            <div style="background: white; border-radius: 18px; padding: 20px 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; margin-bottom: 16px;">
                <p style="font-size: 14px; color: #64748b; margin: 0 0 14px; line-height: 1.5;">{{ __('docs.nav_intro') }}</p>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    @foreach($navItems as $key)
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <div style="width: 6px; height: 6px; border-radius: 50%; background: #059669; flex-shrink: 0; margin-top: 7px;"></div>
                        <p style="font-size: 14px; color: #374151; line-height: 1.5; margin: 0;">{{ __($key) }}</p>
                    </div>
                    @endforeach
                </div>
                <p style="font-size: 13px; color: #64748b; margin: 14px 0 0; line-height: 1.5; font-style: italic;">{{ __('docs.nav_flow') }}</p>
            </div>
            @else
            <div style="background: white; border-radius: 18px; padding: 20px 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f1f5f9; margin-bottom: 16px;">
                <p style="font-size: 14px; color: #374151; line-height: 1.65; margin: 0;">{{ __($section['bodyKey']) }}</p>
            </div>
            @endif

            <div style="display: flex; flex-direction: column; gap: 12px;">
                @foreach($section['images'] as $n)
                <img
                    src="/images/doc/{{ $n }}.png"
                    alt="{{ __($section['titleKey']) }} — screenshot {{ $n }}"
                    style="width: 100%; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.10); border: 1px solid #e2e8f0;"
                >
                @endforeach
            </div>

        </div>
        @endforeach
    </div>

    <!-- Footer CTA -->
    <div style="max-width: 480px; margin: 48px auto 0; padding: 0 24px 64px;">
        <div style="background: linear-gradient(160deg, #064e3b 0%, #059669 100%); border-radius: 24px; padding: 32px 24px; text-align: center;">
            <h3 style="color: white; font-size: 22px; font-weight: 800; margin: 0 0 8px; letter-spacing: -0.01em;">{{ __('docs.hero_title') }}</h3>
            <p style="color: rgba(209,250,229,0.9); font-size: 14px; margin: 0 0 24px; line-height: 1.5;">{{ __('docs.why_3') }}</p>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="{{ route('register') }}" style="display: flex; align-items: center; justify-content: center; height: 50px; border-radius: 14px; background: white; color: #059669; font-size: 15px; font-weight: 700;">
                    {{ __('docs.cta_register') }}
                </a>
                <a href="{{ route('login') }}" style="display: flex; align-items: center; justify-content: center; height: 50px; border-radius: 14px; background: rgba(255,255,255,0.15); color: white; font-size: 15px; font-weight: 700; border: 1.5px solid rgba(255,255,255,0.3);">
                    {{ __('docs.cta_login') }}
                </a>
            </div>
        </div>
        <div style="display: flex; gap: 16px; justify-content: center; margin-top: 24px;">
            <a href="{{ route('home') }}" style="font-size: 13px; color: #94a3b8; font-weight: 600;">← Home</a>
            <a href="{{ route('faq') }}" style="font-size: 13px; color: #059669; font-weight: 700;">FAQ</a>
        </div>
        <p style="font-size: 12px; color: #94a3b8; text-align: center; margin-top: 16px;">
            KitchenLog · EU Directive 2018/851 · FLW Protocol
        </p>
    </div>

@endsection
