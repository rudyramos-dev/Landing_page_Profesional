<?php
/**
 * AppointmentController.php - Controlador de citas
 * 
 * Maneja la creación, validación y gestión de citas.
 * Incluye lógica para obtener slots disponibles.
 */

// Prevenir acceso directo
if (!defined('APP_ROOT')) {
    die('Acceso directo no permitido');
}

class AppointmentController
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
     * Procesar envío de solicitud de cita
     */
    public function submit(): void
    {
        // Verificar método POST
        if (!Security::isMethod('POST')) {
            Response::error('Método no permitido', null, 405);
        }

        // Obtener datos del request
        $input = $_POST;
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
            Security::logSecurityEvent('HONEYPOT', 'Bot detectado en formulario de citas');
            Response::success('Gracias, hemos recibido tu solicitud de cita.');
        }

        // Verificar rate limit
        $rateCheck = $this->rateLimiter->check('appointment_submit');
        if (!$rateCheck['allowed']) {
            Response::rateLimitExceeded($rateCheck['retry_after']);
        }

        // Registrar intento
        $this->rateLimiter->recordAttempt('appointment_submit');

        // Validar datos
        if (!$this->validator->validateAppointment($input)) {
            Response::validationError($this->validator->getErrors());
        }

        // Obtener datos validados
        $data = $this->validator->getData();
        $data['ip_address'] = Security::getClientIp();

        // Verificar disponibilidad del slot (race condition protection)
        if (!$this->isSlotAvailable($data['appointment_date'], $data['appointment_time'])) {
            Response::error(
                'Lo sentimos, el horario seleccionado ya no está disponible. Por favor, elige otro horario.',
                'appointment_time'
            );
        }

        // Guardar en base de datos
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO appointments (name, email, phone, appointment_date, appointment_time, service_interest, message, ip_address)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['appointment_date'],
                $data['appointment_time'],
                $data['service_interest'],
                $data['message'],
                $data['ip_address']
            ]);

            $appointmentId = $this->pdo->lastInsertId();
            $data['id'] = $appointmentId;
            $data['created_at'] = date('Y-m-d H:i:s');

        } catch (PDOException $e) {
            error_log('Error guardando cita: ' . $e->getMessage());
            Response::serverError('Error al procesar tu solicitud. Por favor, intenta de nuevo.');
        }

        // Enviar notificaciones por email
        Mailer::sendAppointmentNotification($data);
        Mailer::sendAppointmentReceived($data);

        // Respuesta exitosa
        $name = Security::escapeHtml($data['name']);
        Response::success(
            "Gracias {$name}, hemos recibido tu solicitud de cita. Te confirmaremos por email en menos de 24 horas.",
            ['appointment_id' => $appointmentId]
        );
    }

    /**
     * Obtener slots disponibles para una fecha
     */
    public function getAvailableSlots(): void
    {
        // Verificar método POST
        if (!Security::isMethod('POST')) {
            Response::error('Método no permitido', null, 405);
        }

        $input = $_POST;
        if (empty($input)) {
            $input = Security::getJsonInput();
        }

        $date = $input['date'] ?? '';

        // Validar formato de fecha
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) {
            Response::error('Formato de fecha inválido', 'date');
        }

        // Verificar que es fecha futura
        $today = new DateTime('today');
        if ($d <= $today) {
            Response::success('La fecha debe ser futura', ['slots' => []]);
        }

        // Verificar que es día laborable según horarios configurados
        $dayOfWeek = (int)$d->format('w'); // 0=Domingo, 1=Lunes, etc.
        $schedule = $this->getScheduleForDay($dayOfWeek);
        
        if (!$schedule) {
            Response::success('Este día no está disponible para citas', ['slots' => []]);
        }

        // Verificar que no es fecha bloqueada
        if ($this->isDateBlocked($date)) {
            Response::success('Esta fecha no está disponible', ['slots' => []]);
        }

        // Generar todos los slots del día basados en el horario configurado
        $allSlots = $this->generateTimeSlotsFromSchedule($schedule);

        // Obtener slots ocupados
        $occupiedSlots = $this->getOccupiedSlots($date);

        // Filtrar slots disponibles
        $availableSlots = array_values(array_diff($allSlots, $occupiedSlots));

        Response::success('Slots disponibles', [
            'date' => $date,
            'slots' => $availableSlots,
            'total_available' => count($availableSlots)
        ]);
    }

    /**
     * Obtener el horario configurado para un día de la semana
     */
    private function getScheduleForDay(int $dayOfWeek): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM schedules 
            WHERE day_of_week = ? AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$dayOfWeek]);
        $schedule = $stmt->fetch();
        
        return $schedule ?: null;
    }

    /**
     * Verificar si una fecha está bloqueada
     */
    private function isDateBlocked(string $date): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM blocked_dates WHERE blocked_date = ?");
        $stmt->execute([$date]);
        $result = $stmt->fetch();
        
        return $result['count'] > 0;
    }

    /**
     * Generar slots de tiempo basados en un horario configurado
     */
    private function generateTimeSlotsFromSchedule(array $schedule): array
    {
        $slots = [];
        $start = $this->timeToMinutes($schedule['start_time']);
        $end = $this->timeToMinutes($schedule['end_time']);
        $duration = (int)$schedule['slot_duration'];

        for ($time = $start; $time < $end; $time += $duration) {
            $hours = floor($time / 60);
            $minutes = $time % 60;
            $slots[] = sprintf('%02d:%02d', $hours, $minutes);
        }

        return $slots;
    }

    /**
     * Generar todos los slots de tiempo para un día (fallback a config)
     */
    private function generateTimeSlots(): array
    {
        $slots = [];
        $start = $this->timeToMinutes(APPOINTMENT_START);
        $end = $this->timeToMinutes(APPOINTMENT_END);
        $duration = APPOINTMENT_SLOT;

        for ($time = $start; $time < $end; $time += $duration) {
            $hours = floor($time / 60);
            $minutes = $time % 60;
            $slots[] = sprintf('%02d:%02d', $hours, $minutes);
        }

        return $slots;
    }

    /**
     * Obtener días disponibles de la semana
     */
    public function getAvailableDays(): array
    {
        $stmt = $this->pdo->query("SELECT DISTINCT day_of_week FROM schedules WHERE is_active = 1 ORDER BY day_of_week");
        $days = [];
        while ($row = $stmt->fetch()) {
            $days[] = (int)$row['day_of_week'];
        }
        return $days;
    }

    /**
     * Obtener slots ocupados para una fecha
     */
    private function getOccupiedSlots(string $date): array
    {
        $stmt = $this->pdo->prepare("
            SELECT appointment_time 
            FROM appointments 
            WHERE appointment_date = ? AND status NOT IN ('cancelled')
        ");
        $stmt->execute([$date]);
        
        $occupied = [];
        while ($row = $stmt->fetch()) {
            $occupied[] = $row['appointment_time'];
        }
        
        return $occupied;
    }

    /**
     * Verificar si un slot específico está disponible
     */
    private function isSlotAvailable(string $date, string $time): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count 
            FROM appointments 
            WHERE appointment_date = ? AND appointment_time = ? AND status NOT IN ('cancelled')
        ");
        $stmt->execute([$date, $time]);
        $result = $stmt->fetch();
        
        return $result['count'] == 0;
    }

    /**
     * Convertir hora HH:MM a minutos
     */
    private function timeToMinutes(string $time): int
    {
        $parts = explode(':', $time);
        return (int)$parts[0] * 60 + (int)$parts[1];
    }

    /**
     * Obtener todas las citas (para panel admin)
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

        // Filtro por fecha de cita
        if (!empty($filters['date_from'])) {
            $where[] = "appointment_date >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = "appointment_date <= ?";
            $params[] = $filters['date_to'];
        }

        // Filtro por búsqueda
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $where[] = '(name LIKE ? OR email LIKE ?)';
            $params[] = $search;
            $params[] = $search;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Paginación
        $page = max(1, (int)($filters['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Contar total
        $countSql = "SELECT COUNT(*) as total FROM appointments {$whereClause}";
        $stmt = $this->pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        // Obtener citas
        $sql = "SELECT * FROM appointments {$whereClause} ORDER BY appointment_date ASC, appointment_time ASC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $appointments = $stmt->fetchAll();

        return [
            'appointments' => $appointments,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Obtener una cita por ID
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM appointments WHERE id = ?");
        $stmt->execute([$id]);
        $appointment = $stmt->fetch();
        return $appointment ?: null;
    }

    /**
     * Actualizar status de una cita
     */
    public function updateStatus(int $id, string $status, int $adminId): bool
    {
        $validStatuses = ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'];
        
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        // Obtener la cita actual
        $appointment = $this->getById($id);
        if (!$appointment) {
            return false;
        }

        $oldStatus = $appointment['status'];

        try {
            $nowExpr = Database::currentTimestampExpression();
            $stmt = $this->pdo->prepare("
                UPDATE appointments 
                SET status = ?, updated_at = {$nowExpr}
                WHERE id = ?
            ");
            $stmt->execute([$status, $id]);

            // Registrar en log de admin
            $this->logAdminAction($adminId, 'status_change', 'appointment', $id, "Status: {$oldStatus} → {$status}");

            // Enviar email según el nuevo status
            $appointment['status'] = $status;
            
            if ($status === 'confirmed' && $oldStatus !== 'confirmed') {
                Mailer::sendAppointmentConfirmed($appointment);
                
                // Marcar que se envió confirmación
                $stmt = $this->pdo->prepare("UPDATE appointments SET confirmation_sent = 1 WHERE id = ?");
                $stmt->execute([$id]);
            }
            
            if ($status === 'cancelled' && $oldStatus !== 'cancelled') {
                Mailer::sendAppointmentCancelled($appointment);
            }

            return true;
        } catch (PDOException $e) {
            error_log('Error actualizando status de cita: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar notas de una cita
     */
    public function updateNotes(int $id, string $notes, int $adminId): bool
    {
        try {
            $notes = Security::sanitizeString($notes, 1000);
            $nowExpr = Database::currentTimestampExpression();
            
            $stmt = $this->pdo->prepare("
                UPDATE appointments 
                SET admin_notes = ?, updated_at = {$nowExpr}
                WHERE id = ?
            ");
            $stmt->execute([$notes, $id]);

            // Registrar en log de admin
            $this->logAdminAction($adminId, 'notes_update', 'appointment', $id, 'Notas actualizadas');

            return true;
        } catch (PDOException $e) {
            error_log('Error actualizando notas: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de citas
     */
    public function getStats(): array
    {
        // Total de citas
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM appointments");
        $total = $stmt->fetch()['total'];

        // Pendientes
        $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'");
        $pending = $stmt->fetch()['total'];

        // Confirmadas (futuras)
        $todayExpr = Database::currentDateExpression();
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as total FROM appointments 
            WHERE status = 'confirmed' AND appointment_date >= {$todayExpr}
        ");
        $confirmed = $stmt->fetch()['total'];

        // Esta semana
        $nextWeekExpr = Database::dateDaysAheadExpression(7);
        $stmt = $this->pdo->query("
            SELECT COUNT(*) as total FROM appointments 
            WHERE appointment_date BETWEEN {$todayExpr} AND {$nextWeekExpr}
        ");
        $thisWeek = $stmt->fetch()['total'];

        // Próximas 3 citas
        $stmt = $this->pdo->query("
            SELECT * FROM appointments 
            WHERE status IN ('pending', 'confirmed') 
            AND appointment_date >= {$todayExpr}
            ORDER BY appointment_date ASC, appointment_time ASC
            LIMIT 3
        ");
        $upcoming = $stmt->fetchAll();

        // Por status
        $stmt = $this->pdo->query("SELECT status, COUNT(*) as count FROM appointments GROUP BY status");
        $byStatus = [];
        while ($row = $stmt->fetch()) {
            $byStatus[$row['status']] = $row['count'];
        }

        return [
            'total' => $total,
            'pending' => $pending,
            'confirmed' => $confirmed,
            'this_week' => $thisWeek,
            'upcoming' => $upcoming,
            'by_status' => $byStatus
        ];
    }

    /**
     * Obtener citas por mes (para vista de calendario)
     */
    public function getByMonth(int $year, int $month): array
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $stmt = $this->pdo->prepare("
            SELECT * FROM appointments 
            WHERE appointment_date BETWEEN ? AND ?
            ORDER BY appointment_date ASC, appointment_time ASC
        ");
        $stmt->execute([$startDate, $endDate]);
        
        return $stmt->fetchAll();
    }

    /**
     * Obtener conteo de citas por día (para calendario)
     */
    public function getCountByDay(int $year, int $month): array
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $stmt = $this->pdo->prepare("
            SELECT appointment_date, COUNT(*) as count 
            FROM appointments 
            WHERE appointment_date BETWEEN ? AND ? AND status NOT IN ('cancelled')
            GROUP BY appointment_date
        ");
        $stmt->execute([$startDate, $endDate]);
        
        $counts = [];
        while ($row = $stmt->fetch()) {
            $counts[$row['appointment_date']] = $row['count'];
        }
        
        return $counts;
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
