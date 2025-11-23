<?php
// public/movements.php - Versión multi-usuario
require_once __DIR__ . '/_layout_top.php';
require_login();

$uid = $_SESSION['user_id'];
$current_role = $_SESSION['user_role'];

// Determinar el usuario objetivo
$target_user_id = $_GET['user_id'] ?? $uid;

// Verificar permisos
if ($target_user_id != $uid && !can_manage_user_inventory($target_user_id, $pdo)) {
    header("Location: ".BASE_URL."/movements.php?msg=no_permission&type=danger");
    exit;
}

// Obtener información del usuario objetivo
$target_user = $pdo->prepare("SELECT username, first_name, last_name, email, role FROM users WHERE id = ?");
$target_user->execute([$target_user_id]);
$target_user_data = $target_user->fetch();

$stmt = $pdo->prepare("SELECT m.*, i.name as item_name FROM movements m JOIN items i ON m.item_id=i.id WHERE m.user_id=? ORDER BY m.created_at DESC LIMIT 500");
$stmt->execute([$target_user_id]);
$rows = $stmt->fetchAll();

// Obtener usuarios gestionables para el selector
if (is_admin() || is_operator()) {
    $manageable_users = get_manageable_users($pdo);
}
?>
<div class="card">
    <div class="card-header">
        <div>
            <h2>Historial de Movimientos</h2>
            <p style="color:var(--text-muted); margin:0;">
                <?php if ($target_user_id != $uid): ?>
                    Movimientos de: <strong><?= h($target_user_data['first_name'] . ' ' . $target_user_data['last_name']) ?></strong>
                <?php else: ?>
                    Últimos movimientos de entrada/salida
                <?php endif; ?>
            </p>
        </div>
        
        <?php if (is_admin() || is_operator()): ?>
        <div class="user-selector">
            <form method="get" class="d-flex align-items-center gap-2">
                <select name="user_id" class="input" onchange="this.form.submit()" style="width: auto;">
                    <option value="<?= $uid ?>">Mis Movimientos</option>
                    <?php foreach($manageable_users as $user): ?>
                        <?php if ($user['id'] != $uid): ?>
                            <option value="<?= h($user['id']) ?>" <?= $target_user_id == $user['id'] ? 'selected' : '' ?>>
                                <?= h($user['first_name'] . ' ' . $user['last_name']) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Item</th>
                <th>Tipo</th>
                <th>Cantidad</th>
                <th>Proveedor</th>
                <th>Cliente</th>
                <th>Nota</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($rows as $r): ?>
                <tr>
                    <td><?= h($r['id']) ?></td>
                    <td><?= h($r['item_name']) ?></td>
                    <td>
                        <span class="badge <?= $r['type'] === 'in' ? 'success' : 'danger' ?>">
                            <?= $r['type'] === 'in' ? 'ENTRADA' : 'SALIDA' ?>
                        </span>
                    </td>
                    <td><?= h($r['quantity']) ?></td>
                    <td><?= h($r['supplier_name'] ?? '-') ?></td>
                    <td><?= h($r['client_name'] ?? '-') ?></td>
                    <td><?= h($r['note']) ?></td>
                    <td><?= h($r['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            
            <?php if(empty($rows)): ?>
                <tr>
                    <td colspan="8" style="color:var(--text-muted); text-align:center; padding:40px;">
                        Sin movimientos aún
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>