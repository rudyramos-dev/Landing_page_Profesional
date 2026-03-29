<?php
/**
 * RateLimiter.php - Control de rate limiting por IP
 * 
 * Implementa límites de solicitudes por IP para prevenir spam y ataques.
 * Los límites se almacenan en SQLite para persistencia entre requests.
 */

// Prevenir acceso directo
if (!defined('APP_ROOT')) {
    die('Acceso directo no permitido');
}

class RateLimiter
{
    private PDO $pdo;
    private string $ip;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->ip = Security::getClientIp();
        
        // Limpiar registros antiguos al inicio de cada request
        $this->cleanup();
    }

    /**
     * Verificar si una acción está permitida según el rate limit
     * 
     * @param string $action Tipo de acción (lead_submit, appointment_submit, admin_login)
     * @return array ['allowed' => bool, 'remaining' => int, 'retry_after' => int]
     */
    public function check(string $action): array
    {
        $limits = $this->getLimits($action);
        $maxAttempts = $limits['max'];
        $windowSeconds = $limits['window'];

        // Buscar registro existente para esta IP y acción
        $stmt = $this->pdo->prepare("
            SELECT id, attempts, window_start 
            FROM rate_limits 
            WHERE ip_address = ? AND action = ?
        ");
        $stmt->execute([$this->ip, $action]);
        $record = $stmt->fetch();

        if (!$record) {
            // No hay registro, crear uno nuevo
            return [
                'allowed' => true,
                'remaining' => $maxAttempts - 1,
                'retry_after' => 0
            ];
        }

        // Verificar si la ventana de tiempo ha expirado
        $windowStart = strtotime($record['window_start']);
        $windowEnd = $windowStart + $windowSeconds;
        $now = time();

        if ($now > $windowEnd) {
            // La ventana expiró, reiniciar contador
            $nowExpr = Database::currentTimestampExpression();
            $stmt = $this->pdo->prepare("
                UPDATE rate_limits 
                SET attempts = 0, window_start = {$nowExpr}, last_attempt = {$nowExpr}
                WHERE id = ?
            ");
            $stmt->execute([$record['id']]);
            
            return [
                'allowed' => true,
                'remaining' => $maxAttempts - 1,
                'retry_after' => 0
            ];
        }

        // Verificar si se ha alcanzado el límite
        if ($record['attempts'] >= $maxAttempts) {
            $retryAfter = $windowEnd - $now;
            
            // Registrar evento de seguridad
            Security::logSecurityEvent(
                'RATE_LIMIT',
                "Rate limit alcanzado para {$action}",
                ['ip' => $this->ip, 'attempts' => $record['attempts'], 'action' => $action]
            );
            
            return [
                'allowed' => false,
                'remaining' => 0,
                'retry_after' => $retryAfter
            ];
        }

        return [
            'allowed' => true,
            'remaining' => $maxAttempts - $record['attempts'] - 1,
            'retry_after' => 0
        ];
    }

    /**
     * Registrar un intento de una acción
     */
    public function recordAttempt(string $action): void
    {
        // Buscar registro existente
        $stmt = $this->pdo->prepare("
            SELECT id, attempts, window_start 
            FROM rate_limits 
            WHERE ip_address = ? AND action = ?
        ");
        $stmt->execute([$this->ip, $action]);
        $record = $stmt->fetch();

        $limits = $this->getLimits($action);
        $windowSeconds = $limits['window'];

        if (!$record) {
            // Crear nuevo registro
            $nowExpr = Database::currentTimestampExpression();
            $stmt = $this->pdo->prepare("
                INSERT INTO rate_limits (ip_address, action, attempts, window_start, last_attempt)
                VALUES (?, ?, 1, {$nowExpr}, {$nowExpr})
            ");
            $stmt->execute([$this->ip, $action]);
            return;
        }

        // Verificar si la ventana ha expirado
        $windowStart = strtotime($record['window_start']);
        $windowEnd = $windowStart + $windowSeconds;
        $now = time();

        if ($now > $windowEnd) {
            // Reiniciar ventana
            $nowExpr = Database::currentTimestampExpression();
            $stmt = $this->pdo->prepare("
                UPDATE rate_limits 
                SET attempts = 1, window_start = {$nowExpr}, last_attempt = {$nowExpr}
                WHERE id = ?
            ");
            $stmt->execute([$record['id']]);
        } else {
            // Incrementar contador
            $nowExpr = Database::currentTimestampExpression();
            $stmt = $this->pdo->prepare("
                UPDATE rate_limits 
                SET attempts = attempts + 1, last_attempt = {$nowExpr}
                WHERE id = ?
            ");
            $stmt->execute([$record['id']]);
        }
    }

    /**
     * Resetear el contador de una acción para una IP (después de login exitoso, por ejemplo)
     */
    public function reset(string $action): void
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM rate_limits WHERE ip_address = ? AND action = ?
        ");
        $stmt->execute([$this->ip, $action]);
    }

    /**
     * Obtener los límites configurados para cada acción
     */
    private function getLimits(string $action): array
    {
        $limits = [
            'lead_submit' => [
                'max' => RATE_LIMIT_MAX_LEADS,
                'window' => RATE_LIMIT_WINDOW
            ],
            'appointment_submit' => [
                'max' => RATE_LIMIT_MAX_APPOINTMENTS,
                'window' => RATE_LIMIT_WINDOW
            ],
            'admin_login' => [
                'max' => RATE_LIMIT_MAX_LOGIN,
                'window' => RATE_LIMIT_LOGIN_WINDOW
            ]
        ];

        return $limits[$action] ?? ['max' => 10, 'window' => 600];
    }

    /**
     * Limpiar registros antiguos de la base de datos
     */
    private function cleanup(): void
    {
        // Eliminar registros de más de 1 hora
        $cutoffExpr = Database::datetimeHoursAgoExpression(1);
        $this->pdo->exec("
            DELETE FROM rate_limits 
            WHERE window_start < {$cutoffExpr}
        ");
    }

    /**
     * Verificar si una IP está en lockout (para login de admin)
     */
    public function isLockedOut(): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT attempts, window_start, last_attempt 
            FROM rate_limits 
            WHERE ip_address = ? AND action = 'admin_login'
        ");
        $stmt->execute([$this->ip]);
        $record = $stmt->fetch();

        if (!$record) {
            return false;
        }

        // Verificar si está en período de lockout
        if ($record['attempts'] >= RATE_LIMIT_MAX_LOGIN) {
            $lastAttempt = strtotime($record['last_attempt']);
            $lockoutEnd = $lastAttempt + RATE_LIMIT_LOCKOUT;
            
            if (time() < $lockoutEnd) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtener tiempo restante de lockout en segundos
     */
    public function getLockoutRemaining(): int
    {
        $stmt = $this->pdo->prepare("
            SELECT last_attempt 
            FROM rate_limits 
            WHERE ip_address = ? AND action = 'admin_login'
        ");
        $stmt->execute([$this->ip]);
        $record = $stmt->fetch();

        if (!$record) {
            return 0;
        }

        $lastAttempt = strtotime($record['last_attempt']);
        $lockoutEnd = $lastAttempt + RATE_LIMIT_LOCKOUT;
        $remaining = $lockoutEnd - time();

        return max(0, $remaining);
    }
}
