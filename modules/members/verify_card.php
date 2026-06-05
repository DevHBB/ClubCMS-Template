<?php
/**
 * ClubCMS — Vérification publique de carte membre
 * URL : /verifier-carte?id=12&hash=abc123...
 */

$userId = (int)($_GET['id'] ?? 0);
$hash   = Helpers::sanitize($_GET['hash'] ?? '');
$valid  = false;
$member = null;

if ($userId && $hash) {
    $member = Database::one(
        "SELECT id, firstname, lastname, email, member_card_hash, member_card_generated_at,
                license_status, license_expiry, role
         FROM cc_users WHERE id = ? AND status = 'active'",
        [$userId]
    );
    if ($member && hash_equals($member['member_card_hash'] ?? '', $hash)) {
        $valid = true;
    }
}

$club  = Config::get('club_name', 'Mon Club');
$color = Config::get('primary_color', '#1d4ed8');
$pageTitle = 'Vérification carte membre — ' . $club;
ob_start();
?>

<div style="min-height:calc(100vh - 64px);display:flex;align-items:center;justify-content:center;padding:2rem;background:var(--color-surface)">
  <div style="max-width:480px;width:100%;text-align:center">

    <?php if ($valid): ?>
      <!-- ✅ Carte valide -->
      <div style="font-size:4rem;margin-bottom:1rem">✅</div>
      <h1 style="font-family:var(--font-heading);font-size:2.2rem;letter-spacing:.08em;color:var(--color-success);margin-bottom:.5rem">Carte authentique</h1>
      <p style="color:var(--color-muted);margin-bottom:2rem">Cette carte membre a bien été émise par <strong><?= Helpers::e($club) ?></strong>.</p>

      <div style="background:#fff;border:1px solid var(--color-border);border-radius:var(--radius-md);padding:1.75rem;text-align:left;margin-bottom:1.5rem">
        <table style="width:100%;border-collapse:collapse;font-size:.9rem">
          <tr style="border-bottom:1px solid var(--color-border)">
            <td style="padding:.6rem 0;color:var(--color-muted);width:40%">Membre</td>
            <td style="padding:.6rem 0;font-weight:700"><?= Helpers::e($member['firstname'] . ' ' . strtoupper($member['lastname'])) ?></td>
          </tr>
          <tr style="border-bottom:1px solid var(--color-border)">
            <td style="padding:.6rem 0;color:var(--color-muted)">N° membre</td>
            <td style="padding:.6rem 0;font-weight:700"><?= str_pad($member['id'], 6, '0', STR_PAD_LEFT) ?></td>
          </tr>
          <tr style="border-bottom:1px solid var(--color-border)">
            <td style="padding:.6rem 0;color:var(--color-muted)">Statut</td>
            <td style="padding:.6rem 0">
              <span class="badge badge-success">Membre actif</span>
            </td>
          </tr>
          <tr style="border-bottom:1px solid var(--color-border)">
            <td style="padding:.6rem 0;color:var(--color-muted)">Licence</td>
            <td style="padding:.6rem 0">
              <?php $ls = $member['license_status']; ?>
              <span class="badge badge-<?= match($ls) { 'valid'=>'success','pending'=>'warning', default=>'error' } ?>">
                <?= match($ls) { 'valid'=>'✅ Valide','pending'=>'⏳ En cours','expired'=>'❌ Expirée','rejected'=>'🚫 Refusée',default=>'— Aucune' } ?>
              </span>
              <?php if ($ls === 'valid' && $member['license_expiry']): ?>
                <span style="font-size:.75rem;color:var(--color-muted);margin-left:.4rem">jusqu'au <?= Helpers::dateFormat($member['license_expiry']) ?></span>
              <?php endif; ?>
            </td>
          </tr>
          <tr>
            <td style="padding:.6rem 0;color:var(--color-muted)">Carte émise le</td>
            <td style="padding:.6rem 0"><?= Helpers::dateFormat($member['member_card_generated_at']) ?></td>
          </tr>
        </table>
      </div>

      <div style="background:color-mix(in srgb,var(--color-success) 8%,transparent);border:1px solid color-mix(in srgb,var(--color-success) 25%,transparent);border-radius:var(--radius-sm);padding:.875rem;font-size:.8rem;color:var(--color-muted)">
        🔒 Cette vérification est sécurisée par signature numérique HMAC-SHA256.<br>
        Toute carte modifiée serait détectée comme invalide.
      </div>

    <?php else: ?>
      <!-- ❌ Carte invalide -->
      <div style="font-size:4rem;margin-bottom:1rem">❌</div>
      <h1 style="font-family:var(--font-heading);font-size:2.2rem;letter-spacing:.08em;color:var(--color-error);margin-bottom:.5rem">Carte invalide</h1>
      <p style="color:var(--color-muted);margin-bottom:2rem">
        Cette carte n'a pas pu être vérifiée.<br>
        Elle n'a peut-être pas été émise par <strong><?= Helpers::e($club) ?></strong>, ou a été modifiée.
      </p>
      <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:var(--radius-sm);padding:.875rem;font-size:.875rem;color:#991b1b">
        ⚠️ En cas de doute, contactez directement le club.<br>
        <?php if (Config::get('club_email')): ?>
          <a href="mailto:<?= Helpers::e(Config::get('club_email')) ?>" style="color:#991b1b"><?= Helpers::e(Config::get('club_email')) ?></a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div style="margin-top:2rem">
      <a href="<?=u('/')?>" style="color:var(--color-muted);font-size:.875rem">← Retour au site</a>
    </div>
  </div>
</div>

<?php
$content = ob_get_clean();
include CC_ROOT . '/templates/layout.php';
