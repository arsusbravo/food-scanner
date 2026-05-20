<?php

return [
    'page_title'       => 'Política de Privacidad — KitchenLog',
    'meta_description' => 'Cómo KitchenLog recopila, utiliza y protege sus datos personales conforme al RGPD.',
    'hero_eyebrow'     => 'Legal',
    'hero_title'       => 'Política de Privacidad',
    'last_updated'     => 'Última actualización: :date',

    's1_title' => '1. Quiénes somos',
    's1_body'  => '
        <p>KitchenLog es un nombre comercial de <strong>ARSUS IT Solutions</strong>, empresario individual inscrito en la Cámara de Comercio de los Países Bajos (KvK). Operamos una plataforma de seguimiento del desperdicio alimentario para cocinas profesionales de la UE.</p>
        <p><strong>Responsable del tratamiento:</strong> ARSUS IT Solutions<br>
        <strong>Número KvK:</strong> 76343251<br>
        <strong>Establecido en:</strong> Países Bajos (UE/EEE)<br>
        <strong>Contacto:</strong> <a href="mailto:info@kitchenlog.eu">info@kitchenlog.eu</a></p>
        <p>Esta política describe qué datos personales tratamos cuando utiliza KitchenLog, por qué los tratamos y los derechos que le asisten en virtud del Reglamento General de Protección de Datos (RGPD, Reglamento (UE) 2016/679).</p>
    ',

    's2_title' => '2. Datos personales que recopilamos',
    's2_body'  => '
        <ul>
            <li><strong>Datos de cuenta:</strong> nombre, correo electrónico, contraseña con hash, nombre de la empresa, país, idioma preferido.</li>
            <li><strong>Datos de uso:</strong> los registros de desperdicio que introduce (nombre, categoría, peso, motivo, fecha, notas y fotos opcionales).</li>
            <li><strong>Datos de facturación:</strong> dirección de facturación, NIF/IVA, historial de facturas. Los datos de tarjeta los tramita exclusivamente Stripe y no se almacenan en nuestros servidores.</li>
            <li><strong>Datos técnicos:</strong> dirección IP, user-agent del navegador, tipo de dispositivo, identificadores de sesión, registros de seguridad.</li>
            <li><strong>Datos de comunicación:</strong> mensajes que envía a nuestro soporte.</li>
        </ul>
    ',

    's3_title' => '3. Por qué tratamos sus datos (bases jurídicas)',
    's3_body'  => '
        <ul>
            <li><strong>Ejecución del contrato — art. 6.1.b) RGPD:</strong> creación y operación de su cuenta, almacenamiento de los registros, generación de informes.</li>
            <li><strong>Obligación legal — art. 6.1.c) RGPD:</strong> facturación y obligaciones fiscales/contables.</li>
            <li><strong>Intereses legítimos — art. 6.1.f) RGPD:</strong> supervisión de seguridad, prevención del fraude y abuso, mejora del servicio. Ponderamos nuestros intereses con sus derechos y libertades antes de basarnos en esta base.</li>
            <li><strong>Consentimiento — art. 6.1.a) RGPD:</strong> cualquier tratamiento no esencial (p. ej., cookies opcionales) solo se realiza tras su consentimiento, revocable en cualquier momento.</li>
        </ul>
    ',

    's4_title' => '4. Cómo usamos sus datos',
    's4_body'  => '
        <ul>
            <li>Operar, mantener y proteger su cuenta de KitchenLog.</li>
            <li>Procesar los registros que introduce y generar los informes de cumplimiento UE que solicite.</li>
            <li>Facturar su suscripción y emitir facturas mediante Stripe.</li>
            <li>Enviar correos estrictamente transaccionales (restablecimiento de contraseña, recibos, alertas de seguridad).</li>
            <li>Detectar, investigar y prevenir abusos, fraudes e incidentes de seguridad.</li>
            <li>Cumplir nuestras obligaciones legales (fiscales, contables, requerimientos legítimos de autoridades).</li>
        </ul>
        <p><strong>No</strong> vendemos sus datos, <strong>no</strong> los usamos para publicidad y <strong>no</strong> tomamos decisiones automatizadas con efectos jurídicos sobre usted.</p>
    ',

    's5_title' => '5. Con quién compartimos datos (encargados)',
    's5_body'  => '
        <p>Empleamos un número reducido de encargados cuidadosamente seleccionados, cada uno vinculado por un contrato de encargo y por garantías acordes al RGPD:</p>
        <ul>
            <li><strong>Stripe Payments Europe Ltd.</strong> (Irlanda, UE) — facturación de suscripciones y procesamiento de pagos. <a href="https://stripe.com/privacy" target="_blank" rel="noopener">Política de privacidad</a>.</li>
            <li><strong>Anthropic, PBC / OpenRouter Inc.</strong> (EE. UU.) — análisis IA de las fotos de desperdicio que sube. Las fotos se tratan de forma transitoria y no son retenidas por el proveedor de IA para entrenamiento.</li>
            <li><strong>Cloudflare, Inc.</strong> (EE. UU.) — protección antibots (Turnstile) en las páginas públicas de registro y demo.</li>
            <li><strong>Brevo SAS</strong> (Francia, UE) — envío de correos transaccionales.</li>
            <li><strong>Proveedor de alojamiento</strong> ubicado en la UE/EEE — alojamiento de aplicación y base de datos.</li>
        </ul>
        <p>Para transferencias fuera de la UE/EEE nos basamos en las <strong>Cláusulas Contractuales Tipo (CCT)</strong> de la Comisión Europea y, cuando proceda, en garantías complementarias (art. 46 RGPD).</p>
    ',

    's6_title' => '6. Plazos de conservación',
    's6_body'  => '
        <ul>
            <li><strong>Datos de cuenta:</strong> mientras dure la cuenta, más hasta 2 años tras el cierre por motivos de seguridad y gestión de reclamaciones.</li>
            <li><strong>Registros de desperdicio:</strong> mientras la cuenta vinculada esté activa. Tras la eliminación de la cuenta, se borran en un plazo de 30 días, salvo obligación legal de conservación.</li>
            <li><strong>Facturas y datos de facturación:</strong> 7 años, conforme a la normativa fiscal neerlandesa y de la UE.</li>
            <li><strong>Registros de servidor, seguridad y acceso:</strong> hasta 90 días.</li>
            <li><strong>Correspondencia con soporte:</strong> hasta 3 años tras el cierre de la conversación.</li>
        </ul>
    ',

    's7_title' => '7. Sus derechos conforme al RGPD',
    's7_body'  => '
        <p>Tiene derecho a:</p>
        <ul>
            <li><strong>Acceso</strong> — obtener confirmación de si tratamos sus datos y recibir una copia (art. 15).</li>
            <li><strong>Rectificación</strong> — que corrijamos datos inexactos o incompletos (art. 16).</li>
            <li><strong>Supresión</strong> — solicitar la eliminación de sus datos («derecho al olvido») (art. 17).</li>
            <li><strong>Limitación</strong> — limitar el tratamiento en determinados casos (art. 18).</li>
            <li><strong>Portabilidad</strong> — recibir sus datos en un formato estructurado y legible por máquina (art. 20).</li>
            <li><strong>Oposición</strong> — oponerse al tratamiento basado en interés legítimo (art. 21).</li>
            <li><strong>Retirar el consentimiento</strong> en cualquier momento, cuando el tratamiento se base en él (art. 7.3).</li>
        </ul>
        <p>Para ejercer estos derechos, escriba a <a href="mailto:info@kitchenlog.eu">info@kitchenlog.eu</a>. Responderemos gratuitamente en un plazo de 30 días.</p>
        <p>También puede presentar una reclamación ante una autoridad de control — en los Países Bajos, la <a href="https://www.autoriteitpersoonsgegevens.nl" target="_blank" rel="noopener">Autoriteit Persoonsgegevens</a>; o ante la autoridad de su país de residencia habitual (en España, la AEPD).</p>
    ',

    's8_title' => '8. Cookies',
    's8_body'  => '
        <p>Solo utilizamos cookies estrictamente necesarias para prestar el servicio (sesión, token de seguridad, idioma preferido y «recuérdame»), además de las cookies de Stripe y Cloudflare cargadas, respectivamente, en las páginas de pago y protegidas frente a bots. No se usan cookies de analítica ni publicidad. Consulte nuestra <a href="/cookies">Política de Cookies</a> para la lista completa y cómo gestionarlas.</p>
    ',

    's9_title' => '9. Seguridad',
    's9_body'  => '
        <p>Protegemos sus datos con medidas estándar del sector: cifrado TLS/HTTPS en tránsito, contraseñas con hash bcrypt, cookies de sesión cifradas, controles de acceso por roles, encargados verificados y registros de auditoría. Si se produjera una violación de seguridad con probable riesgo para sus derechos y libertades, le informaremos sin dilación indebida (arts. 33-34 RGPD).</p>
    ',

    's10_title' => '10. Cambios en esta política',
    's10_body'  => '
        <p>Podemos actualizar esta política periódicamente. Los cambios sustanciales se notificarán por correo electrónico al menos 14 días antes de su entrada en vigor. La fecha de «Última actualización» indica siempre la versión vigente.</p>
    ',

    'nav_terms'   => 'Condiciones Generales',
    'nav_cookies' => 'Política de Cookies',
    'nav_home'    => '← Inicio',
];
