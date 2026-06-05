<?php
/**
 * ClubCMS — Connexion
 */

if (Auth::check()) Helpers::redirect('/membre');

$error  = '';
$return = Helpers::sanitize($_GET['return'] ?? '/membre');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Auth::verifyCsrf()) {
        $error = 'Requête invalide. Réessayez.';
    } else {
        $result = Auth::login($_POST['email'] ?? '', $_POST['password'] ?? '');
        if ($result['success']) {
            Helpers::redirect($return);
        } else {
            $error = $result['error'];
        }
    }
}

$club          = Config::get('club_name', 'Mon Club');
$recaptchaSite = Config::get('recaptcha_site', '');
$pageTitle     = 'Connexion — ' . $club;
ob_start();
?>
<div class="auth-container">
  <div class="auth-card">
    <div class="auth-logo">
      <?php if (Config::get('logo')): ?>
        <img src="<?=asset(Config::get('logo'))?>" alt="<?= Helpers::e($club) ?>" class="auth-club-logo">
      <?php endif; ?>
      <h1 class="auth-title">Connexion</h1>
      <p class="auth-subtitle"><?= Helpers::e($club) ?></p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error">⚠️ <?= Helpers::e($error) ?></div>
    <?php endif; ?>

    <?php if ($_GET['verified'] ?? false): ?>
      <div class="alert alert-success">✅ Email vérifié ! Vous pouvez vous connecter.</div>
    <?php endif; ?>
    <?php if ($_GET['reset'] ?? false): ?>
      <div class="alert alert-success">✅ Mot de passe réinitialisé. Connectez-vous.</div>
    <?php endif; ?>

    <form method="post" id="login-form">
      <?= Auth::csrfField() ?>
      <input type="hidden" name="return" value="<?= Helpers::e($return) ?>">

      <div class="form-group">
        <label for="email">Adresse email</label>
        <input type="email" id="email" name="email"
               value="<?= Helpers::e($_POST['email'] ?? '') ?>"
               placeholder="jean.dupont@email.fr"
               required autocomplete="email" autofocus>
      </div>

      <div class="form-group">
        <label for="password">
          Mot de passe
          <a href="<?=u('/forgot-password')?>" style="float:right;font-weight:400;font-size:.8rem;text-transform:none;letter-spacing:0">Mot de passe oublié ?</a>
        </label>
        <div class="input-icon-wrap">
          <input type="password" id="password" name="password"
                 placeholder="Votre mot de passe"
                 required autocomplete="current-password">
          <button type="button" class="toggle-pass" onclick="togglePass('password')" tabindex="-1">👁</button>
        </div>
      </div>

      <div class="form-group checkbox-group">
        <label class="checkbox-label">
          <input type="checkbox" name="remember">
          <span>Se souvenir de moi</span>
        </label>
      </div>

      <button type="submit" class="btn-submit">Se connecter</button>
    </form>

    <p class="auth-switch">
      Pas encore membre ? <a href="<?=u('/register')?>">Créer un compte</a>
    </p>
  </div>
</div>

<script>
function togglePass(id) {
  const el = document.getElementById(id);
  el.type = el.type === 'password' ? 'text' : 'password';
}
</script>
<?php
$content = ob_get_clean();
include CC_ROOT . '/templates/layout.php';
