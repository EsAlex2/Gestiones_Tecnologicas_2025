<?php
// public/dashboard.php
require_once __DIR__ . '/_layout_top.php';
require_login();

$uid = $_SESSION['user_id'];
$current_role = $_SESSION['user_role'];

// Determinar el usuario objetivo (para admin/operador pueden ver otros usuarios)
$target_user_id = $_GET['user_id'] ?? $uid;

// Verificar permisos
if ($target_user_id != $uid && !can_manage_user_inventory($target_user_id, $pdo)) {
    header("Location: ".BASE_URL."/dashboard.php?msg=no_permission&type=danger");
    exit;
}

// Obtener información del usuario objetivo
$target_user = $pdo->prepare("SELECT username, first_name, last_name, email, role FROM users WHERE id = ?");
$target_user->execute([$target_user_id]);
$target_user_data = $target_user->fetch();

// Stats del usuario objetivo
$totalItems = $pdo->prepare("SELECT COUNT(*) c FROM items WHERE user_id = ?");
$totalItems->execute([$target_user_id]); 
$count = (int)$totalItems->fetch()['c'];

$totalQty = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) s FROM items WHERE user_id = ?");
$totalQty->execute([$target_user_id]); 
$sumQty = (int)$totalQty->fetch()['s'];

$totalValue = $pdo->prepare("SELECT COALESCE(SUM(quantity*unit_price),0) v FROM items WHERE user_id = ?");
$totalValue->execute([$target_user_id]); 
$sumVal = (float)$totalValue->fetch()['v'];

// Data para gráfico: valor por categoría
$catStmt = $pdo->prepare("SELECT COALESCE(c.name,'Sin categoría') as cat, COALESCE(SUM(i.quantity*i.unit_price),0) val FROM items i LEFT JOIN categories c ON i.category_id=c.id WHERE i.user_id=? GROUP BY IFNULL(i.category_id,0) ORDER BY val DESC LIMIT 12");
$catStmt->execute([$target_user_id]); 
$catData = $catStmt->fetchAll();
$catLabels = array_map(function($r){ return $r['cat']; }, $catData);
$catValues = array_map(function($r){ return (float)$r['val']; }, $catData);

// Stats adicionales para admin/operador
$totalUsers = 0;
$userStats = [];
if (is_admin() || is_operator()) {
    $totalUsers = $pdo->query("SELECT COUNT(*) c FROM users")->fetch()['c'];
    
    $roleStats = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetchAll();
    foreach ($roleStats as $stat) {
        $userStats[$stat['role']] = $stat['count'];
    }
    
    // Obtener usuarios gestionables para el selector
    $manageable_users = get_manageable_users($pdo);
}
?>
<div class="card">
    <div class="card-header">
        <div>
            <h2>Dashboard</h2>
            <p style="color:var(--text-muted); margin:0;">
                <?php if ($target_user_id != $uid): ?>
                    Vista del inventario de: <strong><?= h($target_user_data['first_name'] . ' ' . $target_user_data['last_name']) ?></strong>
                    (<?= get_user_role_name($target_user_data['role']) ?>)
                <?php else: ?>
                    Visión general de tu inventario
                <?php endif; ?>
            </p>
        </div>
        
        <?php if (is_admin() || is_operator()): ?>
        <div class="user-selector">
            <form method="get" class="d-flex align-items-center gap-2">
                <select name="user_id" class="input" onchange="this.form.submit()" style="width: auto;">
                    <option value="<?= $uid ?>">Mi Inventario</option>
                    <?php foreach($manageable_users as $user): ?>
                        <?php if ($user['id'] != $uid): ?>
                            <option value="<?= h($user['id']) ?>" <?= $target_user_id == $user['id'] ? 'selected' : '' ?>>
                                <?= h($user['first_name'] . ' ' . $user['last_name']) ?> (<?= get_user_role_name($user['role']) ?>)
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- Información del usuario -->
    <div class="card" style="margin-bottom:15px;">
        <h3>Información de Usuario</h3>
        <div class="form-grid three">
            <div>
                <strong>Nombre:</strong> <?= h($target_user_data['first_name'] . ' ' . $target_user_data['last_name']) ?>
            </div>
            <div>
                <strong>Rol:</strong> 
                <span class="badge <?= 
                    $target_user_data['role'] === ROLE_ADMIN ? 'primary' : 
                    ($target_user_data['role'] === ROLE_OPERATOR ? 'info' : 'success')
                ?>">
                    <?= get_user_role_name($target_user_data['role']) ?>
                </span>
            </div>
            <div>
                <strong>Usuario:</strong> <?= h($target_user_data['username']) ?>
            </div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total de Ítems</div>
            <div class="stat-value"><?= h($count) ?></div>
            <div class="stat-change positive">
                <span>Gestionados</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Existencias Totales</div>
            <div class="stat-value"><?= h($sumQty) ?></div>
            <div class="stat-change positive">
                <span>En inventario</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Valor Total</div>
            <div class="stat-value">$<?= number_format($sumVal, 2) ?></div>
            <div class="stat-change positive">
                <span>Valoración</span>
            </div>
        </div>
    </div>

    <div style="margin-top:18px;" class="card">
        <h3>Valor por Categoría</h3>
        <canvas id="catChart" width="400" height="140"></canvas>
    </div>

    <div style="margin: 25px; padding-top: 15px; display: flex; gap: 12px; flex-wrap: wrap;">
        <a class="button primary" href="items.php?user_id=<?= $target_user_id ?>">
            Gestionar Inventario →
        </a>
        
        <a class="button secondary" href="movements.php?user_id=<?= $target_user_id ?>">
            Ver Movimientos
        </a>
        
        <!-- Botones adicionales según rol -->
        <?php if (is_admin() || is_operator()): ?>
            <a class="button ghost" href="users.php">Gestionar Usuarios</a>
        <?php endif; ?>
        
        <?php if (is_admin()): ?>
            <a class="button ghost" href="../reports/export_excel.php?user_id=<?= $target_user_id ?>">
                Descargar Excel
            </a>
            <a class="button ghost" href="../reports/export_pdf.php?user_id=<?= $target_user_id ?>">
                Descargar PDF
            </a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('catChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($catLabels) ?>,
            datasets: [{ 
                label: 'Valor ($)', 
                data: <?= json_encode($catValues) ?>,
                backgroundColor: 'rgba(30, 64, 175, 0.7)',
                borderColor: 'rgba(30, 64, 175, 1)',
                borderWidth: 1
            }]
        },
        options: { 
            responsive: true, 
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}
</script>