<?php
// public/index.php (Login - permite username o email)
require_once __DIR__ . '/_layout_top.php';
if (is_logged_in()) { header("Location: dashboard.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $identifier = trim($_POST['identifier'] ?? '');
  $password = $_POST['password'] ?? '';
  if (!$identifier || !$password) {
    redirect_with("/index.php", "Campos incompletos", "warning");
  }
  $stmt = $pdo->prepare("SELECT id, username, first_name, password_hash, role FROM users WHERE email = ? OR username = ? LIMIT 1");
  $stmt->execute([strtolower($identifier), $identifier]);
  $user = $stmt->fetch();
  if ($user && password_verify($password, $user['password_hash'])) {
    login_user($user['id'], $user['first_name'].' '.$user['username'], $user['role'], $user['client_id']);
    redirect_with("/dashboard.php", "Bienvenido de vuelta, {$user['first_name']}!", "success");
  } else {
    redirect_with("/index.php", "Credenciales inválidas", "danger");
  }
}
?>
<div class="auth-wrap">
  <div class="card auth-card fade-in">
    <h2>Iniciar sesión</h2>
    <p style="color:var(--muted); margin-bottom:14px;">Ingresa con tu username o correo.</p>
    <form method="post" data-validate>
      <div class="form-grid">
        <input class="input" type="text" name="identifier" placeholder="Username o Correo" required>
        <input class="input" type="password" name="password" placeholder="Contraseña" required>
      </div>
      <div class="auth-actions" style="margin-top:12px;">
        <a href="signup.php" class="button ghost">Crear cuenta</a>
        <a href="password_reset_request.php" class="button ghost">¿Olvidaste tu contraseña?</a>
        <input type="submit" class="button" value="Entrar →">
      </div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
