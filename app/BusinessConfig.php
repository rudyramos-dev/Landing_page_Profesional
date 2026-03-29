<?php
/**
 * BusinessConfig.php - Access layer for business settings
 *
 * Provides a single place to read brand/content settings from DB
 * with fallback to config constants for backward compatibility.
 */

if (!defined('APP_ROOT')) {
    die('Acceso directo no permitido');
}

class BusinessConfig
{
    private static ?array $cache = null;

    private const ALLOWED_KEYS = [
        'business_name',
        'business_tagline',
        'business_description',
        'business_years',
        'business_clients',
        'business_projects',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'seo_og_image',
        'site_url',
        'business_phone',
        'business_whatsapp',
        'business_email',
        'business_address',
        'business_maps_url',
        'notify_email',
        'social_instagram',
        'social_facebook',
        'social_linkedin',
        'color_primary',
        'color_accent',
        'color_bg',
        'whatsapp_message',
        'business_hours',
        'developer_name',
        'developer_url',
    ];

    public static function get(string $key): string
    {
        self::ensureLoaded();

        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        return '';
    }

    public static function all(): array
    {
        self::ensureLoaded();
        return self::$cache;
    }

    public static function save(array $payload): void
    {
        $pdo = Database::getInstance();

        if (Database::isMysql()) {
            $stmt = $pdo->prepare(
                'INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, ' . Database::currentTimestampExpression() . ') '
                . 'ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)'
            );
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (?, ?, ' . Database::currentTimestampExpression() . ') '
                . 'ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value, updated_at = excluded.updated_at'
            );
        }

        foreach (self::ALLOWED_KEYS as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            $value = is_string($payload[$key]) ? trim($payload[$key]) : '';
            $stmt->execute([$key, $value]);
        }

        self::$cache = null;
    }

    private static function ensureLoaded(): void
    {
        if (self::$cache !== null) {
            return;
        }

        self::$cache = self::defaults();

        try {
            $pdo = Database::getInstance();
            $stmt = $pdo->query('SELECT setting_key, setting_value FROM settings');
            $rows = $stmt->fetchAll();

            foreach ($rows as $row) {
                $key = (string)($row['setting_key'] ?? '');
                if (!in_array($key, self::ALLOWED_KEYS, true)) {
                    continue;
                }
                self::$cache[$key] = (string)($row['setting_value'] ?? '');
            }
        } catch (Throwable $e) {
            error_log('Error cargando settings: ' . $e->getMessage());
        }
    }

    private static function defaults(): array
    {
        return [
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
    }
}
