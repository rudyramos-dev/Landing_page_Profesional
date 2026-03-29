<!-- ============================================================ -->
<!-- POR QUÉ NOSOTROS -->
<!-- ============================================================ -->
<section id="nosotros" class="py-16 md:py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="font-serif text-3xl md:text-4xl text-primary mb-4">¿Por qué elegirnos?</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Lo que nos diferencia de otras consultorías.</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <?php foreach ($differentiators as $diff): ?>
            <div class="flex gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-accent/20 flex items-center justify-center">
                    <i data-lucide="<?= $e($diff['icon']) ?>" class="w-6 h-6 text-accent"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-lg text-gray-900 mb-1"><?= $e($diff['title']) ?></h3>
                    <p class="text-gray-600"><?= $e($diff['text']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
