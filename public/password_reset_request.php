<?php
// public/password_reset_request.php
require_once __DIR__ . '/_layout_top.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim($_POST['email'] ?? ''));
  if (!$email)
    redirect_with("/password_reset_request.php", "Ingresa tu correo", "warning");
  $stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE email = ? LIMIT 1");
  $stmt->execute([$email]);
  $user = $stmt->fetch();
  if (!$user)
    redirect_with("/password_reset_request.php", "Si el correo existe recibirás un enlace", "info");

  // generar token y guardar
  $token = bin2hex(random_bytes(32));
  $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hora
  $ins = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?,?,?)");
  $ins->execute([$user['id'], $token, $expires]);

  // intentar enviar correo con PHPMailer si está instalado
  $sent = false;
  $err = null;
  $resetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'] . BASE_URL . "/password_reset.php?token={$token}";

  $vendor = __DIR__ . '/../vendor/autoload.php';

    $subject = "Recuperar contrasenia - " . SMTP_FROM_NAME;
    $accentColor = '#2563eb'; // Un azul profesional (estilo Tailwind/Moderno)

    $htmlBody = "
  <!DOCTYPE html>
  <html>
  <head>
      <meta charset='UTF-8'>
      <style>
          .container { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1f2937; line-height: 1.6; background-color: #f3f4f6; padding: 40px 10px; }
          .card { max-width: 500px; margin: 0 auto; background: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); overflow: hidden; }
          .header { background-color: {$accentColor}; padding: 30px; text-align: center; }
          .content { padding: 40px; }
          .button { display: inline-block; background-color: {$accentColor}; color: #ffffff !important; padding: 14px 28px; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 16px; margin: 20px 0; }
          .footer { padding: 20px; text-align: center; font-size: 12px; color: #6b7280; }
          .token-box { background: #f9fafb; border: 1px dashed #d1d5db; padding: 10px; font-family: monospace; font-size: 12px; word-break: break-all; margin-top: 20px; }
      </style>
  </head>
  <body>
      <div class='container'>
          <div class='card'>
              <div class='header'>
                  <h1 style='color: #ffffff; margin: 0; font-size: 24px;'>Gestiones Tecnológicas</h1>
              </div>
              <div class='content'>
                  <h2 style='margin-top: 0; color: #111827;'>Hola, " . htmlspecialchars($user['first_name']) . "</h2>
                  <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en <strong>" . SMTP_FROM_NAME . "</strong>.</p>
                  <p>Para continuar, haz clic en el botón de abajo:</p>
                  
                  <div style='text-align: center;'>
                      <a href='{$resetUrl}' class='button'>Restablecer mi contraseña</a>
                  </div>

                  <p style='font-size: 14px; color: #4b5563;'>Este enlace es válido por <strong>1 hora</strong>. Si tú no realizaste esta solicitud, puedes ignorar este mensaje; tu contraseña seguirá siendo la misma.</p>
                  
                  <div class='token-box'>
                      <strong>¿El botón no funciona?</strong> Copia este enlace:<br>
                      {$resetUrl}
                  </div>
              </div>
              <div class='footer'>
                  &copy; " . date('Y') . " " . SMTP_FROM_NAME . ". Todos los derechos reservados.
              </div>
          </div>
      </div>
  </body>
  </html>
  ";


  if (file_exists($vendor)) {
    try {
      require $vendor;
      // PHPMailer usage
      $mail = new PHPMailer\PHPMailer\PHPMailer(true);
      $mail->isSMTP();
      $mail->Host = SMTP_HOST;
      $mail->Port = SMTP_PORT;
      $mail->SMTPAuth = true;
      $mail->Username = SMTP_USER;
      $mail->Password = SMTP_PASS;
      $mail->SMTPSecure = 'tls';
      $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
      $mail->addAddress($email, $user['first_name']);
      $mail->isHTML(true);
      $mail->Subject = $subject;
      $mail->Body = $htmlBody;
      $mail->send();
      $sent = true;
    } catch (Exception $e) {
      $err = $e->getMessage();
    }
  }

  if ($sent)
    redirect_with("/index.php", "Se envió un enlace a tu correo", "success");
  // Fallback: mostrar token en pantalla (para pruebas locales) y advertir
  $_SESSION['pr_token_preview'] = $resetUrl;
  redirect_with("/password_reset_request.php", "Sistema no configurado para SMTP. Token generado (ver abajo) — úsalo para probar.", "warning");
}
?>
<div class="auth-wrap">
  <div class="card auth-card">
    <h2>Recuperar contraseña</h2>
    <p style="color:var(--muted); margin-bottom:14px;">Ingresa el correo asociado a tu cuenta.</p>
    <form method="post" data-validate>
      <input class="input" type="email" name="email" placeholder="Tu correo" required>
      <div style="margin-top:12px; display:flex; gap:8px; justify-content:flex-end;">
        <a href="index.php" class="button ghost">Volver</a>
        <input type="submit" class="button" value="Enviar enlace">
      </div>
    </form>
    <?php if (isset($_SESSION['pr_token_preview'])): ?>
      <div style="margin-top:12px; color:var(--muted); font-size:13px;">
        En entorno local: enlace de prueba (cópialo en tu navegador):<br>
        <code
          style="word-break:break-all; background:rgba(0,0,0,.12); padding:8px; display:block; margin-top:6px; border-radius:8px;"><?= h($_SESSION['pr_token_preview']) ?></code>
      </div>
      <?php unset($_SESSION['pr_token_preview']); endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/_layout_bottom.php'; ?>