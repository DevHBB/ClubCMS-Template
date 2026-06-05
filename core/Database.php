<?php
/**
 * ClubCMS — Connexion base de données (Singleton PDO)
 */

class Database {
    private static ?PDO $instance = null;

    public static function get(): PDO {
        if (self::$instance === null) {
            try {
                self::$instance = new PDO(
                    'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                    DB_USER, DB_PASS,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                    ]
                );
            } catch (PDOException $e) {
                http_response_code(503);
                throw new RuntimeException('DB_CONNECTION_FAILED: ' . $e->getMessage());
            }
        }
        return self::$instance;
    }

    /**
     * Raccourci : requête préparée → retourne tous les résultats
     */
    public static function all(string $sql, array $params = []): array {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Raccourci : retourne une seule ligne
     */
    public static function one(string $sql, array $params = []): ?array {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * INSERT / UPDATE / DELETE → retourne le nombre de lignes affectées
     */
    public static function run(string $sql, array $params = []): int {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * INSERT → retourne le dernier ID inséré
     */
    public static function insert(string $sql, array $params = []): string {
        self::run($sql, $params);
        return self::get()->lastInsertId();
    }

    /**
     * Retourne une seule valeur scalaire
     */
    public static function scalar(string $sql, array $params = []): mixed {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
