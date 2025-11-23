<?php
// public/password_reset_request.php
require_once __DIR__ . '/_layout_top.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim($_POST['email'] ?? ''));
  if (!$email) redirect_with("/password_reset_request.php", "Ingresa tu correo", "warning");
  $stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE email = ? LIMIT 1");
  $stmt->execute([$email]);
  $user = $stmt->fetch();
  if (!$user) redirect_with("/password_reset_request.php", "Si el correo existe recibirás un enlace", "info");

  // generar token y guardar
  $token = bin2hex(random_bytes(32));
  $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hora
  $ins = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?,?,?)");
  $ins->execute([$user['id'], $token, $expires]);

  // intentar enviar correo con PHPMailer si está instalado
  $sent = false; $err = null;
  $resetUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']!='off' ? 'https' : 'http') . "://".$_SERVER['HTTP_HOST'].BASE_URL."/password_reset.php?token={$token}";

  $vendor = __DIR__ . '/../vendor/autoload.php';
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
      $mail->Subject = 'Recuperar contraseña - Inventario';
      $mail->Body = 'Hola '.htmlspecialchars($user['first_name']).',<br><br>Usa este enlace para restablecer tu contraseña (válido 1 hora):<br><a href="'.$resetUrl.'">'.$resetUrl.'</a>';
      $mail->send();
      $sent = true;
    } catch (Exception $e) {
      $err = $e->getMessage();
    }
  }

  if ($sent) redirect_with("/index.php", "Se envió un enlace a tu correo", "success");
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
    <?php if(isset($_SESSION['pr_token_preview'])): ?>
      <div style="margin-top:12px; color:var(--muted); font-size:13px;">
        En entorno local: enlace de prueba (cópialo en tu navegador):<br>
        <code style="word-break:break-all; background:rgba(0,0,0,.12); padding:8px; display:block; margin-top:6px; border-radius:8px;"><?=h($_SESSION['pr_token_preview'])?></code>
      </div>
      <?php unset($_SESSION['pr_token_preview']); endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/_layout_bottom.php'; ?>