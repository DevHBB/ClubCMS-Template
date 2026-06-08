<?php
/**
 * ClubCMS — Journal d'activité
 */
class ActivityLog {
    public static function log(string $action, string $entity = '', int $entityId = 0, array $details = []): void {
        try {
            Database::run(
                "CREATE TABLE IF NOT EXISTS cc_activity_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT DEFAULT NULL, action VARCHAR(100) NOT NULL,
                    entity VARCHAR(50) DEFAULT NULL, entity_id INT DEFAULT NULL,
                    details JSON DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )"
            );
            Database::run(
                "INSERT INTO cc_activity_log (user_id,action,entity,entity_id,details,ip) VALUES (?,?,?,?,?,?)",
                [
                    Auth::id() ?: null,
                    $action,
                    $entity ?: null,
                    $entityId ?: null,
                    !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                ]
            );
        } catch(Exception $e) {
            error_log('ActivityLog error: ' . $e->getMessage());
        }
    }

    public static function recent(int $limit = 50): array {
        try {
            return Database::all(
                "SELECT l.*, u.firstname, u.lastname FROM cc_activity_log l
                 LEFT JOIN cc_users u ON l.user_id = u.id
                 ORDER BY l.created_at DESC LIMIT ?",
                [$limit]
            );
        } catch(Exception $e) { return []; }
    }
}
