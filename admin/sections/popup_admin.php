<?php
Auth::require('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Auth::verifyCsrf()) {
    adminFlash('error','CSRF'); Helpers::redirect(u('/admin/popup'));
}

// ── Thèmes prédéfinis ────────────────────────────────────────
$themes = [
    'custom'     => ['🎨','Personnalisé',      '#6366f1','#eff6ff','✨'],
    'noel'       => ['🎄','Noël',              '#c0392b','#fff8f0','⛄'],
    'halloween'  => ['🎃','Halloween',          '#e67e22','#1a0a00','🕷️'],
    'valentin'   => ['❤️','Saint-Valentin',    '#e91e8c','#fff0f5','💝'],
    'paques'     => ['🐣','Pâques',            '#8bc34a','#f9fff0','🌸'],
    'noel_soldes'=> ['🛍️','Soldes d\'hiver',   '#1565c0','#e8f4ff','❄️'],
    'ete'        => ['☀️','Été',               '#f39c12','#fffde7','🌊'],
    'rentree'    => ['📚','Rentrée',            '#795548','#f5f0eb','✏️'],
    'anniversaire'=>['🎂','Anniversaire club', '#9c27b0','#f9f0ff','🎉'],
    'promo'      => ['💰','Promotion',          '#2e7d32','#f0fff4','🏷️'],
];

// ── Save ────────────────────────────────────────────────────
if (isset($_POST['save_popup'])) {
    $theme   = array_key_exists($_POST['theme']??'', $themes) ? $_POST['theme'] : 'custom';
    $enabled = isset($_POST['enabled']) ? 1 : 0;
    $title   = Helpers::sanitize($_POST['popup_title']   ?? '');
    $message = Helpers::sanitize($_POST['popup_message'] ?? '');
    $btn_txt = Helpers::sanitize($_POST['btn_text']      ?? '');
    $btn_url = Helpers::sanitize($_POST['btn_url']       ?? '');
    $show_once     = isset($_POST['show_once'])     ? 1 : 0;
    $show_countdown= isset($_POST['show_countdown'])? 1 : 0;
    $countdown_end = Helpers::sanitize($_POST['countdown_end'] ?? '');
    $bg_custom     = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['bg_color']??'') ? $_POST['bg_color'] : '';
    $text_custom   = preg_match('/^#[0-9a-fA-F]{6}$/', $_POST['text_color']??'') ? $_POST['text_color'] : '';
    $overlay_close = isset($_POST['overlay_close']) ? 1 : 0;

    Config::set('popup_enabled',       $enabled,        'popup');
    Config::set('popup_theme',         $theme,          'popup');
    Config::set('popup_title',         $title,          'popup');
    Config::set('popup_message',       $message,        'popup');
    Config::set('popup_btn_text',      $btn_txt,        'popup');
    Config::set('popup_btn_url',       $btn_url,        'popup');
    Config::set('popup_show_once',     $show_once,      'popup');
    Config::set('popup_show_countdown',$show_countdown, 'popup');
    Config::set('popup_countdown_end', $countdown_end,  'popup');
    Config::set('popup_bg_custom',     $bg_custom,      'popup');
    Config::set('popup_text_custom',   $text_custom,    'popup');
    Config::set('popup_overlay_close', $overlay_close,  'popup');

    adminFlash('success', 'Pop-up sauvegardé.');
    Helpers::redirect(u('/admin/popup'));
}

// ── Lecture config ───────────────────────────────────────────
$cfg = [
    'enabled'        => (int)Config::get('popup_enabled',        0),
    'theme'          => Config::get('popup_theme',         'custom'),
    'title'          => Config::get('popup_title',          ''),
    'message'        => Config::get('popup_message',        ''),
    'btn_text'       => Config::get('popup_btn_text',       ''),
    'btn_url'        => Config::get('popup_btn_url',        ''),
    'show_once'      => (int)Config::get('popup_show_once',      1),
    'show_countdown' => (int)Config::get('popup_show_countdown',  0),
    'countdown_end'  => Config::get('popup_countdown_end',  ''),
    'bg_custom'      => Config::get('popup_bg_custom',      ''),
    'text_custom'    => Config::get('popup_text_custom',    ''),
    'overlay_close'  => (int)Config::get('popup_overlay_close',  1),
];
$t = $themes[$cfg['theme']] ?? $themes['custom'];
[$t_ico, $t_name, $t_color, $t_bg, $t_deco] = $t;
$previewBg   = $cfg['bg_custom']   ?: $t_bg;
$previewAccent = $cfg['bg_custom'] ?: $t_color;

$pageTitle = 'Pop-up annonces';
ob_start();
?>

<div class="page-head">
  <h1>🎉 Pop-up annonces</h1>
  <div style="display:flex;align-items:center;gap:.75rem">
    <span style="font-size:.82rem;color:#64748b">Statut actuel :</span>
    <span style="padding:.25rem .75rem;border-radius:99px;font-size:.82rem;font-weight:700;
      background:<?=$cfg['enabled']?'#dcfce7':'#fee2e2'?>;color:<?=$cfg['enabled']?'#16a34a':'#dc2626'?>">
      <?=$cfg['enabled']?'✅ Actif':'❌ Inactif'?>
    </span>
  </div>
</div>

<form method="post">
<?=Auth::csrfField()?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start">

  <!-- ── Colonne gauche : configuration ───────────────────── -->
  <div style="display:flex;flex-direction:column;gap:1.25rem">

    <!-- Activer/désactiver -->
    <div class="ac">
      <div class="ac-header"><h2>Activation</h2></div>
      <div class="ac-body" style="display:flex;align-items:center;gap:1rem">
        <label class="popup-toggle-wrap" for="chk-enabled">
          <input type="checkbox" name="enabled" value="1" id="chk-enabled"
            <?=$cfg['enabled']?'checked':''?> class="popup-chk">
          <span class="popup-track"></span>
        </label>
        <div>
          <div style="font-weight:700">Afficher le pop-up sur le site</div>
          <div style="font-size:.8rem;color:#64748b">S'affiche dès l'arrivée sur le site</div>
        </div>
      </div>
    </div>

    <!-- Thème -->
    <div class="ac">
      <div class="ac-header"><h2>🎨 Thème</h2></div>
      <div class="ac-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:.5rem" id="theme-grid">
          <?php foreach($themes as $k=>[$ico,$lbl,$col,$bg,$deco]): ?>
          <label style="position:relative">
            <input type="radio" name="theme" value="<?=$k?>"
              <?=$cfg['theme']===$k?'checked':''?>
              onchange="updatePreview()"
              style="position:absolute;opacity:0;width:0;height:0">
            <div class="theme-card" id="tc-<?=$k?>" data-bg="<?=Helpers::e($bg)?>" data-color="<?=Helpers::e($col)?>"
              style="border:2px solid <?=$cfg['theme']===$k?Helpers::e($col):'#e2e8f0'?>;border-radius:12px;
                     padding:.625rem;text-align:center;cursor:pointer;transition:all .15s;
                     background:<?=$cfg['theme']===$k?'color-mix(in srgb,'.Helpers::e($col).' 8%,#fff)':'#fff'?>">
              <div style="font-size:1.4rem"><?=$ico?></div>
              <div style="font-size:.72rem;font-weight:700;margin-top:.2rem;color:#374151"><?=$lbl?></div>
            </div>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Contenu -->
    <div class="ac">
      <div class="ac-header"><h2>📝 Contenu</h2></div>
      <div class="ac-body" style="display:flex;flex-direction:column;gap:.875rem">
        <div class="fg">
          <label>Titre *</label>
          <input type="text" name="popup_title" class="be-input"
            value="<?=Helpers::e($cfg['title'])?>" placeholder="Ex: 🎄 Joyeux Noël !"
            oninput="document.getElementById('prev-title').textContent=this.value">
        </div>
        <div class="fg">
          <label>Message</label>
          <textarea name="popup_message" class="be-input" rows="3"
            placeholder="Votre annonce ou code promo…"
            oninput="document.getElementById('prev-msg').textContent=this.value"
            style="resize:vertical"><?=Helpers::e($cfg['message'])?></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
          <div class="fg">
            <label>Bouton — texte</label>
            <input type="text" name="btn_text" class="be-input"
              value="<?=Helpers::e($cfg['btn_text'])?>" placeholder="Ex: En profiter !">
          </div>
          <div class="fg">
            <label>Bouton — URL</label>
            <input type="url" name="btn_url" class="be-input"
              value="<?=Helpers::e($cfg['btn_url'])?>" placeholder="https://…">
          </div>
        </div>
      </div>
    </div>

    <!-- Couleurs custom -->
    <div class="ac">
      <div class="ac-header"><h2>🎨 Couleurs personnalisées</h2></div>
      <div class="ac-body">
        <p style="font-size:.82rem;color:#64748b;margin-bottom:.875rem">
          Laissez vide pour utiliser les couleurs du thème choisi.
        </p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.875rem">
          <div class="fg">
            <label>Fond du pop-up</label>
            <div style="display:flex;gap:.5rem;align-items:center">
              <input type="color" name="bg_color" value="<?=Helpers::e($cfg['bg_custom']?:$t_bg)?>"
                style="width:40px;height:36px;border-radius:6px;border:1.5px solid #e2e8f0;cursor:pointer;padding:2px">
              <button type="button" onclick="this.previousElementSibling.value='';updatePreview()"
                style="font-size:.75rem;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:.2rem .5rem;cursor:pointer;color:#64748b">
                Reset thème
              </button>
            </div>
          </div>
          <div class="fg">
            <label>Couleur du texte</label>
            <div style="display:flex;gap:.5rem;align-items:center">
              <input type="color" name="text_color" value="<?=Helpers::e($cfg['text_custom']?:'#1e293b')?>"
                style="width:40px;height:36px;border-radius:6px;border:1.5px solid #e2e8f0;cursor:pointer;padding:2px">
              <button type="button" onclick="this.previousElementSibling.value='';updatePreview()"
                style="font-size:.75rem;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;padding:.2rem .5rem;cursor:pointer;color:#64748b">
                Reset
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Options comportement -->
    <div class="ac">
      <div class="ac-header"><h2>⚙️ Comportement</h2></div>
      <div class="ac-body" style="display:flex;flex-direction:column;gap:.875rem">
        <!-- Afficher une seule fois -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.75rem;background:#f8fafc;border-radius:10px">
          <div>
            <div style="font-weight:600;font-size:.9rem">Afficher une seule fois par visite</div>
            <div style="font-size:.78rem;color:#64748b">Ne s'affiche plus si le visiteur l'a fermé</div>
          </div>
          <label class="popup-toggle-wrap">
            <input type="checkbox" name="show_once" value="1" class="popup-chk" <?=$cfg['show_once']?'checked':''?>>
            <span class="popup-track"></span>
          </label>
        </div>
        <!-- Fermer en cliquant sur l'overlay -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:.75rem;background:#f8fafc;border-radius:10px">
          <div>
            <div style="font-weight:600;font-size:.9rem">Fermer en cliquant dehors</div>
            <div style="font-size:.78rem;color:#64748b">Clic sur le fond ferme le pop-up</div>
          </div>
          <label class="popup-toggle-wrap">
            <input type="checkbox" name="overlay_close" value="1" class="popup-chk" <?=$cfg['overlay_close']?'checked':''?>>
            <span class="popup-track"></span>
          </label>
        </div>
        <!-- Compte à rebours -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;padding:.75rem;background:#f8fafc;border-radius:10px">
          <div style="flex:1">
            <div style="font-weight:600;font-size:.9rem">Compte à rebours</div>
            <div style="font-size:.78rem;color:#64748b;margin-bottom:.5rem">Affiche un timer jusqu'à une date</div>
            <div id="countdown-date-wrap" style="display:<?=$cfg['show_countdown']?'block':'none'?>">
              <input type="datetime-local" name="countdown_end" class="be-input" style="max-width:220px"
                value="<?=Helpers::e($cfg['countdown_end'])?>">
            </div>
          </div>
          <label class="popup-toggle-wrap">
            <input type="checkbox" name="show_countdown" value="1" class="popup-chk" <?=$cfg['show_countdown']?'checked':''?>
              onchange="document.getElementById('countdown-date-wrap').style.display=this.checked?'block':'none'">
            <span class="popup-track"></span>
          </label>
        </div>
      </div>
    </div>

    <button type="submit" name="save_popup" class="btn btn-primary" style="width:100%;padding:.875rem">
      💾 Sauvegarder le pop-up
    </button>
  </div>

  <!-- ── Colonne droite : aperçu live ─────────────────────── -->
  <div style="position:sticky;top:1.5rem">
    <div class="ac">
      <div class="ac-header"><h2>👁 Aperçu</h2></div>
      <div class="ac-body" style="padding:1rem">
        <div id="popup-preview" style="border-radius:16px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.15);background:<?=Helpers::e($previewBg)?>">
          <div id="prev-deco-bar" style="background:<?=Helpers::e($previewAccent)?>;padding:.5rem 1rem;display:flex;align-items:center;justify-content:space-between">
            <span id="prev-deco" style="font-size:1.2rem"><?=$t_deco?></span>
            <span style="color:#fff;font-size:.75rem;opacity:.8"><?=$t_name?></span>
            <span style="color:rgba(255,255,255,.7);cursor:pointer;font-size:1rem">✕</span>
          </div>
          <div style="padding:1.25rem">
            <h3 id="prev-title" style="font-size:1.05rem;font-weight:800;margin-bottom:.5rem;color:#1e293b">
              <?=Helpers::e($cfg['title'] ?: 'Titre du pop-up')?>
            </h3>
            <p id="prev-msg" style="font-size:.875rem;color:#475569;line-height:1.6;margin-bottom:.875rem">
              <?=Helpers::e($cfg['message'] ?: 'Votre message s\'affichera ici…')?>
            </p>
            <?php if($cfg['show_countdown']&&$cfg['countdown_end']): ?>
            <div style="display:flex;gap:.4rem;margin-bottom:.875rem;justify-content:center">
              <?php foreach(['J'=>'jours','H'=>'heures','M'=>'min','S'=>'sec'] as $k=>$v): ?>
              <div style="background:<?=Helpers::e($previewAccent)?>;color:#fff;border-radius:8px;padding:.4rem .6rem;text-align:center;min-width:48px">
                <div style="font-size:1.2rem;font-weight:800">00</div>
                <div style="font-size:.6rem;opacity:.8"><?=$v?></div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if($cfg['btn_text']): ?>
            <div style="background:<?=Helpers::e($previewAccent)?>;color:#fff;border-radius:8px;padding:.5rem 1rem;text-align:center;font-weight:700;font-size:.875rem">
              <?=Helpers::e($cfg['btn_text'])?>
            </div>
            <?php endif; ?>
          </div>
        </div>
        <p style="text-align:center;font-size:.75rem;color:#94a3b8;margin-top:.75rem">Aperçu non interactif</p>
      </div>
    </div>
  </div>

</div>
</form>

<style>
/* Toggles */
.popup-toggle-wrap { position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0 }
.popup-chk { opacity:0;position:absolute;inset:0;margin:0;cursor:pointer;z-index:2;width:100%;height:100% }
.popup-track { position:absolute;inset:0;border-radius:99px;background:#e2e8f0;transition:background .2s }
.popup-track::after { content:'';position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:#fff;transition:left .2s;box-shadow:0 1px 3px rgba(0,0,0,.25) }
.popup-chk:checked + .popup-track { background:var(--color-primary) }
.popup-chk:checked + .popup-track::after { left:23px }
/* Thème cards */
.theme-card:hover { transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,.1) }
</style>

<script>
var themes = <?=json_encode(array_map(fn($t)=>['bg'=>$t[3],'color'=>$t[2],'deco'=>$t[4],'name'=>$t[1]], $themes),JSON_UNESCAPED_UNICODE)?>;

function updatePreview() {
  var t = document.querySelector('[name="theme"]:checked')?.value || 'custom';
  var th = themes[t] || themes['custom'];
  var bg    = th.bg;
  var color = th.color;

  // Mise à jour styles des cards
  document.querySelectorAll('.theme-card').forEach(function(c) {
    c.style.border = '2px solid #e2e8f0';
    c.style.background = '#fff';
  });
  var sel = document.getElementById('tc-'+t);
  if (sel) { sel.style.border = '2px solid '+color; sel.style.background = 'color-mix(in srgb,'+color+' 8%,#fff)'; }

  // Mise à jour aperçu
  document.getElementById('popup-preview').style.background = bg;
  document.getElementById('prev-deco-bar').style.background  = color;
  document.getElementById('prev-deco').textContent = th.deco;
}

// Appliquer au chargement
updatePreview();
// Écouter les changements de thème
document.querySelectorAll('[name="theme"]').forEach(function(r){
  r.addEventListener('change', updatePreview);
});
</script>

<?php
$content = ob_get_clean();
include CC_ROOT . '/admin/layout.php';
