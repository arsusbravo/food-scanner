@extends('layouts.public')

@section('page_title', 'KitchenLog — EU Food Waste Tracker')
@section('meta_description', __('welcome.subtitle'))

@section('hero')
    <!-- Logo -->
    <img src="/images/doc/logo-dark.png" alt="KitchenLog" style="width: 64px; height: 64px; object-fit: contain; display: block; margin: 0 auto 20px;" />

    <p style="color: rgba(167,243,208,0.85); font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; margin-bottom: 8px;">
        {{ __('welcome.tagline') }}
    </p>
    <h1 style="color: white; font-size: 40px; font-weight: 800; letter-spacing: -0.02em; margin: 0 0 12px;">
        KitchenLog
    </h1>
    <p style="color: rgba(209,250,229,0.9); font-size: 17px; line-height: 1.5; margin: 0 0 28px;">
        {{ __('welcome.subtitle') }}
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
            @if($canRegister)
            <a href="{{ route('register') }}" style="display: flex; align-items: center; justify-content: center; height: 54px; border-radius: 16px; background: linear-gradient(135deg, #059669, #047857); color: white; font-size: 16px; font-weight: 700; box-shadow: 0 8px 24px rgba(5,150,105,0.4);">
                {{ __('welcome.cta_register') }}
            </a>
            @endif
            <a href="{{ route('login') }}" style="display: flex; align-items: center; justify-content: center; height: 54px; border-radius: 16px; background: white; color: #059669; font-size: 16px; font-weight: 700; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border: 2px solid #d1fae5;">
                {{ __('welcome.cta_login') }}
            </a>
        </div>
    </div>

    <!-- Features -->
    <div style="max-width: 480px; margin: 0 auto; padding: 40px 24px 0;">
        <h2 style="font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.1em; margin: 0 0 16px;">
            {{ __('welcome.features_title') }}
        </h2>
        <div style="display: flex; flex-direction: column; gap: 12px;">

            <div style="background: white; border-radius: 18px; padding: 20px; display: flex; gap: 16px; align-items: flex-start; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                <div style="width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: #ecfdf5;">
                    <img src="/images/doc/logo-light.png" alt="KitchenLog" style="width: 26px; height: 26px; object-fit: contain;" />
                </div>
                <div>
                    <p style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0 0 4px;">{{ __('welcome.feature_log_title') }}</p>
                    <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin: 0;">{{ __('welcome.feature_log_desc') }}</p>
                </div>
            </div>

            <div style="background: white; border-radius: 18px; padding: 20px; display: flex; gap: 16px; align-items: flex-start; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                <div style="width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: #f0fdfa;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 7V5a2 2 0 0 1 2-2h2"/>
                        <path d="M17 3h2a2 2 0 0 1 2 2v2"/>
                        <path d="M21 17v2a2 2 0 0 1-2 2h-2"/>
                        <path d="M7 21H5a2 2 0 0 1-2-2v-2"/>
                        <line x1="7" x2="17" y1="12" y2="12"/>
                    </svg>
                </div>
                <div>
                    <p style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0 0 4px;">{{ __('welcome.feature_scan_title') }}</p>
                    <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin: 0;">{{ __('welcome.feature_scan_desc') }}</p>
                </div>
            </div>

            <div style="background: white; border-radius: 18px; padding: 20px; display: flex; gap: 16px; align-items: flex-start; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
                <div style="width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: #f0f9ff;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0284c7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="12" x2="12" y1="18" y2="12"/>
                        <line x1="8" x2="8" y1="18" y2="16"/>
                        <line x1="16" x2="16" y1="18" y2="14"/>
                    </svg>
                </div>
                <div>
                    <p style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0 0 4px;">{{ __('welcome.feature_report_title') }}</p>
                    <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin: 0;">{{ __('welcome.feature_report_desc') }}</p>
                </div>
            </div>

        </div>
    </div>

    <!-- Highlights checklist -->
    <div style="max-width: 480px; margin: 0 auto; padding: 32px 24px 0;">
        <div style="background: white; border-radius: 18px; padding: 20px 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
            <h2 style="font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.1em; margin: 0 0 16px;">
                {{ __('welcome.highlights_title') }}
            </h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                @foreach(range(1, 6) as $i)
                <div style="display: flex; align-items: center; gap: 8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="m9 12 2 2 4-4"/>
                    </svg>
                    <span style="font-size: 12px; font-weight: 600; color: #374151;">{{ __('welcome.highlight_' . $i) }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div style="max-width: 480px; margin: 0 auto; padding: 32px 24px 48px; text-align: center;">
        <div style="display: flex; gap: 12px; justify-content: center; margin-bottom: 16px;">
            <a href="{{ route('docs') }}" style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 50%; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e2e8f0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                    <path d="M12 17h.01"/>
                </svg>
            </a>
            <a href="{{ route('faq') }}" style="display: inline-flex; align-items: center; justify-content: center; height: 40px; padding: 0 14px; border-radius: 999px; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; font-size: 12px; font-weight: 700; color: #059669; letter-spacing: 0.02em;">
                FAQ
            </a>
        </div>
        <p style="font-size: 12px; color: #94a3b8;">{{ __('welcome.footer') }}</p>
    </div>

@endsection

@if(!empty($inviteToken))
@push('scripts')
<script>localStorage.setItem('invite_token', '{{ $inviteToken }}');</script>
@endpush
@endif
