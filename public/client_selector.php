<?php
// public/client_selector.php - Selector de cliente actual
require_once __DIR__ . '/_layout_top.php';
require_admin();

// Obtener lista de clientes empresariales activos
$business_clients = $pdo->query("
    SELECT bc.*, u.username, bt.name as business_type_name
    FROM business_clients bc
    JOIN users u ON bc.user_id = u.id
    JOIN business_types bt ON bc.business_type_id = bt.id
    WHERE bc.status = 'active'
    ORDER BY bc.business_name
")->fetchAll();

// Procesar selección de cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = (int)($_POST['client_id'] ?? 0);
    
    if ($client_id > 0) {
        $_SESSION['current_client_id'] = $client_id;
        redirect_with("/dashboard.php", "Cliente seleccionado exitosamente", "success");
    } else {
        unset($_SESSION['current_client_id']);
        redirect_with("/dashboard.php", "Modo administrador activado", "info");
    }
}

// Obtener cliente actual seleccionado
$current_client_id = $_SESSION['current_client_id'] ?? null;
$current_client = null;
if ($current_client_id) {
    $stmt = $pdo->prepare("
        SELECT bc.*, bt.name as business_type_name
        FROM business_clients bc
        JOIN business_types bt ON bc.business_type_id = bt.id
        WHERE bc.id = ?
    ");
    $stmt->execute([$current_client_id]);
    $current_client = $stmt->fetch();
}
?>
<div class="card">
    <div class="card-header">
        <h2>Selector de Cliente</h2>
        <div>
            <?php if ($current_client): ?>
                <span class="badge success">Cliente Actual: <?= h($current_client['business_name']) ?></span>
            <?php else: ?>
                <span class="badge primary">Modo Administrador</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <h3>Seleccionar Cliente para Trabajar</h3>
        <form method="post">
            <div class="form-grid" style="grid-template-columns: 1fr auto;">
                <select class="input" name="client_id">
                    <option value="0">-- Modo Administrador (ver todos) --</option>
                    <?php foreach($business_clients as $client): ?>
                        <option value="<?= h($client['id']) ?>" <?= $current_client_id == $client['id'] ? 'selected' : '' ?>>
                            <?= h($client['business_name']) ?> - <?= h($client['personal_first_name'] . ' ' . $client['personal_last_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button primary">Seleccionar</button>
            </div>
        </form>
        
        <?php if ($current_client): ?>
        <div style="margin-top: 20px; padding: 15px; background: var(--success-light); border-radius: 8px;">
            <h4>Cliente Actual Seleccionado</h4>
            <div class="form-grid three">
                <div><strong>Empresa:</strong> <?= h($current_client['business_name']) ?></div>
                <div><strong>Contacto:</strong> <?= h($current_client['personal_first_name'] . ' ' . $current_client['personal_last_name']) ?></div>
                <div><strong>Cargo:</strong> <?= h($current_client['business_position']) ?></div>
                <div><strong>Teléfono:</strong> <?= h($current_client['business_phone'] ?: 'N/A') ?></div>
                <div><strong>Email:</strong> <?= h($current_client['business_email'] ?: 'N/A') ?></div>
                <div><strong>Tipo:</strong> <?= h($current_client['business_type_name']) ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:20px;">
        <h3>Clientes Empresariales Disponibles</h3>
        <div class="stats-grid">
            <?php foreach($business_clients as $client): ?>
            <div class="stat-card <?= $current_client_id == $client['id'] ? 'active' : '' ?>">
                <div class="stat-label"><?= h($client['business_name']) ?></div>
                <div class="stat-value"><?= h($client['personal_first_name'] . ' ' . $client['personal_last_name']) ?></div>
                <div class="stat-change">
                    <span><?= h($client['business_position']) ?></span>
                </div>
                <div style="margin-top:8px;">
                    <small style="color:var(--text-muted);"><?= h($client['business_type_name']) ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.stat-card.active {
    border: 2px solid var(--primary);
    background: var(--primary-light);
}
</style>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>