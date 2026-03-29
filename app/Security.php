<?php
/**
 * Security.php - Funciones de seguridad centralizadas
 * 
 * Maneja: CSRF tokens, sanitización de inputs, honeypot,
 * headers de seguridad y obtención de IP real.
 */

// Prevenir acceso directo
if (!defined('APP_ROOT')) {
    die('Acceso directo no permitido');
}

class Security
{
    /**
     * Configurar headers de seguridad HTTP
     */
    public static function setSecurityHeaders(): void
    {
        // Solo si los headers no han sido enviados
        if (headers_sent()) {
            return;
        }

        // Prevenir clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevenir MIME-sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Política de referencia
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Deshabilitar APIs del navegador innecesarias
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        
        // XSS Protection (navegadores antiguos)
        header('X-XSS-Protection: 1; mode=block');
        
        // Ocultar información del servidor
        header_remove('X-Powered-By');
        
        // HSTS solo en HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // Content Security Policy
        $csp = "default-src 'self'; ";
        $csp .= "script-src 'self' 'unsafe-inline' cdn.tailwindcss.com unpkg.com; ";
        $csp .= "style-src 'self' 'unsafe-inline' fonts.googleapis.com cdn.tailwindcss.com; ";
        $csp .= "font-src 'self' fonts.gstatic.com; ";
        $csp .= "img-src 'self' data:; ";
        $csp .= "connect-src 'self'; ";
        $csp .= "frame-ancestors 'none'; ";
        $csp .= "base-uri 'self'; ";
        $csp .= "form-action 'self'";
        header('Content-Security-Policy: ' . $csp);
    }

    /**
     * Generar token CSRF
     */
    public static function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        
        // Guardar en sesión
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        // También guardar en base de datos para validación adicional
        try {
            $pdo = Database::getInstance();
            $ip = self::getClientIp();
            
            $stmt = $pdo->prepare("INSERT INTO csrf_tokens (token, ip_address) VALUES (?, ?)");
            $stmt->execute([$token, $ip]);
        } catch (Exception $e) {
            error_log('Error guardando CSRF token: ' . $e->getMessage());
        }
        
        return $token;
    }

    /**
     * Validar token CSRF
     */
    public static function validateCsrfToken(?string $token): bool
    {
        if (empty($token)) {
            return false;
        }

        // Verificar en sesión
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $sessionToken = $_SESSION['csrf_token'] ?? null;
        $tokenTime = $_SESSION['csrf_token_time'] ?? 0;

        // Verificar que el token coincide y no ha expirado
        if ($sessionToken !== $token) {
            return false;
        }

        // Verificar expiración (2 horas)
        if ((time() - $tokenTime) > CSRF_TOKEN_EXPIRY) {
            return false;
        }

        // Verificar en base de datos que no ha sido usado
        try {
            $pdo = Database::getInstance();
            
            $stmt = $pdo->prepare("SELECT id, used FROM csrf_tokens WHERE token = ?");
            $stmt->execute([$token]);
            $row = $stmt->fetch();
            
            if (!$row || $row['used'] == 1) {
                return false;
            }
            
            // Marcar como usado (token de un solo uso)
            $stmt = $pdo->prepare("UPDATE csrf_tokens SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);
            
        } catch (Exception $e) {
            error_log('Error validando CSRF token: ' . $e->getMessage());
            return false;
        }

        // Generar nuevo token para siguiente request
        self::generateCsrfToken();

        return true;
    }

    /**
     * Obtener token CSRF actual o generar uno nuevo
     */
    public static function getCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            return self::generateCsrfToken();
        }

        // Verificar si el token no ha expirado
        $tokenTime = $_SESSION['csrf_token_time'] ?? 0;
        if ((time() - $tokenTime) > CSRF_TOKEN_EXPIRY) {
            return self::generateCsrfToken();
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Verificar campo honeypot
     * Retorna true si el honeypot fue llenado (es un bot)
     */
    public static function checkHoneypot(?string $value): bool
    {
        return !empty($value);
    }

    /**
     * Sanitizar string para output HTML
     */
    public static function escapeHtml(?string $string): string
    {
        if ($string === null) {
            return '';
        }
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitizar input de texto
     */
    public static function sanitizeString(?string $string, int $maxLength = 255): string
    {
        if ($string === null) {
            return '';
        }
        
        // Trim whitespace
        $string = trim($string);
        
        // Eliminar tags HTML
        $string = strip_tags($string);
        
        // Limitar longitud
        if (mb_strlen($string) > $maxLength) {
            $string = mb_substr($string, 0, $maxLength);
        }
        
        return $string;
    }

    /**
     * Sanitizar email
     */
    public static function sanitizeEmail(?string $email): string
    {
        if ($email === null) {
            return '';
        }
        
        $email = trim($email);
        $email = strtolower($email);
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        return $email ?: '';
    }

    /**
     * Validar formato de email
     */
    public static function isValidEmail(?string $email): bool
    {
        if (empty($email)) {
            return false;
        }
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Sanitizar número de teléfono
     */
    public static function sanitizePhone(?string $phone): string
    {
        if ($phone === null) {
            return '';
        }
        
        // Mantener solo números, +, -, espacios y paréntesis
        $phone = preg_replace('/[^\d\+\-\s\(\)]/', '', $phone);
        $phone = trim($phone);
        
        // Limitar longitud
        if (strlen($phone) > 20) {
            $phone = substr($phone, 0, 20);
        }
        
        return $phone;
    }

    /**
     * Obtener IP real del cliente (considerando proxies)
     */
    public static function getClientIp(): string
    {
        $ip = '';
        
        // Orden de prioridad para obtener la IP
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Proxy estándar
            'HTTP_X_REAL_IP',            // Nginx
            'HTTP_CLIENT_IP',            // Algunos proxies
            'REMOTE_ADDR'                // Conexión directa
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                // X-Forwarded-For puede contener múltiples IPs
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                
                // Validar que es una IP válida
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    break;
                }
                
                // Si no es válida pero es la última opción, usarla de todos modos
                if ($header === 'REMOTE_ADDR') {
                    $ip = filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
                    break;
                }
            }
        }
        
        // Fallback
        if (empty($ip)) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        
        return $ip;
    }

    /**
     * Obtener User Agent del cliente
     */
    public static function getUserAgent(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return self::sanitizeString($ua, 500);
    }

    /**
     * Registrar evento de seguridad en el log
     */
    public static function logSecurityEvent(string $type, string $message, array $context = []): void
    {
        $logFile = APP_ROOT . '/logs/security.log';
        $logDir = dirname($logFile);
        
        // Crear directorio si no existe
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = self::getClientIp();
        $ua = self::getUserAgent();
        
        $logEntry = "[{$timestamp}] [{$type}] [{$ip}] {$message}";
        
        if (!empty($context)) {
            $logEntry .= ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        $logEntry .= " | UA: {$ua}\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Verificar si la solicitud es AJAX
     */
    public static function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Verificar método HTTP
     */
    public static function isMethod(string $method): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === strtoupper($method);
    }

    /**
     * Obtener datos JSON del body de la solicitud
     */
    public static function getJsonInput(): array
    {
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            return [];
        }
        
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        
        return $data ?: [];
    }
}
