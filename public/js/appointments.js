/**
 * appointments.js - Sistema de Agendamiento de Citas
 * 
 * Maneja la selección de fecha, carga de slots disponibles,
 * y envío del formulario de citas con validación completa.
 */

'use strict';

// Estado global del módulo de citas
const AppointmentState = {
    selectedDate: null,
    selectedSlot: null,
    availableSlots: [],
    isLoading: false,
    minDate: null,
    maxDate: null
};

// Esperar a que el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    initAppointmentSystem();
});

/**
 * Inicializar el sistema de agendamiento
 */
function initAppointmentSystem() {
    const form = document.getElementById('appointment-form');
    if (!form) return;

    // Configurar fechas mínima y máxima
    const today = new Date();
    AppointmentState.minDate = new Date(today);
    AppointmentState.minDate.setDate(today.getDate() + 1); // Mínimo mañana
    
    AppointmentState.maxDate = new Date(today);
    AppointmentState.maxDate.setDate(today.getDate() + 60); // Máximo 60 días

    // Inicializar selector de fecha
    initDatePicker();
    
    // Inicializar validación del formulario
    initFormValidation(form);
    
    // Manejar envío del formulario
    initFormSubmission(form);
}

/**
 * Inicializar el selector de fecha
 */
function initDatePicker() {
    const dateInput = document.getElementById('appt-date') || document.getElementById('appointment-date');
    if (!dateInput) return;

    // Configurar atributos del input de fecha
    dateInput.min = formatDateForInput(AppointmentState.minDate);
    dateInput.max = formatDateForInput(AppointmentState.maxDate);
    
    // Manejar cambio de fecha
    dateInput.addEventListener('change', async (e) => {
        const selectedDate = e.target.value;
        if (!selectedDate) return;

        AppointmentState.selectedDate = selectedDate;
        AppointmentState.selectedSlot = null;
        
        // Cargar slots disponibles
        await loadAvailableSlots(selectedDate);
    });
}

/**
 * Obtener días disponibles de la configuración
 */
function getAvailableDays() {
    // Intentar obtener de data attribute o variable global
    const container = document.getElementById('appointment-slots-container');
    if (container && container.dataset.availableDays) {
        return JSON.parse(container.dataset.availableDays);
    }
    // Por defecto: Lunes a Viernes
    return [1, 2, 3, 4, 5];
}

/**
 * Cargar slots disponibles para una fecha
 */
async function loadAvailableSlots(date) {
    const slotsContainer = document.getElementById('time-slots') || document.getElementById('appointment-slots');
    const slotsLoading = document.getElementById('slots-loading');
    
    if (!slotsContainer) return;

    // Mostrar loading
    AppointmentState.isLoading = true;
    if (slotsLoading) slotsLoading.classList.remove('hidden');
    slotsContainer.innerHTML = '';
    clearSlotsMessage();

    try {
        // Obtener CSRF token
        const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '';
        
        const formData = new FormData();
        formData.append('date', date);
        formData.append('csrf_token', csrfToken);

        const response = await fetch('/api/appointments/slots', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success && data.data && data.data.slots) {
            AppointmentState.availableSlots = data.data.slots;
            renderSlots(data.data.slots);
        } else {
            showSlotsMessage(data.message || 'No hay horarios disponibles para esta fecha.', 'info');
            AppointmentState.availableSlots = [];
        }
    } catch (error) {
        console.error('Error cargando slots:', error);
        showSlotsMessage('Error al cargar los horarios. Por favor, intenta de nuevo.', 'error');
        AppointmentState.availableSlots = [];
    } finally {
        AppointmentState.isLoading = false;
        if (slotsLoading) slotsLoading.classList.add('hidden');
    }
}

/**
 * Renderizar los slots disponibles
 */
function renderSlots(slots) {
    const container = document.getElementById('time-slots') || document.getElementById('appointment-slots');
    if (!container) return;

    if (!slots || slots.length === 0) {
        showSlotsMessage('No hay horarios disponibles para esta fecha.', 'info');
        return;
    }

    container.innerHTML = '';

    slots.forEach(slot => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'slot-button px-4 py-2 border-2 rounded-lg text-sm font-medium transition-all duration-200 ' +
            'border-gray-300 bg-white text-gray-700 hover:border-primary hover:bg-primary/5 ' +
            'focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2';
        button.textContent = slot;
        button.dataset.slot = slot;

        button.addEventListener('click', () => selectSlot(slot, button));
        container.appendChild(button);
    });
}

/**
 * Seleccionar un slot de tiempo
 */
function selectSlot(slot, button) {
    // Remover selección previa
    const allButtons = document.querySelectorAll('.slot-button');
    allButtons.forEach(btn => {
        btn.classList.remove('border-primary', 'bg-primary', 'text-white');
        btn.classList.add('border-gray-300', 'bg-white', 'text-gray-700');
    });

    // Marcar el nuevo slot como seleccionado
    button.classList.remove('border-gray-300', 'bg-white', 'text-gray-700');
    button.classList.add('border-primary', 'bg-primary', 'text-white');

    // Actualizar estado y campo oculto
    AppointmentState.selectedSlot = slot;
    
    const timeInput = document.getElementById('appt-time') || document.getElementById('appointment-time');
    if (timeInput) {
        timeInput.value = slot;
    }

    // Limpiar error de validación si existía
    const slotError = document.querySelector('.slot-error');
    if (slotError) slotError.remove();
}

/**
 * Limpiar los slots
 */
function clearSlots() {
    const container = document.getElementById('time-slots') || document.getElementById('appointment-slots');
    if (container) {
        container.innerHTML = '<p class="text-gray-400 text-sm py-3">Selecciona primero una fecha</p>';
    }
    AppointmentState.selectedSlot = null;
    AppointmentState.availableSlots = [];
    
    const timeInput = document.getElementById('appt-time') || document.getElementById('appointment-time');
    if (timeInput) {
        timeInput.value = '';
    }
}

/**
 * Mostrar mensaje en la sección de slots
 */
function showSlotsMessage(message, type = 'info') {
    clearSlotsMessage();

    const container = document.getElementById('time-slots') || document.getElementById('appointment-slots-container');
    if (!container) return;

    const messageDiv = document.createElement('div');
    messageDiv.className = 'slots-message p-4 rounded-lg text-center text-sm';
    
    if (type === 'error') {
        messageDiv.classList.add('bg-red-50', 'text-red-700', 'border', 'border-red-200');
    } else if (type === 'warning') {
        messageDiv.classList.add('bg-yellow-50', 'text-yellow-700', 'border', 'border-yellow-200');
    } else {
        messageDiv.classList.add('bg-blue-50', 'text-blue-700', 'border', 'border-blue-200');
    }

    messageDiv.textContent = message;
    container.appendChild(messageDiv);
}

/**
 * Limpiar mensaje de slots
 */
function clearSlotsMessage() {
    const message = document.querySelector('.slots-message');
    if (message) message.remove();
}

/**
 * Inicializar validación del formulario
 */
function initFormValidation(form) {
    const inputs = form.querySelectorAll('input:not([type="hidden"]), textarea, select');
    
    inputs.forEach(input => {
        input.addEventListener('blur', () => validateAppointmentField(input));
        input.addEventListener('input', () => {
            if (input.classList.contains('border-red-500')) {
                clearAppointmentFieldError(input);
            }
        });
    });
}

/**
 * Validar campo del formulario de citas
 */
function validateAppointmentField(input) {
    const name = input.name;
    const value = input.value.trim();

    // Ignorar campos ocultos y honeypot
    if (input.type === 'hidden' || input.classList.contains('honeypot-field')) {
        return true;
    }

    const rules = {
        name: {
            required: true,
            minLength: 2,
            maxLength: 100,
            message: 'El nombre debe tener entre 2 y 100 caracteres'
        },
        email: {
            required: true,
            pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            message: 'Ingresa un correo electrónico válido'
        },
        phone: {
            required: true,
            pattern: /^[\d\s\-+()]{7,20}$/,
            message: 'Ingresa un teléfono válido (7-20 dígitos)'
        },
        date: {
            required: true,
            message: 'Selecciona una fecha para la cita'
        },
        appointment_date: {
            required: true,
            message: 'Selecciona una fecha para la cita'
        },
        service: {
            required: false,
            message: 'Selecciona el servicio de interés'
        },
        service_interest: {
            required: true,
            message: 'Selecciona el servicio de interés'
        },
        notes: {
            required: false,
            maxLength: 1000,
            message: 'Las notas no pueden exceder 1000 caracteres'
        },
        message: {
            required: false,
            maxLength: 1000,
            message: 'Las notas no pueden exceder 1000 caracteres'
        }
    };

    const rule = rules[name];
    if (!rule) return true;

    // Validar campo requerido
    if (rule.required && !value) {
        showAppointmentFieldError(input, 'Este campo es obligatorio');
        return false;
    }

    // Si está vacío y no es requerido, es válido
    if (!value && !rule.required) {
        clearAppointmentFieldError(input);
        return true;
    }

    // Validar longitud
    if (rule.minLength && value.length < rule.minLength) {
        showAppointmentFieldError(input, rule.message);
        return false;
    }

    if (rule.maxLength && value.length > rule.maxLength) {
        showAppointmentFieldError(input, rule.message);
        return false;
    }

    // Validar patrón
    if (rule.pattern && !rule.pattern.test(value)) {
        showAppointmentFieldError(input, rule.message);
        return false;
    }

    clearAppointmentFieldError(input);
    return true;
}

/**
 * Mostrar error en campo
 */
function showAppointmentFieldError(input, message) {
    input.classList.remove('border-gray-300', 'border-green-500');
    input.classList.add('border-red-500');
    
    let errorDiv = input.parentElement.querySelector('.field-error');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'field-error text-red-600 text-sm mt-1';
        input.parentElement.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
}

/**
 * Limpiar error de campo
 */
function clearAppointmentFieldError(input) {
    input.classList.remove('border-red-500');
    input.classList.add('border-gray-300');
    
    const errorDiv = input.parentElement.querySelector('.field-error');
    if (errorDiv) errorDiv.remove();
}

/**
 * Inicializar envío del formulario
 */
function initFormSubmission(form) {
    const submitBtn = form.querySelector('button[type="submit"]');
    let isSubmitting = false;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (isSubmitting) return;

        // Validar todos los campos
        let isValid = true;
        const inputs = form.querySelectorAll('input:not([type="hidden"]), textarea, select');
        inputs.forEach(input => {
            if (!validateAppointmentField(input)) {
                isValid = false;
            }
        });

        // Validar que se haya seleccionado un slot
        if (!AppointmentState.selectedSlot) {
            showSlotsError('Por favor, selecciona un horario disponible');
            isValid = false;
        }

        if (!isValid) {
            showAppointmentMessage(form, 'Por favor, corrige los errores en el formulario.', 'error');
            return;
        }

        // Iniciar envío
        isSubmitting = true;
        setAppointmentButtonLoading(submitBtn, true);
        clearAppointmentMessage(form);

        try {
            const formData = new FormData(form);
            
            // Asegurar que el time está incluido
            if (AppointmentState.selectedSlot) {
                formData.set('appointment_time', AppointmentState.selectedSlot);
            }

            const response = await fetch('/api/appointments', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                // Éxito
                showAppointmentMessage(form, data.message || '¡Cita agendada con éxito! Te contactaremos para confirmar.', 'success');
                form.reset();
                clearSlots();
                
                // Tracking de conversión
                if (typeof gtag === 'function') {
                    gtag('event', 'appointment_submit', {
                        event_category: 'conversions',
                        event_label: 'appointment_form'
                    });
                }

                // Actualizar CSRF token
                if (data.new_csrf_token) {
                    const csrfInput = form.querySelector('input[name="csrf_token"]');
                    if (csrfInput) {
                        csrfInput.value = data.new_csrf_token;
                    }
                }
            } else {
                // Error
                if (data.errors && typeof data.errors === 'object') {
                    Object.entries(data.errors).forEach(([field, message]) => {
                        const input = form.querySelector(`[name="${field}"]`);
                        if (input) {
                            showAppointmentFieldError(input, message);
                        }
                    });
                    showAppointmentMessage(form, 'Por favor, corrige los errores indicados.', 'error');
                } else {
                    showAppointmentMessage(form, data.message || 'Error al agendar la cita. Intenta de nuevo.', 'error');
                }
            }
        } catch (error) {
            console.error('Error en envío:', error);
            showAppointmentMessage(form, 'Error de conexión. Por favor, intenta de nuevo más tarde.', 'error');
        } finally {
            isSubmitting = false;
            setAppointmentButtonLoading(submitBtn, false);
        }
    });
}

/**
 * Mostrar error de slots
 */
function showSlotsError(message) {
    const container = document.getElementById('time-slots') || document.getElementById('appointment-slots-container');
    if (!container) return;

    let errorDiv = container.querySelector('.slot-error');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'slot-error text-red-600 text-sm mt-2';
        container.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
}

/**
 * Mostrar mensaje del formulario
 */
function showAppointmentMessage(form, message, type = 'info') {
    let messageDiv = document.getElementById('appointment-message-result') || form.querySelector('.appointment-message');
    if (!messageDiv) {
        messageDiv = document.createElement('div');
        messageDiv.className = 'appointment-message mt-4 p-4 rounded-lg text-center';
        form.appendChild(messageDiv);
    }

    messageDiv.className = 'appointment-message mt-4 p-4 rounded-lg text-center';
    
    if (type === 'success') {
        messageDiv.classList.add('bg-green-100', 'text-green-800', 'border', 'border-green-300');
    } else if (type === 'error') {
        messageDiv.classList.add('bg-red-100', 'text-red-800', 'border', 'border-red-300');
    } else {
        messageDiv.classList.add('bg-blue-100', 'text-blue-800', 'border', 'border-blue-300');
    }

    messageDiv.textContent = message;
    messageDiv.classList.remove('hidden');
    messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

/**
 * Limpiar mensaje del formulario
 */
function clearAppointmentMessage(form) {
    const resultDiv = document.getElementById('appointment-message-result');
    if (resultDiv) {
        resultDiv.classList.add('hidden');
        resultDiv.textContent = '';
        resultDiv.className = 'hidden mt-4 p-4 rounded-lg text-center';
    }

    const messageDiv = form.querySelector('.appointment-message');
    if (messageDiv) messageDiv.remove();
}

/**
 * Establecer estado de carga del botón
 */
function setAppointmentButtonLoading(button, loading) {
    if (!button) return;
    
    if (loading) {
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Agendando...
        `;
        button.classList.add('opacity-75', 'cursor-not-allowed');
    } else {
        button.disabled = false;
        if (button.dataset.originalText) {
            button.innerHTML = button.dataset.originalText;
        }
        button.classList.remove('opacity-75', 'cursor-not-allowed');
    }
}

/**
 * Formatear fecha para input date
 */
function formatDateForInput(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * Formatear fecha para mostrar
 */
function formatDateForDisplay(dateString) {
    const date = new Date(dateString + 'T12:00:00');
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('es-MX', options);
}
