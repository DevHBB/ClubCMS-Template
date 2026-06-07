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

    <!-- Partage réseaux sociaux -->
    <?php if(isset($article)): ?>
    <?php
    $shareUrl   = CC_URL.u('/actualites/'.$article['slug']);
    $shareTitle = urlencode($article['title']);
    $shareUrl_e = urlencode($shareUrl);
    ?>
    <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;padding:1.25rem 0;border-top:1px solid #f1f5f9;margin-top:1.5rem">
      <span style="font-size:.82rem;font-weight:600;color:#64748b">Partager :</span>
      <a href="https://www.facebook.com/sharer/sharer.php?u=<?=$shareUrl_e?>" target="_blank" rel="noopener"
        style="display:inline-flex;align-items:center;gap:.35rem;padding:.4rem .875rem;border-radius:8px;background:#1877f2;color:#fff;text-decoration:none;font-size:.82rem;font-weight:600">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.07C24 5.41 18.63 0 12 0S0 5.41 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.04V9.41c0-3.02 1.8-4.7 4.54-4.7 1.31 0 2.68.24 2.68.24v2.97h-1.5c-1.5 0-1.96.93-1.96 1.89v2.26h3.32l-.53 3.5h-2.8V24C19.62 23.1 24 18.1 24 12.07z"/></svg>
        Facebook
      </a>
      <a href="https://twitter.com/intent/tweet?url=<?=$shareUrl_e?>&text=<?=$shareTitle?>" target="_blank" rel="noopener"
        style="display:inline-flex;align-items:center;gap:.35rem;padding:.4rem .875rem;border-radius:8px;background:#000;color:#fff;text-decoration:none;font-size:.82rem;font-weight:600">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.259 5.631L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
        X / Twitter
      </a>
      <a href="whatsapp://send?text=<?=$shareTitle?>%20<?=$shareUrl_e?>" target="_blank" rel="noopener"
        style="display:inline-flex;align-items:center;gap:.35rem;padding:.4rem .875rem;border-radius:8px;background:#25d366;color:#fff;text-decoration:none;font-size:.82rem;font-weight:600">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z M11.99 2C6.477 2 2 6.477 2 11.99c0 1.76.459 3.417 1.255 4.857L2 22l5.244-1.231A9.955 9.955 0 0011.99 22C17.522 22 22 17.523 22 11.99S17.522 2 11.99 2z"/></svg>
        WhatsApp
      </a>
      <button onclick="navigator.clipboard.writeText('<?=Helpers::e($shareUrl)?>').then(()=>this.textContent='✓ Copié !')"
        style="display:inline-flex;align-items:center;gap:.35rem;padding:.4rem .875rem;border-radius:8px;background:#f1f5f9;color:#475569;border:none;cursor:pointer;font-size:.82rem;font-weight:600;font-family:inherit">
        🔗 Copier le lien
      </button>
    </div>
    <?php endif; ?>
<?php
$content = ob_get_clean();
include CC_ROOT . '/templates/layout.php';
