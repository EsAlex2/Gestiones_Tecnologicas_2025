<?php
// public/signup.php - registro con selección de rol
require_once __DIR__ . '/_layout_top.php';
if (is_logged_in()) {
  header("Location: dashboard.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first = trim($_POST['first_name'] ?? '');
  $last = trim($_POST['last_name'] ?? '');
  $username = trim($_POST['username'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $email = strtolower(trim($_POST['email'] ?? ''));
  $password = $_POST['password'] ?? '';
  $role = $_POST['role'] ?? ROLE_CLIENT;

  // Validar que el rol sea válido
  $valid_roles = [ROLE_ADMIN, ROLE_OPERATOR, ROLE_CLIENT];
  if (!in_array($role, $valid_roles)) {
    $role = ROLE_CLIENT;
  }

  if (!$first || !$last || !$username || !$email || !$password) {
    redirect_with("/signup.php", "Completa todos los campos obligatorios", "warning");
  }

  // Si el usuario intenta registrarse como admin u operador, verificar si ya existe un admin
  if ($role === ROLE_ADMIN || $role === ROLE_OPERATOR) {
    $admin_check = $pdo->query("SELECT id FROM users WHERE role IN ('".ROLE_ADMIN."','".ROLE_OPERATOR."') LIMIT 1");
    if ($admin_check->fetch()) {
      redirect_with("/signup.php", "Solo el primer usuario puede registrarse como administrador/operador. Use el rol Cliente.", "danger");
    }
  }

  // verificar username/email
  $exists = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
  $exists->execute([$email, $username]);
  if ($exists->fetch()) {
    redirect_with("/signup.php", "Email o username ya registrado", "danger");
  }


  $hash = password_hash($password, PASSWORD_ARGON2ID);
  $ins = $pdo->prepare("INSERT INTO users (username, first_name, last_name, phone, email, password_hash, role) VALUES (?,?,?,?,?,?,?)");
  $ins->execute([$username, $first, $last, $phone, $email, $hash, $role]);
  
  // Si es el primer usuario, iniciar sesión automáticamente
  if ($role === ROLE_ADMIN || $role === ROLE_OPERATOR) {
    $stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user) {
      login_user($user['id'], $user['first_name'], $role);
      redirect_with("/dashboard.php", "Cuenta creada y sesión iniciada. Bienvenido!", "success");
    }
  }
  
  redirect_with("/index.php", "Cuenta creada. Ya puedes iniciar sesión.", "success");
}
?>
<div class="auth-wrap">
  <div class="card auth-card scale-in">
    <h2>Crear cuenta</h2>
    <p style="color:var(--muted); margin-bottom:14px;">Crea y configura tu cuenta para Gestionar tu inventario.</p>
    <form method="post" data-validate>
      <div class="form-grid">
        <input class="input" type="text" name="first_name" placeholder="Nombres" required maxlength="30">
        <input class="input" type="text" name="last_name" placeholder="Apellidos" required maxlength="30">
        <input class="input" type="text" name="username" placeholder="Username" required maxlength="15">
        <input class="input" type="tel" name="phone" placeholder="Teléfono móvil" required maxlength="11">
        <input class="input" type="email" name="email" placeholder="Correo" required>
        <input class="input" type="password" name="password" placeholder="Contraseña" required minlength="8" maxlength="16">
        
        <!-- Selector de rol -->
        <select class="input" name="role" required>
          <option value="">-- Selecciona tu rol --</option>
          <option value="<?=ROLE_CLIENT?>">Cliente (Usuario normal)</option>
          <option value="<?=ROLE_OPERATOR?>">Operador</option>
          <option value="<?=ROLE_ADMIN?>">Administrador</option>
        </select>
        <div style="grid-column: 1 / -1;">
          <small style="color:var(--muted);">
            <strong>Nota:</strong> Solo el primer usuario del sistema puede registrarse como Administrador u Operador. 
            Los siguientes usuarios deberán usar el rol "Cliente".
          </small>
        </div>
      </div>
      
      <div class="auth-actions" style="margin-top:20px;">
        <a href="index.php" class="button ghost">Volver</a>
        <input type="submit" class="button" value="Crear cuenta →">
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  $(document).ready(function () {
    // Validaciones existentes...
    var nameInputs = $('input[name="first_name"], input[name="last_name"]');
    nameInputs.on('input', function () {
      var value = $(this).val();
      var cleanValue = value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
      if (value !== cleanValue) {
        $(this).val(cleanValue);
      }
    });

    var phoneInput = $('input[name="phone"]');
    phoneInput.on('input', function () {
      var value = $(this).val();
      var cleanValue = value.replace(/[^0-9]/g, '');
      if (value !== cleanValue) {
        $(this).val(cleanValue);
      }
    });

    $('input').on('blur', function () {
      var value = $(this).val();
      $(this).val($.trim(value));
    });

    // Validación de correo Gmail
    $('input[name="email"]').after('<div class="error-message" style="display:none; color:#ff3860; font-size:12px;">Solo se permiten correos @gmail.com</div>');
    $('form[data-validate]').on('submit', function (e) {
      var email = $('input[name="email"]').val().trim();
      var errorMessage = $('.error-message');
      if (!email.endsWith('@gmail.com')) {
        e.preventDefault();
        errorMessage.show();
        $('input[name="email"]').focus().addClass('error');
        return false;
      }
      errorMessage.hide();
      $('input[name="email"]').removeClass('error');
    });
    $('input[name="email"]').on('input', function () {
      var email = $(this).val().trim();
      var errorMessage = $('.error-message');
      if (email !== '' && !email.endsWith('@gmail.com')) {
        $(this).addClass('error');
        errorMessage.show();
      } else {
        $(this).removeClass('error');
        errorMessage.hide();
      }
    });

    // Validación de longitud de contraseña
    $('form[data-validate]').submit(function (e) {
      var password = $('input[name="password"]').val();
      var minLength = 8;
      var maxLength = 16;
      if (password.length < minLength || password.length > maxLength) {
        e.preventDefault();
        alert('La contraseña debe tener entre ' + minLength + ' y ' + maxLength + ' caracteres.');
        $('input[name="password"]').focus();
        return false;
      }
    });
  });
</script>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>

<script>
  // Enfocar el primer campo del formulario al cargar la página
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelector('.auth-card input[name="first_name"]').focus();
  });

  //hash contraseña en el formulario signup.php pero no en el servidor
  document.querySelector('form[data-validate]').addEventListener('submit', function(e)
  {
    e.preventDefault(); // Evitar el envío del formulario por defecto
    const form = e.target;
    const passwordInput = form.querySelector('input[name="password"]');
    const password = passwordInput.value;

    // Hashear la contraseña usando SHA-256
    crypto.subtle.digest('SHA-256', new TextEncoder().encode(password)).then(hashBuffer => {
      const hashArray = Array.from(new Uint8Array(hashBuffer));
      const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');

      // Reemplazar el valor del campo de contraseña con el hash
      passwordInput.value = hashHex;

      // Enviar el formulario
      form.submit();
    });
  });



</script>