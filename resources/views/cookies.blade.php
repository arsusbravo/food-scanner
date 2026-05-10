@extends('layouts.public')

@section('page_title', 'Cookie Policy — KitchenLog')
@section('meta_description', 'Information about the cookies KitchenLog uses and how to control them.')

@section('hero')
    <img src="/images/doc/logo-dark.png" alt="KitchenLog" style="width: 56px; height: 56px; object-fit: contain; display: block; margin: 0 auto 16px;" />
    <p style="color: rgba(167,243,208,0.85); font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; margin: 0 0 8px;">Legal</p>
    <h1 style="color: white; font-size: 32px; font-weight: 800; letter-spacing: -0.02em; margin: 0 0 10px;">Cookie Policy</h1>
    <p style="color: rgba(209,250,229,0.9); font-size: 14px; margin: 0;">Last updated: {{ date('d F Y') }}</p>
@endsection

@section('content')
<div style="max-width: 680px; margin: 0 auto; padding: 40px 24px 64px;">

    <div style="margin-bottom: 32px;">
        <h2 style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0 0 10px;">What are cookies?</h2>
        <p style="font-size: 14px; color: #374151; line-height: 1.75; margin: 0;">Cookies are small text files placed on your device by a website. They are widely used to make websites work, remember your preferences, and provide information to site owners.</p>
    </div>

    <div style="margin-bottom: 32px;">
        <h2 style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0 0 16px;">Cookies we use</h2>

        <!-- Essential -->
        <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 16px;">
            <div style="background: #f0fdf4; padding: 14px 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 13px; font-weight: 700; color: #059669; background: #dcfce7; padding: 2px 10px; border-radius: 999px;">Essential</span>
                <span style="font-size: 13px; color: #374151; font-weight: 600;">Always active — cannot be disabled</span>
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <th style="text-align: left; padding: 10px 20px; color: #64748b; font-weight: 600;">Name</th>
                            <th style="text-align: left; padding: 10px 20px; color: #64748b; font-weight: 600;">Purpose</th>
                            <th style="text-align: left; padding: 10px 20px; color: #64748b; font-weight: 600;">Expires</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr style="border-bottom: 1px solid #f8fafc;">
                            <td style="padding: 10px 20px; font-family: monospace; color: #0f172a;">kitchenlog_session</td>
                            <td style="padding: 10px 20px; color: #374151;">Keeps you logged in during your visit</td>
                            <td style="padding: 10px 20px; color: #64748b;">Session</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f8fafc;">
                            <td style="padding: 10px 20px; font-family: monospace; color: #0f172a;">XSRF-TOKEN</td>
                            <td style="padding: 10px 20px; color: #374151;">Security token to prevent cross-site request forgery</td>
                            <td style="padding: 10px 20px; color: #64748b;">Session</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 20px; font-family: monospace; color: #0f172a;">locale</td>
                            <td style="padding: 10px 20px; color: #374151;">Remembers your chosen interface language</td>
                            <td style="padding: 10px 20px; color: #64748b;">1 year</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Third-party -->
        <div style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 16px;">
            <div style="background: #fff7ed; padding: 14px 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 13px; font-weight: 700; color: #ea580c; background: #ffedd5; padding: 2px 10px; border-radius: 999px;">Third-party</span>
                <span style="font-size: 13px; color: #374151; font-weight: 600;">Set by Stripe during payment</span>
            </div>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <th style="text-align: left; padding: 10px 20px; color: #64748b; font-weight: 600;">Name</th>
                            <th style="text-align: left; padding: 10px 20px; color: #64748b; font-weight: 600;">Purpose</th>
                            <th style="text-align: left; padding: 10px 20px; color: #64748b; font-weight: 600;">Expires</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 10px 20px; font-family: monospace; color: #0f172a;">__stripe_*</td>
                            <td style="padding: 10px 20px; color: #374151;">Stripe fraud prevention and payment security. Only active during checkout.</td>
                            <td style="padding: 10px 20px; color: #64748b;">Session / 1 year</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="background: #f8fafc; border-radius: 12px; padding: 16px 20px; font-size: 13px; color: #64748b; line-height: 1.6;">
            <strong style="color: #0f172a;">No tracking or advertising cookies.</strong> KitchenLog does not use Google Analytics, Facebook Pixel, or any other analytics or advertising cookies.
        </div>
    </div>

    <div style="margin-bottom: 32px;">
        <h2 style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0 0 10px;">How to control cookies</h2>
        <p style="font-size: 14px; color: #374151; line-height: 1.75; margin: 0 0 10px;">You can control cookies through your browser settings. Note that disabling essential cookies will break login and other core functionality.</p>
        <ul style="font-size: 14px; color: #374151; line-height: 1.75; padding-left: 20px; margin: 0;">
            <li><a href="https://support.google.com/chrome/answer/95647" target="_blank" rel="noopener" style="color: #059669;">Chrome</a></li>
            <li><a href="https://support.mozilla.org/en-US/kb/cookies-information-websites-store-on-your-computer" target="_blank" rel="noopener" style="color: #059669;">Firefox</a></li>
            <li><a href="https://support.apple.com/guide/safari/manage-cookies-sfri11471" target="_blank" rel="noopener" style="color: #059669;">Safari</a></li>
            <li><a href="https://support.microsoft.com/en-us/microsoft-edge/delete-cookies-in-microsoft-edge" target="_blank" rel="noopener" style="color: #059669;">Edge</a></li>
        </ul>
    </div>

    <div style="margin-bottom: 32px;">
        <h2 style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0 0 10px;">Contact</h2>
        <p style="font-size: 14px; color: #374151; line-height: 1.75; margin: 0;">Questions about our cookie use? Email <a href="mailto:info@kitchenlog.eu" style="color: #059669;">info@kitchenlog.eu</a>.</p>
    </div>

    <div style="border-top: 1px solid #e2e8f0; padding-top: 24px; display: flex; gap: 16px; flex-wrap: wrap;">
        <a href="{{ route('privacy') }}" style="font-size: 13px; font-weight: 600; color: #059669;">Privacy Policy</a>
        <a href="{{ route('terms') }}" style="font-size: 13px; font-weight: 600; color: #059669;">Terms &amp; Conditions</a>
        <a href="{{ route('home') }}" style="font-size: 13px; font-weight: 600; color: #64748b;">← Home</a>
    </div>

</div>
@endsection
