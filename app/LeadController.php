<?php
/**
 * LeadController.php - Controlador del formulario de contacto
 * 
 * Maneja la recepción, validación y almacenamiento de leads.
 * Envía notificaciones por email al dueño y confirmación al cliente.
 */

// Prevenir acceso directo
if (!defined('APP_ROOT')) {
    die('Acceso directo no permitido');
}

class LeadController
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
     * Procesar envío del formulario de contacto
     */
    public function submit(): void
    {
        // Verificar método POST
        if (!Security::isMethod('POST')) {
            Response::error('Método no permitido', null, 405);
        }

        // Obtener datos del request
        $input = $_POST;
        
        // Si es JSON, obtener del body
        if (empty($input)) {
            $input = Security::getJsonInput();
        }

        // Verificar CSRF token
        $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            Response::csrfError();
        }

        // Verificar honeypot
        $honeypot = $input['website'] ?? '';
        if (Security::checkHoneypot($honeypot)) {
            // Es un bot, simular éxito sin guardar
            Security::logSecurityEvent('HONEYPOT', 'Bot detectado en formulario de contacto');
            Response::success('Gracias por tu mensaje. Te contactaremos pronto.');
        }

        // Verificar rate limit
        $rateCheck = $this->rateLimiter->check('lead_submit');
        if (!$rateCheck['allowed']) {
            Response::rateLimitExceeded($rateCheck['retry_after']);
        }

        // Registrar intento
        $this->rateLimiter->recordAttempt('lead_submit');

        // Validar datos
        if (!$this->validator->validateLead($input)) {
            $error = $this->validator->getFirstError();
            Response::validationError($this->validator->getErrors());
        }

        // Obtener datos validados
        $data = $this->validator->getData();
        $data['ip_address'] = Security::getClientIp();
        $data['user_agent'] = Security::getUserAgent();

        // Guardar en base de datos
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO leads (name, email, phone, service_interest, message, source, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, 'form', ?, ?)
            ");
            
            $stmt->execute([
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['service_interest'],
                $data['message'],
                $data['ip_address'],
                $data['user_agent']
            ]);

            $leadId = $this->pdo->lastInsertId();
            $data['id'] = $leadId;
            $data['created_at'] = date('Y-m-d H:i:s');

        } catch (PDOException $e) {
            error_log('Error guardando lead: ' . $e->getMessage());
            Response::serverError('Error al procesar tu solicitud. Por favor, intenta de nuevo.');
        }

        // Enviar notificaciones por email (no bloquean la respuesta)
        Mailer::sendLeadNotification($data);
        Mailer::sendLeadConfirmation($data);

        // Respuesta exitosa
        $name = Security::escapeHtml($data['name']);
        Response::success(
            "Gracias {$name}, hemos recibido tu mensaje. Te contactaremos en menos de 24 horas.",
            ['lead_id' => $leadId]
        );
    }

    /**
     * Obtener todos los leads (para el panel admin)
     */
    public function getAll(array $filters = []): array
    {
        $where = [];
        $params = [];

        // Filtro por status
        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }

        // Filtro por búsqueda (nombre o email)
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $where[] = '(name LIKE ? OR email LIKE ?)';
            $params[] = $search;
            $params[] = $search;
        }

        // Filtro por rango de fechas
        if (!empty($filters['date_from'])) {
            $where[] = "date(created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = "date(created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Paginación
        $page = max(1, (int)($filters['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Contar total
        $countSql = "SELECT COUNT(*) as total FROM leads {$whereClause}";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        // Obtener leads
        $sql = "SELECT * FROM leads {$whereClause} ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $leads = $stmt->fetchAll();

        return [
            'leads' => $leads,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Obtener un lead por ID
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM leads WHERE id = ?");
        $stmt->execute([$id]);
        $lead = $stmt->fetch();
        return $lead ?: null;
    }

    /**
     * Actualizar status de un lead
     */
    public function updateStatus(int $id, string $status, int $adminId): bool
    {
        $validStatuses = ['new', 'contacted', 'converted', 'discarded'];
        
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE leads SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);

            // Registrar en log de admin
            $this->logAdminAction($adminId, 'status_change', 'lead', $id, "Status cambiado a: {$status}");

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log('Error actualizando status de lead: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de leads
     */
    public function getStats(): array
    {
        // Total de leads
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM leads");
        $total = $stmt->fetch()['total'];

        // Nuevos hoy
        $todayExpr = Database::currentDateExpression();
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM leads WHERE DATE(created_at) = {$todayExpr}");
        $newToday = $stmt->fetch()['total'];

        // Esta semana
        $sevenDaysAgoExpr = Database::dateDaysAgoExpression(7);
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM leads WHERE DATE(created_at) >= {$sevenDaysAgoExpr}");
        $thisWeek = $stmt->fetch()['total'];

        // Por status
        $stmt = $this->pdo->query("SELECT status, COUNT(*) as count FROM leads GROUP BY status");
        $byStatus = [];
        while ($row = $stmt->fetch()) {
            $byStatus[$row['status']] = $row['count'];
        }

        // Tasa de conversión
        $converted = $byStatus['converted'] ?? 0;
        $contacted = ($byStatus['contacted'] ?? 0) + $converted;
        $conversionRate = $total > 0 ? round(($converted / $total) * 100, 1) : 0;

        return [
            'total' => $total,
            'new_today' => $newToday,
            'this_week' => $thisWeek,
            'by_status' => $byStatus,
            'conversion_rate' => $conversionRate
        ];
    }

    /**
     * Exportar leads a CSV
     */
    public function exportToCsv(array $filters = []): string
    {
        $result = $this->getAll(array_merge($filters, ['page' => 1]));
        
        // Obtener todos sin paginación para export
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = "date(created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = "date(created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = "SELECT id, name, email, phone, service_interest, message, status, created_at FROM leads {$whereClause} ORDER BY created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $leads = $stmt->fetchAll();

        // Generar CSV
        $output = fopen('php://temp', 'r+');
        
        // Headers
        fputcsv($output, ['ID', 'Nombre', 'Email', 'Teléfono', 'Servicio', 'Mensaje', 'Status', 'Fecha']);
        
        // Data
        foreach ($leads as $lead) {
            fputcsv($output, [
                $lead['id'],
                $lead['name'],
                $lead['email'],
                $lead['phone'],
                $lead['service_interest'],
                $lead['message'],
                $lead['status'],
                $lead['created_at']
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Registrar acción de admin
     */
    private function logAdminAction(int $adminId, string $action, string $targetType, int $targetId, string $details): void
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
