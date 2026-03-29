<?php
/**
 * Mailer.php - Sistema de envío de emails
 * 
 * Maneja el envío de emails con headers correctos.
 * Registra errores en log si el envío falla.
 * El fallo de email nunca bloquea la operación principal.
 */

// Prevenir acceso directo
if (!defined('APP_ROOT')) {
    die('Acceso directo no permitido');
}

class Mailer
{
    /**
     * Enviar email de notificación de nuevo lead al dueño
     */
    public static function sendLeadNotification(array $lead): bool
    {
        $business = BusinessConfig::all();
        $to = $business['notify_email'] ?: NOTIFY_EMAIL;
        $subject = "Nuevo lead — {$lead['name']} — " . ($business['business_name'] ?: BUSINESS_NAME);
        
        $body = self::getEmailTemplate('lead_notification', $lead);
        
        return self::send($to, $subject, $body, 'lead_notification', $lead);
    }

    /**
     * Enviar email de notificación de nueva cita al dueño
     */
    public static function sendAppointmentNotification(array $appointment): bool
    {
        $business = BusinessConfig::all();
        $to = $business['notify_email'] ?: NOTIFY_EMAIL;
        $formattedDate = self::formatDate($appointment['appointment_date']);
        $subject = "Nueva cita solicitada — {$appointment['name']} — {$formattedDate} {$appointment['appointment_time']} — " . ($business['business_name'] ?: BUSINESS_NAME);
        
        $body = self::getEmailTemplate('appointment_notification', $appointment);
        
        return self::send($to, $subject, $body, 'appointment_notification', $appointment);
    }

    /**
     * Enviar email de confirmación de recepción al cliente (lead)
     */
    public static function sendLeadConfirmation(array $lead): bool
    {
        $to = $lead['email'];
        $business = BusinessConfig::all();
        $subject = "Hemos recibido tu mensaje — " . ($business['business_name'] ?: BUSINESS_NAME);
        
        $body = self::getEmailTemplate('lead_confirmation', $lead);
        
        return self::send($to, $subject, $body, 'lead_confirmation', $lead);
    }

    /**
     * Enviar email de confirmación de recepción de cita al cliente
     */
    public static function sendAppointmentReceived(array $appointment): bool
    {
        $to = $appointment['email'];
        $formattedDate = self::formatDate($appointment['appointment_date']);
        $business = BusinessConfig::all();
        $subject = "Tu solicitud de cita ha sido recibida — " . ($business['business_name'] ?: BUSINESS_NAME);
        
        $body = self::getEmailTemplate('appointment_received', $appointment);
        
        return self::send($to, $subject, $body, 'appointment_received', $appointment);
    }

    /**
     * Enviar email de cita confirmada al cliente
     */
    public static function sendAppointmentConfirmed(array $appointment): bool
    {
        $to = $appointment['email'];
        $formattedDate = self::formatDate($appointment['appointment_date']);
        $business = BusinessConfig::all();
        $subject = "¡Tu cita está confirmada! — {$formattedDate} a las {$appointment['appointment_time']} — " . ($business['business_name'] ?: BUSINESS_NAME);
        
        $body = self::getEmailTemplate('appointment_confirmed', $appointment);
        
        return self::send($to, $subject, $body, 'appointment_confirmed', $appointment);
    }

    /**
     * Enviar email de cita cancelada al cliente
     */
    public static function sendAppointmentCancelled(array $appointment): bool
    {
        $to = $appointment['email'];
        $business = BusinessConfig::all();
        $subject = "Tu cita ha sido cancelada — " . ($business['business_name'] ?: BUSINESS_NAME);
        
        $body = self::getEmailTemplate('appointment_cancelled', $appointment);
        
        return self::send($to, $subject, $body, 'appointment_cancelled', $appointment);
    }

    /**
     * Enviar email genérico
     */
    private static function send(string $to, string $subject, string $body, string $type, array $context): bool
    {
        $business = BusinessConfig::all();
        $businessName = $business['business_name'] ?: BUSINESS_NAME;
        $notifyEmail = $business['notify_email'] ?: NOTIFY_EMAIL;
        $businessEmail = $business['business_email'] ?: BUSINESS_EMAIL;

        // Headers del email
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $businessName . ' <' . $notifyEmail . '>',
            'Reply-To: ' . $businessEmail,
            'X-Mailer: PHP/' . phpversion()
        ];

        $headersString = implode("\r\n", $headers);

        // Intentar enviar
        $sent = @mail($to, $subject, $body, $headersString);

        if (!$sent) {
            self::logError($type, $to, $subject, $context);
        }

        return $sent;
    }

    /**
     * Registrar error de envío en el log
     */
    private static function logError(string $type, string $to, string $subject, array $context): void
    {
        $logFile = APP_ROOT . '/logs/mail_errors.log';
        $logDir = dirname($logFile);

        // Crear directorio si no existe
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE);
        
        $logEntry = "[{$timestamp}] [{$type}] To: {$to} | Subject: {$subject} | Context: {$contextJson}\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Obtener plantilla de email
     */
    private static function getEmailTemplate(string $template, array $data): string
    {
        $business = BusinessConfig::all();

        $primaryColor = $business['color_primary'] ?: COLOR_PRIMARY;
        $accentColor = $business['color_accent'] ?: COLOR_ACCENT;
        $businessName = $business['business_name'] ?: BUSINESS_NAME;
        $businessPhone = $business['business_phone'] ?: BUSINESS_PHONE;
        $businessEmail = $business['business_email'] ?: BUSINESS_EMAIL;
        $businessAddress = $business['business_address'] ?: BUSINESS_ADDRESS;
        $businessHours = $business['business_hours'] ?: BUSINESS_HOURS;
        $whatsappNumber = $business['business_whatsapp'] ?: BUSINESS_WHATSAPP;
        $siteUrl = $business['site_url'] ?: SITE_URL;
        $adminUrl = $siteUrl . '/' . ADMIN_PATH;

        // Sanitizar datos para HTML
        $name = htmlspecialchars($data['name'] ?? '', ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($data['email'] ?? '', ENT_QUOTES, 'UTF-8');
        $phone = htmlspecialchars($data['phone'] ?? 'No proporcionado', ENT_QUOTES, 'UTF-8');
        $service = htmlspecialchars($data['service_interest'] ?? 'No especificado', ENT_QUOTES, 'UTF-8');
        $message = nl2br(htmlspecialchars($data['message'] ?? '', ENT_QUOTES, 'UTF-8'));
        $date = isset($data['appointment_date']) ? self::formatDate($data['appointment_date']) : '';
        $time = htmlspecialchars($data['appointment_time'] ?? '', ENT_QUOTES, 'UTF-8');
        $ip = htmlspecialchars($data['ip_address'] ?? '', ENT_QUOTES, 'UTF-8');
        $createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');

        // Estilos base
        $styles = "
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: {$primaryColor}; color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { background-color: #ffffff; padding: 30px; border: 1px solid #e0e0e0; }
            .footer { background-color: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 8px 8px; border: 1px solid #e0e0e0; border-top: none; }
            .info-row { padding: 10px 0; border-bottom: 1px solid #eee; }
            .info-label { font-weight: bold; color: {$primaryColor}; }
            .button { display: inline-block; background-color: {$accentColor}; color: #000; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin: 10px 0; }
            .highlight { background-color: #f8f7f4; padding: 20px; border-radius: 6px; margin: 20px 0; border-left: 4px solid {$accentColor}; }
        ";

        $templates = [
            // Notificación de nuevo lead al dueño
            'lead_notification' => "
                <!DOCTYPE html>
                <html>
                <head><style>{$styles}</style></head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>Nuevo Lead Recibido</h1>
                        </div>
                        <div class='content'>
                            <p>Has recibido una nueva consulta desde tu sitio web:</p>
                            
                            <div class='info-row'><span class='info-label'>Nombre:</span> {$name}</div>
                            <div class='info-row'><span class='info-label'>Email:</span> {$email}</div>
                            <div class='info-row'><span class='info-label'>Teléfono:</span> {$phone}</div>
                            <div class='info-row'><span class='info-label'>Servicio de interés:</span> {$service}</div>
                            <div class='info-row'><span class='info-label'>Mensaje:</span><br>{$message}</div>
                            <div class='info-row'><span class='info-label'>Fecha:</span> {$createdAt}</div>
                            <div class='info-row'><span class='info-label'>IP:</span> {$ip}</div>
                            
                            <div style='text-align: center; margin-top: 20px;'>
                                <a href='{$adminUrl}' class='button'>Ver en Panel Admin</a>
                                <a href='https://wa.me/{$whatsappNumber}?text=Hola%20{$name}' class='button'>Contactar por WhatsApp</a>
                            </div>
                        </div>
                        <div class='footer'>
                            <p>{$businessName} | {$businessAddress}</p>
                        </div>
                    </div>
                </body>
                </html>
            ",

            // Notificación de nueva cita al dueño
            'appointment_notification' => "
                <!DOCTYPE html>
                <html>
                <head><style>{$styles}</style></head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>Nueva Cita Solicitada</h1>
                        </div>
                        <div class='content'>
                            <div class='highlight'>
                                <strong>Fecha solicitada:</strong> {$date}<br>
                                <strong>Hora:</strong> {$time}
                            </div>
                            
                            <div class='info-row'><span class='info-label'>Nombre:</span> {$name}</div>
                            <div class='info-row'><span class='info-label'>Email:</span> {$email}</div>
                            <div class='info-row'><span class='info-label'>Teléfono:</span> {$phone}</div>
                            <div class='info-row'><span class='info-label'>Servicio de interés:</span> {$service}</div>
                            <div class='info-row'><span class='info-label'>Mensaje:</span><br>{$message}</div>
                            <div class='info-row'><span class='info-label'>Fecha de solicitud:</span> {$createdAt}</div>
                            <div class='info-row'><span class='info-label'>IP:</span> {$ip}</div>
                            
                            <div style='text-align: center; margin-top: 20px;'>
                                <a href='{$adminUrl}/appointments' class='button'>Confirmar/Rechazar Cita</a>
                            </div>
                        </div>
                        <div class='footer'>
                            <p>{$businessName} | {$businessAddress}</p>
                        </div>
                    </div>
                </body>
                </html>
            ",

            // Confirmación de lead al cliente
            'lead_confirmation' => "
                <!DOCTYPE html>
                <html>
                <head><style>{$styles}</style></head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>{$businessName}</h1>
                        </div>
                        <div class='content'>
                            <p>Hola <strong>{$name}</strong>,</p>
                            
                            <p>Gracias por contactarnos. Hemos recibido tu mensaje y nos pondremos en contacto contigo en menos de 24 horas.</p>
                            
                            <div class='highlight'>
                                <p><strong>Tu consulta:</strong></p>
                                <p>{$message}</p>
                            </div>
                            
                            <p>Mientras tanto, puedes contactarnos directamente:</p>
                            <ul>
                                <li>Teléfono: {$businessPhone}</li>
                                <li>Email: {$businessEmail}</li>
                                <li>WhatsApp: <a href='https://wa.me/{$whatsappNumber}'>Enviar mensaje</a></li>
                            </ul>
                        </div>
                        <div class='footer'>
                            <p>{$businessName}</p>
                            <p>{$businessAddress} | {$businessHours}</p>
                        </div>
                    </div>
                </body>
                </html>
            ",

            // Confirmación de recepción de cita al cliente
            'appointment_received' => "
                <!DOCTYPE html>
                <html>
                <head><style>{$styles}</style></head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>{$businessName}</h1>
                        </div>
                        <div class='content'>
                            <p>Hola <strong>{$name}</strong>,</p>
                            
                            <p>Hemos recibido tu solicitud de cita. Te contactaremos en menos de 24 horas para confirmar la disponibilidad.</p>
                            
                            <div class='highlight'>
                                <p><strong>Datos de tu solicitud:</strong></p>
                                <p>Fecha: {$date}</p>
                                <p>Hora: {$time}</p>
                                <p>Servicio: {$service}</p>
                            </div>
                            
                            <p><em>Nota: Esta es una confirmación de recepción, no de la cita. Te enviaremos otro email cuando confirmemos tu cita.</em></p>
                            
                            <p>Si necesitas hacer algún cambio, contáctanos:</p>
                            <ul>
                                <li>Teléfono: {$businessPhone}</li>
                                <li>WhatsApp: <a href='https://wa.me/{$whatsappNumber}'>Enviar mensaje</a></li>
                            </ul>
                        </div>
                        <div class='footer'>
                            <p>{$businessName}</p>
                            <p>{$businessAddress} | {$businessHours}</p>
                        </div>
                    </div>
                </body>
                </html>
            ",

            // Cita confirmada al cliente
            'appointment_confirmed' => "
                <!DOCTYPE html>
                <html>
                <head><style>{$styles}</style></head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>¡Tu Cita está Confirmada!</h1>
                        </div>
                        <div class='content'>
                            <p>Hola <strong>{$name}</strong>,</p>
                            
                            <p>Tu cita ha sido confirmada. Te esperamos:</p>
                            
                            <div class='highlight' style='text-align: center;'>
                                <p style='font-size: 18px; margin: 0;'><strong>{$date}</strong></p>
                                <p style='font-size: 24px; color: {$primaryColor}; margin: 10px 0;'><strong>{$time}</strong></p>
                                <p style='margin: 0;'>Servicio: {$service}</p>
                            </div>
                            
                            <p><strong>Ubicación:</strong> {$businessAddress}</p>
                            
                            <p>Si necesitas cancelar o reagendar tu cita, por favor contáctanos con al menos 24 horas de anticipación:</p>
                            <ul>
                                <li>Teléfono: {$businessPhone}</li>
                                <li>WhatsApp: <a href='https://wa.me/{$whatsappNumber}'>Enviar mensaje</a></li>
                            </ul>
                        </div>
                        <div class='footer'>
                            <p>{$businessName}</p>
                            <p>{$businessAddress} | {$businessHours}</p>
                        </div>
                    </div>
                </body>
                </html>
            ",

            // Cita cancelada al cliente
            'appointment_cancelled' => "
                <!DOCTYPE html>
                <html>
                <head><style>{$styles}</style></head>
                <body>
                    <div class='container'>
                        <div class='header' style='background-color: #666;'>
                            <h1>Cita Cancelada</h1>
                        </div>
                        <div class='content'>
                            <p>Hola <strong>{$name}</strong>,</p>
                            
                            <p>Lamentamos informarte que tu cita del <strong>{$date}</strong> a las <strong>{$time}</strong> ha sido cancelada.</p>
                            
                            <p>Si deseas reagendar, puedes hacerlo fácilmente:</p>
                            
                            <div style='text-align: center; margin: 20px 0;'>
                                <a href='" . $siteUrl . "/#citas' class='button'>Agendar Nueva Cita</a>
                            </div>
                            
                            <p>O contáctanos directamente:</p>
                            <ul>
                                <li>Teléfono: {$businessPhone}</li>
                                <li>WhatsApp: <a href='https://wa.me/{$whatsappNumber}'>Enviar mensaje</a></li>
                            </ul>
                            
                            <p>Disculpa las molestias.</p>
                        </div>
                        <div class='footer'>
                            <p>{$businessName}</p>
                            <p>{$businessAddress} | {$businessHours}</p>
                        </div>
                    </div>
                </body>
                </html>
            "
        ];

        return $templates[$template] ?? '';
    }

    /**
     * Formatear fecha en español
     */
    private static function formatDate(string $date): string
    {
        $days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        $months = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

        $timestamp = strtotime($date);
        $dayName = $days[date('w', $timestamp)];
        $day = date('j', $timestamp);
        $month = $months[(int)date('n', $timestamp)];
        $year = date('Y', $timestamp);

        return "{$dayName} {$day} de {$month} de {$year}";
    }
}
