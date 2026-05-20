<?php

return [
    'page_title'       => 'Datenschutzerklärung — KitchenLog',
    'meta_description' => 'Wie KitchenLog Ihre personenbezogenen Daten gemäß DSGVO erhebt, verwendet und schützt.',
    'hero_eyebrow'     => 'Rechtliches',
    'hero_title'       => 'Datenschutzerklärung',
    'last_updated'     => 'Zuletzt aktualisiert: :date',

    's1_title' => '1. Wer wir sind',
    's1_body'  => '
        <p>KitchenLog ist ein Handelsname von <strong>ARSUS IT Solutions</strong>, einem Einzelunternehmen, eingetragen bei der niederländischen Handelskammer (KvK). Wir betreiben eine Plattform zur Erfassung von Lebensmittelabfällen für professionelle Küchen in der EU.</p>
        <p><strong>Verantwortlicher:</strong> ARSUS IT Solutions<br>
        <strong>KvK-Nummer:</strong> 76343251<br>
        <strong>Sitz:</strong> Niederlande (EU/EWR)<br>
        <strong>Kontakt:</strong> <a href="mailto:info@kitchenlog.eu">info@kitchenlog.eu</a></p>
        <p>Diese Erklärung beschreibt, welche personenbezogenen Daten wir verarbeiten, wenn Sie KitchenLog nutzen, warum wir dies tun und welche Rechte Sie nach der Datenschutz-Grundverordnung (DSGVO, Verordnung (EU) 2016/679) haben.</p>
    ',

    's2_title' => '2. Welche personenbezogenen Daten wir erheben',
    's2_body'  => '
        <ul>
            <li><strong>Kontodaten:</strong> Name, E-Mail-Adresse, gehashtes Passwort, Firmenname, Land, bevorzugte Sprache.</li>
            <li><strong>Nutzungsdaten:</strong> die von Ihnen erfassten Lebensmittelabfälle (Bezeichnung, Kategorie, Gewicht, Grund, Datum, optionale Notizen und Fotos).</li>
            <li><strong>Rechnungsdaten:</strong> Rechnungsadresse, USt-IdNr., Rechnungsverlauf. Zahlungskartendaten werden ausschließlich von Stripe verarbeitet und niemals auf unseren Servern gespeichert.</li>
            <li><strong>Technische Daten:</strong> IP-Adresse, Browser-User-Agent, Gerätetyp, Sitzungs-IDs, Sicherheitsprotokolle.</li>
            <li><strong>Kommunikationsdaten:</strong> Nachrichten, die Sie an unseren Support senden.</li>
        </ul>
    ',

    's3_title' => '3. Warum wir Daten verarbeiten (Rechtsgrundlagen)',
    's3_body'  => '
        <ul>
            <li><strong>Vertragserfüllung — Art. 6 Abs. 1 lit. b DSGVO:</strong> Einrichtung und Betrieb Ihres Kontos, Speicherung Ihrer Einträge, Erstellung von Berichten.</li>
            <li><strong>Rechtliche Verpflichtung — Art. 6 Abs. 1 lit. c DSGVO:</strong> Rechnungsstellung sowie steuer- und buchhaltungsrechtliche Aufbewahrung.</li>
            <li><strong>Berechtigte Interessen — Art. 6 Abs. 1 lit. f DSGVO:</strong> Sicherheitsüberwachung, Betrugs- und Missbrauchsprävention, Verbesserung des Dienstes. Wir wägen unsere Interessen stets gegen Ihre Rechte und Freiheiten ab, bevor wir uns auf diese Rechtsgrundlage stützen.</li>
            <li><strong>Einwilligung — Art. 6 Abs. 1 lit. a DSGVO:</strong> Nicht zwingend erforderliche Verarbeitungen (z. B. optionale Cookies) erfolgen nur nach Ihrer Einwilligung, die Sie jederzeit widerrufen können.</li>
        </ul>
    ',

    's4_title' => '4. Wie wir Ihre Daten verwenden',
    's4_body'  => '
        <ul>
            <li>Betrieb, Wartung und Sicherung Ihres KitchenLog-Kontos.</li>
            <li>Verarbeitung der von Ihnen erfassten Einträge und Erstellung der von Ihnen angeforderten EU-Konformitätsberichte.</li>
            <li>Abrechnung Ihres Abonnements und Rechnungsstellung über Stripe.</li>
            <li>Versand rein transaktionsbezogener E-Mails (Passwort-Zurücksetzung, Rechnungsbestätigung, Sicherheitswarnungen).</li>
            <li>Erkennung, Untersuchung und Verhinderung von Missbrauch, Betrug und Sicherheitsvorfällen.</li>
            <li>Erfüllung gesetzlicher Pflichten (Steuern, Buchhaltung, rechtmäßige Behördenanfragen).</li>
        </ul>
        <p>Wir verkaufen Ihre Daten <strong>nicht</strong>, nutzen sie <strong>nicht</strong> für Werbung und treffen <strong>keine</strong> automatisierten Entscheidungen mit Rechtswirkung Ihnen gegenüber.</p>
    ',

    's5_title' => '5. Mit wem wir Daten teilen (Auftragsverarbeiter)',
    's5_body'  => '
        <p>Wir setzen eine begrenzte Anzahl sorgfältig ausgewählter Auftragsverarbeiter ein, jeweils gebunden durch einen Auftragsverarbeitungsvertrag und an angemessene DSGVO-Garantien:</p>
        <ul>
            <li><strong>Stripe Payments Europe Ltd.</strong> (Irland, EU) — Abonnementabrechnung und Zahlungsabwicklung. <a href="https://stripe.com/privacy" target="_blank" rel="noopener">Datenschutzerklärung</a>.</li>
            <li><strong>Anthropic, PBC / OpenRouter Inc.</strong> (USA) — KI-Analyse hochgeladener Abfallfotos. Fotos werden lediglich kurzzeitig verarbeitet und nicht zum Training beim KI-Anbieter aufbewahrt.</li>
            <li><strong>Cloudflare, Inc.</strong> (USA) — Bot-Schutz (Turnstile) auf den öffentlichen Anmelde- und Demoseiten.</li>
            <li><strong>Brevo SAS</strong> (Frankreich, EU) — Zustellung transaktionsbezogener E-Mails.</li>
            <li><strong>Hosting-Anbieter</strong> in der EU/EWR — Anwendungs- und Datenbankhosting.</li>
        </ul>
        <p>Für Übermittlungen außerhalb der EU/EWR stützen wir uns auf die <strong>Standardvertragsklauseln (SCC)</strong> der Europäischen Kommission und ergänzende geeignete Garantien (Art. 46 DSGVO).</p>
    ',

    's6_title' => '6. Wie lange wir Ihre Daten speichern',
    's6_body'  => '
        <ul>
            <li><strong>Kontodaten:</strong> während der Laufzeit Ihres Kontos zuzüglich bis zu 2 Jahre nach Schließung für Sicherheit und Streitbeilegung.</li>
            <li><strong>Abfalleinträge:</strong> solange das verknüpfte Konto aktiv ist. Nach Kontolöschung innerhalb von 30 Tagen entfernt, sofern keine Aufbewahrungspflicht besteht.</li>
            <li><strong>Rechnungen und Abrechnungsdaten:</strong> 7 Jahre, wie nach niederländischem und EU-Steuerrecht vorgeschrieben.</li>
            <li><strong>Server-, Sicherheits- und Zugriffslogs:</strong> bis zu 90 Tage.</li>
            <li><strong>Support-Korrespondenz:</strong> bis zu 3 Jahre nach Abschluss des Gesprächs.</li>
        </ul>
    ',

    's7_title' => '7. Ihre Rechte nach der DSGVO',
    's7_body'  => '
        <p>Sie haben das Recht auf:</p>
        <ul>
            <li><strong>Auskunft</strong> — Bestätigung, ob wir Sie betreffende Daten verarbeiten, und eine Kopie davon (Art. 15).</li>
            <li><strong>Berichtigung</strong> — unrichtige oder unvollständige Daten korrigieren zu lassen (Art. 16).</li>
            <li><strong>Löschung</strong> — Löschung Ihrer Daten ("Recht auf Vergessenwerden") (Art. 17).</li>
            <li><strong>Einschränkung</strong> — die Verarbeitung in bestimmten Fällen einzuschränken (Art. 18).</li>
            <li><strong>Datenübertragbarkeit</strong> — Ihre Daten in einem strukturierten, maschinenlesbaren Format zu erhalten (Art. 20).</li>
            <li><strong>Widerspruch</strong> — gegen eine auf berechtigtem Interesse gestützte Verarbeitung Widerspruch einzulegen (Art. 21).</li>
            <li><strong>Widerruf der Einwilligung</strong> jederzeit, soweit die Verarbeitung auf Einwilligung beruht (Art. 7 Abs. 3).</li>
        </ul>
        <p>Senden Sie Ihre Anfrage an <a href="mailto:info@kitchenlog.eu">info@kitchenlog.eu</a>. Wir antworten innerhalb von 30 Tagen kostenlos.</p>
        <p>Sie haben außerdem das Recht, sich bei einer Aufsichtsbehörde zu beschweren — in den Niederlanden bei der <a href="https://www.autoriteitpersoonsgegevens.nl" target="_blank" rel="noopener">Autoriteit Persoonsgegevens</a>; oder bei der Behörde an Ihrem gewöhnlichen Aufenthaltsort.</p>
    ',

    's8_title' => '8. Cookies',
    's8_body'  => '
        <p>Wir verwenden ausschließlich Cookies, die für die Bereitstellung des Dienstes unbedingt erforderlich sind (Sitzung, Sicherheits-Token, Spracheinstellung und „Angemeldet bleiben“), sowie die Stripe- und Cloudflare-Cookies, die nur auf Zahlungs- bzw. botgeschützten Seiten geladen werden. Es werden keine Analyse- oder Werbe-Cookies verwendet. Die vollständige Liste und Hinweise zur Verwaltung finden Sie in unserer <a href="/cookies">Cookie-Richtlinie</a>.</p>
    ',

    's9_title' => '9. Sicherheit',
    's9_body'  => '
        <p>Wir schützen Ihre Daten mit branchenüblichen Maßnahmen: TLS/HTTPS-Verschlüsselung im Transit, mit bcrypt gehashte Passwörter, verschlüsselte Sitzungs-Cookies, rollenbasierte Zugriffskontrollen, geprüfte Auftragsverarbeiter und Audit-Logs. Bei einer Verletzung des Schutzes personenbezogener Daten mit wahrscheinlichem Risiko für Ihre Rechte und Freiheiten benachrichtigen wir Sie unverzüglich (Art. 33–34 DSGVO).</p>
    ',

    's10_title' => '10. Änderungen dieser Erklärung',
    's10_body'  => '
        <p>Wir können diese Erklärung gelegentlich aktualisieren. Wesentliche Änderungen kündigen wir mindestens 14 Tage vor Inkrafttreten per E-Mail an. Das Datum „Zuletzt aktualisiert“ oben zeigt stets die aktuelle Fassung.</p>
    ',

    'nav_terms'   => 'Allgemeine Geschäftsbedingungen',
    'nav_cookies' => 'Cookie-Richtlinie',
    'nav_home'    => '← Startseite',
];
