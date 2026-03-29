/**
 * admin.js - JavaScript del Panel de Administración
 * 
 * Maneja la interactividad del dashboard, modales, 
 * actualizaciones de estado y notificaciones.
 */

'use strict';

// ============================================================
// ESTADO GLOBAL
// ============================================================

const AdminState = {
    currentDeleteTarget: null,
    deleteType: null, // 'lead' or 'appointment'
    csrfToken: null
};

// ============================================================
// INICIALIZACIÓN
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
    // Obtener CSRF token del formulario si existe
    const csrfInput = document.querySelector('input[name="csrf_token"]');
    if (csrfInput) {
        AdminState.csrfToken = csrfInput.value;
    }

    initSidebar();
    initModals();
    initStatusSelects();
    initLeadActions();
    initAppointmentActions();
    initToasts();
});

// ============================================================
// SIDEBAR MOBILE
// ============================================================

function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const openBtn = document.getElementById('open-sidebar');
    const closeBtn = document.getElementById('close-sidebar');

    if (!sidebar) return;

    // Crear overlay para móvil
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay hidden lg:hidden';
        document.body.appendChild(overlay);
    }

    const openSidebar = () => {
        sidebar.classList.add('open');
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    };

    const closeSidebar = () => {
        sidebar.classList.remove('open');
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    };

    if (openBtn) openBtn.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    overlay.addEventListener('click', closeSidebar);

    // Cerrar con Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
}

// ============================================================
// MODALES
// ============================================================

function initModals() {
    // Lead Modal
    const leadModal = document.getElementById('lead-modal');
    const closeLeadModal = document.getElementById('close-lead-modal');
    const leadModalBackdrop = document.getElementById('lead-modal-backdrop');

    if (leadModal) {
        if (closeLeadModal) closeLeadModal.addEventListener('click', () => hideModal(leadModal));
        if (leadModalBackdrop) leadModalBackdrop.addEventListener('click', () => hideModal(leadModal));
    }

    // Appointment Modal
    const appointmentModal = document.getElementById('appointment-modal');
    const closeAppointmentModal = document.getElementById('close-appointment-modal');
    const appointmentModalBackdrop = document.getElementById('appointment-modal-backdrop');

    if (appointmentModal) {
        if (closeAppointmentModal) closeAppointmentModal.addEventListener('click', () => hideModal(appointmentModal));
        if (appointmentModalBackdrop) appointmentModalBackdrop.addEventListener('click', () => hideModal(appointmentModal));
    }

    // Delete Modal
    const deleteModal = document.getElementById('delete-modal');
    const cancelDelete = document.getElementById('cancel-delete');
    const confirmDelete = document.getElementById('confirm-delete');
    const deleteModalBackdrop = document.getElementById('delete-modal-backdrop');

    if (deleteModal) {
        if (cancelDelete) cancelDelete.addEventListener('click', () => hideModal(deleteModal));
        if (deleteModalBackdrop) deleteModalBackdrop.addEventListener('click', () => hideModal(deleteModal));
        if (confirmDelete) confirmDelete.addEventListener('click', handleDelete);
    }

    // Cerrar modales con Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('#lead-modal.show, #appointment-modal.show, #delete-modal.show').forEach(modal => {
                hideModal(modal);
            });
        }
    });
}

function showModal(modal) {
    if (!modal) return;
    modal.classList.remove('hidden');
    // Trigger reflow for animation
    modal.offsetHeight;
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function hideModal(modal) {
    if (!modal) return;
    modal.classList.remove('show');
    setTimeout(() => {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }, 200);
}

// ============================================================
// STATUS SELECTS
// ============================================================

function initStatusSelects() {
    // Lead status selects
    document.querySelectorAll('.lead-status-select').forEach(select => {
        select.addEventListener('change', async (e) => {
            const leadId = e.target.dataset.leadId;
            const newStatus = e.target.value;
            await updateLeadStatus(leadId, newStatus, e.target);
        });
    });

    // Appointment status selects
    document.querySelectorAll('.appointment-status-select').forEach(select => {
        select.addEventListener('change', async (e) => {
            const appointmentId = e.target.dataset.appointmentId;
            const newStatus = e.target.value;
            await updateAppointmentStatus(appointmentId, newStatus, e.target);
        });
    });
}

async function updateLeadStatus(leadId, status, selectElement) {
    const originalValue = selectElement.dataset.originalValue || selectElement.value;
    selectElement.dataset.originalValue = originalValue;
    selectElement.disabled = true;

    try {
        const formData = new FormData();
        formData.append('id', leadId);
        formData.append('status', status);
        formData.append('csrf_token', AdminState.csrfToken || '');

        const response = await fetch('/api/admin/leads/status', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            showToast('Estado actualizado correctamente', 'success');
            updateSelectStyles(selectElement, status, 'lead');
        } else {
            selectElement.value = originalValue;
            showToast(data.message || 'Error al actualizar el estado', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        selectElement.value = originalValue;
        showToast('Error de conexión', 'error');
    } finally {
        selectElement.disabled = false;
    }
}

async function updateAppointmentStatus(appointmentId, status, selectElement) {
    const originalValue = selectElement.dataset.originalValue || selectElement.value;
    selectElement.dataset.originalValue = originalValue;
    selectElement.disabled = true;

    try {
        const formData = new FormData();
        formData.append('id', appointmentId);
        formData.append('status', status);
        formData.append('csrf_token', AdminState.csrfToken || '');

        const response = await fetch('/api/admin/appointments/status', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            showToast('Estado actualizado correctamente', 'success');
            updateSelectStyles(selectElement, status, 'appointment');
        } else {
            selectElement.value = originalValue;
            showToast(data.message || 'Error al actualizar el estado', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        selectElement.value = originalValue;
        showToast('Error de conexión', 'error');
    } finally {
        selectElement.disabled = false;
    }
}

function updateSelectStyles(select, status, type) {
    // Remove all status classes
    select.classList.remove(
        'bg-blue-100', 'text-blue-700',
        'bg-yellow-100', 'text-yellow-700',
        'bg-purple-100', 'text-purple-700',
        'bg-green-100', 'text-green-700',
        'bg-red-100', 'text-red-700',
        'bg-gray-100', 'text-gray-700'
    );

    // Add new status classes
    if (type === 'lead') {
        const classes = {
            'new': ['bg-blue-100', 'text-blue-700'],
            'contacted': ['bg-yellow-100', 'text-yellow-700'],
            'qualified': ['bg-purple-100', 'text-purple-700'],
            'converted': ['bg-green-100', 'text-green-700'],
            'lost': ['bg-gray-100', 'text-gray-700']
        };
        select.classList.add(...(classes[status] || ['bg-gray-100', 'text-gray-700']));
    } else {
        const classes = {
            'pending': ['bg-yellow-100', 'text-yellow-700'],
            'confirmed': ['bg-blue-100', 'text-blue-700'],
            'completed': ['bg-green-100', 'text-green-700'],
            'cancelled': ['bg-red-100', 'text-red-700'],
            'no_show': ['bg-gray-100', 'text-gray-700']
        };
        select.classList.add(...(classes[status] || ['bg-gray-100', 'text-gray-700']));
    }
}

// ============================================================
// LEAD ACTIONS
// ============================================================

function initLeadActions() {
    // View lead buttons
    document.querySelectorAll('.view-lead-btn').forEach(btn => {
        btn.addEventListener('click', () => viewLead(btn.dataset.leadId));
    });

    // Delete lead buttons
    document.querySelectorAll('.delete-lead-btn').forEach(btn => {
        btn.addEventListener('click', () => confirmDeleteLead(btn.dataset.leadId));
    });
}

async function viewLead(leadId) {
    const modal = document.getElementById('lead-modal');
    const content = document.getElementById('lead-modal-content');
    
    if (!modal || !content) return;

    content.innerHTML = '<div class="flex justify-center py-8"><div class="spinner text-primary"></div></div>';
    showModal(modal);

    try {
        const response = await fetch(`/api/admin/leads/${leadId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success && data.data) {
            const lead = data.data;
            content.innerHTML = `
                <div class="space-y-1">
                    <div class="detail-row">
                        <span class="detail-label">Nombre</span>
                        <span class="detail-value">${escapeHtml(lead.name)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email</span>
                        <span class="detail-value">
                            <a href="mailto:${escapeHtml(lead.email)}" class="text-primary hover:underline">${escapeHtml(lead.email)}</a>
                        </span>
                    </div>
                    ${lead.phone ? `
                    <div class="detail-row">
                        <span class="detail-label">Teléfono</span>
                        <span class="detail-value">
                            <a href="tel:${escapeHtml(lead.phone)}" class="text-primary hover:underline">${escapeHtml(lead.phone)}</a>
                        </span>
                    </div>
                    ` : ''}
                    ${lead.service ? `
                    <div class="detail-row">
                        <span class="detail-label">Servicio</span>
                        <span class="detail-value">${escapeHtml(lead.service)}</span>
                    </div>
                    ` : ''}
                    <div class="detail-row">
                        <span class="detail-label">Estado</span>
                        <span class="detail-value">${getStatusBadge(lead.status, 'lead')}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Fecha</span>
                        <span class="detail-value">${formatDateTime(lead.created_at)}</span>
                    </div>
                    ${lead.message ? `
                    <div class="detail-row flex-col">
                        <span class="detail-label mb-2">Mensaje</span>
                        <span class="detail-value message">${escapeHtml(lead.message)}</span>
                    </div>
                    ` : ''}
                </div>
            `;
        } else {
            content.innerHTML = '<p class="text-center text-red-500">Error al cargar los datos</p>';
        }
    } catch (error) {
        console.error('Error:', error);
        content.innerHTML = '<p class="text-center text-red-500">Error de conexión</p>';
    }

    // Reinitialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function confirmDeleteLead(leadId) {
    AdminState.currentDeleteTarget = leadId;
    AdminState.deleteType = 'lead';
    
    const modal = document.getElementById('delete-modal');
    const message = document.getElementById('delete-modal-message');
    
    if (message) {
        message.textContent = '¿Estás seguro de que deseas eliminar este lead? Esta acción no se puede deshacer.';
    }
    
    showModal(modal);
}

// ============================================================
// APPOINTMENT ACTIONS
// ============================================================

function initAppointmentActions() {
    // View appointment buttons
    document.querySelectorAll('.view-appointment-btn').forEach(btn => {
        btn.addEventListener('click', () => viewAppointment(btn.dataset.appointmentId));
    });

    // Delete appointment buttons
    document.querySelectorAll('.delete-appointment-btn').forEach(btn => {
        btn.addEventListener('click', () => confirmDeleteAppointment(btn.dataset.appointmentId));
    });
}

async function viewAppointment(appointmentId) {
    const modal = document.getElementById('appointment-modal');
    const content = document.getElementById('appointment-modal-content');
    
    if (!modal || !content) return;

    content.innerHTML = '<div class="flex justify-center py-8"><div class="spinner text-primary"></div></div>';
    showModal(modal);

    try {
        const response = await fetch(`/api/admin/appointments/${appointmentId}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success && data.data) {
            const apt = data.data;
            content.innerHTML = `
                <div class="space-y-1">
                    <div class="detail-row">
                        <span class="detail-label">Nombre</span>
                        <span class="detail-value">${escapeHtml(apt.name)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email</span>
                        <span class="detail-value">
                            <a href="mailto:${escapeHtml(apt.email)}" class="text-primary hover:underline">${escapeHtml(apt.email)}</a>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Teléfono</span>
                        <span class="detail-value">
                            <a href="tel:${escapeHtml(apt.phone)}" class="text-primary hover:underline">${escapeHtml(apt.phone)}</a>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Fecha</span>
                        <span class="detail-value">${formatDate(apt.appointment_date)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Hora</span>
                        <span class="detail-value">${escapeHtml(apt.appointment_time)}</span>
                    </div>
                    ${apt.service ? `
                    <div class="detail-row">
                        <span class="detail-label">Servicio</span>
                        <span class="detail-value">${escapeHtml(apt.service)}</span>
                    </div>
                    ` : ''}
                    <div class="detail-row">
                        <span class="detail-label">Estado</span>
                        <span class="detail-value">${getStatusBadge(apt.status, 'appointment')}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Registrado</span>
                        <span class="detail-value">${formatDateTime(apt.created_at)}</span>
                    </div>
                    ${apt.notes ? `
                    <div class="detail-row flex-col">
                        <span class="detail-label mb-2">Notas</span>
                        <span class="detail-value message">${escapeHtml(apt.notes)}</span>
                    </div>
                    ` : ''}
                </div>
            `;
        } else {
            content.innerHTML = '<p class="text-center text-red-500">Error al cargar los datos</p>';
        }
    } catch (error) {
        console.error('Error:', error);
        content.innerHTML = '<p class="text-center text-red-500">Error de conexión</p>';
    }

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function confirmDeleteAppointment(appointmentId) {
    AdminState.currentDeleteTarget = appointmentId;
    AdminState.deleteType = 'appointment';
    
    const modal = document.getElementById('delete-modal');
    const message = document.getElementById('delete-modal-message');
    
    if (message) {
        message.textContent = '¿Estás seguro de que deseas eliminar esta cita? Esta acción no se puede deshacer.';
    }
    
    showModal(modal);
}

// ============================================================
// DELETE HANDLER
// ============================================================

async function handleDelete() {
    if (!AdminState.currentDeleteTarget || !AdminState.deleteType) return;

    const confirmBtn = document.getElementById('confirm-delete');
    if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<span class="spinner inline-block mr-2"></span> Eliminando...';
    }

    try {
        const endpoint = AdminState.deleteType === 'lead' 
            ? `/api/admin/leads/${AdminState.currentDeleteTarget}/delete`
            : `/api/admin/appointments/${AdminState.currentDeleteTarget}/delete`;

        const formData = new FormData();
        formData.append('csrf_token', AdminState.csrfToken || '');

        const response = await fetch(endpoint, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            showToast('Registro eliminado correctamente', 'success');
            hideModal(document.getElementById('delete-modal'));
            
            // Remove row from table
            const selector = AdminState.deleteType === 'lead' 
                ? `.delete-lead-btn[data-lead-id="${AdminState.currentDeleteTarget}"]`
                : `.delete-appointment-btn[data-appointment-id="${AdminState.currentDeleteTarget}"]`;
            
            const btn = document.querySelector(selector);
            if (btn) {
                const row = btn.closest('tr');
                if (row) {
                    row.style.opacity = '0';
                    row.style.transform = 'translateX(20px)';
                    row.style.transition = 'all 0.3s ease';
                    setTimeout(() => row.remove(), 300);
                }
            }
        } else {
            showToast(data.message || 'Error al eliminar el registro', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error de conexión', 'error');
    } finally {
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = 'Eliminar';
        }
        AdminState.currentDeleteTarget = null;
        AdminState.deleteType = null;
    }
}

// ============================================================
// TOAST NOTIFICATIONS
// ============================================================

function initToasts() {
    // Create toast container if it doesn't exist
    if (!document.querySelector('.toast-container')) {
        const container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
}

function showToast(message, type = 'info', duration = 4000) {
    const container = document.querySelector('.toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icons = {
        success: 'check-circle',
        error: 'x-circle',
        warning: 'alert-triangle',
        info: 'info'
    };

    toast.innerHTML = `
        <i data-lucide="${icons[type] || 'info'}" class="w-5 h-5 flex-shrink-0"></i>
        <span>${escapeHtml(message)}</span>
        <button class="ml-auto text-current opacity-60 hover:opacity-100" onclick="this.parentElement.remove()">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    `;

    container.appendChild(toast);

    // Initialize Lucide icons in toast
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    // Auto-remove after duration
    setTimeout(() => {
        toast.classList.add('hiding');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// ============================================================
// UTILITY FUNCTIONS
// ============================================================

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString + 'T12:00:00');
    return date.toLocaleDateString('es-MX', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatDateTime(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('es-MX', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getStatusBadge(status, type) {
    if (type === 'lead') {
        const labels = {
            'new': ['Nuevo', 'bg-blue-100 text-blue-700'],
            'contacted': ['Contactado', 'bg-yellow-100 text-yellow-700'],
            'qualified': ['Calificado', 'bg-purple-100 text-purple-700'],
            'converted': ['Convertido', 'bg-green-100 text-green-700'],
            'lost': ['Perdido', 'bg-gray-100 text-gray-700']
        };
        const [label, classes] = labels[status] || [status, 'bg-gray-100 text-gray-700'];
        return `<span class="px-3 py-1 rounded-full text-sm ${classes}">${label}</span>`;
    } else {
        const labels = {
            'pending': ['Pendiente', 'bg-yellow-100 text-yellow-700'],
            'confirmed': ['Confirmada', 'bg-blue-100 text-blue-700'],
            'completed': ['Completada', 'bg-green-100 text-green-700'],
            'cancelled': ['Cancelada', 'bg-red-100 text-red-700'],
            'no_show': ['No asistió', 'bg-gray-100 text-gray-700']
        };
        const [label, classes] = labels[status] || [status, 'bg-gray-100 text-gray-700'];
        return `<span class="px-3 py-1 rounded-full text-sm ${classes}">${label}</span>`;
    }
}
