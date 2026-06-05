<?php
// Si config existe, on a accès aux helpers
if (defined('CC_ROOT') && function_exists('u')) {
    $pageTitle = 'Accès refusé — ' . Config::get('club_name','ClubCMS');
    ob_start();
    ?>
    <div style="min-height:60vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:3rem 1rem">
      <div>
        <div style="font-size:4rem;margin-bottom:1rem">🔒</div>
        <h1 style="font-family:var(--font-heading);font-size:2rem;letter-spacing:.06em;margin-bottom:.5rem">Accès refusé</h1>
        <p style="color:var(--color-muted);margin-bottom:2rem">Vous n'avez pas les droits pour accéder à cette page.</p>
        <a href="<?= u('/') ?>" style="background:var(--color-primary);color:#fff;padding:.75rem 2rem;border-radius:8px;text-decoration:none;font-weight:600">← Retour à l'accueil</a>
      </div>
    </div>
    <?php
    $content = ob_get_clean();
    include CC_ROOT . '/templates/layout.php';
} else {
    http_response_code(403);
    echo '<!DOCTYPE html><html><body style="font-family:sans-serif;text-align:center;padding:5rem"><h1>403 — Accès refusé</h1><a href="<?=u(\'/\')?>\">← Accueil</a></body></html>';
}
