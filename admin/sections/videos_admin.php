<?php
Auth::require('admin');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Auth::verifyCsrf()) {
    adminFlash('error','CSRF'); Helpers::redirect(u('/admin/videos'));
}

// Migrations
try {
    Database::run("CREATE TABLE IF NOT EXISTS cc_video_folders (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(200) NOT NULL, slug VARCHAR(200) NOT NULL, description TEXT, require_login TINYINT(1) DEFAULT 0, `order` INT DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY slug (slug))");
    Database::run("CREATE TABLE IF NOT EXISTS cc_videos (id INT AUTO_INCREMENT PRIMARY KEY, folder_id INT NOT NULL, title VARCHAR(200) NOT NULL, description TEXT, filename VARCHAR(255) DEFAULT NULL, embed_url VARCHAR(500) DEFAULT NULL, thumbnail VARCHAR(255) DEFAULT NULL, allow_download TINYINT(1) DEFAULT 0, require_login TINYINT(1) DEFAULT 0, filesize BIGINT DEFAULT NULL, `order` INT DEFAULT 0, created_by INT NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
} catch(Exception $e) {}

// ── Dossier ───────────────────────────────────────────────────
if (isset($_POST['save_folder'])) {
    $id   = (int)($_POST['folder_id'] ?? 0);
    $name = Helpers::sanitize($_POST['name'] ?? '');
    $desc = Helpers::sanitize($_POST['description'] ?? '');
    $rl   = (int)($_POST['require_login'] ?? 0);
    if ($id) {
        Database::run("UPDATE cc_video_folders SET name=?,description=?,require_login=? WHERE id=?", [$name,$desc,$rl,$id]);
    } else {
        $slug = Helpers::uniqueSlug($name, 'cc_video_folders');
        Database::run("INSERT INTO cc_video_folders (name,slug,description,require_login) VALUES (?,?,?,?)", [$name,$slug,$desc,$rl]);
    }
    adminFlash('success','Dossier sauvegardé.');
    Helpers::redirect(u('/admin/videos'));
}
if (isset($_POST['delete_folder'])) {
    $id = (int)$_POST['folder_id'];
    // Supprimer les fichiers vidéo
    $vids = Database::all("SELECT filename FROM cc_videos WHERE folder_id=? AND filename IS NOT NULL", [$id]);
    foreach($vids as $v) @unlink(CC_ROOT.'/uploads/videos/'.$v['filename']);
    Database::run("DELETE FROM cc_videos WHERE folder_id=?", [$id]);
    Database::run("DELETE FROM cc_video_folders WHERE id=?", [$id]);
    adminFlash('success','Dossier et vidéos supprimés.');
    Helpers::redirect(u('/admin/videos'));
}

// ── Ajouter vidéo ─────────────────────────────────────────────
if (isset($_POST['save_video'])) {
    $vid  = (int)($_POST['video_id'] ?? 0);
    $fid  = (int)$_POST['folder_id'];
    $title= Helpers::sanitize($_POST['title'] ?? '');
    $desc = Helpers::sanitize($_POST['description'] ?? '');
    $embed= trim($_POST['embed_url'] ?? '');
    $dl   = (int)($_POST['allow_download'] ?? 0);
    $rl   = (int)($_POST['require_login'] ?? 0);

    $filename = null; $filesize = null;
    if (!empty($_FILES['video_file']['name']) && $_FILES['video_file']['error']===0) {
        $allowed = ['mp4','webm','ogg','mov','avi','mkv'];
        $ext     = strtolower(pathinfo($_FILES['video_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            @mkdir(CC_ROOT.'/uploads/videos', 0755, true);
            $filename = uniqid('vid_').'.'.$ext;
            move_uploaded_file($_FILES['video_file']['tmp_name'], CC_ROOT.'/uploads/videos/'.$filename);
            $filesize = $_FILES['video_file']['size'];
        }
    }

    // Miniature
    $thumb = null;
    if (!empty($_FILES['thumbnail']['name']) && $_FILES['thumbnail']['error']===0) {
        $up = Helpers::uploadImage($_FILES['thumbnail'], CC_ROOT.'/uploads/videos/thumbs', 5);
        if ($up['success']) $thumb = $up['filename'];
    }

    if ($vid) {
        $existing = Database::one("SELECT filename,thumbnail FROM cc_videos WHERE id=?", [$vid]);
        if (!$filename && $existing) $filename = $existing['filename'];
        if (!$thumb    && $existing) $thumb    = $existing['thumbnail'];
        Database::run("UPDATE cc_videos SET folder_id=?,title=?,description=?,embed_url=?,allow_download=?,require_login=?,filename=?,thumbnail=?,filesize=? WHERE id=?",
            [$fid,$title,$desc,$embed?:null,$dl,$rl,$filename,$thumb,$filesize??null,$vid]);
        adminFlash('success','Vidéo mise à jour.');
    } else {
        Database::run("INSERT INTO cc_videos (folder_id,title,description,embed_url,allow_download,require_login,filename,thumbnail,filesize,created_by) VALUES (?,?,?,?,?,?,?,?,?,?)",
            [$fid,$title,$desc,$embed?:null,$dl,$rl,$filename,$thumb,$filesize,Auth::id()]);
        adminFlash('success','Vidéo ajoutée.');
    }
    Helpers::redirect(u('/admin/videos?folder='.$fid));
}
if (isset($_POST['delete_video'])) {
    $vid = (int)$_POST['video_id'];
    $v   = Database::one("SELECT filename,thumbnail,folder_id FROM cc_videos WHERE id=?", [$vid]);
    if ($v) {
        if ($v['filename']) @unlink(CC_ROOT.'/uploads/videos/'.$v['filename']);
        if ($v['thumbnail']) @unlink(CC_ROOT.'/uploads/videos/thumbs/'.$v['thumbnail']);
        Database::run("DELETE FROM cc_videos WHERE id=?", [$vid]);
    }
    adminFlash('success','Vidéo supprimée.');
    Helpers::redirect(u('/admin/videos?folder='.($v['folder_id']??'')));
}

$folderId = (int)($_GET['folder'] ?? 0);
$editVid  = isset($_GET['edit']) ? Database::one("SELECT * FROM cc_videos WHERE id=?", [(int)$_GET['edit']]) : null;
$folders  = Database::all("SELECT * FROM cc_video_folders ORDER BY `order`,name");
$videos   = $folderId ? Database::all("SELECT v.*,f.name AS folder_name FROM cc_videos v JOIN cc_video_folders f ON f.id=v.folder_id WHERE v.folder_id=? ORDER BY v.`order`,v.created_at DESC", [$folderId]) : [];
$curFolder= $folderId ? Database::one("SELECT * FROM cc_video_folders WHERE id=?", [$folderId]) : null;

$pageTitle = '🎬 Vidéos';
ob_start(); ?>

<div class="page-head">
  <h1>🎬 Médiathèque vidéos</h1>
  <a href="<?=u('/videos')?>" class="btn btn-ghost btn-sm" target="_blank">Voir la page →</a>
</div>

<div style="display:grid;grid-template-columns:260px 1fr;gap:1.25rem;align-items:start">

  <!-- Sidebar dossiers -->
  <div>
    <div class="ac">
      <div class="ac-header"><h2>📁 Dossiers</h2></div>
      <div class="ac-body" style="padding:.5rem">
        <a href="<?=u('/admin/videos')?>" style="display:block;padding:.5rem .75rem;border-radius:8px;text-decoration:none;font-size:.875rem;font-weight:<?=$folderId?'400':'700'?>;background:<?=$folderId?'transparent':'var(--color-primary)'?>;color:<?=$folderId?'#475569':'#fff'?>;margin-bottom:.25rem">
          📁 Tous les dossiers
        </a>
        <?php foreach($folders as $f): ?>
        <div style="display:flex;align-items:center;gap:.25rem;margin-bottom:.25rem">
          <a href="<?=u('/admin/videos?folder='.$f['id'])?>" style="flex:1;display:block;padding:.5rem .75rem;border-radius:8px;text-decoration:none;font-size:.875rem;font-weight:<?=$folderId===$f['id']?'700':'400'?>;background:<?=$folderId===$f['id']?'var(--color-primary)':'#f8fafc'?>;color:<?=$folderId===$f['id']?'#fff':'#475569'?>">
            🎬 <?=Helpers::e($f['name'])?>
            <span style="opacity:.6;font-size:.75rem">(<?=Database::scalar("SELECT COUNT(*) FROM cc_videos WHERE folder_id=?",[$f['id']])?> vidéos)</span>
          </a>
          <button onclick="document.getElementById('edit-folder-<?=$f['id']?>').classList.toggle('hidden')" style="background:none;border:none;cursor:pointer;padding:.3rem;color:#64748b">✏️</button>
        </div>
        <form id="edit-folder-<?=$f['id']?>" class="hidden" method="post" style="background:#f8fafc;border-radius:8px;padding:.625rem;margin-bottom:.5rem">
          <?=Auth::csrfField()?>
          <input type="hidden" name="folder_id" value="<?=$f['id']?>">
          <input type="text" name="name" class="bi" value="<?=Helpers::e($f['name'])?>" placeholder="Nom" style="margin-bottom:.35rem">
          <input type="text" name="description" class="bi" value="<?=Helpers::e($f['description']??'')?>" placeholder="Description" style="margin-bottom:.35rem">
          <label style="display:flex;align-items:center;gap:.4rem;font-size:.78rem;margin-bottom:.35rem">
            <input type="checkbox" name="require_login" value="1" <?=$f['require_login']?'checked':''?>> Membres uniquement
          </label>
          <div style="display:flex;gap:.35rem">
            <button type="submit" name="save_folder" class="btn btn-primary btn-sm">💾</button>
            <button type="submit" name="delete_folder" class="btn btn-sm" style="background:#fee2e2;color:#dc2626" onclick="return confirm('Supprimer ce dossier et toutes ses vidéos ?')">🗑</button>
          </div>
        </form>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Nouveau dossier -->
    <div class="ac" style="margin-top:1rem">
      <div class="ac-header"><h2>+ Nouveau dossier</h2></div>
      <div class="ac-body">
        <form method="post">
          <?=Auth::csrfField()?>
          <input type="text" name="name" class="bi" placeholder="Nom du dossier" required style="margin-bottom:.5rem">
          <input type="text" name="description" class="bi" placeholder="Description (optionnel)" style="margin-bottom:.5rem">
          <label style="display:flex;align-items:center;gap:.4rem;font-size:.82rem;margin-bottom:.75rem">
            <input type="checkbox" name="require_login" value="1"> Réservé aux membres
          </label>
          <button type="submit" name="save_folder" class="btn btn-primary btn-sm">Créer</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Zone principale -->
  <div>
    <?php if(!$folderId): ?>
    <!-- Vue globale des dossiers -->
    <?php if(empty($folders)): ?>
    <div class="ac"><div class="ac-body" style="text-align:center;padding:2.5rem;color:#94a3b8">
      📁 Créez un dossier pour commencer à ajouter des vidéos.
    </div></div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1rem">
      <?php foreach($folders as $f): $count=Database::scalar("SELECT COUNT(*) FROM cc_videos WHERE folder_id=?",[$f['id']]); ?>
      <a href="<?=u('/admin/videos?folder='.$f['id'])?>" style="display:block;text-decoration:none;border:1.5px solid #e2e8f0;border-radius:12px;overflow:hidden;background:#fff;transition:border-color .15s" onmouseover="this.style.borderColor='var(--color-primary)'" onmouseout="this.style.borderColor='#e2e8f0'">
        <div style="aspect-ratio:16/9;background:linear-gradient(135deg,#1e293b,#334155);display:flex;align-items:center;justify-content:center;font-size:2.5rem">🎬</div>
        <div style="padding:.75rem">
          <div style="font-weight:700;font-size:.9rem;color:#1e293b"><?=Helpers::e($f['name'])?></div>
          <div style="font-size:.75rem;color:#94a3b8;margin-top:.2rem"><?=$count?> vidéo<?=$count!=1?'s':''?></div>
          <?php if($f['require_login']): ?><span style="font-size:.68rem;background:#eff6ff;color:#2563eb;padding:.1rem .4rem;border-radius:4px">🔒 Membres</span><?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- Contenu d'un dossier -->
    <div class="ac" style="margin-bottom:1.25rem">
      <div class="ac-header">
        <h2>🎬 <?=Helpers::e($curFolder['name'])?></h2>
        <a href="<?=u('/admin/videos')?>" class="btn btn-ghost btn-sm">← Retour</a>
      </div>
    </div>

    <!-- Formulaire ajout/édition vidéo -->
    <div class="ac" style="margin-bottom:1.25rem">
      <div class="ac-header"><h2><?=$editVid?'✏️ Modifier la vidéo':'+ Ajouter une vidéo'?></h2></div>
      <div class="ac-body">
        <form method="post" enctype="multipart/form-data">
          <?=Auth::csrfField()?>
          <input type="hidden" name="folder_id" value="<?=$folderId?>">
          <?php if($editVid): ?><input type="hidden" name="video_id" value="<?=$editVid['id']?>"><?php endif; ?>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:.75rem">
            <div>
              <label style="font-size:.78rem;font-weight:600;color:#64748b;display:block;margin-bottom:.3rem">Titre *</label>
              <input type="text" name="title" class="bi" value="<?=Helpers::e($editVid['title']??'')?>" required>
            </div>
            <div>
              <label style="font-size:.78rem;font-weight:600;color:#64748b;display:block;margin-bottom:.3rem">Description</label>
              <input type="text" name="description" class="bi" value="<?=Helpers::e($editVid['description']??'')?>">
            </div>
          </div>
          <div style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;padding:.875rem;margin-bottom:.75rem">
            <div style="font-weight:600;font-size:.85rem;margin-bottom:.625rem">Source vidéo (choisir une option)</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
              <div>
                <label style="font-size:.78rem;font-weight:600;color:#64748b;display:block;margin-bottom:.3rem">📁 Fichier local (mp4, webm…)</label>
                <input type="file" name="video_file" class="bi" accept=".mp4,.webm,.ogg,.mov" style="padding:.35rem">
                <?php if($editVid&&$editVid['filename']): ?>
                <div style="font-size:.72rem;color:#16a34a;margin-top:.25rem">✓ Fichier actuel : <?=Helpers::e($editVid['filename'])?></div>
                <?php endif; ?>
              </div>
              <div>
                <label style="font-size:.78rem;font-weight:600;color:#64748b;display:block;margin-bottom:.3rem">🔗 URL YouTube / Vimeo (embed)</label>
                <input type="url" name="embed_url" class="bi" value="<?=Helpers::e($editVid['embed_url']??'')?>" placeholder="https://www.youtube.com/embed/...">
                <div style="font-size:.72rem;color:#64748b;margin-top:.25rem">Sur YouTube : Partager → Intégrer → copier l'URL embed</div>
              </div>
            </div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.75rem;margin-bottom:.75rem">
            <div>
              <label style="font-size:.78rem;font-weight:600;color:#64748b;display:block;margin-bottom:.3rem">🖼 Miniature (optionnel)</label>
              <input type="file" name="thumbnail" class="bi" accept="image/*" style="padding:.35rem">
              <?php if($editVid&&$editVid['thumbnail']): ?>
              <img src="<?=u('/uploads/videos/thumbs/'.$editVid['thumbnail'])?>" style="width:100%;height:60px;object-fit:cover;border-radius:6px;margin-top:.35rem">
              <?php endif; ?>
            </div>
            <div style="display:flex;flex-direction:column;gap:.5rem;justify-content:center">
              <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.875rem">
                <input type="checkbox" name="allow_download" value="1" <?=($editVid['allow_download']??0)?'checked':''?> style="width:18px;height:18px;accent-color:var(--color-primary)">
                <span><strong>⬇️ Téléchargement autorisé</strong><br><span style="font-size:.75rem;color:#64748b">Les visiteurs peuvent télécharger</span></span>
              </label>
              <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;font-size:.875rem">
                <input type="checkbox" name="require_login" value="1" <?=($editVid['require_login']??0)?'checked':''?> style="width:18px;height:18px;accent-color:var(--color-primary)">
                <span><strong>🔒 Membres uniquement</strong><br><span style="font-size:.75rem;color:#64748b">Connexion requise</span></span>
              </label>
            </div>
          </div>
          <div style="display:flex;gap:.5rem">
            <button type="submit" name="save_video" class="btn btn-primary">💾 <?=$editVid?'Mettre à jour':'Ajouter la vidéo'?></button>
            <?php if($editVid): ?><a href="<?=u('/admin/videos?folder='.$folderId)?>" class="btn btn-ghost">Annuler</a><?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <!-- Liste des vidéos -->
    <?php if(empty($videos)): ?>
    <div class="ac"><div class="ac-body" style="text-align:center;padding:2rem;color:#94a3b8">Aucune vidéo dans ce dossier.</div></div>
    <?php else: ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem">
      <?php foreach($videos as $v): ?>
      <div style="border:1.5px solid #e2e8f0;border-radius:12px;overflow:hidden;background:#fff">
        <!-- Miniature ou player préview -->
        <div style="aspect-ratio:16/9;background:#1e293b;position:relative;overflow:hidden">
          <?php if($v['thumbnail']): ?>
          <img src="<?=u('/uploads/videos/thumbs/'.$v['thumbnail'])?>" style="width:100%;height:100%;object-fit:cover">
          <?php elseif($v['embed_url']): ?>
          <div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:2rem">▶️</div>
          <?php elseif($v['filename']): ?>
          <div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:2rem">🎬</div>
          <?php endif; ?>
          <!-- Badges -->
          <div style="position:absolute;top:.4rem;right:.4rem;display:flex;gap:.3rem">
            <?php if($v['allow_download']): ?><span style="background:#16a34a;color:#fff;font-size:.65rem;padding:.15rem .4rem;border-radius:4px">⬇️ DL</span><?php endif; ?>
            <?php if($v['require_login']): ?><span style="background:#2563eb;color:#fff;font-size:.65rem;padding:.15rem .4rem;border-radius:4px">🔒</span><?php endif; ?>
          </div>
        </div>
        <div style="padding:.75rem">
          <div style="font-weight:700;font-size:.875rem;margin-bottom:.2rem"><?=Helpers::e($v['title'])?></div>
          <?php if($v['description']): ?><div style="font-size:.75rem;color:#64748b;margin-bottom:.5rem"><?=Helpers::e($v['description'])?></div><?php endif; ?>
          <?php if($v['filesize']): ?><div style="font-size:.72rem;color:#94a3b8"><?=round($v['filesize']/1048576,1)?> Mo</div><?php endif; ?>
          <div style="display:flex;gap:.35rem;margin-top:.625rem">
            <a href="<?=u('/admin/videos?folder='.$folderId.'&edit='.$v['id'])?>" class="btn btn-ghost btn-sm">✏️</a>
            <form method="post" onsubmit="return confirm('Supprimer cette vidéo ?')" style="margin:0">
              <?=Auth::csrfField()?>
              <input type="hidden" name="video_id" value="<?=$v['id']?>">
              <button type="submit" name="delete_video" class="btn btn-sm" style="background:#fee2e2;color:#dc2626;border:1.5px solid #fecaca">🗑</button>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<script>
document.querySelectorAll('.hidden').forEach(function(el){ el.style.display='none'; });
document.querySelectorAll('[id^="edit-folder-"]').forEach(function(el){ el.classList.remove('hidden'); el.style.display='none'; });
</script>

<?php
$content = ob_get_clean();
include CC_ROOT . '/admin/layout.php';
