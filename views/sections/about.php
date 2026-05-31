<!-- ============================================================ -->
<!-- SOBRE NOSOTROS -->
<!-- ============================================================ -->
<section id="nosotros" class="py-20 md:py-32 relative overflow-hidden">
    <!-- Background con patrón sutil -->
    <div class="absolute inset-0 bg-[var(--color-bg)]">
        <div class="absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 1px 1px, var(--color-primary) 1px, transparent 0); background-size: 40px 40px;"></div>
    </div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <!-- Header con tipografía elegante -->
        <div class="text-center mb-16 md:mb-20">
            <span class="inline-block text-sm font-medium tracking-widest uppercase text-[var(--color-accent)] mb-4">Conócenos</span>
            <h2 class="font-serif text-4xl md:text-5xl lg:text-6xl text-[var(--color-primary)] mb-6"><?= $e($businessName ?? 'Sobre Nosotros') ?></h2>
            <div class="w-24 h-1 bg-[var(--color-accent)] mx-auto mb-6"></div>
            <p class="text-lg md:text-xl text-gray-600 max-w-2xl mx-auto leading-relaxed">
                <?= $e($businessDescription ?? 'Somos un equipo dedicado a brindar soluciones integrales con excellence y compromiso.') ?>
            </p>
        </div>

        <!-- Grid principal: Historia + Valores -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 mb-16">
            
            <!-- Columna izquierda: Historia (8 cols) -->
            <div class="lg:col-span-7 space-y-8">
                <!-- Bloque de historia con borde lateral -->
                <div class="relative pl-8 border-l-4 border-[var(--color-accent)]">
                    <h3 class="font-serif text-2xl md:text-3xl text-[var(--color-primary)] mb-4">Nuestra Historia</h3>
                    <p class="text-gray-600 leading-relaxed text-lg">
                        Con más de <strong class="text-[var(--color-primary)]"><?= $e($businessYears ?? '10') ?> años</strong> de trayectoria, hemos construido relaciones duraderas con nuestros clientes basadas en la confianza, transparencia y resultados excepcionales. Cada proyecto es una oportunidad para demostrar nuestro compromiso con la excelencia.
                    </p>
                </div>

                <!-- Misión y Visión lado a lado -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Misión -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
                        <div class="w-12 h-12 rounded-lg bg-[var(--color-primary)] flex items-center justify-center mb-4">
                            <i data-lucide="target" class="w-6 h-6 text-white"></i>
                        </div>
                        <h4 class="font-serif text-xl text-[var(--color-primary)] mb-3">Misión</h4>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            Brindar soluciones integrales que superen las expectativas de nuestros clientes, manteniendo los más altos estándares de calidad y profesionalismo.
                        </p>
                    </div>

                    <!-- Visión -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
                        <div class="w-12 h-12 rounded-lg bg-[var(--color-accent)] flex items-center justify-center mb-4">
                            <i data-lucide="eye" class="w-6 h-6 text-[var(--color-primary)]"></i>
                        </div>
                        <h4 class="font-serif text-xl text-[var(--color-primary)] mb-3">Visión</h4>
                        <p class="text-gray-600 text-sm leading-relaxed">
                            Ser reconocidos como líderes en nuestro sector, innovando constantemente y expandiendo nuestro impacto positivo en la comunidad empresarial.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Columna derecha: Imagen/Estadística destacada (4 cols) -->
            <div class="lg:col-span-5">
                <div class="relative">
                    <!-- Card principal con estadísticas -->
                    <div class="bg-[var(--color-primary)] rounded-2xl p-8 text-white relative overflow-hidden">
                        <!-- Patrón decorativo -->
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                        <div class="absolute bottom-0 left-0 w-24 h-24 bg-[var(--color-accent)]/20 rounded-full translate-y-1/2 -translate-x-1/2"></div>
                        
                        <div class="relative">
                            <div class="text-center mb-8">
                                <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-white/10 flex items-center justify-center">
                                    <i data-lucide="award" class="w-10 h-10 text-[var(--color-accent)]"></i>
                                </div>
                                <h4 class="font-serif text-2xl mb-2"><?= $e($businessTagline ?? 'Excelencia') ?></h4>
                                <p class="text-white/70 text-sm">Profesionales certificados</p>
                            </div>

                            <!-- Estadísticas -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-4 bg-white/10 rounded-xl">
                                    <div class="flex items-center gap-3">
                                        <i data-lucide="calendar" class="w-5 h-5 text-[var(--color-accent)]"></i>
                                        <span class="text-sm">Años de experiencia</span>
                                    </div>
                                    <span class="font-bold text-xl"><?= $e($businessYears ?? '10+') ?></span>
                                </div>
                                <div class="flex items-center justify-between p-4 bg-white/10 rounded-xl">
                                    <div class="flex items-center gap-3">
                                        <i data-lucide="users" class="w-5 h-5 text-[var(--color-accent)]"></i>
                                        <span class="text-sm">Clientes atendidos</span>
                                    </div>
                                    <span class="font-bold text-xl"><?= $e($businessClients ?? '100+') ?></span>
                                </div>
                                <div class="flex items-center justify-between p-4 bg-white/10 rounded-xl">
                                    <div class="flex items-center gap-3">
                                        <i data-lucide="briefcase" class="w-5 h-5 text-[var(--color-accent)]"></i>
                                        <span class="text-sm">Proyectos completados</span>
                                    </div>
                                    <span class="font-bold text-xl"><?= $e($businessProjects ?? '200+') ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Valores / Diferenciadores -->
        <div class="mt-16">
            <h3 class="font-serif text-2xl md:text-3xl text-[var(--color-primary)] text-center mb-12">¿Por qué elegirnos?</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach (($differentiators ?? []) as $index => $diff): ?>
                <div class="group bg-white p-6 rounded-xl border border-gray-100 hover:border-[var(--color-accent)] hover:shadow-lg transition-all duration-300">
                    <div class="w-14 h-14 rounded-xl bg-[var(--color-accent)]/10 flex items-center justify-center mb-5 group-hover:bg-[var(--color-accent)] group-hover:scale-110 transition-all duration-300">
                        <i data-lucide="<?= $e($diff['icon'] ?? 'check-circle') ?>" class="w-7 h-7 text-[var(--color-accent)] group-hover:text-white transition-colors duration-300"></i>
                    </div>
                    <h4 class="font-semibold text-lg text-[var(--color-primary)] mb-2"><?= $e($diff['title'] ?? 'Título') ?></h4>
                    <p class="text-gray-600 text-sm leading-relaxed"><?= $e($diff['text'] ?? 'Descripción del diferenciador') ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
