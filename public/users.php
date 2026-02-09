<?php
// public/users.php - Gestión de usuarios (solo para admin y operadores)
require_once __DIR__ . '/_layout_top.php';
require_operator_or_admin();

$uid = $_SESSION['user_id'];
$current_role = $_SESSION['user_role'];

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'create_user' && is_admin()) {
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? ROLE_CLIENT;

    if (!$first || !$last || !$username || !$email || !$password) {
      redirect_with("/users.php", "Completa todos los campos obligatorios", "warning");
    }

    // Verificar username/email
    $exists = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $exists->execute([$email, $username]);
    if ($exists->fetch()) {
      redirect_with("/users.php", "Email o username ya registrado", "danger");
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $ins = $pdo->prepare("INSERT INTO users (username, first_name, last_name, phone, email, password_hash, role) VALUES (?,?,?,?,?,?,?)");
    $ins->execute([$username, $first, $last, $phone, $email, $hash, $role]);
    redirect_with("/users.php", "Usuario creado exitosamente", "success");

  } elseif ($action === 'update_user') {
    $id = (int) ($_POST['id'] ?? 0);
    $first = trim($_POST['first_name'] ?? '');
    $last = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $role = $_POST['role'] ?? ROLE_CLIENT;

    // Operadores no pueden cambiar roles ni editar admins
    if (is_operator()) {
      // Verificar que el usuario a editar no sea admin
      $user_check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
      $user_check->execute([$id]);
      $user_role = $user_check->fetch()['role'] ?? '';

      if ($user_role === ROLE_ADMIN) {
        redirect_with("/users.php", "No tienes permisos para editar administradores", "danger");
      }

      // Operadores no pueden cambiar roles
      $role = $user_role;
    }

    if ($id <= 0)
      redirect_with("/users.php", "ID inválido", "danger");

    // Verificar que el email/username no esté en uso por otro usuario
    $exists = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR username = ?) AND id != ?");
    $exists->execute([$email, $username, $id]);
    if ($exists->fetch()) {
      redirect_with("/users.php", "Email o username ya está en uso por otro usuario", "danger");
    }

    $upd = $pdo->prepare("UPDATE users SET username=?, first_name=?, last_name=?, phone=?, email=?, role=? WHERE id=?");
    $upd->execute([$username, $first, $last, $phone, $email, $role, $id]);
    redirect_with("/users.php", "Usuario actualizado", "success");

  } elseif ($action === 'change_password') {
    $id = (int) ($_POST['id'] ?? 0);
    $new_password = $_POST['new_password'] ?? '';

    if ($id <= 0 || !$new_password) {
      redirect_with("/users.php", "Datos incompletos", "warning");
    }

    // Operadores no pueden cambiar contraseñas de admins
    if (is_operator()) {
      $user_check = $pdo->prepare("SELECT role FROM users WHERE id = ?");
      $user_check->execute([$id]);
      $user_role = $user_check->fetch()['role'] ?? '';

      if ($user_role === ROLE_ADMIN) {
        redirect_with("/users.php", "No tienes permisos para cambiar contraseñas de administradores", "danger");
      }
    }

    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $upd = $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?");
    $upd->execute([$hash, $id]);
    redirect_with("/users.php", "Contraseña actualizada", "success");
  }
}

// Eliminar usuario (solo admin)
if (isset($_GET['delete']) && is_admin()) {
  $id = (int) $_GET['delete'];

  // No permitir eliminarse a sí mismo
  if ($id == $uid) {
    redirect_with("/users.php", "No puedes eliminar tu propia cuenta", "danger");
  }

  if ($id > 0) {
    $del = $pdo->prepare("DELETE FROM users WHERE id=?");
    $del->execute([$id]);
    redirect_with("/users.php", "Usuario eliminado", "success");
  }
}

// Obtener lista de usuarios
$query = "SELECT id, username, first_name, last_name, phone, email, role, created_at FROM users ORDER BY created_at DESC";
$users = $pdo->query($query)->fetchAll();
?>
<div class="card">
  <div class="header">
    <h2>Gestión de Usuarios</h2>
    <div>
      <span class="badge <?= is_admin() ? 'danger' : 'warning' ?>">
        <?= get_user_role_name($current_role) ?>
      </span>
    </div>
  </div>

  <?php if (is_admin()): ?>
    <!-- Formulario para crear usuarios (solo admin) -->
    <form method="post" class="card" data-validate>
      <h3>Crear Nuevo Usuario</h3>
      <input type="hidden" name="action" value="create_user">
      <div class="form-grid two">
        <input class="input" id="first_name" type="text" name="first_name" placeholder="Nombres" required>
        <input class="input" id="last_name" type="text" name="last_name" placeholder="Apellidos" required>
        <input class="input" id="username" type="text" name="username" placeholder="Username" required>
        <input class="input" id="phone" type="tel" name="phone" placeholder="Teléfono">
        <input class="input" id="email" type="email" name="email" placeholder="Solo se permite correos @gmail.com" required>
        <input class="input" id="password" type="password" name="password" placeholder="Contraseña" required minlength="8"
          maxlength="16">
      </div>
      <div class="form-grid two" style="margin-top:8px;">
        <select class="input" name="role" required>
          <option value="">-- Seleccionar Rol --</option>
          <option value="<?= ROLE_CLIENT ?>">Cliente</option>
          <option value="<?= ROLE_OPERATOR ?>">Operador</option>
          <option value="<?= ROLE_ADMIN ?>">Administrador</option>
        </select>
        <div style="display:flex; align-items:center;">
          <input type="submit" class="button" value="Crear Usuario">
        </div>
      </div>
    </form>
  <?php endif; ?>

  <!-- Lista de usuarios -->
  <div class="card-body" style="margin-top:30px;">
    <h3>Lista de Usuarios</h3>
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Usuario</th>
          <th>Nombres</th>
          <th>Teléfono</th>
          <th>Email</th>
          <th>Rol</th>
          <th>Registro</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
          <tr>
            <td><?= h($user['id']) ?></td>
            <td><?= h($user['username']) ?></td>
            <td><?= h($user['first_name'] . ' ' . $user['last_name']) ?></td>
            <td><?= h($user['phone']) ?></td>
            <td><?= h($user['email']) ?></td>
            <td>
              <span class="badge <?=
                $user['role'] === ROLE_ADMIN ? 'danger' :
                ($user['role'] === ROLE_OPERATOR ? 'warning' : 'info')
                ?>">
                <?= get_user_role_name($user['role']) ?>
              </span>
            </td>
            <td><?= h($user['created_at']) ?></td>
            <td>
              <details>
                <summary class="button ghost">Editar</summary>
                <div style="margin-top:8px; padding:10px; background:var(--bg-alt); border-radius:8px;">
                  <!-- Formulario de edición -->
                  <form method="post" style="display:grid; gap:6px;">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="id" value="<?= h($user['id']) ?>">
                    <input class="input" type="text" name="username" value="<?= h($user['username']) ?>" required>
                    <input class="input" type="text" name="first_name" value="<?= h($user['first_name']) ?>" required>
                    <input class="input" type="text" name="last_name" value="<?= h($user['last_name']) ?>" required>
                    <input class="input" type="tel" name="phone" value="<?= h($user['phone']) ?>">
                    <input class="input" type="email" name="email" value="<?= h($user['email']) ?>" required>

                    <?php if (is_admin()): ?>
                      <select class="input" name="role">
                        <option value="<?= ROLE_CLIENT ?>" <?= $user['role'] === ROLE_CLIENT ? 'selected' : '' ?>>Cliente
                        </option>
                        <option value="<?= ROLE_OPERATOR ?>" <?= $user['role'] === ROLE_OPERATOR ? 'selected' : '' ?>>Operador
                        </option>
                        <option value="<?= ROLE_ADMIN ?>" <?= $user['role'] === ROLE_ADMIN ? 'selected' : '' ?>>Administrador
                        </option>
                      </select>
                    <?php else: ?>
                      <input type="hidden" name="role" value="<?= h($user['role']) ?>">
                      <div style="padding:8px; background:var(--bg); border-radius:4px;">
                        Rol: <?= get_user_role_name($user['role']) ?>
                      </div>
                    <?php endif; ?>

                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                      <button type="submit" class="button">Guardar</button>

                      <!-- Cambiar contraseña -->
                      <button type="button" class="button ghost" onclick="showPasswordForm(<?= h($user['id']) ?>)">
                        Cambiar Contraseña
                      </button>

                      <!-- Eliminar (solo admin y no propio usuario) -->
                      <?php if (is_admin() && $user['id'] != $uid): ?>
                        <a class="button danger" href="users.php?delete=<?= h($user['id']) ?>"
                          onclick="return confirm('¿Estás seguro de eliminar este usuario?')">
                          Eliminar
                        </a>
                      <?php endif; ?>
                    </div>
                  </form>

                  <!-- Formulario para cambiar contraseña (oculto inicialmente) -->
                  <form method="post" id="passwordForm<?= h($user['id']) ?>" style="display:none; margin-top:10px;">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="id" value="<?= h($user['id']) ?>">
                    <input class="input" type="password" name="new_password" placeholder="Nueva contraseña" required
                      minlength="8">
                    <div style="margin-top:6px;">
                      <button type="submit" class="button">Actualizar Contraseña</button>
                      <button type="button" class="button ghost"
                        onclick="hidePasswordForm(<?= h($user['id']) ?>)">Cancelar</button>
                    </div>
                  </form>
                </div>
              </details>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  function showPasswordForm(userId) {
    document.getElementById('passwordForm' + userId).style.display = 'block';
  }

  function hidePasswordForm(userId) {
    document.getElementById('passwordForm' + userId).style.display = 'none';
    document.getElementById('passwordForm' + userId).querySelector('input[name="new_password"]').value = '';
  }
</script>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>

  $(document).ready(function () {

    //validaciones para nombres y Apellidos para la creacion y edicion de usuarios
    $('input[name="first_name"], input[name="last_name"]').on('input', function () {
      var value = $(this).val();
      var cleanValue = value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
      if (value !== cleanValue) {
        $(this).val(cleanValue);
      }
    });

    //validaciones para el campo de telefono para la creacion y edicion de usuarios
    $('input[name="phone"]').on('input', function () {
      var value = $(this).val();
      var cleanValue = value.replace(/[^0-9\s]/g, '');
      if (value !== cleanValue) {
        $(this).val(cleanValue);
      }
    });

    //validaciones para el campo de username para la creacion y edicion de usuarios
    $('input[name="username"]').on('input', function () {
      var value = $(this).val();
      var cleanValue = value.replace(/[^a-zA-Z0-9_.-]/g, '');
      if (value !== cleanValue) {
        $(this).val(cleanValue);
      }
    });

    //validaciones para el campo de email para la creacion y edicion de usuarios
    // Validaciones para los campos de email en los formularios de creación y edición de usuarios
    $('input[name="email"]');
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
  });

</script>