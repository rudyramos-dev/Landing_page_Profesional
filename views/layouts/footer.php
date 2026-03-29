<!-- ============================================================ -->
<!-- FOOTER -->
<!-- ============================================================ -->
<footer class="bg-primary text-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Logo y descripción -->
            <div>
                <div class="text-2xl font-bold mb-4"><?= $e($businessName) ?></div>
                <p class="text-white/70 text-sm mb-4"><?= $e($businessDescription) ?></p>
            </div>
            
            <!-- Enlaces rápidos -->
            <div>
                <h4 class="font-semibold mb-4">Enlaces rápidos</h4>
                <nav class="space-y-2">
                    <a href="#servicios" class="block text-white/70 hover:text-white transition-colors">Servicios</a>
                    <a href="#nosotros" class="block text-white/70 hover:text-white transition-colors">Nosotros</a>
                    <a href="#proceso" class="block text-white/70 hover:text-white transition-colors">Proceso</a>
                    <a href="#faq" class="block text-white/70 hover:text-white transition-colors">FAQ</a>
                    <a href="#contacto" class="block text-white/70 hover:text-white transition-colors">Contacto</a>
                </nav>
            </div>
            
            <!-- Contacto -->
            <div>
                <h4 class="font-semibold mb-4">Contacto</h4>
                <address class="not-italic space-y-2 text-white/70 text-sm">
                    <p><?= $e($businessAddress) ?></p>
                    <p><?= $e($businessPhone) ?></p>
                    <p><?= $e($businessEmail) ?></p>
                    <p><?= $e($businessHours) ?></p>
                </address>
            </div>
        </div>
        
        <div class="mt-8 pt-8 border-t border-white/20 flex flex-col md:flex-row items-center justify-between gap-4">
            <p class="text-white/50 text-sm">
                &copy; <span id="current-year"></span> <?= $e($businessName) ?>. Todos los derechos reservados.
            </p>
            <p class="text-white/50 text-sm">
                Desarrollado por <a href="<?= $e($developerUrl) ?>" target="_blank" rel="noopener noreferrer" class="hover:text-white transition-colors"><?= $e($developerName) ?></a>
            </p>
        </div>
    </div>
</footer>

<!-- ============================================================ -->
<!-- WHATSAPP FLOAT -->
<!-- ============================================================ -->
<div class="whatsapp-float">
    <span class="whatsapp-tooltip">¿Tienes dudas?</span>
    <a 
        href="https://wa.me/<?= $e($businessWhatsapp) ?>?text=<?= urlencode($whatsappMessage) ?>" 
        target="_blank" 
        rel="noopener noreferrer"
        aria-label="Contactar por WhatsApp"
    >
        <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
    </a>
</div>

<!-- Services data for modal -->
<script>
    window.servicesData = <?= json_encode($services, JSON_UNESCAPED_UNICODE) ?>;
</script>

<!-- JavaScript -->
<script src="/public/js/app.js" defer></script>
<script src="/public/js/form.js" defer></script>
<script src="/public/js/appointments.js" defer></script>

<!-- Initialize Lucide Icons -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
        document.getElementById('current-year').textContent = new Date().getFullYear();
    });
</script>
</body>
</html>
