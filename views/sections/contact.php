<!-- ============================================================ -->
<!-- CONTACTO -->
<!-- ============================================================ -->
<section id="contacto" class="py-16 md:py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="font-serif text-3xl md:text-4xl text-primary mb-4">Contáctanos</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Estamos aquí para ayudarte. Envíanos un mensaje y te responderemos en menos de 24 horas.</p>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Información de contacto -->
            <div>
                <h3 class="font-semibold text-xl text-gray-900 mb-6">Información de contacto</h3>
                
                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="phone" class="w-5 h-5 text-primary"></i>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Teléfono</div>
                            <a href="tel:<?= $e($businessPhone) ?>" class="text-gray-900 hover:text-primary"><?= $e($businessPhone) ?></a>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="mail" class="w-5 h-5 text-primary"></i>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Email</div>
                            <a href="mailto:<?= $e($businessEmail) ?>" class="text-gray-900 hover:text-primary"><?= $e($businessEmail) ?></a>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="map-pin" class="w-5 h-5 text-primary"></i>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Ubicación</div>
                            <address class="text-gray-900 not-italic"><?= $e($businessAddress) ?></address>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="clock" class="w-5 h-5 text-primary"></i>
                        </div>
                        <div>
                            <div class="text-sm text-gray-500">Horario</div>
                            <div class="text-gray-900"><?= $e($businessHours) ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- WhatsApp Button -->
                <a 
                    href="https://wa.me/<?= $e($businessWhatsapp) ?>?text=<?= urlencode($whatsappMessage) ?>" 
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 mt-8 px-6 py-3 rounded-lg bg-green-500 text-white font-medium hover:bg-green-600 transition-colors"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    Escribir por WhatsApp
                </a>
                
                <!-- Redes sociales -->
                <?php if ($socialInstagram || $socialFacebook || $socialLinkedin): ?>
                <div class="mt-8 pt-8 border-t">
                    <h4 class="text-sm font-medium text-gray-500 mb-4">Síguenos</h4>
                    <div class="flex gap-3">
                        <?php if ($socialInstagram): ?>
                        <a href="<?= $e($socialInstagram) ?>" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors" aria-label="Instagram">
                            <i data-lucide="instagram" class="w-5 h-5 text-gray-600"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($socialFacebook): ?>
                        <a href="<?= $e($socialFacebook) ?>" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors" aria-label="Facebook">
                            <i data-lucide="facebook" class="w-5 h-5 text-gray-600"></i>
                        </a>
                        <?php endif; ?>
                        <?php if ($socialLinkedin): ?>
                        <a href="<?= $e($socialLinkedin) ?>" target="_blank" rel="noopener noreferrer" class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors" aria-label="LinkedIn">
                            <i data-lucide="linkedin" class="w-5 h-5 text-gray-600"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Formulario de contacto -->
            <div>
                <form id="contact-form" class="bg-gray-50 rounded-xl p-6 md:p-8">
                    <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
                    <!-- Honeypot -->
                    <div style="display:none" aria-hidden="true">
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>
                    
                    <div class="space-y-5">
                        <div>
                            <label for="contact-name" class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
                            <input 
                                type="text" 
                                id="contact-name" 
                                name="name" 
                                required 
                                maxlength="100"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                placeholder="Tu nombre"
                            >
                            <p class="mt-1 text-sm text-red-500 hidden" data-error="name"></p>
                        </div>
                        
                        <div>
                            <label for="contact-email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input 
                                type="email" 
                                id="contact-email" 
                                name="email" 
                                required 
                                maxlength="255"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                placeholder="tu@email.com"
                            >
                            <p class="mt-1 text-sm text-red-500 hidden" data-error="email"></p>
                        </div>
                        
                        <div>
                            <label for="contact-phone" class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                            <input 
                                type="tel" 
                                id="contact-phone" 
                                name="phone" 
                                maxlength="20"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                                placeholder="+52 999 123 4567"
                            >
                        </div>
                        
                        <div>
                            <label for="contact-service" class="block text-sm font-medium text-gray-700 mb-1">Servicio de interés</label>
                            <select 
                                id="contact-service" 
                                name="service_interest"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                            >
                                <option value="">Selecciona un servicio</option>
                                <?php foreach ($services as $service): ?>
                                <option value="<?= $e($service['title']) ?>"><?= $e($service['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="contact-message" class="block text-sm font-medium text-gray-700 mb-1">Mensaje</label>
                            <textarea 
                                id="contact-message" 
                                name="message" 
                                rows="4"
                                maxlength="1000"
                                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all resize-none"
                                placeholder="Cuéntanos cómo podemos ayudarte..."
                            ></textarea>
                        </div>
                        
                        <button 
                            type="submit" 
                            id="contact-submit"
                            class="w-full px-6 py-4 rounded-lg font-semibold bg-primary text-white hover:opacity-90 transition-all flex items-center justify-center gap-2"
                        >
                            <span>Enviar mensaje</span>
                            <i data-lucide="send" class="w-5 h-5"></i>
                        </button>
                    </div>
                    
                    <p class="mt-4 text-xs text-gray-500 text-center">Los campos marcados con * son obligatorios</p>
                    
                    <!-- Success/Error message -->
                    <div id="contact-message-result" class="hidden mt-4 p-4 rounded-lg text-center"></div>
                </form>
            </div>
        </div>
    </div>
</section>
