<?php
/**
 * AdminController.php - Controlador del panel de administración
 * 
 * Maneja el dashboard, gestión de leads, citas y todas las
 * operaciones del panel de administración.
 */

// Prevenir acceso directo
if (!defined('APP_ROOT')) {
    die('Acceso directo no permitido');
}

class AdminController
{
    private PDO $pdo;
    private AuthController $auth;
    private LeadController $leadController;
    private AppointmentController $appointmentController;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
        $this->auth = new AuthController();
        $this->leadController = new LeadController();
        $this->appointmentController = new AppointmentController();
    }

    /**
     * Dashboard principal
     */
    public function dashboard(): void
    {
        $this->auth->requireAuth();

        $admin = $this->auth->getCurrentAdmin();
        $leadStats = $this->leadController->getStats();
        $appointmentStats = $this->appointmentController->getStats();

        // Actividad reciente combinada
        $recentActivity = $this->getRecentActivity();

        $this->render('dashboard', [
            'admin' => $admin,
            'leadStats' => $leadStats,
            'appointmentStats' => $appointmentStats,
            'recentActivity' => $recentActivity
        ]);
    }

    /**
     * Vista de leads
     */
    public function leads(): void
    {
        $this->auth->requireAuth();

        $admin = $this->auth->getCurrentAdmin();
        
        $filters = [
            'status' => $_GET['status'] ?? '',
            'search' => $_GET['search'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'page' => $_GET['page'] ?? 1
        ];

        $result = $this->leadController->getAll($filters);

        $this->render('leads', [
            'admin' => $admin,
            'leads' => $result['leads'],
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'per_page' => $result['per_page'],
                'total_pages' => $result['total_pages']
            ],
            'filters' => $filters
        ]);
    }

    /**
     * Vista de citas
     */
    public function appointments(): void
    {
        $this->auth->requireAuth();

        $admin = $this->auth->getCurrentAdmin();
        
        $displayMode = $_GET['display'] ?? 'table'; // table o calendar
        
        $filters = [
            'status' => $_GET['status'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'search' => $_GET['search'] ?? '',
            'page' => $_GET['page'] ?? 1
        ];

        $result = $this->appointmentController->getAll($filters);

        // Para vista de calendario
        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));
        $calendarData = $this->appointmentController->getCountByDay($year, $month);

        $this->render('appointments', [
            'admin' => $admin,
            'appointments' => $result['appointments'],
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'per_page' => $result['per_page'],
                'total_pages' => $result['total_pages']
            ],
            'filters' => $filters,
            'displayMode' => $displayMode,
            'calendarData' => $calendarData,
            'calendarYear' => $year,
            'calendarMonth' => $month
        ]);
    }

    /**
     * Actualizar status de lead (AJAX)
     */
    public function updateLeadStatus(int $id): void
    {
        $this->auth->requireAuth();

        if (!Security::isMethod('POST')) {
            Response::error('Método no permitido', null, 405);
        }

        $input = Security::getJsonInput();
        if (empty($input)) {
            $input = $_POST;
        }

        // Verificar CSRF
        $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            Response::csrfError();
        }

        $status = $input['status'] ?? '';
        $adminId = $this->auth->getAdminId();

        if ($this->leadController->updateStatus($id, $status, $adminId)) {
            Response::success('Status actualizado correctamente');
        } else {
            Response::error('Error al actualizar el status');
        }
    }

    /**
     * Actualizar status de cita (AJAX)
     */
    public function updateAppointmentStatus(int $id): void
    {
        $this->auth->requireAuth();

        if (!Security::isMethod('POST')) {
            Response::error('Método no permitido', null, 405);
        }

        $input = Security::getJsonInput();
        if (empty($input)) {
            $input = $_POST;
        }

        // Verificar CSRF
        $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            Response::csrfError();
        }

        $status = $input['status'] ?? '';
        $adminId = $this->auth->getAdminId();

        if ($this->appointmentController->updateStatus($id, $status, $adminId)) {
            $message = 'Status actualizado correctamente';
            if ($status === 'confirmed') {
                $message .= '. Se ha enviado email de confirmación al cliente.';
            } elseif ($status === 'cancelled') {
                $message .= '. Se ha enviado email de cancelación al cliente.';
            }
            Response::success($message);
        } else {
            Response::error('Error al actualizar el status');
        }
    }

    /**
     * Actualizar notas de cita (AJAX)
     */
    public function updateAppointmentNotes(int $id): void
    {
        $this->auth->requireAuth();

        if (!Security::isMethod('POST')) {
            Response::error('Método no permitido', null, 405);
        }

        $input = Security::getJsonInput();
        if (empty($input)) {
            $input = $_POST;
        }

        // Verificar CSRF
        $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            Response::csrfError();
        }

        $notes = $input['notes'] ?? '';
        $adminId = $this->auth->getAdminId();

        if ($this->appointmentController->updateNotes($id, $notes, $adminId)) {
            Response::success('Notas actualizadas correctamente');
        } else {
            Response::error('Error al actualizar las notas');
        }
    }

    /**
     * Exportar leads a CSV
     */
    public function exportLeadsCsv(): void
    {
        $this->auth->requireAuth();

        $filters = [
            'status' => $_GET['status'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? ''
        ];

        $csv = $this->leadController->exportToCsv($filters);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="leads_' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF"; // BOM para Excel
        echo $csv;
        exit;
    }

    /**
     * Obtener detalle de lead (AJAX)
     */
    public function getLeadDetail(int $id): void
    {
        $this->auth->requireAuth();

        $lead = $this->leadController->getById($id);

        if (!$lead) {
            Response::notFound('Lead no encontrado');
        }

        Response::success('Lead encontrado', ['lead' => $lead]);
    }

    /**
     * Obtener detalle de cita (AJAX)
     */
    public function getAppointmentDetail(int $id): void
    {
        $this->auth->requireAuth();

        $appointment = $this->appointmentController->getById($id);

        if (!$appointment) {
            Response::notFound('Cita no encontrada');
        }

        Response::success('Cita encontrada', ['appointment' => $appointment]);
    }

    /**
     * Obtener citas de un día específico (para calendario)
     */
    public function getAppointmentsByDay(): void
    {
        $this->auth->requireAuth();

        $date = $_GET['date'] ?? '';

        if (empty($date)) {
            Response::error('Fecha requerida');
        }

        $result = $this->appointmentController->getAll([
            'date_from' => $date,
            'date_to' => $date,
            'page' => 1
        ]);

        Response::success('Citas del día', [
            'date' => $date,
            'appointments' => $result['appointments']
        ]);
    }

    /**
     * Vista de configuración (servicios y horarios)
     */
    public function settings(): void
    {
        $this->auth->requireAuth();

        $admin = $this->auth->getCurrentAdmin();
        
        // Obtener servicios
        $services = $this->getServices();
        
        // Obtener horarios
        $schedules = $this->getSchedules();
        
        // Obtener fechas bloqueadas
        $blockedDates = $this->getBlockedDates();

        // Obtener contenido editable de landing
        $testimonials = $this->getTestimonials();
        $faqItems = $this->getFaqItems();

        // Ajustes de marca/negocio
        $businessSettings = BusinessConfig::all();

        $this->render('settings', [
            'admin' => $admin,
            'services' => $services,
            'schedules' => $schedules,
            'blockedDates' => $blockedDates,
            'testimonials' => $testimonials,
            'faqItems' => $faqItems,
            'businessSettings' => $businessSettings
        ]);
    }

    /**
     * Obtener todos los servicios
     */
    public function getServices(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM services ORDER BY sort_order ASC, id ASC");
        return $stmt->fetchAll();
    }

    /**
     * Obtener servicios activos (para API pública)
     */
    public function getActiveServices(): array
    {
        $stmt = $this->pdo->query("SELECT id, name, COALESCE(short_desc, description) as description, duration, icon FROM services WHERE is_active = 1 ORDER BY sort_order ASC");
        return $stmt->fetchAll();
    }

    /**
     * Guardar servicio (crear o actualizar)
     */
    public function saveService(): void
    {
        $this->auth->requireAuth();

        if (!Security::isMethod('POST')) {
            Response::error('Método no permitido', null, 405);
        }

        $input = Security::getJsonInput();
        if (empty($input)) {
            $input = $_POST;
        }

        // Verificar CSRF
        $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            Response::csrfError();
        }

        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $name = Security::sanitizeString($input['name'] ?? '', 150);
        $description = Security::sanitizeString($input['description'] ?? '', 1000);
        $shortDesc = Security::sanitizeString($input['short_desc'] ?? '', 500);
        $fullDesc = Security::sanitizeString($input['full_desc'] ?? '', 3000);
        $icon = Security::sanitizeString($input['icon'] ?? 'briefcase', 80);
        $duration = (int)($input['duration'] ?? 60);
        $price = (float)($input['price'] ?? 0);
        $isActive = isset($input['is_active']) ? 1 : 0;
        $sortOrder = (int)($input['sort_order'] ?? 0);

        if ($shortDesc === '') {
            $shortDesc = $description;
        }
        if ($fullDesc === '') {
            $fullDesc = $shortDesc;
        }

        // Validaciones
        if (empty($name)) {
            Response::error('El nombre del servicio es obligatorio');
        }

        if ($duration < 15 || $duration > 480) {
            Response::error('La duración debe estar entre 15 y 480 minutos');
        }

        try {
            if ($id > 0) {
                // Actualizar
                $stmt = $this->pdo->prepare("
                    UPDATE services 
                    SET name = ?, description = ?, short_desc = ?, full_desc = ?, icon = ?, duration = ?, price = ?, is_active = ?, sort_order = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $description, $shortDesc, $fullDesc, $icon, $duration, $price, $isActive, $sortOrder, $id]);
                Response::success('Servicio actualizado correctamente', ['id' => $id]);
            } else {
                // Crear
                $stmt = $this->pdo->prepare("
                    INSERT INTO services (name, description, short_desc, full_desc, icon, duration, price, is_active, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $description, $shortDesc, $fullDesc, $icon, $duration, $price, $isActive, $sortOrder]);
                $newId = $this->pdo->lastInsertId();
                Response::success('Servicio creado correctamente', ['id' => $newId]);
            }
        } catch (PDOException $e) {
            error_log('Error guardando servicio: ' . $e->getMessage());
            Response::error('Error al guardar el servicio');
        }
    }

    /**
     * Eliminar servicio
     */
    public function deleteService(int $id): void
    {
        $this->auth->requireAuth();

        if (!Security::isMethod('POST')) {
            Response::error('Método no permitido', null, 405);
        }

        $input = Security::getJsonInput();
        if (empty($input)) {
            $input = $_POST;
        }

        // Verificar CSRF
        $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            Response::csrfError();
        }

        try {
            $stmt = $this->pdo->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                Response::success('Servicio eliminado correctamente');
            } else {
                Response::error('Servicio no encontrado');
            }
        } catch (PDOException $e) {
            error_log('Error eliminando servicio: ' . $e->getMessage());
            Response::error('Error al eliminar el servicio');
        }
    }

    /**
     * Obtener todos los horarios
     */
    public function getSchedules(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM schedules ORDER BY day_of_week ASC, start_time ASC");
        return $stmt->fetchAll();
    }

    /**
     * Guardar horario
     */
    public function saveSchedule(): void
    {
        $this->auth->requireAuth();

        if (!Security::isMethod('POST')) {
            Response::error('Método no permitido', null, 405);
        }

        $input = Security::getJsonInput();
        if (empty($input)) {
            $input = $_POST;
        }

        // Verificar CSRF
        $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            Response::csrfError();
        }

        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $dayOfWeek = (int)($input['day_of_week'] ?? 0);
        $startTime = Security::sanitizeString($input['start_time'] ?? '', 5);
        $endTime = Security::sanitizeString($input['end_time'] ?? '', 5);
        $slotDuration = (int)($input['slot_duration'] ?? 60);
        $isActive = isset($input['is_active']) ? 1 : 0;

        // Validaciones
        if ($dayOfWeek < 0 || $dayOfWeek > 6) {
            Response::error('Día de la semana inválido');
        }

        if (empty($startTime) || empty($endTime)) {
            Response::error('Las horas de inicio y fin son obligatorias');
        }

        if ($startTime >= $endTime) {
            Response::error('La hora de inicio debe ser anterior a la hora de fin');
        }

        if ($slotDuration < 15 || $slotDuration > 240) {
            Response::error('La duración del slot debe estar entre 15 y 240 minutos');
        }

        try {
            if ($id > 0) {
                // Actualizar
                $stmt = $this->pdo->prepare("
                    UPDATE schedules 
                    SET day_of_week = ?, start_time = ?, end_time = ?, slot_duration = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([$dayOfWeek, $startTime, $endTime, $slotDuration, $isActive, $id]);
                Response::success('Horario actualizado correctamente', ['id' => $id]);
            } else {
                // Crear
                $stmt = $this->pdo->prepare("
                    INSERT INTO schedules (day_of_week, start_time, end_time, slot_duration, is_active)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$dayOfWeek, $startTime, $endTime, $slotDuration, $isActive]);
                $newId = $this->pdo->lastInsertId();
                Response::success('Horario creado correctamente', ['id' => $newId]);
            }
        } catch (PDOException $e) {
            error_log('Error guardando horario: ' . $e->getMessage());
            Response::error('Error al guardar el horario');
        }
    }

    /**
     * Eliminar horario
     */
    public function deleteSchedule(int $id): void
    {
        $this->auth->requireAuth();

        if (!Security::isMethod('POST')) {
            Response::error('Método no permitido', null, 405);
        }

        $input = Security::getJsonInput();
        if (empty($input)) {
            $input = $_POST;
        }

        // Verificar CSRF
        $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            Response::csrfError();
        }

        try {
            $stmt = $this->pdo->prepare("DELETE FROM schedules WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                Response::success('Horario eliminado correctamente');
            } else {
                Response::error('Horario no encontrado');
            }
        } catch (PDOException $e) {
            error_log('Error eliminando horario: ' . $e->getMessage());
            Response::error('Error al eliminar el horario');
        }
    }

    /**
     * Obtener fechas bloqueadas
     */
    public function getBlockedDates(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM blocked_dates ORDER BY blocked_date ASC");
        return $stmt->fetchAll();
    }

    /**
     * Agregar fecha bloqueada
     */
    public function addBlockedDate(): void
    {
        $this->auth->requireAuth();

        if (!Security::isMethod('POST')) {
            Response::error('Método no permitido', null, 405);
        }

        $input = Security::getJsonInput();
        if (empty($input)) {
            $input = $_POST;
        }

        // Verificar CSRF
        $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            Response::csrfError();
        }

        $blockedDate = Security::sanitizeString($input['blocked_date'] ?? '', 10);
        $reason = Security::sanitizeString($input['reason'] ?? '', 255);

        if (empty($blockedDate)) {
            Response::error('La fecha es obligatoria');
        }

        // Validar formato de fecha
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $blockedDate)) {
            Response::error('Formato de fecha inválido');
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO blocked_dates (blocked_date, reason) VALUES (?, ?)");
            $stmt->execute([$blockedDate, $reason]);
            $newId = $this->pdo->lastInsertId();
            Response::success('Fecha bloqueada agregada', ['id' => $newId]);
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'UNIQUE') !== false) {
                Response::error('Esta fecha ya está bloqueada');
            }
            error_log('Error agregando fecha bloqueada: ' . $e->getMessage());
            Response::error('Error al agregar la fecha bloqueada');
        }
    }

    /**
     * Eliminar fecha bloqueada
     */
    public function deleteBlockedDate(int $id): void
    {
        $this->auth->requireAuth();

        if (!Security::isMethod('POST')) {
            Response::error('Método no permitido', null, 405);
        }

        $input = Security::getJsonInput();
        if (empty($input)) {
            $input = $_POST;
        }

        // Verificar CSRF
        $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            Response::csrfError();
        }

        try {
            $stmt = $this->pdo->prepare("DELETE FROM blocked_dates WHERE id = ?");
            $stmt->execute([$id]);
            
            if ($stmt->rowCount() > 0) {
                Response::success('Fecha bloqueada eliminada');
            } else {
                Response::error('Fecha no encontrada');
            }
        } catch (PDOException $e) {
            error_log('Error eliminando fecha bloqueada: ' . $e->getMessage());
            Response::error('Error al eliminar la fecha bloqueada');
        }
    }

    /**
     * Obtener testimonios
     */
    public function getTestimonials(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM testimonials ORDER BY sort_order ASC, id ASC');
        return $stmt->fetchAll();
    }

    /**
     * Guardar testimonio (crear o actualizar)
     */
    public function saveTestimonial(): void
    {
        $this->auth->requireAuth();

        if (!Security::isMethod('POST')) {
            Response::error('Método no permitido', null, 405);
        }

        $input = Security::getJsonInput();
        if (empty($input)) {
            $input = $_POST;
        }

        $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            Response::csrfError();
        }

        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $name = Security::sanitizeString($input['name'] ?? '', 120);
        $company = Security::sanitizeString($input['company'] ?? '', 150);
        $text = Security::sanitizeString($input['text'] ?? '', 1000);
        $initials = strtoupper(Security::sanitizeString($input['initials'] ?? '', 4));
        $color = Security::sanitizeString($input['color'] ?? '', 20);
        $sortOrder = (int)($input['sort_order'] ?? 0);
        $isActive = isset($input['is_active']) ? 1 : 0;

        if ($name === '' || $text === '') {
            Response::error('Nombre y testimonio son obligatorios');
        }

        if ($initials === '') {
            $initials = strtoupper(substr($name, 0, 2));
        }

        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '#4A90A4';
        }

        try {
            if ($id > 0) {
                $stmt = $this->pdo->prepare('UPDATE testimonials SET name = ?, company = ?, text = ?, initials = ?, color = ?, sort_order = ?, is_active = ? WHERE id = ?');
                $stmt->execute([$name, $company, $text, $initials, $color, $sortOrder, $isActive, $id]);
                Response::success('Testimonio actualizado correctamente', ['id' => $id]);
            } else {
                $stmt = $this->pdo->prepare('INSERT INTO testimonials (name, company, text, initials, color, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$name, $company, $text, $initials, $color, $sortOrder, $isActive]);
                Response::success('Testimonio creado correctamente', ['id' => $this->pdo->lastInsertId()]);
            }
        } catch (PDOException $e) {
            error_log('Error guardando testimonio: ' . $e->getMessage());
            Response::error('Error al guardar el testimonio');
        }
    }

    /**
     * Eliminar testimonio
     */
    public function deleteTestimonial(int $id): void
    {
        $this->auth->requireAuth();

        if (!Security::isMethod('POST')) {
            Response::error('Método no permitido', null, 405);
        }

        $input = Security::getJsonInput();
        if (empty($input)) {
            $input = $_POST;
        }

        $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            Response::csrfError();
        }

        try {
            $stmt = $this->pdo->prepare('DELETE FROM testimonials WHERE id = ?');
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                Response::success('Testimonio eliminado correctamente');
            }

            Response::error('Testimonio no encontrado');
        } catch (PDOException $e) {
            error_log('Error eliminando testimonio: ' . $e->getMessage());
            Response::error('Error al eliminar el testimonio');
        }
    }

    /**
     * Obtener items FAQ
     */
    public function getFaqItems(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM faq_items ORDER BY sort_order ASC, id ASC');
        return $stmt->fetchAll();
    }

    /**
     * Guardar item FAQ (crear o actualizar)
     */
    public function saveFaqItem(): void
    {
        $this->auth->requireAuth();

        if (!Security::isMethod('POST')) {
            Response::error('Método no permitido', null, 405);
        }

        $input = Security::getJsonInput();
        if (empty($input)) {
            $input = $_POST;
        }

        $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            Response::csrfError();
        }

        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $question = Security::sanitizeString($input['question'] ?? '', 500);
        $answer = Security::sanitizeString($input['answer'] ?? '', 2000);
        $sortOrder = (int)($input['sort_order'] ?? 0);
        $isActive = isset($input['is_active']) ? 1 : 0;

        if ($question === '' || $answer === '') {
            Response::error('Pregunta y respuesta son obligatorias');
        }

        try {
            if ($id > 0) {
                $stmt = $this->pdo->prepare('UPDATE faq_items SET question = ?, answer = ?, sort_order = ?, is_active = ? WHERE id = ?');
                $stmt->execute([$question, $answer, $sortOrder, $isActive, $id]);
                Response::success('FAQ actualizado correctamente', ['id' => $id]);
            } else {
                $stmt = $this->pdo->prepare('INSERT INTO faq_items (question, answer, sort_order, is_active) VALUES (?, ?, ?, ?)');
                $stmt->execute([$question, $answer, $sortOrder, $isActive]);
                Response::success('FAQ creado correctamente', ['id' => $this->pdo->lastInsertId()]);
            }
        } catch (PDOException $e) {
            error_log('Error guardando FAQ: ' . $e->getMessage());
            Response::error('Error al guardar el FAQ');
        }
    }

    /**
     * Eliminar item FAQ
     */
    public function deleteFaqItem(int $id): void
    {
        $this->auth->requireAuth();

        if (!Security::isMethod('POST')) {
            Response::error('Método no permitido', null, 405);
        }

        $input = Security::getJsonInput();
        if (empty($input)) {
            $input = $_POST;
        }

        $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            Response::csrfError();
        }

        try {
            $stmt = $this->pdo->prepare('DELETE FROM faq_items WHERE id = ?');
            $stmt->execute([$id]);

            if ($stmt->rowCount() > 0) {
                Response::success('FAQ eliminado correctamente');
            }

            Response::error('FAQ no encontrado');
        } catch (PDOException $e) {
            error_log('Error eliminando FAQ: ' . $e->getMessage());
            Response::error('Error al eliminar el FAQ');
        }
    }

    /**
     * Guardar ajustes de marca/negocio
     */
    public function saveBusinessSettings(): void
    {
        $this->auth->requireAuth();

        if (!Security::isMethod('POST')) {
            Response::error('Método no permitido', null, 405);
        }

        $input = Security::getJsonInput();
        if (empty($input)) {
            $input = $_POST;
        }

        $csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::validateCsrfToken($csrfToken)) {
            Response::csrfError();
        }

        $payload = [
            'business_name' => Security::sanitizeString($input['business_name'] ?? '', 150),
            'business_tagline' => Security::sanitizeString($input['business_tagline'] ?? '', 200),
            'business_description' => Security::sanitizeString($input['business_description'] ?? '', 1000),
            'business_phone' => Security::sanitizePhone($input['business_phone'] ?? ''),
            'business_whatsapp' => preg_replace('/[^\d]/', '', (string)($input['business_whatsapp'] ?? '')),
            'business_email' => Security::sanitizeEmail($input['business_email'] ?? ''),
            'business_address' => Security::sanitizeString($input['business_address'] ?? '', 255),
            'business_hours' => Security::sanitizeString($input['business_hours'] ?? '', 255),
            'site_url' => Security::sanitizeString($input['site_url'] ?? '', 255),
            'seo_title' => Security::sanitizeString($input['seo_title'] ?? '', 255),
            'seo_description' => Security::sanitizeString($input['seo_description'] ?? '', 400),
            'seo_keywords' => Security::sanitizeString($input['seo_keywords'] ?? '', 400),
            'color_primary' => Security::sanitizeString($input['color_primary'] ?? '', 20),
            'color_accent' => Security::sanitizeString($input['color_accent'] ?? '', 20),
            'color_bg' => Security::sanitizeString($input['color_bg'] ?? '', 20),
            'social_instagram' => Security::sanitizeString($input['social_instagram'] ?? '', 255),
            'social_facebook' => Security::sanitizeString($input['social_facebook'] ?? '', 255),
            'social_linkedin' => Security::sanitizeString($input['social_linkedin'] ?? '', 255),
            'whatsapp_message' => Security::sanitizeString($input['whatsapp_message'] ?? '', 300),
        ];

        if ($payload['business_name'] === '' || !Security::isValidEmail($payload['business_email'])) {
            Response::error('Nombre y email del negocio son obligatorios y deben ser válidos');
        }

        foreach (['color_primary', 'color_accent', 'color_bg'] as $colorKey) {
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $payload[$colorKey])) {
                Response::error('Los colores deben usar formato hexadecimal, por ejemplo #1a3a5c');
            }
        }

        try {
            BusinessConfig::save($payload);
            Response::success('Ajustes de marca actualizados correctamente');
        } catch (Throwable $e) {
            error_log('Error guardando ajustes de negocio: ' . $e->getMessage());
            Response::error('Error al guardar los ajustes');
        }
    }

    /**
     * Obtener actividad reciente combinada
     */
    private function getRecentActivity(): array
    {
        // Últimos 10 leads
        $stmt = $this->pdo->query("
            SELECT id, name, 'lead' as type, status, created_at 
            FROM leads 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $leads = $stmt->fetchAll();

        // Últimas 10 citas
        $stmt = $this->pdo->query("
            SELECT id, name, 'appointment' as type, status, created_at 
            FROM appointments 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $appointments = $stmt->fetchAll();

        // Combinar y ordenar
        $activity = array_merge($leads, $appointments);
        usort($activity, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        // Retornar solo los primeros 10
        return array_slice($activity, 0, 10);
    }

    /**
     * Renderizar vista del admin
     */
    private function render(string $viewName, array $data = []): void
    {
        // Pasar el nombre de la vista
        $view = $viewName;
        
        // Extraer variables para la vista
        extract($data);

        // CSRF token para formularios
        $csrfToken = Security::getCsrfToken();
        
        // Obtener estadísticas para el sidebar si no están ya disponibles
        if (!isset($leadStats)) {
            $leadStats = $this->leadController->getStats();
        }
        if (!isset($appointmentStats)) {
            $appointmentStats = $this->appointmentController->getStats();
        }

        // Incluir layout del admin
        include APP_ROOT . '/admin/index.php';
        exit;
    }
}
