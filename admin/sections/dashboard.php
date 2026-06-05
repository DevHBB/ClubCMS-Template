<?php
$pageTitle = 'Tableau de bord';
ob_start();
?>
<div class="page-head"><h1>🏠 Tableau de bord</h1></div>

<div class="stats-grid">
  <?php
  $stats = [
    ['👥', Database::scalar("SELECT COUNT(*) FROM cc_users WHERE status='active'"), 'Membres actifs'],
    ['⏳', Database::scalar("SELECT COUNT(*) FROM cc_users WHERE license_status='pending'"), 'Licences en attente'],
    ['🛒', Database::scalar("SELECT COUNT(*) FROM cc_shop_orders WHERE status='pending'"), 'Commandes en attente'],
    ['📅', Database::scalar("SELECT COUNT(*) FROM cc_planning_slots WHERE date_start >= NOW() AND published=1"), 'Créneaux à venir'],
    ['💬', Database::scalar("SELECT COUNT(*) FROM cc_forum_topics WHERE created_at > DATE_SUB(NOW(),INTERVAL 7 DAY)"), 'Topics cette semaine'],
    ['📨', Database::scalar("SELECT COUNT(*) FROM cc_newsletter_subscribers WHERE active=1"), 'Abonnés newsletter'],
  ];
  foreach ($stats as [$icon,$val,$lbl]):
  ?>
  <div class="stat-card"><div class="stat-icon"><?=$icon?></div><div class="stat-val"><?=$val?></div><div class="stat-lbl"><?=$lbl?></div></div>
  <?php endforeach; ?>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem">
  <!-- Derniers inscrits -->
  <div class="ac">
    <div class="ac-header"><h2>Derniers inscrits</h2><a href="<?=u('/admin/users')?>" class="btn btn-ghost btn-sm">Voir tout</a></div>
    <table class="at">
      <thead><tr><th>Nom</th><th>Email</th><th>Statut</th><th>Le</th></tr></thead>
      <tbody>
        <?php foreach(Database::all("SELECT * FROM cc_users ORDER BY created_at DESC LIMIT 6") as $u): ?>
        <tr>
          <td><strong><?=Helpers::e($u['firstname'].' '.$u['lastname'])?></strong></td>
          <td><?=Helpers::e($u['email'])?></td>
          <td><span class="badge badge-<?=$u['status']==='active'?'success':'warning'?>"><?=$u['status']?></span></td>
          <td><?=Helpers::dateFormat($u['created_at'])?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Dernières commandes -->
  <div class="ac">
    <div class="ac-header"><h2>Dernières commandes</h2><a href="<?=u('/admin/shop')?>" class="btn btn-ghost btn-sm">Voir tout</a></div>
    <table class="at">
      <thead><tr><th>#</th><th>Total</th><th>Statut</th><th>Le</th></tr></thead>
      <tbody>
        <?php foreach(Database::all("SELECT * FROM cc_shop_orders ORDER BY created_at DESC LIMIT 6") as $o): ?>
        <tr>
          <td>#<?=$o['id']?></td>
          <td><?=Helpers::price($o['total'])?></td>
          <td><span class="badge badge-<?=$o['status']==='paid'?'success':($o['status']==='pending'?'warning':'muted')?>"><?=$o['status']?></span></td>
          <td><?=Helpers::dateFormat($o['created_at'])?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php
$content = ob_get_clean();
include CC_ROOT . '/admin/layout.php';
