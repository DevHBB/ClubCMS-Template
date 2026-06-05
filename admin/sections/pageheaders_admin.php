<?php
Auth::require('admin');

$pages = [
    'planning'  => ['label' => 'Planning',    'icon' => '📅'],
    'forum'     => ['label' => 'Forum',       'icon' => '💬'],
    'galerie'   => ['label' => 'Galerie',     'icon' => '🖼️'],
    'boutique'  => ['label' => 'Boutique',    'icon' => '🛒'],
    'videos'    => ['label' => 'Vidéos',      'icon' => '🎬'],
    'articles'  => ['label' => 'Actualités',  'icon' => '📰'],
];

// ── Sauvegarder ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::verifyCsrf() && isset($_POST['save_headers'])) {
    foreach ($pages as $slug => $p) {
        Config::set("ph_{$slug}_title",    Helpers::sanitize($_POST["ph_{$slug}_title"]    ?? ''), 'pageheaders');
        Config::set("ph_{$slug}_subtitle", Helpers::sanitize($_POST["ph_{$slug}_subtitle"] ?? ''), 'pageheaders');
    }
    adminFlash('success', 'En-têtes sauvegardés.');
    Helpers::redirect(u('/admin/pageheaders'));
}

$pageTitle = '🏷️ En-têtes des pages';
ob_start(); ?>

<div class="page-head">
  <h1>🏷️ En-têtes des pages</h1>
</div>

<div style="max-width:720px">
  <div style="background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:10px;padding:.875rem 1.25rem;margin-bottom:1.5rem;font-size:.875rem;color:#0369a1">
    ℹ️ Ajoutez un <strong>titre</strong> et un <strong>sous-titre</strong> qui s'afficheront en haut de chaque page du site. Laissez vide pour ne rien afficher.
  </div>

  <form method="post">
    <?=Auth::csrfField()?>
    <div style="display:flex;flex-direction:column;gap:1rem">
      <?php foreach($pages as $slug => $p):
        $title    = Config::get("ph_{$slug}_title",    '');
        $subtitle = Config::get("ph_{$slug}_subtitle", '');
      ?>
      <div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;overflow:hidden">
        <div style="background:#f8fafc;padding:.75rem 1rem;font-weight:700;font-size:.9rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.5rem">
          <span><?=$p['icon']?></span> <?=Helpers::e($p['label'])?>
        </div>
        <div style="padding:.875rem 1rem;display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
          <div>
            <label style="font-size:.78rem;font-weight:600;color:#64748b;display:block;margin-bottom:.3rem">Titre</label>
            <input type="text" name="ph_<?=$slug?>_title" class="input-std"
              value="<?=Helpers::e($title)?>"
              placeholder="Ex: Notre Planning">
          </div>
          <div>
            <label style="font-size:.78rem;font-weight:600;color:#64748b;display:block;margin-bottom:.3rem">Sous-titre / petit mot</label>
            <input type="text" name="ph_<?=$slug?>_subtitle" class="input-std"
              value="<?=Helpers::e($subtitle)?>"
              placeholder="Ex: Retrouvez tous nos créneaux disponibles">
          </div>
        </div>
        <?php if($title || $subtitle): ?>
        <div style="padding:.4rem 1rem .75rem;font-size:.75rem;color:#64748b">
          Aperçu : <strong><?=Helpers::e($title)?></strong><?=$subtitle?' — '.Helpers::e($subtitle):''?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="margin-top:1.25rem">
      <button type="submit" name="save_headers" class="btn btn-primary">💾 Sauvegarder</button>
    </div>
  </form>
</div>

<?php
$content = ob_get_clean();
include CC_ROOT . '/admin/layout.php';
