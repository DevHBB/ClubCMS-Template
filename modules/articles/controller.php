<?php
/**
 * Page Actualités — liste de tous les articles publiés
 */
$total = (int)Database::scalar(
    "SELECT COUNT(*) FROM cc_articles
     WHERE type='article' AND published=1" .
    (Auth::check() ? '' : " AND require_login=0")
);
$pager    = Helpers::paginate($total, 9);
$articles = Database::all(
    "SELECT a.*, u.firstname, u.lastname
     FROM cc_articles a
     JOIN cc_users u ON a.user_id=u.id
     WHERE a.type='article' AND a.published=1" .
    (Auth::check() ? '' : " AND a.require_login=0") .
    " ORDER BY a.created_at DESC LIMIT ? OFFSET ?",
    [$pager['perPage'], $pager['offset']]
);

$pageTitle = 'Actualités — ' . Config::get('club_name');
ob_start();?>

<!-- Bannière -->
<div style="background:linear-gradient(135deg,var(--color-primary),color-mix(in srgb,var(--color-primary) 65%,#000));padding:2.5rem 0 2rem;color:#fff">
  <div class="container">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem">
      <div>
        <h1 style="font-family:var(--font-heading);font-size:clamp(2rem,4vw,3rem);letter-spacing:.08em;margin-bottom:.25rem">📰 Actualités</h1>
        <p style="opacity:.8;font-size:.95rem">Toutes les nouvelles de <?= Helpers::e(Config::get('club_name')) ?></p>
      </div>
      <?php if ($total > 0): ?>
      <div style="background:rgba(255,255,255,.15);border-radius:8px;padding:.5rem 1rem;font-size:.875rem;font-weight:600">
        <?= $total ?> article<?= $total > 1 ? 's' : '' ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="container" style="padding:2.5rem 1.5rem 4rem">

  <?php if (empty($articles)): ?>
  <!-- État vide -->
  <div style="text-align:center;padding:5rem 2rem">
    <div style="font-size:3.5rem;margin-bottom:1rem">📰</div>
    <h2 style="font-family:var(--font-heading);font-size:1.8rem;letter-spacing:.06em;margin-bottom:.5rem">Aucune actualité pour le moment</h2>
    <p style="color:var(--color-muted);max-width:400px;margin:0 auto 2rem">Le club n'a pas encore publié d'article. Revenez prochainement !</p>
    <?php if (Auth::isAdmin()): ?>
    <a href="<?= u('/admin/articles?edit=0') ?>" style="background:var(--color-primary);color:#fff;padding:.75rem 2rem;border-radius:8px;text-decoration:none;font-weight:600;display:inline-block">
      + Créer le premier article
    </a>
    <?php endif; ?>
  </div>

  <?php else: ?>
  <!-- Grille articles -->
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.5rem">
    <?php foreach ($articles as $a): ?>
    <article style="background:#fff;border:1px solid var(--color-border);border-radius:12px;overflow:hidden;display:flex;flex-direction:column;transition:box-shadow .2s;box-shadow:0 1px 4px rgba(0,0,0,.06)" onmouseover="this.style.boxShadow='0 8px 24px rgba(0,0,0,.1)'" onmouseout="this.style.boxShadow='0 1px 4px rgba(0,0,0,.06)'">
      <?php if ($a['cover']): ?>
      <a href="<?= u('/' . $a['slug']) ?>">
        <img src="<?= asset($a['cover']) ?>" alt="<?= Helpers::e($a['title']) ?>" style="width:100%;height:200px;object-fit:cover;display:block">
      </a>
      <?php else: ?>
      <div style="height:4px;background:linear-gradient(90deg,var(--color-primary),var(--color-secondary))"></div>
      <?php endif; ?>
      <div style="padding:1.25rem;flex:1;display:flex;flex-direction:column">
        <div style="font-size:.75rem;color:var(--color-muted);margin-bottom:.4rem;display:flex;align-items:center;gap:.5rem">
          <span><?= Helpers::dateFormat($a['created_at']) ?></span>
          <span>·</span>
          <span><?= Helpers::e($a['firstname'] . ' ' . $a['lastname']) ?></span>
          <?php if ($a['require_login']): ?><span style="background:#eff6ff;color:#1d4ed8;font-size:.65rem;font-weight:700;padding:.1rem .35rem;border-radius:3px">🔒 Membres</span><?php endif; ?>
        </div>
        <h2 style="font-family:var(--font-heading);font-size:1.2rem;letter-spacing:.04em;line-height:1.25;margin-bottom:.5rem">
          <a href="<?= u('/' . $a['slug']) ?>" style="color:var(--color-text);text-decoration:none" onmouseover="this.style.color='var(--color-primary)'" onmouseout="this.style.color='var(--color-text)'">
            <?= Helpers::e($a['title']) ?>
          </a>
        </h2>
        <?php if ($a['excerpt']): ?>
        <p style="color:var(--color-muted);font-size:.875rem;line-height:1.6;flex:1"><?= Helpers::e($a['excerpt']) ?></p>
        <?php endif; ?>
        <a href="<?= u('/' . $a['slug']) ?>" style="display:inline-flex;align-items:center;gap:.3rem;margin-top:1rem;color:var(--color-primary);font-weight:600;font-size:.875rem;text-decoration:none">
          Lire la suite <span style="font-size:1rem">→</span>
        </a>
      </div>
    </article>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($pager['pages'] > 1): ?>
  <div style="display:flex;justify-content:center;gap:.35rem;margin-top:2.5rem">
    <?php if ($pager['page'] > 1): ?>
    <a href="<?= u('/actualites?page='.($pager['page']-1)) ?>" style="padding:.5rem .875rem;border:1.5px solid var(--color-border);border-radius:7px;color:var(--color-muted);text-decoration:none;font-size:.875rem">← Précédent</a>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $pager['pages']; $i++): ?>
    <a href="<?= u('/actualites?page='.$i) ?>" style="padding:.5rem .875rem;border:1.5px solid <?=$i===$pager['page']?'var(--color-primary)':'var(--color-border)'?>;border-radius:7px;color:<?=$i===$pager['page']?'var(--color-primary)':'var(--color-muted)'?>;text-decoration:none;font-size:.875rem;font-weight:<?=$i===$pager['page']?'700':'400'?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($pager['page'] < $pager['pages']): ?>
    <a href="<?= u('/actualites?page='.($pager['page']+1)) ?>" style="padding:.5rem .875rem;border:1.5px solid var(--color-border);border-radius:7px;color:var(--color-muted);text-decoration:none;font-size:.875rem">Suivant →</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>

</div>
<?php
$content = ob_get_clean();
include CC_ROOT . '/templates/layout.php';
