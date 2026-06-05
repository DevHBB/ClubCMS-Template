<?php
/**
 * ClubCMS — Admin Modèles d'emails
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Auth::verifyCsrf()) { adminFlash('error','CSRF'); Helpers::redirect(u('/admin/mails')); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_mail'])) {
    $key = Helpers::sanitize($_POST['mail_key'] ?? '');
    if ($key) {
        Config::set('mail_tpl_subject_'.$key, Helpers::sanitize($_POST['subject'] ?? ''), 'mails');
        Config::set('mail_tpl_body_'.$key,    $_POST['body'] ?? '',                       'mails');
    }
    adminFlash('success','Modèle sauvegardé.'); Helpers::redirect('/admin/mails?tab='.$key);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    $to  = Helpers::sanitize($_POST['test_email'] ?? Auth::user()['email']);
    $key = Helpers::sanitize($_POST['mail_key'] ?? 'welcome');
    $subject = Config::get('mail_tpl_subject_'.$key, 'Test — ClubCMS');
    $body    = Config::get('mail_tpl_body_'.$key, '<p>Test</p>');
    Mailer::send($to, 'Test', $subject, $body);
    adminFlash('success', 'Email de test envoyé à '.$to);
    Helpers::redirect('/admin/mails?tab='.$key);
}

// Définition des modèles d'emails
$mailTemplates = [
    'welcome'     => ['label' => 'Bienvenue à l\'inscription',       'icon' => '🎉', 'vars' => '{firstname}, {club_name}, {verify_url}'],
    'order'       => ['label' => 'Confirmation de commande',          'icon' => '📦', 'vars' => '{firstname}, {order_id}, {total}, {club_name}'],
    'forum_reply' => ['label' => 'Nouvelle réponse forum',            'icon' => '💬', 'vars' => '{firstname}, {topic_title}, {topic_url}, {club_name}'],
    'gallery_new' => ['label' => 'Nouvelle galerie publiée',          'icon' => '📸', 'vars' => '{firstname}, {folder_name}, {folder_url}, {club_name}'],
    'booking_ok'  => ['label' => 'Réservation confirmée',             'icon' => '📅', 'vars' => '{firstname}, {slot_title}, {slot_date}, {club_name}'],
    'booking_wait'=> ['label' => 'Réservation liste d\'attente',      'icon' => '⏳', 'vars' => '{firstname}, {slot_title}, {club_name}'],
    'member_card' => ['label' => 'Carte membre',                      'icon' => '🪪', 'vars' => '{firstname}, {lastname}, {member_id}, {club_name}'],
    'reset_pass'  => ['label' => 'Réinitialisation mot de passe',     'icon' => '🔑', 'vars' => '{firstname}, {reset_url}, {club_name}'],
    'newsletter'  => ['label' => 'Newsletter',                        'icon' => '📨', 'vars' => '{firstname}, {club_name}, {unsubscribe_url}'],
];

$tab = $_GET['tab'] ?? 'welcome';
if (!isset($mailTemplates[$tab])) $tab = 'welcome';
$currentTpl = $mailTemplates[$tab];

$pageTitle = 'Modèles d\'emails';
ob_start();
?>
<div class="page-head"><h1>✉️ Modèles d'emails</h1></div>

<div style="display:grid;grid-template-columns:200px 1fr;gap:1.5rem;align-items:start">

  <!-- Nav latérale -->
  <div class="ac" style="overflow:hidden">
    <div style="padding:.4rem">
      <?php foreach ($mailTemplates as $k => $m): ?>
        <a href="?tab=<?= $k ?>" style="
          display:flex;align-items:center;gap:.6rem;
          padding:.55rem .75rem;border-radius:6px;
          font-size:.85rem;font-weight:<?= $tab===$k?'700':'400' ?>;
          color:<?= $tab===$k ? 'var(--color-primary)' : '#374151' ?>;
          background:<?= $tab===$k ? 'color-mix(in srgb,var(--color-primary) 10%,transparent)' : 'transparent' ?>;
          text-decoration:none;margin-bottom:1px;
          border:1px solid <?= $tab===$k ? 'color-mix(in srgb,var(--color-primary) 25%,transparent)' : 'transparent' ?>;">
          <span style="font-size:1rem;flex-shrink:0"><?= $m['icon'] ?></span>
          <span style="line-height:1.3"><?= Helpers::e($m['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Éditeur -->
  <div>
    <div class="ac" style="margin-bottom:1rem">
      <div class="ac-header">
        <h2><?= $currentTpl['icon'] ?> <?= Helpers::e($currentTpl['label']) ?></h2>
      </div>
      <div class="ac-body">
        <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:.75rem 1rem;margin-bottom:1.25rem;font-size:.82rem;color:#92400e">
          <strong>Variables disponibles :</strong> <code style="background:rgba(0,0,0,.06);padding:.1rem .35rem;border-radius:3px"><?= Helpers::e($currentTpl['vars']) ?></code>
        </div>
        <form method="post">
          <?= Auth::csrfField() ?>
          <input type="hidden" name="mail_key" value="<?= $tab ?>">
          <div class="fg">
            <label>Objet de l'email</label>
            <input type="text" name="subject" value="<?= Helpers::e(Config::get('mail_tpl_subject_'.$tab, getMailDefaultSubject($tab))) ?>">
          </div>
          <div class="fg">
            <label>Corps de l'email (HTML)</label>
            <textarea name="body" rows="16" style="font-family:monospace;font-size:.82rem;line-height:1.6"><?= Helpers::e(Config::get('mail_tpl_body_'.$tab, getMailDefaultBody($tab))) ?></textarea>
          </div>
          <div style="display:flex;justify-content:flex-end">
            <button type="submit" name="save_mail" class="btn btn-primary">💾 Sauvegarder</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Envoi test -->
    <div class="ac">
      <div class="ac-header"><h2>📤 Envoyer un email de test</h2></div>
      <div class="ac-body">
        <form method="post" style="display:flex;gap:.75rem;align-items:flex-end">
          <?= Auth::csrfField() ?>
          <input type="hidden" name="mail_key" value="<?= $tab ?>">
          <div class="fg" style="flex:1;margin:0">
            <label>Email de destination</label>
            <input type="email" name="test_email" value="<?= Helpers::e(Auth::user()['email']) ?>">
          </div>
          <button type="submit" name="send_test" class="btn btn-ghost">📤 Envoyer le test</button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php

function getMailDefaultSubject(string $key): string {
    $club = Config::get('club_name','Mon Club');
    return match($key) {
        'welcome'     => "Bienvenue chez {$club} !",
        'order'       => "Votre commande est confirmée — {$club}",
        'forum_reply' => "Nouvelle réponse à votre sujet — {$club}",
        'gallery_new' => "Nouvelle galerie publiée — {$club}",
        'booking_ok'  => "Réservation confirmée — {$club}",
        'booking_wait'=> "Liste d'attente — {$club}",
        'member_card' => "Votre carte membre — {$club}",
        'reset_pass'  => "Réinitialisation de votre mot de passe — {$club}",
        'newsletter'  => "Actualités de {$club}",
        default       => "Message de {$club}",
    };
}

function getMailDefaultBody(string $key): string {
    return match($key) {
        'welcome'     => "<h2>Bienvenue {firstname} !</h2>\n<p>Votre compte sur <strong>{club_name}</strong> a été créé avec succès.</p>\n<p><a href=\"{verify_url}\">Cliquez ici pour vérifier votre email</a></p>",
        'order'       => "<h2>Commande confirmée ✅</h2>\n<p>Bonjour {firstname}, votre commande #{order_id} pour un total de {total} a bien été reçue.</p>",
        'forum_reply' => "<h2>Nouvelle réponse 💬</h2>\n<p>Bonjour {firstname}, quelqu'un a répondu à votre sujet <strong>{topic_title}</strong>.</p>\n<p><a href=\"{topic_url}\">Voir la réponse</a></p>",
        'gallery_new' => "<h2>Nouvelle galerie 📸</h2>\n<p>Bonjour {firstname}, un nouvel album <strong>{folder_name}</strong> vient d'être publié.</p>\n<p><a href=\"{folder_url}\">Voir les photos</a></p>",
        'booking_ok'  => "<h2>Réservation confirmée ✅</h2>\n<p>Bonjour {firstname}, votre inscription pour <strong>{slot_title}</strong> le {slot_date} est confirmée.</p>",
        'booking_wait'=> "<h2>Liste d'attente ⏳</h2>\n<p>Bonjour {firstname}, vous êtes sur liste d'attente pour <strong>{slot_title}</strong>. Vous serez notifié si une place se libère.</p>",
        'member_card' => "<h2>Votre carte membre 🪪</h2>\n<p>Bonjour {firstname} {lastname}, votre carte de membre #{member_id} de <strong>{club_name}</strong> est disponible dans votre espace personnel.</p>",
        'reset_pass'  => "<h2>Réinitialisation du mot de passe 🔑</h2>\n<p>Bonjour {firstname}, <a href=\"{reset_url}\">cliquez ici</a> pour choisir un nouveau mot de passe. Ce lien expire dans 1 heure.</p>",
        'newsletter'  => "<h2>Actualités de {club_name}</h2>\n<p>Bonjour {firstname},</p>\n<p>Voici les dernières actualités du club.</p>\n<p style=\"font-size:.75em\"><a href=\"{unsubscribe_url}\">Se désabonner</a></p>",
        default       => "<p>Message de {club_name}</p>",
    };
}

$content = ob_get_clean();
include CC_ROOT . '/admin/layout.php';
