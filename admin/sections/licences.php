<?php
Auth::require('coach');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Auth::verifyCsrf()) {
    adminFlash('error','CSRF'); Helpers::redirect(u('/admin/licences'));
}

// ── Validation / refus de licence ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $uid = (int)($_POST['user_id'] ?? 0);
    $s   = in_array($_POST['status']??'',['valid','rejected']) ? $_POST['status'] : null;
    if ($uid && $s) {
        Database::run("UPDATE cc_users SET license_status=? WHERE id=?", [$s, $uid]);
        adminFlash('success', $s==='valid' ? 'Licence validée.' : 'Licence refusée.');
    }
    Helpers::redirect(u('/admin/licences'));
}

// ── Vérification PDF carte membre ─────────────────────────────
// Lit le hash embarqué dans le contenu binaire du PDF lors de sa génération
// Un simple renommage de fichier ne suffit pas — le hash est inscrit dans le PDF
$cardVerifyResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_card_pdf'])) {
    if (empty($_FILES['card_pdf']['tmp_name']) || $_FILES['card_pdf']['size'] === 0) {
        $cardVerifyResult = ['type'=>'warning', 'msg'=>'⚠️ Aucun fichier reçu.'];
    } else {
        $pdfContent = @file_get_contents($_FILES['card_pdf']['tmp_name']);

        if (!$pdfContent || substr($pdfContent, 0, 4) !== '%PDF') {
            $cardVerifyResult = ['type'=>'error', 'msg'=>'❌ Fichier invalide — ce n\'est pas un PDF.'];
        } else {
            // Extraire hash et ID depuis les métadonnées embarquées par ClubCMS
            $hashFromPdf   = null;
            $memberIdFromPdf = null;

            if (preg_match('/CLUBCMS-HASH:([a-f0-9]{12})/i', $pdfContent, $mh)) {
                $hashFromPdf = strtolower($mh[1]);
            }
            if (preg_match('/clubcms-verify:[a-f0-9]+:member:([0-9]+)/i', $pdfContent, $mi)) {
                $memberIdFromPdf = (int)$mi[1];
            }

            if (!$hashFromPdf || !$memberIdFromPdf) {
                $cardVerifyResult = [
                    'type' => 'error',
                    'msg'  => '❌ <strong>Document non authentique</strong> — Ce PDF ne contient aucune signature numérique ClubCMS. '
                            . 'Il n\'a pas été généré par ce site, ou a été modifié. Un simple renommage de fichier est insuffisant.'
                ];
            } else {
                $member = Database::one(
                    "SELECT id, firstname, lastname, member_card_hash FROM cc_users WHERE id=?",
                    [$memberIdFromPdf]
                );

                if (!$member) {
                    $cardVerifyResult = ['type'=>'error', 'msg'=>'❌ <strong>Membre introuvable</strong> — ID ' . $memberIdFromPdf . ' inconnu.'];
                } elseif (!$member['member_card_hash']) {
                    $cardVerifyResult = ['type'=>'error', 'msg'=>'❌ <strong>Carte jamais générée</strong> — Ce membre n\'a pas de carte officielle dans le système.'];
                } elseif (strtolower($member['member_card_hash']) !== $hashFromPdf) {
                    $cardVerifyResult = [
                        'type' => 'error',
                        'msg'  => '❌ <strong>Carte INVALIDE</strong> — La signature numérique interne ne correspond pas. '
                                . 'Ce document a été falsifié.'
                    ];
                } else {
                    $cardVerifyResult = [
                        'type' => 'success',
                        'msg'  => '✅ <strong>Carte AUTHENTIQUE</strong> — Document original généré par ce site pour '
                                . '<strong>' . Helpers::e($member['firstname'] . ' ' . $member['lastname']) . '</strong>'
                                . ' (membre #' . $memberIdFromPdf . ').'
                    ];
                }
            }
        }
    }
}

// ── Données ───────────────────────────────────────────────────
$pending = Database::all("SELECT * FROM cc_users WHERE license_status='pending' ORDER BY updated_at DESC");
$all     = Database::all("SELECT * FROM cc_users WHERE license_status NOT IN ('none') ORDER BY updated_at DESC LIMIT 50");
$pageTitle = 'Licences';
ob_start();
?>

<div class="page-head"><h1>📄 Licences</h1></div>

<?php if ($pending): ?>
<div class="ac" style="margin-bottom:1.5rem">
  <div class="ac-header"><h2>⏳ En attente (<?=count($pending)?>)</h2></div>
  <table class="at"><thead><tr><th>Membre</th><th>N° Licence</th><th>Expiration</th><th>Document</th><th>Actions</th></tr></thead><tbody>
    <?php foreach ($pending as $u): ?>
    <tr>
      <td><strong><?=Helpers::e($u['firstname'].' '.$u['lastname'])?></strong><br><small><?=Helpers::e($u['email'])?></small></td>
      <td><?=Helpers::e($u['license_number']??'—')?></td>
      <td><?=$u['license_expiry'] ? Helpers::dateFormat($u['license_expiry']) : '—'?></td>
      <td><?php if ($u['license_file']): ?><a href="<?=asset($u['license_file'])?>" target="_blank" class="btn btn-ghost btn-sm">📎</a><?php else: ?>—<?php endif; ?></td>
      <td style="display:flex;gap:.35rem">
        <form method="post"><?=Auth::csrfField()?><input type="hidden" name="user_id" value="<?=$u['id']?>"><input type="hidden" name="status" value="valid"><button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Valider ?')">✅ Valider</button></form>
        <form method="post"><?=Auth::csrfField()?><input type="hidden" name="user_id" value="<?=$u['id']?>"><input type="hidden" name="status" value="rejected"><button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Refuser ?')">❌ Refuser</button></form>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody></table>
</div>
<?php else: ?>
<div class="alert alert-success" style="margin-bottom:1rem">✅ Aucune licence en attente.</div>
<?php endif; ?>

<div class="ac" style="margin-bottom:1.5rem">
  <div class="ac-header"><h2>Toutes les licences</h2></div>
  <table class="at"><thead><tr><th>Membre</th><th>N°</th><th>Expiration</th><th>Statut</th></tr></thead><tbody>
    <?php foreach ($all as $u): ?>
    <tr>
      <td><?=Helpers::e($u['firstname'].' '.$u['lastname'])?></td>
      <td><?=Helpers::e($u['license_number']??'—')?></td>
      <td><?=$u['license_expiry'] ? Helpers::dateFormat($u['license_expiry']) : '—'?></td>
      <td><span class="badge badge-<?=match($u['license_status']){'valid'=>'success','pending'=>'warning','expired','rejected'=>'error',default=>'muted'}?>"><?=$u['license_status']?></span></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($all)): ?><tr><td colspan="4" style="text-align:center;padding:2rem;color:#94a3b8">Aucune licence soumise.</td></tr><?php endif; ?>
  </tbody></table>
</div>

<!-- ── Vérification PDF carte membre ── -->
<div class="ac">
  <div class="ac-header"><h2>🔍 Authentifier une carte membre PDF</h2></div>
  <div class="ac-body">
    <p style="color:#64748b;font-size:.875rem;margin-bottom:1.25rem">
      Uploadez le PDF reçu d'un membre. Le système lit la <strong>signature numérique embarquée</strong> dans le document
      et la compare avec la base de données. Un simple renommage de fichier sera détecté comme fraude.
    </p>
    <form method="post" enctype="multipart/form-data">
      <?=Auth::csrfField()?>
      <div style="display:flex;gap:.75rem;align-items:flex-end">
        <div class="fg" style="flex:1;margin:0">
          <label>Fichier PDF de la carte membre</label>
          <input type="file" name="card_pdf" accept="application/pdf" required>
        </div>
        <button type="submit" name="verify_card_pdf" value="1" class="btn btn-primary">🔍 Authentifier</button>
      </div>
    </form>

    <?php if ($cardVerifyResult): ?>
    <?php
      $styles = [
        'success' => 'background:#dcfce7;border-color:#86efac;color:#166534',
        'error'   => 'background:#fee2e2;border-color:#fca5a5;color:#991b1b',
        'warning' => 'background:#fef3c7;border-color:#fde68a;color:#92400e',
      ];
      $s = $styles[$cardVerifyResult['type']] ?? $styles['warning'];
    ?>
    <div style="border:1px solid;border-radius:10px;padding:1rem 1.25rem;margin-top:1.25rem;font-size:.9rem;line-height:1.65;<?=$s?>">
      <?=$cardVerifyResult['msg']?>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php
$content = ob_get_clean();
include CC_ROOT . '/admin/layout.php';
