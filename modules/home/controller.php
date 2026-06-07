<?php
/**
 * ClubCMS — Page d'accueil
 */
$activeSlugs = array_column(Database::all("SELECT slug FROM cc_modules WHERE enabled=1"), 'slug');
$heroTitle   = Config::get('hero_title', Config::get('club_name',''));
$heroSub     = Config::get('hero_subtitle','');
$heroImg     = Config::get('hero_image','');
$heroBtn1L   = Config::get('hero_btn1_label','Mon espace');
$heroBtn1U   = Config::get('hero_btn1_url','/membre');
$heroBtn2L   = Config::get('hero_btn2_label','Planning');
$heroBtn2U   = Config::get('hero_btn2_url','/planning');
$stats = [
    'members'  => (int)Database::scalar("SELECT COUNT(*) FROM cc_users WHERE status='active'"),
    'topics'   => (int)Database::scalar("SELECT COUNT(*) FROM cc_forum_topics"),
    'slots'    => (int)Database::scalar("SELECT COUNT(*) FROM cc_planning_slots WHERE date_start>=NOW()"),
    'photos'   => (int)Database::scalar("SELECT COUNT(*) FROM cc_gallery_photos"),
];
$homepageBlocks = json_decode(Config::get('homepage_blocks','[]'), true) ?? [];
// DEBUG TEMPORAIRE - A SUPPRIMER
if (Auth::isAdmin() && isset($_GET['debug_blocks'])) {
    header('Content-Type: text/plain');
    $raw = Config::get('homepage_blocks','[]');
    echo "RAW: " . $raw . "

";
    echo "DECODED: ";
    var_dump($homepageBlocks);
    exit;
}

$pageTitle = Config::get('club_name','ClubCMS');
ob_start();
?>

<!-- ══ HERO ══ -->
<div class="hero-wrap" style="position:relative;overflow:hidden;min-height:520px;display:flex;align-items:center;background:linear-gradient(135deg,var(--color-primary),color-mix(in srgb,var(--color-primary) 60%,#000))">
  <?php if($heroImg): ?>
  <img src="<?=asset($heroImg)?>" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:.35">
  <?php endif; ?>
  <div class="container" style="position:relative;z-index:1;padding:5rem 1.5rem;text-align:center;color:#fff;width:100%">
    <h1 style="font-family:var(--font-heading);font-size:clamp(2.5rem,6vw,5rem);letter-spacing:.08em;margin-bottom:1rem;line-height:1"><?=Helpers::e($heroTitle)?></h1>
    <?php if($heroSub): ?><p style="font-size:clamp(1rem,2vw,1.3rem);opacity:.85;max-width:600px;margin:0 auto 2.5rem;line-height:1.6"><?=Helpers::e($heroSub)?></p><?php endif; ?>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
      <?php if($heroBtn1L): ?><a href="<?=u($heroBtn1U)?>" style="background:#fff;color:var(--color-primary);padding:.875rem 2.25rem;border-radius:10px;font-weight:700;font-size:1rem;text-decoration:none;transition:opacity .2s" onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'"><?=Helpers::e($heroBtn1L)?></a><?php endif; ?>
      <?php if($heroBtn2L): ?><a href="<?=u($heroBtn2U)?>" style="background:transparent;color:#fff;border:2px solid rgba(255,255,255,.7);padding:.875rem 2.25rem;border-radius:10px;font-weight:700;font-size:1rem;text-decoration:none" onmouseover="this.style.borderColor='#fff'" onmouseout="this.style.borderColor='rgba(255,255,255,.7)'"><?=Helpers::e($heroBtn2L)?></a><?php endif; ?>
    </div>
    <p style="margin-top:2rem;opacity:.65;font-size:.875rem">👥 <?=$stats['members']?> membre<?=$stats['members']>1?'s':''?> nous ont rejoints</p>
  </div>
</div>

<!-- ══ STATS ══ -->
<?php
$statsEnabled = Config::get('stats_bar_enabled','1');
$statsBoxes   = json_decode(Config::get('stats_bar_boxes','[]'), true) ?: [
    ['type'=>'members','label'=>'MEMBRES','value'=>''],
    ['type'=>'topics','label'=>'DISCUSSIONS','value'=>''],
    ['type'=>'slots','label'=>'CRÉNEAUX À VENIR','value'=>''],
    ['type'=>'photos','label'=>'PHOTOS','value'=>''],
];
// Résoudre les valeurs auto
$autoVals = [
    'members'  => $stats['members'],
    'topics'   => $stats['topics'],
    'slots'    => $stats['slots'],
    'photos'   => $stats['photos'],
    'articles' => (int)Database::scalar("SELECT COUNT(*) FROM cc_articles WHERE published=1 AND type='article'"),
    'videos'   => (int)Database::scalar("SELECT COUNT(*) FROM cc_videos"),
];
if($statsEnabled):
?>
<div style="background:var(--color-primary);color:#fff;padding:1.25rem 0">
  <div class="container" style="display:grid;grid-template-columns:repeat(<?=count($statsBoxes)?>,1fr);text-align:center;gap:.5rem">
    <?php foreach($statsBoxes as $box):
      $val   = ($box['type']==='custom') ? $box['value'] : ($autoVals[$box['type']] ?? 0);
      $label = $box['label'] ?: strtoupper($box['type']);
      if(!$val && !$box['label']) continue;
    ?>
    <div>
      <div style="font-family:var(--font-heading);font-size:2.2rem;letter-spacing:.05em"><?=$val?></div>
      <div style="font-size:.7rem;letter-spacing:.12em;opacity:.75"><?=Helpers::e($label)?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- ══ BLOCS CONFIGURABLES ══ -->
<?php if (!empty($homepageBlocks)):
  // Charger BlockRenderer si pas encore chargé
  if (!class_exists('BlockRenderer')) {
    require_once CC_ROOT . '/core/BlockRenderer.php';
  }
?>
<div class="container" style="padding:2rem 1.5rem 4rem;max-width:980px;margin:0 auto">
  <?= BlockRenderer::render($homepageBlocks) ?>
</div>
<?php endif; ?>

<!-- ══ FALLBACK si aucun bloc ══ -->
<?php if (empty($homepageBlocks)): ?>
<div class="container" style="padding:4rem 1.5rem">
  <div style="display:grid;grid-template-columns:1fr 300px;gap:3rem;align-items:start">
    <div>
      <?php
      $articles = Database::all("SELECT a.*, u.firstname, u.lastname FROM cc_articles a JOIN cc_users u ON a.user_id=u.id WHERE a.published=1 AND a.type='article'" . (Auth::check() ? '' : " AND a.require_login=0") . " ORDER BY a.created_at DESC LIMIT 3");
      ?>
      <?php if ($articles): ?>
      <h2 style="font-family:var(--font-heading);font-size:2rem;letter-spacing:.06em;margin-bottom:1.5rem">📰 ACTUALITÉS</h2>
      <div style="display:flex;flex-direction:column;gap:1.5rem">
        <?php foreach ($articles as $a): ?>
        <article style="background:#fff;border:1px solid var(--color-border);border-radius:12px;overflow:hidden;display:flex;flex-direction:column">
          <?php if ($a['cover']): ?><img src="<?=asset(Helpers::e($a['cover']))?>" style="height:180px;width:100%;object-fit:cover"><?php endif; ?>
          <div style="padding:1.25rem">
            <div style="font-size:.75rem;color:var(--color-muted);margin-bottom:.35rem"><?=Helpers::dateFormat($a['created_at'])?></div>
            <h3 style="font-family:var(--font-heading);font-size:1.3rem;letter-spacing:.04em;margin-bottom:.5rem"><a href="<?=u('/'.$a['slug'])?>" style="color:var(--color-text);text-decoration:none"><?=Helpers::e($a['title'])?></a></h3>
            <?php if($a['excerpt']): ?><p style="color:var(--color-muted);font-size:.875rem;line-height:1.6"><?=Helpers::e($a['excerpt'])?></p><?php endif; ?>
            <a href="<?=u('/'.$a['slug'])?>" style="color:var(--color-primary);font-weight:600;font-size:.875rem;display:inline-block;margin-top:.75rem">Lire la suite →</a>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
      <div style="margin-top:1.5rem"><a href="<?=u('/actualites')?>" style="color:var(--color-primary);font-weight:600">Toutes les actualités →</a></div>
      <?php else: ?>
      <?php if(Auth::isAdmin()): ?>
      <div style="background:#f8fafc;border:2px dashed #e2e8f0;border-radius:12px;padding:3rem;text-align:center">
        <div style="font-size:2.5rem;margin-bottom:.75rem">🏠</div>
        <h2 style="font-family:var(--font-heading);font-size:1.5rem;margin-bottom:.5rem">Personnalisez votre accueil</h2>
        <p style="color:#64748b;margin-bottom:1.25rem">Ajoutez des blocs dans Pages & Accueil pour personnaliser cette page.</p>
        <a href="<?=u('/admin/pages?tab=homepage')?>" class="btn btn-primary">✏️ Modifier l'accueil</a>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div style="position:sticky;top:80px">
      <?php if (Auth::check()): ?>
      <div style="background:#fff;border:1px solid var(--color-border);border-radius:12px;padding:1.25rem;margin-bottom:1.25rem">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--color-muted);margin-bottom:.875rem">Accès rapide</div>
        <a href="<?=u('/membre')?>" style="display:flex;align-items:center;gap:.6rem;padding:.55rem 0;border-bottom:1px solid #f1f5f9;text-decoration:none;color:var(--color-text);font-size:.875rem">👤 Mon profil</a>
        <?php if(in_array('planning',$activeSlugs)): ?><a href="<?=u('/planning')?>" style="display:flex;align-items:center;gap:.6rem;padding:.55rem 0;border-bottom:1px solid #f1f5f9;text-decoration:none;color:var(--color-text);font-size:.875rem">📅 Planning</a><?php endif; ?>
        <?php if(in_array('forum',$activeSlugs)): ?><a href="<?=u('/forum')?>" style="display:flex;align-items:center;gap:.6rem;padding:.55rem 0;border-bottom:1px solid #f1f5f9;text-decoration:none;color:var(--color-text);font-size:.875rem">💬 Forum</a><?php endif; ?>
        <?php if(in_array('gallery',$activeSlugs)): ?><a href="<?=u('/galerie')?>" style="display:flex;align-items:center;gap:.6rem;padding:.55rem 0;text-decoration:none;color:var(--color-text);font-size:.875rem">📸 Galerie</a><?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if(in_array('planning',$activeSlugs)): ?>
      <?php $nextSlots = Database::all("SELECT * FROM cc_planning_slots WHERE published=1 AND date_start>=NOW() ORDER BY date_start ASC LIMIT 5"); ?>
      <?php if($nextSlots): ?>
      <div style="background:#fff;border:1px solid var(--color-border);border-radius:12px;padding:1.25rem">
        <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--color-muted);margin-bottom:.875rem">📅 Prochains créneaux</div>
        <?php foreach($nextSlots as $s): ?>
        <div style="display:flex;align-items:center;gap:.75rem;padding:.5rem 0;border-bottom:1px solid #f1f5f9">
          <div style="background:var(--color-primary);color:#fff;border-radius:6px;padding:.25rem .5rem;font-size:.72rem;font-weight:700;white-space:nowrap"><?=Helpers::dateFormat($s['date_start'])?></div>
          <div style="font-size:.82rem;font-weight:500;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?=Helpers::e($s['title'])?></div>
        </div>
        <?php endforeach; ?>
        <a href="<?=u('/planning')?>" style="display:block;text-align:center;margin-top:.75rem;font-size:.8rem;color:var(--color-primary);font-weight:600;text-decoration:none">Voir le planning →</a>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>



<?php
$content = ob_get_clean();
include CC_ROOT . '/templates/layout.php';
