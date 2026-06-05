<?php
/**
 * ClubCMS — Vérification email
 */

$token  = Helpers::sanitize($_GET['token'] ?? '');
$ok     = $token && Auth::verifyEmail($token);
$pageTitle = 'Vérification email — ' . Config::get('club_name');
ob_start();
?>
<div class="auth-container">
  <div class="auth-card" style="text-align:center">
    <?php if ($ok): ?>
      <div style="font-size:4rem;margin-bottom:1rem">✅</div>
      <h1 class="auth-title">Email vérifié !</h1>
      <p style="color:var(--color-muted);margin:1rem 0 2rem">Votre compte est maintenant actif. Vous pouvez vous connecter.</p>
      <a href="/login?verified=1" class="btn-submit" style="display:block;text-align:center;text-decoration:none">Se connecter</a>
    <?php else: ?>
      <div style="font-size:4rem;margin-bottom:1rem">❌</div>
      <h1 class="auth-title">Lien invalide</h1>
      <p style="color:var(--color-muted);margin:1rem 0 2rem">Ce lien est invalide ou a déjà été utilisé.</p>
      <a href="<?=u('/login')?>" class="btn-submit" style="display:block;text-align:center;text-decoration:none">Retour à la connexion</a>
    <?php endif; ?>
  </div>
</div>
<?php
$content = ob_get_clean();
include CC_ROOT . '/templates/layout.php';
