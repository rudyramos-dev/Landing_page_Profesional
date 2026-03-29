<!-- ============================================================ -->
<!-- NAVBAR -->
<!-- ============================================================ -->
<nav id="navbar" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300" role="navigation" aria-label="Navegación principal">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 md:h-20">
            <!-- Logo -->
            <a href="/" class="flex items-center">
                <span class="text-xl md:text-2xl font-bold text-white navbar-logo"><?= $e($businessName) ?></span>
            </a>
            
            <!-- Desktop Navigation -->
            <div class="hidden md:flex items-center space-x-8">
                <a href="#servicios" class="text-white/90 hover:text-white transition-colors nav-link">Servicios</a>
                <a href="#nosotros" class="text-white/90 hover:text-white transition-colors nav-link">Nosotros</a>
                <a href="#proceso" class="text-white/90 hover:text-white transition-colors nav-link">Proceso</a>
                <a href="#faq" class="text-white/90 hover:text-white transition-colors nav-link">FAQ</a>
                <a href="#contacto" class="text-white/90 hover:text-white transition-colors nav-link">Contacto</a>
                <a href="#citas" class="px-5 py-2.5 rounded-lg font-medium transition-all bg-accent text-gray-900 hover:opacity-90">
                    Agendar consulta
                </a>
            </div>
            
            <!-- Mobile menu button -->
            <button id="mobile-menu-btn" class="md:hidden p-2 text-white" aria-label="Abrir menú" aria-expanded="false">
                <i data-lucide="menu" class="w-6 h-6"></i>
            </button>
        </div>
    </div>
    
    <!-- Mobile Navigation -->
    <div id="mobile-menu" class="mobile-menu fixed top-0 right-0 bottom-0 w-72 bg-white shadow-2xl md:hidden">
        <div class="p-4">
            <button id="mobile-menu-close" class="p-2 text-gray-600 float-right" aria-label="Cerrar menú">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
            <div class="clear-both pt-8">
                <a href="#servicios" class="block py-3 text-gray-700 hover:text-primary border-b border-gray-100 mobile-nav-link">Servicios</a>
                <a href="#nosotros" class="block py-3 text-gray-700 hover:text-primary border-b border-gray-100 mobile-nav-link">Nosotros</a>
                <a href="#proceso" class="block py-3 text-gray-700 hover:text-primary border-b border-gray-100 mobile-nav-link">Proceso</a>
                <a href="#faq" class="block py-3 text-gray-700 hover:text-primary border-b border-gray-100 mobile-nav-link">FAQ</a>
                <a href="#contacto" class="block py-3 text-gray-700 hover:text-primary border-b border-gray-100 mobile-nav-link">Contacto</a>
                <a href="#citas" class="block mt-4 px-5 py-3 rounded-lg font-medium text-center bg-accent text-gray-900 mobile-nav-link">
                    Agendar consulta
                </a>
            </div>
        </div>
    </div>
</nav>
