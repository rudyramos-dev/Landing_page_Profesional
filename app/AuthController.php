<?php
/**
 * AuthController.php - Controlador de autenticación del panel admin
 * 
 * Maneja login, logout y verificación de sesiones.
 * Implementa medidas de seguridad contra ataques de fuerza bruta.
 */

// Prevenir acceso directo
if (!defined('APP_ROOT')) {
    die('Acceso directo no permitido');
}

class AuthController
{
    private PDO $pdo;
    private RateLimiter $rateLimiter;
    private Validator $validator;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->rateLimiter = new RateLimiter();
        $this->validator = new Validator();
    }

    /**
     * Mostrar página de login o procesar login
     */
    public function login(): void
    {
        // Si ya está logueado, redirigir al dashboard
        if ($this->isLoggedIn()) {
            Response::redirect('/' . ADMIN_PATH);
        }

        if (Security::isMethod('POST')) {
            $this->processLogin();
        } else {
            $this->showLoginPage();
        }
    }

    /**
     * Procesar intento de login
     */
    private function processLogin(): void
    {
        $input = $_POST;

        // Verificar CSRF
        $csrfToken = $input['csrf_token'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            $this->showLoginPage('Token de seguridad inválido. Por favor, intenta de nuevo.');
            return;
        }

        // Verificar lockout por IP
        if ($this->rateLimiter->isLockedOut()) {
            $remaining = $this->rateLimiter->getLockoutRemaining();
            $minutes = ceil($remaining / 60);
            $this->showLoginPage("Demasiados intentos fallidos. Intenta en {$minutes} minutos.");
            return;
        }

        // Verificar rate limit
        $rateCheck = $this->rateLimiter->check('admin_login');
        if (!$rateCheck['allowed']) {
            $minutes = ceil($rateCheck['retry_after'] / 60);
            Security::logSecurityEvent('LOGIN_RATE_LIMIT', 'Rate limit alcanzado en login');
            $this->showLoginPage("Demasiados intentos. Intenta en {$minutes} minutos.");
            return;
        }

        // Registrar intento
        $this->rateLimiter->recordAttempt('admin_login');

        // Validar datos
        if (!$this->validator->validateLogin($input)) {
            $error = $this->validator->getFirstError();
            $this->showLoginPage($error['message']);
            return;
        }

        $data = $this->validator->getData();

        // Buscar usuario
        $stmt = $this->pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$data['username']]);
        $admin = $stmt->fetch();

        if (!$admin) {
            Security::logSecurityEvent('LOGIN_FAILED', 'Usuario no encontrado', ['username' => $data['username']]);
            $this->showLoginPage('Credenciales incorrectas.');
            return;
        }

        // Verificar si la cuenta está bloqueada
        if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
            Security::logSecurityEvent('LOGIN_BLOCKED', 'Cuenta bloqueada', ['username' => $data['username']]);
            $this->showLoginPage('Esta cuenta está temporalmente bloqueada.');
            return;
        }

        // Verificar contraseña
        if (!password_verify($data['password'], $admin['password_hash'])) {
            // Incrementar intentos fallidos
            $this->incrementLoginAttempts($admin['id']);
            Security::logSecurityEvent('LOGIN_FAILED', 'Contraseña incorrecta', ['username' => $data['username']]);
            $this->showLoginPage('Credenciales incorrectas.');
            return;
        }

        // Login exitoso
        $this->createSession($admin);
        
        // Resetear rate limit y intentos fallidos
        $this->rateLimiter->reset('admin_login');
        $this->resetLoginAttempts($admin['id']);
        
        // Actualizar último login
        $nowExpr = Database::currentTimestampExpression();
        $stmt = $this->pdo->prepare("UPDATE admins SET last_login = {$nowExpr} WHERE id = ?");
        $stmt->execute([$admin['id']]);

        // Registrar en log
        $this->logAdminAction($admin['id'], 'login', null, null, 'Login exitoso');

        Security::logSecurityEvent('LOGIN_SUCCESS', 'Login exitoso', ['username' => $data['username']]);

        Response::redirect('/' . ADMIN_PATH);
    }

    /**
     * Cerrar sesión
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $adminId = $_SESSION['admin_id'] ?? null;

        if ($adminId) {
            $this->logAdminAction($adminId, 'logout', null, null, 'Logout');
        }

        // Destruir sesión
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();

        Response::redirect('/' . ADMIN_PATH . '/login');
    }

    /**
     * Verificar si el usuario está logueado
     */
    public function isLoggedIn(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verificar que existe la sesión de admin
        if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_logged_in'])) {
            return false;
        }

        // Verificar timeout de inactividad
        $lastActivity = $_SESSION['admin_last_activity'] ?? 0;
        if ((time() - $lastActivity) > ADMIN_SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }

        // Actualizar último activity
        $_SESSION['admin_last_activity'] = time();

        return true;
    }

    /**
     * Requerir autenticación (middleware)
     */
    public function requireAuth(): void
    {
        if (!$this->isLoggedIn()) {
            // Si es petición AJAX, devolver error JSON
            if (Security::isAjaxRequest()) {
                Response::unauthorized('Sesión expirada. Por favor, inicia sesión de nuevo.');
            }
            Response::redirect('/' . ADMIN_PATH . '/login');
        }
    }

    /**
     * Obtener ID del admin actual
     */
    public function getAdminId(): ?int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['admin_id'] ?? null;
    }

    /**
     * Obtener datos del admin actual
     */
    public function getCurrentAdmin(): ?array
    {
        $adminId = $this->getAdminId();
        if (!$adminId) {
            return null;
        }

        $stmt = $this->pdo->prepare("SELECT id, username, last_login, created_at FROM admins WHERE id = ?");
        $stmt->execute([$adminId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Cambiar contraseña del admin
     */
    public function changePassword(string $currentPassword, string $newPassword): bool
    {
        $adminId = $this->getAdminId();
        if (!$adminId) {
            return false;
        }

        // Obtener admin actual
        $stmt = $this->pdo->prepare("SELECT password_hash FROM admins WHERE id = ?");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($currentPassword, $admin['password_hash'])) {
            return false;
        }

        // Validar nueva contraseña
        if (strlen($newPassword) < 8) {
            return false;
        }

        // Actualizar contraseña
        $newHash = password_hash($newPassword, PASSWORD_ARGON2ID);
        $stmt = $this->pdo->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
        $stmt->execute([$newHash, $adminId]);

        $this->logAdminAction($adminId, 'password_change', null, null, 'Contraseña cambiada');

        return true;
    }

    /**
     * Crear sesión de admin
     */
    private function createSession(array $admin): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configurar cookies de sesión seguras
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            session_start();
        }

        // Regenerar ID de sesión para prevenir session fixation
        session_regenerate_id(true);

        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_last_activity'] = time();
        $_SESSION['admin_ip'] = Security::getClientIp();
    }

    /**
     * Incrementar intentos de login fallidos
     */
    private function incrementLoginAttempts(int $adminId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE admins 
            SET login_attempts = login_attempts + 1 
            WHERE id = ?
        ");
        $stmt->execute([$adminId]);

        // Si alcanza 10 intentos, bloquear cuenta
        $stmt = $this->pdo->prepare("SELECT login_attempts FROM admins WHERE id = ?");
        $stmt->execute([$adminId]);
        $result = $stmt->fetch();

        if ($result && $result['login_attempts'] >= 10) {
            $lockUntil = date('Y-m-d H:i:s', time() + 3600); // 1 hora
            $stmt = $this->pdo->prepare("UPDATE admins SET locked_until = ? WHERE id = ?");
            $stmt->execute([$lockUntil, $adminId]);
            
            Security::logSecurityEvent('ACCOUNT_LOCKED', "Cuenta bloqueada por intentos fallidos", ['admin_id' => $adminId]);
        }
    }

    /**
     * Resetear intentos de login
     */
    private function resetLoginAttempts(int $adminId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE admins 
            SET login_attempts = 0, locked_until = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$adminId]);
    }

    /**
     * Mostrar página de login
     */
    private function showLoginPage(?string $error = null): void
    {
        $csrfToken = Security::getCsrfToken();
        
        // Incluir template de login
        include APP_ROOT . '/admin/login.php';
        exit;
    }

    /**
     * Registrar acción de admin
     */
    private function logAdminAction(int $adminId, string $action, ?string $targetType, ?int $targetId, string $details): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO admin_log (admin_id, action, target_type, target_id, details, ip_address)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $adminId,
                $action,
                $targetType,
                $targetId,
                $details,
                Security::getClientIp()
            ]);
        } catch (PDOException $e) {
            error_log('Error en admin_log: ' . $e->getMessage());
        }
    }
}
