<!-- ============================================================ -->
<!-- CITAS -->
<!-- ============================================================ -->
<section id="citas" class="py-16 md:py-24">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="font-serif text-3xl md:text-4xl text-primary mb-4">¿Prefieres agendar una cita?</h2>
            <p class="text-gray-600">Elige el día y horario que mejor te convenga para tu consulta gratuita.</p>
        </div>
        
        <form id="appointment-form" class="bg-white rounded-xl p-6 md:p-8 shadow-sm border border-gray-100">
            <input type="hidden" name="csrf_token" value="<?= $e($csrfToken) ?>">
            <!-- Honeypot -->
            <div style="display:none" aria-hidden="true">
                <input type="text" name="website" tabindex="-1" autocomplete="off">
            </div>
            
            <div class="space-y-5">
                <!-- Fecha y hora -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="appt-date" class="block text-sm font-medium text-gray-700 mb-1">Fecha *</label>
                        <input 
                            type="date" 
                            id="appt-date" 
                            name="appointment_date" 
                            required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                        >
                        <p class="mt-1 text-sm text-red-500 hidden" data-error="appointment_date"></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Horario disponible *</label>
                        <div id="time-slots" class="min-h-[48px] flex flex-wrap gap-2">
                            <p class="text-gray-400 text-sm py-3">Selecciona primero una fecha</p>
                        </div>
                        <input type="hidden" id="appt-time" name="appointment_time" required>
                        <p class="mt-1 text-sm text-red-500 hidden" data-error="appointment_time"></p>
                    </div>
                </div>
                
                <!-- Datos personales -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="appt-name" class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
                        <input 
                            type="text" 
                            id="appt-name" 
                            name="name" 
                            required 
                            maxlength="100"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                            placeholder="Tu nombre"
                        >
                        <p class="mt-1 text-sm text-red-500 hidden" data-error="name"></p>
                    </div>
                    
                    <div>
                        <label for="appt-email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input 
                            type="email" 
                            id="appt-email" 
                            name="email" 
                            required 
                            maxlength="255"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                            placeholder="tu@email.com"
                        >
                        <p class="mt-1 text-sm text-red-500 hidden" data-error="email"></p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="appt-phone" class="block text-sm font-medium text-gray-700 mb-1">Teléfono *</label>
                        <input 
                            type="tel" 
                            id="appt-phone" 
                            name="phone" 
                            required
                            maxlength="20"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                            placeholder="+52 999 123 4567"
                        >
                        <p class="mt-1 text-sm text-red-500 hidden" data-error="phone"></p>
                    </div>
                    
                    <div>
                        <label for="appt-service" class="block text-sm font-medium text-gray-700 mb-1">Servicio de interés *</label>
                        <select 
                            id="appt-service" 
                            name="service_interest"
                            required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all"
                        >
                            <option value="">Selecciona un servicio</option>
                            <?php foreach ($appointmentServices as $service): ?>
                            <option value="<?= $e($service['name']) ?>"><?= $e($service['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="mt-1 text-sm text-red-500 hidden" data-error="service_interest"></p>
                    </div>
                </div>
                
                <div>
                    <label for="appt-message" class="block text-sm font-medium text-gray-700 mb-1">Mensaje (opcional)</label>
                    <textarea 
                        id="appt-message" 
                        name="message" 
                        rows="3"
                        maxlength="1000"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all resize-none"
                        placeholder="¿Hay algo que debamos saber antes de la cita?"
                    ></textarea>
                </div>
                
                <button 
                    type="submit" 
                    id="appointment-submit"
                    class="w-full px-6 py-4 rounded-lg font-semibold bg-accent text-gray-900 hover:opacity-90 transition-all flex items-center justify-center gap-2"
                >
                    <span>Solicitar cita</span>
                    <i data-lucide="calendar" class="w-5 h-5"></i>
                </button>
            </div>
            
            <p class="mt-4 text-xs text-gray-500 text-center">Los campos marcados con * son obligatorios</p>
            
            <!-- Success/Error message -->
            <div id="appointment-message-result" class="hidden mt-4 p-4 rounded-lg text-center"></div>
        </form>
    </div>
</section>
