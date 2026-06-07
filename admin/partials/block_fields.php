<?php
/**
 * Champs de saisie selon le type de bloc
 * Variables : $p (prefix name), $block, $type
 */
$b = $block; // alias court
?>
<?php if ($type === 'paragraph'): ?>
<?php $eid = 'be-ed-'.md5($p); ?>
  <div class="be-field">
    <label class="be-label">Texte</label>
    <!-- Barre d'outils WYSIWYG -->
    <div class="be-toolbar" style="gap:.2rem;flex-wrap:wrap;border-radius:8px 8px 0 0;border-bottom:none">
      <button type="button" class="be-tool" onmousedown="event.preventDefault();document.execCommand('bold')"          title="Gras"><b>G</b></button>
      <button type="button" class="be-tool" onmousedown="event.preventDefault();document.execCommand('italic')"        title="Italique"><i>I</i></button>
      <button type="button" class="be-tool" onmousedown="event.preventDefault();document.execCommand('underline')"     title="Souligné"><u>S</u></button>
      <button type="button" class="be-tool" onmousedown="event.preventDefault();document.execCommand('strikeThrough')" title="Barré"><s>S</s></button>
      <span style="width:1px;background:#e2e8f0;align-self:stretch;margin:0 .1rem"></span>
      <!-- Couleur texte -->
      <label class="be-tool" title="Couleur du texte" style="display:inline-flex;align-items:center;gap:.15rem;cursor:pointer;padding:.18rem .4rem">
        A <input type="color" value="#e74c3c"
           onchange="document.execCommand('foreColor',false,this.value)"
           style="width:18px;height:18px;border:none;border-radius:3px;cursor:pointer;padding:0;background:none">
      </label>
      <!-- Surligneur -->
      <label class="be-tool" title="Surligneur" style="display:inline-flex;align-items:center;gap:.15rem;cursor:pointer;padding:.18rem .4rem">
        ▓ <input type="color" value="#fff176"
           onchange="document.execCommand('hiliteColor',false,this.value)"
           style="width:18px;height:18px;border:none;border-radius:3px;cursor:pointer;padding:0">
      </label>
      <span style="width:1px;background:#e2e8f0;align-self:stretch;margin:0 .1rem"></span>
      <!-- Taille -->
      <select class="be-tool" onchange="beWysiwygSize(this,'<?=$eid?>')" style="padding:.2rem .3rem;font-size:.78rem;height:28px">
        <option value="">Taille</option>
        <option value="1">Petit</option>
        <option value="3">Normal</option>
        <option value="4">Grand</option>
        <option value="5">Très grand</option>
        <option value="7">Énorme</option>
      </select>
      <!-- Police -->
      <select class="be-tool" onchange="document.execCommand('fontName',false,this.value);this.value=''" style="padding:.2rem .3rem;font-size:.78rem;height:28px">
        <option value="">Police</option>
        <option value="Arial">Arial</option>
        <option value="Georgia">Georgia</option>
        <option value="Times New Roman">Times New Roman</option>
        <option value="Courier New">Courier New</option>
        <option value="Verdana">Verdana</option>
      </select>
      <span style="width:1px;background:#e2e8f0;align-self:stretch;margin:0 .1rem"></span>
      <!-- Alignement -->
      <button type="button" class="be-tool" onmousedown="event.preventDefault();document.execCommand('justifyLeft')"    title="Gauche">◀</button>
      <button type="button" class="be-tool" onmousedown="event.preventDefault();document.execCommand('justifyCenter')"  title="Centrer">▬</button>
      <button type="button" class="be-tool" onmousedown="event.preventDefault();document.execCommand('justifyRight')"   title="Droite">▶</button>
      <button type="button" class="be-tool" onmousedown="event.preventDefault();document.execCommand('justifyFull')"    title="Justifier">☰</button>
      <span style="width:1px;background:#e2e8f0;align-self:stretch;margin:0 .1rem"></span>
      <!-- Liens & listes -->
      <button type="button" class="be-tool" onmousedown="event.preventDefault();beWysiwygLink('<?=$eid?>')" title="Insérer un lien">🔗</button>
      <button type="button" class="be-tool" onmousedown="event.preventDefault();document.execCommand('insertUnorderedList')" title="Liste à puces">• Liste</button>
      <button type="button" class="be-tool" onmousedown="event.preventDefault();document.execCommand('insertOrderedList')"   title="Liste numérotée">1. Liste</button>
      <!-- Image -->
      <button type="button" class="be-tool" onmousedown="event.preventDefault();beWysiwygImage('<?=$eid?>')" title="Insérer une image">🖼</button>
      <!-- Effacer formatage -->
      <button type="button" class="be-tool" onmousedown="event.preventDefault();document.execCommand('removeFormat')" title="Effacer le formatage" style="color:#94a3b8">✕fmt</button>
    </div>
    <!-- Zone éditable WYSIWYG -->
    <div id="<?=$eid?>"
         contenteditable="true"
         class="be-wysiwyg"
         style="min-height:120px;border:1.5px solid #e2e8f0;border-top:none;border-radius:0 0 8px 8px;padding:.75rem;font-size:.9rem;line-height:1.75;outline:none;background:#fff;cursor:text"
         oninput="beWysiwygSync(this,'<?=htmlspecialchars($p)?>')"
         onfocus="this.style.borderColor='var(--color-primary)'"
         onblur="this.style.borderColor='#e2e8f0'"
    ><?= $b['content'] ?? '' ?></div>
    <input type="hidden" name="<?=$p?>[content]" id="<?=$eid?>-val" value="<?=Helpers::e($b['content']??'')?>">
  </div>

<?php elseif ($type === 'heading'): ?>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Titre</label>
      <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'')?>" placeholder="Mon titre de section">
    </div>
    <div class="be-field">
      <label class="be-label">Niveau</label>
      <select name="<?=$p?>[level]" class="be-select">
        <option value="h2" <?=($b['level']??'h2')==='h2'?'selected':''?>>H2 — Titre principal</option>
        <option value="h3" <?=($b['level']??'h2')==='h3'?'selected':''?>>H3 — Sous-titre</option>
        <option value="h4" <?=($b['level']??'h2')==='h4'?'selected':''?>>H4 — Petit titre</option>
      </select>
    </div>
  </div>
  <div class="be-field">
    <label class="be-label">Sous-titre (optionnel)</label>
    <input type="text" name="<?=$p?>[subtitle]" class="be-input" value="<?=Helpers::e($b['subtitle']??'')?>" placeholder="Un texte complémentaire sous le titre">
  </div>
  <div class="be-field">
    <label class="be-label">Alignement</label>
    <select name="<?=$p?>[align]" class="be-select" style="max-width:200px">
      <option value="left"   <?=($b['align']??'left')==='left'?'selected':''?>>◀ Gauche</option>
      <option value="center" <?=($b['align']??'left')==='center'?'selected':''?>>▬ Centre</option>
      <option value="right"  <?=($b['align']??'left')==='right'?'selected':''?>>▶ Droite</option>
    </select>
  </div>

<?php elseif ($type === 'quote'): ?>
  <div class="be-field">
    <label class="be-label">Citation</label>
    <textarea name="<?=$p?>[content]" class="be-richtext" style="min-height:80px" placeholder="Le texte de la citation…"><?=Helpers::e($b['content']??'')?></textarea>
  </div>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Auteur (optionnel)</label>
      <input type="text" name="<?=$p?>[author]" class="be-input" value="<?=Helpers::e($b['author']??'')?>" placeholder="Jean Dupont">
    </div>
    <div class="be-field">
      <label class="be-label">Couleur de bordure</label>
      <input type="color" name="<?=$p?>[color]" value="<?=Helpers::e($b['color']??'#3b82f6')?>" style="height:40px;width:60px;border-radius:6px;border:1px solid #e2e8f0;cursor:pointer;padding:2px">
    </div>
  </div>

<?php elseif ($type === 'info_boxes'): ?>
  <div class="be-field">
    <label class="be-label">Titre de section (optionnel)</label>
    <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'')?>" placeholder="Nos points forts">
  </div>
  <div class="be-field">
    <label class="be-label">Nombre de colonnes</label>
    <select name="<?=$p?>[cols]" class="be-select" style="max-width:180px">
      <option value="2" <?=($b['cols']??'3')==='2'?'selected':''?>>2 colonnes</option>
      <option value="3" <?=($b['cols']??'3')==='3'?'selected':''?>>3 colonnes</option>
      <option value="4" <?=($b['cols']??'3')==='4'?'selected':''?>>4 colonnes</option>
    </select>
  </div>
  <?php $cols = (int)($b['cols']??3); if ($cols < 2 || $cols > 4) $cols = 3; ?>
  <?php for ($c = 1; $c <= $cols; $c++): ?>
  <div class="be-ibox">
    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b;margin-bottom:.6rem">Colonne <?=$c?></div>
    <div class="be-row three">
      <div class="be-field" style="margin:0">
        <label class="be-label">Emoji / Icône</label>
        <input type="text" name="<?=$p?>[box<?=$c?>_icon]" class="be-input" value="<?=Helpers::e($b['box'.$c.'_icon']??'')?>" placeholder="🏆" style="font-size:1.3rem;text-align:center">
      </div>
      <div class="be-field" style="margin:0;grid-column:span 2">
        <label class="be-label">Titre</label>
        <input type="text" name="<?=$p?>[box<?=$c?>_title]" class="be-input" value="<?=Helpers::e($b['box'.$c.'_title']??'')?>" placeholder="Notre expertise">
      </div>
    </div>
    <div class="be-field" style="margin-top:.6rem;margin-bottom:0">
      <label class="be-label">Description</label>
      <textarea name="<?=$p?>[box<?=$c?>_text]" class="be-textarea" style="min-height:70px" placeholder="Texte de description…"><?=Helpers::e($b['box'.$c.'_text']??'')?></textarea>
    </div>
  </div>
  <?php endfor; ?>

<?php elseif ($type === 'two_columns'): ?>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Colonne gauche</label>
      <textarea name="<?=$p?>[col_left]" class="be-richtext" placeholder="Contenu de gauche…"><?=Helpers::e($b['col_left']??'')?></textarea>
    </div>
    <div class="be-field">
      <label class="be-label">Colonne droite</label>
      <textarea name="<?=$p?>[col_right]" class="be-richtext" placeholder="Contenu de droite…"><?=Helpers::e($b['col_right']??'')?></textarea>
    </div>
  </div>
  <div class="be-field">
    <label class="be-label">Répartition</label>
    <select name="<?=$p?>[split]" class="be-select" style="max-width:220px">
      <option value="50-50" <?=($b['split']??'50-50')==='50-50'?'selected':''?>>50% / 50%</option>
      <option value="60-40" <?=($b['split']??'50-50')==='60-40'?'selected':''?>>60% / 40%</option>
      <option value="40-60" <?=($b['split']??'50-50')==='40-60'?'selected':''?>>40% / 60%</option>
      <option value="70-30" <?=($b['split']??'50-50')==='70-30'?'selected':''?>>70% / 30%</option>
    </select>
  </div>

<?php elseif ($type === 'cta'): ?>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Titre</label>
      <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'')?>" placeholder="Rejoignez-nous !">
    </div>
    <div class="be-field">
      <label class="be-label">Texte du bouton</label>
      <input type="text" name="<?=$p?>[btn_label]" class="be-input" value="<?=Helpers::e($b['btn_label']??'')?>" placeholder="S'inscrire">
    </div>
  </div>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Texte d'accroche</label>
      <input type="text" name="<?=$p?>[subtitle]" class="be-input" value="<?=Helpers::e($b['subtitle']??'')?>" placeholder="Devenez membre de notre club dès aujourd'hui">
    </div>
    <div class="be-field">
      <label class="be-label">Lien du bouton</label>
      <input type="text" name="<?=$p?>[btn_url]" class="be-input" value="<?=Helpers::e($b['btn_url']??'/register')?>" placeholder="/register">
    </div>
  </div>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Couleur de fond</label>
      <input type="color" name="<?=$p?>[bg_color]" value="<?=Helpers::e($b['bg_color']??'#1d4ed8')?>" style="height:40px;width:60px;border-radius:6px;border:1px solid #e2e8f0;cursor:pointer;padding:2px">
    </div>
    <div class="be-field">
      <label class="be-label">Style bouton</label>
      <select name="<?=$p?>[btn_style]" class="be-select">
        <option value="white"   <?=($b['btn_style']??'white')==='white'?'selected':''?>>Blanc</option>
        <option value="outline" <?=($b['btn_style']??'white')==='outline'?'selected':''?>>Contour blanc</option>
        <option value="dark"    <?=($b['btn_style']??'white')==='dark'?'selected':''?>>Sombre</option>
      </select>
    </div>
  </div>

<?php elseif ($type === 'alert'): ?>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Type d'alerte</label>
      <select name="<?=$p?>[alert_type]" class="be-select">
        <option value="info"    <?=($b['alert_type']??'info')==='info'?'selected':''?>>ℹ️ Information</option>
        <option value="success" <?=($b['alert_type']??'info')==='success'?'selected':''?>>✅ Succès</option>
        <option value="warning" <?=($b['alert_type']??'info')==='warning'?'selected':''?>>⚠️ Avertissement</option>
        <option value="error"   <?=($b['alert_type']??'info')==='error'?'selected':''?>>❌ Erreur / Urgent</option>
      </select>
    </div>
    <div class="be-field">
      <label class="be-label">Titre (optionnel)</label>
      <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'')?>" placeholder="Attention !">
    </div>
  </div>
  <div class="be-field">
    <label class="be-label">Message</label>
    <textarea name="<?=$p?>[content]" class="be-textarea" style="min-height:80px" placeholder="Le contenu du message d'alerte…"><?=Helpers::e($b['content']??'')?></textarea>
  </div>

<?php elseif ($type === 'divider'): ?>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Style</label>
      <select name="<?=$p?>[style]" class="be-select">
        <option value="line"   <?=($b['style']??'line')==='line'?'selected':''?>>— Ligne fine</option>
        <option value="thick"  <?=($b['style']??'line')==='thick'?'selected':''?>>━ Ligne épaisse</option>
        <option value="dots"   <?=($b['style']??'line')==='dots'?'selected':''?>>··· Points</option>
        <option value="space"  <?=($b['style']??'line')==='space'?'selected':''?>>  Espace vide</option>
        <option value="stars"  <?=($b['style']??'line')==='stars'?'selected':''?>>✦ Étoiles</option>
      </select>
    </div>
    <div class="be-field">
      <label class="be-label">Espacement</label>
      <select name="<?=$p?>[spacing]" class="be-select">
        <option value="sm" <?=($b['spacing']??'md')==='sm'?'selected':''?>>Petit</option>
        <option value="md" <?=($b['spacing']??'md')==='md'?'selected':''?>>Normal</option>
        <option value="lg" <?=($b['spacing']??'md')==='lg'?'selected':''?>>Grand</option>
      </select>
    </div>
  </div>

<?php elseif ($type === 'image'): ?>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Image</label>
      <input type="file" name="<?=$p?>[image_upload]" accept="image/*">
      <?php if (!empty($b['image_src'])): ?>
      <img src="<?=asset($b['image_src'])?>" style="height:60px;margin-top:.4rem;border-radius:6px;object-fit:cover">
      <input type="hidden" name="<?=$p?>[image_src]" value="<?=Helpers::e($b['image_src'])?>">
      <?php endif; ?>
    </div>
    <div class="be-field">
      <label class="be-label">Ou URL externe</label>
      <input type="text" name="<?=$p?>[image_url]" class="be-input" value="<?=Helpers::e($b['image_url']??'')?>" placeholder="https://example.com/image.jpg">
    </div>
  </div>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Légende (optionnelle)</label>
      <input type="text" name="<?=$p?>[caption]" class="be-input" value="<?=Helpers::e($b['caption']??'')?>" placeholder="Description de l'image">
    </div>
    <div class="be-field">
      <label class="be-label">Largeur</label>
      <select name="<?=$p?>[width]" class="be-select">
        <option value="full"   <?=($b['width']??'full')==='full'?'selected':''?>>Pleine largeur</option>
        <option value="large"  <?=($b['width']??'full')==='large'?'selected':''?>>Grande (800px)</option>
        <option value="medium" <?=($b['width']??'full')==='medium'?'selected':''?>>Moyenne (500px)</option>
        <option value="small"  <?=($b['width']??'full')==='small'?'selected':''?>>Petite (300px)</option>
      </select>
    </div>
  </div>
  <div class="be-field">
    <label class="be-label">Alignement</label>
    <select name="<?=$p?>[align]" class="be-select" style="max-width:180px">
      <option value="center" <?=($b['align']??'center')==='center'?'selected':''?>>Centre</option>
      <option value="left"   <?=($b['align']??'center')==='left'?'selected':''?>>Gauche</option>
      <option value="right"  <?=($b['align']??'center')==='right'?'selected':''?>>Droite</option>
    </select>
  </div>

<?php elseif ($type === 'video'): ?>
  <div class="be-field">
    <label class="be-label">URL de la vidéo</label>
    <input type="text" name="<?=$p?>[url]" class="be-input" value="<?=Helpers::e($b['url']??'')?>" placeholder="https://www.youtube.com/watch?v=... ou https://vimeo.com/...">
    <div style="font-size:.75rem;color:#94a3b8;margin-top:.3rem">Formats acceptés : YouTube, Vimeo, Dailymotion</div>
  </div>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Titre (optionnel)</label>
      <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'')?>" placeholder="Titre de la vidéo">
    </div>
    <div class="be-field">
      <label class="be-label">Hauteur</label>
      <select name="<?=$p?>[ratio]" class="be-select">
        <option value="16-9" <?=($b['ratio']??'16-9')==='16-9'?'selected':''?>>16:9 (standard)</option>
        <option value="4-3"  <?=($b['ratio']??'16-9')==='4-3'?'selected':''?>>4:3</option>
        <option value="1-1"  <?=($b['ratio']??'16-9')==='1-1'?'selected':''?>>1:1 (carré)</option>
      </select>
    </div>
  </div>

<?php elseif ($type === 'gallery_grid'): ?>
  <div class="be-field">
    <label class="be-label">Titre (optionnel)</label>
    <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'')?>" placeholder="Galerie photos">
  </div>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Nombre de photos à afficher</label>
      <input type="number" name="<?=$p?>[count]" class="be-input" value="<?=Helpers::e($b['count']??'6')?>" min="3" max="24">
    </div>
    <div class="be-field">
      <label class="be-label">Colonnes</label>
      <select name="<?=$p?>[cols]" class="be-select">
        <option value="3" <?=($b['cols']??'3')==='3'?'selected':''?>>3 colonnes</option>
        <option value="4" <?=($b['cols']??'3')==='4'?'selected':''?>>4 colonnes</option>
      </select>
    </div>
  </div>

<?php elseif ($type === 'map'): ?>
  <div class="be-field">
    <label class="be-label">Titre (optionnel)</label>
    <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'Nous trouver')?>" placeholder="Nous trouver">
  </div>
  <div class="be-field">
    <label class="be-label">Adresse</label>
    <input type="text" name="<?=$p?>[address]" class="be-input" value="<?=Helpers::e($b['address']??'')?>" placeholder="12 rue des Sports, 69000 Lyon">
    <div style="font-size:.75rem;color:#94a3b8;margin-top:.3rem">L'adresse sera affichée sur une carte Google Maps intégrée</div>
  </div>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Hauteur de la carte</label>
      <select name="<?=$p?>[height]" class="be-select">
        <option value="300" <?=($b['height']??'400')==='300'?'selected':''?>>Petite (300px)</option>
        <option value="400" <?=($b['height']??'400')==='400'?'selected':''?>>Normale (400px)</option>
        <option value="500" <?=($b['height']??'400')==='500'?'selected':''?>>Grande (500px)</option>
      </select>
    </div>
    <div class="be-field">
      <label class="be-label">Texte complémentaire (optionnel)</label>
      <input type="text" name="<?=$p?>[extra_info]" class="be-input" value="<?=Helpers::e($b['extra_info']??'')?>" placeholder="Parking disponible · Accès PMR">
    </div>
  </div>

<?php elseif ($type === 'faq'): ?>
  <div class="be-field">
    <label class="be-label">Titre de la section</label>
    <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'Questions fréquentes')?>" placeholder="Questions fréquentes">
  </div>
  <div id="faq-items-<?=$bi?>">
    <?php $faqs = $b['faqs'] ?? [['q'=>'','a'=>'']]; ?>
    <?php foreach ($faqs as $fi => $faq): ?>
    <div class="be-ibox faq-item" style="margin-bottom:.5rem">
      <div class="be-row">
        <div class="be-field" style="margin:0;grid-column:span 2">
          <label class="be-label">Question</label>
          <input type="text" name="<?=$p?>[faqs][<?=$fi?>][q]" class="be-input" value="<?=Helpers::e($faq['q']??'')?>" placeholder="Quelle est la question ?">
        </div>
      </div>
      <div class="be-field" style="margin-top:.6rem;margin-bottom:0">
        <label class="be-label">Réponse</label>
        <textarea name="<?=$p?>[faqs][<?=$fi?>][a]" class="be-textarea" style="min-height:70px" placeholder="La réponse à cette question…"><?=Helpers::e($faq['a']??'')?></textarea>
      </div>
      <button type="button" onclick="this.closest('.faq-item').remove()" style="background:none;border:none;color:#dc2626;cursor:pointer;font-size:.78rem;margin-top:.4rem;padding:0">✕ Supprimer cette question</button>
    </div>
    <?php endforeach; ?>
  </div>
  <button type="button" onclick="beAddFaq('<?=$bi?>','<?=$p?>')" style="background:#f8fafc;border:1px dashed #e2e8f0;border-radius:8px;padding:.5rem 1rem;cursor:pointer;font-size:.82rem;color:#64748b;width:100%;margin-top:.5rem;font-family:inherit">+ Ajouter une question</button>

<?php elseif ($type === 'schedule'): ?>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Titre</label>
      <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'Prochains créneaux')?>" placeholder="Prochains créneaux">
    </div>
    <div class="be-field">
      <label class="be-label">Nombre de créneaux</label>
      <input type="number" name="<?=$p?>[count]" class="be-input" value="<?=Helpers::e($b['count']??'5')?>" min="1" max="20">
    </div>
  </div>

<?php elseif ($type === 'team'): ?>
  <div class="be-field">
    <label class="be-label">Titre de la section</label>
    <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'Notre équipe')?>" placeholder="Notre équipe">
  </div>
  <div class="be-field">
    <label class="be-label">Texte d'introduction</label>
    <textarea name="<?=$p?>[content]" class="be-textarea" style="min-height:80px" placeholder="Présentez votre équipe en quelques mots…"><?=Helpers::e($b['content']??'')?></textarea>
  </div>

<?php elseif ($type === 'newsletter_form'): ?>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Titre</label>
      <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'Restez informé')?>" placeholder="Restez informé">
    </div>
    <div class="be-field">
      <label class="be-label">Sous-titre</label>
      <input type="text" name="<?=$p?>[subtitle]" class="be-input" value="<?=Helpers::e($b['subtitle']??'Newsletter du club')?>" placeholder="Newsletter du club">
    </div>
  </div>

<?php elseif ($type === 'html'): ?>
  <div class="be-field">
    <label class="be-label">Code HTML</label>
    <textarea name="<?=$p?>[html]" class="be-textarea code" placeholder="<!-- Votre code HTML ici -->"><?=Helpers::e($b['html']??'')?></textarea>
    <div style="font-size:.75rem;color:#f59e0b;margin-top:.3rem">⚠️ Le code HTML est affiché tel quel — utilisez avec précaution.</div>
  </div>

<?php elseif ($type === 'icon_list'): ?>
  <div class="be-field">
    <label class="be-label">Titre (optionnel)</label>
    <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'')?>" placeholder="Ce que nous offrons">
  </div>
  <div class="be-field">
    <label class="be-label">Éléments (un par ligne — commencer par un emoji)</label>
    <textarea name="<?=$p?>[items]" class="be-textarea" style="min-height:130px" placeholder="✅ Premier avantage&#10;🏆 Deuxième avantage&#10;🚀 Troisième avantage"><?=Helpers::e($b['items']??'')?></textarea>
    <div style="font-size:.72rem;color:#94a3b8;margin-top:.3rem">Chaque ligne = un élément. Commencez par un emoji pour l'icône.</div>
  </div>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Colonnes</label>
      <select name="<?=$p?>[cols]" class="be-select" style="max-width:160px">
        <option value="1" <?=($b['cols']??'1')==='1'?'selected':''?>>1 colonne</option>
        <option value="2" <?=($b['cols']??'1')==='2'?'selected':''?>>2 colonnes</option>
      </select>
    </div>
  </div>

<?php elseif ($type === 'table'): ?>
  <div class="be-field">
    <label class="be-label">Titre (optionnel)</label>
    <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'')?>" placeholder="Tableau des résultats">
  </div>
  <div class="be-field">
    <label class="be-label">En-têtes des colonnes (séparées par |)</label>
    <input type="text" name="<?=$p?>[headers]" class="be-input" value="<?=Helpers::e($b['headers']??'')?>" placeholder="Nom | Catégorie | Date | Résultat">
  </div>
  <div class="be-field">
    <label class="be-label">Données (une ligne par rangée, cellules séparées par |)</label>
    <textarea name="<?=$p?>[rows]" class="be-textarea" style="min-height:120px" placeholder="Jean Dupont | Senior | 12/05 | 1er place&#10;Marie Martin | Junior | 12/05 | 2e place"><?=Helpers::e($b['rows']??'')?></textarea>
  </div>
  <div class="be-field">
    <label class="be-label" style="display:flex;align-items:center;gap:.4rem;text-transform:none;letter-spacing:0;font-weight:400;font-size:.875rem;cursor:pointer">
      <input type="checkbox" name="<?=$p?>[striped]" value="1" <?=!empty($b['striped'])?'checked':''?>> Lignes alternées (zébré)
    </label>
  </div>

<?php elseif ($type === 'highlight_box'): ?>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Emoji / Icône</label>
      <input type="text" name="<?=$p?>[icon]" class="be-input" value="<?=Helpers::e($b['icon']??'⭐')?>" placeholder="⭐" style="font-size:1.4rem;text-align:center;max-width:80px">
    </div>
    <div class="be-field">
      <label class="be-label">Titre</label>
      <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'')?>" placeholder="Information importante">
    </div>
  </div>
  <div class="be-field">
    <label class="be-label">Contenu</label>
    <textarea name="<?=$p?>[content]" class="be-textarea" style="min-height:80px" placeholder="Le texte de votre encadré…"><?=Helpers::e($b['content']??'')?></textarea>
  </div>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Couleur de fond</label>
      <input type="color" name="<?=$p?>[bg_color]" value="<?=Helpers::e($b['bg_color']??'#fef3c7')?>" style="height:40px;width:60px;border-radius:6px;border:1px solid #e2e8f0;cursor:pointer;padding:2px">
    </div>
    <div class="be-field">
      <label class="be-label">Couleur de bordure</label>
      <input type="color" name="<?=$p?>[border_color]" value="<?=Helpers::e($b['border_color']??'#f59e0b')?>" style="height:40px;width:60px;border-radius:6px;border:1px solid #e2e8f0;cursor:pointer;padding:2px">
    </div>
  </div>

<?php elseif ($type === 'stats_counter'): ?>
  <div class="be-field">
    <label class="be-label">Titre de section (optionnel)</label>
    <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'')?>" placeholder="Le club en chiffres">
  </div>
  <?php for ($s = 1; $s <= 4; $s++): ?>
  <div class="be-ibox">
    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;color:#64748b;margin-bottom:.5rem">Chiffre <?=$s?></div>
    <div class="be-row three">
      <div class="be-field" style="margin:0"><label class="be-label">Nombre</label><input type="text" name="<?=$p?>[stat<?=$s?>_num]" class="be-input" value="<?=Helpers::e($b['stat'.$s.'_num']??'')?>" placeholder="150"></div>
      <div class="be-field" style="margin:0"><label class="be-label">Unité (optionnel)</label><input type="text" name="<?=$p?>[stat<?=$s?>_unit]" class="be-input" value="<?=Helpers::e($b['stat'.$s.'_unit']??'')?>" placeholder="membres"></div>
      <div class="be-field" style="margin:0"><label class="be-label">Description</label><input type="text" name="<?=$p?>[stat<?=$s?>_label]" class="be-input" value="<?=Helpers::e($b['stat'.$s.'_label']??'')?>" placeholder="actifs"></div>
    </div>
  </div>
  <?php endfor; ?>

<?php elseif ($type === 'steps'): ?>
  <div class="be-field">
    <label class="be-label">Titre de section (optionnel)</label>
    <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'Comment nous rejoindre ?')?>" placeholder="Comment nous rejoindre ?">
  </div>
  <div id="steps-<?=$bi?>">
    <?php $steps = $b['steps'] ?? [['title'=>'','content'=>'']]; ?>
    <?php foreach ($steps as $si => $step): ?>
    <div class="be-ibox step-item" style="margin-bottom:.5rem;display:flex;gap:.75rem;align-items:flex-start">
      <div style="background:var(--color-primary);color:#fff;border-radius:50%;width:28px;height:28px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0;margin-top:.35rem"><?=$si+1?></div>
      <div style="flex:1">
        <div class="be-field" style="margin-bottom:.4rem"><label class="be-label">Titre de l'étape</label><input type="text" name="<?=$p?>[steps][<?=$si?>][title]" class="be-input" value="<?=Helpers::e($step['title']??'')?>" placeholder="Étape <?=$si+1?>…"></div>
        <div class="be-field" style="margin-bottom:0"><label class="be-label">Description</label><textarea name="<?=$p?>[steps][<?=$si?>][content]" class="be-textarea" style="min-height:60px"><?=Helpers::e($step['content']??'')?></textarea></div>
      </div>
      <button type="button" onclick="this.closest('.step-item').remove()" style="background:none;border:none;color:#cbd5e1;cursor:pointer;font-size:1rem;margin-top:.3rem" onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#cbd5e1'">✕</button>
    </div>
    <?php endforeach; ?>
  </div>
  <button type="button" onclick="beAddStep('<?=$bi?>','<?=$p?>')" class="btn-add-child" style="margin-top:.5rem">+ Ajouter une étape</button>

<?php elseif ($type === 'price_table'): ?>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Titre de section</label>
      <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'Nos tarifs')?>" placeholder="Nos tarifs">
    </div>
    <div class="be-field">
      <label class="be-label">Nombre d'offres</label>
      <select name="<?=$p?>[cols]" class="be-select" style="max-width:160px">
        <option value="2" <?=($b['cols']??'2')==='2'?'selected':''?>>2 offres</option>
        <option value="3" <?=($b['cols']??'2')==='3'?'selected':''?>>3 offres</option>
      </select>
    </div>
  </div>
  <?php $pcols = (int)($b['cols']??2); if($pcols<2||$pcols>3) $pcols=2; ?>
  <?php for ($pc = 1; $pc <= $pcols; $pc++): ?>
  <div class="be-ibox" style="position:relative">
    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;color:#64748b;margin-bottom:.5rem">Offre <?=$pc?></div>
    <div class="be-row">
      <div class="be-field"><label class="be-label">Nom de l'offre</label><input type="text" name="<?=$p?>[plan<?=$pc?>_name]" class="be-input" value="<?=Helpers::e($b['plan'.$pc.'_name']??'')?>" placeholder="Licence annuelle"></div>
      <div class="be-field"><label class="be-label">Prix</label><input type="text" name="<?=$p?>[plan<?=$pc?>_price]" class="be-input" value="<?=Helpers::e($b['plan'.$pc.'_price']??'')?>" placeholder="80€"></div>
    </div>
    <div class="be-field"><label class="be-label">Description courte</label><input type="text" name="<?=$p?>[plan<?=$pc?>_desc]" class="be-input" value="<?=Helpers::e($b['plan'.$pc.'_desc']??'')?>" placeholder="Par an · Accès complet"></div>
    <div class="be-field"><label class="be-label">Avantages (un par ligne)</label><textarea name="<?=$p?>[plan<?=$pc?>_features]" class="be-textarea" style="min-height:80px" placeholder="Accès aux créneaux&#10;Carte membre&#10;Newsletter"><?=Helpers::e($b['plan'.$pc.'_features']??'')?></textarea></div>
    <div class="be-row">
      <div class="be-field"><label class="be-label">Texte du bouton</label><input type="text" name="<?=$p?>[plan<?=$pc?>_btn]" class="be-input" value="<?=Helpers::e($b['plan'.$pc.'_btn']??'S\'inscrire')?>" placeholder="S'inscrire"></div>
      <div class="be-field"><label class="be-label"><input type="checkbox" name="<?=$p?>[plan<?=$pc?>_highlight]" value="1" <?=!empty($b['plan'.$pc.'_highlight'])?'checked':''?>> Mise en avant</label></div>
    </div>
  </div>
  <?php endfor; ?>

<?php elseif ($type === 'testimonials'): ?>
  <div class="be-field">
    <label class="be-label">Titre de section (optionnel)</label>
    <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'Ce qu\'ils disent')?>" placeholder="Ce qu'ils disent">
  </div>
  <?php $testims = $b['items'] ?? [['name'=>'','role'=>'','text'=>''],['name'=>'','role'=>'','text'=>'']]; ?>
  <?php foreach ($testims as $ti => $t): ?>
  <div class="be-ibox" style="margin-bottom:.5rem">
    <div class="be-row">
      <div class="be-field"><label class="be-label">Nom</label><input type="text" name="<?=$p?>[items][<?=$ti?>][name]" class="be-input" value="<?=Helpers::e($t['name']??'')?>" placeholder="Jean Dupont"></div>
      <div class="be-field"><label class="be-label">Rôle / Titre</label><input type="text" name="<?=$p?>[items][<?=$ti?>][role]" class="be-input" value="<?=Helpers::e($t['role']??'')?>" placeholder="Membre depuis 2021"></div>
    </div>
    <div class="be-field" style="margin-bottom:0"><label class="be-label">Témoignage</label><textarea name="<?=$p?>[items][<?=$ti?>][text]" class="be-textarea" style="min-height:70px" placeholder="Un excellent club, accueillant et bien organisé…"><?=Helpers::e($t['text']??'')?></textarea></div>
  </div>
  <?php endforeach; ?>

<?php elseif ($type === 'countdown'): ?>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Titre</label>
      <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'')?>" placeholder="Prochain événement dans…">
    </div>
    <div class="be-field">
      <label class="be-label">Date cible</label>
      <input type="datetime-local" name="<?=$p?>[target_date]" class="be-input" value="<?=Helpers::e($b['target_date']??'')?>">
    </div>
  </div>
  <div class="be-field">
    <label class="be-label">Nom de l'événement</label>
    <input type="text" name="<?=$p?>[event_name]" class="be-input" value="<?=Helpers::e($b['event_name']??'')?>" placeholder="Championnat régional 2026">
  </div>

<?php elseif ($type === 'latest_articles'): ?>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Titre de section</label>
      <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'Dernières actualités')?>" placeholder="Dernières actualités">
    </div>
    <div class="be-field">
      <label class="be-label">Nombre d'articles</label>
      <input type="number" name="<?=$p?>[count]" class="be-input" value="<?=Helpers::e($b['count']??'3')?>" min="1" max="9" style="max-width:80px">
    </div>
  </div>
  <div class="be-field">
    <label class="be-label">Disposition</label>
    <select name="<?=$p?>[layout]" class="be-select" style="max-width:220px">
      <option value="grid"   <?=($b['layout']??'grid')==='grid'?'selected':''?>>Grille</option>
      <option value="list"   <?=($b['layout']??'grid')==='list'?'selected':''?>>Liste verticale</option>
    </select>
  </div>

<?php elseif ($type === 'contact_info'): ?>
  <div class="be-field">
    <label class="be-label">Titre (optionnel)</label>
    <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'Contactez-nous')?>" placeholder="Contactez-nous">
  </div>
  <div class="be-row">
    <div class="be-field"><label class="be-label">Téléphone</label><input type="text" name="<?=$p?>[phone]" class="be-input" value="<?=Helpers::e($b['phone']??'')?>" placeholder="06 12 34 56 78"></div>
    <div class="be-field"><label class="be-label">Email</label><input type="email" name="<?=$p?>[email]" class="be-input" value="<?=Helpers::e($b['email']??'')?>" placeholder="contact@monclub.fr"></div>
  </div>
  <div class="be-row">
    <div class="be-field"><label class="be-label">Adresse</label><input type="text" name="<?=$p?>[address]" class="be-input" value="<?=Helpers::e($b['address']??'')?>" placeholder="12 rue des Sports, 69000 Lyon"></div>
    <div class="be-field"><label class="be-label">Horaires</label><input type="text" name="<?=$p?>[hours]" class="be-input" value="<?=Helpers::e($b['hours']??'')?>" placeholder="Mar & Jeu 18h-20h, Sam 10h-12h"></div>
  </div>

<?php elseif ($type === 'social_links'): ?>
  <div class="be-field">
    <label class="be-label">Titre (optionnel)</label>
    <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'')?>" placeholder="Suivez-nous">
  </div>
  <div class="be-row">
    <div class="be-field"><label class="be-label">Facebook</label><input type="url" name="<?=$p?>[facebook]" class="be-input" value="<?=Helpers::e($b['facebook']??'')?>" placeholder="https://facebook.com/…"></div>
    <div class="be-field"><label class="be-label">Instagram</label><input type="url" name="<?=$p?>[instagram]" class="be-input" value="<?=Helpers::e($b['instagram']??'')?>" placeholder="https://instagram.com/…"></div>
  </div>
  <div class="be-row">
    <div class="be-field"><label class="be-label">YouTube</label><input type="url" name="<?=$p?>[youtube]" class="be-input" value="<?=Helpers::e($b['youtube']??'')?>" placeholder="https://youtube.com/@…"></div>
    <div class="be-field"><label class="be-label">Twitter / X</label><input type="url" name="<?=$p?>[twitter]" class="be-input" value="<?=Helpers::e($b['twitter']??'')?>" placeholder="https://x.com/…"></div>
  </div>
  <div class="be-row">
    <div class="be-field"><label class="be-label">TikTok</label><input type="url" name="<?=$p?>[tiktok]" class="be-input" value="<?=Helpers::e($b['tiktok']??'')?>" placeholder="https://tiktok.com/@…"></div>
    <div class="be-field"><label class="be-label">LinkedIn</label><input type="url" name="<?=$p?>[linkedin]" class="be-input" value="<?=Helpers::e($b['linkedin']??'')?>" placeholder="https://linkedin.com/company/…"></div>
  </div>
  <div class="be-row">
    <div class="be-field"><label class="be-label">Snapchat</label><input type="url" name="<?=$p?>[snapchat]" class="be-input" value="<?=Helpers::e($b['snapchat']??'')?>" placeholder="https://snapchat.com/add/…"></div>
    <div class="be-field"><label class="be-label">Discord</label><input type="url" name="<?=$p?>[discord]" class="be-input" value="<?=Helpers::e($b['discord']??'')?>" placeholder="https://discord.gg/…"></div>
  </div>
  <div class="be-row">
    <div class="be-field"><label class="be-label">Twitch</label><input type="url" name="<?=$p?>[twitch]" class="be-input" value="<?=Helpers::e($b['twitch']??'')?>" placeholder="https://twitch.tv/…"></div>
    <div class="be-field"><label class="be-label">WhatsApp (lien groupe)</label><input type="url" name="<?=$p?>[whatsapp]" class="be-input" value="<?=Helpers::e($b['whatsapp']??'')?>" placeholder="https://chat.whatsapp.com/…"></div>
  </div>
  <!-- Réseaux personnalisés — jusqu'à 3 -->
  <div style="font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b;margin:.875rem 0 .4rem">Autre(s) réseau / lien libre</div>
  <?php for ($sn = 1; $sn <= 3; $sn++): ?>
  <div class="be-ibox" style="margin-bottom:.4rem">
    <div class="be-row">
      <div class="be-field" style="margin:0">
        <label class="be-label">Nom du réseau <?=$sn?></label>
        <input type="text" name="<?=$p?>[custom<?=$sn?>_label]" class="be-input" value="<?=Helpers::e($b['custom'.$sn.'_label']??'')?>" placeholder="ex: Strava, BeReal, Thread…">
      </div>
      <div class="be-field" style="margin:0">
        <label class="be-label">URL</label>
        <input type="url" name="<?=$p?>[custom<?=$sn?>_url]" class="be-input" value="<?=Helpers::e($b['custom'.$sn.'_url']??'')?>" placeholder="https://…">
      </div>
    </div>
  </div>
  <?php endfor; ?>

<?php elseif ($type === 'accordion'): ?>
  <div class="be-field">
    <label class="be-label">Titre de section (optionnel)</label>
    <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'')?>" placeholder="Informations">
  </div>
  <div id="accordion-items-<?=$bi?>">
    <?php $accItems = $b['items'] ?? [['title'=>'','content'=>'']]; ?>
    <?php foreach ($accItems as $ai => $acc): ?>
    <div class="be-ibox acc-item" style="margin-bottom:.4rem">
      <div class="be-field"><label class="be-label">Titre de la section</label><input type="text" name="<?=$p?>[items][<?=$ai?>][title]" class="be-input" value="<?=Helpers::e($acc['title']??'')?>" placeholder="Section <?=$ai+1?>"></div>
      <div class="be-field" style="margin-bottom:.3rem"><label class="be-label">Contenu</label><textarea name="<?=$p?>[items][<?=$ai?>][content]" class="be-textarea" style="min-height:80px"><?=Helpers::e($acc['content']??'')?></textarea></div>
      <button type="button" onclick="this.closest('.acc-item').remove()" style="background:none;border:none;color:#dc2626;cursor:pointer;font-size:.78rem;padding:0">✕ Supprimer</button>
    </div>
    <?php endforeach; ?>
  </div>
  <button type="button" onclick="beAddAccordion('<?=$bi?>','<?=$p?>')" class="btn-add-child" style="margin-top:.35rem">+ Ajouter une section</button>

<?php elseif ($type === 'banner_image'): ?>
  <div class="be-row">
    <div class="be-field"><label class="be-label">Image (upload)</label><input type="file" name="<?=$p?>[image_upload]" accept="image/*"><?php if(!empty($b['image_src'])): ?><img src="<?=asset($b['image_src'])?>" style="height:48px;border-radius:5px;margin-top:.35rem"><input type="hidden" name="<?=$p?>[image_src]" value="<?=Helpers::e($b['image_src'])?>"> <?php endif; ?></div>
    <div class="be-field"><label class="be-label">Ou URL externe</label><input type="url" name="<?=$p?>[image_url]" class="be-input" value="<?=Helpers::e($b['image_url']??'')?>" placeholder="https://…"></div>
  </div>
  <div class="be-row">
    <div class="be-field"><label class="be-label">Titre superposé</label><input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'')?>" placeholder="Bienvenue dans notre club"></div>
    <div class="be-field"><label class="be-label">Sous-titre</label><input type="text" name="<?=$p?>[subtitle]" class="be-input" value="<?=Helpers::e($b['subtitle']??'')?>" placeholder="Une communauté passionnée"></div>
  </div>
  <div class="be-row">
    <div class="be-field"><label class="be-label">Hauteur</label><select name="<?=$p?>[height]" class="be-select"><option value="300" <?=($b['height']??'400')==='300'?'selected':''?>>300px</option><option value="400" <?=($b['height']??'400')==='400'?'selected':''?>>400px</option><option value="500" <?=($b['height']??'400')==='500'?'selected':''?>>500px</option><option value="600" <?=($b['height']??'400')==='600'?'selected':''?>>600px</option></select></div>
    <div class="be-field"><label class="be-label">Opacité overlay</label><input type="range" name="<?=$p?>[overlay]" min="0" max="80" step="10" value="<?=Helpers::e($b['overlay']??'40')?>" style="width:100%;margin-top:.75rem"></div>
  </div>

<?php elseif ($type === 'partners'): ?>
  <div class="be-row">
    <div class="be-field">
      <label class="be-label">Titre (optionnel)</label>
      <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'Nos partenaires')?>" placeholder="Nos partenaires">
    </div>
    <div class="be-field">
      <label class="be-label">Taille des logos</label>
      <select name="<?=$p?>[logo_size]" class="be-select">
        <option value="sm"  <?=($b['logo_size']??'md')==='sm'?'selected':''?>>Petite (48px)</option>
        <option value="md"  <?=($b['logo_size']??'md')==='md'?'selected':''?>>Normale (80px)</option>
        <option value="lg"  <?=($b['logo_size']??'md')==='lg'?'selected':''?>>Grande (120px)</option>
        <option value="xl"  <?=($b['logo_size']??'md')==='xl'?'selected':''?>>Très grande (160px)</option>
      </select>
    </div>
  </div>
  <?php for ($pp = 1; $pp <= 6; $pp++): ?>
  <div class="be-ibox" style="margin-bottom:.5rem">
    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;color:#94a3b8;margin-bottom:.5rem">Partenaire <?=$pp?></div>
    <div class="be-row">
      <div class="be-field" style="margin:0">
        <label class="be-label">Nom</label>
        <input type="text" name="<?=$p?>[partner<?=$pp?>_name]" class="be-input" value="<?=Helpers::e($b['partner'.$pp.'_name']??'')?>" placeholder="Nom du partenaire">
      </div>
      <div class="be-field" style="margin:0">
        <label class="be-label">Site web</label>
        <input type="url" name="<?=$p?>[partner<?=$pp?>_url]" class="be-input" value="<?=Helpers::e($b['partner'.$pp.'_url']??'')?>" placeholder="https://…">
      </div>
    </div>
    <div class="be-row" style="margin-top:.5rem">
      <div class="be-field" style="margin:0">
        <label class="be-label">Logo (upload image)</label>
        <input type="file" name="<?=$p?>[partner<?=$pp?>_logo_file]" accept="image/*">
        <?php if (!empty($b['partner'.$pp.'_logo_src'])): ?>
        <div style="margin-top:.35rem;display:flex;align-items:center;gap:.5rem">
          <img src="<?=asset($b['partner'.$pp.'_logo_src'])?>" style="height:40px;border-radius:4px;object-fit:contain;background:#f8fafc;padding:4px;border:1px solid #e2e8f0">
          <span style="font-size:.72rem;color:#64748b">Logo actuel</span>
        </div>
        <input type="hidden" name="<?=$p?>[partner<?=$pp?>_logo_src]" value="<?=Helpers::e($b['partner'.$pp.'_logo_src'])?>">
        <?php endif; ?>
      </div>
      <div class="be-field" style="margin:0">
        <label class="be-label">Ou URL du logo externe</label>
        <input type="url" name="<?=$p?>[partner<?=$pp?>_logo_url]" class="be-input" value="<?=Helpers::e($b['partner'.$pp.'_logo_url']??'')?>" placeholder="https://example.com/logo.png">
      </div>
    </div>
  </div>
  <?php endfor; ?>

<?php elseif ($type === 'embed'): ?>
  <div class="be-field">
    <label class="be-label">Titre (optionnel)</label>
    <input type="text" name="<?=$p?>[title]" class="be-input" value="<?=Helpers::e($b['title']??'')?>" placeholder="Formulaire de contact">
  </div>
  <div class="be-field">
    <label class="be-label">Code d'intégration (iframe ou script)</label>
    <textarea name="<?=$p?>[html]" class="be-textarea code" placeholder="<iframe src=&quot;https://…&quot;></iframe>"><?=Helpers::e($b['html']??'')?></textarea>
  </div>
  <div class="be-field">
    <label class="be-label">Hauteur de l'intégration</label>
    <input type="number" name="<?=$p?>[height]" class="be-input" value="<?=Helpers::e($b['height']??'400')?>" min="100" max="1200" style="max-width:100px"> px
  </div>
<?php elseif($type==='weather'): ?>
  <div class="be-field">
    <label class="be-label">🌤 Ville</label>
    <input type="text" name="<?=$p?>[city]" class="be-input" value="<?=Helpers::e($b['city']??'')?>" placeholder="Ex: Paris, Lyon, Marseille…">
    <small style="color:#64748b;font-size:.75rem">La météo est récupérée via wttr.in (gratuit, RGPD friendly, pas de clé API)</small>
  </div>
  <div class="be-field">
    <label class="be-label">Style d'affichage</label>
    <select name="<?=$p?>[weather_style]" class="be-input">
      <option value="compact" <?=($b['weather_style']??'compact')==='compact'?'selected':''?>>Compact (une ligne)</option>
      <option value="card" <?=($b['weather_style']??'')==='card'?'selected':''?>>Carte météo</option>
    </select>
  </div>
<?php else: ?>
  <div class="be-field">
    <label class="be-label">Contenu</label>
    <textarea name="<?=$p?>[content]" class="be-textarea" placeholder="Contenu du bloc…"><?=Helpers::e($b['content']??'')?></textarea>
  </div>
<?php endif; ?>
