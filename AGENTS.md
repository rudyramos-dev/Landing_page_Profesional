# AGENTS.md - Landing Page Profesional

## Overview
Landing page para "Consultoría Nexus" - Consultoría empresarial con sistema de citas y captura de leads.

**Stack:** PHP Vanilla + SQLite/MySQL + TailwindCSS + Lucide Icons
**Tema:** Azul marino (#1a3a5c) + Dorado (#e8a020)

---

## Project Structure

```
├── app/                    # Lógica de negocio (PHP)
├── views/                  # Vistas (layouts + sections)
├── database/               # SQLite (leads.db)
├── public/                 # Assets estáticos
├── config.php              # Configuración global
└── index.php               # Entry point
```

---

## Architecture

### Controllers (app/)
| Archivo | Responsabilidad |
|---------|----------------|
| `AdminController.php` | Panel admin: dashboard, gestión leads/citas |
| `LeadController.php` | Captura leads, validación, exportación CSV |
| `AppointmentController.php` | Agenda citas, validación disponibilidad |
| `AuthController.php` | Login/logout admins, sesiones |
| `Mailer.php` | Envío de emails (notificaciones) |
| `Security.php` | CSRF, rate limiting, sanitización |
| `Validator.php` | Validación de inputs |
| `RateLimiter.php` | Protección contra spam |

### Database Schema (SQLite/MySQL)
| Tabla | Propósito |
|-------|-----------|
| `leads` | Prospectos del formulario contacto |
| `appointments` | Citas agendadas |
| `admins` | Cuentas panel admin |
| `services` | Catálogo de servicios |
| `schedules` | Horarios disponibles |
| `testimonials` | Testimonios en landing |
| `faq_items` | FAQs |
| `settings` | Configuración dinámica |

---

## Key Variables (config.php)

**Business Info:**
- `$businessName = "Consultoría Nexus"`
- `$businessTagline = "Soluciones estratégicas para tu empresa"`
- `$businessYears = "8"`, `$businessClients = "120+"`, `$businessProjects = "340+"`

**Contact:**
- Tel: configurable
- Email notificaciones: `mpuc19017@gmail.com`
- WhatsApp: configurable

**Design:**
- Primary: `#1a3a5c`, Accent: `#e8a020`, BG: `#f8f7f4`

---

## Views Structure

### Layouts
- `layouts/head.php` - SEO, meta tags, CSS, Schema.org
- `layouts/header.php` - Navbar responsive
- `layouts/footer.php` - Footer + WhatsApp button

### Sections (orden en landing)
1. `hero.php` - Hero principal con stats
2. `services.php` - Servicios ofrecidos
3. `about.php` - "Por qué elegirnos"
4. `testimonials.php` - Testimonios
5. `process.php` - Proceso de trabajo
6. `faq.php` - Preguntas frecuentes
7. `contact.php` - Formulario leads
8. `appointments.php` - Agendar cita

---

## Important Notes

### Security
- CSRF tokens en todos los formularios
- Rate limiting: 3 leads/citas por 10 min
- Passwords hasheados (password_hash)

### Admin Panel
- URL: `/admin/`
- Dashboard con estadísticas
- CRUD de servicios, testimonios, FAQs

### API Endpoints
- `POST /lead` - Guardar lead
- `POST /appointment` - Agendar cita
- `POST /admin/login` - Login admin

---

## Common Tasks

### Agregar nueva sección
1. Crear `views/sections/nueva.php`
2. Agregar al array `$sections` en `index.php`
3. Definir variables en `config.php`

### Modificar colores
Editar CSS variables en `config.php` y `public/css/app.css`

### Agregar servicio
1. Insertar en tabla `services`
2. O agregar en array `$services` en `config.php`
