<?php
/**
 * ClubCMS — Helpers
 */
class Helpers {

    // ── Base path (détecté une fois) ─────────────────────────────
    private static ?string $basePath = null;

    public static function basePath(): string {
        if (self::$basePath === null) {
            // Ex: SCRIPT_NAME = /clubcms/index.php -> basePath = /clubcms
            // Ex: SCRIPT_NAME = /index.php         -> basePath = 
            $dir = dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php');
            self::$basePath = rtrim(str_replace('\\', '/', $dir), '/');
        }
        return self::$basePath;
    }

    /**
     * Génère une URL absolue depuis la racine du site.
     * Fonctionne avec ET sans mod_rewrite, dans un sous-dossier ou à la racine.
     */
    public static function url(string $path, bool $forceCompat = false): string {
        $compat = $forceCompat || (isset($GLOBALS['forceCompat']) && $GLOBALS['forceCompat']);
        $base   = self::basePath();
        $p      = ltrim($path, '/');

        if ($compat) {
            if (empty($p) || $p === 'home') return $base . '/index.php?route=home';
            return $base . '/index.php?route=' . $p;
        }
        return $base . '/' . $p;
    }

    /**
     * Génère une URL vers un asset statique (CSS, JS, images).
     * Toujours un chemin direct — jamais via index.php.
     */
    public static function asset(string $path): string {
        $base = self::basePath();
        return $base . '/' . ltrim($path, '/');
    }

    // ── Sécurité ─────────────────────────────────────────────────
    public static function e(?string $s): string {
        return htmlspecialchars($s ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function sanitize(string $s): string {
        return trim(strip_tags($s));
    }

    public static function isAjax(): bool {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    // ── Redirections ─────────────────────────────────────────────
    public static function redirect(string $url): void {
        header('Location: ' . $url);
        exit;
    }

    public static function json(mixed $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    // ── Dates ────────────────────────────────────────────────────
    public static function dateFormat(?string $date): string {
        if (!$date) return '—';
        try { return (new DateTime($date))->format('d/m/Y'); } catch(Exception $e) { return $date; }
    }

    public static function dateTimeFormat(?string $date): string {
        if (!$date) return '—';
        try { return (new DateTime($date))->format('d/m/Y à H:i'); } catch(Exception $e) { return $date; }
    }

    public static function timeAgo(?string $date): string {
        if (!$date) return '—';
        try {
            $diff = (new DateTime())->getTimestamp() - (new DateTime($date))->getTimestamp();
            if ($diff < 60)        return 'à l\'instant';
            if ($diff < 3600)      return 'il y a ' . floor($diff / 60) . ' min';
            if ($diff < 86400)     return 'il y a ' . floor($diff / 3600) . ' h';
            if ($diff < 604800)    return 'il y a ' . floor($diff / 86400) . ' j';
            if ($diff < 2592000)   return 'il y a ' . floor($diff / 604800) . ' sem.';
            if ($diff < 31536000)  return 'il y a ' . floor($diff / 2592000) . ' mois';
            return 'il y a ' . floor($diff / 31536000) . ' an' . (floor($diff / 31536000) > 1 ? 's' : '');
        } catch(Exception $e) { return $date; }
    }

    // ── Strings ──────────────────────────────────────────────────
    public static function excerpt(string $text, int $len = 120): string {
        $text = strip_tags($text);
        if (mb_strlen($text) <= $len) return $text;
        return mb_substr($text, 0, $len) . '…';
    }

    public static function slug(string $text): string {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[àâäáãå]/u', 'a', $text);
        $text = preg_replace('/[éèêë]/u',   'e', $text);
        $text = preg_replace('/[îïíì]/u',   'i', $text);
        $text = preg_replace('/[ôöóòõ]/u',  'o', $text);
        $text = preg_replace('/[ùûüúì]/u',  'u', $text);
        $text = preg_replace('/[ç]/u',      'c', $text);
        $text = preg_replace('/[ñ]/u',      'n', $text);
        $text = preg_replace('/[^a-z0-9\-]/u', '-', $text);
        $text = preg_replace('/-+/', '-', $text);
        return trim($text, '-');
    }

    public static function uniqueSlug(string $title, string $table): string {
        $base = self::slug($title) ?: 'page';
        $slug = $base;
        $i    = 1;
        while (Database::scalar("SELECT COUNT(*) FROM `$table` WHERE slug=?", [$slug])) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    // ── Images ───────────────────────────────────────────────────
    public static function uploadImage(array $file, string $destDir, int $maxMb = 10): array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success'=>false,'error'=>'Erreur d\'upload : '.$file['error']];
        }
        $allowed = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'];
        $mime    = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed)) {
            return ['success'=>false,'error'=>'Format non autorisé : '.$mime];
        }
        if ($file['size'] > $maxMb * 1024 * 1024) {
            return ['success'=>false,'error'=>'Fichier trop lourd (max '.$maxMb.'Mo)'];
        }
        @mkdir($destDir, 0755, true);
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid('img_', true) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $destDir . '/' . $filename)) {
            return ['success'=>false,'error'=>'Impossible de déplacer le fichier'];
        }
        return ['success'=>true,'filename'=>$filename];
    }

    // ── Prix ─────────────────────────────────────────────────────
    public static function price(float $amount, string $currency = null): string {
        $sym = $currency ?? Config::get('currency_symbol','€');
        return number_format($amount, 2, ',', ' ') . ' ' . $sym;
    }

    // ── Pagination ───────────────────────────────────────────────
    public static function paginate(int $total, int $perPage = 15): array {
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $pages   = max(1, (int)ceil($total / $perPage));
        $page    = min($page, $pages);
        $offset  = ($page - 1) * $perPage;
        return compact('page','pages','perPage','offset','total');
    }

    // ── Hash carte membre ─────────────────────────────────────────
    public static function memberCardHash(int $userId): string {
        return substr(hash_hmac('sha256', (string)$userId, CC_SECRET), 0, 12);
    }
}
