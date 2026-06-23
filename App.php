<?php

/**
 * Forms — Application container
 *
 * Static access to core dependencies: database, config, and translations.
 * Eliminates "undefined variable" IDE warnings in included view/api files.
 *
 * Usage:
 *   App::db()         — PDO instance
 *   App::config()     — configuration array
 *   App::lang()       — translation dict for current language
 *   App::langCode()   — current language code ('en' or 'zh')
 *   App::isAdmin()    — whether admin session is active
 */
class App
{
    public static PDO $db;
    public static array $config;
    public static array $lang;
    public static string $langCode = 'en';

    // ── Accessors ──────────────────────────────────────────────

    public static function db(): PDO
    {
        return self::$db;
    }

    /** @return array */
    public static function config(): array
    {
        return self::$config;
    }

    /** @return array */
    public static function lang(): array
    {
        return self::$lang;
    }

    public static function langCode(): string
    {
        return self::$langCode;
    }

    // ── Shorthand helpers ──────────────────────────────────────

    /** Localised string by key */
    public static function t(string $key): string
    {
        return self::$lang[$key] ?? $key;
    }

    /** Config value by key */
    public static function cfg(string $key): mixed
    {
        return self::$config[$key] ?? null;
    }

    // ── Auth helpers ───────────────────────────────────────────

    public static function isAdmin(): bool
    {
        return !empty($_SESSION['admin_logged_in']);
    }

    /** Check if user is authenticated for a specific form query */
    public static function isQueryUser(int $formId = 0): bool
    {
        if ($formId > 0) {
            return !empty($_SESSION['query_logged_in'][$formId]);
        }
        return !empty($_SESSION['query_logged_in']);
    }

    public static function requireAdmin(): void
    {
        if (!self::isAdmin()) {
            header('Location: /admin/login');
            exit;
        }
    }

    /** Load a view file, injecting all variables passed in $data */
    public static function view(string $path, array $data = []): void
    {
        extract($data);
        require $path;
    }
}
