<!-- ============================================================ -->
<!-- SERVICIOS -->
<!-- ============================================================ -->
<section id="servicios" class="py-16 md:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="font-serif text-3xl md:text-4xl text-primary mb-4">¿En qué te puedo ayudar?</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Ofrecemos soluciones integrales para impulsar el crecimiento de tu negocio.</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($services as $index => $service): ?>
            <div class="bg-white rounded-xl p-6 shadow-sm hover:shadow-md transition-shadow border border-gray-100">
                <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center mb-4">
                    <i data-lucide="<?= $e($service['icon']) ?>" class="w-6 h-6 text-primary"></i>
                </div>
                <h3 class="font-semibold text-lg text-gray-900 mb-2"><?= $e($service['title']) ?></h3>
                <p class="text-gray-600 text-sm mb-4"><?= $e($service['short_desc']) ?></p>
                <button 
                    onclick="openServiceModal(<?= $index ?>)" 
                    class="text-primary font-medium text-sm hover:underline inline-flex items-center"
                >
                    Saber más
                    <i data-lucide="chevron-right" class="w-4 h-4 ml-1"></i>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Service Modal -->
<dialog id="service-modal" class="rounded-xl">
    <div class="p-6">
        <div class="flex items-start justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
                <i id="modal-icon" data-lucide="search" class="w-6 h-6 text-primary"></i>
            </div>
            <button onclick="closeServiceModal()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors" aria-label="Cerrar">
                <i data-lucide="x" class="w-5 h-5 text-gray-500"></i>
            </button>
        </div>
        <h3 id="modal-title" class="font-semibold text-xl text-gray-900 mb-3"></h3>
        <p id="modal-description" class="text-gray-600 leading-relaxed"></p>
        <div class="mt-6 pt-4 border-t">
            <a href="#contacto" onclick="closeServiceModal()" class="inline-flex items-center justify-center w-full px-6 py-3 rounded-lg font-medium bg-primary text-white hover:opacity-90 transition-all">
                Solicitar información
            </a>
        </div>
    </div>
</dialog>
