<?php
/**
 * ClubCMS — Journal d'activité & Sauvegardes
 */
Auth::require('admin');
if (!class_exists('ActivityLog')) require_once (defined('CC_ROOT') ? CC_ROOT : dirname(__DIR__,2)) . '/core/ActivityLog.php';
if (!class_exists('Invoice'))     require_once (defined('CC_ROOT') ? CC_ROOT : dirname(__DIR__,2)) . '/core/Invoice.php';


$tab = $_GET['tab'] ?? 'logs';

// ── Sauvegarde BDD ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['backup_now']) && Auth::verifyCsrf()) {
    try {
        require_once CC_ROOT . '/config/config.php';
        $db  = DB_NAME;
        $host= DB_HOST; $user= DB_USER; $pass= DB_PASS;
        $dir = CC_ROOT . '/uploads/backups/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $dir . $filename;

        // Générer le dump SQL via PHP (sans mysqldump pour compatibilité hébergeur)
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "-- ClubCMS Backup — " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Database: $db\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $createStmt = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $createStmt['Create Table'] . ";\n\n";
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $cols = '`' . implode('`,`', array_keys($rows[0])) . '`';
                foreach ($rows as $row) {
                    $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote($v), $row);
                    $sql .= "INSERT INTO `$table` ($cols) VALUES (" . implode(',', $vals) . ");\n";
                }
                $sql .= "\n";
            }
        }
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($filepath, $sql);

        // Enregistrer dans cc_backups
        try {
            Database::run("CREATE TABLE IF NOT EXISTS cc_backups (id INT AUTO_INCREMENT PRIMARY KEY, filename VARCHAR(255) NOT NULL, size INT DEFAULT 0, type VARCHAR(20) DEFAULT 'manual', created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
            Database::run("INSERT INTO cc_backups (filename,size,type) VALUES (?,?,?)", [$filename, strlen($sql), 'manual']);
        } catch(Exception $e) {}

        ActivityLog::log('backup_created', 'backup', 0, ['filename' => $filename, 'size' => strlen($sql)]);
        adminFlash('success', '✅ Sauvegarde créée : ' . $filename . ' (' . round(strlen($sql)/1024, 1) . ' Ko)');
    } catch(Exception $e) {
        adminFlash('error', 'Erreur : ' . $e->getMessage());
    }
    Helpers::redirect(u('/admin/logs?tab=backups'));
}

// Télécharger une sauvegarde
if (isset($_GET['download']) && Auth::isAdmin()) {
    $fname = basename($_GET['download']);
    $fpath = CC_ROOT . '/uploads/backups/' . $fname;
    if (file_exists($fpath) && preg_match('/^backup_[\d_-]+\.sql$/', $fname)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        readfile($fpath); exit;
    }
}

// Supprimer une sauvegarde
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_backup']) && Auth::verifyCsrf()) {
    $fname = basename($_POST['filename'] ?? '');
    $fpath = CC_ROOT . '/uploads/backups/' . $fname;
    if (file_exists($fpath)) @unlink($fpath);
    try { Database::run("DELETE FROM cc_backups WHERE filename=?", [$fname]); } catch(Exception $e) {}
    adminFlash('success', 'Sauvegarde supprimée.');
    Helpers::redirect(u('/admin/logs?tab=backups'));
}

$logs = class_exists('ActivityLog') ? ActivityLog::recent(100) : [];
$backups = [];
$backupDir = CC_ROOT . '/uploads/backups/';
if (is_dir($backupDir)) {
    foreach (array_reverse(glob($backupDir . 'backup_*.sql')) as $f) {
        $backups[] = ['filename'=>basename($f),'size'=>filesize($f),'date'=>filemtime($f)];
    }
}

$pageTitle = '📋 Logs & Sauvegardes';
ob_start();
?>
<div class="page-head"><h1>📋 Logs & Sauvegardes</h1></div>

<div style="display:flex;gap:.35rem;margin-bottom:1.5rem">
  <a href="<?=u('/admin/logs')?>"             class="btn <?=$tab!=='backups'?'btn-primary':'btn-ghost'?>">📋 Journal d'activité</a>
  <a href="<?=u('/admin/logs?tab=backups')?>" class="btn <?=$tab==='backups'?'btn-primary':'btn-ghost'?>">💾 Sauvegardes</a>
</div>

<?php if($tab==='backups'): ?>
<!-- Sauvegardes -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.75rem">
  <div>
    <strong>Sauvegardes de la base de données</strong>
    <div style="font-size:.78rem;color:#64748b;margin-top:.2rem">Les fichiers sont stockés dans <code>uploads/backups/</code></div>
  </div>
  <form method="post">
    <?=Auth::csrfField()?>
    <button type="submit" name="backup_now" class="btn btn-primary" onclick="return confirm('Créer une sauvegarde maintenant ?')">💾 Sauvegarder maintenant</button>
  </form>
</div>
<?php if(empty($backups)): ?>
<div style="background:#f8fafc;border-radius:12px;padding:2rem;text-align:center;color:#94a3b8">Aucune sauvegarde.</div>
<?php else: ?>
<div class="at-wrap"><table class="at">
  <thead><tr><th>Fichier</th><th>Taille</th><th>Date</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach($backups as $b): ?>
  <tr>
    <td><code style="font-size:.78rem"><?=Helpers::e($b['filename'])?></code></td>
    <td><?=round($b['size']/1024,1)?> Ko</td>
    <td><?=date('d/m/Y H:i',$b['date'])?></td>
    <td style="display:flex;gap:.35rem">
      <a href="<?=u('/admin/logs?tab=backups')?>&download=<?=urlencode($b['filename'])?>" class="btn btn-primary btn-sm">⬇️ Télécharger</a>
      <form method="post" onsubmit="return confirm('Supprimer cette sauvegarde ?')" style="margin:0">
        <?=Auth::csrfField()?><input type="hidden" name="filename" value="<?=Helpers::e($b['filename'])?>">
        <button type="submit" name="delete_backup" class="btn btn-sm" style="background:#fee2e2;color:#dc2626;border:1.5px solid #fecaca">🗑</button>
      </form>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>

<?php else: ?>
<!-- Journal d'activité -->
<?php if(empty($logs)): ?>
<div style="background:#f8fafc;border-radius:12px;padding:2rem;text-align:center;color:#94a3b8">Aucune activité enregistrée.</div>
<?php else: ?>
<div class="ac">
  <div class="ac-header"><h2>100 dernières actions</h2></div>
  <div class="at-wrap"><table class="at">
    <thead><tr><th>Date</th><th>Utilisateur</th><th>Action</th><th>Entité</th><th>IP</th><th>Détails</th></tr></thead>
    <tbody>
    <?php foreach($logs as $log): ?>
    <tr>
      <td style="font-size:.75rem;white-space:nowrap"><?=(new DateTime($log['created_at']))->format('d/m H:i:s')?></td>
      <td style="font-size:.78rem"><?=$log['firstname']?Helpers::e($log['firstname'].' '.$log['lastname']):'<span style="color:#94a3b8">Visiteur</span>'?></td>
      <td><code style="font-size:.75rem;background:#f1f5f9;padding:.1rem .35rem;border-radius:4px"><?=Helpers::e($log['action'])?></code></td>
      <td style="font-size:.78rem"><?=$log['entity']?Helpers::e($log['entity']).' #'.$log['entity_id']:''?></td>
      <td style="font-size:.72rem;color:#94a3b8"><?=Helpers::e($log['ip']??'')?></td>
      <td style="font-size:.72rem;color:#64748b;max-width:200px;overflow:hidden;text-overflow:ellipsis">
        <?php if($log['details']): $d=json_decode($log['details'],true); echo Helpers::e(implode(', ',array_map(fn($k,$v)=>"$k: $v",array_keys($d),array_values($d)))); endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>
<?php endif; ?>

<?php
$content = ob_get_clean();
include CC_ROOT . '/admin/layout.php';
