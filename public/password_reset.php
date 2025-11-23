<?php
// public/password_reset.php
require_once __DIR__ . '/_layout_top.php';
$token = $_GET['token'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['token'] ?? '';
  $pass = $_POST['password'] ?? '';
  if (!$token || !$pass) redirect_with("/password_reset.php?token=".$token, "Datos faltantes", "warning");
  // validar token
  $stmt = $pdo->prepare("SELECT pr.*, u.email FROM password_resets pr JOIN users u ON pr.user_id=u.id WHERE pr.token=? AND pr.used=0 AND pr.expires_at >= NOW() LIMIT 1");
  $stmt->execute([$token]);
  $row = $stmt->fetch();
  if (!$row) redirect_with("/password_reset.php", "Token inválido o expirado", "danger");
  // actualizar contraseña
  $hash = password_hash($pass, PASSWORD_DEFAULT);
  $pdo->beginTransaction();
  $upd = $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?");
  $upd->execute([$hash, $row['user_id']]);
  $mark = $pdo->prepare("UPDATE password_resets SET used=1 WHERE id=?");
  $mark->execute([$row['id']]);
  $pdo->commit();
  redirect_with("/index.php", "Contraseña actualizada. Ya puedes iniciar sesión.", "success");
}
?>
<div class="auth-wrap">
  <div class="card auth-card">
    <h2>Restablecer contraseña</h2>
    <p style="color:var(--muted); margin-bottom:14px;">Ingresa tu nueva contraseña.</p>
    <form method="post" data-validate>
      <input type="hidden" name="token" value="<?=h($token)?>">
      <input class="input" type="password" name="password" placeholder="Nueva contraseña" required>
      <div style="margin-top:12px; display:flex; gap:8px; justify-content:flex-end;">
        <a href="index.php" class="button ghost">Volver</a>
        <input type="submit" class="button" value="Actualizar contraseña">
      </div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/_layout_bottom.php'; ?>