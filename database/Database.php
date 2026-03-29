<?php
/**
 * Database.php - Singleton PDO with multi-driver support
 *
 * Supports sqlite (default for demo) and mysql (for client production).
 */

if (!defined('APP_ROOT')) {
    die('Acceso directo no permitido');
}

class Database
{
    private static ?PDO $instance = null;
    private static string $driver = 'sqlite';
    private static string $dbPath = '';

    private function __construct() {}
    private function __clone() {}

    public function __wakeup()
    {
        throw new Exception('Cannot unserialize singleton');
    }

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::connect();
            self::createTables();
        }

        return self::$instance;
    }

    public static function getDriver(): string
    {
        return self::$driver;
    }

    public static function isSqlite(): bool
    {
        return self::$driver === 'sqlite';
    }

    public static function isMysql(): bool
    {
        return self::$driver === 'mysql';
    }

    public static function currentTimestampExpression(): string
    {
        return self::isMysql() ? 'NOW()' : "datetime('now','localtime')";
    }

    public static function currentDateExpression(): string
    {
        return self::isMysql() ? 'CURDATE()' : "date('now', 'localtime')";
    }

    public static function dateDaysAgoExpression(int $days): string
    {
        $days = max(0, $days);
        return self::isMysql() ? "DATE_SUB(CURDATE(), INTERVAL {$days} DAY)" : "date('now', '-{$days} days', 'localtime')";
    }

    public static function dateDaysAheadExpression(int $days): string
    {
        $days = max(0, $days);
        return self::isMysql() ? "DATE_ADD(CURDATE(), INTERVAL {$days} DAY)" : "date('now', '+{$days} days', 'localtime')";
    }

    public static function datetimeHoursAgoExpression(int $hours): string
    {
        $hours = max(0, $hours);
        return self::isMysql() ? "DATE_SUB(NOW(), INTERVAL {$hours} HOUR)" : "datetime('now', '-{$hours} hour', 'localtime')";
    }

    private static function connect(): void
    {
        $requestedDriver = strtolower((string)(DB_CONNECTION ?? 'sqlite'));
        self::$driver = in_array($requestedDriver, ['sqlite', 'mysql'], true) ? $requestedDriver : 'sqlite';

        try {
            if (self::$driver === 'mysql') {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    DB_HOST,
                    DB_PORT,
                    DB_DATABASE,
                    DB_CHARSET
                );

                self::$instance = new PDO($dsn, DB_USERNAME, DB_PASSWORD, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } else {
                self::$dbPath = DB_PATH;

                $dir = dirname(self::$dbPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                self::$instance = new PDO('sqlite:' . self::$dbPath, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);

                self::$instance->exec('PRAGMA journal_mode=WAL');
                self::$instance->exec('PRAGMA busy_timeout=5000');
                self::$instance->exec('PRAGMA foreign_keys=ON');
            }
        } catch (PDOException $e) {
            error_log('Database connection error: ' . $e->getMessage());
            throw new Exception('Error de conexion a la base de datos');
        }
    }

    private static function createTables(): void
    {
        if (self::isMysql()) {
            self::createTablesMysql();
        } else {
            self::createTablesSqlite();
        }

        self::ensureSchemaCompatibility();

        self::createDefaultAdmin();
        self::createDefaultServicesAndSchedules();
        self::seedDefaultBusinessSettings();
    }

    private static function ensureSchemaCompatibility(): void
    {
        try {
            if (self::isMysql()) {
                self::ensureMysqlColumn('services', 'icon', "ALTER TABLE services ADD COLUMN icon VARCHAR(80) DEFAULT 'briefcase'");
                self::ensureMysqlColumn('services', 'short_desc', 'ALTER TABLE services ADD COLUMN short_desc TEXT NULL');
                self::ensureMysqlColumn('services', 'full_desc', 'ALTER TABLE services ADD COLUMN full_desc TEXT NULL');
                return;
            }

            self::ensureSqliteColumn('services', 'icon', "ALTER TABLE services ADD COLUMN icon TEXT DEFAULT 'briefcase'");
            self::ensureSqliteColumn('services', 'short_desc', 'ALTER TABLE services ADD COLUMN short_desc TEXT');
            self::ensureSqliteColumn('services', 'full_desc', 'ALTER TABLE services ADD COLUMN full_desc TEXT');
        } catch (Throwable $e) {
            error_log('Error en migracion de esquema: ' . $e->getMessage());
        }
    }

    private static function ensureSqliteColumn(string $table, string $column, string $alterSql): void
    {
        $stmt = self::$instance->query("PRAGMA table_info({$table})");
        $columns = $stmt->fetchAll();

        foreach ($columns as $row) {
            if (($row['name'] ?? '') === $column) {
                return;
            }
        }

        self::$instance->exec($alterSql);
    }

    private static function ensureMysqlColumn(string $table, string $column, string $alterSql): void
    {
        $stmt = self::$instance->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
        $stmt->execute([$column]);
        $exists = $stmt->fetch();

        if ($exists) {
            return;
        }

        self::$instance->exec($alterSql);
    }

    private static function createTablesSqlite(): void
    {
        $pdo = self::$instance;

        $pdo->exec("CREATE TABLE IF NOT EXISTS leads (
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
            created_at TEXT DEFAULT (datetime('now','localtime'))
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_leads_email ON leads(email)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_leads_status ON leads(status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_leads_created ON leads(created_at)');

        $pdo->exec("CREATE TABLE IF NOT EXISTS appointments (
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
            confirmation_sent INTEGER DEFAULT 0,
            reminder_sent INTEGER DEFAULT 0,
            ip_address TEXT,
            created_at TEXT DEFAULT (datetime('now','localtime')),
            updated_at TEXT DEFAULT (datetime('now','localtime'))
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_appt_date ON appointments(appointment_date)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_appt_status ON appointments(status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_appt_email ON appointments(email)');

        $pdo->exec("CREATE TABLE IF NOT EXISTS rate_limits (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address TEXT NOT NULL,
            action TEXT NOT NULL,
            attempts INTEGER DEFAULT 1,
            window_start TEXT DEFAULT (datetime('now','localtime')),
            last_attempt TEXT DEFAULT (datetime('now','localtime'))
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_rate_ip_action ON rate_limits(ip_address, action)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_rate_window ON rate_limits(window_start)');

        $pdo->exec("CREATE TABLE IF NOT EXISTS csrf_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            token TEXT NOT NULL UNIQUE,
            ip_address TEXT,
            created_at TEXT DEFAULT (datetime('now','localtime')),
            used INTEGER DEFAULT 0
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_csrf_token ON csrf_tokens(token)');

        $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            last_login TEXT,
            login_attempts INTEGER DEFAULT 0,
            locked_until TEXT,
            created_at TEXT DEFAULT (datetime('now','localtime'))
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id INTEGER,
            action TEXT NOT NULL,
            target_type TEXT,
            target_id INTEGER,
            details TEXT,
            ip_address TEXT,
            created_at TEXT DEFAULT (datetime('now','localtime'))
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            duration INTEGER DEFAULT 60,
            price REAL DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            sort_order INTEGER DEFAULT 0,
            icon TEXT DEFAULT 'briefcase',
            short_desc TEXT,
            full_desc TEXT,
            created_at TEXT DEFAULT (datetime('now','localtime'))
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_services_active ON services(is_active)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_services_order ON services(sort_order)');

        $pdo->exec("CREATE TABLE IF NOT EXISTS schedules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            day_of_week INTEGER NOT NULL,
            start_time TEXT NOT NULL,
            end_time TEXT NOT NULL,
            slot_duration INTEGER DEFAULT 60,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT (datetime('now','localtime'))
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_schedules_day ON schedules(day_of_week)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_schedules_active ON schedules(is_active)');

        $pdo->exec("CREATE TABLE IF NOT EXISTS blocked_dates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            blocked_date TEXT NOT NULL UNIQUE,
            reason TEXT,
            created_at TEXT DEFAULT (datetime('now','localtime'))
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_blocked_date ON blocked_dates(blocked_date)');

        $pdo->exec("CREATE TABLE IF NOT EXISTS testimonials (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            company TEXT,
            text TEXT NOT NULL,
            initials TEXT,
            color TEXT,
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT (datetime('now','localtime'))
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_testimonials_order ON testimonials(sort_order)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_testimonials_active ON testimonials(is_active)');

        $pdo->exec("CREATE TABLE IF NOT EXISTS faq_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            question TEXT NOT NULL,
            answer TEXT NOT NULL,
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT (datetime('now','localtime'))
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_faq_order ON faq_items(sort_order)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_faq_active ON faq_items(is_active)');

        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT NOT NULL UNIQUE,
            setting_value TEXT,
            updated_at TEXT DEFAULT (datetime('now','localtime'))
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_settings_key ON settings(setting_key)');
    }

    private static function createTablesMysql(): void
    {
        $pdo = self::$instance;

        $pdo->exec("CREATE TABLE IF NOT EXISTS leads (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(30) NULL,
            service_interest VARCHAR(255) NULL,
            message TEXT NULL,
            source VARCHAR(50) DEFAULT 'form',
            status VARCHAR(50) DEFAULT 'new',
            ip_address VARCHAR(64) NULL,
            user_agent TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_leads_email (email),
            INDEX idx_leads_status (status),
            INDEX idx_leads_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS appointments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(30) NOT NULL,
            appointment_date DATE NOT NULL,
            appointment_time TIME NOT NULL,
            service_interest VARCHAR(255) NULL,
            message TEXT NULL,
            status VARCHAR(50) DEFAULT 'pending',
            admin_notes TEXT NULL,
            confirmation_sent TINYINT(1) DEFAULT 0,
            reminder_sent TINYINT(1) DEFAULT 0,
            ip_address VARCHAR(64) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_appt_date (appointment_date),
            INDEX idx_appt_status (status),
            INDEX idx_appt_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS rate_limits (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(64) NOT NULL,
            action VARCHAR(100) NOT NULL,
            attempts INT DEFAULT 1,
            window_start DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_rate_ip_action (ip_address, action),
            INDEX idx_rate_window (window_start)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS csrf_tokens (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(128) NOT NULL UNIQUE,
            ip_address VARCHAR(64) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            used TINYINT(1) DEFAULT 0,
            INDEX idx_csrf_token (token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(120) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            last_login DATETIME NULL,
            login_attempts INT DEFAULT 0,
            locked_until DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            admin_id BIGINT UNSIGNED NULL,
            action VARCHAR(120) NOT NULL,
            target_type VARCHAR(120) NULL,
            target_id BIGINT NULL,
            details TEXT NULL,
            ip_address VARCHAR(64) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin_log_admin (admin_id),
            INDEX idx_admin_log_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS services (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            duration INT DEFAULT 60,
            price DECIMAL(10,2) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            sort_order INT DEFAULT 0,
            icon VARCHAR(80) DEFAULT 'briefcase',
            short_desc TEXT NULL,
            full_desc TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_services_active (is_active),
            INDEX idx_services_order (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS schedules (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            day_of_week TINYINT NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            slot_duration INT DEFAULT 60,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_schedules_day (day_of_week),
            INDEX idx_schedules_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS blocked_dates (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            blocked_date DATE NOT NULL UNIQUE,
            reason VARCHAR(255) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_blocked_date (blocked_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS testimonials (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            company VARCHAR(255) NULL,
            text TEXT NOT NULL,
            initials VARCHAR(10) NULL,
            color VARCHAR(20) NULL,
            sort_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_testimonials_order (sort_order),
            INDEX idx_testimonials_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS faq_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            question TEXT NOT NULL,
            answer TEXT NOT NULL,
            sort_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_faq_order (sort_order),
            INDEX idx_faq_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(120) NOT NULL UNIQUE,
            setting_value TEXT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_settings_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    private static function createDefaultAdmin(): void
    {
        $pdo = self::$instance;

        $stmt = $pdo->query('SELECT COUNT(*) as count FROM admins');
        $result = $stmt->fetch();

        if ((int)$result['count'] !== 0) {
            return;
        }

        $tempPassword = bin2hex(random_bytes(8));
        $passwordHash = password_hash($tempPassword, PASSWORD_ARGON2ID);

        $stmt = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (?, ?)');
        $stmt->execute(['admin', $passwordHash]);

        if (self::isSqlite()) {
            $credentialsFile = dirname(self::$dbPath) . '/initial_credentials.txt';
            $credentialsContent = "=== CREDENCIALES INICIALES DEL PANEL ADMIN ===\n";
            $credentialsContent .= "Usuario: admin\n";
            $credentialsContent .= "Contrasena: {$tempPassword}\n";
            $credentialsContent .= "=== CAMBIA ESTA CONTRASENA INMEDIATAMENTE ===\n";
            $credentialsContent .= 'Fecha de creacion: ' . date('Y-m-d H:i:s') . "\n";
            $credentialsContent .= "\nELIMINA ESTE ARCHIVO DESPUES DE LEERLO\n";

            file_put_contents($credentialsFile, $credentialsContent);
            @chmod($credentialsFile, 0600);
            return;
        }

        error_log("[INIT_ADMIN] Usuario: admin | Password temporal: {$tempPassword}");
    }

    private static function createDefaultServicesAndSchedules(): void
    {
        $pdo = self::$instance;

        $stmt = $pdo->query('SELECT COUNT(*) as count FROM services');
        $result = $stmt->fetch();

        if ((int)$result['count'] === 0) {
            $insertSql = 'INSERT INTO services (name, description, duration, price, sort_order, icon, short_desc, full_desc) VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
            $stmt = $pdo->prepare($insertSql);

            foreach (SERVICES as $index => $service) {
                $name = (string)($service['title'] ?? 'Servicio');
                $shortDesc = (string)($service['short_desc'] ?? '');
                $fullDesc = (string)($service['full_desc'] ?? $shortDesc);
                $icon = (string)($service['icon'] ?? 'briefcase');
                $duration = 60;

                $stmt->execute([$name, $shortDesc, $duration, 0, $index + 1, $icon, $shortDesc, $fullDesc]);
            }
        }

        $stmt = $pdo->query('SELECT COUNT(*) as count FROM schedules');
        $result = $stmt->fetch();

        if ((int)$result['count'] === 0) {
            $stmt = $pdo->prepare('INSERT INTO schedules (day_of_week, start_time, end_time, slot_duration) VALUES (?, ?, ?, ?)');

            foreach (AVAILABLE_DAYS as $day) {
                $stmt->execute([(int)$day, APPOINTMENT_START, APPOINTMENT_END, APPOINTMENT_SLOT]);
            }
        }

        $stmt = $pdo->query('SELECT COUNT(*) as count FROM blocked_dates');
        $result = $stmt->fetch();

        if ((int)$result['count'] === 0 && !empty(BLOCKED_DATES)) {
            $stmt = $pdo->prepare('INSERT INTO blocked_dates (blocked_date, reason) VALUES (?, ?)');
            foreach (BLOCKED_DATES as $date) {
                $stmt->execute([$date, 'Fecha bloqueada inicial']);
            }
        }

        $stmt = $pdo->query('SELECT COUNT(*) as count FROM testimonials');
        $result = $stmt->fetch();
        if ((int)$result['count'] === 0 && !empty(TESTIMONIALS)) {
            $stmt = $pdo->prepare('INSERT INTO testimonials (name, company, text, initials, color, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)');
            foreach (TESTIMONIALS as $index => $testimonial) {
                $stmt->execute([
                    (string)($testimonial['name'] ?? ''),
                    (string)($testimonial['company'] ?? ''),
                    (string)($testimonial['text'] ?? ''),
                    (string)($testimonial['initials'] ?? ''),
                    (string)($testimonial['color'] ?? '#4A90A4'),
                    $index + 1,
                ]);
            }
        }

        $stmt = $pdo->query('SELECT COUNT(*) as count FROM faq_items');
        $result = $stmt->fetch();
        if ((int)$result['count'] === 0 && !empty(FAQ_ITEMS)) {
            $stmt = $pdo->prepare('INSERT INTO faq_items (question, answer, sort_order, is_active) VALUES (?, ?, ?, 1)');
            foreach (FAQ_ITEMS as $index => $faq) {
                $stmt->execute([
                    (string)($faq['question'] ?? ''),
                    (string)($faq['answer'] ?? ''),
                    $index + 1,
                ]);
            }
        }
    }

    private static function seedDefaultBusinessSettings(): void
    {
        $defaults = [
            'business_name' => BUSINESS_NAME,
            'business_tagline' => BUSINESS_TAGLINE,
            'business_description' => BUSINESS_DESCRIPTION,
            'business_years' => BUSINESS_YEARS,
            'business_clients' => BUSINESS_CLIENTS,
            'business_projects' => BUSINESS_PROJECTS,
            'seo_title' => SEO_TITLE,
            'seo_description' => SEO_DESCRIPTION,
            'seo_keywords' => SEO_KEYWORDS,
            'seo_og_image' => SEO_OG_IMAGE,
            'site_url' => SITE_URL,
            'business_phone' => BUSINESS_PHONE,
            'business_whatsapp' => BUSINESS_WHATSAPP,
            'business_email' => BUSINESS_EMAIL,
            'business_address' => BUSINESS_ADDRESS,
            'business_maps_url' => BUSINESS_MAPS_URL,
            'notify_email' => NOTIFY_EMAIL,
            'social_instagram' => SOCIAL_INSTAGRAM,
            'social_facebook' => SOCIAL_FACEBOOK,
            'social_linkedin' => SOCIAL_LINKEDIN,
            'color_primary' => COLOR_PRIMARY,
            'color_accent' => COLOR_ACCENT,
            'color_bg' => COLOR_BG,
            'whatsapp_message' => WHATSAPP_MESSAGE,
            'business_hours' => BUSINESS_HOURS,
            'developer_name' => DEVELOPER_NAME,
            'developer_url' => DEVELOPER_URL,
        ];

        $pdo = self::$instance;

        foreach ($defaults as $key => $value) {
            if (self::isMysql()) {
                $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = setting_value');
                $stmt->execute([$key, (string)$value]);
                continue;
            }

            $stmt = $pdo->prepare('INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)');
            $stmt->execute([$key, (string)$value]);
        }
    }

    public static function cleanupRateLimits(): void
    {
        $pdo = self::getInstance();

        $rateLimitCutoff = self::datetimeHoursAgoExpression(1);
        $csrfCutoff = self::datetimeHoursAgoExpression(2);

        $pdo->exec("DELETE FROM rate_limits WHERE window_start < {$rateLimitCutoff}");
        $pdo->exec("DELETE FROM csrf_tokens WHERE created_at < {$csrfCutoff} OR used = 1");
    }

    public static function close(): void
    {
        self::$instance = null;
    }
}
