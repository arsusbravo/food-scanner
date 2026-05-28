<?php

return [
    'page_title'       => 'Cookie Policy — KitchenLog',
    'meta_description' => 'Information about the cookies KitchenLog uses and how to control them, under the EU ePrivacy Directive and GDPR.',
    'hero_eyebrow'     => 'Legal',
    'hero_title'       => 'Cookie Policy',
    'last_updated'     => 'Last updated: :date',

    'what_title' => 'What are cookies?',
    'what_body'  => 'Cookies are small text files placed on your device when you visit a website. They are widely used to make sites work, keep you signed in, remember your preferences and protect against fraud. Some cookies are set by the site itself ("first-party"); others by external services we rely on ("third-party").',

    'list_title' => 'Cookies we use',
    'col_name'    => 'Name',
    'col_purpose' => 'Purpose',
    'col_expires' => 'Expires',

    'essential_badge' => 'Essential',
    'essential_note'  => 'Always active — cannot be disabled (no consent required under Art. 5(3) ePrivacy)',
    'third_badge'     => 'Third-party',
    'third_note'      => 'Loaded only on payment / bot-protected pages',

    'session_purpose'    => 'Keeps you signed in during your visit',
    'xsrf_purpose'       => 'Security token preventing cross-site request forgery (CSRF)',
    'remember_purpose'   => 'Keeps you signed in across visits ("remember me")',
    'locale_purpose'     => 'Remembers your chosen interface language',
    'stripe_purpose'     => 'Stripe fraud-prevention and payment security. Loads only on the checkout page.',
    'cloudflare_purpose' => 'Cloudflare Turnstile bot-protection challenge. Loads only on sign-up and demo pages.',

    'expires_session' => 'Session',
    'expires_1y'      => '1 year',
    'expires_400d'    => '400 days',
    'expires_stripe'  => 'Up to 1 year',
    'expires_30m'     => '30 minutes',

    'no_tracking_title' => 'No advertising cookies.',
    'no_tracking_body'  => 'KitchenLog never loads advertising cookies. Analytics is only loaded if you click "Accept all" in the cookie banner — it stays off if you choose "Essential only", and you can change your mind at any time by clearing the kitchenlog_consent cookie.',

    'consent_title' => 'Consent',
    'consent_body'  => '
        <p>The essential cookies above are <strong>strictly necessary</strong> to deliver the service you request (signing in, billing, security, remembering your language and your cookie choice). Under Article 5(3) of the EU ePrivacy Directive and the corresponding national laws, strictly necessary cookies do not require prior consent and you cannot opt out of them while still using the corresponding feature.</p>
        <p><strong>Analytics is opt-in.</strong> Only if you click "Accept all" in the cookie banner do we load <strong>Google Tag Manager</strong> (container <code>GTM-PG3QTV73</code>) and <strong>Google Analytics 4</strong> (measurement ID <code>G-HT4N2PNR2D</code>), which together help us understand how the site is used. GA4 sets the <code>_ga</code> and <code>_ga_HT4N2PNR2D</code> cookies (2 years) to distinguish visitors and persist session state. Choosing "Essential only" keeps GTM, GA4 and all analytics cookies off. Your choice is stored in the <code>kitchenlog_consent</code> cookie (1 year). You can withdraw consent at any time by clearing that cookie. We follow the EDPB <em>Guidelines 05/2020 on consent</em>.</p>
    ',

    'control_title' => 'How to control cookies',
    'control_body'  => 'You can manage or delete cookies through your browser settings. Note that disabling essential cookies will break sign-in and other core functionality.',

    'contact_title' => 'Contact',
    'contact_body'  => 'Questions about our use of cookies? Email <a href="mailto:info@kitchenlog.eu">info@kitchenlog.eu</a>.',

    'nav_privacy' => 'Privacy Policy',
    'nav_terms'   => 'Terms & Conditions',
    'nav_home'    => '← Home',
];
