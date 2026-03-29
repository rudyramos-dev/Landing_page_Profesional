<?php
/**
 * index.php - Front Controller y Router Principal
 * 
 * Landing Page Profesional Universal
 * 
 * Este archivo actúa como punto de entrada único para todas las peticiones.
 * Maneja el routing, carga de dependencias y renderizado de la landing page.
 */

// ============================================================
// CONFIGURACIÓN DE ERRORES (Producción)
// ============================================================

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// ============================================================
// CONSTANTES DE LA APLICACIÓN
// ============================================================

define('APP_ROOT', __DIR__);
define('APP_VERSION', '1.0.0');

// ============================================================
// CARGAR CONFIGURACIÓN Y DEPENDENCIAS
// ============================================================

require_once APP_ROOT . '/app/Env.php';
Env::load(APP_ROOT . '/.env');
require_once APP_ROOT . '/config.php';
require_once APP_ROOT . '/database/Database.php';
require_once APP_ROOT . '/app/BusinessConfig.php';
require_once APP_ROOT . '/app/Security.php';
require_once APP_ROOT . '/app/RateLimiter.php';
require_once APP_ROOT . '/app/Validator.php';
require_once APP_ROOT . '/app/Response.php';
require_once APP_ROOT . '/app/Mailer.php';
require_once APP_ROOT . '/app/LeadController.php';
require_once APP_ROOT . '/app/AppointmentController.php';
require_once APP_ROOT . '/app/AuthController.php';
require_once APP_ROOT . '/app/AdminController.php';
require_once APP_ROOT . '/app/Helpers.php';
require_once APP_ROOT . '/app/Router.php';

// ============================================================
// INICIALIZAR BASE DE DATOS Y LIMPIAR REGISTROS ANTIGUOS
// ============================================================

try {
    Database::getInstance();
    Database::cleanupRateLimits();
} catch (Exception $e) {
    error_log('Error inicializando base de datos: ' . $e->getMessage());
}

// ============================================================
// CONFIGURAR HEADERS DE SEGURIDAD
// ============================================================

Security::setSecurityHeaders();

// ============================================================
// OBTENER URI Y MÉTODO
// ============================================================

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Limpiar query string de la URI
$uriPath = parse_url($requestUri, PHP_URL_PATH);
$uriPath = rtrim($uriPath, '/') ?: '/';

// ============================================================
// ROUTER
// ============================================================

// Rutas API (POST)
if (strpos($uriPath, '/api/') === 0) {
    handleApiRoutes($uriPath, $requestMethod);
    exit;
}

// Rutas del panel de administración
$adminPath = '/' . ADMIN_PATH;
if (strpos($uriPath, $adminPath) === 0) {
    handleAdminRoutes($uriPath, $adminPath, $requestMethod);
    exit;
}

// Archivos estáticos especiales
if ($uriPath === '/robots.txt') {
    header('Content-Type: text/plain');
    echo generateRobotsTxt();
    exit;
}

if ($uriPath === '/sitemap.xml') {
    header('Content-Type: application/xml');
    echo generateSitemap();
    exit;
}

// Ruta principal: Landing Page
if ($uriPath === '/' || $uriPath === '/index.php') {
    renderLandingPage();
    exit;
}

// 404 para cualquier otra ruta
render404Page();
exit;

/**
 * Renderizar la landing page completa
 */
function renderLandingPage(): void
{
    // Obtener token CSRF para los formularios
    $csrfToken = Security::getCsrfToken();
    
    // Escapar valores para HTML
    $e = function($str) { return Security::escapeHtml($str); };

    // Cargar configuracion de negocio desde DB (fallback a config.php)
    $business = BusinessConfig::all();
    $businessName = $business['business_name'];
    $businessTagline = $business['business_tagline'];
    $businessDescription = $business['business_description'];
    $businessYears = $business['business_years'];
    $businessClients = $business['business_clients'];
    $businessProjects = $business['business_projects'];
    $seoTitle = $business['seo_title'];
    $seoDescription = $business['seo_description'];
    $seoKeywords = $business['seo_keywords'];
    $seoOgImage = $business['seo_og_image'];
    $siteUrl = $business['site_url'];
    $businessPhone = $business['business_phone'];
    $businessWhatsapp = $business['business_whatsapp'];
    $businessEmail = $business['business_email'];
    $businessAddress = $business['business_address'];
    $businessHours = $business['business_hours'];
    $socialInstagram = $business['social_instagram'];
    $socialFacebook = $business['social_facebook'];
    $socialLinkedin = $business['social_linkedin'];
    $colorPrimary = $business['color_primary'];
    $colorAccent = $business['color_accent'];
    $colorBg = $business['color_bg'];
    $whatsappMessage = $business['whatsapp_message'];
    $developerName = $business['developer_name'];
    $developerUrl = $business['developer_url'];
    
    // Generar Schema.org JSON-LD
    $schemaOrg = generateSchemaOrg($business);

    // Obtener servicios desde DB para landing (fallback a config)
    $services = [];
    $testimonials = [];
    $faqItems = [];

    try {
        $pdo = Database::getInstance();

        $stmt = $pdo->query("SELECT name, icon, short_desc, full_desc FROM services WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
        $serviceRows = $stmt->fetchAll();
        foreach ($serviceRows as $row) {
            $services[] = [
                'icon' => $row['icon'] ?: 'briefcase',
                'title' => $row['name'],
                'short_desc' => $row['short_desc'] ?: ($row['full_desc'] ?: ''),
                'full_desc' => $row['full_desc'] ?: ($row['short_desc'] ?: ''),
            ];
        }

        $stmt = $pdo->query("SELECT name, company, text, initials, color FROM testimonials WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
        $testimonialRows = $stmt->fetchAll();
        foreach ($testimonialRows as $row) {
            $testimonials[] = [
                'name' => $row['name'],
                'company' => $row['company'] ?? '',
                'text' => $row['text'],
                'initials' => $row['initials'] ?: strtoupper(substr((string)$row['name'], 0, 2)),
                'color' => $row['color'] ?: '#4A90A4',
            ];
        }

        $stmt = $pdo->query("SELECT question, answer FROM faq_items WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");
        $faqItems = $stmt->fetchAll();
    } catch (Throwable $e) {
        error_log('Error cargando contenido de landing: ' . $e->getMessage());
    }

    if (empty($services)) {
        $services = SERVICES;
    }
    if (empty($testimonials)) {
        $testimonials = TESTIMONIALS;
    }
    if (empty($faqItems)) {
        $faqItems = FAQ_ITEMS;
    }

    // Obtener servicios activos para el formulario de citas (config dinamico)
    $appointmentServices = [];
    foreach ($services as $service) {
        if (!empty($service['title'])) {
            $appointmentServices[] = ['name' => $service['title']];
        }
    }

    $differentiators = DIFFERENTIATORS;
    $processSteps = PROCESS_STEPS;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Básico -->
    <title><?= $e($seoTitle) ?></title>
    <meta name="description" content="<?= $e($seoDescription) ?>">
    <meta name="keywords" content="<?= $e($seoKeywords) ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= $e($siteUrl) ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= $e($seoTitle) ?>">
    <meta property="og:description" content="<?= $e($seoDescription) ?>">
    <meta property="og:image" content="<?= $e($siteUrl . $seoOgImage) ?>">
    <meta property="og:url" content="<?= $e($siteUrl) ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="es_MX">
    <meta property="og:site_name" content="<?= $e($businessName) ?>">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $e($seoTitle) ?>">
    <meta name="twitter:description" content="<?= $e($seoDescription) ?>">
    <meta name="twitter:image" content="<?= $e($siteUrl . $seoOgImage) ?>">
    
    <!-- Geolocalización -->
    <meta name="geo.region" content="MX">
    <meta name="geo.placename" content="<?= $e($businessAddress) ?>">
    
    <!-- Preconnect para recursos externos -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Fuentes -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Instrument+Serif&display=swap" rel="stylesheet">
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- CSS Variables y estilos críticos inline -->
    <style>
        :root {
            --color-primary: <?= $e($colorPrimary) ?>;
            --color-accent: <?= $e($colorAccent) ?>;
            --color-bg: <?= $e($colorBg) ?>;
        }
        
        body {
            font-family: 'DM Sans', sans-serif;
            background-color: var(--color-bg);
        }
        
        .font-serif {
            font-family: 'Instrument Serif', serif;
        }
        
        .bg-primary { background-color: var(--color-primary); }
        .text-primary { color: var(--color-primary); }
        .border-primary { border-color: var(--color-primary); }
        .bg-accent { background-color: var(--color-accent); }
        .text-accent { color: var(--color-accent); }
        .border-accent { border-color: var(--color-accent); }
        
        /* Patrón geométrico para hero */
        .hero-pattern {
            background-color: var(--color-primary);
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(255,255,255,0.1) 2%, transparent 2.5%),
                radial-gradient(circle at 75% 75%, rgba(255,255,255,0.08) 2%, transparent 2.5%);
            background-size: 60px 60px;
        }
        
        /* Navbar scroll effect */
        .navbar-scrolled {
            background-color: rgba(255,255,255,0.98);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        
        /* Focus states para accesibilidad */
        a:focus, button:focus, input:focus, select:focus, textarea:focus {
            outline: 2px solid var(--color-accent);
            outline-offset: 2px;
        }
        
        /* Scroll suave */
        html {
            scroll-behavior: smooth;
        }
        
        /* Animación suave para elementos */
        .transition-all {
            transition: all 0.3s ease;
        }
        
        /* Estilos para el botón de WhatsApp */
        .whatsapp-float {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
        }
        
        .whatsapp-float a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            background-color: #25D366;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .whatsapp-float a:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.5);
        }
        
        .whatsapp-tooltip {
            position: absolute;
            right: 70px;
            top: 50%;
            transform: translateY(-50%);
            background: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }
        
        .whatsapp-float:hover .whatsapp-tooltip {
            opacity: 1;
            visibility: visible;
        }
        
        /* Form states */
        .form-error {
            border-color: #ef4444 !important;
        }
        
        .form-success {
            border-color: #22c55e !important;
        }
        
        /* Loading spinner */
        .spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* FAQ accordion */
        details summary {
            cursor: pointer;
            list-style: none;
        }
        
        details summary::-webkit-details-marker {
            display: none;
        }
        
        details[open] summary .chevron {
            transform: rotate(180deg);
        }
        
        /* Dialog styles */
        dialog {
            border: none;
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 90vw;
            width: 500px;
            padding: 0;
        }
        
        dialog::backdrop {
            background: rgba(0, 0, 0, 0.5);
        }
        
        /* Mobile menu */
        .mobile-menu {
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }
        
        .mobile-menu.open {
            transform: translateX(0);
        }
    </style>
    
    <!-- Schema.org JSON-LD -->
    <?= $schemaOrg ?>
    
    <!-- CSS personalizado -->
    <link rel="stylesheet" href="/public/css/app.css">
</head>
<body class="antialiased">
    
    <?php include APP_ROOT . '/views/layouts/header.php'; ?>
    
    <main>
        <?php include APP_ROOT . '/views/sections/hero.php'; ?>
        
        <!-- ============================================================ -->
        <!-- SERVICIOS -->
        <!-- ============================================================ -->
        <?php include APP_ROOT . '/views/sections/services.php'; ?>
        
        <!-- ============================================================ -->
        <!-- POR QUÉ NOSOTROS -->
        <!-- ============================================================ -->
        <?php include APP_ROOT . '/views/sections/about.php'; ?>
        
        <!-- ============================================================ -->
        <!-- TESTIMONIOS -->
        <!-- ============================================================ -->
        <?php include APP_ROOT . '/views/sections/testimonials.php'; ?>
        
        <!-- ============================================================ -->
        <!-- PROCESO -->
        <!-- ============================================================ -->
        <?php include APP_ROOT . '/views/sections/process.php'; ?>
        
        <!-- ============================================================ -->
        <!-- FAQ -->
        <!-- ============================================================ -->
        <?php include APP_ROOT . '/views/sections/faq.php'; ?>
        
        <!-- ============================================================ -->
        <!-- CONTACTO -->
        <!-- ============================================================ -->
        <?php include APP_ROOT . '/views/sections/contact.php'; ?>
        
        <!-- ============================================================ -->
        <!-- CITAS -->
        <!-- ============================================================ -->
        <?php include APP_ROOT . '/views/sections/appointments.php'; ?>
    </main>
    
    <?php include APP_ROOT . '/views/layouts/footer.php'; ?>
    
    <?php
}
