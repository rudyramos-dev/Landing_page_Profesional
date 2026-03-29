<?php
/**
 * Response.php - Respuestas JSON estandarizadas
 * 
 * Proporciona métodos para enviar respuestas JSON consistentes
 * con códigos HTTP apropiados.
 */

// Prevenir acceso directo
if (!defined('APP_ROOT')) {
    die('Acceso directo no permitido');
}

class Response
{
    /**
     * Enviar respuesta de éxito
     */
    public static function success(string $message = 'Operación exitosa', array $data = [], int $code = 200): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Enviar respuesta de error
     */
    public static function error(string $message = 'Error en la operación', ?string $field = null, int $code = 400): void
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($field !== null) {
            $response['field'] = $field;
        }

        self::json($response, $code);
    }

    /**
     * Enviar respuesta de error de validación
     */
    public static function validationError(array $errors, string $message = 'Error de validación'): void
    {
        $firstField = array_key_first($errors);
        $firstMessage = $errors[$firstField] ?? $message;

        self::json([
            'success' => false,
            'message' => $firstMessage,
            'field' => $firstField,
            'errors' => $errors
        ], 422);
    }

    /**
     * Enviar respuesta de rate limit excedido
     */
    public static function rateLimitExceeded(int $retryAfter = 60): void
    {
        if (!headers_sent()) {
            header('Retry-After: ' . $retryAfter);
        }

        $minutes = ceil($retryAfter / 60);
        $message = $minutes === 1 
            ? 'Has enviado demasiadas solicitudes. Intenta en 1 minuto.' 
            : "Has enviado demasiadas solicitudes. Intenta en {$minutes} minutos.";

        self::json([
            'success' => false,
            'message' => $message,
            'retry_after' => $retryAfter
        ], 429);
    }

    /**
     * Enviar respuesta de no autorizado
     */
    public static function unauthorized(string $message = 'No autorizado'): void
    {
        self::json([
            'success' => false,
            'message' => $message
        ], 401);
    }

    /**
     * Enviar respuesta de prohibido
     */
    public static function forbidden(string $message = 'Acceso denegado'): void
    {
        self::json([
            'success' => false,
            'message' => $message
        ], 403);
    }

    /**
     * Enviar respuesta de recurso no encontrado
     */
    public static function notFound(string $message = 'Recurso no encontrado'): void
    {
        self::json([
            'success' => false,
            'message' => $message
        ], 404);
    }

    /**
     * Enviar respuesta de error interno del servidor
     */
    public static function serverError(string $message = 'Error interno del servidor'): void
    {
        self::json([
            'success' => false,
            'message' => $message
        ], 500);
    }

    /**
     * Enviar respuesta de CSRF inválido
     */
    public static function csrfError(): void
    {
        self::json([
            'success' => false,
            'message' => 'Token de seguridad inválido o expirado. Por favor, recarga la página e intenta de nuevo.'
        ], 403);
    }

    /**
     * Enviar respuesta JSON
     */
    private static function json(array $data, int $code = 200): void
    {
        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Redirigir a una URL
     */
    public static function redirect(string $url, int $code = 302): void
    {
        if (!headers_sent()) {
            http_response_code($code);
            header('Location: ' . $url);
        }
        exit;
    }
}
