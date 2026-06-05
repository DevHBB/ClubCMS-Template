<?php
/**
 * ClubCMS — Module Galerie
 * Routes : /galerie, /galerie/{slug}
 */

$action = $segments[1] ?? 'index';

$pageTitle = 'Galerie — ' . Config::get('club_name');
ob_start();
?>

<?php if ($action === 'index'): ?>
<!-- ═══════════════════════════ RACINE : dossiers de niveau 0 -->
<div class="gallery-hero">
  <div class="container">
    <h1 class="forum-title">📸 Galerie</h1>
    <p class="forum-subtitle">Photos et albums du club</p>
  </div>
</div>

<div class="container gallery-wrap">
  <?php
  $folders = Database::all(
      "SELECT f.*,
              (SELECT COUNT(*) FROM cc_gallery_photos p WHERE p.folder_id = f.id) AS photo_count,
              (SELECT COUNT(*) FROM cc_gallery_folders sf WHERE sf.parent_id = f.id) AS sub_count
       FROM cc_gallery_folders f
       WHERE f.parent_id IS NULL
       " . (Auth::check() ? '' : "AND f.require_login = 0") . "
       ORDER BY f.order ASC, f.id DESC"
  );
  ?>
  <div class="gallery-grid">
    <?php foreach ($folders as $f):
      $coverSrc = $f['cover']
        ? '/assets/uploads/gallery/' . $f['cover']
        : null;
      if (!$coverSrc) {
          $lastPhoto = Database::one(
              "SELECT filename FROM cc_gallery_photos WHERE folder_id = ? ORDER BY id DESC LIMIT 1",
              [$f['id']]
          );
          if ($lastPhoto) $coverSrc = '/assets/uploads/gallery/' . $lastPhoto['filename'];
      }
    ?>
    <a href="/galerie/<?= Helpers::e($f['slug']) ?>" class="gallery-folder-card">
      <div class="gf-cover">
        <?php if ($coverSrc): ?>
          <img src="<?= Helpers::e($coverSrc) ?>" alt="<?= Helpers::e($f['name']) ?>" loading="lazy">
        <?php else: ?>
          <div class="gf-no-cover">📂</div>
        <?php endif; ?>
        <?php if ($f['require_login']): ?>
          <span class="gf-lock">🔒</span>
        <?php endif; ?>
      </div>
      <div class="gf-info">
        <div class="gf-name"><?= Helpers::e($f['name']) ?></div>
        <div class="gf-meta">
          <?= $f['photo_count'] ?> photo<?= $f['photo_count'] > 1 ? 's' : '' ?>
          <?php if ($f['sub_count']): ?> · <?= $f['sub_count'] ?> album<?= $f['sub_count'] > 1 ? 's' : '' ?><?php endif; ?>
        </div>
      </div>
    </a>
    <?php endforeach; ?>

    <?php if (empty($folders)): ?>
      <div class="empty-state" style="grid-column:1/-1">
        <div class="empty-icon">📷</div>
        <p>Aucun album photo disponible.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php else: ?>
<!-- ═══════════════════════════ DOSSIER / SOUS-DOSSIERS + PHOTOS -->
<?php
$slug   = $action; // /galerie/{slug}
$folder = Database::one("SELECT * FROM cc_gallery_folders WHERE slug = ?", [$slug]);
if (!$folder) { http_response_code(404); include CC_ROOT . '/templates/404.php'; exit; }

// Accès restreint ?
if ($folder['require_login'] && !Auth::check()) {
    Helpers::redirect('/login?return=/galerie/' . urlencode($slug));
}

$pageTitle = Helpers::e($folder['name']) . ' — Galerie — ' . Config::get('club_name');

// Dossier parent
$parent = $folder['parent_id']
    ? Database::one("SELECT * FROM cc_gallery_folders WHERE id = ?", [$folder['parent_id']])
    : null;

// Sous-dossiers
$subFolders = Database::all(
    "SELECT f.*,
            (SELECT COUNT(*) FROM cc_gallery_photos p WHERE p.folder_id = f.id) AS photo_count
     FROM cc_gallery_folders f WHERE f.parent_id = ?
     " . (Auth::check() ? '' : "AND f.require_login = 0") . "
     ORDER BY f.order ASC",
    [$folder['id']]
);

// Photos du dossier
$total  = (int)Database::scalar("SELECT COUNT(*) FROM cc_gallery_photos WHERE folder_id = ?", [$folder['id']]);
$pager  = Helpers::paginate($total, 36);
$photos = Database::all(
    "SELECT * FROM cc_gallery_photos WHERE folder_id = ? ORDER BY `order` ASC, id DESC LIMIT ? OFFSET ?",
    [$folder['id'], $pager['perPage'], $pager['offset']]
);
?>

<div class="gallery-hero">
  <div class="container">
    <nav class="breadcrumb">
      <a href="/galerie">Galerie</a>
      <?php if ($parent): ?>
        <span>›</span> <a href="/galerie/<?= Helpers::e($parent['slug']) ?>"><?= Helpers::e($parent['name']) ?></a>
      <?php endif; ?>
      <span>›</span> <span><?= Helpers::e($folder['name']) ?></span>
    </nav>
    <h1 class="forum-title">📂 <?= Helpers::e($folder['name']) ?></h1>
    <?php if ($folder['description']): ?>
      <p class="forum-subtitle"><?= Helpers::e($folder['description']) ?></p>
    <?php endif; ?>
  </div>
</div>

<div class="container gallery-wrap">

  <!-- Sous-dossiers -->
  <?php if ($subFolders): ?>
  <section style="margin-bottom:3rem">
    <h2 class="gallery-section-title">📁 Albums</h2>
    <div class="gallery-grid sub">
      <?php foreach ($subFolders as $sf):
        $coverSrc = null;
        $lp = Database::one("SELECT filename FROM cc_gallery_photos WHERE folder_id = ? ORDER BY id DESC LIMIT 1", [$sf['id']]);
        if ($lp) $coverSrc = '/assets/uploads/gallery/' . $lp['filename'];
      ?>
      <a href="/galerie/<?= Helpers::e($sf['slug']) ?>" class="gallery-folder-card sub">
        <div class="gf-cover">
          <?php if ($coverSrc): ?>
            <img src="<?= Helpers::e($coverSrc) ?>" alt="<?= Helpers::e($sf['name']) ?>" loading="lazy">
          <?php else: ?>
            <div class="gf-no-cover">📂</div>
          <?php endif; ?>
        </div>
        <div class="gf-info">
          <div class="gf-name"><?= Helpers::e($sf['name']) ?></div>
          <div class="gf-meta"><?= $sf['photo_count'] ?> photo<?= $sf['photo_count'] > 1 ? 's' : '' ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- Photos -->
  <?php if ($photos): ?>
  <section>
    <?php if ($subFolders): ?><h2 class="gallery-section-title">🖼️ Photos</h2><?php endif; ?>
    <div class="photos-masonry">
      <?php foreach ($photos as $i => $ph):
        $src = '/assets/uploads/gallery/' . $ph['filename'];
      ?>
      <div class="photo-item reveal" style="animation-delay:<?= ($i % 12) * 0.04 ?>s">
        <img src="<?= Helpers::e($src) ?>"
             alt="<?= Helpers::e($ph['title'] ?? '') ?>"
             loading="lazy"
             data-lightbox="<?= Helpers::e($src) ?>"
             data-caption="<?= Helpers::e($ph['caption'] ?? $ph['title'] ?? '') ?>"
             class="photo-thumb">
        <?php if ($ph['title']): ?>
          <div class="photo-caption"><?= Helpers::e($ph['title']) ?></div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($pager['pages'] > 1): ?>
    <div class="pagination">
      <?php for ($i = 1; $i <= $pager['pages']; $i++): ?>
        <a href="<?=u('/galerie?page='.$i)?>" class="page-btn <?= $i === $pager['page'] ? 'active' : '' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>
  </section>
  <?php elseif (empty($subFolders)): ?>
    <div class="empty-state">
      <div class="empty-icon">📷</div>
      <p>Aucune photo dans cet album.</p>
      <?php if (Auth::isAdmin()): ?>
        <a href="/admin/galerie/upload?folder=<?= $folder['id'] ?>" class="btn btn-primary">Ajouter des photos</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div>
<?php endif; ?>

<style>
.gallery-hero{background:linear-gradient(135deg,var(--color-primary),color-mix(in srgb,var(--color-primary) 70%,#000));padding:3rem 0;color:#fff;margin-bottom:2rem}
.gallery-wrap{padding-bottom:4rem}
.gallery-section-title{font-family:var(--font-heading);font-size:1.5rem;letter-spacing:.06em;margin-bottom:1.25rem}
.breadcrumb{display:flex;align-items:center;gap:.4rem;font-size:.85rem;color:rgba(255,255,255,.7);margin-bottom:.75rem}
.breadcrumb a{color:rgba(255,255,255,.8);text-decoration:none}
.breadcrumb a:hover{color:#fff}
/* Grille dossiers */
.gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1.25rem}
.gallery-grid.sub{grid-template-columns:repeat(auto-fill,minmax(180px,1fr))}
.gallery-folder-card{display:block;border-radius:var(--radius-md);overflow:hidden;border:1px solid var(--color-border);transition:all .25s;text-decoration:none;background:#fff}
.gallery-folder-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-md);border-color:var(--color-primary)}
.gf-cover{position:relative;aspect-ratio:4/3;overflow:hidden;background:var(--color-surface)}
.gf-cover img{width:100%;height:100%;object-fit:cover;transition:transform .35s}
.gallery-folder-card:hover .gf-cover img{transform:scale(1.06)}
.gf-no-cover{display:flex;align-items:center;justify-content:center;height:100%;font-size:3rem;color:var(--color-muted)}
.gf-lock{position:absolute;top:.5rem;right:.5rem;font-size:.9rem;background:rgba(0,0,0,.5);border-radius:4px;padding:.1rem .3rem}
.gf-info{padding:.875rem 1rem}
.gf-name{font-weight:700;font-size:.95rem;color:var(--color-text);margin-bottom:.15rem}
.gf-meta{font-size:.78rem;color:var(--color-muted)}
/* Masonry photos */
.photos-masonry{columns:4 220px;column-gap:8px;margin-bottom:2rem}
.photo-item{break-inside:avoid;margin-bottom:8px;position:relative;border-radius:var(--radius-sm);overflow:hidden;cursor:pointer}
.photo-thumb{width:100%;display:block;transition:transform .3s;border-radius:var(--radius-sm)}
.photo-item:hover .photo-thumb{transform:scale(1.02)}
.photo-caption{position:absolute;bottom:0;left:0;right:0;background:linear-gradient(transparent,rgba(0,0,0,.65));color:#fff;font-size:.75rem;padding:.75rem .5rem .4rem;transform:translateY(100%);transition:transform .25s}
.photo-item:hover .photo-caption{transform:none}
@media(max-width:600px){.gallery-grid{grid-template-columns:repeat(auto-fill,minmax(150px,1fr))}.photos-masonry{columns:2 140px}}
</style>

<?php
$content = ob_get_clean();
include CC_ROOT . '/templates/layout.php';
