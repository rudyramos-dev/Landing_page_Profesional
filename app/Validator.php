<?php
/**
 * Validator.php - Validación centralizada de datos
 * 
 * Proporciona métodos de validación server-side para todos los formularios.
 * Retorna errores específicos por campo para feedback al usuario.
 */

// Prevenir acceso directo
if (!defined('APP_ROOT')) {
    die('Acceso directo no permitido');
}

class Validator
{
    private array $errors = [];
    private array $data = [];
    private ?PDO $pdo = null;

    public function __construct()
    {
        try {
            $this->pdo = Database::getInstance();
        } catch (Throwable $e) {
            $this->pdo = null;
        }
    }

    /**
     * Validar datos de un lead (formulario de contacto)
     */
    public function validateLead(array $input): bool
    {
        $this->errors = [];
        $this->data = [];

        // Nombre (requerido, 2-100 caracteres)
        $name = Security::sanitizeString($input['name'] ?? '', 100);
        if (empty($name)) {
            $this->errors['name'] = 'El nombre es obligatorio';
        } elseif (mb_strlen($name) < 2) {
            $this->errors['name'] = 'El nombre debe tener al menos 2 caracteres';
        }
        $this->data['name'] = $name;

        // Email (requerido, formato válido)
        $email = Security::sanitizeEmail($input['email'] ?? '');
        if (empty($email)) {
            $this->errors['email'] = 'El email es obligatorio';
        } elseif (!Security::isValidEmail($email)) {
            $this->errors['email'] = 'El formato del email no es válido';
        } elseif (mb_strlen($email) > 255) {
            $this->errors['email'] = 'El email es demasiado largo';
        }
        $this->data['email'] = $email;

        // Teléfono (opcional, máximo 20 caracteres)
        $phone = Security::sanitizePhone($input['phone'] ?? '');
        $this->data['phone'] = $phone;

        // Servicio de interés (opcional)
        $service = Security::sanitizeString($input['service_interest'] ?? '', 100);
        $this->data['service_interest'] = $service;

        // Mensaje (opcional, máximo 1000 caracteres)
        $message = Security::sanitizeString($input['message'] ?? '', 1000);
        $this->data['message'] = $message;

        return empty($this->errors);
    }

    /**
     * Validar datos de una cita
     */
    public function validateAppointment(array $input): bool
    {
        $this->errors = [];
        $this->data = [];

        // Nombre (requerido)
        $name = Security::sanitizeString($input['name'] ?? '', 100);
        if (empty($name)) {
            $this->errors['name'] = 'El nombre es obligatorio';
        } elseif (mb_strlen($name) < 2) {
            $this->errors['name'] = 'El nombre debe tener al menos 2 caracteres';
        }
        $this->data['name'] = $name;

        // Email (requerido)
        $email = Security::sanitizeEmail($input['email'] ?? '');
        if (empty($email)) {
            $this->errors['email'] = 'El email es obligatorio';
        } elseif (!Security::isValidEmail($email)) {
            $this->errors['email'] = 'El formato del email no es válido';
        }
        $this->data['email'] = $email;

        // Teléfono (requerido para citas)
        $phone = Security::sanitizePhone($input['phone'] ?? '');
        if (empty($phone)) {
            $this->errors['phone'] = 'El teléfono es obligatorio para agendar una cita';
        } elseif (strlen($phone) < 10) {
            $this->errors['phone'] = 'El teléfono debe tener al menos 10 dígitos';
        }
        $this->data['phone'] = $phone;

        // Fecha de la cita (requerido, formato YYYY-MM-DD)
        $date = $input['appointment_date'] ?? '';
        if (empty($date)) {
            $this->errors['appointment_date'] = 'La fecha es obligatoria';
        } elseif (!$this->isValidDate($date)) {
            $this->errors['appointment_date'] = 'El formato de fecha no es válido';
        } elseif (!$this->isFutureDate($date)) {
            $this->errors['appointment_date'] = 'La fecha debe ser futura';
        } elseif (!$this->isWorkingDay($date)) {
            $this->errors['appointment_date'] = 'La fecha seleccionada no está disponible';
        } elseif ($this->isBlockedDate($date)) {
            $this->errors['appointment_date'] = 'Esta fecha no está disponible';
        }
        $this->data['appointment_date'] = $date;

        // Hora de la cita (requerido, formato HH:MM)
        $time = $input['appointment_time'] ?? '';
        if (empty($time)) {
            $this->errors['appointment_time'] = 'La hora es obligatoria';
        } elseif (!$this->isValidTime($time)) {
            $this->errors['appointment_time'] = 'El formato de hora no es válido';
        } elseif (!$this->isValidSlot($time)) {
            $this->errors['appointment_time'] = 'El horario seleccionado no está disponible';
        }
        $this->data['appointment_time'] = $time;

        // Servicio de interés (requerido para citas)
        $service = Security::sanitizeString($input['service_interest'] ?? '', 100);
        if (empty($service)) {
            $this->errors['service_interest'] = 'Selecciona un servicio de interés';
        }
        $this->data['service_interest'] = $service;

        // Mensaje (opcional)
        $message = Security::sanitizeString($input['message'] ?? '', 1000);
        $this->data['message'] = $message;

        return empty($this->errors);
    }

    /**
     * Validar credenciales de login
     */
    public function validateLogin(array $input): bool
    {
        $this->errors = [];
        $this->data = [];

        // Username
        $username = Security::sanitizeString($input['username'] ?? '', 50);
        if (empty($username)) {
            $this->errors['username'] = 'El usuario es obligatorio';
        }
        $this->data['username'] = $username;

        // Password (no sanitizar, solo verificar que existe)
        $password = $input['password'] ?? '';
        if (empty($password)) {
            $this->errors['password'] = 'La contraseña es obligatoria';
        }
        $this->data['password'] = $password;

        return empty($this->errors);
    }

    /**
     * Obtener errores de validación
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Obtener primer error
     */
    public function getFirstError(): ?array
    {
        if (empty($this->errors)) {
            return null;
        }

        $field = array_key_first($this->errors);
        return [
            'field' => $field,
            'message' => $this->errors[$field]
        ];
    }

    /**
     * Obtener datos validados y sanitizados
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Verificar si una fecha tiene formato válido (YYYY-MM-DD)
     */
    private function isValidDate(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Verificar si una fecha es futura
     */
    private function isFutureDate(string $date): bool
    {
        $inputDate = new DateTime($date);
        $today = new DateTime('today');
        return $inputDate > $today;
    }

    /**
     * Verificar si una fecha es día laborable
     */
    private function isWorkingDay(string $date): bool
    {
        $dayOfWeek = (int)(new DateTime($date))->format('w');

        if ($this->pdo) {
            try {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM schedules WHERE day_of_week = ? AND is_active = 1");
                $stmt->execute([$dayOfWeek]);
                $result = $stmt->fetch();
                return (int)($result['count'] ?? 0) > 0;
            } catch (Throwable $e) {
                // fallback a config
            }
        }

        return in_array($dayOfWeek, AVAILABLE_DAYS);
    }

    /**
     * Verificar si una fecha está bloqueada
     */
    private function isBlockedDate(string $date): bool
    {
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM blocked_dates WHERE blocked_date = ?");
                $stmt->execute([$date]);
                $result = $stmt->fetch();
                return (int)($result['count'] ?? 0) > 0;
            } catch (Throwable $e) {
                // fallback a config
            }
        }

        return in_array($date, BLOCKED_DATES);
    }

    /**
     * Verificar si una hora tiene formato válido (HH:MM)
     */
    private function isValidTime(string $time): bool
    {
        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $time) === 1;
    }

    /**
     * Verificar si un horario está dentro del rango permitido
     */
    private function isValidSlot(string $time): bool
    {
        $slotMinutes = $this->timeToMinutes($time);

        if ($this->pdo) {
            try {
                $stmt = $this->pdo->query("SELECT start_time, end_time, slot_duration FROM schedules WHERE is_active = 1");
                $schedules = $stmt->fetchAll();

                if (!empty($schedules)) {
                    foreach ($schedules as $schedule) {
                        $startMinutes = $this->timeToMinutes($schedule['start_time']);
                        $endMinutes = $this->timeToMinutes($schedule['end_time']);
                        $slotDuration = (int)$schedule['slot_duration'];

                        if ($slotMinutes >= $startMinutes && $slotMinutes < $endMinutes) {
                            return ($slotMinutes - $startMinutes) % $slotDuration === 0;
                        }
                    }

                    return false;
                }
            } catch (Throwable $e) {
                // fallback a config
            }
        }

        $startMinutes = $this->timeToMinutes(APPOINTMENT_START);
        $endMinutes = $this->timeToMinutes(APPOINTMENT_END);
        if ($slotMinutes < $startMinutes || $slotMinutes >= $endMinutes) {
            return false;
        }

        $slotDuration = APPOINTMENT_SLOT;
        return ($slotMinutes - $startMinutes) % $slotDuration === 0;
    }

    /**
     * Convertir hora HH:MM a minutos desde medianoche
     */
    private function timeToMinutes(string $time): int
    {
        $parts = explode(':', $time);
        return (int)$parts[0] * 60 + (int)$parts[1];
    }
}
