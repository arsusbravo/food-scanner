@extends('layouts.public')

@section('page_title', 'Privacy Policy — KitchenLog')
@section('meta_description', 'How KitchenLog collects, uses, and protects your personal data.')

@section('hero')
    <img src="/images/doc/logo-dark.png" alt="KitchenLog" style="width: 56px; height: 56px; object-fit: contain; display: block; margin: 0 auto 16px;" />
    <p style="color: rgba(167,243,208,0.85); font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase; margin: 0 0 8px;">Legal</p>
    <h1 style="color: white; font-size: 32px; font-weight: 800; letter-spacing: -0.02em; margin: 0 0 10px;">Privacy Policy</h1>
    <p style="color: rgba(209,250,229,0.9); font-size: 14px; margin: 0;">Last updated: {{ date('d F Y') }}</p>
@endsection

@section('content')
<div style="max-width: 680px; margin: 0 auto; padding: 40px 24px 64px;">

    @php
    $sections = [
        ['title' => '1. Who we are', 'body' => '
            <p>KitchenLog is een handelsnaam van de eenmanszaak ARSUS IT Solutions, geregistreerd in het Nederlandse Handelsregister van de Kamer van Koophandel. Wij bieden een platform voor het bijhouden van voedselafval voor professionele keukens in de EU.</p>
            <p><strong>Verwerkingsverantwoordelijke (Data Controller):</strong> ARSUS IT Solutions<br>
            <strong>KvK-nummer:</strong> 76343251<br>
            <strong>Contact:</strong> <a href="mailto:info@kitchenlog.eu">info@kitchenlog.eu</a></p>
        '],
        ['title' => '2. What data we collect', 'body' => '
            <p>We collect the following personal data:</p>
            <ul>
                <li><strong>Account data:</strong> name, email address, password (hashed), country</li>
                <li><strong>Usage data:</strong> food waste entries you log (item name, category, weight, date)</li>
                <li><strong>Payment data:</strong> billing address, payment method (processed by Stripe — we never store card numbers)</li>
                <li><strong>Technical data:</strong> IP address, browser type, session identifiers</li>
                <li><strong>Communication data:</strong> emails you send to us</li>
            </ul>
        '],
        ['title' => '3. Legal basis for processing', 'body' => '
            <ul>
                <li><strong>Contract performance (Art. 6(1)(b) GDPR):</strong> processing your account and food waste entries to provide the service</li>
                <li><strong>Legal obligation (Art. 6(1)(c) GDPR):</strong> invoicing and tax records</li>
                <li><strong>Legitimate interest (Art. 6(1)(f) GDPR):</strong> security, fraud prevention, service improvement</li>
                <li><strong>Consent (Art. 6(1)(a) GDPR):</strong> non-essential cookies (where applicable)</li>
            </ul>
        '],
        ['title' => '4. How we use your data', 'body' => '
            <ul>
                <li>Provide and maintain your KitchenLog account</li>
                <li>Process food waste entries and generate EU compliance reports</li>
                <li>Process subscription payments via Stripe</li>
                <li>Send transactional emails (password reset, invoice receipts)</li>
                <li>Detect and prevent fraud and abuse</li>
                <li>Comply with applicable laws and regulations</li>
            </ul>
        '],
        ['title' => '5. Third-party processors', 'body' => '
            <p>We share data with the following sub-processors under data processing agreements:</p>
            <ul>
                <li><strong>Stripe Inc.</strong> (USA) — payment processing. <a href="https://stripe.com/privacy" target="_blank" rel="noopener">Stripe Privacy Policy</a></li>
                <li><strong>OpenRouter Inc.</strong> (USA) — AI image analysis for food waste scanning</li>
                <li><strong>Brevo SAS</strong> (France) — transactional email delivery</li>
            </ul>
            <p>Transfers outside the EEA are covered by Standard Contractual Clauses (SCCs).</p>
        '],
        ['title' => '6. Data retention', 'body' => '
            <ul>
                <li><strong>Account data:</strong> retained for the duration of your account plus 2 years after closure</li>
                <li><strong>Food waste entries:</strong> retained as long as your account is active</li>
                <li><strong>Payment records:</strong> retained for 7 years (legal obligation)</li>
                <li><strong>Server logs:</strong> retained for 90 days</li>
            </ul>
        '],
        ['title' => '7. Your rights', 'body' => '
            <p>Under GDPR you have the right to:</p>
            <ul>
                <li><strong>Access</strong> the personal data we hold about you</li>
                <li><strong>Correct</strong> inaccurate data</li>
                <li><strong>Delete</strong> your data ("right to be forgotten")</li>
                <li><strong>Restrict</strong> or <strong>object to</strong> processing</li>
                <li><strong>Data portability</strong> — receive your data in a machine-readable format</li>
                <li><strong>Withdraw consent</strong> at any time (where processing is based on consent)</li>
            </ul>
            <p>To exercise any right, email us at <a href="mailto:info@kitchenlog.eu">info@kitchenlog.eu</a>. We respond within 30 days.</p>
            <p>You may also lodge a complaint with your national supervisory authority. In the Netherlands: <a href="https://www.autoriteitpersoonsgegevens.nl" target="_blank" rel="noopener">Autoriteit Persoonsgegevens</a>.</p>
        '],
        ['title' => '8. Cookies', 'body' => '
            <p>We use cookies for essential functionality only (session management and language preference). See our <a href="/cookies">Cookie Policy</a> for the full list.</p>
        '],
        ['title' => '9. Security', 'body' => '
            <p>We use industry-standard measures including HTTPS encryption, hashed passwords (bcrypt), and access controls. No system is 100% secure; we encourage you to use a strong, unique password.</p>
        '],
        ['title' => '10. Changes to this policy', 'body' => '
            <p>We may update this policy periodically. We will notify you by email for material changes. The "last updated" date at the top reflects the latest revision.</p>
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
        <a href="{{ route('terms') }}" style="font-size: 13px; font-weight: 600; color: #059669;">Terms &amp; Conditions</a>
        <a href="{{ route('cookies') }}" style="font-size: 13px; font-weight: 600; color: #059669;">Cookie Policy</a>
        <a href="{{ route('home') }}" style="font-size: 13px; font-weight: 600; color: #64748b;">← Home</a>
    </div>

</div>

<style>
    .legal-content ul { padding-left: 20px; margin: 8px 0; }
    .legal-content li { margin-bottom: 6px; }
    .legal-content p  { margin: 0 0 10px; }
    .legal-content a  { color: #059669; }
</style>
@endsection
