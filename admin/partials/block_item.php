<?php
/**
 * Rendu d'un bloc individuel
 * Variables : $bi (index), $block (données), $type, $def, $fieldPrefix, $blockCatalog
 */
$p = $fieldPrefix . '[' . $bi . ']';
?>
<div class="be-block" data-bi="<?=$bi?>" id="be-block-<?=$fieldPrefix?>-<?=$bi?>">
  <div class="be-head" onclick="beToggle(this)">
    <span class="be-handle" onclick="event.stopPropagation()">⠿</span>
    <input type="hidden" name="<?=$p?>[type]" value="<?=Helpers::e($type)?>">
    <span class="be-badge" style="background:<?=$def['color']?>"><?=$def['icon']?> <?=$def['label']?></span>
    <span class="be-preview"><?=Helpers::e(Helpers::excerpt($block['title']??$block['content']??$block['html']??'Bloc sans titre',50))?></span>
    <div class="be-actions" onclick="event.stopPropagation()">
      <button type="button" class="be-btn" onclick="beMove(this,-1)" title="Monter">↑</button>
      <button type="button" class="be-btn" onclick="beMove(this,1)"  title="Descendre">↓</button>
      <button type="button" class="be-btn del" onclick="beDelete(this)" title="Supprimer">✕</button>
    </div>
  </div>
  <div class="be-body">
    <?php include __DIR__ . '/block_fields.php'; ?>
  </div>
</div>
