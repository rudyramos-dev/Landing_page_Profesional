# AGENTS.md - Landing Page Profesional

## Overview
Landing page para "Consultoría Nexus" - Consultoría empresarial con sistema de citas y captura de leads.

**Stack:** PHP Vanilla + SQLite/MySQL + TailwindCSS + Lucide Icons
**Tema:** Azul marino (#1a3a5c) + Dorado (#e8a020)
**Versión:** 1.0.0

---

## Project Structure

```
Landing_page_Profesional/
├── app/                        # Lógica de negocio (PHP)
│   ├── .htaccess               # Seguridad: deniega acceso HTTP
│   ├── AdminController.php     # Panel admin: dashboard, CRUD
│   ├── AppointmentController.php # Agenda citas
│   ├── AuthController.php      # Login/logout admins
│   ├── BusinessConfig.php      # Configuración de marca
│   ├── Env.php                 # Carga variables de entorno
│   ├── Helpers.php             # Funciones utilitarias
│   ├── LeadController.php       # Captura y gestión de leads
│   ├── Mailer.php              # Envío de emails
│   ├── RateLimiter.php          # Protección anti-spam
│   ├── Response.php            # Respuestas JSON/HTTP
│   ├── Router.php              # Enrutamiento
│   ├── Security.php            # CSRF, sanitización
│   └── Validator.php           # Validación de inputs
├── views/
│   ├── layouts/
│   │   ├── head.php           # SEO, meta tags, CSS
│   │   ├── header.php         # Navbar responsive
│   │   └── footer.php         # Footer + WhatsApp button
│   ├── partials/              # Componentes reutilizables
│   └── sections/              # Secciones de la landing
├── database/
│   ├── .htaccess              # Seguridad
│   ├── Database.php           # Singleton PDO
│   ├── leads.db               # SQLite (producción local)
│   └── initial_credentials.txt # Credenciales admin (eliminar tras uso)
├── public/
│   ├── css/app.css            # Estilos custom
│   ├── img/                   # Assets imágenes
│   └── js/                    # Scripts JavaScript
├── admin/
│   ├── index.php              # Dashboard admin
│   ├── login.php              # Página de login
│   └── assets/                # CSS/JS del admin
├── config.php                 # Configuración global
├── index.php                  # Entry point
├── .env                       # Variables de entorno
├── .env.example               # Template de .env
└── .htaccess                  # Configuración Apache
```

---

## 🔧 Controllers - Análisis Detallado

### 1. Database.php

**Tipo:** Singleton Pattern - Conexión PDO

**Responsabilidades:**
- Gestionar conexiones SQLite/MySQL
- Crear tablas automáticamente
- Seed data inicial

**Métodos principales:**
```php
public static function getInstance(): Database
public function getConnection(): PDO
public function initialize(): void
```

**Flujo de datos:**
```
1. getInstance() → si no existe, crea nueva instancia
2. __construct() → detecta driver (SQLite/MySQL) del .env
3. getConnection() → retorna PDO singleton
4. initialize() → ejecuta createTables() + seedData()
```

**Dependencias:** PDO, .env (DB_* variables)

**Issues técnicos:**
- ❌ No hay manejo de reconexión ante fallos
- ❌ Falta pool de conexiones para producción
- ⚠️ El seed data no verifica si ya existen datos

---

### 2. AdminController.php

**Tipo:** Controlador principal del panel admin

**Responsabilidades:**
- Dashboard con estadísticas
- Gestión de leads (CRUD completo)
- Gestión de citas (CRUD completo)
- CRUD de servicios, testimonios, FAQs
- Configuración general

**Métodos principales:**
```php
public function dashboard(): void
public function leads(): void
public function appointments(): void
public function services(): void
public function testimonials(): void
public function faqs(): void
public function settings(): void
public function updateLeadStatus(int $id): void
public function updateAppointmentStatus(int $id): void
public function deleteLead(int $id): void
public function deleteAppointment(int $id): void
public function exportLeadsCSV(): void
```

**Dependencias:**
- PDO (via Database)
- AuthController (verificación de sesión)
- LeadController (delegación de leads)
- AppointmentController (delegación de citas)
- Security (CSRF)

**Flujo de datos:**
```
Request → checkAuth() → executeAction() → renderView()
```

**Issues técnicos:**
- ❌ Mezcla de responsabilidades (debería usar servicios separados)
- ❌ No hay paginación en listados
- ⚠️ Validación de inputs podría mejorarse

---

### 3. LeadController.php

**Tipo:** Controlador de dominio - Leads

**Responsabilidades:**
- Recibir submissions del formulario de contacto
- Validación de datos
- Rate limiting anti-spam
- Envío de notificaciones por email
- Exportación CSV

**Métodos principales:**
```php
public function store(): void
public function index(): void
public function exportCSV(): void
public function getStatistics(): array
```

**Validaciones:**
- name: required, 2-100 chars
- email: required, valid email format
- phone: optional, valid phone format
- message: optional, max 2000 chars
- service_interest: optional, valid service ID

**Dependencias:**
- Database (PDO)
- RateLimiter (protección spam)
- Validator (validación de inputs)
- Security (sanitización)
- Mailer (notificaciones)

**Flujo de datos:**
```
POST /lead → validateCSRF() → rateLimitCheck() → validateInput()
→ sanitize() → insertDB() → sendEmail() → JSON response
```

**Issues técnicos:**
- ❌ No hay soft delete de leads
- ❌ Falta deduplicación por email
- ⚠️ El export CSV podría ser lento con muchos registros

---

### 4. AppointmentController.php

**Tipo:** Controlador de dominio - Citas

**Responsabilidades:**
- Recibir solicitudes de cita
- Validar disponibilidad (días, horarios, no holidays)
- Verificar conflictos con citas existentes
- Envío de confirmaciones

**Métodos principales:**
```php
public function store(): void
public function index(): void
public function getAvailableSlots(string $date): array
public function validateAppointmentData(array $data): bool
```

**Validaciones especiales:**
- Fecha: no pasado, no fin de semana, no holiday
- Horario: dentro de rangos configurados (09:00-17:00)
- No doble booking: verificar que no exista cita en mismo slot
- Slot duration: 30 minutos (configurable)

**Dependencias:**
- Database
- RateLimiter
- Validator
- Security
- Mailer

**Flujo de datos:**
```
POST /appointment → validateDate() → validateTime() → checkConflict()
→ insertDB() → sendConfirmation() → JSON response
```

**Issues técnicos:**
- ❌ No hay waitlist para slots ocupados
- ❌ Falta recordatorio automático (email/SMS)
- ⚠️ No hay timezone handling (asume timezone local)

---

### 5. AuthController.php

**Tipo:** Controlador de autenticación

**Responsabilidades:**
- Login de admins
- Logout
- Mantener sesión activa
- Verificar permisos

**Métodos principales:**
```php
public function login(): void
public function logout(): void
public function checkAuth(): bool
public function getCurrentAdmin(): ?array
```

**Seguridad:**
- Passwords hasheados con `password_hash()`
- Rate limiting: 5 intentos por 15 minutos
- Session regeneration en login exitoso
- HTTP-only cookies

**Dependencias:**
- Database (tabla admins)
- Security (CSRF)
- RateLimiter

**Issues técnicos:**
- ❌ No hay 2FA/MFA
- ❌ No hay "remember me" funcional
- ⚠️ Falta logout automático por inactividad

---

### 6. Security.php

**Tipo:** Helper de seguridad

**Responsabilidades:**
- Generar/verificar tokens CSRF
- Sanitizar inputs
- Headers de seguridad

**Métodos principales:**
```php
public static function generateCSRFToken(): string
public static function verifyCSRFToken(string $token): bool
public static function sanitize(string $input): string
public static function setSecurityHeaders(): void
```

**CSRF Configuration:**
- Token expiry: 2 horas (configurable)
- Storage: $_SESSION

**Headers configurados:**
- X-Content-Type-Options: nosniff
- X-Frame-Options: DENY
- X-XSS-Protection: 1; mode=block
- Referrer-Policy: strict-origin-when-cross-origin

---

### 7. Mailer.php

**Tipo:** Servicio de email

**Responsabilidades:**
- Envío de emails transaccionales
- Templates de email
- Manejo de errores

**Métodos principales:**
```php
public function send(array $to, string $subject, string $body): bool
public function sendLeadNotification(array $lead): bool
public function sendAppointmentConfirmation(array $appointment): bool
```

**Configuración (.env):**
```
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=user@example.com
MAIL_PASSWORD=password
MAIL_FROM=notifications@domain.com
MAIL_FROM_NAME="Consultoría Nexus"
```

**Issues técnicos:**
- ⚠️ No hay queue de emails (envío síncrono)
- ⚠️ No hay retry automático en fallos

---

### 8. RateLimiter.php

**Tipo:** Helper anti-spam

**Responsabilidades:**
- Limitar requests por IP
- Prevenir flooding
- Proteger formularios

**Métodos principales:**
```php
public static function check(string $identifier, int $maxAttempts, int $windowMinutes): bool
public static function record(string $identifier): void
public static function isBlocked(string $identifier): bool
```

**Límites configurados:**
- Leads: 3 por ventana de 10 min
- Appointments: 3 por ventana de 10 min
- Login: 5 intentos por 15 min

**Storage:** SQLite (rate_limits table)

---

## 🗄️ Base de Datos - Análisis Detallado

### Schema completo

```sql
-- Leads (Contactos)
CREATE TABLE leads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT,
    service_interest TEXT,
    message TEXT,
    source TEXT DEFAULT 'form',
    status TEXT DEFAULT 'new',
    ip_address TEXT,
    user_agent TEXT,
    created_at TEXT DEFAULT (datetime('now', 'localtime'))
);
-- Índices: idx_leads_email, idx_leads_status, idx_leads_created

-- Appointments (Citas)
CREATE TABLE appointments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT NOT NULL,
    appointment_date TEXT NOT NULL,
    appointment_time TEXT NOT NULL,
    service_interest TEXT,
    message TEXT,
    status TEXT DEFAULT 'pending',
    admin_notes TEXT,
    ip_address TEXT,
    created_at TEXT DEFAULT (datetime('now', 'localtime'))
);
-- Índices: idx_appointments_date, idx_appointments_status

-- Admins (Usuarios admin)
CREATE TABLE admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    email TEXT,
    name TEXT,
    role TEXT DEFAULT 'admin',
    last_login TEXT,
    created_at TEXT DEFAULT (datetime('now', 'localtime'))
);

-- Admin Log (Auditoría)
CREATE TABLE admin_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    admin_id INTEGER,
    action TEXT NOT NULL,
    details TEXT,
    ip_address TEXT,
    created_at TEXT DEFAULT (datetime('now', 'localtime'))
);

-- Services (Servicios)
CREATE TABLE services (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    icon TEXT NOT NULL,
    title TEXT NOT NULL,
    short_desc TEXT,
    full_desc TEXT,
    active INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    created_at TEXT DEFAULT (datetime('now', 'localtime'))
);

-- Schedules (Horarios)
CREATE TABLE schedules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    day TEXT NOT NULL,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    active INTEGER DEFAULT 1
);

-- Blocked Dates (Feriados)
CREATE TABLE blocked_dates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    blocked_date TEXT UNIQUE NOT NULL,
    reason TEXT
);

-- Testimonials (Testimonios)
CREATE TABLE testimonials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    company TEXT,
    role TEXT,
    text TEXT NOT NULL,
    rating INTEGER DEFAULT 5,
    order_num INTEGER DEFAULT 0,
    active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now', 'localtime'))
);

-- FAQ Items (Preguntas frecuentes)
CREATE TABLE faq_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    order_num INTEGER DEFAULT 0,
    active INTEGER DEFAULT 1,
    created_at TEXT DEFAULT (datetime('now', 'localtime'))
);

-- Settings (Configuración)
CREATE TABLE settings (
    key TEXT PRIMARY KEY,
    value TEXT,
    updated_at TEXT DEFAULT (datetime('now', 'localtime'))
);

-- Rate Limits (Anti-spam)
CREATE TABLE rate_limits (
    identifier TEXT NOT NULL,
    action TEXT NOT NULL,
    attempts INTEGER DEFAULT 0,
    window_start TEXT NOT NULL
);
```

### Datos seed

**Services (4):**
1. Consultoría Estratégica - icon: target
2. Transformación Digital - icon: zap
3. Gestión de Proyectos - icon: layers
4. Capacitación Empresarial - icon: graduation-cap

**Testimonials (3):**
1. Juan Pérez - CEO TechStart
2. María González - Gerente Marketing
3. Carlos Rodríguez - Director Operations

**Process Steps (4):**
1. Consulta inicial
2. Análisis profundo
3. Plan de acción
4. Implementación

**FAQs (5):** preguntas frecuentes configurables

---

## 🎨 Frontend - Análisis Detallado

### Variables CSS (app.css)

```css
:root {
    --color-primary: #1a3a5c;     /* Azul marino */
    --color-accent: #e8a020;       /* Dorado */
    --color-bg: #f8f7f4;          /* Crema */
    --transition-speed: 0.3s;     /* Transiciones */
}
```

### Componentes reutilizables

| Clase | Líneas | Props |
|-------|--------|-------|
| `.card` | 299-308 | bg-white, rounded-12px, border, hover:shadow |
| `.time-slot` | 147-171 | estados: default, hover, selected, disabled |
| `.alert`, `.alert-success`, `.alert-error` | 314-330 | feedback visual |
| `.spinner` | 187-200 | loading indicator animado |
| `.whatsapp-float` | 250-293 | botón fijo con tooltip |
| `.skip-link` | 343-356 | accesibilidad |

### Animaciones

```css
@keyframes spin { from { rotate: 0deg } to { rotate: 360deg } }
@keyframes fade-in { from { opacity: 0 } to { opacity: 1 } }
```

### Hero Pattern

```css
.hero-pattern {
    background-color: var(--color-primary);
    background-image: 
        radial-gradient(circle at 25% 25%, rgba(255,255,255,0.1) 2%, transparent 2.5%),
        radial-gradient(circle at 75% 75%, rgba(255,255,255,0.08) 2%, transparent 2.5%);
    background-size: 60px 60px;
}
```

---

## 📄 Secciones - Análisis Detallado

### 1. hero.php

**Variables:** `$businessTagline`, `$businessDescription`, `$businessYears`, `$businessClients`, `$businessProjects`

**Estructura:**
```html
<section id="hero" class="hero-pattern min-h-screen">
    <!-- Título H1 con font-serif -->
    <!-- Descripción -->
    <!-- Stats: 3 columnas (Años, Clientes, Proyectos) -->
    <!-- CTAs: 2 botones (Primario dorado, Secundario outline) -->
</section>
```

**Iconos Lucide:** `arrow-right`

**Responsive:** `md:` (768px) para padding y tamaños de fuente

---

### 2. services.php

**Variables:** `$services` (array de objetos)

**Estructura:**
```html
<section id="servicios">
    <!-- Header: título + badge "Nuestros Servicios" -->
    <!-- Grid: 2x2 en desktop, 1 columna en móvil -->
    <!-- Cards: icono + título + descripción corta + CTA expandir -->
    <!-- Modal: descripción completa al expandir -->
</section>
```

**Iconos Lucide:** configurable por servicio (target, zap, layers, etc.)

---

### 3. about.php (REDISEÑADO)

**Variables:** `$businessName`, `$businessYears`, `$businessClients`, `$businessProjects`, `$differentiators`

**Estructura:**
```html
<section id="nosotros">
    <!-- Header con tipografía elegante + línea decorativa -->
    <!-- Grid 12 cols: 7 contenido + 5 estadísticas -->
    <!-- Historia con borde lateral accent -->
    <!-- Misión y Visión lado a lado -->
    <!-- Card estadísticas con fondo primary -->
    <!-- Diferenciadores: 4 columnas con hover effects -->
</section>
```

**Iconos Lucide:** target, eye, award, calendar, users, briefcase

---

### 4. testimonials.php

**Variables:** `$testimonials` (array de objetos con name, company, role, text, rating)

**Estructura:**
```html
<section id="testimonios">
    <!-- Header -->
    <!-- Carousel/Grid de testimonios -->
    <!-- Cards con avatar, nombre, rol, empresa, texto, rating -->
</section>
```

**Iconos Lucide:** quote, star

---

### 5. process.php

**Variables:** `$processSteps` (array de objetos con step, title, description)

**Estructura:**
```html
<section id="proceso">
    <!-- Timeline vertical u horizontal -->
    <!-- Steps numerados con conectores -->
    <!-- Descripción de cada paso -->
</section>
```

---

### 6. faq.php

**Variables:** `$faqItems` (array de objetos con question, answer)

**Estructura:**
```html
<section id="faq">
    <!-- Acordeón con <details> -->
    <!-- Pregunta como summary (clickable) -->
    <!-- Respuesta expandible -->
    <!-- Chevron animado en open/close -->
</section>
```

**CSS:** chevron rotation animation

---

### 7. contact.php

**Variables:** ninguna (formulario genérico)

**Campos:**
- Nombre (required)
- Email (required)
- Teléfono (optional)
- Servicio de interés (select)
- Mensaje (textarea)

**Features:**
- CSRF token
- Honeypot anti-spam
- Validación client-side
- Toast de confirmación

---

### 8. appointments.php

**Variables:** `$services`, horarios disponibles

**Estructura:**
```html
<section id="citas">
    <!-- Calendario selector de fecha -->
    <!-- Slots disponibles (30 min) -->
    <!-- Formulario: nombre, email, teléfono, mensaje -->
    <!-- Confirmación visual -->
</section>
```

**Validaciones:**
- Fecha no pasado
- No fin de semana
- No holidays
- Slot disponible

---

## 🔐 Seguridad

### Implementado
- ✅ CSRF tokens en todos los formularios
- ✅ Rate limiting (3 leads/citas por 10 min)
- ✅ Password hashing (password_hash)
- ✅ Sanitización de inputs
- ✅ Headers de seguridad
- ✅ Honeypot anti-spam
- ✅ IP tracking para auditoría

### Pendiente
- ❌ 2FA/MFA para admins
- ❌ Logout automático por inactividad
- ❌ Encriptación de datos sensibles en DB
- ❌ Rate limiting por endpoint específico

---

## 🚀 Deployment

### URL Producción
`https://landing.mauricioramos.tech`

### Variables de entorno requeridas
```
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=sqlite
DB_DATABASE=database/leads.db
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
NOTIFICATION_EMAIL=mpuc19017@gmail.com
```

---

## 📝 Tareas Comunes

### Agregar nueva sección
1. Crear `views/sections/nueva.php`
2. Agregar al array `$sections` en `index.php`
3. Definir variables en `config.php`

### Modificar colores
1. Editar CSS variables en `config.php`
2. Actualizar `:root` en `public/css/app.css`

### Agregar servicio
1. INSERT INTO services (icon, title, short_desc, full_desc)
2. O agregar en array `$services` en `config.php`

### Exportar leads
1. Ir a `/admin/` → Leads
2. Click "Exportar CSV"

---

## 🔄 API Endpoints

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/lead` | Guardar lead |
| POST | `/appointment` | Agendar cita |
| GET | `/admin` | Dashboard |
| POST | `/admin/login` | Login admin |
| POST | `/admin/logout` | Logout admin |

---

## 📊 Métricas del Proyecto

- **Controllers:** 8 archivos
- **Tablas DB:** 11
- **Secciones:** 8
- **Líneas CSS:** ~400
- **Endpoints:** 5 principales

---

*Última actualización: 2026-05-31*
*Documentación generada con subagentes de análisis*