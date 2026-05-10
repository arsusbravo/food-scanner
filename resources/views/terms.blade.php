@extends('layouts.public')

@section('page_title', 'Terms & Conditions — KitchenLog')
@section('meta_description', 'Terms and conditions for using the KitchenLog food waste tracking platform.')

@section('hero')
    <img src="/images/doc/logo-dark.png" alt="KitchenLog" style="width: 56px; height: 56px; object-fit: contain; display: block; margin: 0 auto 16px;" />
    <p style="color: rgba(167,243,208,0.85); font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; margin: 0 0 8px;">Legal</p>
    <h1 style="color: white; font-size: 32px; font-weight: 800; letter-spacing: -0.02em; margin: 0 0 10px;">Terms &amp; Conditions</h1>
    <p style="color: rgba(209,250,229,0.9); font-size: 14px; margin: 0;">Last updated: {{ date('d F Y') }}</p>
@endsection

@section('content')
<div style="max-width: 680px; margin: 0 auto; padding: 40px 24px 64px;">

    @php
    $sections = [
        ['title' => '1. About KitchenLog', 'body' => '
            <p>KitchenLog is a food waste tracking platform operated by ARSUS IT Solutions (Netherlands). By creating an account or using KitchenLog you agree to these Terms & Conditions in full.</p>
            <p>If you do not agree, do not use the service.</p>
        '],
        ['title' => '2. Eligibility', 'body' => '
            <p>You must be at least 18 years old and authorised to enter into contracts on behalf of the business you represent. KitchenLog is intended for professional (B2B) use by HORECA businesses in the EU.</p>
        '],
        ['title' => '3. Your account', 'body' => '
            <ul>
                <li>You are responsible for keeping your login credentials secure.</li>
                <li>You may not share your account or allow others to access it.</li>
                <li>You are responsible for all activity that occurs under your account.</li>
                <li>Notify us immediately at <a href="mailto:info@kitchenlog.eu">info@kitchenlog.eu</a> if you suspect unauthorised access.</li>
            </ul>
        '],
        ['title' => '4. Free plan', 'body' => '
            <p>The free plan allows unlimited manual waste logging and on-screen EU compliance reports. AI scanning and PDF/CSV export are not available on the free plan. We reserve the right to modify or discontinue the free plan with 30 days notice.</p>
        '],
        ['title' => '5. Pro plan & payments', 'body' => '
            <ul>
                <li>The Pro plan is available monthly (€39/month) or annually (€390/year). All prices exclude applicable VAT.</li>
                <li>Payments are processed by <strong>Stripe Inc.</strong> We do not store your payment card details.</li>
                <li>The annual plan is a <strong>one-time payment</strong> for 12 months of Pro access. There is <strong>no automatic renewal</strong>.</li>
                <li>The monthly plan renews automatically each calendar month until cancelled.</li>
                <li>You can cancel anytime through your account settings. Cancellation takes effect at the end of the current billing period.</li>
                <li>We do not offer refunds for partial periods, except where required by law.</li>
            </ul>
        '],
        ['title' => '6. AI scan credits', 'body' => '
            <p>Pro plan subscribers receive 1,000 AI scan credits per month. Credits reset on the same calendar day each month. Unused credits do not roll over. Credits are consumed per scan attempt regardless of outcome.</p>
        '],
        ['title' => '7. Acceptable use', 'body' => '
            <p>You agree not to:</p>
            <ul>
                <li>Use KitchenLog for any unlawful purpose</li>
                <li>Attempt to reverse-engineer, scrape, or overload our systems</li>
                <li>Upload malicious content or attempt to compromise the security of the platform</li>
                <li>Resell or sublicense access to KitchenLog</li>
                <li>Use the AI scanning feature for purposes other than food waste logging</li>
            </ul>
            <p>Violation may result in immediate account termination without refund.</p>
        '],
        ['title' => '8. Data & exports', 'body' => '
            <p>All food waste data you enter belongs to you. We process it solely to provide the service. You can export your data at any time (Pro plan) in PDF or CSV format. Upon account deletion, your data is removed within 30 days except where we are legally required to retain it.</p>
        '],
        ['title' => '9. Compliance reports', 'body' => '
            <p>KitchenLog generates reports intended to assist with EU Directive 2018/851 compliance. These reports are based on data you enter. We do not guarantee that our reports satisfy every regulatory requirement in every jurisdiction. You remain responsible for verifying compliance with your local authority.</p>
        '],
        ['title' => '10. Service availability', 'body' => '
            <p>We aim for high availability but do not guarantee uninterrupted access. Planned maintenance will be communicated in advance where possible. We are not liable for losses caused by downtime.</p>
        '],
        ['title' => '11. Limitation of liability', 'body' => '
            <p>To the maximum extent permitted by law, Arsus B.V. is not liable for indirect, incidental, or consequential damages arising from your use of KitchenLog. Our total liability is limited to the amount you paid us in the 12 months preceding the claim.</p>
        '],
        ['title' => '12. Changes to these terms', 'body' => '
            <p>We may update these terms. For material changes, we will notify you by email at least 14 days in advance. Continued use after the effective date constitutes acceptance.</p>
        '],
        ['title' => '13. Governing law', 'body' => '
            <p>These terms are governed by the laws of the Netherlands. Disputes are subject to the exclusive jurisdiction of the courts of the Netherlands.</p>
        '],
        ['title' => '14. Contact', 'body' => '
            <p>Questions about these terms? Email us at <a href="mailto:info@kitchenlog.eu">info@kitchenlog.eu</a>.</p>
        '],
    ];
    @endphp

    @foreach($sections as $s)
    <div style="margin-bottom: 32px;">
        <h2 style="font-size: 17px; font-weight: 700; color: #0f172a; margin: 0 0 10px;">{{ $s['title'] }}</h2>
        <div style="font-size: 14px; color: #374151; line-height: 1.75;">
            {!! $s['body'] !!}
        </div>
    </div>
    @endforeach

    <div style="border-top: 1px solid #e2e8f0; padding-top: 24px; display: flex; gap: 16px; flex-wrap: wrap;">
        <a href="{{ route('privacy') }}" style="font-size: 13px; font-weight: 600; color: #059669;">Privacy Policy</a>
        <a href="{{ route('cookies') }}" style="font-size: 13px; font-weight: 600; color: #059669;">Cookie Policy</a>
        <a href="{{ route('home') }}" style="font-size: 13px; font-weight: 600; color: #64748b;">← Home</a>
    </div>

</div>
@endsection
