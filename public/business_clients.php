<?php
// public/business_clients.php - Gestión de clientes empresariales (solo administradores)
require_once __DIR__ . '/_layout_top.php';
require_admin();

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_business_client') {
        // Datos personales
        $personal_dni = trim($_POST['personal_dni'] ?? '');
        $personal_first_name = trim($_POST['personal_first_name'] ?? '');
        $personal_last_name = trim($_POST['personal_last_name'] ?? '');
        $personal_email = trim($_POST['personal_email'] ?? '');
        $personal_gender = $_POST['personal_gender'] ?? 'other';
        $personal_phone = trim($_POST['personal_phone'] ?? '');

        // Datos empresariales
        $business_id = trim($_POST['business_id'] ?? '');
        $business_name = trim($_POST['business_name'] ?? '');
        $business_phone = trim($_POST['business_phone'] ?? '');
        $business_email = trim($_POST['business_email'] ?? '');
        $business_address = trim($_POST['business_address'] ?? '');
        $business_type_id = (int) ($_POST['business_type_id'] ?? 0);
        $business_position = trim($_POST['business_position'] ?? '');

        // Usuario asociado
        $user_id = (int) ($_POST['user_id'] ?? 0);

        // Validaciones
        $errors = [];
        if (!$personal_dni)
            $errors[] = "DNI es requerido";
        if (!$personal_first_name)
            $errors[] = "Nombres personales son requeridos";
        if (!$personal_last_name)
            $errors[] = "Apellidos personales son requeridos";
        if (!$business_id)
            $errors[] = "Identificador empresarial es requerido";
        if (!$business_name)
            $errors[] = "Nombre de empresa es requerido";
        if (!$business_type_id)
            $errors[] = "Tipo de empresa es requerido";
        if (!$business_position)
            $errors[] = "Cargo en la empresa es requerido";
        if ($user_id <= 0)
            $errors[] = "Usuario asociado es requerido";

        if (!empty($errors)) {
            redirect_with("/business_clients.php", implode(", ", $errors), "danger");
        }

        // Verificar si el DNI ya existe
        $exists = $pdo->prepare("SELECT id FROM business_clients WHERE personal_dni = ?");
        $exists->execute([$personal_dni]);
        if ($exists->fetch()) {
            redirect_with("/business_clients.php", "El DNI ya está registrado", "danger");
        }

        // Verificar si el identificador empresarial ya existe
        $exists = $pdo->prepare("SELECT id FROM business_clients WHERE business_id = ?");
        $exists->execute([$business_id]);
        if ($exists->fetch()) {
            redirect_with("/business_clients.php", "El identificador empresarial ya está registrado", "danger");
        }

        // Verificar si el usuario ya tiene un cliente empresarial
        $exists = $pdo->prepare("SELECT id FROM business_clients WHERE user_id = ?");
        $exists->execute([$user_id]);
        if ($exists->fetch()) {
            redirect_with("/business_clients.php", "El usuario ya tiene un cliente empresarial asociado", "danger");
        }

        try {
            $pdo->beginTransaction();

            // Crear el cliente empresarial
            $ins = $pdo->prepare("INSERT INTO business_clients 
                (user_id, personal_dni, personal_first_name, personal_last_name, personal_email, 
                 personal_gender, personal_phone, business_id, business_name, business_phone, 
                 business_email, business_address, business_type_id, business_position) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $ins->execute([
                $user_id,
                $personal_dni,
                $personal_first_name,
                $personal_last_name,
                $personal_email,
                $personal_gender,
                $personal_phone,
                $business_id,
                $business_name,
                $business_phone,
                $business_email,
                $business_address,
                $business_type_id,
                $business_position
            ]);

            $client_id = $pdo->lastInsertId();

            // Actualizar el usuario con el client_id
            $upd = $pdo->prepare("UPDATE users SET client_id = ? WHERE id = ?");
            $upd->execute([$client_id, $user_id]);

            $pdo->commit();
            redirect_with("/business_clients.php", "Cliente empresarial creado exitosamente", "success");

        } catch (Exception $e) {
            $pdo->rollBack();
            redirect_with("/business_clients.php", "Error al crear cliente empresarial: " . $e->getMessage(), "danger");
        }

    } elseif ($action === 'update_business_client') {
        $id = (int) ($_POST['id'] ?? 0);

        // Datos personales
        $personal_dni = trim($_POST['personal_dni'] ?? '');
        $personal_first_name = trim($_POST['personal_first_name'] ?? '');
        $personal_last_name = trim($_POST['personal_last_name'] ?? '');
        $personal_email = trim($_POST['personal_email'] ?? '');
        $personal_gender = $_POST['personal_gender'] ?? 'other';
        $personal_phone = trim($_POST['personal_phone'] ?? '');

        // Datos empresariales
        $business_id = trim($_POST['business_id'] ?? '');
        $business_name = trim($_POST['business_name'] ?? '');
        $business_phone = trim($_POST['business_phone'] ?? '');
        $business_email = trim($_POST['business_email'] ?? '');
        $business_address = trim($_POST['business_address'] ?? '');
        $business_type_id = (int) ($_POST['business_type_id'] ?? 0);
        $business_position = trim($_POST['business_position'] ?? '');

        if ($id <= 0) {
            redirect_with("/business_clients.php", "ID inválido", "danger");
        }

        // Verificar si el DNI ya existe en otro cliente
        $exists = $pdo->prepare("SELECT id FROM business_clients WHERE personal_dni = ? AND id != ?");
        $exists->execute([$personal_dni, $id]);
        if ($exists->fetch()) {
            redirect_with("/business_clients.php", "El DNI ya está registrado en otro cliente", "danger");
        }

        // Verificar si el identificador empresarial ya existe en otro cliente
        $exists = $pdo->prepare("SELECT id FROM business_clients WHERE business_id = ? AND id != ?");
        $exists->execute([$business_id, $id]);
        if ($exists->fetch()) {
            redirect_with("/business_clients.php", "El identificador empresarial ya está registrado en otro cliente", "danger");
        }

        $upd = $pdo->prepare("UPDATE business_clients SET 
            personal_dni = ?, personal_first_name = ?, personal_last_name = ?, personal_email = ?,
            personal_gender = ?, personal_phone = ?, business_id = ?, business_name = ?,
            business_phone = ?, business_email = ?, business_address = ?, business_type_id = ?,
            business_position = ?, updated_at = NOW() WHERE id = ?");
        $upd->execute([
            $personal_dni,
            $personal_first_name,
            $personal_last_name,
            $personal_email,
            $personal_gender,
            $personal_phone,
            $business_id,
            $business_name,
            $business_phone,
            $business_email,
            $business_address,
            $business_type_id,
            $business_position,
            $id
        ]);

        redirect_with("/business_clients.php", "Cliente empresarial actualizado", "success");

    } elseif ($action === 'toggle_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'active';

        if ($id <= 0) {
            redirect_with("/business_clients.php", "ID inválido", "danger");
        }

        $upd = $pdo->prepare("UPDATE business_clients SET status = ?, updated_at = NOW() WHERE id = ?");
        $upd->execute([$status, $id]);

        $status_text = $status === 'active' ? 'activado' : 'desactivado';
        redirect_with("/business_clients.php", "Cliente $status_text", "success");
    }
}

// Obtener lista de tipos de empresa
$business_types = $pdo->query("SELECT * FROM business_types ORDER BY name")->fetchAll();

// Obtener lista de usuarios sin cliente empresarial
$available_users = $pdo->query("
    SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.role 
    FROM users u 
    LEFT JOIN business_clients bc ON u.id = bc.user_id 
    WHERE bc.id IS NULL AND u.role IN ('" . ROLE_ANALYST . "','" . ROLE_OPERATOR . "')
    ORDER BY u.first_name, u.last_name
")->fetchAll();

// Obtener lista de clientes empresariales
$business_clients = $pdo->query("
    SELECT bc.*, u.username, u.email as user_email, bt.name as business_type_name
    FROM business_clients bc
    JOIN users u ON bc.user_id = u.id
    JOIN business_types bt ON bc.business_type_id = bt.id
    ORDER BY bc.created_at DESC
")->fetchAll();
?>
<div class="card">
    <div class="card-header">
        <h2>Gestión de Clientes Empresariales</h2>
        <div>
            <span class="badge primary"><?= count($business_clients) ?> Clientes</span>
        </div>
    </div>

    <!-- Formulario para crear cliente empresarial -->
    <?php if (!empty($available_users)): ?>
        <form method="post" class="card" data-validate>
            <h3>Nuevo Cliente Empresarial</h3>
            <input type="hidden" name="action" value="create_business_client">

            <div class="form-section">
                <h4>Información Personal del Contacto</h4>
                <div class="form-grid two">
                    <div class="input-group">
                        <label class="input-label">Cedula de Identidad *</label>
                        <input class="input" type="text" name="personal_dni" placeholder="Número de Cedula" required
                            maxlength="20">
                    </div>
                    <div class="input-group">
                        <label class="input-label">Usuario Asociado *</label>
                        <select class="input" name="user_id" required>
                            <option value="">-- Seleccionar Usuario --</option>
                            <?php foreach ($available_users as $user): ?>
                                <option value="<?= h($user['id']) ?>">
                                    <?= h($user['first_name'] . ' ' . $user['last_name']) ?> (<?= h($user['username']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label class="input-label">Nombres *</label>
                        <input class="input" type="text" name="personal_first_name" placeholder="Nombres" required
                            maxlength="50">
                    </div>
                    <div class="input-group">
                        <label class="input-label">Apellidos *</label>
                        <input class="input" type="text" name="personal_last_name" placeholder="Apellidos" required
                            maxlength="50">
                    </div>
                    <div class="input-group">
                        <label class="input-label">Email Personal</label>
                        <input class="input" type="email" name="personal_email" placeholder="correo@personal.com"
                            maxlength="30">
                    </div>

                    <div class="input-group">
                        <label class="input-label">Género *</label>
                        <select class="input" name="personal_gender" required>
                            <option value="" selected>-- Seleccionar Género --</option>
                            <option value="male">Masculino</option>
                            <option value="female">Femenino</option>
                            <option value="other">Prefiero no decirlo</option>
                        </select>
                    </div>
                    <div class="input-group">
                        <label class="input-label">Teléfono Personal</label>
                        <input class="input" type="text" name="personal_phone" placeholder="Número de teléfono" required
                            maxlength="11">
                    </div>
                </div>
            </div>

            <div class="dropdown-divider"></div>

            <div class="form-section" style="margin-top: 20px;">
                <h4>Información Empresarial</h4>
                <div class="form-grid two">
                    <div class="input-group">
                        <label class="input-label">Identificador Empresarial *</label>
                        <input class="input" type="text" name="business_id" placeholder="RUC, NIT, etc." required
                            maxlength="25">
                    </div>
                    <div class="input-group">
                        <label class="input-label">Nombre de la Empresa *</label>
                        <input class="input" type="text" name="business_name" placeholder="Nombre legal de la empresa"
                            required maxlength="200">
                    </div>
                    <div class="input-group">
                        <label class="input-label">Teléfono Empresarial</label>
                        <input class="input" type="text" name="business_phone" placeholder="Teléfono de oficina"
                            maxlength="30">
                    </div>
                    <div class="input-group">
                        <label class="input-label">Email Empresarial</label>
                        <input class="input" type="email" name="business_email" placeholder="correo@empresa.com"
                            maxlength="150">
                    </div>
                    <div class="input-group">
                        <label class="input-label">Tipo de Empresa *</label>
                        <select class="input" name="business_type_id" required>
                            <option value="">-- Seleccionar Tipo --</option>
                            <?php foreach ($business_types as $type): ?>
                                <option value="<?= h($type['id']) ?>"><?= h($type['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <label class="input-label">Cargo en la Empresa *</label>
                        <input class="input" type="text" name="business_position"
                            placeholder="Ej: Gerente, Propietario, etc." required maxlength="100">
                    </div>
                </div>
                <div class="input-group" style="margin-top: 8px;">
                    <label class="input-label">Dirección de la Empresa</label>
                    <textarea class="input" name="business_address" rows="3" placeholder="Dirección completa de la empresa"
                        maxlength="500"></textarea>
                </div>
            </div>

            <div style="margin-top:20px; display:flex; gap:8px; justify-content:flex-end;">
                <input type="submit" class="button primary" value="Crear Cliente Empresarial">
            </div>
        </form>
    <?php else: ?>
        <div class="card" style="text-align: center; padding: 40px;">
            <h3>No hay usuarios disponibles</h3>
            <p style="color: var(--text-muted);">
                Todos los usuarios ya tienen un cliente empresarial asociado o no hay usuarios con rol Cliente/Operador.
            </p>
            <a href="users.php" class="button primary" style="margin-top: 15px;">Gestionar Usuarios</a>
        </div>
    <?php endif; ?>

    <!-- Lista de clientes empresariales -->
    <div class="card" style="margin-top:20px;">
        <h3>Clientes Empresariales Registrados</h3>

        <?php if (empty($business_clients)): ?>
            <div style="text-align:center; padding:40px; color:var(--text-muted);">
                No hay clientes empresariales registrados. Crea el primer cliente ↑
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Empresa</th>
                        <th>Contacto</th>
                        <th>Cedula de Identidad</th>
                        <th>Usuario</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($business_clients as $client): ?>
                        <tr>
                            <td>
                                <strong><?= h($client['business_name']) ?></strong>
                                <br><small style="color:var(--text-muted);"><?= h($client['business_id']) ?></small>
                            </td>
                            <td>
                                <?= h($client['personal_first_name'] . ' ' . $client['personal_last_name']) ?>
                                <br><small style="color:var(--text-muted);"><?= h($client['business_position']) ?></small>
                            </td>
                            <td><?= h($client['personal_dni']) ?></td>
                            <td><?= h($client['username']) ?></td>
                            <td><?= h($client['business_type_name']) ?></td>
                            <td>
                                <span class="badge <?= $client['status'] === 'active' ? 'success' : 'secondary' ?>">
                                    <?= $client['status'] === 'active' ? 'Activo' : 'Inactivo' ?>
                                </span>
                            </td>
                            <td><?= h($client['created_at']) ?></td>
                            <td>
                                <details>
                                    <summary class="button ghost small">Gestionar</summary>
                                    <div style="margin-top:8px; padding:12px; background:var(--bg-alt); border-radius:8px;">
                                        <!-- Formulario de edición -->
                                        <form method="post">
                                            <input type="hidden" name="action" value="update_business_client">
                                            <input type="hidden" name="id" value="<?= h($client['id']) ?>">

                                            <div style="display: grid; gap: 6px;">
                                                <input class="input" type="text" name="personal_dni"
                                                    value="<?= h($client['personal_dni']) ?>" required>
                                                <input class="input" type="text" name="personal_first_name"
                                                    value="<?= h($client['personal_first_name']) ?>" required>
                                                <input class="input" type="text" name="personal_last_name"
                                                    value="<?= h($client['personal_last_name']) ?>" required>
                                                <input class="input" type="email" name="personal_email"
                                                    value="<?= h($client['personal_email']) ?>" required>
                                                <select class="input" name="personal_gender" required>
                                                    <option value="male" <?= $client['personal_gender'] === 'male' ? 'selected' : '' ?>>Masculino</option>
                                                    <option value="female" <?= $client['personal_gender'] === 'female' ? 'selected' : '' ?>>Femenino</option>
                                                    <option value="other" <?= $client['personal_gender'] === 'other' ? 'selected' : '' ?>>Otro</option>
                                                </select>
                                                <input class="input" type="text" name="personal_phone"
                                                    value="<?= h($client['personal_phone']) ?>" required maxlength="11">

                                                <input class="input" type="text" name="business_id"
                                                    value="<?= h($client['business_id']) ?>" required>
                                                <input class="input" type="text" name="business_name"
                                                    value="<?= h($client['business_name']) ?>" required>
                                                <input class="input" type="text" name="business_phone"
                                                    value="<?= h($client['business_phone']) ?>">
                                                <input class="input" type="email" name="business_email"
                                                    value="<?= h($client['business_email']) ?>">
                                                <select class="input" name="business_type_id" required>
                                                    <?php foreach ($business_types as $type): ?>
                                                        <option value="<?= h($type['id']) ?>"
                                                            <?= $client['business_type_id'] == $type['id'] ? 'selected' : '' ?>>
                                                            <?= h($type['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input class="input" type="text" name="business_position"
                                                    value="<?= h($client['business_position']) ?>" required>
                                                <textarea class="input" name="business_address"
                                                    rows="2"><?= h($client['business_address']) ?></textarea>
                                            </div>

                                            <div style="display:flex; gap:6px; margin-top:8px; flex-wrap:wrap;">
                                                <button type="submit" class="button primary small">Actualizar</button>

                                                <!-- Cambiar estado -->
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="id" value="<?= h($client['id']) ?>">
                                                    <input type="hidden" name="status"
                                                        value="<?= $client['status'] === 'active' ? 'inactive' : 'active' ?>">
                                                    <button type="submit"
                                                        class="button <?= $client['status'] === 'active' ? 'warning' : 'success' ?> small">
                                                        <?= $client['status'] === 'active' ? 'Desactivar' : 'Activar' ?>
                                                    </button>
                                                </form>
                                            </div>
                                        </form>
                                    </div>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>

<!-- Validaciones con jQuery -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>

    $(document).ready(function () {
        // Validaciones para los campos de texto
        $('input[name="personal_dni"]').on('input', function () {
            var value = $(this).val();
            var cleanValue = value.replace(/[^0-9\s]/g, '');
            if (value !== cleanValue) {
                $(this).val(cleanValue);
            }
        });
    });

    $(document).ready(function () {
        // Validaciones para los campos de texto
        $('input[name="business_id"]').on('input', function () {
            var value = $(this).val();
            var cleanValue = value.replace(/[^a-zA-Z0-9-\s]/g, '');
            if (value !== cleanValue) {
                $(this).val(cleanValue);
            }
        });
    });

    $(document).ready(function () {
        // Validaciones para los campos de texto
        $('input[name="personal_first_name"], input[name="personal_last_name"], input[name="business_name"]').on('input', function () {
            var value = $(this).val();
            var cleanValue = value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
            if (value !== cleanValue) {
                $(this).val(cleanValue);
            }
        });
    });


    $(document).ready(function () {
        // Validaciones para los campos de texto
        $('input[name="personal_phone"], input[name="business_phone"]').on('input', function () {
            var value = $(this).val();
            var cleanValue = value.replace(/[^0-9]/g, '');
            if (value !== cleanValue) {
                $(this).val(cleanValue);
            }
        });
    });

    $(document).ready(function () {
        // Validaciones para los campos de texto
        $('input[name="business_position"]').on('input', function () {
            var value = $(this).val();
            var cleanValue = value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
            if (value !== cleanValue) {
                $(this).val(cleanValue);
            }
        });
    });

    $(document).ready(function () {
        // Validaciones para los campos de texto
        $('textarea[name="business_address"]').on('input', function () {
            var value = $(this).val();
            var cleanValue = value.replace(/[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑ#\-\.,\s]/g, '');
            if (value !== cleanValue) {
                $(this).val(cleanValue);
            }
        });
    });



    $(document).ready(function () {
        //validaciones para correos, que solo sean correos gmail
        $('input[name="personal_email"], input[name="business_email"]').after('<div class="error-message" style="display:none; color:#ff3860; font-size:12px;">Solo se permiten correos @gmail.com</div>');
        $('form[data-validate]').on('submit', function (e) {
            var email = $('input[name="personal_email"], input[name="business_email"]').val().trim();
            var errorMessage = $('.error-message');
            if (!email.endsWith('@gmail.com')) {
                e.preventDefault();
                errorMessage.show();
                $('input[name="personal_email"], input[name="business_email"]').focus().addClass('error');
                return false;
            }
            errorMessage.hide();
            $('input[name="personal_email"], input[name="business_email"]').removeClass('error');
        });
        $('input[name="personal_email"], input[name="business_email"]').on('input', function () {
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

        $('input[name="personal_email"], input[name="business_email"]').on('input', function () {
            var value = $(this).val();
            var cleanValue = value.replace(/[^@.a-zA-ZáéíóúÁÉÍÓÚñÑ0-9\s]/g, '');
            if (value !== cleanValue) {
                $(this).val(cleanValue);
            }
        });
    });
</script>