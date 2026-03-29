<!-- ============================================================ -->
<!-- FAQ -->
<!-- ============================================================ -->
<section id="faq" class="py-16 md:py-24" itemscope itemtype="https://schema.org/FAQPage">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="font-serif text-3xl md:text-4xl text-primary mb-4">Preguntas frecuentes</h2>
        </div>
        
        <div class="space-y-4">
            <?php foreach ($faqItems as $index => $faq): ?>
            <details class="bg-white rounded-xl border border-gray-200 overflow-hidden group" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                <summary class="flex items-center justify-between p-5 cursor-pointer hover:bg-gray-50 transition-colors">
                    <span class="font-medium text-gray-900 pr-4" itemprop="name"><?= $e($faq['question']) ?></span>
                    <i data-lucide="chevron-down" class="w-5 h-5 text-gray-400 flex-shrink-0 chevron transition-transform duration-200"></i>
                </summary>
                <div class="px-5 pb-5" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                    <p class="text-gray-600" itemprop="text"><?= $e($faq['answer']) ?></p>
                </div>
            </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>
