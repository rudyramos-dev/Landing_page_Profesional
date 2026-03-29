/**
 * app.js - JavaScript principal de la landing page
 * 
 * Maneja: navegación, scroll effects, modales, y utilidades globales.
 */

(function() {
    'use strict';

    // ============================================================
    // NAVBAR SCROLL EFFECT
    // ============================================================

    const navbar = document.getElementById('navbar');
    let lastScroll = 0;

    function handleNavbarScroll() {
        const currentScroll = window.pageYOffset;

        if (currentScroll > 50) {
            navbar.classList.add('navbar-scrolled');
        } else {
            navbar.classList.remove('navbar-scrolled');
        }

        lastScroll = currentScroll;
    }

    // Usar IntersectionObserver para mejor rendimiento
    const heroSection = document.getElementById('hero');
    
    if (heroSection && navbar) {
        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && entry.intersectionRatio > 0.1) {
                        navbar.classList.remove('navbar-scrolled');
                    } else {
                        navbar.classList.add('navbar-scrolled');
                    }
                });
            },
            { threshold: [0.1] }
        );

        observer.observe(heroSection);
    }

    // Fallback con scroll event
    window.addEventListener('scroll', handleNavbarScroll, { passive: true });

    // ============================================================
    // MOBILE MENU
    // ============================================================

    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenuClose = document.getElementById('mobile-menu-close');
    const mobileMenu = document.getElementById('mobile-menu');

    function openMobileMenu() {
        mobileMenu.classList.add('open');
        document.body.style.overflow = 'hidden';
        mobileMenuBtn.setAttribute('aria-expanded', 'true');
    }

    function closeMobileMenu() {
        mobileMenu.classList.remove('open');
        document.body.style.overflow = '';
        mobileMenuBtn.setAttribute('aria-expanded', 'false');
    }

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', openMobileMenu);
    }

    if (mobileMenuClose) {
        mobileMenuClose.addEventListener('click', closeMobileMenu);
    }

    // Cerrar al hacer clic en enlaces del menú móvil
    const mobileNavLinks = document.querySelectorAll('.mobile-nav-link');
    mobileNavLinks.forEach(link => {
        link.addEventListener('click', closeMobileMenu);
    });

    // Cerrar al hacer clic fuera del menú
    document.addEventListener('click', (e) => {
        if (mobileMenu && mobileMenu.classList.contains('open')) {
            if (!mobileMenu.contains(e.target) && !mobileMenuBtn.contains(e.target)) {
                closeMobileMenu();
            }
        }
    });

    // Cerrar con tecla Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && mobileMenu && mobileMenu.classList.contains('open')) {
            closeMobileMenu();
        }
    });

    // ============================================================
    // SMOOTH SCROLL PARA LINKS DE NAVEGACIÓN
    // ============================================================

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            
            if (targetId === '#') return;

            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                e.preventDefault();
                
                const navbarHeight = navbar ? navbar.offsetHeight : 0;
                const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - navbarHeight - 20;

                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });

                // Actualizar URL sin scroll
                history.pushState(null, null, targetId);
            }
        });
    });

    // ============================================================
    // SERVICE MODAL
    // ============================================================

    const serviceModal = document.getElementById('service-modal');
    const modalTitle = document.getElementById('modal-title');
    const modalDescription = document.getElementById('modal-description');
    const modalIcon = document.getElementById('modal-icon');

    window.openServiceModal = function(index) {
        if (!window.servicesData || !window.servicesData[index]) return;

        const service = window.servicesData[index];
        
        modalTitle.textContent = service.title;
        modalDescription.textContent = service.full_desc;
        
        // Actualizar icono
        modalIcon.setAttribute('data-lucide', service.icon);
        lucide.createIcons();

        serviceModal.showModal();

        // Focus trap básico
        trapFocus(serviceModal);
    };

    window.closeServiceModal = function() {
        serviceModal.close();
    };

    // Cerrar modal al hacer clic en backdrop
    if (serviceModal) {
        serviceModal.addEventListener('click', (e) => {
            if (e.target === serviceModal) {
                closeServiceModal();
            }
        });

        // Cerrar con Escape
        serviceModal.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeServiceModal();
            }
        });
    }

    // ============================================================
    // FOCUS TRAP (para modales y menú móvil)
    // ============================================================

    function trapFocus(element) {
        const focusableElements = element.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        if (focusableElements.length === 0) return;

        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];

        firstFocusable.focus();

        element.addEventListener('keydown', function handleTab(e) {
            if (e.key !== 'Tab') return;

            if (e.shiftKey) {
                if (document.activeElement === firstFocusable) {
                    e.preventDefault();
                    lastFocusable.focus();
                }
            } else {
                if (document.activeElement === lastFocusable) {
                    e.preventDefault();
                    firstFocusable.focus();
                }
            }
        });
    }

    // ============================================================
    // UTILIDADES GLOBALES
    // ============================================================

    // Función para mostrar mensajes
    window.showMessage = function(container, message, type = 'success') {
        const messageDiv = document.getElementById(container);
        if (!messageDiv) return;

        messageDiv.className = `mt-4 p-4 rounded-lg text-center ${
            type === 'success' 
                ? 'bg-green-50 text-green-800 border border-green-200' 
                : 'bg-red-50 text-red-800 border border-red-200'
        }`;
        messageDiv.textContent = message;
        messageDiv.classList.remove('hidden');

        // Scroll al mensaje
        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    // Función para ocultar mensajes
    window.hideMessage = function(container) {
        const messageDiv = document.getElementById(container);
        if (messageDiv) {
            messageDiv.classList.add('hidden');
        }
    };

    // Función para mostrar error en campo específico
    window.showFieldError = function(fieldName, message, formId = null) {
        const selector = formId 
            ? `#${formId} [data-error="${fieldName}"]`
            : `[data-error="${fieldName}"]`;
        
        const errorElement = document.querySelector(selector);
        
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.remove('hidden');
        }

        // Marcar input como error
        const inputSelector = formId
            ? `#${formId} [name="${fieldName}"]`
            : `[name="${fieldName}"]`;
        
        const input = document.querySelector(inputSelector);
        if (input) {
            input.classList.add('form-error');
        }
    };

    // Función para limpiar errores de campos
    window.clearFieldErrors = function(formId = null) {
        const selector = formId
            ? `#${formId} [data-error]`
            : '[data-error]';
        
        document.querySelectorAll(selector).forEach(el => {
            el.textContent = '';
            el.classList.add('hidden');
        });

        const inputSelector = formId
            ? `#${formId} input, #${formId} select, #${formId} textarea`
            : 'input, select, textarea';
        
        document.querySelectorAll(inputSelector).forEach(el => {
            el.classList.remove('form-error');
        });
    };

    // Función para establecer estado de carga en botón
    window.setButtonLoading = function(button, isLoading) {
        if (!button) return;

        if (isLoading) {
            button.disabled = true;
            button.dataset.originalText = button.innerHTML;
            button.innerHTML = '<span class="spinner"></span> Enviando...';
        } else {
            button.disabled = false;
            if (button.dataset.originalText) {
                button.innerHTML = button.dataset.originalText;
            }
        }
    };

    // ============================================================
    // YEAR DINÁMICO EN FOOTER
    // ============================================================

    const yearElement = document.getElementById('current-year');
    if (yearElement) {
        yearElement.textContent = new Date().getFullYear();
    }

    // ============================================================
    // INICIALIZACIÓN
    // ============================================================

    // Ejecutar al cargar
    document.addEventListener('DOMContentLoaded', function() {
        // Verificar scroll inicial
        handleNavbarScroll();
        
        // Inicializar iconos Lucide
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });

})();
