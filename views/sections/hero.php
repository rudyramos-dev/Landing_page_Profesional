<!-- ============================================================ -->
<!-- HERO -->
<!-- ============================================================ -->
<section id="hero" class="hero-pattern min-h-screen flex items-center pt-16 md:pt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24">
        <div class="max-w-3xl">
            <h1 class="font-serif text-4xl md:text-5xl lg:text-6xl text-white leading-tight mb-6">
                <?= $e($businessTagline) ?>
            </h1>
            <p class="text-lg md:text-xl text-white/80 mb-8 max-w-2xl">
                <?= $e($businessDescription) ?>
            </p>
            
            <!-- Stats -->
            <div class="flex flex-wrap gap-8 mb-10">
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold text-accent"><?= $e($businessYears) ?></div>
                    <div class="text-white/70 text-sm">Años de experiencia</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold text-accent"><?= $e($businessClients) ?></div>
                    <div class="text-white/70 text-sm">Clientes atendidos</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl md:text-4xl font-bold text-accent"><?= $e($businessProjects) ?></div>
                    <div class="text-white/70 text-sm">Proyectos completados</div>
                </div>
            </div>
            
            <!-- CTAs -->
            <div class="flex flex-col sm:flex-row gap-4">
                <a href="#contacto" class="inline-flex items-center justify-center px-8 py-4 rounded-lg font-semibold bg-accent text-gray-900 hover:opacity-90 transition-all">
                    Solicitar consulta gratuita
                    <i data-lucide="arrow-right" class="w-5 h-5 ml-2"></i>
                </a>
                <a href="#servicios" class="inline-flex items-center justify-center px-8 py-4 rounded-lg font-semibold border-2 border-white/30 text-white hover:bg-white/10 transition-all">
                    Ver servicios
                </a>
            </div>
        </div>
    </div>
</section>
