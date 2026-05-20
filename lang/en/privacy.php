<?php

return [
    'page_title'       => 'Privacy Policy — KitchenLog',
    'meta_description' => 'How KitchenLog collects, uses and protects your personal data under the GDPR.',
    'hero_eyebrow'     => 'Legal',
    'hero_title'       => 'Privacy Policy',
    'last_updated'     => 'Last updated: :date',

    's1_title' => '1. Who we are',
    's1_body'  => '
        <p>KitchenLog is a trading name of <strong>ARSUS IT Solutions</strong>, a sole proprietorship registered with the Dutch Chamber of Commerce (KvK). We operate a food-waste tracking platform for professional kitchens in the EU.</p>
        <p><strong>Data controller:</strong> ARSUS IT Solutions<br>
        <strong>KvK number:</strong> 76343251<br>
        <strong>Established in:</strong> The Netherlands (EU/EEA)<br>
        <strong>Contact:</strong> <a href="mailto:info@kitchenlog.eu">info@kitchenlog.eu</a></p>
        <p>This policy explains what personal data we process when you use KitchenLog, why we process it, and the rights you have under the EU General Data Protection Regulation (GDPR, Regulation (EU) 2016/679).</p>
    ',

    's2_title' => '2. What personal data we collect',
    's2_body'  => '
        <ul>
            <li><strong>Account data:</strong> name, email address, hashed password, company name, country, preferred language.</li>
            <li><strong>Usage data:</strong> the food-waste entries you log (item name, category, weight, reason, date, optional notes and photos).</li>
            <li><strong>Billing data:</strong> billing address, VAT number, invoice history. Payment-card details are handled by Stripe and are never stored on our servers.</li>
            <li><strong>Technical data:</strong> IP address, browser user-agent, device type, session identifiers, security logs.</li>
            <li><strong>Communication data:</strong> messages you send to our support inbox.</li>
        </ul>
    ',

    's3_title' => '3. Why we process it (legal basis)',
    's3_body'  => '
        <ul>
            <li><strong>Performance of a contract — Art. 6(1)(b) GDPR:</strong> creating and operating your account, storing your waste entries, generating reports.</li>
            <li><strong>Legal obligation — Art. 6(1)(c) GDPR:</strong> invoicing, VAT and tax-administration records.</li>
            <li><strong>Legitimate interests — Art. 6(1)(f) GDPR:</strong> security monitoring, fraud and abuse prevention, service improvement. We weigh these interests against your rights and freedoms before relying on this basis.</li>
            <li><strong>Consent — Art. 6(1)(a) GDPR:</strong> any non-essential processing (e.g. optional cookies) is only carried out after you have given consent, which you can withdraw at any time.</li>
        </ul>
    ',

    's4_title' => '4. How we use your data',
    's4_body'  => '
        <ul>
            <li>Operate, maintain and secure your KitchenLog account.</li>
            <li>Process the food-waste entries you log and generate the EU compliance reports you request.</li>
            <li>Bill your subscription and issue invoices through Stripe.</li>
            <li>Send strictly transactional emails (password reset, invoice receipts, security alerts).</li>
            <li>Detect, investigate and prevent abuse, fraud and security incidents.</li>
            <li>Comply with our legal obligations (tax, accounting, lawful requests from authorities).</li>
        </ul>
        <p>We do <strong>not</strong> sell your data, do <strong>not</strong> use it for advertising, and do <strong>not</strong> make automated decisions that produce legal effects about you.</p>
    ',

    's5_title' => '5. Who we share data with (sub-processors)',
    's5_body'  => '
        <p>We rely on a small number of carefully selected sub-processors, each bound by a Data Processing Agreement and committed to GDPR-compliant safeguards:</p>
        <ul>
            <li><strong>Stripe Payments Europe Ltd.</strong> (Ireland, EU) — subscription billing and payment processing. <a href="https://stripe.com/privacy" target="_blank" rel="noopener">Privacy policy</a>.</li>
            <li><strong>Anthropic, PBC / OpenRouter Inc.</strong> (USA) — AI analysis of food-waste photos that you upload. Photos are processed transiently and not retained by the AI provider for training.</li>
            <li><strong>Cloudflare, Inc.</strong> (USA) — bot protection (Turnstile) on the public sign-up and demo pages.</li>
            <li><strong>Brevo SAS</strong> (France, EU) — delivery of transactional email.</li>
            <li><strong>Hosting provider</strong> located in the EU/EEA — application and database hosting.</li>
        </ul>
        <p>For transfers outside the EU/EEA we rely on the European Commission’s <strong>Standard Contractual Clauses (SCCs)</strong> and additional safeguards where required (Art. 46 GDPR).</p>
    ',

    's6_title' => '6. How long we keep your data (retention)',
    's6_body'  => '
        <ul>
            <li><strong>Account data:</strong> for the lifetime of your account, plus up to 2 years after closure for security and dispute-handling.</li>
            <li><strong>Food-waste entries:</strong> for as long as the linked account is active. On account deletion, removed within 30 days unless legally required to retain.</li>
            <li><strong>Invoices and billing records:</strong> 7 years, as required by Dutch and EU tax law.</li>
            <li><strong>Server, security and access logs:</strong> up to 90 days.</li>
            <li><strong>Support correspondence:</strong> up to 3 years after the conversation ends.</li>
        </ul>
    ',

    's7_title' => '7. Your rights under the GDPR',
    's7_body'  => '
        <p>You have the right to:</p>
        <ul>
            <li><strong>Access</strong> — obtain confirmation that we process your data and a copy of it (Art. 15).</li>
            <li><strong>Rectification</strong> — have inaccurate or incomplete data corrected (Art. 16).</li>
            <li><strong>Erasure</strong> — request deletion of your data ("right to be forgotten") (Art. 17).</li>
            <li><strong>Restriction</strong> — limit the way we process your data in certain cases (Art. 18).</li>
            <li><strong>Portability</strong> — receive your data in a structured, machine-readable format (Art. 20).</li>
            <li><strong>Objection</strong> — object to processing carried out on the basis of our legitimate interests (Art. 21).</li>
            <li><strong>Withdraw consent</strong> at any time, where consent is the legal basis (Art. 7(3)).</li>
        </ul>
        <p>To exercise any of these rights, email <a href="mailto:info@kitchenlog.eu">info@kitchenlog.eu</a>. We will respond within 30 days, free of charge.</p>
        <p>You also have the right to lodge a complaint with a supervisory authority — in the Netherlands, the <a href="https://www.autoriteitpersoonsgegevens.nl" target="_blank" rel="noopener">Autoriteit Persoonsgegevens</a>; or with the authority in your country of habitual residence.</p>
    ',

    's8_title' => '8. Cookies',
    's8_body'  => '
        <p>We use only the cookies that are strictly necessary to provide the service (session, security token, language preference and "remember me"), plus the Stripe and Cloudflare cookies that load on payment and bot-protected pages respectively. No analytics or advertising cookies are used. See our <a href="/cookies">Cookie Policy</a> for the full list and how to control them.</p>
    ',

    's9_title' => '9. Security',
    's9_body'  => '
        <p>We protect your data with industry-standard measures: TLS/HTTPS encryption in transit, bcrypt-hashed passwords, encrypted session cookies, role-based access controls, vetted sub-processors and audit logs. We notify affected users without undue delay in the event of a personal-data breach with a likely risk to their rights and freedoms (Art. 33–34 GDPR).</p>
    ',

    's10_title' => '10. Changes to this policy',
    's10_body'  => '
        <p>We may update this policy from time to time. Material changes will be notified by email at least 14 days before they take effect. The "Last updated" date at the top of this page always shows the current version.</p>
    ',

    'nav_terms'   => 'Terms & Conditions',
    'nav_cookies' => 'Cookie Policy',
    'nav_home'    => '← Home',
];
