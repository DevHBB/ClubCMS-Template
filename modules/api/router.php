<?php
/**
 * ClubCMS — API REST interne
 * Routes : /api/{resource}/{id}/{action}
 */

header('Content-Type: application/json');

$resource = $segments[1] ?? '';
$id       = (int)($segments[2] ?? 0);
$action   = $segments[3] ?? '';
$method   = $_SERVER['REQUEST_METHOD'];

// Lecture du body JSON
$body = [];
$raw  = file_get_contents('php://input');
if ($raw) $body = json_decode($raw, true) ?? [];

// CSRF pour les mutations
if (in_array($method, ['POST','PATCH','DELETE','PUT'])) {
    $csrf = $body['csrf'] ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals(Auth::csrfToken(), $csrf)) {
        Helpers::json(['error' => 'CSRF invalide'], 403);
    }
}

// ── Router ─────────────────────────────────────────────────────
match(true) {

    // ── Forum ──────────────────────────────────────────────────
    $resource === 'forum' && $segments[2] === 'post' && $method === 'DELETE' => (function() use ($id) {
        Auth::require('member');
        $post = Database::one("SELECT * FROM cc_forum_posts WHERE id = ?", [$id]);
        if (!$post) Helpers::json(['error' => 'Introuvable'], 404);
        if ($post['user_id'] !== Auth::id() && !Auth::isAdmin()) Helpers::json(['error' => 'Interdit'], 403);
        if ($post['is_first_post'] && !Auth::isAdmin()) Helpers::json(['error' => 'Supprimez le sujet entier'], 403);
        Database::run("DELETE FROM cc_forum_posts WHERE id = ?", [$id]);
        Helpers::json(['success' => true]);
    })(),

    $resource === 'forum' && $segments[2] === 'post' && $method === 'PATCH' => (function() use ($id, $body) {
        Auth::require('member');
        $post = Database::one("SELECT * FROM cc_forum_posts WHERE id = ?", [$id]);
        if (!$post) Helpers::json(['error' => 'Introuvable'], 404);
        if ($post['user_id'] !== Auth::id() && !Auth::isAdmin()) Helpers::json(['error' => 'Interdit'], 403);
        $content = trim($body['content'] ?? '');
        if (strlen($content) < 3) Helpers::json(['error' => 'Message trop court'], 400);
        Database::run("UPDATE cc_forum_posts SET content = ?, edited_at = NOW() WHERE id = ?", [$content, $id]);
        Helpers::json(['success' => true]);
    })(),

    $resource === 'forum' && $segments[2] === 'topic' && $method === 'DELETE' => (function() use ($id) {
        Auth::require('admin');
        Database::run("DELETE FROM cc_forum_posts WHERE topic_id = ?", [$id]);
        Database::run("DELETE FROM cc_forum_topics WHERE id = ?", [$id]);
        Helpers::json(['success' => true]);
    })(),

    $resource === 'forum' && $segments[2] === 'topic' && $action === 'lock' => (function() use ($id) {
        Auth::require('admin');
        $topic = Database::one("SELECT locked FROM cc_forum_topics WHERE id = ?", [$id]);
        if (!$topic) Helpers::json(['error' => 'Introuvable'], 404);
        Database::run("UPDATE cc_forum_topics SET locked = ? WHERE id = ?", [$topic['locked'] ? 0 : 1, $id]);
        Helpers::json(['success' => true]);
    })(),

    $resource === 'forum' && $segments[2] === 'topic' && $action === 'pin' => (function() use ($id) {
        Auth::require('admin');
        $topic = Database::one("SELECT pinned FROM cc_forum_topics WHERE id = ?", [$id]);
        if (!$topic) Helpers::json(['error' => 'Introuvable'], 404);
        Database::run("UPDATE cc_forum_topics SET pinned = ? WHERE id = ?", [$topic['pinned'] ? 0 : 1, $id]);
        Helpers::json(['success' => true]);
    })(),

    // ── Galerie ────────────────────────────────────────────────
    $resource === 'gallery' && $segments[2] === 'folder' && $method === 'DELETE' => (function() use ($id) {
        Auth::require('admin');
        // Supprime les photos physiques
        $photos = Database::all("SELECT filename FROM cc_gallery_photos WHERE folder_id = ?", [$id]);
        foreach ($photos as $p) {
            @unlink(CC_ROOT . '/assets/uploads/gallery/' . $p['filename']);
        }
        Database::run("DELETE FROM cc_gallery_photos WHERE folder_id = ?", [$id]);
        // Sous-dossiers récursivement
        $subs = Database::all("SELECT id FROM cc_gallery_folders WHERE parent_id = ?", [$id]);
        foreach ($subs as $sub) {
            $subPhotos = Database::all("SELECT filename FROM cc_gallery_photos WHERE folder_id = ?", [$sub['id']]);
            foreach ($subPhotos as $sp) @unlink(CC_ROOT . '/assets/uploads/gallery/' . $sp['filename']);
            Database::run("DELETE FROM cc_gallery_photos WHERE folder_id = ?", [$sub['id']]);
            Database::run("DELETE FROM cc_gallery_folders WHERE id = ?", [$sub['id']]);
        }
        Database::run("DELETE FROM cc_gallery_folders WHERE id = ?", [$id]);
        Helpers::json(['success' => true]);
    })(),

    $resource === 'gallery' && $segments[2] === 'photo' && $method === 'DELETE' => (function() use ($id) {
        Auth::require('admin');
        $photo = Database::one("SELECT filename FROM cc_gallery_photos WHERE id = ?", [$id]);
        if (!$photo) Helpers::json(['error' => 'Introuvable'], 404);
        @unlink(CC_ROOT . '/assets/uploads/gallery/' . $photo['filename']);
        Database::run("DELETE FROM cc_gallery_photos WHERE id = ?", [$id]);
        Helpers::json(['success' => true]);
    })(),

    // ── Planning ───────────────────────────────────────────────
    $resource === 'planning' && $segments[2] === 'slot' && $method === 'DELETE' => (function() use ($id) {
        Auth::require('coach');
        Database::run("DELETE FROM cc_planning_bookings WHERE slot_id = ?", [$id]);
        Database::run("DELETE FROM cc_planning_slots WHERE id = ?", [$id]);
        Helpers::json(['success' => true]);
    })(),

    $resource === 'planning' && $segments[2] === 'slot' && $method === 'GET' => (function() use ($id) {
        $slot = Database::one(
            "SELECT s.*, u.firstname AS cn, u.lastname AS cl,
                    (SELECT COUNT(*) FROM cc_planning_bookings b WHERE b.slot_id = s.id AND b.status='confirmed') AS booked
             FROM cc_planning_slots s LEFT JOIN cc_users u ON s.coach_id = u.id WHERE s.id = ?",
            [$id]
        );
        if (!$slot) Helpers::json(['error' => 'Introuvable'], 404);
        $bookings = Auth::isCoach() ? Database::all(
            "SELECT b.*, u.firstname, u.lastname, u.email FROM cc_planning_bookings b LEFT JOIN cc_users u ON b.user_id = u.id WHERE b.slot_id = ? ORDER BY b.created_at",
            [$id]
        ) : [];
        Helpers::json(['slot' => $slot, 'bookings' => $bookings]);
    })(),

    $resource === 'planning' && $segments[2] === 'booking' && $method === 'DELETE' => (function() use ($id) {
        Auth::require('member');
        $booking = Database::one("SELECT * FROM cc_planning_bookings WHERE id = ?", [$id]);
        if (!$booking) Helpers::json(['error' => 'Introuvable'], 404);
        if ($booking['user_id'] !== Auth::id() && !Auth::isCoach()) Helpers::json(['error' => 'Interdit'], 403);
        Database::run("UPDATE cc_planning_bookings SET status = 'cancelled' WHERE id = ?", [$id]);
        Helpers::json(['success' => true]);
    })(),

    // ── Boutique ───────────────────────────────────────────────
    $resource === 'shop' && $segments[2] === 'cart-count' && $method === 'GET' => (function() {
        $count = array_sum(array_column($_SESSION['cart'] ?? [], 'qty'));
        Helpers::json(['count' => $count]);
    })(),

    $resource === 'shop' && $segments[2] === 'product' && $method === 'DELETE' => (function() use ($id) {
        Auth::require('admin');
        Database::run("UPDATE cc_shop_products SET published = 0 WHERE id = ?", [$id]);
        Helpers::json(['success' => true]);
    })(),

    // ── Membres ────────────────────────────────────────────────
    $resource === 'members' && $segments[2] === 'search' && $method === 'GET' => (function() {
        Auth::require('coach');
        $q = '%' . Helpers::sanitize($_GET['q'] ?? '') . '%';
        $members = Database::all(
            "SELECT id, firstname, lastname, email, role, license_status, avatar
             FROM cc_users WHERE (firstname LIKE ? OR lastname LIKE ? OR email LIKE ?) AND status = 'active'
             LIMIT 10",
            [$q, $q, $q]
        );
        Helpers::json(['members' => $members]);
    })(),

    // ── Newsletter ─────────────────────────────────────────────
    $resource === 'newsletter' && $segments[2] === 'subscribe' && $method === 'POST' => (function() {
        Database::run("CREATE TABLE IF NOT EXISTS `cc_newsletter_subscribers` (`id` int NOT NULL AUTO_INCREMENT,`email` varchar(191) NOT NULL,`firstname` varchar(100) DEFAULT NULL,`active` tinyint(1) DEFAULT 1,`token` varchar(64) DEFAULT NULL,`created_at` datetime DEFAULT CURRENT_TIMESTAMP,PRIMARY KEY (`id`),UNIQUE KEY `email` (`email`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $email = strtolower(trim($_POST['email'] ?? ''));
        $fname = Helpers::sanitize($_POST['firstname'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) Helpers::json(['error'=>'Email invalide'],400);
        $existing = Database::one("SELECT id, active FROM cc_newsletter_subscribers WHERE email=?", [$email]);
        if ($existing) {
            if ($existing['active']) Helpers::json(['error'=>'Déjà inscrit !'],409);
            Database::run("UPDATE cc_newsletter_subscribers SET active=1, firstname=? WHERE email=?", [$fname, $email]);
        } else {
            $token = bin2hex(random_bytes(16));
            Database::insert("INSERT INTO cc_newsletter_subscribers (email, firstname, active, token) VALUES (?,?,1,?)", [$email, $fname, $token]);
        }
        Helpers::json(['success'=>true]);
    })(),

    // ── Admin ──────────────────────────────────────────────────
    $resource === 'admin' && $segments[2] === 'modules' && $method === 'POST' => (function() use ($body) {
        Auth::require('superadmin');
        $slug  = Helpers::sanitize($body['slug'] ?? '');
        $field = in_array($body['field'] ?? '', ['enabled','require_login']) ? $body['field'] : null;
        $value = (int)($body['value'] ?? 0);
        if ($slug && $field) {
            Database::run("UPDATE cc_modules SET `$field` = ? WHERE slug = ?", [$value, $slug]);
            Helpers::json(['success' => true]);
        }
        Helpers::json(['error' => 'Paramètres invalides'], 400);
    })(),

    $resource === 'admin' && $segments[2] === 'users' && $method === 'POST' => (function() use ($body) {
        Auth::require('admin');
        $userId = (int)($body['user_id'] ?? 0);
        $field  = in_array($body['field'] ?? '', ['role','status']) ? $body['field'] : null;
        $value  = Helpers::sanitize($body['value'] ?? '');
        if ($userId && $field) {
            if ($field === 'role' && !Auth::isSuperAdmin()) Helpers::json(['error' => 'Interdit'], 403);
            Database::run("UPDATE cc_users SET `$field` = ? WHERE id = ?", [$value, $userId]);
            Helpers::json(['success' => true]);
        }
        Helpers::json(['error' => 'Paramètres invalides'], 400);
    })(),

    // ── Santé API ──────────────────────────────────────────────
    $resource === 'ping' => Helpers::json(['status' => 'ok', 'version' => CC_VERSION, 'time' => date('c')]),

    // ── 404 ────────────────────────────────────────────────────
    default => Helpers::json(['error' => 'Route API introuvable'], 404),
};
