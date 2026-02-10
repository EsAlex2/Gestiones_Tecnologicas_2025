<?php
// public/index.php (Login - permite username o email)
require_once __DIR__ . '/_layout_top.php';
if (is_logged_in()) {
  header("Location: dashboard.php");
  exit;
}

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
    login_user($user['id'], $user['first_name'] . ' ' . $user['username'], $user['role'], $user['client_id']);
    redirect_with("/dashboard.php", "Bienvenido de vuelta, {$user['first_name']}!", "success");
  } else {
    redirect_with("/index.php", "Credenciales inválidas", "danger");
  }
}
?>
<div class="auth" style="padding-top: 150px;">
  <div class="card auth-card fade-in">
    <h2>Iniciar sesión</h2>
    <p style="color:var(--muted); margin-bottom:14px;">Ingresa con tu username o correo.</p>
    <form id="loginForm" method="post" data-validate>
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

<script>
  /*
  // Enfocar el primer campo del formulario al cargar la página
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('#loginForm input[name="identifier"]').focus();
  });

  
  //hash contraseña en el formulario index.php pero no en el servidor
  document.querySelector('#loginForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Evitar el envío del formulario por defecto
    const form = e.target;
    const passwordInput = form.querySelector('input[name="password"]');
    const password = passwordInput.value;

    // Hashear la contraseña usando SHA-256
    crypto.subtle.digest('SHA-256', new TextEncoder().encode(password)).then(hashBuffer => {
      // Convertir el ArrayBuffer a una cadena hexadecimal
      const hashArray = Array.from(new Uint8Array(hashBuffer));
      const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');

      // Reemplazar el valor del campo de contraseña con el hash
      passwordInput.value = hashHex;

      // Enviar el formulario
      form.submit();
    });
  });
  */

</script>


