<?php
/**
 * admin/index.php - Panel de Administración Dashboard
 * 
 * Esta página es renderizada por el front controller (index.php).
 * Solo debe ser incluida, no accedida directamente.
 * 
 * Variables disponibles (pasadas desde AdminController::render):
 * - $view: string ('dashboard', 'leads', 'appointments')
 * - $admin: array (datos del admin actual)
 * - $csrfToken: string (token CSRF para formularios)
 * 
 * Para Dashboard:
 * - $leadStats, $appointmentStats, $recentActivity
 * 
 * Para Leads:
 * - $leads, $pagination, $filters
 * 
 * Para Appointments:
 * - $appointments, $pagination, $filters, $calendarData
 */

// Prevenir acceso directo
if (!defined('APP_ROOT')) {
    die('Acceso directo no permitido');
}

// Determinar la vista actual (pasada desde AdminController)
$currentView = $view ?? 'dashboard';

$businessSettings = $businessSettings ?? BusinessConfig::all();
$brandPrimary = $businessSettings['color_primary'] ?? COLOR_PRIMARY;
$brandAccent = $businessSettings['color_accent'] ?? COLOR_ACCENT;
$brandBg = $businessSettings['color_bg'] ?? COLOR_BG;
$brandName = $businessSettings['business_name'] ?? BUSINESS_NAME;

// Helper functions
function timeAgo($datetime) {
    if (!$datetime) return '';
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'hace un momento';
    if ($diff < 3600) return 'hace ' . floor($diff / 60) . ' min';
    if ($diff < 86400) return 'hace ' . floor($diff / 3600) . ' h';
    if ($diff < 604800) return 'hace ' . floor($diff / 86400) . ' días';
    
    return date('d/m/Y', $time);
}

function formatDate($date) {
    if (!$date) return '';
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    if (!$datetime) return '';
    return date('d/m/Y H:i', strtotime($datetime));
}

function getLeadStatusClass($status) {
    $classes = [
        'new' => 'bg-blue-100 text-blue-700',
        'contacted' => 'bg-yellow-100 text-yellow-700',
        'converted' => 'bg-green-100 text-green-700',
        'discarded' => 'bg-gray-100 text-gray-700',
    ];
    return $classes[$status] ?? 'bg-gray-100 text-gray-700';
}

function getLeadStatusLabel($status) {
    $labels = [
        'new' => 'Nuevo',
        'contacted' => 'Contactado',
        'converted' => 'Convertido',
        'discarded' => 'Descartado',
    ];
    return $labels[$status] ?? ucfirst($status);
}

function getAppointmentStatusClass($status) {
    $classes = [
        'pending' => 'bg-yellow-100 text-yellow-700',
        'confirmed' => 'bg-blue-100 text-blue-700',
        'completed' => 'bg-green-100 text-green-700',
        'cancelled' => 'bg-red-100 text-red-700',
        'no_show' => 'bg-gray-100 text-gray-700',
    ];
    return $classes[$status] ?? 'bg-gray-100 text-gray-700';
}

function getAppointmentStatusLabel($status) {
    $labels = [
        'pending' => 'Pendiente',
        'confirmed' => 'Confirmada',
        'completed' => 'Completada',
        'cancelled' => 'Cancelada',
        'no_show' => 'No asistió',
    ];
    return $labels[$status] ?? ucfirst($status);
}

function getDayOfWeekLabel($day) {
    $days = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];
    return $days[$day] ?? 'Desconocido';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Panel de Administración | <?php echo htmlspecialchars($brandName); ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '<?php echo htmlspecialchars($brandPrimary); ?>',
                        accent: '<?php echo htmlspecialchars($brandAccent); ?>',
                    }
                }
            }
        }
    </script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        .sidebar-link.active { background: rgba(255,255,255,0.2); }
        .toast-container { position: fixed; top: 1rem; right: 1rem; z-index: 100; }
        .toast { padding: 1rem; border-radius: 0.5rem; margin-bottom: 0.5rem; animation: slideIn 0.3s ease; }
        .toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .toast.error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
    </style>
</head>
<body class="bg-gray-100 min-h-screen" style="background-color: <?php echo htmlspecialchars($brandBg); ?>;">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-primary transform -translate-x-full lg:translate-x-0 lg:static transition-transform duration-200 ease-in-out">
            <div class="flex flex-col h-full">
                <!-- Logo -->
                <div class="flex items-center justify-between h-16 px-4 border-b border-white/10">
                    <a href="/<?php echo ADMIN_PATH; ?>" class="text-white font-bold text-lg truncate">
                        <?php echo htmlspecialchars($brandName); ?>
                    </a>
                    <button id="close-sidebar" class="lg:hidden text-white/70 hover:text-white">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto">
                    <a href="/<?php echo ADMIN_PATH; ?>" 
                       class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentView === 'dashboard' ? 'active bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white'; ?>">
                        <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                        Dashboard
                    </a>
                    <a href="/<?php echo ADMIN_PATH; ?>/leads" 
                       class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentView === 'leads' ? 'active bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white'; ?>">
                        <i data-lucide="users" class="w-5 h-5"></i>
                        Leads
                        <?php if (isset($leadStats['by_status']['new']) && $leadStats['by_status']['new'] > 0): ?>
                        <span class="ml-auto bg-accent text-white text-xs px-2 py-1 rounded-full">
                            <?php echo $leadStats['by_status']['new']; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <a href="/<?php echo ADMIN_PATH; ?>/appointments" 
                       class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentView === 'appointments' ? 'active bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white'; ?>">
                        <i data-lucide="calendar" class="w-5 h-5"></i>
                        Citas
                        <?php if (isset($appointmentStats['pending']) && $appointmentStats['pending'] > 0): ?>
                        <span class="ml-auto bg-accent text-white text-xs px-2 py-1 rounded-full">
                            <?php echo $appointmentStats['pending']; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <a href="/<?php echo ADMIN_PATH; ?>/settings" 
                       class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg transition-colors <?php echo $currentView === 'settings' ? 'active bg-white/20 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white'; ?>">
                        <i data-lucide="settings" class="w-5 h-5"></i>
                        Configuración
                    </a>
                </nav>

                <!-- User Menu -->
                <div class="p-4 border-t border-white/10">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                            <i data-lucide="user" class="w-5 h-5 text-white"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-white font-medium truncate"><?php echo htmlspecialchars($admin['username'] ?? 'Admin'); ?></p>
                        </div>
                    </div>
                    <a href="/<?php echo ADMIN_PATH; ?>/logout" 
                       class="flex items-center gap-2 text-white/70 hover:text-white transition-colors text-sm">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        Cerrar sesión
                    </a>
                </div>
            </div>
        </aside>

        <!-- Sidebar Overlay (mobile) -->
        <div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 lg:hidden hidden"></div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm h-16 flex items-center justify-between px-4 lg:px-6">
                <div class="flex items-center gap-4">
                    <button id="open-sidebar" class="lg:hidden text-gray-600 hover:text-gray-900">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                    <h1 class="text-xl font-semibold text-gray-900">
                        <?php
                        switch ($currentView) {
                            case 'leads': echo 'Gestión de Leads'; break;
                            case 'appointments': echo 'Gestión de Citas'; break;
                            default: echo 'Dashboard';
                        }
                        ?>
                    </h1>
                </div>
                <div class="flex items-center gap-4">
                    <a href="/" target="_blank" class="text-gray-600 hover:text-primary transition-colors" title="Ver sitio">
                        <i data-lucide="external-link" class="w-5 h-5"></i>
                    </a>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                
                <?php if ($currentView === 'dashboard'): ?>
                <!-- ==================== DASHBOARD VIEW ==================== -->
                <div class="space-y-6">
                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Total Leads -->
                        <div class="bg-white rounded-xl p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm">Total Leads</p>
                                    <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($leadStats['total'] ?? 0); ?></p>
                                </div>
                                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                    <i data-lucide="users" class="w-6 h-6 text-blue-600"></i>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-4">
                                <span class="text-green-600 font-medium">+<?php echo $leadStats['this_week'] ?? 0; ?></span> esta semana
                            </p>
                        </div>

                        <!-- Leads Nuevos -->
                        <div class="bg-white rounded-xl p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm">Leads Nuevos</p>
                                    <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($leadStats['by_status']['new'] ?? 0); ?></p>
                                </div>
                                <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                                    <i data-lucide="inbox" class="w-6 h-6 text-yellow-600"></i>
                                </div>
                            </div>
                            <a href="/<?php echo ADMIN_PATH; ?>/leads?status=new" class="text-xs text-primary hover:underline mt-4 inline-block">
                                Ver leads nuevos →
                            </a>
                        </div>

                        <!-- Total Citas -->
                        <div class="bg-white rounded-xl p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm">Total Citas</p>
                                    <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($appointmentStats['total'] ?? 0); ?></p>
                                </div>
                                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                                    <i data-lucide="calendar" class="w-6 h-6 text-purple-600"></i>
                                </div>
                            </div>
                            <p class="text-xs text-gray-500 mt-4">
                                <span class="text-green-600 font-medium">+<?php echo $appointmentStats['this_week'] ?? 0; ?></span> esta semana
                            </p>
                        </div>

                        <!-- Citas Pendientes -->
                        <div class="bg-white rounded-xl p-6 shadow-sm">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-gray-500 text-sm">Citas Pendientes</p>
                                    <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo number_format($appointmentStats['pending'] ?? 0); ?></p>
                                </div>
                                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                                    <i data-lucide="clock" class="w-6 h-6 text-green-600"></i>
                                </div>
                            </div>
                            <a href="/<?php echo ADMIN_PATH; ?>/appointments?status=pending" class="text-xs text-primary hover:underline mt-4 inline-block">
                                Ver citas pendientes →
                            </a>
                        </div>
                    </div>

                    <!-- Recent Activity & Upcoming -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Recent Activity -->
                        <div class="bg-white rounded-xl shadow-sm">
                            <div class="p-6 border-b border-gray-100">
                                <h2 class="font-semibold text-gray-900">Actividad Reciente</h2>
                            </div>
                            <div class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                                <?php if (!empty($recentActivity)): ?>
                                    <?php foreach ($recentActivity as $item): ?>
                                    <div class="p-4 hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 <?php echo $item['type'] === 'lead' ? 'bg-blue-100' : 'bg-purple-100'; ?> rounded-full flex items-center justify-center">
                                                <i data-lucide="<?php echo $item['type'] === 'lead' ? 'user' : 'calendar'; ?>" 
                                                   class="w-5 h-5 <?php echo $item['type'] === 'lead' ? 'text-blue-600' : 'text-purple-600'; ?>"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-gray-900 truncate"><?php echo htmlspecialchars($item['name']); ?></p>
                                                <p class="text-sm text-gray-500">
                                                    <?php echo $item['type'] === 'lead' ? 'Nuevo lead' : 'Nueva cita'; ?>
                                                    · <?php echo timeAgo($item['created_at']); ?>
                                                </p>
                                            </div>
                                            <span class="px-2 py-1 text-xs rounded-full <?php echo $item['type'] === 'lead' ? getLeadStatusClass($item['status']) : getAppointmentStatusClass($item['status']); ?>">
                                                <?php echo $item['type'] === 'lead' ? getLeadStatusLabel($item['status']) : getAppointmentStatusLabel($item['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-8 text-center text-gray-500">
                                        <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                                        <p>No hay actividad reciente</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Upcoming Appointments -->
                        <div class="bg-white rounded-xl shadow-sm">
                            <div class="p-6 border-b border-gray-100">
                                <div class="flex items-center justify-between">
                                    <h2 class="font-semibold text-gray-900">Próximas Citas</h2>
                                    <a href="/<?php echo ADMIN_PATH; ?>/appointments" class="text-sm text-primary hover:underline">Ver todas</a>
                                </div>
                            </div>
                            <div class="divide-y divide-gray-100">
                                <?php if (!empty($appointmentStats['upcoming'])): ?>
                                    <?php foreach ($appointmentStats['upcoming'] as $appointment): ?>
                                    <div class="p-4 hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                                                <i data-lucide="calendar" class="w-5 h-5 text-primary"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-medium text-gray-900 truncate"><?php echo htmlspecialchars($appointment['name']); ?></p>
                                                <p class="text-sm text-gray-500">
                                                    <?php echo formatDate($appointment['appointment_date']); ?> a las <?php echo htmlspecialchars($appointment['appointment_time']); ?>
                                                </p>
                                            </div>
                                            <span class="px-2 py-1 text-xs rounded-full <?php echo getAppointmentStatusClass($appointment['status']); ?>">
                                                <?php echo getAppointmentStatusLabel($appointment['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-8 text-center text-gray-500">
                                        <i data-lucide="calendar-x" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                                        <p>No hay citas próximas</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <?php elseif ($currentView === 'leads'): ?>
                <!-- ==================== LEADS VIEW ==================== -->
                <div class="space-y-6">
                    <!-- Filters -->
                    <div class="bg-white rounded-xl p-4 shadow-sm">
                        <form method="GET" class="flex flex-wrap gap-4">
                            <div class="flex-1 min-w-[200px]">
                                <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" 
                                       placeholder="Buscar por nombre o email..." 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>
                            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                <option value="">Todos los estados</option>
                                <option value="new" <?php echo ($filters['status'] ?? '') === 'new' ? 'selected' : ''; ?>>Nuevo</option>
                                <option value="contacted" <?php echo ($filters['status'] ?? '') === 'contacted' ? 'selected' : ''; ?>>Contactado</option>
                                <option value="converted" <?php echo ($filters['status'] ?? '') === 'converted' ? 'selected' : ''; ?>>Convertido</option>
                                <option value="discarded" <?php echo ($filters['status'] ?? '') === 'discarded' ? 'selected' : ''; ?>>Descartado</option>
                            </select>
                            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                                <i data-lucide="search" class="w-4 h-4 inline-block mr-1"></i>
                                Filtrar
                            </button>
                            <a href="/<?php echo ADMIN_PATH; ?>/leads/export?<?php echo http_build_query($filters); ?>" 
                               class="px-6 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                <i data-lucide="download" class="w-4 h-4 inline-block mr-1"></i>
                                Exportar CSV
                            </a>
                        </form>
                    </div>

                    <!-- Leads Table -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Contacto</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Servicio</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Estado</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Fecha</th>
                                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if (!empty($leads)): ?>
                                        <?php foreach ($leads as $lead): ?>
                                        <tr class="hover:bg-gray-50 transition-colors" data-lead-id="<?php echo $lead['id']; ?>">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                                                        <span class="text-gray-600 font-medium"><?php echo strtoupper(substr($lead['name'], 0, 1)); ?></span>
                                                    </div>
                                                    <div>
                                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($lead['name']); ?></p>
                                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($lead['email']); ?></p>
                                                        <?php if (!empty($lead['phone'])): ?>
                                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($lead['phone']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-gray-600">
                                                <?php echo htmlspecialchars($lead['service_interest'] ?? 'No especificado'); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <select class="lead-status-select text-sm rounded-full px-3 py-1 border-0 cursor-pointer <?php echo getLeadStatusClass($lead['status']); ?>"
                                                        data-lead-id="<?php echo $lead['id']; ?>"
                                                        data-original="<?php echo $lead['status']; ?>">
                                                    <option value="new" <?php echo $lead['status'] === 'new' ? 'selected' : ''; ?>>Nuevo</option>
                                                    <option value="contacted" <?php echo $lead['status'] === 'contacted' ? 'selected' : ''; ?>>Contactado</option>
                                                    <option value="converted" <?php echo $lead['status'] === 'converted' ? 'selected' : ''; ?>>Convertido</option>
                                                    <option value="discarded" <?php echo $lead['status'] === 'discarded' ? 'selected' : ''; ?>>Descartado</option>
                                                </select>
                                            </td>
                                            <td class="px-6 py-4 text-gray-600">
                                                <?php echo formatDateTime($lead['created_at']); ?>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <button class="view-lead-btn text-gray-400 hover:text-primary transition-colors p-2" 
                                                        data-lead-id="<?php echo $lead['id']; ?>" title="Ver detalles">
                                                    <i data-lucide="eye" class="w-5 h-5"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                                <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                                                <p>No hay leads registrados</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if (isset($pagination) && $pagination['total'] > $pagination['per_page']): ?>
                        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                            <p class="text-sm text-gray-600">
                                Mostrando <?php echo (($pagination['page'] - 1) * $pagination['per_page']) + 1; ?> - 
                                <?php echo min($pagination['page'] * $pagination['per_page'], $pagination['total']); ?> 
                                de <?php echo $pagination['total']; ?> registros
                            </p>
                            <div class="flex gap-2">
                                <?php if ($pagination['page'] > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($filters, ['page' => $pagination['page'] - 1])); ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">Anterior</a>
                                <?php endif; ?>
                                <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                                <a href="?<?php echo http_build_query(array_merge($filters, ['page' => $pagination['page'] + 1])); ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">Siguiente</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php elseif ($currentView === 'appointments'): ?>
                <!-- ==================== APPOINTMENTS VIEW ==================== -->
                <div class="space-y-6">
                    <!-- Filters -->
                    <div class="bg-white rounded-xl p-4 shadow-sm">
                        <form method="GET" class="flex flex-wrap gap-4">
                            <div class="flex-1 min-w-[200px]">
                                <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" 
                                       placeholder="Buscar por nombre o email..." 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>" 
                                   class="px-4 py-2 border border-gray-300 rounded-lg" placeholder="Desde">
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>" 
                                   class="px-4 py-2 border border-gray-300 rounded-lg" placeholder="Hasta">
                            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg">
                                <option value="">Todos los estados</option>
                                <option value="pending" <?php echo ($filters['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="confirmed" <?php echo ($filters['status'] ?? '') === 'confirmed' ? 'selected' : ''; ?>>Confirmada</option>
                                <option value="completed" <?php echo ($filters['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completada</option>
                                <option value="cancelled" <?php echo ($filters['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelada</option>
                                <option value="no_show" <?php echo ($filters['status'] ?? '') === 'no_show' ? 'selected' : ''; ?>>No asistió</option>
                            </select>
                            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                                <i data-lucide="search" class="w-4 h-4 inline-block mr-1"></i>
                                Filtrar
                            </button>
                        </form>
                    </div>

                    <!-- Appointments Table -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Cliente</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Fecha y Hora</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Servicio</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Estado</th>
                                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if (!empty($appointments)): ?>
                                        <?php foreach ($appointments as $appointment): ?>
                                        <tr class="hover:bg-gray-50 transition-colors" data-appointment-id="<?php echo $appointment['id']; ?>">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                                                        <i data-lucide="user" class="w-5 h-5 text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <p class="font-medium text-gray-900"><?php echo htmlspecialchars($appointment['name']); ?></p>
                                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['email']); ?></p>
                                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['phone']); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <p class="font-medium text-gray-900"><?php echo formatDate($appointment['appointment_date']); ?></p>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($appointment['appointment_time']); ?></p>
                                            </td>
                                            <td class="px-6 py-4 text-gray-600">
                                                <?php echo htmlspecialchars($appointment['service_interest'] ?? 'No especificado'); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <select class="appointment-status-select text-sm rounded-full px-3 py-1 border-0 cursor-pointer <?php echo getAppointmentStatusClass($appointment['status']); ?>"
                                                        data-appointment-id="<?php echo $appointment['id']; ?>"
                                                        data-original="<?php echo $appointment['status']; ?>">
                                                    <option value="pending" <?php echo $appointment['status'] === 'pending' ? 'selected' : ''; ?>>Pendiente</option>
                                                    <option value="confirmed" <?php echo $appointment['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmada</option>
                                                    <option value="completed" <?php echo $appointment['status'] === 'completed' ? 'selected' : ''; ?>>Completada</option>
                                                    <option value="cancelled" <?php echo $appointment['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelada</option>
                                                    <option value="no_show" <?php echo $appointment['status'] === 'no_show' ? 'selected' : ''; ?>>No asistió</option>
                                                </select>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <button class="view-appointment-btn text-gray-400 hover:text-primary transition-colors p-2" 
                                                        data-appointment-id="<?php echo $appointment['id']; ?>" title="Ver detalles">
                                                    <i data-lucide="eye" class="w-5 h-5"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                                <i data-lucide="calendar-x" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                                                <p>No hay citas registradas</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if (isset($pagination) && $pagination['total'] > $pagination['per_page']): ?>
                        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                            <p class="text-sm text-gray-600">
                                Mostrando <?php echo (($pagination['page'] - 1) * $pagination['per_page']) + 1; ?> - 
                                <?php echo min($pagination['page'] * $pagination['per_page'], $pagination['total']); ?> 
                                de <?php echo $pagination['total']; ?> registros
                            </p>
                            <div class="flex gap-2">
                                <?php if ($pagination['page'] > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($filters, ['page' => $pagination['page'] - 1])); ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">Anterior</a>
                                <?php endif; ?>
                                <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                                <a href="?<?php echo http_build_query(array_merge($filters, ['page' => $pagination['page'] + 1])); ?>" 
                                   class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">Siguiente</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php elseif ($currentView === 'settings'): ?>
                <!-- ==================== SETTINGS VIEW ==================== -->
                <div class="space-y-6">
                    <!-- Services Section -->
                    <div class="bg-white rounded-xl shadow-sm">
                        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">Servicios</h2>
                                <p class="text-sm text-gray-500">Administra los servicios disponibles para citas</p>
                            </div>
                            <button onclick="openServiceModal()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                Agregar Servicio
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Nombre</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Descripción</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Icono</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Duración</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Estado</th>
                                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if (!empty($services)): ?>
                                        <?php foreach ($services as $service): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($service['name']); ?></td>
                                            <td class="px-6 py-4 text-gray-600 text-sm max-w-xs truncate"><?php echo htmlspecialchars($service['short_desc'] ?? $service['description'] ?? ''); ?></td>
                                            <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($service['icon'] ?? 'briefcase'); ?></td>
                                            <td class="px-6 py-4 text-gray-600"><?php echo $service['duration']; ?> min</td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-1 text-xs rounded-full <?php echo $service['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'; ?>">
                                                    <?php echo $service['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <button onclick="editService(<?php echo htmlspecialchars(json_encode($service)); ?>)" 
                                                        class="text-gray-400 hover:text-primary transition-colors p-2" title="Editar">
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </button>
                                                <button onclick="deleteService(<?php echo $service['id']; ?>, '<?php echo htmlspecialchars($service['name']); ?>')" 
                                                        class="text-gray-400 hover:text-red-500 transition-colors p-2" title="Eliminar">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                                <i data-lucide="package" class="w-10 h-10 mx-auto mb-2 text-gray-300"></i>
                                                <p>No hay servicios configurados</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Schedules Section -->
                    <div class="bg-white rounded-xl shadow-sm">
                        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">Horarios Disponibles</h2>
                                <p class="text-sm text-gray-500">Define los días y horarios para agendar citas</p>
                            </div>
                            <button onclick="openScheduleModal()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                Agregar Horario
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Día</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Hora Inicio</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Hora Fin</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Duración Slot</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Estado</th>
                                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if (!empty($schedules)): ?>
                                        <?php foreach ($schedules as $schedule): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 font-medium text-gray-900"><?php echo getDayOfWeekLabel($schedule['day_of_week']); ?></td>
                                            <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($schedule['start_time']); ?></td>
                                            <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($schedule['end_time']); ?></td>
                                            <td class="px-6 py-4 text-gray-600"><?php echo $schedule['slot_duration']; ?> min</td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-1 text-xs rounded-full <?php echo $schedule['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'; ?>">
                                                    <?php echo $schedule['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <button onclick="editSchedule(<?php echo htmlspecialchars(json_encode($schedule)); ?>)" 
                                                        class="text-gray-400 hover:text-primary transition-colors p-2" title="Editar">
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </button>
                                                <button onclick="deleteSchedule(<?php echo $schedule['id']; ?>, '<?php echo getDayOfWeekLabel($schedule['day_of_week']); ?>')" 
                                                        class="text-gray-400 hover:text-red-500 transition-colors p-2" title="Eliminar">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                                <i data-lucide="clock" class="w-10 h-10 mx-auto mb-2 text-gray-300"></i>
                                                <p>No hay horarios configurados</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Blocked Dates Section -->
                    <div class="bg-white rounded-xl shadow-sm">
                        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">Fechas Bloqueadas</h2>
                                <p class="text-sm text-gray-500">Marca días específicos como no disponibles (vacaciones, feriados, etc.)</p>
                            </div>
                            <button onclick="openBlockedDateModal()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                Bloquear Fecha
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Fecha</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Motivo</th>
                                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if (!empty($blockedDates)): ?>
                                        <?php foreach ($blockedDates as $blockedDate): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 font-medium text-gray-900"><?php echo formatDate($blockedDate['blocked_date']); ?></td>
                                            <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($blockedDate['reason'] ?? '-'); ?></td>
                                            <td class="px-6 py-4 text-right">
                                                <button onclick="deleteBlockedDate(<?php echo $blockedDate['id']; ?>, '<?php echo formatDate($blockedDate['blocked_date']); ?>')" 
                                                        class="text-gray-400 hover:text-red-500 transition-colors p-2" title="Eliminar">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                                                <i data-lucide="calendar-off" class="w-10 h-10 mx-auto mb-2 text-gray-300"></i>
                                                <p>No hay fechas bloqueadas</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Testimonials Section -->
                    <div class="bg-white rounded-xl shadow-sm">
                        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">Testimonios</h2>
                                <p class="text-sm text-gray-500">Administra los testimonios que aparecen en la landing</p>
                            </div>
                            <button onclick="openTestimonialModal()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                Agregar Testimonio
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Cliente</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Empresa</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Estado</th>
                                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if (!empty($testimonials)): ?>
                                        <?php foreach ($testimonials as $testimonial): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($testimonial['name']); ?></td>
                                            <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($testimonial['company'] ?? '-'); ?></td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-1 text-xs rounded-full <?php echo $testimonial['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'; ?>">
                                                    <?php echo $testimonial['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <button onclick="editTestimonial(<?php echo htmlspecialchars(json_encode($testimonial)); ?>)" class="text-gray-400 hover:text-primary transition-colors p-2" title="Editar">
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </button>
                                                <button onclick="deleteTestimonial(<?php echo $testimonial['id']; ?>, '<?php echo htmlspecialchars($testimonial['name']); ?>')" class="text-gray-400 hover:text-red-500 transition-colors p-2" title="Eliminar">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                                <i data-lucide="message-square" class="w-10 h-10 mx-auto mb-2 text-gray-300"></i>
                                                <p>No hay testimonios configurados</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- FAQ Section -->
                    <div class="bg-white rounded-xl shadow-sm">
                        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">Preguntas Frecuentes (FAQ)</h2>
                                <p class="text-sm text-gray-500">Administra las preguntas frecuentes de la landing</p>
                            </div>
                            <button onclick="openFaqModal()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                Agregar FAQ
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Pregunta</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase">Estado</th>
                                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php if (!empty($faqItems)): ?>
                                        <?php foreach ($faqItems as $faq): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 font-medium text-gray-900"><?php echo htmlspecialchars($faq['question']); ?></td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-1 text-xs rounded-full <?php echo $faq['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'; ?>">
                                                    <?php echo $faq['is_active'] ? 'Activo' : 'Inactivo'; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                <button onclick="editFaq(<?php echo htmlspecialchars(json_encode($faq)); ?>)" class="text-gray-400 hover:text-primary transition-colors p-2" title="Editar">
                                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                                </button>
                                                <button onclick="deleteFaq(<?php echo $faq['id']; ?>, '<?php echo htmlspecialchars($faq['question']); ?>')" class="text-gray-400 hover:text-red-500 transition-colors p-2" title="Eliminar">
                                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="px-6 py-8 text-center text-gray-500">
                                                <i data-lucide="help-circle" class="w-10 h-10 mx-auto mb-2 text-gray-300"></i>
                                                <p>No hay preguntas frecuentes configuradas</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Brand/Business Settings Section -->
                    <div class="bg-white rounded-xl shadow-sm">
                        <div class="p-6 border-b border-gray-100">
                            <h2 class="text-lg font-semibold text-gray-900">Ajustes de Marca y Negocio</h2>
                            <p class="text-sm text-gray-500">Actualiza contenido base, SEO y colores de la landing</p>
                        </div>
                        <form id="business-settings-form" class="p-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del negocio *</label>
                                    <input type="text" name="business_name" required value="<?php echo htmlspecialchars($businessSettings['business_name'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Slogan</label>
                                    <input type="text" name="business_tagline" value="<?php echo htmlspecialchars($businessSettings['business_tagline'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                    <input type="email" name="business_email" required value="<?php echo htmlspecialchars($businessSettings['business_email'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                                    <input type="text" name="business_phone" value="<?php echo htmlspecialchars($businessSettings['business_phone'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp (solo números)</label>
                                    <input type="text" name="business_whatsapp" value="<?php echo htmlspecialchars($businessSettings['business_whatsapp'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Horario de atención</label>
                                    <input type="text" name="business_hours" value="<?php echo htmlspecialchars($businessSettings['business_hours'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                                <input type="text" name="business_address" value="<?php echo htmlspecialchars($businessSettings['business_address'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción del negocio</label>
                                <textarea name="business_description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"><?php echo htmlspecialchars($businessSettings['business_description'] ?? ''); ?></textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Color primario *</label>
                                    <input type="color" name="color_primary" value="<?php echo htmlspecialchars($businessSettings['color_primary'] ?? '#1a3a5c'); ?>" class="w-full h-10 border border-gray-300 rounded-lg">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Color acento *</label>
                                    <input type="color" name="color_accent" value="<?php echo htmlspecialchars($businessSettings['color_accent'] ?? '#e8a020'); ?>" class="w-full h-10 border border-gray-300 rounded-lg">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Color fondo *</label>
                                    <input type="color" name="color_bg" value="<?php echo htmlspecialchars($businessSettings['color_bg'] ?? '#f8f7f4'); ?>" class="w-full h-10 border border-gray-300 rounded-lg">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">URL del sitio (SITE_URL)</label>
                                    <input type="url" name="site_url" value="<?php echo htmlspecialchars($businessSettings['site_url'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mensaje WhatsApp por defecto</label>
                                    <input type="text" name="whatsapp_message" value="<?php echo htmlspecialchars($businessSettings['whatsapp_message'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Instagram</label>
                                    <input type="url" name="social_instagram" value="<?php echo htmlspecialchars($businessSettings['social_instagram'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Facebook</label>
                                    <input type="url" name="social_facebook" value="<?php echo htmlspecialchars($businessSettings['social_facebook'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">LinkedIn</label>
                                    <input type="url" name="social_linkedin" value="<?php echo htmlspecialchars($businessSettings['social_linkedin'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">SEO title</label>
                                    <input type="text" name="seo_title" value="<?php echo htmlspecialchars($businessSettings['seo_title'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">SEO keywords</label>
                                    <input type="text" name="seo_keywords" value="<?php echo htmlspecialchars($businessSettings['seo_keywords'] ?? ''); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">SEO description</label>
                                <textarea name="seo_description" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"><?php echo htmlspecialchars($businessSettings['seo_description'] ?? ''); ?></textarea>
                            </div>

                            <div class="flex justify-end pt-4 border-t">
                                <button type="submit" class="px-5 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">Guardar ajustes</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Lead Detail Modal -->
    <div id="lead-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50" onclick="closeModal('lead-modal')"></div>
        <div class="absolute inset-4 lg:inset-auto lg:top-1/2 lg:left-1/2 lg:-translate-x-1/2 lg:-translate-y-1/2 lg:w-full lg:max-w-2xl bg-white rounded-xl shadow-2xl overflow-hidden">
            <div class="flex items-center justify-between p-6 border-b">
                <h3 class="text-lg font-semibold">Detalles del Lead</h3>
                <button onclick="closeModal('lead-modal')" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div id="lead-modal-content" class="p-6 max-h-[60vh] overflow-y-auto">
                <div class="flex justify-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div></div>
            </div>
        </div>
    </div>

    <!-- Appointment Detail Modal -->
    <div id="appointment-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50" onclick="closeModal('appointment-modal')"></div>
        <div class="absolute inset-4 lg:inset-auto lg:top-1/2 lg:left-1/2 lg:-translate-x-1/2 lg:-translate-y-1/2 lg:w-full lg:max-w-2xl bg-white rounded-xl shadow-2xl overflow-hidden">
            <div class="flex items-center justify-between p-6 border-b">
                <h3 class="text-lg font-semibold">Detalles de la Cita</h3>
                <button onclick="closeModal('appointment-modal')" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <div id="appointment-modal-content" class="p-6 max-h-[60vh] overflow-y-auto">
                <div class="flex justify-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div></div>
            </div>
        </div>
    </div>

    <!-- Service Modal -->
    <div id="service-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50" onclick="closeModal('service-modal')"></div>
        <div class="absolute inset-4 lg:inset-auto lg:top-1/2 lg:left-1/2 lg:-translate-x-1/2 lg:-translate-y-1/2 lg:w-full lg:max-w-lg bg-white rounded-xl shadow-2xl overflow-hidden">
            <div class="flex items-center justify-between p-6 border-b">
                <h3 id="service-modal-title" class="text-lg font-semibold">Agregar Servicio</h3>
                <button onclick="closeModal('service-modal')" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <form id="service-form" class="p-6 space-y-4">
                <input type="hidden" name="id" id="service-id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del servicio *</label>
                    <input type="text" name="name" id="service-name" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea name="description" id="service-description" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción corta (tarjeta landing)</label>
                    <textarea name="short_desc" id="service-short-desc" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción completa (modal landing)</label>
                    <textarea name="full_desc" id="service-full-desc" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Icono Lucide (ej: search, briefcase, target)</label>
                    <input type="text" name="icon" id="service-icon" value="briefcase"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Duración (minutos)</label>
                        <input type="number" name="duration" id="service-duration" value="60" min="15" max="480"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                        <input type="number" name="sort_order" id="service-sort-order" value="0" min="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="service-active" checked 
                           class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                    <label for="service-active" class="text-sm text-gray-700">Servicio activo</label>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" onclick="closeModal('service-modal')" 
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Schedule Modal -->
    <div id="schedule-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50" onclick="closeModal('schedule-modal')"></div>
        <div class="absolute inset-4 lg:inset-auto lg:top-1/2 lg:left-1/2 lg:-translate-x-1/2 lg:-translate-y-1/2 lg:w-full lg:max-w-lg bg-white rounded-xl shadow-2xl overflow-hidden">
            <div class="flex items-center justify-between p-6 border-b">
                <h3 id="schedule-modal-title" class="text-lg font-semibold">Agregar Horario</h3>
                <button onclick="closeModal('schedule-modal')" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <form id="schedule-form" class="p-6 space-y-4">
                <input type="hidden" name="id" id="schedule-id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Día de la semana *</label>
                    <select name="day_of_week" id="schedule-day" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                        <option value="">Seleccionar día</option>
                        <option value="1">Lunes</option>
                        <option value="2">Martes</option>
                        <option value="3">Miércoles</option>
                        <option value="4">Jueves</option>
                        <option value="5">Viernes</option>
                        <option value="6">Sábado</option>
                        <option value="0">Domingo</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hora de inicio *</label>
                        <input type="time" name="start_time" id="schedule-start" required value="09:00"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hora de fin *</label>
                        <input type="time" name="end_time" id="schedule-end" required value="18:00"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duración de cada slot (minutos)</label>
                    <input type="number" name="slot_duration" id="schedule-slot-duration" value="60" min="15" max="240"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="schedule-active" checked 
                           class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                    <label for="schedule-active" class="text-sm text-gray-700">Horario activo</label>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" onclick="closeModal('schedule-modal')" 
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Blocked Date Modal -->
    <div id="blocked-date-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50" onclick="closeModal('blocked-date-modal')"></div>
        <div class="absolute inset-4 lg:inset-auto lg:top-1/2 lg:left-1/2 lg:-translate-x-1/2 lg:-translate-y-1/2 lg:w-full lg:max-w-md bg-white rounded-xl shadow-2xl overflow-hidden">
            <div class="flex items-center justify-between p-6 border-b">
                <h3 class="text-lg font-semibold">Bloquear Fecha</h3>
                <button onclick="closeModal('blocked-date-modal')" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <form id="blocked-date-form" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha a bloquear *</label>
                    <input type="date" name="blocked_date" id="blocked-date-value" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Motivo (opcional)</label>
                    <input type="text" name="reason" id="blocked-date-reason" placeholder="Ej: Feriado, Vacaciones, etc."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" onclick="closeModal('blocked-date-modal')" 
                            class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">Bloquear</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Testimonial Modal -->
    <div id="testimonial-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50" onclick="closeModal('testimonial-modal')"></div>
        <div class="absolute inset-4 lg:inset-auto lg:top-1/2 lg:left-1/2 lg:-translate-x-1/2 lg:-translate-y-1/2 lg:w-full lg:max-w-xl bg-white rounded-xl shadow-2xl overflow-hidden">
            <div class="flex items-center justify-between p-6 border-b">
                <h3 id="testimonial-modal-title" class="text-lg font-semibold">Agregar Testimonio</h3>
                <button onclick="closeModal('testimonial-modal')" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <form id="testimonial-form" class="p-6 space-y-4">
                <input type="hidden" name="id" id="testimonial-id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                        <input type="text" name="name" id="testimonial-name" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Empresa</label>
                        <input type="text" name="company" id="testimonial-company" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Testimonio *</label>
                    <textarea name="text" id="testimonial-text" rows="4" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Iniciales</label>
                        <input type="text" name="initials" id="testimonial-initials" maxlength="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                        <input type="color" name="color" id="testimonial-color" value="#4A90A4" class="w-full h-10 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                        <input type="number" name="sort_order" id="testimonial-sort-order" value="0" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="testimonial-active" checked class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                    <label for="testimonial-active" class="text-sm text-gray-700">Activo</label>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" onclick="closeModal('testimonial-modal')" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- FAQ Modal -->
    <div id="faq-modal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50" onclick="closeModal('faq-modal')"></div>
        <div class="absolute inset-4 lg:inset-auto lg:top-1/2 lg:left-1/2 lg:-translate-x-1/2 lg:-translate-y-1/2 lg:w-full lg:max-w-xl bg-white rounded-xl shadow-2xl overflow-hidden">
            <div class="flex items-center justify-between p-6 border-b">
                <h3 id="faq-modal-title" class="text-lg font-semibold">Agregar FAQ</h3>
                <button onclick="closeModal('faq-modal')" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            <form id="faq-form" class="p-6 space-y-4">
                <input type="hidden" name="id" id="faq-id">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pregunta *</label>
                    <input type="text" name="question" id="faq-question" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Respuesta *</label>
                    <textarea name="answer" id="faq-answer" rows="4" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                        <input type="number" name="sort_order" id="faq-sort-order" value="0" min="0" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary/20 focus:border-primary">
                    </div>
                    <div class="flex items-center gap-2 mt-7">
                        <input type="checkbox" name="is_active" id="faq-active" checked class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <label for="faq-active" class="text-sm text-gray-700">Activo</label>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t">
                    <button type="button" onclick="closeModal('faq-modal')" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container"></div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // CSRF Token
        const csrfToken = '<?php echo $csrfToken ?? ''; ?>';
        const adminPath = '<?php echo ADMIN_PATH; ?>';

        // Sidebar toggle
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const openSidebarBtn = document.getElementById('open-sidebar');
        const closeSidebarBtn = document.getElementById('close-sidebar');

        function openSidebar() {
            sidebar.classList.remove('-translate-x-full');
            sidebarOverlay.classList.remove('hidden');
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        }

        if (openSidebarBtn) openSidebarBtn.addEventListener('click', openSidebar);
        if (closeSidebarBtn) closeSidebarBtn.addEventListener('click', closeSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

        // Toast notifications
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${type}`;
            toast.innerHTML = `
                <div class="flex items-center gap-2">
                    <i data-lucide="${type === 'success' ? 'check-circle' : 'alert-circle'}" class="w-5 h-5"></i>
                    <span>${message}</span>
                </div>
            `;
            container.appendChild(toast);
            lucide.createIcons();
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }

        // View lead details
        document.querySelectorAll('.view-lead-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const leadId = btn.dataset.leadId;
                openModal('lead-modal');
                
                try {
                    const response = await fetch(`/${adminPath}/leads/${leadId}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await response.json();
                    
                    if (data.success && data.data.lead) {
                        const lead = data.data.lead;
                        document.getElementById('lead-modal-content').innerHTML = `
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Nombre</p>
                                        <p class="font-medium">${escapeHtml(lead.name)}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Email</p>
                                        <p class="font-medium"><a href="mailto:${escapeHtml(lead.email)}" class="text-primary hover:underline">${escapeHtml(lead.email)}</a></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Teléfono</p>
                                        <p class="font-medium">${lead.phone ? `<a href="tel:${escapeHtml(lead.phone)}" class="text-primary hover:underline">${escapeHtml(lead.phone)}</a>` : 'No proporcionado'}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Servicio de interés</p>
                                        <p class="font-medium">${escapeHtml(lead.service_interest || 'No especificado')}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Fecha de registro</p>
                                        <p class="font-medium">${escapeHtml(lead.created_at)}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Estado</p>
                                        <p class="font-medium">${escapeHtml(lead.status)}</p>
                                    </div>
                                </div>
                                ${lead.message ? `
                                <div>
                                    <p class="text-sm text-gray-500 mb-2">Mensaje</p>
                                    <div class="bg-gray-50 p-4 rounded-lg text-sm whitespace-pre-wrap">${escapeHtml(lead.message)}</div>
                                </div>
                                ` : ''}
                            </div>
                        `;
                    } else {
                        document.getElementById('lead-modal-content').innerHTML = '<p class="text-center text-red-500">Error al cargar los datos</p>';
                    }
                } catch (error) {
                    document.getElementById('lead-modal-content').innerHTML = '<p class="text-center text-red-500">Error de conexión</p>';
                }
            });
        });

        // View appointment details
        document.querySelectorAll('.view-appointment-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const appointmentId = btn.dataset.appointmentId;
                openModal('appointment-modal');
                
                try {
                    const response = await fetch(`/${adminPath}/appointments/${appointmentId}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await response.json();
                    
                    if (data.success && data.data.appointment) {
                        const apt = data.data.appointment;
                        document.getElementById('appointment-modal-content').innerHTML = `
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500">Nombre</p>
                                        <p class="font-medium">${escapeHtml(apt.name)}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Email</p>
                                        <p class="font-medium"><a href="mailto:${escapeHtml(apt.email)}" class="text-primary hover:underline">${escapeHtml(apt.email)}</a></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Teléfono</p>
                                        <p class="font-medium"><a href="tel:${escapeHtml(apt.phone)}" class="text-primary hover:underline">${escapeHtml(apt.phone)}</a></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Servicio</p>
                                        <p class="font-medium">${escapeHtml(apt.service_interest || 'No especificado')}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Fecha de cita</p>
                                        <p class="font-medium">${escapeHtml(apt.appointment_date)}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Hora</p>
                                        <p class="font-medium">${escapeHtml(apt.appointment_time)}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Estado</p>
                                        <p class="font-medium">${escapeHtml(apt.status)}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500">Registrado</p>
                                        <p class="font-medium">${escapeHtml(apt.created_at)}</p>
                                    </div>
                                </div>
                                ${apt.message ? `
                                <div>
                                    <p class="text-sm text-gray-500 mb-2">Mensaje</p>
                                    <div class="bg-gray-50 p-4 rounded-lg text-sm whitespace-pre-wrap">${escapeHtml(apt.message)}</div>
                                </div>
                                ` : ''}
                            </div>
                        `;
                    } else {
                        document.getElementById('appointment-modal-content').innerHTML = '<p class="text-center text-red-500">Error al cargar los datos</p>';
                    }
                } catch (error) {
                    document.getElementById('appointment-modal-content').innerHTML = '<p class="text-center text-red-500">Error de conexión</p>';
                }
            });
        });

        // Update lead status
        document.querySelectorAll('.lead-status-select').forEach(select => {
            select.addEventListener('change', async (e) => {
                const leadId = e.target.dataset.leadId;
                const newStatus = e.target.value;
                const originalStatus = e.target.dataset.original;
                
                try {
                    const formData = new FormData();
                    formData.append('status', newStatus);
                    formData.append('csrf_token', csrfToken);
                    
                    const response = await fetch(`/${adminPath}/leads/${leadId}/status`, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showToast('Estado actualizado correctamente');
                        e.target.dataset.original = newStatus;
                        // Update select classes
                        e.target.className = e.target.className.replace(/bg-\w+-100 text-\w+-700/g, '');
                        const statusClasses = {
                            'new': 'bg-blue-100 text-blue-700',
                            'contacted': 'bg-yellow-100 text-yellow-700',
                            'converted': 'bg-green-100 text-green-700',
                            'discarded': 'bg-gray-100 text-gray-700'
                        };
                        e.target.classList.add(...(statusClasses[newStatus] || 'bg-gray-100 text-gray-700').split(' '));
                    } else {
                        e.target.value = originalStatus;
                        showToast(data.message || 'Error al actualizar', 'error');
                    }
                } catch (error) {
                    e.target.value = originalStatus;
                    showToast('Error de conexión', 'error');
                }
            });
        });

        // Update appointment status
        document.querySelectorAll('.appointment-status-select').forEach(select => {
            select.addEventListener('change', async (e) => {
                const appointmentId = e.target.dataset.appointmentId;
                const newStatus = e.target.value;
                const originalStatus = e.target.dataset.original;
                
                try {
                    const formData = new FormData();
                    formData.append('status', newStatus);
                    formData.append('csrf_token', csrfToken);
                    
                    const response = await fetch(`/${adminPath}/appointments/${appointmentId}/status`, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        showToast(data.message || 'Estado actualizado correctamente');
                        e.target.dataset.original = newStatus;
                        // Update select classes
                        e.target.className = e.target.className.replace(/bg-\w+-100 text-\w+-700/g, '');
                        const statusClasses = {
                            'pending': 'bg-yellow-100 text-yellow-700',
                            'confirmed': 'bg-blue-100 text-blue-700',
                            'completed': 'bg-green-100 text-green-700',
                            'cancelled': 'bg-red-100 text-red-700',
                            'no_show': 'bg-gray-100 text-gray-700'
                        };
                        e.target.classList.add(...(statusClasses[newStatus] || 'bg-gray-100 text-gray-700').split(' '));
                    } else {
                        e.target.value = originalStatus;
                        showToast(data.message || 'Error al actualizar', 'error');
                    }
                } catch (error) {
                    e.target.value = originalStatus;
                    showToast('Error de conexión', 'error');
                }
            });
        });

        // Escape HTML helper
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Close modals with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeModal('lead-modal');
                closeModal('appointment-modal');
                closeModal('service-modal');
                closeModal('schedule-modal');
                closeModal('blocked-date-modal');
                closeModal('testimonial-modal');
                closeModal('faq-modal');
            }
        });

        // ==================== SERVICES MANAGEMENT ====================
        
        function openServiceModal(service = null) {
            const form = document.getElementById('service-form');
            const title = document.getElementById('service-modal-title');
            
            if (service) {
                title.textContent = 'Editar Servicio';
                document.getElementById('service-id').value = service.id;
                document.getElementById('service-name').value = service.name;
                document.getElementById('service-description').value = service.description || '';
                document.getElementById('service-short-desc').value = service.short_desc || service.description || '';
                document.getElementById('service-full-desc').value = service.full_desc || service.short_desc || service.description || '';
                document.getElementById('service-icon').value = service.icon || 'briefcase';
                document.getElementById('service-duration').value = service.duration || 60;
                document.getElementById('service-sort-order').value = service.sort_order || 0;
                document.getElementById('service-active').checked = service.is_active == 1;
            } else {
                title.textContent = 'Agregar Servicio';
                form.reset();
                document.getElementById('service-id').value = '';
                document.getElementById('service-icon').value = 'briefcase';
                document.getElementById('service-active').checked = true;
            }
            
            openModal('service-modal');
        }

        function editService(service) {
            openServiceModal(service);
        }

        document.getElementById('service-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('csrf_token', csrfToken);
            
            // Handle checkbox
            if (!document.getElementById('service-active').checked) {
                formData.delete('is_active');
            }
            
            try {
                const response = await fetch(`/${adminPath}/services/save`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message || 'Servicio guardado correctamente');
                    closeModal('service-modal');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast(data.message || 'Error al guardar', 'error');
                }
            } catch (error) {
                showToast('Error de conexión', 'error');
            }
        });

        async function deleteService(id, name) {
            if (!confirm(`¿Eliminar el servicio "${name}"?`)) return;
            
            try {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                
                const response = await fetch(`/${adminPath}/services/${id}/delete`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('Servicio eliminado');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast(data.message || 'Error al eliminar', 'error');
                }
            } catch (error) {
                showToast('Error de conexión', 'error');
            }
        }

        // ==================== SCHEDULES MANAGEMENT ====================
        
        function openScheduleModal(schedule = null) {
            const form = document.getElementById('schedule-form');
            const title = document.getElementById('schedule-modal-title');
            
            if (schedule) {
                title.textContent = 'Editar Horario';
                document.getElementById('schedule-id').value = schedule.id;
                document.getElementById('schedule-day').value = schedule.day_of_week;
                document.getElementById('schedule-start').value = schedule.start_time;
                document.getElementById('schedule-end').value = schedule.end_time;
                document.getElementById('schedule-slot-duration').value = schedule.slot_duration || 60;
                document.getElementById('schedule-active').checked = schedule.is_active == 1;
            } else {
                title.textContent = 'Agregar Horario';
                form.reset();
                document.getElementById('schedule-id').value = '';
                document.getElementById('schedule-active').checked = true;
            }
            
            openModal('schedule-modal');
        }

        function editSchedule(schedule) {
            openScheduleModal(schedule);
        }

        document.getElementById('schedule-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('csrf_token', csrfToken);
            
            // Handle checkbox
            if (!document.getElementById('schedule-active').checked) {
                formData.delete('is_active');
            }
            
            try {
                const response = await fetch(`/${adminPath}/schedules/save`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message || 'Horario guardado correctamente');
                    closeModal('schedule-modal');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast(data.message || 'Error al guardar', 'error');
                }
            } catch (error) {
                showToast('Error de conexión', 'error');
            }
        });

        async function deleteSchedule(id, dayName) {
            if (!confirm(`¿Eliminar el horario del día "${dayName}"?`)) return;
            
            try {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                
                const response = await fetch(`/${adminPath}/schedules/${id}/delete`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('Horario eliminado');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast(data.message || 'Error al eliminar', 'error');
                }
            } catch (error) {
                showToast('Error de conexión', 'error');
            }
        }

        // ==================== BLOCKED DATES MANAGEMENT ====================
        
        function openBlockedDateModal() {
            document.getElementById('blocked-date-form').reset();
            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('blocked-date-value').min = today;
            openModal('blocked-date-modal');
        }

        document.getElementById('blocked-date-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('csrf_token', csrfToken);
            
            try {
                const response = await fetch(`/${adminPath}/blocked-dates/add`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast(data.message || 'Fecha bloqueada agregada');
                    closeModal('blocked-date-modal');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast(data.message || 'Error al agregar', 'error');
                }
            } catch (error) {
                showToast('Error de conexión', 'error');
            }
        });

        async function deleteBlockedDate(id, date) {
            if (!confirm(`¿Eliminar el bloqueo de la fecha "${date}"?`)) return;
            
            try {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                
                const response = await fetch(`/${adminPath}/blocked-dates/${id}/delete`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showToast('Fecha desbloqueada');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast(data.message || 'Error al eliminar', 'error');
                }
            } catch (error) {
                showToast('Error de conexión', 'error');
            }
        }

        // ==================== TESTIMONIALS MANAGEMENT ====================

        function openTestimonialModal(testimonial = null) {
            const form = document.getElementById('testimonial-form');
            const title = document.getElementById('testimonial-modal-title');

            if (testimonial) {
                title.textContent = 'Editar Testimonio';
                document.getElementById('testimonial-id').value = testimonial.id;
                document.getElementById('testimonial-name').value = testimonial.name || '';
                document.getElementById('testimonial-company').value = testimonial.company || '';
                document.getElementById('testimonial-text').value = testimonial.text || '';
                document.getElementById('testimonial-initials').value = testimonial.initials || '';
                document.getElementById('testimonial-color').value = testimonial.color || '#4A90A4';
                document.getElementById('testimonial-sort-order').value = testimonial.sort_order || 0;
                document.getElementById('testimonial-active').checked = testimonial.is_active == 1;
            } else {
                title.textContent = 'Agregar Testimonio';
                form.reset();
                document.getElementById('testimonial-id').value = '';
                document.getElementById('testimonial-color').value = '#4A90A4';
                document.getElementById('testimonial-active').checked = true;
            }

            openModal('testimonial-modal');
        }

        function editTestimonial(testimonial) {
            openTestimonialModal(testimonial);
        }

        document.getElementById('testimonial-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            formData.append('csrf_token', csrfToken);

            if (!document.getElementById('testimonial-active').checked) {
                formData.delete('is_active');
            }

            try {
                const response = await fetch(`/${adminPath}/testimonials/save`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();
                if (data.success) {
                    showToast(data.message || 'Testimonio guardado correctamente');
                    closeModal('testimonial-modal');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast(data.message || 'Error al guardar', 'error');
                }
            } catch (error) {
                showToast('Error de conexión', 'error');
            }
        });

        async function deleteTestimonial(id, name) {
            if (!confirm(`¿Eliminar el testimonio de "${name}"?`)) return;

            try {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);

                const response = await fetch(`/${adminPath}/testimonials/${id}/delete`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();
                if (data.success) {
                    showToast('Testimonio eliminado');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast(data.message || 'Error al eliminar', 'error');
                }
            } catch (error) {
                showToast('Error de conexión', 'error');
            }
        }

        // ==================== FAQ MANAGEMENT ====================

        function openFaqModal(faq = null) {
            const form = document.getElementById('faq-form');
            const title = document.getElementById('faq-modal-title');

            if (faq) {
                title.textContent = 'Editar FAQ';
                document.getElementById('faq-id').value = faq.id;
                document.getElementById('faq-question').value = faq.question || '';
                document.getElementById('faq-answer').value = faq.answer || '';
                document.getElementById('faq-sort-order').value = faq.sort_order || 0;
                document.getElementById('faq-active').checked = faq.is_active == 1;
            } else {
                title.textContent = 'Agregar FAQ';
                form.reset();
                document.getElementById('faq-id').value = '';
                document.getElementById('faq-active').checked = true;
            }

            openModal('faq-modal');
        }

        function editFaq(faq) {
            openFaqModal(faq);
        }

        document.getElementById('faq-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            formData.append('csrf_token', csrfToken);

            if (!document.getElementById('faq-active').checked) {
                formData.delete('is_active');
            }

            try {
                const response = await fetch(`/${adminPath}/faq/save`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();
                if (data.success) {
                    showToast(data.message || 'FAQ guardado correctamente');
                    closeModal('faq-modal');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast(data.message || 'Error al guardar', 'error');
                }
            } catch (error) {
                showToast('Error de conexión', 'error');
            }
        });

        async function deleteFaq(id, question) {
            if (!confirm(`¿Eliminar FAQ: "${question}"?`)) return;

            try {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);

                const response = await fetch(`/${adminPath}/faq/${id}/delete`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();
                if (data.success) {
                    showToast('FAQ eliminado');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast(data.message || 'Error al eliminar', 'error');
                }
            } catch (error) {
                showToast('Error de conexión', 'error');
            }
        }

        // ==================== BUSINESS SETTINGS ====================

        document.getElementById('business-settings-form')?.addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch(`/${adminPath}/business-settings/save`, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const data = await response.json();
                if (data.success) {
                    showToast(data.message || 'Ajustes guardados correctamente');
                    setTimeout(() => location.reload(), 500);
                } else {
                    showToast(data.message || 'Error al guardar ajustes', 'error');
                }
            } catch (error) {
                showToast('Error de conexión', 'error');
            }
        });
    </script>
</body>
</html>
