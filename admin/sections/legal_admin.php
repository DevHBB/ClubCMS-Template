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
  <?php foreach($tabs as $k=>$l): ?><a href="<?=u('/admin/legal?tab='.$k)?>" class="btn <?=$tab===$k?'btn-primary':'btn-ghost'?>"><?=$l?></a><?php endforeach; ?>
</div>
<div class="ac">
  <div class="ac-header"><h2><?=$tabs[$tab]??$tab?></h2><small style="color:#64748b">Accessible sur : /<?=str_replace('_','-',$tab)?></small></div>
  <div class="ac-body">
    <?php
    $clubName    = Config::get('club_name','[Nom du club]');
    $clubEmail   = Config::get('club_email','[email@club.fr]');
    $clubAddress = Config::get('club_address','[Adresse]');
    $clubCity    = Config::get('club_city','[Ville]');
    $clubUrl     = Config::get('site_url', $_SERVER['HTTP_HOST'] ?? '[votresite.fr]');
    $legalTemplates = [
      'mentions_legales' => [
        'asso' => "<h2>Mentions légales</h2>
<h3>Éditeur du site</h3>
<p><strong>$clubName</strong><br>Association loi 1901<br>$clubAddress, $clubCity<br>Email : $clubEmail</p>
<h3>Hébergeur</h3>
<p>[Nom hébergeur] — [Adresse hébergeur] — [Site hébergeur]</p>
<h3>Responsable de la publication</h3>
<p>[Prénom Nom], [Fonction]</p>
<h3>Propriété intellectuelle</h3>
<p>L'ensemble du contenu de ce site est la propriété de $clubName. Toute reproduction est interdite sans autorisation préalable.</p>",
        'pro'  => "<h2>Mentions légales</h2>
<h3>Éditeur du site</h3>
<p><strong>$clubName</strong><br>SARL/SAS au capital de [X] €<br>SIRET : [XXX XXX XXX XXXXX]<br>$clubAddress, $clubCity<br>Email : $clubEmail</p>
<h3>Hébergeur</h3>
<p>[Nom hébergeur] — [Adresse hébergeur] — [Site hébergeur]</p>
<h3>Responsable de la publication</h3>
<p>[Prénom Nom], [Fonction]</p>",
      ],
      'confidentialite' => [
        'asso' => "<h2>Politique de confidentialité</h2>
<h3>Responsable du traitement</h3>
<p>$clubName — $clubEmail</p>
<h3>Données collectées</h3>
<p>Nom, prénom, email, date de naissance à l'inscription. Ces données sont utilisées uniquement pour la gestion des membres.</p>
<h3>Durée de conservation</h3>
<p>Les données sont conservées pendant la durée d'adhésion + 3 ans conformément à la réglementation.</p>
<h3>Vos droits (RGPD)</h3>
<p>Conformément au RGPD, vous disposez d'un droit d'accès, de rectification, de suppression et de portabilité de vos données. Pour exercer ces droits : $clubEmail</p>",
        'pro'  => "<h2>Politique de confidentialité</h2>
<h3>Responsable du traitement</h3>
<p>$clubName — SIRET [XXXXX] — $clubEmail</p>
<h3>Données collectées</h3>
<p>Nom, prénom, email, données de commande. Traitées sur base contractuelle (RGPD art. 6.1.b).</p>
<h3>Durée de conservation</h3>
<p>Données clients conservées 5 ans (obligation légale comptable).</p>
<h3>Vos droits</h3>
<p>Accès, rectification, suppression, portabilité : $clubEmail</p>",
      ],
      'cookies' => [
        'asso' => "<h2>Politique de cookies</h2>
<p>Ce site utilise des cookies techniques nécessaires au fonctionnement (session, panier). Aucun cookie de tracking tiers n'est utilisé sans votre consentement.</p>
<h3>Cookies utilisés</h3>
<ul><li><strong>Session PHP</strong> : maintien de la connexion (supprimé à la fermeture du navigateur)</li>
<li><strong>cc_cookie_ok</strong> : mémorise votre acceptation du bandeau cookies (localStorage, 1 an)</li></ul>",
        'pro'  => "<h2>Politique de cookies</h2>
<p>Ce site utilise des cookies techniques et, avec votre consentement, des cookies d'amélioration de l'expérience.</p>
<h3>Cookies utilisés</h3>
<ul><li><strong>Session PHP</strong> : maintien de la connexion</li>
<li><strong>cc_cookie_ok</strong> : mémorise votre choix cookies</li></ul>",
      ],
      'cgu' => [
        'asso' => "<h2>Conditions Générales d'Utilisation</h2>
<h3>Objet</h3><p>Les présentes CGU régissent l'utilisation du site de $clubName.</p>
<h3>Accès au site</h3><p>L'accès à certaines fonctionnalités nécessite la création d'un compte membre.</p>
<h3>Responsabilités</h3><p>$clubName ne saurait être tenu responsable des contenus publiés par les membres sur le forum.</p>
<h3>Modification</h3><p>$clubName se réserve le droit de modifier les présentes CGU à tout moment.</p>",
        'pro'  => "<h2>Conditions Générales d'Utilisation</h2>
<h3>Objet</h3><p>Les présentes CGU régissent l'utilisation du site de $clubName.</p>
<h3>Accès</h3><p>L'accès nécessite la création d'un compte. Tout abus entraîne la suspension du compte.</p>
<h3>Propriété intellectuelle</h3><p>Tout le contenu est protégé. Reproduction interdite sans accord écrit.</p>",
      ],
    ];
    $hasTemplate = isset($legalTemplates[$tab]);
    ?>
    <?php if($hasTemplate && !Config::get('legal_'.$tab)): ?>
    <div style="background:#f0f9ff;border:1.5px solid #bae6fd;border-radius:10px;padding:.875rem 1rem;margin-bottom:1rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
      <div style="flex:1;font-size:.85rem;color:#0369a1">📄 Page vide — chargez un modèle pour démarrer :</div>
      <div style="display:flex;gap:.5rem">
        <button type="button" onclick="loadTemplate('asso')" class="btn btn-ghost btn-sm">🏅 Association loi 1901</button>
        <button type="button" onclick="loadTemplate('pro')" class="btn btn-ghost btn-sm">🏢 Club / Société</button>
      </div>
    </div>
    <script>
    var tpl = <?=json_encode($legalTemplates[$tab] ?? [])?>;
    function loadTemplate(type){ document.querySelector('textarea[name="<?=$tab?>"]').value=tpl[type]||''; }
    </script>
    <?php endif; ?>
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
