<?php
/**
 * Helpers.php - Funciones auxiliares para la aplicación
 * 
 * Contiene funciones de generación de contenido estático
 * que no dependen del request actual.
 */

if (!defined('APP_ROOT')) {
    die('Acceso directo no permitido');
}

/**
 * Generar contenido de robots.txt
 */
function generateRobotsTxt(): string
{
    $siteUrl = SITE_URL;
    $adminPath = ADMIN_PATH;
    
    return <<<ROBOTS
User-agent: *
Allow: /
Disallow: /{$adminPath}/
Disallow: /api/
Disallow: /database/
Disallow: /app/
Disallow: /logs/

Sitemap: {$siteUrl}/sitemap.xml
ROBOTS;
}

/**
 * Generar contenido de sitemap.xml
 */
function generateSitemap(): string
{
    $siteUrl = SITE_URL;
    $today = date('Y-m-d');
    
    return <<<SITEMAP
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{$siteUrl}/</loc>
        <lastmod>{$today}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
    </url>
</urlset>
SITEMAP;
}

/**
 * Generar Schema.org JSON-LD para SEO
 */
function generateSchemaOrg(array $business): string
{
    $businessName = $business['business_name'] ?? BUSINESS_NAME;
    $businessDescription = $business['business_description'] ?? BUSINESS_DESCRIPTION;
    $siteUrl = $business['site_url'] ?? SITE_URL;
    $businessPhone = $business['business_phone'] ?? BUSINESS_PHONE;
    $businessEmail = $business['business_email'] ?? BUSINESS_EMAIL;
    $businessAddress = $business['business_address'] ?? BUSINESS_ADDRESS;
    $businessHours = $business['business_hours'] ?? BUSINESS_HOURS;
    $socialInstagram = $business['social_instagram'] ?? SOCIAL_INSTAGRAM;
    $socialFacebook = $business['social_facebook'] ?? SOCIAL_FACEBOOK;
    $socialLinkedin = $business['social_linkedin'] ?? SOCIAL_LINKEDIN;

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'LocalBusiness',
        'name' => $businessName,
        'description' => $businessDescription,
        'url' => $siteUrl,
        'telephone' => $businessPhone,
        'email' => $businessEmail,
        'address' => [
            '@type' => 'PostalAddress',
            'addressLocality' => $businessAddress,
            'addressCountry' => 'MX'
        ],
        'openingHours' => $businessHours
    ];

    $sameAs = [];
    if ($socialInstagram) {
        $sameAs[] = $socialInstagram;
    }
    if ($socialFacebook) {
        $sameAs[] = $socialFacebook;
    }
    if ($socialLinkedin) {
        $sameAs[] = $socialLinkedin;
    }
    
    if (!empty($sameAs)) {
        $schema['sameAs'] = $sameAs;
    }

    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Renderizar página 404
 */
function render404Page(): void
{
    $business = BusinessConfig::all();
    $businessName = $business['business_name'] ?? BUSINESS_NAME;
    $colorPrimary = $business['color_primary'] ?? COLOR_PRIMARY;
    $colorAccent = $business['color_accent'] ?? COLOR_ACCENT;
    $colorBg = $business['color_bg'] ?? COLOR_BG;

    http_response_code(404);
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página no encontrada — <?= Security::escapeHtml($businessName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --color-primary: <?= Security::escapeHtml($colorPrimary) ?>;
            --color-accent: <?= Security::escapeHtml($colorAccent) ?>;
            --color-bg: <?= Security::escapeHtml($colorBg) ?>;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center" style="background-color: var(--color-bg);">
    <div class="text-center px-4">
        <h1 class="text-6xl font-bold mb-4" style="color: var(--color-primary);">404</h1>
        <p class="text-xl text-gray-600 mb-8">Lo sentimos, la página que buscas no existe.</p>
        <a href="/" class="inline-block px-6 py-3 rounded-lg text-white font-medium transition-all hover:opacity-90" style="background-color: var(--color-primary);">
            Volver al inicio
        </a>
    </div>
</body>
</html>
    <?php
    exit;
}
