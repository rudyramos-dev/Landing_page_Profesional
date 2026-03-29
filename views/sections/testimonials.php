<!-- ============================================================ -->
<!-- TESTIMONIOS -->
<!-- ============================================================ -->
<section id="testimonios" class="py-16 md:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="font-serif text-3xl md:text-4xl text-primary mb-4">Lo que dicen nuestros clientes</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($testimonials as $testimonial): ?>
            <article class="bg-white rounded-xl p-6 shadow-sm border border-gray-100" itemscope itemtype="https://schema.org/Review">
                <!-- Stars -->
                <div class="flex gap-1 mb-4">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                    <i data-lucide="star" class="w-5 h-5 text-amber-400 fill-amber-400"></i>
                    <?php endfor; ?>
                </div>
                
                <p class="text-gray-600 mb-6" itemprop="reviewBody">"<?= $e($testimonial['text']) ?>"</p>
                
                <div class="flex items-center gap-3">
                    <div 
                        class="w-10 h-10 rounded-full flex items-center justify-center text-white font-medium text-sm"
                        style="background-color: <?= $e($testimonial['color']) ?>"
                    >
                        <?= $e($testimonial['initials']) ?>
                    </div>
                    <div>
                        <div class="font-medium text-gray-900" itemprop="author"><?= $e($testimonial['name']) ?></div>
                        <div class="text-sm text-gray-500"><?= $e($testimonial['company']) ?></div>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
