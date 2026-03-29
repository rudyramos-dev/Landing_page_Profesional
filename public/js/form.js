/**
 * form.js - Formulario de Contacto
 * 
 * Maneja la validación y envío AJAX del formulario de contacto.
 * Incluye validación en tiempo real, honeypot field y feedback visual.
 */

'use strict';

// Esperar a que el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    initContactForm();
});

/**
 * Inicializar el formulario de contacto
 */
function initContactForm() {
    const form = document.getElementById('contact-form');
    if (!form) return;

    // Referencias a elementos
    const submitBtn = form.querySelector('button[type="submit"]');
    const inputs = form.querySelectorAll('input, textarea, select');
    
    // Estado del formulario
    let isSubmitting = false;

    // Validación en tiempo real para cada campo
    inputs.forEach(input => {
        // Validar al perder el foco
        input.addEventListener('blur', () => validateField(input));
        
        // Limpiar error al escribir
        input.addEventListener('input', () => {
            if (input.classList.contains('border-red-500')) {
                clearFieldError(input);
            }
        });
    });

    // Manejar envío del formulario
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Prevenir múltiples envíos
        if (isSubmitting) return;

        // Validar todos los campos
        let isValid = true;
        inputs.forEach(input => {
            if (!validateField(input)) {
                isValid = false;
            }
        });

        if (!isValid) {
            showFormMessage(form, 'Por favor, corrige los errores en el formulario.', 'error');
            return;
        }

        // Iniciar envío
        isSubmitting = true;
        setButtonLoading(submitBtn, true);
        clearFormMessage(form);

        try {
            const formData = new FormData(form);
            
            const response = await fetch('/api/leads', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (data.success) {
                // Éxito
                showFormMessage(form, data.message || '¡Mensaje enviado con éxito! Nos pondremos en contacto pronto.', 'success');
                form.reset();
                
                // Tracking de conversión (si existe)
                if (typeof gtag === 'function') {
                    gtag('event', 'lead_form_submit', {
                        event_category: 'conversions',
                        event_label: 'contact_form'
                    });
                }

                // Regenerar token CSRF si está disponible
                if (data.new_csrf_token) {
                    const csrfInput = form.querySelector('input[name="csrf_token"]');
                    if (csrfInput) {
                        csrfInput.value = data.new_csrf_token;
                    }
                }
            } else {
                // Error de validación o servidor
                if (data.errors && typeof data.errors === 'object') {
                    // Mostrar errores específicos por campo
                    Object.entries(data.errors).forEach(([field, message]) => {
                        const input = form.querySelector(`[name="${field}"]`);
                        if (input) {
                            showFieldError(input, message);
                        }
                    });
                    showFormMessage(form, 'Por favor, corrige los errores indicados.', 'error');
                } else {
                    showFormMessage(form, data.message || 'Error al enviar el mensaje. Intenta de nuevo.', 'error');
                }
            }
        } catch (error) {
            console.error('Error en envío:', error);
            showFormMessage(form, 'Error de conexión. Por favor, intenta de nuevo más tarde.', 'error');
        } finally {
            isSubmitting = false;
            setButtonLoading(submitBtn, false);
        }
    });
}

/**
 * Validar un campo individual
 */
function validateField(input) {
    const name = input.name;
    const value = input.value.trim();
    
    // Ignorar campos ocultos y honeypot
    if (input.type === 'hidden' || input.classList.contains('honeypot-field')) {
        return true;
    }

    // Reglas de validación por campo
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
            required: false,
            pattern: /^[\d\s\-+()]{7,20}$/,
            message: 'Ingresa un teléfono válido (7-20 dígitos)'
        },
        message: {
            required: true,
            minLength: 10,
            maxLength: 2000,
            message: 'El mensaje debe tener entre 10 y 2000 caracteres'
        },
        service: {
            required: false,
            message: 'Selecciona un servicio'
        }
    };

    const rule = rules[name];
    if (!rule) return true; // Campo sin reglas definidas

    // Validar campo requerido
    if (rule.required && !value) {
        showFieldError(input, 'Este campo es obligatorio');
        return false;
    }

    // Si el campo está vacío y no es requerido, es válido
    if (!value && !rule.required) {
        clearFieldError(input);
        return true;
    }

    // Validar longitud mínima
    if (rule.minLength && value.length < rule.minLength) {
        showFieldError(input, rule.message);
        return false;
    }

    // Validar longitud máxima
    if (rule.maxLength && value.length > rule.maxLength) {
        showFieldError(input, rule.message);
        return false;
    }

    // Validar patrón
    if (rule.pattern && !rule.pattern.test(value)) {
        showFieldError(input, rule.message);
        return false;
    }

    // Campo válido
    clearFieldError(input);
    showFieldSuccess(input);
    return true;
}

/**
 * Mostrar error en un campo
 */
function showFieldError(input, message) {
    // Aplicar estilos de error
    input.classList.remove('border-gray-300', 'border-green-500', 'focus:ring-green-500');
    input.classList.add('border-red-500', 'focus:ring-red-500');
    
    // Buscar o crear contenedor de error
    let errorDiv = input.parentElement.querySelector('.field-error');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'field-error text-red-600 text-sm mt-1';
        input.parentElement.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
}

/**
 * Limpiar error de un campo
 */
function clearFieldError(input) {
    input.classList.remove('border-red-500', 'focus:ring-red-500');
    input.classList.add('border-gray-300');
    
    const errorDiv = input.parentElement.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

/**
 * Mostrar éxito en un campo
 */
function showFieldSuccess(input) {
    input.classList.remove('border-red-500', 'border-gray-300');
    input.classList.add('border-green-500');
}

/**
 * Mostrar mensaje general del formulario
 */
function showFormMessage(form, message, type = 'info') {
    // Buscar o crear contenedor de mensaje
    let messageDiv = form.querySelector('.form-message');
    if (!messageDiv) {
        messageDiv = document.createElement('div');
        messageDiv.className = 'form-message mt-4 p-4 rounded-lg text-center';
        form.appendChild(messageDiv);
    }

    // Aplicar estilos según tipo
    messageDiv.className = 'form-message mt-4 p-4 rounded-lg text-center';
    
    if (type === 'success') {
        messageDiv.classList.add('bg-green-100', 'text-green-800', 'border', 'border-green-300');
    } else if (type === 'error') {
        messageDiv.classList.add('bg-red-100', 'text-red-800', 'border', 'border-red-300');
    } else {
        messageDiv.classList.add('bg-blue-100', 'text-blue-800', 'border', 'border-blue-300');
    }

    messageDiv.textContent = message;
    
    // Scroll al mensaje si está fuera de vista
    messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

/**
 * Limpiar mensaje del formulario
 */
function clearFormMessage(form) {
    const messageDiv = form.querySelector('.form-message');
    if (messageDiv) {
        messageDiv.remove();
    }
}

/**
 * Establecer estado de carga del botón
 */
function setButtonLoading(button, loading) {
    if (!button) return;
    
    if (loading) {
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = `
            <svg class="animate-spin -ml-1 mr-2 h-5 w-5 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Enviando...
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
 * Formatear número de teléfono mientras se escribe
 */
function formatPhoneInput(input) {
    input.addEventListener('input', (e) => {
        // Permitir solo dígitos, espacios, guiones, paréntesis y +
        let value = e.target.value.replace(/[^\d\s\-+()]/g, '');
        e.target.value = value;
    });
}

// Aplicar formato de teléfono si existe el campo
document.addEventListener('DOMContentLoaded', () => {
    const phoneInput = document.querySelector('input[name="phone"]');
    if (phoneInput) {
        formatPhoneInput(phoneInput);
    }
});
