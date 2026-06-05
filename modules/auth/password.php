<?php
/**
 * ClubCMS — Mot de passe oublié / Réinitialisation
 */

$isForgot = ($module === 'forgot-password');
$isReset  = ($module === 'reset-password');
$token    = Helpers::sanitize($_GET['token'] ?? '');
$error    = '';
$success  = '';

// ── Demande de reset ───────────────────────────────────────────
if ($isForgot && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf()) { $error = 'Requête invalide.'; }
    else {
        Auth::requestPasswordReset($_POST['email'] ?? '');
        // On affiche toujours succès (ne révèle pas si l'email existe)
        $success = 'Si cette adresse existe, vous recevrez un email dans quelques minutes.';
    }
}

// ── Réinitialisation ───────────────────────────────────────────
if ($isReset && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf()) { $error = 'Requête invalide.'; }
    else {
        $result = Auth::resetPassword(
            Helpers::sanitize($_POST['token'] ?? ''),
            $_POST['password'] ?? ''
        );
        if ($result['success']) {
            Helpers::redirect('/login?reset=1');
        } else {
            $error = $result['error'];
        }
    }
}

$pageTitle = ($isForgot ? 'Mot de passe oublié' : 'Nouveau mot de passe') . ' — ' . Config::get('club_name');
ob_start();
?>
<div class="auth-container">
  <div class="auth-card">
    <div class="auth-logo">
      <h1 class="auth-title"><?= $isForgot ? '🔑 Mot de passe oublié' : '🔐 Nouveau mot de passe' ?></h1>
      <p class="auth-subtitle"><?= Helpers::e(Config::get('club_name')) ?></p>
    </div>

    <?php if ($error): ?><div class="alert alert-error">⚠️ <?= Helpers::e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success">✅ <?= Helpers::e($success) ?></div><?php endif; ?>

    <?php if ($isForgot && !$success): ?>
    <form method="post">
      <?= Auth::csrfField() ?>
      <div class="form-group">
        <label for="email">Votre adresse email</label>
        <input type="email" id="email" name="email" placeholder="jean.dupont@email.fr" required autofocus>
      </div>
      <button type="submit" class="btn-submit">Envoyer le lien de réinitialisation</button>
    </form>

    <?php elseif ($isReset): ?>
    <?php
    // Vérifie que le token est valide
    $validToken = Database::one(
        "SELECT id FROM cc_users WHERE reset_token = ? AND reset_expires > NOW()",
        [$token]
    );
    if (!$validToken && !$error):
    ?>
      <div class="alert alert-error">⚠️ Ce lien est invalide ou expiré. <a href="<?=u('/forgot-password')?>">Faire une nouvelle demande</a></div>
    <?php else: ?>
    <form method="post">
      <?= Auth::csrfField() ?>
      <input type="hidden" name="token" value="<?= Helpers::e($token) ?>">
      <div class="form-group">
        <label>Nouveau mot de passe</label>
        <div class="input-icon-wrap">
          <input type="password" name="password" placeholder="8 caractères minimum" required minlength="8" autofocus>
          <button type="button" class="toggle-pass" onclick="togglePass('p1')" tabindex="-1">👁</button>
        </div>
        <div class="password-strength" id="pass-strength"></div>
      </div>
      <div class="form-group">
        <label>Confirmer le mot de passe</label>
        <div class="input-icon-wrap">
          <input type="password" id="p2" name="password2" placeholder="Répétez le mot de passe" required>
          <button type="button" class="toggle-pass" onclick="togglePass('p2')" tabindex="-1">👁</button>
        </div>
      </div>
      <button type="submit" class="btn-submit">Enregistrer le nouveau mot de passe</button>
    </form>
    <script>
    function togglePass(id) { const el = document.getElementById(id)||document.querySelector('input[type=password]'); if(el) el.type = el.type==='password'?'text':'password'; }
    const pi = document.querySelector('input[name=password]');
    const ps = document.getElementById('pass-strength');
    if(pi && ps) pi.addEventListener('input', () => {
      const v=pi.value; let s=0;
      if(v.length>=8)s++; if(/[A-Z]/.test(v))s++; if(/[0-9]/.test(v))s++; if(/[^A-Za-z0-9]/.test(v))s++;
      const l=['','Faible','Moyen','Bien','Fort'], c=['','#ef4444','#f59e0b','#3b82f6','#10b981'];
      ps.textContent = v.length ? '🔒 '+(l[s]||'Faible') : '';
      ps.style.color = c[s]||'#ef4444';
    });
    </script>
    <?php endif; ?>
    <?php endif; ?>

    <p class="auth-switch"><a href="<?=u('/login')?>">← Retour à la connexion</a></p>
  </div>
</div>
<?php
$content = ob_get_clean();
include CC_ROOT . '/templates/layout.php';
