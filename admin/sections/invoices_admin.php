<?php
/**
 * ClubCMS — Admin Factures
 */
Auth::require('admin');
if (!class_exists('ActivityLog')) require_once dirname(__DIR__,2) . '/core/ActivityLog.php';
if (!class_exists('Invoice'))     require_once dirname(__DIR__,2) . '/core/Invoice.php';


// Migrations
try { Database::run("CREATE TABLE IF NOT EXISTS cc_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY, invoice_number VARCHAR(30) NOT NULL UNIQUE,
    order_id INT NOT NULL, user_id INT DEFAULT NULL,
    status ENUM('draft','issued','paid','cancelled') DEFAULT 'issued',
    subtotal_ht DECIMAL(10,2) NOT NULL DEFAULT 0.00, tva_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    tva_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00, total_ttc DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    billing_info JSON DEFAULT NULL, items JSON NOT NULL, notes TEXT DEFAULT NULL,
    issued_at DATETIME DEFAULT CURRENT_TIMESTAMP, paid_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)"); } catch(Exception $e) {}

// Télécharger une facture
if (isset($_GET['pdf']) && (int)$_GET['pdf']) {
    Invoice::generatePdf((int)$_GET['pdf']);
    exit;
}

// Générer facture manuelle pour une commande
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['gen_invoice']) && Auth::verifyCsrf()) {
    $oid = (int)$_POST['order_id'];
    $invId = Invoice::createForOrder($oid);
    adminFlash($invId ? 'success' : 'error', $invId ? 'Facture générée.' : 'Erreur lors de la génération.');
    Helpers::redirect(u('/admin/invoices'));
}

$invoices = Database::all(
    "SELECT i.*, u.firstname, u.lastname FROM cc_invoices i
     LEFT JOIN cc_users u ON i.user_id = u.id
     ORDER BY i.created_at DESC LIMIT 100"
);

// Commandes sans facture
$ordersWithoutInvoice = Database::all(
    "SELECT o.id, o.total, o.created_at, u.firstname, u.lastname
     FROM cc_shop_orders o
     LEFT JOIN cc_invoices i ON i.order_id = o.id
     LEFT JOIN cc_users u ON o.user_id = u.id
     WHERE i.id IS NULL AND o.status IN ('paid','shipped','refunded')
     ORDER BY o.created_at DESC LIMIT 20"
);

$pageTitle = '🧾 Factures';
ob_start();
?>
<div class="page-head">
  <h1>🧾 Factures</h1>
  <a href="<?=u('/admin/invoices?tab=settings')?>" class="btn btn-ghost btn-sm">⚙️ Paramètres TVA</a>
</div>

<?php if(!empty($ordersWithoutInvoice)): ?>
<div style="background:#fffbeb;border:1.5px solid #fde68a;border-radius:12px;padding:1rem 1.25rem;margin-bottom:1.5rem">
  <div style="font-weight:700;color:#92400e;margin-bottom:.5rem">⚠️ <?=count($ordersWithoutInvoice)?> commande(s) sans facture</div>
  <?php foreach($ordersWithoutInvoice as $o): ?>
  <div style="display:flex;align-items:center;justify-content:space-between;font-size:.82rem;margin-bottom:.35rem;flex-wrap:wrap;gap:.5rem">
    <span>Commande #<?=$o['id']?> — <?=Helpers::e($o['firstname'].' '.$o['lastname'])?> — <?=Helpers::price($o['total'])?></span>
    <form method="post" style="margin:0">
      <?=Auth::csrfField()?><input type="hidden" name="order_id" value="<?=$o['id']?>">
      <button type="submit" name="gen_invoice" class="btn btn-primary btn-sm">🧾 Générer</button>
    </form>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Stats rapides -->
<?php
$statsMonth = Database::one("SELECT COUNT(*) as nb, COALESCE(SUM(total_ttc),0) as total FROM cc_invoices WHERE issued_at >= DATE_FORMAT(NOW(),'%Y-%m-01')");
$statsYear  = Database::one("SELECT COUNT(*) as nb, COALESCE(SUM(total_ttc),0) as total FROM cc_invoices WHERE YEAR(issued_at)=YEAR(NOW())");
?>
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem">
  <div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;padding:1rem;text-align:center">
    <div style="font-size:1.5rem;font-weight:800;color:var(--color-primary)"><?=$statsMonth['nb']?></div>
    <div style="font-size:.78rem;color:#64748b">Factures ce mois</div>
  </div>
  <div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;padding:1rem;text-align:center">
    <div style="font-size:1.5rem;font-weight:800;color:#16a34a"><?=Helpers::price($statsMonth['total'])?></div>
    <div style="font-size:.78rem;color:#64748b">CA ce mois TTC</div>
  </div>
  <div style="background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;padding:1rem;text-align:center">
    <div style="font-size:1.5rem;font-weight:800;color:#6366f1"><?=Helpers::price($statsYear['total'])?></div>
    <div style="font-size:.78rem;color:#64748b">CA cette année TTC</div>
  </div>
</div>

<!-- Liste factures -->
<div class="ac">
  <div class="ac-header"><h2>Toutes les factures</h2></div>
  <?php if(empty($invoices)): ?>
  <div class="ac-body" style="text-align:center;color:#94a3b8;padding:2rem">Aucune facture générée.</div>
  <?php else: ?>
  <div class="at-wrap"><table class="at">
    <thead><tr><th>N° Facture</th><th>Client</th><th>HT</th><th>TVA</th><th>TTC</th><th>Statut</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($invoices as $inv):
      $colors=['issued'=>'#3b82f6','paid'=>'#16a34a','cancelled'=>'#dc2626','draft'=>'#94a3b8'];
      $labels=['issued'=>'Émise','paid'=>'Payée','cancelled'=>'Annulée','draft'=>'Brouillon'];
    ?>
    <tr>
      <td><strong><?=Helpers::e($inv['invoice_number'])?></strong></td>
      <td><?=Helpers::e($inv['firstname'].' '.$inv['lastname'])?><div style="font-size:.72rem;color:#94a3b8">Cmd #<?=$inv['order_id']?></div></td>
      <td><?=Helpers::price($inv['subtotal_ht'])?></td>
      <td><?=Helpers::price($inv['tva_amount'])?> (<?=$inv['tva_rate']?>%)</td>
      <td><strong><?=Helpers::price($inv['total_ttc'])?></strong></td>
      <td><span style="background:<?=$colors[$inv['status']]??'#94a3b8'?>;color:#fff;border-radius:99px;padding:.15rem .5rem;font-size:.72rem;font-weight:700"><?=$labels[$inv['status']]??$inv['status']?></span></td>
      <td style="font-size:.78rem"><?=(new DateTime($inv['issued_at']))->format('d/m/Y')?></td>
      <td>
        <a href="<?=u('/admin/invoices')?>&pdf=<?=$inv['id']?>" class="btn btn-primary btn-sm" target="_blank">⬇️ PDF</a>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include CC_ROOT . '/admin/layout.php';
