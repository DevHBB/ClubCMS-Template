<?php
/**
 * ClubCMS — Système d'envoi d'emails
 * SMTP natif PHP sans dépendance externe
 */

class Mailer {

    // ── Envoi principal ──────────────────────────────────────────
    public static function send(string $to, string $toName, string $subject, string $bodyHtml): bool {
        // Enregistrer dans la queue BDD
        try {
            Database::insert(
                "INSERT INTO cc_mail_queue (to_email, to_name, subject, body_html) VALUES (?, ?, ?, ?)",
                [$to, $toName, $subject, $bodyHtml]
            );
        } catch(Exception $e) {}

        // Si SMTP configuré → envoi immédiat
        if (Config::get('mail_host')) {
            try {
                return self::sendSmtp($to, $toName, $subject, $bodyHtml);
            } catch(Exception $e) {
                error_log('Mailer SMTP error: ' . $e->getMessage());
                return false;
            }
        }

        // Fallback mail() PHP — silencieux si non configuré
        if (!function_exists('mail')) return false;
        try {
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . Config::get('mail_from_name', 'ClubCMS') . " <" . Config::get('mail_from_email', 'noreply@club.fr') . ">\r\n";
            @mail($to, $subject, $bodyHtml, $headers); // @ supprime l'avertissement SMTP
        } catch(Exception $e) {}
        return false;
    }

    // ── SMTP natif ───────────────────────────────────────────────
    private static function sendSmtp(string $to, string $toName, string $subject, string $bodyHtml): bool {
        $host    = Config::get('mail_host');
        $port    = (int) Config::get('mail_port', 587);
        $user    = Config::get('mail_user');
        $pass    = Config::get('mail_pass');
        $from    = Config::get('mail_from_email');
        $fromName= Config::get('mail_from_name', 'ClubCMS');

        try {
            $errno = 0; $errstr = '';
            $prefix = ($port === 465) ? 'ssl://' : '';
            $socket = fsockopen($prefix . $host, $port, $errno, $errstr, 10);
            if (!$socket) return false;

            $self_response = fgets($socket, 515);

            $send = fn($cmd) => fwrite($socket, $cmd . "\r\n");
            $read = fn() => fgets($socket, 515);

            $send("EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
            while ($line = $read()) { if (substr($line, 3, 1) === ' ') break; }

            if ($port === 587) {
                $send("STARTTLS");
                $read();
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                $send("EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
                while ($line = $read()) { if (substr($line, 3, 1) === ' ') break; }
            }

            $send("AUTH LOGIN");    $read();
            $send(base64_encode($user)); $read();
            $send(base64_encode($pass)); $read();

            $send("MAIL FROM: <$from>"); $read();
            $send("RCPT TO: <$to>");     $read();
            $send("DATA");               $read();

            $boundary = md5(uniqid());
            $message  = self::buildEmail($from, $fromName, $to, $toName, $subject, $bodyHtml, $boundary);

            $send($message . "\r\n.");
            $read();
            $send("QUIT");
            fclose($socket);

            Database::run("UPDATE cc_mail_queue SET status='sent', sent_at=NOW() WHERE to_email=? AND status='pending' ORDER BY id DESC LIMIT 1", [$to]);
            return true;

        } catch (Exception $e) {
            return false;
        }
    }

    private static function buildEmail(string $from, string $fromName, string $to, string $toName, string $subject, string $html, string $boundary): string {
        $lines  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>\r\n";
        $lines .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <$to>\r\n";
        $lines .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $lines .= "MIME-Version: 1.0\r\n";
        $lines .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
        $lines .= "Date: " . date('r') . "\r\n\r\n";
        $lines .= "--$boundary\r\n";
        $lines .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $lines .= strip_tags($html) . "\r\n\r\n";
        $lines .= "--$boundary\r\n";
        $lines .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
        $lines .= $html . "\r\n\r\n";
        $lines .= "--$boundary--";
        return $lines;
    }

    // ── Template HTML ────────────────────────────────────────────
    private static function template(string $title, string $content): string {
        $club    = Config::get('club_name', 'ClubCMS');
        $color   = Config::get('primary_color', '#1d4ed8');
        $logo    = Config::get('logo') ? self::baseUrl() . '/' . Config::get('logo') : '';
        $year    = date('Y');

        $logoHtml = $logo ? "<img src=\"$logo\" alt=\"$club\" style=\"max-height:50px;max-width:180px\">" : "<strong>$club</strong>";

        return "<!DOCTYPE html>
<html lang='fr'>
<head><meta charset='UTF-8'><meta name='viewport' content='width=device-width,initial-scale=1'>
<title>{$title}</title></head>
<body style='margin:0;padding:0;background:#f4f6fa;font-family:Arial,sans-serif'>
<table width='100%' cellpadding='0' cellspacing='0' bgcolor='#f4f6fa'>
<tr><td align='center' style='padding:30px 10px'>
<table width='600' cellpadding='0' cellspacing='0' style='max-width:600px'>
  <!-- Header -->
  <tr><td style='background:{$color};border-radius:12px 12px 0 0;padding:28px 36px;text-align:center'>
    <div style='color:#fff'>{$logoHtml}</div>
  </td></tr>
  <!-- Body -->
  <tr><td style='background:#fff;padding:36px;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb'>
    {$content}
  </td></tr>
  <!-- Footer -->
  <tr><td style='background:#f8fafc;border:1px solid #e5e7eb;border-top:none;border-radius:0 0 12px 12px;padding:20px 36px;text-align:center'>
    <p style='color:#6b7280;font-size:12px;margin:0'>
      © {$year} {$club} — Propulsé par ClubCMS<br>
      <a href='" . self::baseUrl() . "/unsubscribe' style='color:#6b7280'>Se désabonner</a>
    </p>
  </td></tr>
</table>
</td></tr></table>
</body></html>";
    }

    // ── URL de base ──────────────────────────────────────────────
    private static function baseUrl(): string {
        if (defined('CC_URL')) return rtrim(CC_URL, '/');
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        return $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    // ── Templates d'emails ───────────────────────────────────────
    public static function sendWelcome(int $userId, string $email, string $firstname, string $token, bool $needsVerif): void {
        $club = Config::get('club_name', 'Mon Club');
        $baseUrl = defined('CC_URL') ? CC_URL : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST']);
        $url  = $baseUrl . u("/verify-email?token={$token}");
        $name = $firstname ?: 'Nouveau membre';

        $content = "<h2 style='color:#111827;margin-top:0'>Bienvenue chez {$club} ! 🎉</h2>
<p style='color:#374151'>Bonjour <strong>{$name}</strong>,</p>
<p style='color:#374151'>Votre compte a bien été créé.</p>";

        if ($needsVerif) {
            $content .= "<p style='color:#374151'>Pour activer votre compte, cliquez sur le bouton ci-dessous :</p>
<p style='text-align:center;margin:28px 0'>
  <a href='{$url}' style='background:#1d4ed8;color:#fff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:bold'>
    ✅ Vérifier mon adresse email
  </a>
</p>
<p style='color:#6b7280;font-size:13px'>Ce lien est valable 24 heures.</p>";
        } else {
            $content .= "<p style='text-align:center;margin:28px 0'>
  <a href='" . self::baseUrl() . "/login' style='background:#1d4ed8;color:#fff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:bold'>
    🔐 Me connecter
  </a>
</p>";
        }

        self::send($email, $name, "Bienvenue chez {$club} !", self::template("Bienvenue !", $content));
    }

    public static function sendPasswordReset(string $email, string $firstname, string $token): void {
        $url  = self::baseUrl() . "/reset-password?token={$token}";
        $club = Config::get('club_name', 'Mon Club');
        $name = $firstname ?: 'Membre';

        $content = "<h2 style='color:#111827;margin-top:0'>Réinitialisation de mot de passe</h2>
<p style='color:#374151'>Bonjour <strong>{$name}</strong>,</p>
<p style='color:#374151'>Vous avez demandé à réinitialiser votre mot de passe.</p>
<p style='text-align:center;margin:28px 0'>
  <a href='{$url}' style='background:#dc2626;color:#fff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:bold'>
    🔑 Réinitialiser mon mot de passe
  </a>
</p>
<p style='color:#6b7280;font-size:13px'>Ce lien est valable <strong>1 heure</strong>. Si vous n'avez pas fait cette demande, ignorez cet email.</p>";

        self::send($email, $name, "Réinitialisation de votre mot de passe — {$club}", self::template("Mot de passe oublié", $content));
    }

    public static function sendOrderConfirmation(array $order, string $email, string $name): void {
        $club  = Config::get('club_name', 'Mon Club');
        $total = Helpers::price($order['total']);

        $content = "<h2 style='color:#111827;margin-top:0'>Commande confirmée ✅</h2>
<p style='color:#374151'>Bonjour <strong>{$name}</strong>,</p>
<p style='color:#374151'>Votre commande <strong>#{$order['id']}</strong> a bien été reçue.</p>
<p style='color:#374151'><strong>Montant total :</strong> {$total}</p>
<p style='text-align:center;margin:28px 0'>
  <a href='" . self::baseUrl() . "/membre/commandes/{$order['id']}' style='background:#059669;color:#fff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:bold'>
    📦 Voir ma commande
  </a>
</p>";
        self::send($email, $name, "Commande #{$order['id']} confirmée — {$club}", self::template("Confirmation de commande", $content));
    }

    public static function sendForumReply(array $topic, string $email, string $name): void {
        $club = Config::get('club_name', 'Mon Club');
        $url  = self::baseUrl() . "/forum/" . $topic['slug'];

        $content = "<h2 style='color:#111827;margin-top:0'>Nouvelle réponse sur votre topic 💬</h2>
<p style='color:#374151'>Bonjour <strong>{$name}</strong>,</p>
<p style='color:#374151'>Quelqu'un a répondu à votre discussion : <strong>{$topic['title']}</strong></p>
<p style='text-align:center;margin:28px 0'>
  <a href='{$url}' style='background:#7c3aed;color:#fff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:bold'>
    💬 Voir la réponse
  </a>
</p>";
        self::send($email, $name, "Nouvelle réponse — {$topic['title']}", self::template("Réponse forum", $content));
    }

    public static function sendNewGalleryCategory(string $email, string $name, array $folder): void {
        $club = Config::get('club_name', 'Mon Club');
        $url  = self::baseUrl() . "/galerie/" . $folder['slug'];

        $content = "<h2 style='color:#111827;margin-top:0'>Nouvelle galerie publiée 📸</h2>
<p style='color:#374151'>Bonjour <strong>{$name}</strong>,</p>
<p style='color:#374151'>Un nouvel album photos a été ajouté : <strong>{$folder['name']}</strong></p>
<p style='text-align:center;margin:28px 0'>
  <a href='{$url}' style='background:#db2777;color:#fff;padding:14px 28px;border-radius:8px;text-decoration:none;font-weight:bold'>
    📷 Voir la galerie
  </a>
</p>";
        self::send($email, $name, "Nouvelle galerie : {$folder['name']} — {$club}", self::template("Nouvelle galerie", $content));
    }
}
