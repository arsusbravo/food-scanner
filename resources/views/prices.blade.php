@extends('layouts.public')

@section('page_title', __('prices.page_title'))
@section('meta_description', __('prices.meta_description'))

@section('hero')
    <img src="/images/doc/logo-dark.png" alt="KitchenLog" style="width: 56px; height: 56px; object-fit: contain; display: block; margin: 0 auto 16px;" />
    <p style="color: rgba(167,243,208,0.85); font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; margin: 0 0 8px;">{{ __('prices.hero_eyebrow') }}</p>
    <h1 style="color: white; font-size: 32px; font-weight: 800; letter-spacing: -0.02em; margin: 0 0 10px;">{{ __('prices.hero_title') }}</h1>
    <p style="color: rgba(209,250,229,0.9); font-size: 14px; line-height: 1.5; margin: 0;">{{ __('prices.hero_subtitle') }}</p>
@endsection

@section('content')
<div style="max-width: 720px; margin: 0 auto; padding: 32px 24px 64px;">

    {{-- Demo callout --}}
    <a href="{{ route('demo.index') }}" style="display: block; background: linear-gradient(160deg, #ecfdf5, #f0fdf4); border: 1px solid #a7f3d0; border-radius: 18px; padding: 16px 20px; margin-bottom: 28px; text-decoration: none;">
        <p style="font-size: 13px; font-weight: 800; color: #047857; margin: 0 0 4px; letter-spacing: 0.02em;">{{ __('prices.demo_title') }}</p>
        <p style="font-size: 13px; color: #065f46; line-height: 1.5; margin: 0 0 6px;">{{ __('prices.demo_body') }}</p>
        <p style="font-size: 13px; font-weight: 700; color: #059669; margin: 0;">{{ __('prices.demo_cta') }}</p>
    </a>

    {{-- Free plan card --}}
    <div style="background: white; border: 1px solid #e2e8f0; border-radius: 20px; padding: 28px 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.05); margin-bottom: 28px;">
        <div style="display: flex; align-items: baseline; gap: 12px; margin-bottom: 6px;">
            <span style="font-size: 11px; font-weight: 800; color: #64748b; background: #f1f5f9; padding: 4px 10px; border-radius: 999px; letter-spacing: 0.04em;">{{ __('prices.free_badge') }}</span>
            <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0;">{{ __('prices.free_title') }}</h2>
        </div>
        <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin: 0 0 14px;">{{ __('prices.free_intro') }}</p>

        <div style="display: flex; align-items: baseline; gap: 8px; margin-bottom: 18px;">
            <span style="font-size: 36px; font-weight: 800; color: #0f172a; letter-spacing: -0.02em;">{{ __('prices.free_price') }}</span>
            <span style="font-size: 14px; color: #94a3b8;">{{ __('prices.free_period') }}</span>
        </div>

        <ul style="list-style: none; padding: 0; margin: 0 0 22px;">
            @foreach (range(1, 5) as $i)
            <li style="display: flex; align-items: flex-start; gap: 10px; padding: 6px 0; font-size: 14px; color: #1e293b; line-height: 1.55;">
                <span style="flex-shrink: 0; width: 18px; height: 18px; border-radius: 999px; background: #ecfdf5; display: inline-flex; align-items: center; justify-content: center; margin-top: 1px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5 9-12"/></svg>
                </span>
                <span>{{ __('prices.free_feat_' . $i) }}</span>
            </li>
            @endforeach
        </ul>

        @auth
            {{-- Logged-in user is already past the free signup — send them into the app --}}
            <a href="{{ url('/waste') }}" style="display: flex; align-items: center; justify-content: center; height: 50px; border-radius: 14px; background: white; color: #059669; font-size: 15px; font-weight: 700; border: 2px solid #d1fae5; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                {{ __('prices.cta_open_app') }}
            </a>
        @else
            <a href="{{ route('register') }}" style="display: flex; align-items: center; justify-content: center; height: 50px; border-radius: 14px; background: white; color: #059669; font-size: 15px; font-weight: 700; border: 2px solid #d1fae5; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                {{ __('prices.free_cta') }}
            </a>
        @endauth
    </div>

    {{-- Pro section --}}
    <div style="text-align: center; margin: 36px 0 20px;">
        <h2 style="font-size: 22px; font-weight: 800; color: #0f172a; letter-spacing: -0.01em; margin: 0 0 6px;">{{ __('prices.pro_section_title') }}</h2>
        <p style="font-size: 14px; color: #64748b; max-width: 480px; margin: 0 auto; line-height: 1.5;">{{ __('prices.pro_section_sub') }}</p>
    </div>

    <div class="prices-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        {{-- Monthly --}}
        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 20px; padding: 24px 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.05); display: flex; flex-direction: column;">
            <p style="font-size: 13px; font-weight: 700; color: #475569; margin: 0 0 4px;">{{ __('prices.pro_monthly_title') }}</p>
            <p style="font-size: 32px; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; line-height: 1.1; margin: 0;">{{ __('prices.pro_monthly_price') }}</p>
            <p style="font-size: 12px; color: #94a3b8; margin: 4px 0 18px;">{{ __('prices.pro_monthly_per') }}</p>

            <ul style="list-style: none; padding: 0; margin: 0 0 18px; flex: 1;">
                @foreach (range(1, 6) as $i)
                <li style="display: flex; align-items: flex-start; gap: 8px; padding: 4px 0; font-size: 12.5px; color: #1e293b; line-height: 1.5;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 3px;"><path d="m5 12 5 5 9-12"/></svg>
                    <span>{{ __('prices.pro_feat_' . $i) }}</span>
                </li>
                @endforeach
            </ul>

            @auth
                <form method="POST" action="{{ route('waste.subscription.checkout') }}" style="margin: 0;">
                    @csrf
                    <input type="hidden" name="interval" value="monthly">
                    <button type="submit" style="cursor: pointer; border: none; width: 100%; display: flex; align-items: center; justify-content: center; height: 46px; border-radius: 12px; background: linear-gradient(135deg, #059669, #047857); color: white; font-size: 14px; font-weight: 700; box-shadow: 0 4px 12px rgba(5,150,105,0.25);">
                        {{ __('prices.pro_cta_monthly') }}
                    </button>
                </form>
            @else
                <a href="{{ route('register') }}" style="display: flex; align-items: center; justify-content: center; height: 46px; border-radius: 12px; background: linear-gradient(135deg, #059669, #047857); color: white; font-size: 14px; font-weight: 700; box-shadow: 0 4px 12px rgba(5,150,105,0.25);">
                    {{ __('prices.pro_cta_monthly') }}
                </a>
            @endauth
        </div>

        {{-- Annual (highlighted) --}}
        <div style="background: linear-gradient(160deg, #f0fdf4, #ecfdf5); border: 2px solid #6ee7b7; border-radius: 20px; padding: 24px 20px; box-shadow: 0 4px 20px rgba(5,150,105,0.12); display: flex; flex-direction: column; position: relative;">
            <span style="position: absolute; top: 14px; right: 14px; background: #059669; color: white; font-size: 10px; font-weight: 800; padding: 3px 9px; border-radius: 999px; letter-spacing: 0.04em;">{{ __('prices.pro_annual_save') }}</span>

            <p style="font-size: 13px; font-weight: 700; color: #475569; margin: 0 0 4px;">{{ __('prices.pro_annual_title') }}</p>
            <p style="font-size: 32px; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; line-height: 1.1; margin: 0;">{{ __('prices.pro_annual_price') }}</p>
            <p style="font-size: 12px; color: #059669; font-weight: 700; margin: 4px 0 0;">{{ __('prices.pro_annual_per_month') }}</p>
            <p style="font-size: 12px; color: #94a3b8; margin: 2px 0 18px;">{{ __('prices.pro_annual_per') }}</p>

            <ul style="list-style: none; padding: 0; margin: 0 0 18px; flex: 1;">
                @foreach (range(1, 6) as $i)
                <li style="display: flex; align-items: flex-start; gap: 8px; padding: 4px 0; font-size: 12.5px; color: #1e293b; line-height: 1.5;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 3px;"><path d="m5 12 5 5 9-12"/></svg>
                    <span>{{ __('prices.pro_feat_' . $i) }}</span>
                </li>
                @endforeach
            </ul>

            @auth
                <form method="POST" action="{{ route('waste.subscription.checkout') }}" style="margin: 0;">
                    @csrf
                    <input type="hidden" name="interval" value="annual">
                    <button type="submit" style="cursor: pointer; border: none; width: 100%; display: flex; align-items: center; justify-content: center; height: 46px; border-radius: 12px; background: linear-gradient(135deg, #059669, #047857); color: white; font-size: 14px; font-weight: 700; box-shadow: 0 6px 16px rgba(5,150,105,0.35);">
                        {{ __('prices.pro_cta_annual') }}
                    </button>
                </form>
            @else
                <a href="{{ route('register') }}" style="display: flex; align-items: center; justify-content: center; height: 46px; border-radius: 12px; background: linear-gradient(135deg, #059669, #047857); color: white; font-size: 14px; font-weight: 700; box-shadow: 0 6px 16px rgba(5,150,105,0.35);">
                    {{ __('prices.pro_cta_annual') }}
                </a>
            @endauth
        </div>
    </div>

    {{-- Fine print --}}
    <div style="margin: 28px auto 0; max-width: 560px; text-align: center; font-size: 12px; color: #64748b; line-height: 1.7;">
        <p style="margin: 0 0 6px; display: inline-flex; align-items: center; gap: 6px; color: #475569; font-weight: 600;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            {{ __('prices.trust_stripe') }}
        </p>
        <p style="margin: 0 0 6px;">{{ __('prices.vat_note') }}</p>
        <p style="margin: 0 0 6px;">{{ __('prices.cancel_note') }}</p>
        <p style="margin: 0;">{{ __('prices.pro_pricing_changes') }}</p>
    </div>

    {{-- FAQ link --}}
    <div style="text-align: center; margin: 24px 0 0;">
        <a href="{{ route('faq') }}" style="font-size: 13px; font-weight: 600; color: #059669;">{{ __('prices.faq_link') }}</a>
    </div>

    {{-- Back to home --}}
    <div style="border-top: 1px solid #e2e8f0; padding-top: 24px; margin-top: 32px; text-align: center;">
        <a href="{{ route('home') }}" style="font-size: 13px; font-weight: 600; color: #64748b;">{{ __('prices.nav_home') }}</a>
    </div>

</div>

<style>
    @media (max-width: 540px) {
        .prices-grid { grid-template-columns: 1fr !important; }
    }
</style>
@endsection
