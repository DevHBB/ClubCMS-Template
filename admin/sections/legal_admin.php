<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Auth::verifyCsrf()) { adminFlash('error','CSRF'); Helpers::redirect(u('/admin/legal')); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach(['cgv','cgu','reglement','confidentialite','mentions_legales','cookies'] as $key) {
        if (isset($_POST[$key])) Config::set('legal_'.$key, $_POST[$key], 'legal');
    }
    adminFlash('success','Pages légales sauvegardées.'); Helpers::redirect(u('/admin/legal'));
}
$tab = $_GET['tab'] ?? 'cgv';
$tabs = ['cgv'=>'CGV','cgu'=>'CGU','reglement'=>'Règlement','confidentialite'=>'Confidentialité','mentions_legales'=>'Mentions légales','cookies'=>'Cookies'];
$pageTitle = 'Pages légales';
ob_start();
?>
<div class="page-head"><h1>⚖️ Pages légales</h1></div>
<div style="display:flex;gap:.35rem;margin-bottom:1.25rem;flex-wrap:wrap">
  <?php foreach($tabs as $k=>$l): ?><a href="?tab=<?=$k?>" class="btn <?=$tab===$k?'btn-primary':'btn-ghost'?>"><?=$l?></a><?php endforeach; ?>
</div>
<div class="ac">
  <div class="ac-header"><h2><?=$tabs[$tab]??$tab?></h2><small style="color:#64748b">Accessible sur : /<?=str_replace('_','-',$tab)?></small></div>
  <div class="ac-body">
    <form method="post">
      <?=Auth::csrfField()?>
      <div class="fg">
        <label>Contenu (HTML autorisé)</label>
        <textarea name="<?=$tab?>" rows="25" style="font-family:monospace;font-size:.85rem"><?=Helpers::e(Config::get('legal_'.$tab,''))?></textarea>
        <small style="color:#94a3b8">Vous pouvez utiliser des balises HTML : &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt;, &lt;strong&gt;, etc.</small>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:.75rem">
        <?php if(Config::get('legal_'.$tab)): ?><a href="/<?=str_replace('_','-',$tab)?>" target="_blank" class="btn btn-ghost">👁 Voir</a><?php endif; ?>
        <button type="submit" class="btn btn-primary">💾 Sauvegarder</button>
      </div>
    </form>
  </div>
</div>
<?php $content=ob_get_clean(); include CC_ROOT.'/admin/layout.php';
