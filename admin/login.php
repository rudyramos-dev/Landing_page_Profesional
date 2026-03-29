<?php
/**
 * admin/login.php - Página de Login del Panel de Administración
 * 
 * Esta página es renderizada por el front controller (index.php).
 * Solo debe ser incluida, no accedida directamente.
 */

// Prevenir acceso directo
if (!defined('APP_ROOT')) {
    die('Acceso directo no permitido');
}

$business = BusinessConfig::all();
$brandName = $business['business_name'] ?? BUSINESS_NAME;
$brandPrimary = $business['color_primary'] ?? COLOR_PRIMARY;
$brandAccent = $business['color_accent'] ?? COLOR_ACCENT;
$brandBg = $business['color_bg'] ?? COLOR_BG;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Acceso Administrativo | <?php echo htmlspecialchars($brandName); ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '<?php echo htmlspecialchars($brandPrimary); ?>',
                        accent: '<?php echo htmlspecialchars($brandAccent); ?>',
                    }
                }
            }
        }
    </script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        body {
            background: linear-gradient(135deg, <?php echo htmlspecialchars($brandPrimary); ?>15 0%, <?php echo htmlspecialchars($brandBg); ?> 100%);
        }
        .login-card {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Logo / Header -->
        <div class="text-center mb-8">
            <a href="/" class="inline-block">
                <h1 class="text-2xl font-bold text-primary"><?php echo htmlspecialchars($brandName); ?></h1>
            </a>
            <p class="text-gray-600 mt-2">Panel de Administración</p>
        </div>

        <!-- Login Card -->
        <div class="login-card bg-white rounded-2xl p-8">
            <!-- Error Message -->
            <?php if (!empty($loginError)): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"></i>
                <span class="text-red-700 text-sm"><?php echo htmlspecialchars($loginError); ?></span>
            </div>
            <?php endif; ?>

            <!-- Success Message -->
            <?php if (!empty($logoutMessage)): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg flex items-start gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5"></i>
                <span class="text-green-700 text-sm"><?php echo htmlspecialchars($logoutMessage); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="/<?php echo ADMIN_PATH; ?>/login" id="login-form" class="space-y-6">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                
                <!-- Honeypot -->
                <div style="position: absolute; left: -9999px; top: -9999px;">
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        Usuario
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="user" class="w-5 h-5 text-gray-400"></i>
                        </span>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            required
                            autocomplete="username"
                            class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                            placeholder="Ingresa tu usuario"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Contraseña
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i data-lucide="lock" class="w-5 h-5 text-gray-400"></i>
                        </span>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            autocomplete="current-password"
                            class="w-full pl-10 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                            placeholder="Ingresa tu contraseña"
                        >
                        <button 
                            type="button" 
                            id="toggle-password"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center"
                            title="Mostrar/ocultar contraseña"
                        >
                            <i data-lucide="eye" class="w-5 h-5 text-gray-400 hover:text-gray-600"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="flex items-center">
                    <input 
                        type="checkbox" 
                        id="remember" 
                        name="remember" 
                        class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
                    >
                    <label for="remember" class="ml-2 text-sm text-gray-600">
                        Recordarme en este dispositivo
                    </label>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full bg-primary text-white py-3 px-4 rounded-lg font-semibold hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-all duration-200 flex items-center justify-center gap-2"
                >
                    <i data-lucide="log-in" class="w-5 h-5"></i>
                    Iniciar Sesión
                </button>
            </form>
        </div>

        <!-- Back to Site -->
        <div class="text-center mt-6">
            <a href="/" class="text-sm text-gray-600 hover:text-primary transition-colors inline-flex items-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Volver al sitio
            </a>
        </div>

        <!-- Security Notice -->
        <div class="text-center mt-8">
            <p class="text-xs text-gray-500">
                <i data-lucide="shield-check" class="w-3 h-3 inline-block mr-1"></i>
                Conexión segura · Acceso restringido
            </p>
        </div>
    </div>

    <script>
        // Inicializar iconos
        lucide.createIcons();

        // Toggle password visibility
        const toggleBtn = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');
        
        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', () => {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                
                // Cambiar el icono
                const icon = toggleBtn.querySelector('i');
                if (icon) {
                    icon.setAttribute('data-lucide', type === 'password' ? 'eye' : 'eye-off');
                    lucide.createIcons();
                }
            });
        }

        // Auto-focus en el primer campo vacío
        const usernameInput = document.getElementById('username');
        if (usernameInput && !usernameInput.value) {
            usernameInput.focus();
        } else if (passwordInput && !passwordInput.value) {
            passwordInput.focus();
        }
    </script>
</body>
</html>
