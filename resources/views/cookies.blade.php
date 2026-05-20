@extends('layouts.public')

@section('page_title', __('cookies.page_title'))
@section('meta_description', __('cookies.meta_description'))

@section('hero')
    <img src="/images/doc/logo-dark.png" alt="KitchenLog" style="width: 56px; height: 56px; object-fit: contain; display: block; margin: 0 auto 16px;" />
    <p style="color: rgba(167,243,208,0.85); font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; margin: 0 0 8px;">{{ __('cookies.hero_eyebrow') }}</p>
    <h1 style="color: white; font-size: 32px; font-weight: 800; letter-spacing: -0.02em; margin: 0 0 10px;">{{ __('cookies.hero_title') }}</h1>
    <p style="color: rgba(209,250,229,0.9); font-size: 14px; margin: 0;">{{ __('cookies.last_updated', ['date' => date('d F Y')]) }}</p>
@endsection

@section('content')
<div style="max-width: 680px; margin: 0 auto; padding: 40px 24px 64px;">

    <div style="margin-bottom: 32px;">
        <h2 style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0 0 10px;">{{ __('cookies.what_title') }}</h2>
        <p style="font-size: 14px; color: #374151; line-height: 1.75; margin: 0;">{{ __('cookies.what_body') }}</p>
    </div>

    <div style="margin-bottom: 32px;">
        <h2 style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0 0 16px;">{{ __('cookies.list_title') }}</h2>

        <!-- Essential -->
        <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 16px;">
            <div style="background: #f0fdf4; padding: 14px 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 13px; font-weight: 700; color: #059669; background: #dcfce7; padding: 2px 10px; border-radius: 999px;">{{ __('cookies.essential_badge') }}</span>
                <span style="font-size: 13px; color: #374151; font-weight: 600;">{{ __('cookies.essential_note') }}</span>
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <th style="text-align: left; padding: 10px 20px; color: #64748b; font-weight: 600;">{{ __('cookies.col_name') }}</th>
                            <th style="text-align: left; padding: 10px 20px; color: #64748b; font-weight: 600;">{{ __('cookies.col_purpose') }}</th>
                            <th style="text-align: left; padding: 10px 20px; color: #64748b; font-weight: 600;">{{ __('cookies.col_expires') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid #f8fafc;">
                            <td style="padding: 10px 20px; font-family: monospace; color: #0f172a;">kitchenlog_session</td>
                            <td style="padding: 10px 20px; color: #374151;">{{ __('cookies.session_purpose') }}</td>
                            <td style="padding: 10px 20px; color: #64748b;">{{ __('cookies.expires_session') }}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f8fafc;">
                            <td style="padding: 10px 20px; font-family: monospace; color: #0f172a;">XSRF-TOKEN</td>
                            <td style="padding: 10px 20px; color: #374151;">{{ __('cookies.xsrf_purpose') }}</td>
                            <td style="padding: 10px 20px; color: #64748b;">{{ __('cookies.expires_session') }}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f8fafc;">
                            <td style="padding: 10px 20px; font-family: monospace; color: #0f172a;">remember_web_*</td>
                            <td style="padding: 10px 20px; color: #374151;">{{ __('cookies.remember_purpose') }}</td>
                            <td style="padding: 10px 20px; color: #64748b;">{{ __('cookies.expires_400d') }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 20px; font-family: monospace; color: #0f172a;">locale</td>
                            <td style="padding: 10px 20px; color: #374151;">{{ __('cookies.locale_purpose') }}</td>
                            <td style="padding: 10px 20px; color: #64748b;">{{ __('cookies.expires_1y') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Third-party -->
        <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 16px;">
            <div style="background: #fff7ed; padding: 14px 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 13px; font-weight: 700; color: #ea580c; background: #ffedd5; padding: 2px 10px; border-radius: 999px;">{{ __('cookies.third_badge') }}</span>
                <span style="font-size: 13px; color: #374151; font-weight: 600;">{{ __('cookies.third_note') }}</span>
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <th style="text-align: left; padding: 10px 20px; color: #64748b; font-weight: 600;">{{ __('cookies.col_name') }}</th>
                            <th style="text-align: left; padding: 10px 20px; color: #64748b; font-weight: 600;">{{ __('cookies.col_purpose') }}</th>
                            <th style="text-align: left; padding: 10px 20px; color: #64748b; font-weight: 600;">{{ __('cookies.col_expires') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid #f8fafc;">
                            <td style="padding: 10px 20px; font-family: monospace; color: #0f172a;">__stripe_*</td>
                            <td style="padding: 10px 20px; color: #374151;">{{ __('cookies.stripe_purpose') }}</td>
                            <td style="padding: 10px 20px; color: #64748b;">{{ __('cookies.expires_stripe') }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 20px; font-family: monospace; color: #0f172a;">cf_*</td>
                            <td style="padding: 10px 20px; color: #374151;">{{ __('cookies.cloudflare_purpose') }}</td>
                            <td style="padding: 10px 20px; color: #64748b;">{{ __('cookies.expires_30m') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="background: #f8fafc; border-radius: 12px; padding: 16px 20px; font-size: 13px; color: #64748b; line-height: 1.6;">
            <strong style="color: #0f172a;">{{ __('cookies.no_tracking_title') }}</strong> {{ __('cookies.no_tracking_body') }}
        </div>
    </div>

    <div style="margin-bottom: 32px;">
        <h2 style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0 0 10px;">{{ __('cookies.consent_title') }}</h2>
        <div style="font-size: 14px; color: #374151; line-height: 1.75;" class="legal-content">
            {!! __('cookies.consent_body') !!}
        </div>
    </div>

    <div style="margin-bottom: 32px;">
        <h2 style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0 0 10px;">{{ __('cookies.control_title') }}</h2>
        <p style="font-size: 14px; color: #374151; line-height: 1.75; margin: 0 0 10px;">{{ __('cookies.control_body') }}</p>
        <ul style="font-size: 14px; color: #374151; line-height: 1.75; padding-left: 20px; margin: 0;">
            <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener" style="color: #059669;">Chrome</a></li>
            <li><a href="https://support.mozilla.org/en-US/kb/cookies-information-websites-store-on-your-computer" target="_blank" rel="noopener" style="color: #059669;">Firefox</a></li>
            <li><a href="https://support.apple.com/guide/safari/manage-cookies-sfri11471" target="_blank" rel="noopener" style="color: #059669;">Safari</a></li>
            <li><a href="https://support.microsoft.com/en-us/microsoft-edge/delete-cookies-in-microsoft-edge" target="_blank" rel="noopener" style="color: #059669;">Edge</a></li>
        </ul>
    </div>

    <div style="margin-bottom: 32px;">
        <h2 style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0 0 10px;">{{ __('cookies.contact_title') }}</h2>
        <p style="font-size: 14px; color: #374151; line-height: 1.75; margin: 0;">{!! __('cookies.contact_body') !!}</p>
    </div>

    <div style="border-top: 1px solid #e2e8f0; padding-top: 24px; display: flex; gap: 16px; flex-wrap: wrap;">
        <a href="{{ route('privacy') }}" style="font-size: 13px; font-weight: 600; color: #059669;">{{ __('cookies.nav_privacy') }}</a>
        <a href="{{ route('terms') }}" style="font-size: 13px; font-weight: 600; color: #059669;">{{ __('cookies.nav_terms') }}</a>
        <a href="{{ route('home') }}" style="font-size: 13px; font-weight: 600; color: #64748b;">{{ __('cookies.nav_home') }}</a>
    </div>

</div>

<style>
    .legal-content ul { padding-left: 20px; margin: 8px 0; }
    .legal-content li { margin-bottom: 6px; }
    .legal-content p  { margin: 0 0 10px; }
    .legal-content a  { color: #059669; }
</style>
@endsection
