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
