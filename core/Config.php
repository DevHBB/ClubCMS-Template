<?php
/**
 * ClubCMS — Gestionnaire de configuration (lecture BDD avec cache)
 */

class Config {
    private static array $cache = [];
    private static bool  $loaded = false;

    /**
     * Charge toute la config en une seule requête
     */
    public static function load(): void {
        if (self::$loaded) return;
        $rows = Database::all("SELECT `key`, `value` FROM cc_config");
        foreach ($rows as $row) {
            self::$cache[$row['key']] = $row['value'];
        }
        self::$loaded = true;
    }

    /**
     * Retourne une valeur de config
     */
    public static function get(string $key, mixed $default = null): mixed {
        self::load();
        return self::$cache[$key] ?? $default;
    }

    /**
     * Met à jour une valeur en BDD et en cache
     */
    public static function set(string $key, mixed $value, string $group = 'general'): void {
        Database::run(
            "REPLACE INTO cc_config (`key`, `value`, `group`) VALUES (?, ?, ?)",
            [$key, $value, $group]
        );
        self::$cache[$key] = $value;
    }

    /**
     * Met à jour plusieurs valeurs à la fois
     */
    public static function setMany(array $values, string $group = 'general'): void {
        foreach ($values as $key => $value) {
            self::set($key, $value, $group);
        }
    }

    /**
     * Retourne toutes les clés d'un groupe
     */
    public static function getGroup(string $group): array {
        self::load();
        $rows = Database::all("SELECT `key`, `value` FROM cc_config WHERE `group` = ?", [$group]);
        $result = [];
        foreach ($rows as $row) {
            $result[$row['key']] = $row['value'];
        }
        return $result;
    }

    /**
     * Vérifie si le site est installé
     */
    public static function isInstalled(): bool {
        try {
            return (bool) Database::scalar("SELECT `value` FROM cc_config WHERE `key` = 'installed'");
        } catch (Exception $e) {
            return false;
        }
    }
}
