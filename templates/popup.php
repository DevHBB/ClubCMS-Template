<?php
/**
 * ClubCMS — Pop-up annonce (inclus dans layout.php si actif)
 */
if (!Config::get('popup_enabled', 0)) return;

$theme   = Config::get('popup_theme',   'custom');
$title   = Config::get('popup_title',   '');
$message = Config::get('popup_message', '');
$btnTxt  = Config::get('popup_btn_text','');
$btnUrl  = Config::get('popup_btn_url', '');
$once    = (int)Config::get('popup_show_once',      1);
$cdown   = (int)Config::get('popup_show_countdown', 0);
$cdEnd   = Config::get('popup_countdown_end', '');
$ovClose = (int)Config::get('popup_overlay_close',  1);
$bgCustom  = Config::get('popup_bg_custom',   '');
$txtCustom = Config::get('popup_text_custom', '');

if (!$title && !$message) return;

$themes = [
    'custom'     => ['🎨','Personnalisé',       '#6366f1','#eff6ff','✨','linear-gradient(135deg,#6366f1,#8b5cf6)'],
    'noel'       => ['🎄','Noël',               '#c0392b','#fff8f0','⛄','linear-gradient(135deg,#c0392b,#27ae60)'],
    'halloween'  => ['🎃','Halloween',           '#e67e22','#1a0a00','🕷️','linear-gradient(135deg,#e67e22,#2c1810)'],
    'valentin'   => ['❤️','Saint-Valentin',     '#e91e8c','#fff0f5','💝','linear-gradient(135deg,#e91e8c,#ff6b9d)'],
    'paques'     => ['🐣','Pâques',             '#8bc34a','#f9fff0','🌸','linear-gradient(135deg,#8bc34a,#f9a825)'],
    'noel_soldes'=> ['🛍️','Soldes d\'hiver',    '#1565c0','#e8f4ff','❄️','linear-gradient(135deg,#1565c0,#0288d1)'],
    'ete'        => ['☀️','Été',                '#f39c12','#fffde7','🌊','linear-gradient(135deg,#f39c12,#e74c3c)'],
    'rentree'    => ['📚','Rentrée',             '#795548','#f5f0eb','✏️','linear-gradient(135deg,#795548,#a1887f)'],
    'anniversaire'=>['🎂','Anniversaire club',  '#9c27b0','#f9f0ff','🎉','linear-gradient(135deg,#9c27b0,#e040fb)'],
    'promo'      => ['💰','Promotion',           '#2e7d32','#f0fff4','🏷️','linear-gradient(135deg,#2e7d32,#66bb6a)'],
];

$t = $themes[$theme] ?? $themes['custom'];
[$ico, $tname, $tcolor, $tbg, $tdeco, $tgrad] = $t;
$popupBg    = $bgCustom  ?: $tbg;
$popupColor = $tcolor;
$textColor  = $txtCustom ?: '#1e293b';
$popupId    = 'site-popup';
?>

<div id="<?=$popupId?>-overlay" style="
  display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);
  z-index:99999;align-items:center;justify-content:center;padding:1rem;
  backdrop-filter:blur(3px)">

  <div id="<?=$popupId?>" style="
    background:<?=Helpers::e($popupBg)?>;border-radius:20px;
    max-width:460px;width:100%;box-shadow:0 24px 64px rgba(0,0,0,.25);
    overflow:hidden;animation:popupIn .35s cubic-bezier(.34,1.56,.64,1) both">

    <!-- Barre header colorée -->
    <div style="background:<?=Helpers::e($tgrad)?>;padding:.875rem 1.25rem;
      display:flex;align-items:center;justify-content:space-between">
      <div style="display:flex;align-items:center;gap:.5rem">
        <span style="font-size:1.5rem"><?=$ico?></span>
        <span style="color:rgba(255,255,255,.85);font-size:.82rem;font-weight:600"><?=Helpers::e($tname)?></span>
      </div>
      <button onclick="closePopup()" style="
        background:rgba(255,255,255,.2);border:none;border-radius:50%;
        width:28px;height:28px;cursor:pointer;color:#fff;font-size:1rem;
        display:flex;align-items:center;justify-content:center;
        transition:background .15s" onmouseover="this.style.background='rgba(255,255,255,.35)'"
        onmouseout="this.style.background='rgba(255,255,255,.2)'">&times;</button>
    </div>

    <!-- Corps -->
    <div style="padding:1.5rem 1.75rem">
      <!-- Déco animée -->
      <div style="text-align:center;font-size:2.5rem;margin-bottom:.75rem;
        animation:bounce 1.5s ease-in-out infinite"><?=$tdeco?></div>

      <h2 style="text-align:center;font-size:1.3rem;font-weight:800;
        color:<?=Helpers::e($textColor)?>;margin-bottom:.625rem;line-height:1.3">
        <?=Helpers::e($title)?>
      </h2>

      <?php if($message): ?>
      <p style="text-align:center;color:<?=Helpers::e($textColor)?>;opacity:.8;
        font-size:.9rem;line-height:1.65;margin-bottom:1rem">
        <?=nl2br(Helpers::e($message))?>
      </p>
      <?php endif; ?>

      <!-- Compte à rebours -->
      <?php if($cdown && $cdEnd): ?>
      <div id="popup-countdown" style="display:flex;gap:.5rem;justify-content:center;margin-bottom:1rem">
        <?php foreach(['j'=>'Jours','h'=>'Heures','m'=>'Min','s'=>'Sec'] as $k=>$v): ?>
        <div style="background:<?=Helpers::e($tgrad)?>;border-radius:10px;padding:.5rem .75rem;
          text-align:center;min-width:56px;box-shadow:0 2px 8px rgba(0,0,0,.15)">
          <div id="cd-<?=$k?>" style="font-size:1.5rem;font-weight:800;color:#fff;line-height:1">00</div>
          <div style="font-size:.6rem;color:rgba(255,255,255,.75);margin-top:.15rem"><?=$v?></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Bouton CTA -->
      <?php if($btnTxt): ?>
      <div style="text-align:center;margin-bottom:.875rem">
        <a href="<?=Helpers::e($btnUrl?:'')?>" style="
          display:inline-block;background:<?=Helpers::e($tgrad)?>;color:#fff;
          padding:.75rem 2rem;border-radius:99px;font-weight:700;font-size:.95rem;
          text-decoration:none;box-shadow:0 4px 16px rgba(0,0,0,.2);
          transition:transform .15s,box-shadow .15s"
          onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 6px 20px rgba(0,0,0,.25)'"
          onmouseout="this.style.transform='';this.style.boxShadow='0 4px 16px rgba(0,0,0,.2)'"
          <?=$btnUrl?'':'onclick="closePopup();return false"'?>>
          <?=Helpers::e($btnTxt)?>
        </a>
      </div>
      <?php endif; ?>

      <div style="text-align:center">
        <button onclick="closePopup()" style="
          background:none;border:none;cursor:pointer;color:<?=Helpers::e($textColor)?>;
          opacity:.5;font-size:.8rem;text-decoration:underline">
          Fermer
        </button>
      </div>
    </div>
  </div>
</div>

<style>
@keyframes popupIn {
  from { opacity:0;transform:scale(.85) translateY(20px); }
  to   { opacity:1;transform:scale(1)   translateY(0); }
}
@keyframes bounce {
  0%,100% { transform:translateY(0); }
  50%      { transform:translateY(-6px); }
}
</style>

<script>
(function() {
  var STORAGE_KEY = 'popup_closed_<?=md5($title.$message)?>';
  var showOnce    = <?=$once?'true':'false'?>;
  var ovClose     = <?=$ovClose?'true':'false'?>;
  var cdEnd       = <?=$cdown&&$cdEnd?'"'.Helpers::e($cdEnd).'"':'null'?>;

  function openPopup() {
    var ov = document.getElementById('<?=$popupId?>-overlay');
    if (ov) { ov.style.display = 'flex'; }
  }
  function closePopup() {
    var ov = document.getElementById('<?=$popupId?>-overlay');
    if (ov) {
      ov.style.opacity = '0';
      setTimeout(function(){ ov.style.display='none'; ov.style.opacity=''; }, 250);
    }
    if (showOnce) sessionStorage.setItem(STORAGE_KEY, '1');
  }
  window.closePopup = closePopup;

  // Overlay click
  var ov = document.getElementById('<?=$popupId?>-overlay');
  if (ov && ovClose) {
    ov.addEventListener('click', function(e) {
      if (e.target === ov) closePopup();
    });
  }

  // Compte à rebours
  if (cdEnd) {
    var endDate = new Date(cdEnd);
    function updateCd() {
      var diff = endDate - new Date();
      if (diff <= 0) { diff = 0; }
      var s = Math.floor(diff/1000)%60;
      var m = Math.floor(diff/60000)%60;
      var h = Math.floor(diff/3600000)%24;
      var j = Math.floor(diff/86400000);
      function pad(n){return String(n).padStart(2,'0');}
      var dj=document.getElementById('cd-j'), dh=document.getElementById('cd-h'),
          dm=document.getElementById('cd-m'), ds=document.getElementById('cd-s');
      if(dj)dj.textContent=pad(j); if(dh)dh.textContent=pad(h);
      if(dm)dm.textContent=pad(m); if(ds)ds.textContent=pad(s);
    }
    updateCd();
    setInterval(updateCd, 1000);
  }

  // Affichage avec délai
  setTimeout(function() {
    if (showOnce && sessionStorage.getItem(STORAGE_KEY)) return;
    openPopup();
  }, 800);
})();
</script>
