<?php
/**
 * ============================================================
 * CONFIGURACIÓN DEL NEGOCIO — Editar para cada cliente
 * ============================================================
 * 
 * Este archivo contiene TODAS las variables de configuración del negocio.
 * Es la única fuente de verdad para personalizar la landing page.
 * 
 * Para personalizar para un nuevo cliente, solo edita este archivo.
 */

// Prevenir acceso directo
if (!defined('APP_ROOT')) {
    die('Acceso directo no permitido');
}

$envGet = static function (string $key, string $default = ''): string {
    if (class_exists('Env')) {
        return (string)Env::get($key, $default);
    }

    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }

    return (string)$value;
};

// ============================================================
// INFORMACIÓN DEL NEGOCIO
// ============================================================

define('BUSINESS_NAME',        'Consultoría Nexus');
define('BUSINESS_TAGLINE',     'Soluciones estratégicas para tu empresa');
define('BUSINESS_DESCRIPTION', 'Ayudamos a pequeñas y medianas empresas a optimizar sus procesos, reducir costos y crecer de forma sostenible.');
define('BUSINESS_YEARS',       '8');           // Años de experiencia
define('BUSINESS_CLIENTS',     '120+');        // Clientes atendidos
define('BUSINESS_PROJECTS',    '340+');        // Proyectos completados

// ============================================================
// SEO Y META TAGS
// ============================================================

define('SEO_TITLE',            'Consultoría Nexus — Soluciones estratégicas para tu empresa');
define('SEO_DESCRIPTION',      'Consultoría empresarial especializada en optimización de procesos, reducción de costos y crecimiento sostenible para PyMEs en México.');
define('SEO_KEYWORDS',         'consultoría empresarial, optimización de procesos, estrategia empresarial, Campeche, México');
define('SEO_OG_IMAGE',         '/public/img/og-image.jpg'); // Imagen 1200x630px para redes sociales
define('SITE_URL',             'https://landing.mauricioramos.tech'); // Sin trailing slash

// ============================================================
// INFORMACIÓN DE CONTACTO
// ============================================================

define('BUSINESS_PHONE',       '+52 938 123 4567');
define('BUSINESS_WHATSAPP',    '529381234567');  // Sin + ni espacios
define('BUSINESS_EMAIL',       'contacto@negocio.com');
define('BUSINESS_ADDRESS',     'Campeche, México');
define('BUSINESS_MAPS_URL',    '');            // URL de Google Maps (opcional)

// Email donde llegan las notificaciones de leads y citas
define('NOTIFY_EMAIL',         'mpuc19017@gmail.com');

// ============================================================
// REDES SOCIALES (dejar vacío '' si no aplica)
// ============================================================

define('SOCIAL_INSTAGRAM',     '');
define('SOCIAL_FACEBOOK',      '');
define('SOCIAL_LINKEDIN',      '');

// ============================================================
// COLORES DEL NEGOCIO (se inyectan como CSS variables)
// ============================================================

define('COLOR_PRIMARY',        '#1a3a5c');   // Azul marino — profesional
define('COLOR_ACCENT',         '#e8a020');   // Dorado — confianza
define('COLOR_BG',             '#f8f7f4');   // Crema — calidez

// ============================================================
// WHATSAPP
// ============================================================

define('WHATSAPP_MESSAGE',     'Hola, vi tu página web y me gustaría obtener más información sobre tus servicios.');

// ============================================================
// HORARIO DE ATENCIÓN
// ============================================================

define('BUSINESS_HOURS',       'Lunes a Viernes, 9:00 am – 6:00 pm');

// Días laborables para el agendador de citas (0=Dom, 1=Lun, ..., 6=Sáb)
define('AVAILABLE_DAYS',       [1, 2, 3, 4, 5]); // Lunes a Viernes
define('APPOINTMENT_START',    '09:00');
define('APPOINTMENT_END',      '17:00');
define('APPOINTMENT_SLOT',     30); // Duración de cada slot en minutos

// Días no disponibles (feriados, vacaciones) — formato YYYY-MM-DD
define('BLOCKED_DATES',        ['2025-12-25', '2026-01-01', '2026-02-03']);

// ============================================================
// CRÉDITO DEL DESARROLLADOR (aparece en el footer)
// ============================================================

define('DEVELOPER_NAME',       'Rudy Ramos');
define('DEVELOPER_URL',        'https://mauricioramos.tech');

// ============================================================
// PANEL DE ADMINISTRACIÓN
// ============================================================

define('ADMIN_PATH',           'gestion-admin'); // URL del panel: /gestion-admin (NO usar /admin)
define('ADMIN_SESSION_TIMEOUT', 3600); // Segundos de inactividad antes de cerrar sesión

// ============================================================
// SERVICIOS OFRECIDOS
// ============================================================

define('SERVICES', [
    [
        'icon'        => 'search',
        'title'       => 'Diagnóstico Empresarial',
        'short_desc'  => 'Análisis profundo de tu negocio para identificar áreas de mejora y oportunidades de crecimiento.',
        'full_desc'   => 'Realizamos un análisis exhaustivo de todas las áreas de tu empresa: operaciones, finanzas, recursos humanos y marketing. Identificamos cuellos de botella, ineficiencias y oportunidades que quizás no has detectado. Te entregamos un informe detallado con recomendaciones priorizadas y un plan de acción claro.',
    ],
    [
        'icon'        => 'settings',
        'title'       => 'Optimización de Procesos',
        'short_desc'  => 'Rediseñamos tus procesos para aumentar eficiencia y reducir costos operativos.',
        'full_desc'   => 'Mapeamos tus procesos actuales, identificamos redundancias y diseñamos flujos de trabajo optimizados. Implementamos mejoras que pueden reducir tus costos operativos hasta un 30% y acelerar los tiempos de entrega. Incluye capacitación a tu equipo y seguimiento post-implementación.',
    ],
    [
        'icon'        => 'trending-up',
        'title'       => 'Estrategia de Crecimiento',
        'short_desc'  => 'Desarrollamos planes estratégicos para escalar tu negocio de forma sostenible.',
        'full_desc'   => 'Creamos un roadmap de crecimiento personalizado basado en el análisis de tu mercado, competencia y capacidades internas. Definimos objetivos SMART, identificamos nuevas oportunidades de mercado y diseñamos estrategias de expansión que minimizan riesgos y maximizan el retorno de inversión.',
    ],
    [
        'icon'        => 'calculator',
        'title'       => 'Consultoría Financiera',
        'short_desc'  => 'Mejoramos tu salud financiera con análisis, proyecciones y estrategias de ahorro.',
        'full_desc'   => 'Analizamos tu estructura de costos, flujo de efectivo y rentabilidad por línea de negocio. Creamos proyecciones financieras realistas, identificamos fuentes de financiamiento adecuadas y desarrollamos estrategias para mejorar tus márgenes. Te ayudamos a tomar decisiones financieras informadas.',
    ],
]);

// ============================================================
// DIFERENCIADORES (Por qué nosotros)
// ============================================================

define('DIFFERENTIATORS', [
    [
        'icon'  => 'award',
        'title' => 'Experiencia Comprobada',
        'text'  => 'Más de 8 años ayudando a empresas como la tuya a alcanzar sus objetivos.',
    ],
    [
        'icon'  => 'users',
        'title' => 'Enfoque Personalizado',
        'text'  => 'Cada negocio es único. Nuestras soluciones se adaptan a tus necesidades específicas.',
    ],
    [
        'icon'  => 'target',
        'title' => 'Resultados Medibles',
        'text'  => 'Establecemos métricas claras y te mostramos el impacto real de nuestro trabajo.',
    ],
    [
        'icon'  => 'handshake',
        'title' => 'Acompañamiento Continuo',
        'text'  => 'No te dejamos solo. Te acompañamos en cada paso de la implementación.',
    ],
]);

// ============================================================
// TESTIMONIOS
// ============================================================

define('TESTIMONIALS', [
    [
        'name'    => 'María González',
        'company' => 'Distribuidora del Golfo',
        'text'    => 'Gracias a la consultoría, logramos reducir nuestros costos operativos en un 25% en solo 6 meses. El equipo fue profesional y siempre estuvo disponible para resolver nuestras dudas.',
        'initials'=> 'MG',
        'color'   => '#4A90A4',
    ],
    [
        'name'    => 'Roberto Hernández',
        'company' => 'Constructora Peninsular',
        'text'    => 'El diagnóstico empresarial nos abrió los ojos a problemas que no sabíamos que teníamos. Las recomendaciones fueron prácticas y fáciles de implementar. Muy recomendados.',
        'initials'=> 'RH',
        'color'   => '#6B8E23',
    ],
    [
        'name'    => 'Ana Lucía Pérez',
        'company' => 'Boutique Campeche',
        'text'    => 'Pensé que la consultoría era solo para empresas grandes, pero me equivoqué. Me ayudaron a organizar mi negocio y ahora tengo claridad sobre hacia dónde voy.',
        'initials'=> 'AL',
        'color'   => '#9B59B6',
    ],
]);

// ============================================================
// PROCESO DE TRABAJO
// ============================================================

define('PROCESS_STEPS', [
    [
        'number' => '01',
        'title'  => 'Consulta Inicial',
        'text'   => 'Conocemos tu negocio, entendemos tus desafíos y definimos objetivos claros.',
    ],
    [
        'number' => '02',
        'title'  => 'Diagnóstico',
        'text'   => 'Analizamos a fondo tu situación actual e identificamos oportunidades.',
    ],
    [
        'number' => '03',
        'title'  => 'Propuesta',
        'text'   => 'Te presentamos un plan de acción detallado con tiempos y costos claros.',
    ],
    [
        'number' => '04',
        'title'  => 'Ejecución',
        'text'   => 'Implementamos las soluciones y te acompañamos en todo el proceso.',
    ],
]);

// ============================================================
// PREGUNTAS FRECUENTES (FAQ)
// ============================================================

define('FAQ_ITEMS', [
    [
        'question' => '¿Cuánto cuesta una consultoría?',
        'answer'   => 'El costo depende del alcance del proyecto y las necesidades específicas de tu negocio. Ofrecemos una consulta inicial gratuita donde evaluamos tu situación y te presentamos una propuesta personalizada sin compromiso.',
    ],
    [
        'question' => '¿Cuánto tiempo toma ver resultados?',
        'answer'   => 'Depende del tipo de proyecto. Algunas mejoras operativas pueden implementarse en semanas, mientras que transformaciones más profundas pueden tomar de 3 a 6 meses. En la propuesta inicial definimos un cronograma realista con hitos medibles.',
    ],
    [
        'question' => '¿Trabajan con empresas pequeñas?',
        'answer'   => 'Sí, trabajamos con empresas de todos los tamaños. De hecho, muchas PyMEs son las que más se benefician de una consultoría porque les ayuda a profesionalizar sus operaciones y competir mejor en el mercado.',
    ],
    [
        'question' => '¿Qué incluye la consulta inicial gratuita?',
        'answer'   => 'En la consulta inicial (aproximadamente 45 minutos) conocemos tu negocio, entendemos tus principales desafíos y objetivos, y te damos una primera impresión de cómo podríamos ayudarte. No hay compromiso ni costo.',
    ],
    [
        'question' => '¿Cómo es el proceso de trabajo?',
        'answer'   => 'Después de la consulta inicial, realizamos un diagnóstico profundo de tu negocio. Con esa información, te presentamos una propuesta detallada. Si decides continuar, implementamos las soluciones con reuniones periódicas de seguimiento.',
    ],
]);

// ============================================================
// CONFIGURACIÓN DE SEGURIDAD
// ============================================================

define('CSRF_TOKEN_EXPIRY', 7200);  // 2 horas en segundos
define('RATE_LIMIT_WINDOW', 600);   // 10 minutos en segundos
define('RATE_LIMIT_MAX_LEADS', 3);  // Máximo de leads por ventana
define('RATE_LIMIT_MAX_APPOINTMENTS', 3); // Máximo de citas por ventana
define('RATE_LIMIT_MAX_LOGIN', 5);  // Máximo de intentos de login
define('RATE_LIMIT_LOGIN_WINDOW', 900); // 15 minutos
define('RATE_LIMIT_LOCKOUT', 1800); // 30 minutos de bloqueo

// ============================================================
// CONFIGURACIÓN DE BASE DE DATOS (via .env con fallback seguro)
// ============================================================

define('DB_CONNECTION', $envGet('DB_CONNECTION', 'sqlite')); // sqlite | mysql
define('DB_HOST', $envGet('DB_HOST', '127.0.0.1'));
define('DB_PORT', $envGet('DB_PORT', '3306'));
define('DB_DATABASE', $envGet('DB_DATABASE', 'landing_page'));
define('DB_USERNAME', $envGet('DB_USERNAME', 'root'));
define('DB_PASSWORD', $envGet('DB_PASSWORD', ''));
define('DB_CHARSET', $envGet('DB_CHARSET', 'utf8mb4'));
$dbPathFromEnv = $envGet('DB_PATH', __DIR__ . '/database/leads.db');
if ($dbPathFromEnv !== '' && !preg_match('#^(?:[A-Za-z]:[\\/]|/)#', $dbPathFromEnv)) {
    $dbPathFromEnv = __DIR__ . '/' . ltrim($dbPathFromEnv, '/\\');
}
define('DB_PATH', $dbPathFromEnv);
