<?php

return [
    'page_title'       => 'Politique de confidentialité — KitchenLog',
    'meta_description' => 'Comment KitchenLog collecte, utilise et protège vos données personnelles conformément au RGPD.',
    'hero_eyebrow'     => 'Mentions légales',
    'hero_title'       => 'Politique de confidentialité',
    'last_updated'     => 'Dernière mise à jour : :date',

    's1_title' => '1. Qui nous sommes',
    's1_body'  => '
        <p>KitchenLog est une marque commerciale d’<strong>ARSUS IT Solutions</strong>, une entreprise individuelle immatriculée auprès de la Chambre de commerce néerlandaise (KvK). Nous exploitons une plateforme de suivi des déchets alimentaires destinée aux cuisines professionnelles de l’UE.</p>
        <p><strong>Responsable du traitement :</strong> ARSUS IT Solutions<br>
        <strong>Numéro KvK :</strong> 76343251<br>
        <strong>Établi aux :</strong> Pays-Bas (UE/EEE)<br>
        <strong>Contact :</strong> <a href="mailto:info@kitchenlog.eu">info@kitchenlog.eu</a></p>
        <p>La présente politique décrit les données à caractère personnel que nous traitons lorsque vous utilisez KitchenLog, les raisons de ce traitement et les droits dont vous disposez au titre du Règlement général sur la protection des données (RGPD, Règlement (UE) 2016/679).</p>
    ',

    's2_title' => '2. Données personnelles que nous collectons',
    's2_body'  => '
        <ul>
            <li><strong>Données de compte :</strong> nom, adresse e-mail, mot de passe haché, nom de l’entreprise, pays, langue préférée.</li>
            <li><strong>Données d’utilisation :</strong> les enregistrements de déchets alimentaires que vous saisissez (nom, catégorie, poids, motif, date, notes et photos facultatives).</li>
            <li><strong>Données de facturation :</strong> adresse de facturation, numéro de TVA, historique des factures. Les coordonnées bancaires sont traitées exclusivement par Stripe et ne sont jamais stockées sur nos serveurs.</li>
            <li><strong>Données techniques :</strong> adresse IP, user-agent du navigateur, type d’appareil, identifiants de session, journaux de sécurité.</li>
            <li><strong>Données de communication :</strong> messages envoyés à notre support.</li>
        </ul>
    ',

    's3_title' => '3. Pourquoi nous traitons vos données (bases légales)',
    's3_body'  => '
        <ul>
            <li><strong>Exécution du contrat — art. 6, §1, b) RGPD :</strong> création et exploitation de votre compte, conservation de vos enregistrements, génération des rapports.</li>
            <li><strong>Obligation légale — art. 6, §1, c) RGPD :</strong> facturation et obligations comptables/fiscales.</li>
            <li><strong>Intérêts légitimes — art. 6, §1, f) RGPD :</strong> surveillance de la sécurité, prévention de la fraude et des abus, amélioration du service. Nous évaluons systématiquement nos intérêts au regard de vos droits et libertés avant de nous appuyer sur cette base.</li>
            <li><strong>Consentement — art. 6, §1, a) RGPD :</strong> tout traitement non essentiel (cookies optionnels, par exemple) n’a lieu qu’après votre consentement, révocable à tout moment.</li>
        </ul>
    ',

    's4_title' => '4. Comment nous utilisons vos données',
    's4_body'  => '
        <ul>
            <li>Exploiter, maintenir et sécuriser votre compte KitchenLog.</li>
            <li>Traiter les enregistrements saisis et générer les rapports de conformité UE que vous demandez.</li>
            <li>Facturer votre abonnement et émettre des factures via Stripe.</li>
            <li>Envoyer des e-mails strictement transactionnels (réinitialisation de mot de passe, reçus, alertes de sécurité).</li>
            <li>Détecter, étudier et prévenir les abus, fraudes et incidents de sécurité.</li>
            <li>Respecter nos obligations légales (fiscalité, comptabilité, demandes légitimes des autorités).</li>
        </ul>
        <p>Nous <strong>ne vendons pas</strong> vos données, <strong>ne les utilisons pas</strong> à des fins publicitaires et <strong>ne prenons pas</strong> de décisions automatisées produisant des effets juridiques à votre égard.</p>
    ',

    's5_title' => '5. Avec qui nous partageons vos données (sous-traitants)',
    's5_body'  => '
        <p>Nous nous appuyons sur un nombre restreint de sous-traitants soigneusement sélectionnés, chacun lié par un accord de traitement et engagé sur des garanties conformes au RGPD :</p>
        <ul>
            <li><strong>Stripe Payments Europe Ltd.</strong> (Irlande, UE) — facturation des abonnements et traitement des paiements. <a href="https://stripe.com/privacy" target="_blank" rel="noopener">Politique de confidentialité</a>.</li>
            <li><strong>Anthropic, PBC / OpenRouter Inc.</strong> (États-Unis) — analyse IA des photos de déchets téléchargées. Les photos sont traitées de manière transitoire et ne sont pas conservées par le fournisseur d’IA à des fins d’entraînement.</li>
            <li><strong>Cloudflare, Inc.</strong> (États-Unis) — protection anti-bot (Turnstile) sur les pages publiques d’inscription et de démo.</li>
            <li><strong>Brevo SAS</strong> (France, UE) — distribution des e-mails transactionnels.</li>
            <li><strong>Hébergeur</strong> situé dans l’UE/EEE — hébergement de l’application et de la base de données.</li>
        </ul>
        <p>Pour les transferts hors UE/EEE, nous nous appuyons sur les <strong>clauses contractuelles types (CCT)</strong> de la Commission européenne et sur des garanties complémentaires lorsque cela est requis (art. 46 RGPD).</p>
    ',

    's6_title' => '6. Durées de conservation',
    's6_body'  => '
        <ul>
            <li><strong>Données de compte :</strong> pendant la durée de vie du compte, plus 2 ans maximum après la clôture pour les besoins de sécurité et de gestion des litiges.</li>
            <li><strong>Enregistrements de déchets :</strong> tant que le compte associé est actif. Supprimés dans les 30 jours suivant la suppression du compte, sauf obligation légale de conservation.</li>
            <li><strong>Factures et données de facturation :</strong> 7 ans, conformément aux obligations fiscales néerlandaises et européennes.</li>
            <li><strong>Journaux serveurs, sécurité et accès :</strong> 90 jours maximum.</li>
            <li><strong>Correspondance avec le support :</strong> jusqu’à 3 ans après la clôture de la conversation.</li>
        </ul>
    ',

    's7_title' => '7. Vos droits au titre du RGPD',
    's7_body'  => '
        <p>Vous disposez des droits suivants :</p>
        <ul>
            <li><strong>Accès</strong> — obtenir la confirmation que nous traitons vos données et en recevoir une copie (art. 15).</li>
            <li><strong>Rectification</strong> — faire corriger des données inexactes ou incomplètes (art. 16).</li>
            <li><strong>Effacement</strong> — demander la suppression de vos données (« droit à l’oubli ») (art. 17).</li>
            <li><strong>Limitation</strong> — restreindre le traitement dans certains cas (art. 18).</li>
            <li><strong>Portabilité</strong> — recevoir vos données dans un format structuré et lisible par machine (art. 20).</li>
            <li><strong>Opposition</strong> — vous opposer au traitement fondé sur l’intérêt légitime (art. 21).</li>
            <li><strong>Retrait du consentement</strong> à tout moment, lorsque le traitement repose sur celui-ci (art. 7, §3).</li>
        </ul>
        <p>Pour exercer ces droits, écrivez-nous à <a href="mailto:info@kitchenlog.eu">info@kitchenlog.eu</a>. Nous répondons gratuitement sous 30 jours.</p>
        <p>Vous pouvez également introduire une réclamation auprès d’une autorité de contrôle — aux Pays-Bas, l’<a href="https://www.autoriteitpersoonsgegevens.nl" target="_blank" rel="noopener">Autoriteit Persoonsgegevens</a> ; ou auprès de l’autorité de votre pays de résidence habituelle (en France, la CNIL).</p>
    ',

    's8_title' => '8. Cookies',
    's8_body'  => '
        <p>Nous n’utilisons que les cookies strictement nécessaires à la prestation du service (session, jeton de sécurité, langue préférée et « rester connecté »), auxquels s’ajoutent les cookies Stripe et Cloudflare chargés respectivement sur les pages de paiement et protégées par anti-bot. Aucun cookie d’analyse ou publicitaire n’est utilisé. Consultez notre <a href="/cookies">Politique relative aux cookies</a> pour la liste complète et les moyens de contrôle.</p>
    ',

    's9_title' => '9. Sécurité',
    's9_body'  => '
        <p>Nous protégeons vos données par des mesures conformes à l’état de l’art : chiffrement TLS/HTTPS en transit, mots de passe hachés avec bcrypt, cookies de session chiffrés, contrôles d’accès basés sur les rôles, sous-traitants vérifiés et journaux d’audit. En cas de violation de données à caractère personnel susceptible d’engendrer un risque pour vos droits et libertés, nous vous en informons sans délai injustifié (art. 33–34 RGPD).</p>
    ',

    's10_title' => '10. Modifications de la présente politique',
    's10_body'  => '
        <p>Nous pouvons mettre à jour cette politique de temps à autre. Toute modification substantielle est notifiée par e-mail au moins 14 jours avant son entrée en vigueur. La date « Dernière mise à jour » indique toujours la version en cours.</p>
    ',

    'nav_terms'   => 'Conditions générales',
    'nav_cookies' => 'Politique relative aux cookies',
    'nav_home'    => '← Accueil',
];
