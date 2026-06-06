<?php
/**
 * ClubCMS — Assistant d'installation
 * session_start() EN PREMIER — c'était le bug des redirections infinies
 */

// TOUJOURS en premier, avant tout output et tout traitement
session_start();

define('INSTALL_MODE', true);
$step  = (int)($_GET['step'] ?? 0);
$error = '';
$success = false;

// ── Si déjà installé, redirige vers le site ───────────────────
if (file_exists(__DIR__ . '/config/config.php') && $step < 5) {
    // Laisse quand même accéder à l'étape 5 (page succès)
}

// ── Traitement POST ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($step === 1) {
        $host = trim($_POST['db_host'] ?? 'localhost');
        $name = trim($_POST['db_name'] ?? '');
        $user = trim($_POST['db_user'] ?? '');
        $pass = $_POST['db_pass'] ?? '';
        $port = (int)($_POST['db_port'] ?? 3306);
        try {
            $pdo = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $sql = file_get_contents(__DIR__ . '/install/schema.sql');
            // Exécute instruction par instruction pour éviter les erreurs multi-requêtes
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                if ($stmt) try { $pdo->exec($stmt); } catch(Exception $e) {}
            }
            $_SESSION['install_db'] = compact('host','name','user','pass','port');
            header('Location: install.php?step=2'); exit;
        } catch (PDOException $e) {
            $error = 'Connexion impossible : ' . htmlspecialchars($e->getMessage());
        }
    }

    elseif ($step === 2) {
        $_SESSION['install_club'] = [
            'club_name'      => trim($_POST['club_name'] ?? 'Mon Club'),
            'club_sport'     => trim($_POST['club_sport'] ?? ''),
            'club_email'     => trim($_POST['club_email'] ?? ''),
            'club_phone'     => trim($_POST['club_phone'] ?? ''),
            'club_address'   => trim($_POST['club_address'] ?? ''),
            'club_city'      => trim($_POST['club_city'] ?? ''),
            'primary_color'  => $_POST['primary_color'] ?? '#1d4ed8',
            'secondary_color'=> $_POST['secondary_color'] ?? '#f59e0b',
            'font_heading'   => $_POST['font_heading'] ?? 'Bebas Neue',
            'font_body'      => $_POST['font_body'] ?? 'DM Sans',
            'timezone'       => $_POST['timezone'] ?? 'Europe/Paris',
            'currency_symbol'=> $_POST['currency_symbol'] ?? '€',
        ];
        // Upload logo
        @mkdir(__DIR__ . '/assets/uploads/logos', 0755, true);
        @mkdir(__DIR__ . '/assets/uploads/heroes', 0755, true);
        if (!empty($_FILES['logo']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png','jpg','jpeg','gif','webp','svg'])) {
                move_uploaded_file($_FILES['logo']['tmp_name'], __DIR__ . '/assets/uploads/logos/logo.'.$ext);
                $_SESSION['install_club']['logo'] = 'assets/uploads/logos/logo.'.$ext;
            }
        }
        if (!empty($_FILES['hero']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['hero']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png','jpg','jpeg','gif','webp'])) {
                move_uploaded_file($_FILES['hero']['tmp_name'], __DIR__ . '/assets/uploads/heroes/hero.'.$ext);
                $_SESSION['install_club']['hero_image'] = 'assets/uploads/heroes/hero.'.$ext;
            }
        }
        header('Location: install.php?step=3'); exit;
    }

    elseif ($step === 3) {
        $email = trim($_POST['admin_email'] ?? '');
        $pass1 = $_POST['admin_pass'] ?? '';
        $pass2 = $_POST['admin_pass2'] ?? '';
        $fname = trim($_POST['admin_firstname'] ?? '');
        $lname = trim($_POST['admin_lastname'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Adresse email invalide.';
        } elseif (strlen($pass1) < 8) {
            $error = 'Le mot de passe doit faire au moins 8 caractères.';
        } elseif ($pass1 !== $pass2) {
            $error = 'Les mots de passe ne correspondent pas.';
        } else {
            $_SESSION['install_admin'] = ['email'=>$email,'pass'=>$pass1,'fname'=>$fname,'lname'=>$lname];
            header('Location: install.php?step=4'); exit;
        }
    }

    elseif ($step === 4) {
        $_SESSION['install_mail'] = [
            'mail_host'       => trim($_POST['mail_host'] ?? ''),
            'mail_port'       => $_POST['mail_port'] ?? '587',
            'mail_user'       => trim($_POST['mail_user'] ?? ''),
            'mail_pass'       => $_POST['mail_pass'] ?? '',
            'mail_from_email' => trim($_POST['mail_from_email'] ?? ''),
            'mail_from_name'  => trim($_POST['mail_from_name'] ?? ''),
            'recaptcha_site'  => trim($_POST['recaptcha_site'] ?? ''),
            'recaptcha_secret'=> trim($_POST['recaptcha_secret'] ?? ''),
            'stripe_public'   => trim($_POST['stripe_public'] ?? ''),
            'stripe_secret'   => trim($_POST['stripe_secret'] ?? ''),
            'paypal_client'   => trim($_POST['paypal_client'] ?? ''),
            'paypal_secret'   => trim($_POST['paypal_secret'] ?? ''),
            'paypal_mode'     => $_POST['paypal_mode'] ?? 'sandbox',
        ];
        header('Location: install.php?step=5'); exit;
    }

    elseif ($step === 5) {
        $db    = $_SESSION['install_db']    ?? [];
        $club  = $_SESSION['install_club']  ?? [];
        $admin = $_SESSION['install_admin'] ?? [];
        $mail  = $_SESSION['install_mail']  ?? [];

        if (empty($db['host']) || empty($admin['email'])) {
            $error = 'Session expirée ou données manquantes. Recommencez depuis l\'étape 1.';
        } else {
            try {
                $pdo = new PDO(
                    "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4",
                    $db['user'], $db['pass'],
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );

                // Config du club
                $configs = array_merge($club, $mail, [
                    'installed'    => '1',
                    'install_date' => date('Y-m-d H:i:s'),
                    'site_version' => '1.0.0',
                ]);
                $stmt = $pdo->prepare("REPLACE INTO cc_config (`key`, `value`, `group`) VALUES (?, ?, ?)");
                foreach ($configs as $k => $v) {
                    $grp = (str_starts_with($k,'mail_')||str_starts_with($k,'recaptcha')||str_starts_with($k,'stripe')||str_starts_with($k,'paypal')) ? 'api' : 'general';
                    $stmt->execute([$k, $v, $grp]);
                }

                // Menu par défaut — identique au fallback du site
                // Récupère les modules activés par défaut dans la BDD
                $defaultModules = $pdo->query("SELECT slug, label FROM cc_modules WHERE enabled=1 ORDER BY slug")->fetchAll(PDO::FETCH_ASSOC);
                $defaultMenu = [
                    ['label'=>'Accueil', 'url'=>'/', 'visible'=>1, 'require_login'=>0, 'children'=>[]],
                ];
                $slugToUrl = [
                    'forum'    => '/forum',
                    'shop'     => '/boutique',
                    'gallery'  => '/galerie',
                    'planning' => '/planning',
                    'members'  => '/annuaire',
                ];
                $slugToLabel = [
                    'forum'    => 'Forum',
                    'shop'     => 'Boutique',
                    'gallery'  => 'Galerie',
                    'planning' => 'Planning',
                    'members'  => 'Annuaire',
                ];
                foreach ($defaultModules as $mod) {
                    $s = $mod['slug'];
                    if (!isset($slugToUrl[$s])) continue;
                    $defaultMenu[] = [
                        'label'        => $slugToLabel[$s] ?? $mod['label'],
                        'url'          => $slugToUrl[$s],
                        'visible'      => 1,
                        'require_login'=> 0,
                        'children'     => [],
                    ];
                }
                $stmt->execute(['menu_items', json_encode($defaultMenu, JSON_UNESCAPED_UNICODE), 'menu']);

                // SuperAdmin (ignore si email déjà existant)
                $hash = password_hash($admin['pass'], PASSWORD_BCRYPT, ['cost'=>12]);
                try {
                    $pdo->prepare("INSERT INTO cc_users (email,password,role,status,firstname,lastname,email_verified) VALUES (?,?,'superadmin','active',?,?,1)")
                        ->execute([$admin['email'],$hash,$admin['fname'],$admin['lname']]);
                } catch (Exception $e) {
                    // Email déjà existant — on met à jour
                    $pdo->prepare("UPDATE cc_users SET password=?,role='superadmin',status='active' WHERE email=?")
                        ->execute([$hash, $admin['email']]);
                }

                // Génère config/config.php
                @mkdir(__DIR__ . '/config', 0755, true);
                $secret = bin2hex(random_bytes(32));
                $tz     = $club['timezone'] ?? 'Europe/Paris';
                $cfg  = "<?php\n";
                $cfg .= "// ClubCMS — Configuration générée le ".date('Y-m-d H:i:s')."\n";
                $cfg .= "if(!defined('CC_ROOT')) define('CC_ROOT', dirname(__DIR__));\n";
                $cfg .= "if(!defined('CC_VERSION')) define('CC_VERSION', '1.0.0');\n";
                $cfg .= "define('DB_HOST', ".var_export($db['host'],true).");\n";
                $cfg .= "define('DB_PORT', ".(int)$db['port'].");\n";
                $cfg .= "define('DB_NAME', ".var_export($db['name'],true).");\n";
                $cfg .= "define('DB_USER', ".var_export($db['user'],true).");\n";
                $cfg .= "define('DB_PASS', ".var_export($db['pass'],true).");\n";
                $cfg .= "define('CC_SECRET', ".var_export($secret,true).");\n";
                $cfg .= "if(!defined('CC_URL')) define('CC_URL', (isset(\$_SERVER['HTTPS'])&&\$_SERVER['HTTPS']==='on'?'https':'http').'://'.(\$_SERVER['HTTP_HOST']??'localhost'));\n";
                $cfg .= "define('TIMEZONE', ".var_export($tz,true).");\n";
                $cfg .= "date_default_timezone_set(TIMEZONE);\n";
                $cfg .= "require_once CC_ROOT.'/core/Database.php';\n";
                $cfg .= "require_once CC_ROOT.'/core/Auth.php';\n";
                $cfg .= "require_once CC_ROOT.'/core/Config.php';\n";
                $cfg .= "require_once CC_ROOT.'/core/Mailer.php';\n";
                $cfg .= "require_once CC_ROOT.'/core/Helpers.php';\n";
                $cfg .= "require_once CC_ROOT.'/core/BlockRenderer.php';\n";
                $cfg .= "require_once CC_ROOT.'/core/CriteriaRenderer.php';\n";
                file_put_contents(__DIR__ . '/config/config.php', $cfg);

                // .htaccess
                $htaccess = "Options -Indexes\nRewriteEngine On\nRewriteRule ^(config|core|pdf)/.*\$ - [F,L]\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule ^(.*)$ index.php?route=\$1 [QSA,L]\n";
                file_put_contents(__DIR__ . '/.htaccess', $htaccess);

                $success = true;
                // Vide la session install
                unset($_SESSION['install_db'],$_SESSION['install_club'],$_SESSION['install_admin'],$_SESSION['install_mail']);

            } catch (Exception $e) {
                $error = 'Erreur lors de l\'installation : ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}

$fonts     = ['Bebas Neue','Oswald','Barlow Condensed','Anton','DM Sans','Inter','Poppins','Nunito','Lato','Roboto'];
$timezones = DateTimeZone::listIdentifiers(DateTimeZone::EUROPE);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>ClubCMS — Installation</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Bebas+Neue&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{--bg:#0a0a0f;--surface:#13131a;--border:#1e1e2e;--text:#e2e8f0;--muted:#64748b;--accent:#3b82f6;--accent2:#f59e0b;--success:#10b981;--error:#ef4444;--r:12px;--font:'DM Sans',sans-serif}
body{background:var(--bg);color:var(--text);font-family:var(--font);min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:2rem}
.wrap{width:100%;max-width:700px}
.logo{font-family:'Bebas Neue',sans-serif;font-size:2.5rem;letter-spacing:.15em;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;text-align:center;margin-bottom:.25rem}
.sub{color:var(--muted);font-size:.9rem;text-align:center;margin-bottom:2rem}
/* Stepper */
.stepper{display:flex;justify-content:center;gap:0;margin-bottom:2rem;position:relative}
.stepper::before{content:'';position:absolute;top:18px;left:5%;right:5%;height:2px;background:var(--border);z-index:0}
.step{display:flex;flex-direction:column;align-items:center;gap:.4rem;position:relative;z-index:1;flex:1}
.step-c{width:36px;height:36px;border-radius:50%;border:2px solid var(--border);background:var(--surface);display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:600;color:var(--muted)}
.step.done .step-c{background:var(--success);border-color:var(--success);color:#fff}
.step.active .step-c{background:var(--accent);border-color:var(--accent);color:#fff;box-shadow:0 0 20px rgba(59,130,246,.4)}
.step-l{font-size:.68rem;color:var(--muted);text-align:center}
.step.active .step-l{color:var(--accent)} .step.done .step-l{color:var(--success)}
/* Card */
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:2rem;margin-bottom:1.5rem}
.card-title{font-size:1.1rem;font-weight:700;margin-bottom:.25rem;display:flex;align-items:center;gap:.5rem}
.card-desc{color:var(--muted);font-size:.875rem;margin-bottom:1.5rem}
/* Form */
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.span2{grid-column:span 2}
.fg{display:flex;flex-direction:column;gap:.35rem}
label{font-size:.78rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.05em}
input[type=text],input[type=email],input[type=password],input[type=number],input[type=url],input[type=tel],select,textarea{width:100%;background:#0d0d14;border:1px solid var(--border);border-radius:8px;color:var(--text);padding:.6rem .9rem;font-family:var(--font);font-size:.875rem;outline:none;transition:border-color .2s}
input:focus,select:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(59,130,246,.15)}
select option{background:#13131a}
.color-row{display:flex;align-items:center;gap:.5rem}
input[type=color]{width:44px;height:38px;border-radius:8px;border:1px solid var(--border);cursor:pointer;padding:2px;background:#0d0d14}
.file-zone{border:2px dashed var(--border);border-radius:8px;padding:1.25rem;text-align:center;cursor:pointer;position:relative;overflow:hidden;transition:border-color .2s}
.file-zone:hover{border-color:var(--accent)}
.file-zone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.file-icon{font-size:1.8rem;margin-bottom:.3rem}
.file-txt{font-size:.82rem;color:var(--muted)}
/* Alerts */
.alert{padding:.75rem 1rem;border-radius:8px;font-size:.875rem;margin-bottom:1rem;display:flex;align-items:flex-start;gap:.5rem}
.err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:#fca5a5}
.info{background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.2);color:#93c5fd}
/* Buttons */
.btn{display:inline-flex;align-items:center;gap:.5rem;padding:.65rem 1.75rem;border-radius:8px;font-weight:600;font-size:.875rem;cursor:pointer;border:none;font-family:var(--font);transition:all .2s;text-decoration:none}
.btn-primary{background:linear-gradient(135deg,var(--accent),#6366f1);color:#fff}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(59,130,246,.3)}
.btn-success{background:linear-gradient(135deg,var(--success),#059669);color:#fff}
.btn-ghost{background:transparent;color:var(--muted);border:1px solid var(--border)}
.btn-ghost:hover{border-color:var(--text);color:var(--text)}
.actions{display:flex;justify-content:space-between;align-items:center;margin-top:1.5rem}
/* Section divider */
.sdiv{height:1px;background:var(--border);margin:1.5rem 0;position:relative}
.sdiv span{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:var(--surface);padding:0 .75rem;font-size:.72rem;color:var(--muted);white-space:nowrap}
/* Badge */
.badge{display:inline-block;background:rgba(59,130,246,.15);color:var(--accent);padding:.12rem .45rem;border-radius:4px;font-size:.68rem;font-weight:700;margin-left:.4rem;vertical-align:middle}
/* Success screen */
.success-banner{text-align:center;background:linear-gradient(135deg,rgba(16,185,129,.15),rgba(59,130,246,.1));border:1px solid rgba(16,185,129,.3);border-radius:var(--r);padding:2.5rem 2rem;margin-bottom:1.5rem}
.ql-grid{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin:1.5rem 0}
.ql-card{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:1rem;text-decoration:none;transition:all .2s;display:block}
.ql-card:hover{border-color:var(--accent);background:rgba(59,130,246,.08)}
.ql-icon{font-size:1.5rem;margin-bottom:.3rem}
.ql-title{font-weight:700;font-size:.875rem;color:var(--text)}
.ql-desc{font-size:.72rem;color:var(--muted);margin-top:.1rem}
/* FAQ */
.faq-item{background:var(--surface);border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:.6rem}
.faq-q{padding:.875rem 1.1rem;font-weight:600;font-size:.875rem;cursor:pointer;display:flex;justify-content:space-between;align-items:center}
.faq-q:hover{background:rgba(255,255,255,.03)}
.faq-arrow{font-size:.7rem;transition:transform .25s;color:var(--muted)}
.faq-a{padding:0 1.1rem 1rem;color:var(--muted);font-size:.835rem;line-height:1.75;display:none}
.faq-a strong{color:var(--text)}
.faq-a code{background:rgba(255,255,255,.08);padding:.1rem .35rem;border-radius:3px;font-size:.85em}
/* Danger */
.danger-box{background:rgba(239,68,68,.1);border:2px solid rgba(239,68,68,.4);border-radius:10px;padding:1.1rem 1.25rem;margin-top:1.25rem}
.danger-box strong{color:#ef4444;font-size:1rem;display:block;margin-bottom:.35rem}
.danger-box p{color:#fca5a5;font-size:.85rem;line-height:1.7;margin:0}
.danger-box code{background:rgba(239,68,68,.2);padding:.1rem .35rem;border-radius:3px}

@media(max-width:600px){.grid2{grid-template-columns:1fr}.span2{grid-column:span 1}.ql-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo">⚡ ClubCMS</div>
  <div class="sub">Assistant d'installation — Configurez votre site en 5 minutes</div>

  <?php if (!$success): ?>
  <!-- Stepper (étape 0 = Vérification) -->
  <div class="stepper">
    <?php foreach (['Vérif.','BDD','Club','Admin','Intégr.','Finaliser'] as $i=>$lbl):
      $n=$i; $cls=$n<$step?'done':($n===$step?'active':'');
    ?>
    <div class="step <?=$cls?>"><div class="step-c"><?=$n<$step?'✓':($n===0?'🔍':$n)?></div><div class="step-l"><?=$lbl?></div></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($error): ?><div class="alert err">⚠️ <?=$error?></div><?php endif; ?>

  <?php if ($step===0 && !$success): ?>
  <!-- ══ ÉTAPE 0 : VÉRIFICATION ENVIRONNEMENT ══ -->
  <?php
  // ── Checks serveur ────────────────────────────────────────────
  $phpVersion     = PHP_VERSION;
  $phpOk          = version_compare($phpVersion, '8.0.0', '>=');
  $pdoOk          = extension_loaded('pdo_mysql');
  $gdOk           = extension_loaded('gd');
  $mbstringOk     = extension_loaded('mbstring');
  $fileinfoOk     = extension_loaded('fileinfo');
  $curlOk         = extension_loaded('curl');
  $configDir      = __DIR__ . '/config';
  $uploadsDir     = __DIR__ . '/assets/uploads';
  @mkdir($configDir, 0755, true);
  @mkdir($uploadsDir, 0755, true);
  $configWritable  = is_writable($configDir) || is_writable(__DIR__);
  $uploadsWritable = is_writable($uploadsDir);

  // Test mod_rewrite : on regarde si Apache signale le module
  $modRewrite = false;
  if (function_exists('apache_get_modules')) {
      $modRewrite = in_array('mod_rewrite', apache_get_modules());
  } elseif (isset($_SERVER['HTTP_MOD_REWRITE'])) {
      $modRewrite = strtolower($_SERVER['HTTP_MOD_REWRITE']) === 'on';
  } elseif (isset($_SERVER['REDIRECT_HTTP_MOD_REWRITE'])) {
      $modRewrite = strtolower($_SERVER['REDIRECT_HTTP_MOD_REWRITE']) === 'on';
  }
  // Détection XAMPP / serveur local
  $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost','127.0.0.1','::1'])
          || str_contains(strtolower(__DIR__), 'xampp')
          || str_contains(strtolower(__DIR__), 'wamp')
          || str_contains(strtolower(__DIR__), 'laragon');

  $extMsg = "<br><span style='display:inline-block;margin-top:.4rem;line-height:1.9'>"
           . "🖥️ <strong>XAMPP</strong> : XAMPP Control Panel → Config → <code>php.ini</code> → retirez le <code>;</code> devant l'extension → redémarrez Apache.<br>"
           . "🌐 <strong>Hébergeur</strong> (OVH, o2switch, Infomaniak…) : panneau de contrôle → <em>Version PHP</em> ou <em>Extensions PHP</em> → activez l'extension.<br>"
           . "❓ Pas d'accès ? Contactez votre hébergeur en demandant d'activer cette extension PHP."
           . "</span>";

  $checks = [
    ['PHP ≥ 8.0', $phpOk, "Version détectée : PHP $phpVersion",
      "Votre serveur utilise PHP $phpVersion. Mettez à jour vers PHP 8.0+. Sur XAMPP : téléchargez une version récente sur apachefriends.org. Sur un hébergeur : changez la version PHP dans votre panneau de contrôle (cPanel → Select PHP Version, Plesk → PHP Settings)."
    ],
    ['Extension PDO MySQL', $pdoOk, "Activée ✓",
      "Nécessaire pour la connexion à la base de données MySQL. " . $extMsg . " (nom de l'extension : <code>pdo_mysql</code>)"
    ],
    ['Extension GD (images)', $gdOk, "Activée ✓",
      "Nécessaire pour le traitement des images (redimensionnement, uploads). " . $extMsg . " (nom : <code>gd</code>) — Activée par défaut chez la plupart des hébergeurs."
    ],
    ['Extension mbstring', $mbstringOk, "Activée ✓",
      "Nécessaire pour la gestion des caractères spéciaux et accents. " . $extMsg . " (nom : <code>mbstring</code>)"
    ],
    ['Extension fileinfo', $fileinfoOk, "Activée ✓",
      "Nécessaire pour détecter le type des fichiers uploadés. " . $extMsg . " (nom : <code>fileinfo</code>)"
    ],
    ['Extension cURL', $curlOk, "Activée ✓",
      "Nécessaire pour les paiements PayPal, le reCAPTCHA et les mises à jour automatiques. " . $extMsg . " (nom : <code>curl</code>) — Activée par défaut chez la plupart des hébergeurs."
    ],
    ['Dossier config/ accessible', $configWritable, "Accessible en écriture ✓",
      "Le dossier <code>config/</code> doit être accessible en écriture. Sur XAMPP : vérifiez que le dossier n'est pas en lecture seule. Sur un hébergeur : via FTP (FileZilla), clic droit sur le dossier → Permissions → mettez <code>755</code>."
    ],
    ['Dossier uploads/ accessible', $uploadsWritable, "Accessible en écriture ✓",
      "Le dossier <code>uploads/</code> doit être accessible en écriture (pour les photos, documents, vidéos…). Via FTP : clic droit → Permissions → <code>755</code>. Sur XAMPP : vérifiez que Windows n'a pas verrouillé le dossier."
    ],
    ['mod_rewrite / URL propres', $modRewrite, "Actif ✓ — Les URLs propres fonctionneront",
      "Nécessaire pour que les URLs du site fonctionnent (/forum, /planning…). "
      . ($isLocal
        ? "Sur XAMPP : <a href='#xampp-fix' style='color:var(--accent)'>suivez le guide ci-dessous →</a>"
        : "Le fichier <code>.htaccess</code> doit être présent à la racine (il est inclus dans le ZIP). Si ça ne fonctionne toujours pas, contactez votre hébergeur en demandant d'activer <strong>AllowOverride All</strong> pour votre domaine."
      )
    ],
  ];

  $criticalFail = !$phpOk || !$pdoOk || !$configWritable;
  $warnings     = !$gdOk || !$mbstringOk || !$fileinfoOk || !$curlOk || !$uploadsWritable;
  $allOk        = $phpOk && $pdoOk && $gdOk && $mbstringOk && $fileinfoOk && $curlOk && $configWritable && $uploadsWritable && $modRewrite;
  ?>

  <div class="card">
    <div class="card-title"><span>🔍</span> Vérification de votre environnement</div>
    <div class="card-desc">ClubCMS vérifie que votre serveur est correctement configuré avant de commencer.</div>

    <div style="display:flex;flex-direction:column;gap:0">
      <?php foreach ($checks as [$label,$ok,$msgOk,$msgFail]): ?>
      <div style="display:flex;align-items:flex-start;gap:.875rem;padding:.75rem 0;border-bottom:1px solid var(--border)">
        <span style="font-size:1.1rem;flex-shrink:0;margin-top:.05rem"><?=$ok?'✅':($label==='mod_rewrite Apache'?'⚠️':'❌')?></span>
        <div style="flex:1">
          <div style="font-weight:600;font-size:.875rem"><?=$label?></div>
          <div style="font-size:.78rem;color:<?=$ok?'var(--success)':($label==='mod_rewrite Apache'?'#f59e0b':'var(--error)')?>">
            <?=$ok?$msgOk:$msgFail?>
          </div>
        </div>
        <span style="font-size:1rem;flex-shrink:0"><?=$ok?'':'→'?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($criticalFail): ?>
    <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);border-radius:8px;padding:1rem;margin-top:1.25rem">
      <strong style="color:var(--error)">❌ Des problèmes critiques empêchent l'installation.</strong><br>
      <span style="font-size:.82rem;color:var(--muted)">Corrigez les éléments marqués ❌ ci-dessus, puis rechargez cette page.</span>
    </div>
    <?php elseif ($allOk): ?>
    <div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);border-radius:8px;padding:1rem;margin-top:1.25rem">
      <strong style="color:var(--success)">✅ Tout est parfait ! Votre serveur est prêt.</strong><br>
      <span style="font-size:.82rem;color:var(--muted)">Cliquez sur "Commencer l'installation" pour configurer votre site.</span>
    </div>
    <?php else: ?>
    <div style="background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);border-radius:8px;padding:1rem;margin-top:1.25rem">
      <strong style="color:var(--accent2)">⚠️ Quelques points à vérifier, mais vous pouvez continuer.</strong><br>
      <span style="font-size:.82rem;color:var(--muted)">L'installation est possible mais certaines fonctionnalités pourraient ne pas fonctionner correctement.</span>
    </div>
    <?php endif; ?>

    <div class="actions" style="margin-top:1.5rem">
      <button type="button" onclick="location.reload()" class="btn btn-ghost">🔄 Revérifier</button>
      <?php if (!$criticalFail): ?>
      <a href="install.php?step=1" class="btn btn-primary">Commencer l'installation →</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!$modRewrite && $isLocal): ?>
  <!-- Guide XAMPP mod_rewrite -->
  <div id="xampp-fix" class="card" style="border-color:rgba(245,158,11,.4)">
    <div class="card-title"><span>🛠️</span> Comment activer mod_rewrite sur XAMPP</div>
    <div class="card-desc">Sans cette configuration, les pages du site (articles, forum, galerie...) retourneront des erreurs 404. Suivez ces 3 étapes — <strong style="color:var(--accent2)">2 minutes</strong> :</div>

    <div style="background:rgba(0,0,0,.3);border-radius:8px;padding:1.1rem;font-size:.82rem;line-height:2;font-family:monospace;margin-bottom:1rem">
      <div style="color:var(--accent2);font-weight:700;margin-bottom:.3rem">ÉTAPE 1 — Ouvrez httpd.conf :</div>
      XAMPP Control Panel → bouton Config (à droite d'Apache) → Apache (httpd.conf)<br>

      <div style="color:var(--accent2);font-weight:700;margin:1rem 0 .3rem">ÉTAPE 2 — Cherchez cette ligne et retirez le # :</div>
      <span style="color:#f87171">#LoadModule rewrite_module modules/mod_rewrite.so</span><br>
      <span style="color:#6ee7b7">→ LoadModule rewrite_module modules/mod_rewrite.so</span><br>

      <div style="color:var(--accent2);font-weight:700;margin:1rem 0 .3rem">ÉTAPE 3 — Cherchez &lt;Directory "C:/xampp/htdocs"&gt; et changez :</div>
      <span style="color:#f87171">AllowOverride None</span><br>
      <span style="color:#6ee7b7">→ AllowOverride All</span>
    </div>

    <div style="background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.2);border-radius:8px;padding:.875rem;font-size:.82rem;color:var(--muted)">
      <strong style="color:var(--text)">Puis :</strong> Sauvegardez httpd.conf →
      XAMPP Control Panel → Stop Apache → Start Apache →
      <a href="install.php?step=0" style="color:var(--accent)">Revenir ici pour vérifier</a>
    </div>
  </div>

  <!-- Guide Hébergeur mutualisé -->
  <div class="card" style="border-color:rgba(99,102,241,.4);margin-top:1rem">
    <div class="card-title"><span>🌐</span> Vous êtes sur un hébergeur mutualisé ? (OVH, o2switch, Infomaniak, Hostinger…)</div>
    <div class="card-desc">Pas de panneau XAMPP ici. Voici comment résoudre chaque problème selon votre hébergeur :</div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:.75rem">
      <div style="background:rgba(0,0,0,.2);border-radius:8px;padding:.875rem;font-size:.82rem;line-height:1.9">
        <div style="color:var(--accent2);font-weight:700;margin-bottom:.5rem">📁 cPanel (OVH mutualisé, HostGator, PlanetHoster…)</div>
        Connectez-vous à votre espace client<br>
        → <strong>cPanel</strong> → rubrique "Logiciels"<br>
        → <strong>Sélectionner une version PHP</strong><br>
        → Choisissez <strong>PHP 8.1 ou 8.2</strong><br>
        → Onglet <strong>Extensions</strong><br>
        → Cochez : <code>pdo_mysql</code>, <code>gd</code>, <code>mbstring</code>, <code>fileinfo</code>, <code>curl</code>, <code>zip</code><br>
        → Cliquez <strong>Enregistrer</strong>
      </div>
      <div style="background:rgba(0,0,0,.2);border-radius:8px;padding:.875rem;font-size:.82rem;line-height:1.9">
        <div style="color:var(--accent2);font-weight:700;margin-bottom:.5rem">⚙️ Plesk (Infomaniak, LWS, Ionos…)</div>
        Espace client → <strong>Plesk</strong><br>
        → Domaines → votre domaine<br>
        → <strong>Paramètres PHP</strong><br>
        → Version PHP : choisissez <strong>8.1+</strong><br>
        → Activez les extensions manquantes<br>
        → Cliquez <strong>Appliquer</strong>
      </div>
      <div style="background:rgba(0,0,0,.2);border-radius:8px;padding:.875rem;font-size:.82rem;line-height:1.9">
        <div style="color:var(--accent2);font-weight:700;margin-bottom:.5rem">🔧 o2switch (hPanel)</div>
        Espace client o2switch<br>
        → <strong>Version PHP multi</strong><br>
        → Sélectionnez PHP 8.1 ou 8.2<br>
        → Activez les extensions nécessaires<br>
        → <strong>Mod_rewrite</strong> : déjà activé par défaut chez o2switch ✅
      </div>
      <div style="background:rgba(0,0,0,.2);border-radius:8px;padding:.875rem;font-size:.82rem;line-height:1.9">
        <div style="color:var(--accent2);font-weight:700;margin-bottom:.5rem">🟣 Hostinger</div>
        Espace client → <strong>Hébergement</strong><br>
        → <strong>Configuration PHP</strong><br>
        → Version : PHP 8.1 ou 8.2<br>
        → Extensions : activez celles manquantes<br>
        → Sauvegardez
      </div>
      <div style="background:rgba(0,0,0,.2);border-radius:8px;padding:.875rem;font-size:.82rem;line-height:1.9">
        <div style="color:var(--accent2);font-weight:700;margin-bottom:.5rem">📄 Via fichier php.ini (tous hébergeurs)</div>
        Créez un fichier <code>php.ini</code> à la racine du site :<br>
        <code style="display:block;background:rgba(0,0,0,.3);padding:.5rem;border-radius:4px;margin-top:.4rem;line-height:1.7">extension=pdo_mysql<br>extension=gd<br>extension=mbstring<br>extension=fileinfo<br>extension=curl<br>extension=zip</code>
      </div>
      <div style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.3);border-radius:8px;padding:.875rem;font-size:.82rem;line-height:1.9">
        <div style="color:var(--accent2);font-weight:700;margin-bottom:.5rem">📞 En dernier recours</div>
        Contactez le support de votre hébergeur avec ce message :<br><br>
        <em style="color:rgba(255,255,255,.7)">"Bonjour, j'installe une application PHP 8.1. J'ai besoin que les extensions suivantes soient activées : pdo_mysql, gd, mbstring, fileinfo, curl, zip. Merci également de confirmer que mod_rewrite est activé."</em>
      </div>
    </div>

    <div style="background:rgba(99,102,241,.1);border:1px solid rgba(99,102,241,.3);border-radius:8px;padding:.875rem;margin-top:1rem;font-size:.82rem">
      <strong>💡 Bon à savoir :</strong> Sur la plupart des hébergeurs modernes (o2switch, Infomaniak, Hostinger), toutes les extensions sont déjà activées par défaut avec PHP 8.1+.
      Si vous avez juste une erreur <strong>mod_rewrite</strong>, vérifiez que le fichier <code>.htaccess</code> est bien présent à la racine du site.
    </div>
  </div>
  <?php endif; ?>

  <?php elseif ($step===1 && !$success): ?>
  <!-- ══ ÉTAPE 1 : BDD ══ -->
  <div class="card">
    <div class="card-title"><span>🗄️</span> Connexion à la base de données</div>
    <div class="card-desc">Créez d'abord une base de données MySQL vide dans votre hébergeur, puis renseignez les informations ci-dessous.</div>
    <form method="post">
      <div class="grid2">
        <div class="fg"><label>Hôte MySQL</label><input type="text" name="db_host" value="localhost" required placeholder="localhost"></div>
        <div class="fg"><label>Port</label><input type="number" name="db_port" value="3306" required></div>
        <div class="fg"><label>Nom de la base *</label><input type="text" name="db_name" required placeholder="clubcms_db"></div>
        <div class="fg"><label>Utilisateur *</label><input type="text" name="db_user" required placeholder="root"></div>
        <div class="fg span2"><label>Mot de passe</label><input type="password" name="db_pass" placeholder="Laissez vide si aucun"></div>
      </div>
      <div class="actions"><a href="install.php?step=0" class="btn btn-ghost">← Retour</a><button type="submit" class="btn btn-primary">Suivant →</button></div>
    </form>
  </div>

  <?php elseif ($step===2 && !$success): ?>
  <!-- ══ ÉTAPE 2 : CLUB ══ -->
  <div class="card">
    <div class="card-title"><span>🏟️</span> Informations du club</div>
    <div class="card-desc">Ces infos apparaîtront sur le site, les emails et la carte membre PDF.</div>
    <form method="post" enctype="multipart/form-data">
      <div class="grid2">
        <div class="fg"><label>Nom du club *</label><input type="text" name="club_name" placeholder="FC Champions" required></div>
        <div class="fg"><label>Sport / Discipline</label><input type="text" name="club_sport" placeholder="Football, Judo..."></div>
        <div class="fg"><label>Email du club</label><input type="email" name="club_email" placeholder="contact@monclub.fr"></div>
        <div class="fg"><label>Téléphone</label><input type="tel" name="club_phone" placeholder="+33 6 12 34 56 78"></div>
        <div class="fg span2"><label>Adresse</label><input type="text" name="club_address" placeholder="12 rue des Champions"></div>
        <div class="fg"><label>Ville</label><input type="text" name="club_city" placeholder="Lyon"></div>
        <div class="fg"><label>Fuseau horaire</label><select name="timezone"><?php foreach($timezones as $tz): ?><option value="<?=$tz?>" <?=$tz==='Europe/Paris'?'selected':''?>><?=$tz?></option><?php endforeach; ?></select></div>
      </div>
      <div class="sdiv"><span>IDENTITÉ VISUELLE</span></div>
      <div class="grid2">
        <div class="fg"><label>Couleur principale</label><div class="color-row"><input type="color" name="primary_color" value="#1d4ed8"><input type="text" value="#1d4ed8" style="flex:1" oninput="this.previousElementSibling.value=this.value" onchange="this.previousElementSibling.value=this.value"></div></div>
        <div class="fg"><label>Couleur secondaire</label><div class="color-row"><input type="color" name="secondary_color" value="#f59e0b"><input type="text" value="#f59e0b" style="flex:1" oninput="this.previousElementSibling.value=this.value"></div></div>
        <div class="fg"><label>Police des titres</label><select name="font_heading"><?php foreach($fonts as $f): ?><option value="<?=$f?>" <?=$f==='Bebas Neue'?'selected':''?>><?=$f?></option><?php endforeach; ?></select></div>
        <div class="fg"><label>Police du texte</label><select name="font_body"><?php foreach($fonts as $f): ?><option value="<?=$f?>" <?=$f==='DM Sans'?'selected':''?>><?=$f?></option><?php endforeach; ?></select></div>
        <div class="fg"><label>Logo du club</label><div class="file-zone"><input type="file" name="logo" accept="image/*"><div class="file-icon">🖼️</div><div class="file-txt">PNG, SVG recommandé</div></div></div>
        <div class="fg"><label>Image bannière (Hero)</label><div class="file-zone"><input type="file" name="hero" accept="image/*"><div class="file-icon">🌄</div><div class="file-txt">Min. 1920px de large</div></div></div>
      </div>
      <div class="actions"><a href="install.php?step=1" class="btn btn-ghost">← Retour</a><button type="submit" class="btn btn-primary">Suivant →</button></div>
    </form>
  </div>

  <?php elseif ($step===3 && !$success): ?>
  <!-- ══ ÉTAPE 3 : ADMIN ══ -->
  <div class="card">
    <div class="card-title"><span>👑</span> Compte Super Administrateur</div>
    <div class="card-desc">Ce compte aura accès à tout : paiements, modules, configuration avancée.</div>
    <form method="post">
      <div class="grid2">
        <div class="fg"><label>Prénom</label><input type="text" name="admin_firstname" placeholder="Jean" required></div>
        <div class="fg"><label>Nom</label><input type="text" name="admin_lastname" placeholder="Dupont" required></div>
        <div class="fg span2"><label>Email de connexion *</label><input type="email" name="admin_email" placeholder="admin@monclub.fr" required></div>
        <div class="fg"><label>Mot de passe *</label><input type="password" name="admin_pass" placeholder="8 caractères minimum" required minlength="8"></div>
        <div class="fg"><label>Confirmer *</label><input type="password" name="admin_pass2" placeholder="Répétez le mot de passe" required></div>
      </div>
      <div class="actions"><a href="install.php?step=2" class="btn btn-ghost">← Retour</a><button type="submit" class="btn btn-primary">Suivant →</button></div>
    </form>
  </div>

  <?php elseif ($step===4 && !$success): ?>
  <!-- ══ ÉTAPE 4 : INTÉGRATIONS ══ -->
  <div class="card">
    <div class="card-title"><span>🔌</span> Emails & Clés API <span class="badge">Optionnel</span></div>
    <div class="card-desc">Tout peut être configuré plus tard depuis l'administration du site.</div>
    <form method="post">
      <div class="sdiv"><span>SMTP — ENVOI D'EMAILS</span></div>
      <div class="grid2">
        <div class="fg"><label>Hôte SMTP</label><input type="text" name="mail_host" placeholder="smtp.monhébergeur.fr"></div>
        <div class="fg"><label>Port SMTP</label><input type="number" name="mail_port" value="587"></div>
        <div class="fg"><label>Utilisateur SMTP</label><input type="email" name="mail_user" placeholder="noreply@monclub.fr"></div>
        <div class="fg"><label>Mot de passe SMTP</label><input type="password" name="mail_pass"></div>
        <div class="fg"><label>Email expéditeur</label><input type="email" name="mail_from_email" placeholder="noreply@monclub.fr"></div>
        <div class="fg"><label>Nom expéditeur</label><input type="text" name="mail_from_name" placeholder="Mon Club"></div>
      </div>
      <div class="sdiv"><span>RECAPTCHA V3 — ANTI-ROBOTS</span></div>
      <div class="grid2">
        <div class="fg"><label>Clé publique (site)</label><input type="text" name="recaptcha_site" placeholder="6Lc..."></div>
        <div class="fg"><label>Clé secrète</label><input type="text" name="recaptcha_secret" placeholder="6Lc..."></div>
      </div>
      <div class="sdiv"><span>STRIPE <span class="badge">PAIEMENT EN LIGNE</span></span></div>
      <div class="grid2">
        <div class="fg"><label>Clé publique Stripe</label><input type="text" name="stripe_public" placeholder="pk_live_..."></div>
        <div class="fg"><label>Clé secrète Stripe</label><input type="text" name="stripe_secret" placeholder="sk_live_..."></div>
      </div>
      <div class="sdiv"><span>PAYPAL</span></div>
      <div class="grid2">
        <div class="fg"><label>Client ID PayPal</label><input type="text" name="paypal_client" placeholder="AYeT..."></div>
        <div class="fg"><label>Secret PayPal</label><input type="text" name="paypal_secret" placeholder="EBW..."></div>
        <div class="fg span2"><label>Mode PayPal</label><select name="paypal_mode"><option value="sandbox">Sandbox (test)</option><option value="live">Live (production)</option></select></div>
      </div>
      <div class="actions"><a href="install.php?step=3" class="btn btn-ghost">← Retour</a><button type="submit" class="btn btn-primary">Finaliser →</button></div>
    </form>
  </div>

  <?php elseif ($step===5 && !$success): ?>
  <!-- ══ ÉTAPE 5 : LANCER ══ -->
  <div class="card">
    <div class="card-title"><span>🚀</span> Lancer l'installation</div>
    <div class="card-desc">Tout est prêt. Cliquez pour créer les fichiers de configuration.</div>
    <div class="alert info">ℹ️ Cette action va créer <code>config/config.php</code> et <code>.htaccess</code>. Le dossier doit être accessible en écriture par Apache.</div>
    <form method="post">
      <div class="actions"><a href="install.php?step=4" class="btn btn-ghost">← Retour</a><button type="submit" class="btn btn-success">✓ Lancer l'installation</button></div>
    </form>
  </div>

  <?php elseif ($success): ?>
  <!-- ══ SUCCÈS ══ -->
  <?php
  // ── Détection mod_rewrite ────────────────────────────────────
  // On teste si Apache a bien passé la requête via mod_rewrite
  // en regardant si REQUEST_URI contient "install.php" (accès direct = pas de rewrite)
  // ou si on est passé via ?route= (rewrite actif)
  $modRewriteOk = !str_contains($_SERVER['REQUEST_URI'] ?? '', 'install.php')
               || function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules());

  // Test plus fiable : essayer d'accéder à rewrite_test.php via une URL réécrite
  // On ne peut pas faire ça en PHP pur côté serveur, on le fait en JS côté client

  // Détection XAMPP
  $isXampp = str_contains(strtolower(PHP_BINARY . ($_SERVER['SERVER_SOFTWARE'] ?? '')), 'xampp')
          || str_contains(strtolower(__DIR__), 'xampp')
          || str_contains(strtolower(__DIR__), 'wamp')
          || str_contains(strtolower(__DIR__), 'laragon');

  // Génération des URLs adaptées
  // Si on a accédé à install.php directement (sans rewrite), les URLs propres ne marcheront pas
  $baseUrl   = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
  $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
  // URL avec index.php?route= (toujours fonctionnel)
  $urlSite   = $baseUrl . $scriptDir . '/index.php?route=home';
  $urlAdmin  = $baseUrl . $scriptDir . '/index.php?route=admin';
  $urlAppear = $baseUrl . $scriptDir . '/index.php?route=admin/config&tab=appearance';
  $urlPages  = $baseUrl . $scriptDir . '/index.php?route=admin/pages&edit=homepage';
  $urlUsers  = $baseUrl . $scriptDir . '/index.php?route=admin/users';
  $urlPlann  = $baseUrl . $scriptDir . '/index.php?route=admin/planning&edit=0';
  ?>

  <!-- Vérifications système -->
  <div class="card" style="margin-bottom:1rem">
    <div class="card-title" style="margin-bottom:1.25rem"><span>🔍</span> Vérification de votre environnement</div>

    <?php
    $checks = [
      [
        'label' => 'Base de données MySQL',
        'ok'    => true,
        'msg'   => 'Connectée et tables créées avec succès.',
        'fix'   => '',
      ],
      [
        'label' => 'Fichier config/config.php',
        'ok'    => file_exists(__DIR__ . '/config/config.php'),
        'msg'   => 'Créé avec succès.',
        'fix'   => 'Le fichier n\'a pas pu être créé. Vérifiez les droits d\'écriture du dossier.',
      ],
      [
        'label' => 'Dossier assets/uploads accessible',
        'ok'    => is_writable(__DIR__ . '/assets/uploads') || @mkdir(__DIR__ . '/assets/uploads', 0755, true),
        'msg'   => 'Accessible en écriture.',
        'fix'   => 'Faites un clic droit sur le dossier assets/uploads dans FileZilla → Permissions → 755.',
      ],
      [
        'label' => 'Extension PHP PDO MySQL',
        'ok'    => extension_loaded('pdo_mysql'),
        'msg'   => 'Activée.',
        'fix'   => 'Activez pdo_mysql dans php.ini.',
      ],
      [
        'label' => 'Extension PHP GD (images)',
        'ok'    => extension_loaded('gd'),
        'msg'   => 'Activée.',
        'fix'   => 'Activez gd dans php.ini (décommentez extension=gd).',
      ],
    ];

    $allOk = true;
    foreach ($checks as $c) {
      if (!$c['ok']) $allOk = false;
      echo '<div style="display:flex;align-items:flex-start;gap:.75rem;padding:.65rem 0;border-bottom:1px solid rgba(255,255,255,.05)">';
      echo '<span style="font-size:1.1rem;flex-shrink:0">'.($c['ok']?'✅':'❌').'</span>';
      echo '<div>';
      echo '<div style="font-weight:600;font-size:.875rem">'.$c['label'].'</div>';
      echo '<div style="font-size:.78rem;color:'.($c['ok']?'var(--success)':'var(--error))').'">';
      echo $c['ok'] ? $c['msg'] : '⚠️ '.$c['fix'];
      echo '</div></div></div>';
    }
    ?>

    <!-- Vérification mod_rewrite via JS (seul moyen fiable) -->
    <div style="display:flex;align-items:flex-start;gap:.75rem;padding:.65rem 0" id="rewrite-check">
      <span style="font-size:1.1rem;flex-shrink:0" id="rewrite-icon">⏳</span>
      <div>
        <div style="font-weight:600;font-size:.875rem">mod_rewrite Apache (URLs propres)</div>
        <div style="font-size:.78rem;color:var(--muted)" id="rewrite-msg">Vérification en cours...</div>
      </div>
    </div>
  </div>

  <!-- Alerte mod_rewrite si XAMPP détecté -->
  <div id="rewrite-ok-block" style="display:none">
    <div class="success-banner">
      <div style="font-size:3rem;margin-bottom:.75rem">🎉</div>
      <div style="font-family:'Bebas Neue',sans-serif;font-size:2.2rem;letter-spacing:.12em;color:var(--success);margin-bottom:.5rem">Tout est prêt !</div>
      <p style="color:var(--muted);font-size:.9rem;margin-bottom:1.5rem">Le site fonctionne avec des URLs propres. Accédez à votre site :</p>
      <div class="ql-grid" id="links-grid">
        <a href="<?=$urlSite?>" class="ql-card" target="_blank"><div class="ql-icon">🌐</div><div class="ql-title">Voir le site</div><div class="ql-desc">Page d'accueil</div></a>
        <a href="<?=$urlAdmin?>" class="ql-card" target="_blank"><div class="ql-icon">⚙️</div><div class="ql-title">Administration</div><div class="ql-desc">Tableau de bord</div></a>
        <a href="<?=$urlPages?>" class="ql-card" target="_blank"><div class="ql-icon">🏠</div><div class="ql-title">Modifier l'accueil</div><div class="ql-desc">Blocs, hero, texte...</div></a>
        <a href="<?=$urlAppear?>" class="ql-card" target="_blank"><div class="ql-icon">🎨</div><div class="ql-title">Thème & couleurs</div><div class="ql-desc">Logo, polices...</div></a>
        <a href="<?=$urlUsers?>" class="ql-card" target="_blank"><div class="ql-icon">👥</div><div class="ql-title">Membres</div><div class="ql-desc">Rôles, licences...</div></a>
        <a href="<?=$urlPlann?>" class="ql-card" target="_blank"><div class="ql-icon">📅</div><div class="ql-title">Planning</div><div class="ql-desc">Créneaux, réservations</div></a>
      </div>
    </div>
  </div>

  <!-- Bloc affiché si mod_rewrite NE FONCTIONNE PAS (XAMPP non configuré) -->
  <div id="rewrite-fail-block" style="display:none">
    <div style="background:rgba(245,158,11,.12);border:2px solid rgba(245,158,11,.5);border-radius:var(--r);padding:1.5rem;margin-bottom:1rem">
      <div style="font-size:1.1rem;font-weight:700;color:var(--accent2);margin-bottom:.75rem">⚠️ Configuration XAMPP incomplète — 3 étapes requises</div>

      <p style="color:var(--muted);font-size:.875rem;margin-bottom:1.25rem">
        La base de données est installée ✅ mais le routage des URLs n'est pas activé.
        Sans cette configuration, les pages articles/forum/galerie etc. retourneront une erreur 404.
      </p>

      <div style="background:rgba(0,0,0,.3);border-radius:8px;padding:1rem;font-size:.82rem;line-height:1.8;font-family:monospace;margin-bottom:1rem">
        <div style="color:var(--accent2);font-weight:700;margin-bottom:.5rem"># ÉTAPE 1 — Ouvrez httpd.conf dans XAMPP :</div>
        XAMPP Control Panel → Config (Apache) → Apache (httpd.conf)<br><br>
        <div style="color:var(--accent2);font-weight:700;margin-bottom:.5rem"># ÉTAPE 2 — Cherchez et décommentez :</div>
        <span style="color:#f87171">avant :</span> #LoadModule rewrite_module modules/mod_rewrite.so<br>
        <span style="color:#6ee7b7">après :</span> LoadModule rewrite_module modules/mod_rewrite.so<br><br>
        <div style="color:var(--accent2);font-weight:700;margin-bottom:.5rem"># ÉTAPE 3 — Cherchez le bloc htdocs et changez :</div>
        &lt;Directory "C:/xampp/htdocs"&gt;<br>
        &nbsp;&nbsp;<span style="color:#f87171">avant :</span> AllowOverride None<br>
        &nbsp;&nbsp;<span style="color:#6ee7b7">après :</span> AllowOverride All<br>
        &lt;/Directory&gt;
      </div>

      <div style="background:rgba(0,0,0,.2);border-radius:8px;padding:.875rem;font-size:.82rem;color:var(--muted);margin-bottom:1rem">
        <strong style="color:var(--text)">Ensuite :</strong> Sauvegardez → Stop Apache → Start Apache dans XAMPP Control Panel<br>
        <strong style="color:var(--text)">Puis :</strong> Revenez sur cette page et cliquez "Re-vérifier" ci-dessous.
      </div>

      <button onclick="checkRewrite()" class="btn btn-primary" style="width:100%">🔄 Re-vérifier après redémarrage Apache</button>
    </div>

    <!-- Les liens fonctionnent quand même avec index.php?route= -->
    <div class="card">
      <div class="card-title" style="margin-bottom:1rem"><span>✅</span> Ce qui fonctionne déjà maintenant</div>
      <p style="color:var(--muted);font-size:.85rem;margin-bottom:1.25rem">
        La base de données et le site de base fonctionnent. Ces liens utilisent le format compatible XAMPP (<code>index.php?route=...</code>) :
      </p>
      <div class="ql-grid">
        <a href="<?=$urlSite?>" class="ql-card" target="_blank"><div class="ql-icon">🌐</div><div class="ql-title">Voir le site</div><div class="ql-desc">index.php?route=home</div></a>
        <a href="<?=$urlAdmin?>" class="ql-card" target="_blank"><div class="ql-icon">⚙️</div><div class="ql-title">Administration</div><div class="ql-desc">index.php?route=admin</div></a>
        <a href="<?=$urlPages?>" class="ql-card" target="_blank"><div class="ql-icon">🏠</div><div class="ql-title">Modifier l'accueil</div><div class="ql-desc">Pages & accueil</div></a>
        <a href="<?=$urlAppear?>" class="ql-card" target="_blank"><div class="ql-icon">🎨</div><div class="ql-title">Thème & couleurs</div><div class="ql-desc">Apparence</div></a>
      </div>
    </div>
  </div>

  <!-- FAQ -->
  <div class="card">
    <div class="card-title" style="margin-bottom:1rem"><span>❓</span> Que faire maintenant ?</div>
    <?php
    $faqs=[
      ['🎨 Changer les couleurs et le logo', 'Admin → Paramètres → Apparence. Couleur principale, secondaire, police, logo et image de bannière.'],
      ['📝 Modifier la page d\'accueil', 'Admin → Pages & accueil → Accueil. Modifiez le hero et ajoutez des blocs : texte, boîtes d\'info, carte Google Maps, galerie, planning, FAQ, HTML libre...'],
      ['👥 Inviter des membres', 'Partagez l\'URL de votre site. Les membres s\'inscrivent eux-mêmes. Gérez-les dans Admin → Membres.'],
      ['📅 Créer un créneau de planning', 'Admin → Planning → "+ Nouveau créneau". Définissez le type, le coach, si une inscription est requise.'],
      ['🛒 Activer la boutique', 'Admin → Boutique → Ajouter un produit. Configurez Stripe/PayPal dans Paramètres → Paiements pour le paiement en ligne.'],
      ['📄 Valider les licences', 'Admin → Licences. Les membres soumettent leur justificatif. Validez ou refusez en un clic.'],
    ];
    foreach($faqs as $i=>[$q,$a]):
    ?>
    <div class="faq-item">
      <div class="faq-q" onclick="tFaq(<?=$i?>)"><?=$q?><span class="faq-arrow" id="fa-<?=$i?>">▼</span></div>
      <div class="faq-a" id="fb-<?=$i?>"><?=$a?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="danger-box">
    <strong>🔴 SÉCURITÉ — Supprimez install.php maintenant</strong>
    <p>Tant que ce fichier existe, n'importe qui peut réinstaller le site et effacer toutes vos données.<br><br>
    <strong style="color:#fff">FileZilla :</strong> Connexion FTP → clic droit sur <code>install.php</code> → Supprimer<br>
    <strong style="color:#fff">cPanel :</strong> Gestionnaire de fichiers → sélectionner <code>install.php</code> → Supprimer</p>
  </div>

  <script>
  function tFaq(i){
    const a=document.getElementById('fb-'+i),ar=document.getElementById('fa-'+i);
    const open=a.style.display!=='block';
    a.style.display=open?'block':'none'; ar.style.transform=open?'rotate(180deg)':'';
  }

  // Vérification mod_rewrite : on tente d'accéder à une URL réécrite
  // Si mod_rewrite fonctionne, rewrite_test.php sera accessible sans passer par index.php
  async function checkRewrite() {
    document.getElementById('rewrite-icon').textContent = '⏳';
    document.getElementById('rewrite-msg').textContent  = 'Vérification en cours...';
    document.getElementById('rewrite-msg').style.color  = 'var(--muted)';

    try {
      // On teste si une URL réécrite (/rewrite_test.php) retourne le bon JSON
      const res = await fetch('rewrite_test.php', {
        headers: { 'X-Rewrite-Test': '1' },
        cache: 'no-store'
      });
      const json = await res.json();

      if (json.rewrite === true) {
        // On vérifie vraiment que ça passe par mod_rewrite en testant une URL réécrite
        try {
          const res2 = await fetch('index.php?route=api/ping', { cache: 'no-store' });
          const j2   = await res2.json();
          // Si ça marche, mod_rewrite fonctionne (ou pas nécessaire car on utilise ?route=)
        } catch(e) {}

        setRewriteOk();
      } else {
        setRewriteFail();
      }
    } catch(e) {
      setRewriteFail();
    }
  }

  function setRewriteOk() {
    document.getElementById('rewrite-icon').textContent = '✅';
    document.getElementById('rewrite-msg').textContent  = 'Actif — les URLs propres fonctionnent.';
    document.getElementById('rewrite-msg').style.color  = 'var(--success)';
    document.getElementById('rewrite-ok-block').style.display   = 'block';
    document.getElementById('rewrite-fail-block').style.display = 'none';
  }

  function setRewriteFail() {
    document.getElementById('rewrite-icon').textContent = '⚠️';
    document.getElementById('rewrite-msg').textContent  = 'Non configuré — les articles/pages retourneront des erreurs 404. Suivez les instructions ci-dessous.';
    document.getElementById('rewrite-msg').style.color  = '#f59e0b';
    document.getElementById('rewrite-ok-block').style.display   = 'none';
    document.getElementById('rewrite-fail-block').style.display = 'block';
  }

  // Test au chargement : est-ce qu'on arrive via mod_rewrite ou pas ?
  // Si REQUEST_URI contient "install.php", on est en accès direct = mod_rewrite peut être off
  // Le test VRAI se fait avec fetch
  window.addEventListener('DOMContentLoaded', () => {
    checkRewrite();
    tFaq(0);
  });
  </script>
  <?php endif; ?>

  <div style="text-align:center;color:var(--muted);font-size:.72rem;margin-top:1rem">ClubCMS v1.0 — Pour tous les clubs et associations sportives</div>
</div>
</body>
</html>
