<!-- ============================================================ -->
<!-- PROCESO -->
<!-- ============================================================ -->
<section id="proceso" class="py-16 md:py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="font-serif text-3xl md:text-4xl text-primary mb-4">Nuestro proceso de trabajo</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Un enfoque estructurado para garantizar resultados.</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <?php foreach ($processSteps as $index => $step): ?>
            <div class="relative">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-primary text-white font-bold text-xl mb-4">
                        <?= $e($step['number']) ?>
                    </div>
                    <h3 class="font-semibold text-lg text-gray-900 mb-2"><?= $e($step['title']) ?></h3>
                    <p class="text-gray-600 text-sm"><?= $e($step['text']) ?></p>
                </div>
                <?php if ($index < count($processSteps) - 1): ?>
                <div class="hidden md:block absolute top-8 left-[calc(50%+40px)] w-[calc(100%-80px)] h-0.5 bg-gray-200"></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
