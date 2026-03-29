<?php
/**
 * Router.php - Funciones de enrutamiento
 * 
 * Maneja todas las rutas de la aplicación:
 * - Rutas API (/api/*)
 * - Rutas del panel de administración
 * - Rutas estáticas (robots.txt, sitemap.xml)
 */

if (!defined('APP_ROOT')) {
    die('Acceso directo no permitido');
}

/**
 * Manejar rutas de la API
 */
function handleApiRoutes(string $uri, string $method): void
{
    if ($method === 'GET') {
        switch ($uri) {
            case '/api/services':
                $adminController = new AdminController();
                $services = $adminController->getActiveServices();
                Response::success('Servicios obtenidos', ['services' => $services]);
                break;
            
            default:
                Response::notFound('Endpoint no encontrado');
        }
        return;
    }

    if ($method !== 'POST') {
        Response::error('Método no permitido', null, 405);
    }

    switch ($uri) {
        case '/api/leads':
            $controller = new LeadController();
            $controller->submit();
            break;

        case '/api/appointments':
            $controller = new AppointmentController();
            $controller->submit();
            break;

        case '/api/appointments/slots':
            $controller = new AppointmentController();
            $controller->getAvailableSlots();
            break;

        default:
            Response::notFound('Endpoint no encontrado');
    }
}

/**
 * Manejar rutas del panel de administración
 */
function handleAdminRoutes(string $uri, string $adminPath, string $method): void
{
    $adminUri = substr($uri, strlen($adminPath)) ?: '/';
    $adminUri = rtrim($adminUri, '/') ?: '/';

    $authController = new AuthController();
    $adminController = new AdminController();

    if ($adminUri === '/login') {
        $authController->login();
        return;
    }

    if ($adminUri === '/logout') {
        $authController->logout();
        return;
    }

    $authController->requireAuth();

    if ($adminUri === '/' || $adminUri === '') {
        $adminController->dashboard();
        return;
    }

    if ($adminUri === '/leads') {
        $adminController->leads();
        return;
    }

    if ($adminUri === '/leads/export') {
        $adminController->exportLeadsCsv();
        return;
    }

    if ($adminUri === '/appointments') {
        $adminController->appointments();
        return;
    }

    if ($adminUri === '/appointments/by-day') {
        $adminController->getAppointmentsByDay();
        return;
    }

    if (preg_match('#^/leads/(\d+)/status$#', $adminUri, $matches)) {
        $adminController->updateLeadStatus((int)$matches[1]);
        return;
    }

    if (preg_match('#^/leads/(\d+)$#', $adminUri, $matches)) {
        $adminController->getLeadDetail((int)$matches[1]);
        return;
    }

    if (preg_match('#^/appointments/(\d+)/status$#', $adminUri, $matches)) {
        $adminController->updateAppointmentStatus((int)$matches[1]);
        return;
    }

    if (preg_match('#^/appointments/(\d+)/notes$#', $adminUri, $matches)) {
        $adminController->updateAppointmentNotes((int)$matches[1]);
        return;
    }

    if (preg_match('#^/appointments/(\d+)$#', $adminUri, $matches)) {
        $adminController->getAppointmentDetail((int)$matches[1]);
        return;
    }

    if ($adminUri === '/settings') {
        $adminController->settings();
        return;
    }

    if ($adminUri === '/services/save') {
        $adminController->saveService();
        return;
    }

    if (preg_match('#^/services/(\d+)/delete$#', $adminUri, $matches)) {
        $adminController->deleteService((int)$matches[1]);
        return;
    }

    if ($adminUri === '/testimonials/save') {
        $adminController->saveTestimonial();
        return;
    }

    if (preg_match('#^/testimonials/(\d+)/delete$#', $adminUri, $matches)) {
        $adminController->deleteTestimonial((int)$matches[1]);
        return;
    }

    if ($adminUri === '/faq/save') {
        $adminController->saveFaqItem();
        return;
    }

    if (preg_match('#^/faq/(\d+)/delete$#', $adminUri, $matches)) {
        $adminController->deleteFaqItem((int)$matches[1]);
        return;
    }

    if ($adminUri === '/business-settings/save') {
        $adminController->saveBusinessSettings();
        return;
    }

    if ($adminUri === '/schedules/save') {
        $adminController->saveSchedule();
        return;
    }

    if (preg_match('#^/schedules/(\d+)/delete$#', $adminUri, $matches)) {
        $adminController->deleteSchedule((int)$matches[1]);
        return;
    }

    if ($adminUri === '/blocked-dates/add') {
        $adminController->addBlockedDate();
        return;
    }

    if (preg_match('#^/blocked-dates/(\d+)/delete$#', $adminUri, $matches)) {
        $adminController->deleteBlockedDate((int)$matches[1]);
        return;
    }

    Response::notFound('Página no encontrada');
}
