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
  header("Location: " . BASE_URL . "/dashboard.php?msg=no_permission&type=danger");
  exit;
}

// Obtener información del usuario objetivo - MODIFICADO: incluir client_id
$target_user = $pdo->prepare("SELECT username, first_name, last_name, email, role, client_id FROM users WHERE id = ?");
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
$catLabels = array_map(function ($r) {
  return $r['cat'];
}, $catData);
$catValues = array_map(function ($r) {
  return (float)$r['val'];
}, $catData);

// Data para gráfico: cantidad de items por categoría
$catCountStmt = $pdo->prepare("SELECT COALESCE(c.name,'Sin categoría') as cat, COUNT(*) as count FROM items i LEFT JOIN categories c ON i.category_id=c.id WHERE i.user_id=? GROUP BY IFNULL(i.category_id,0) ORDER BY count DESC LIMIT 10");
$catCountStmt->execute([$target_user_id]);
$catCountData = $catCountStmt->fetchAll();
$catCountLabels = array_map(function ($r) {
  return $r['cat'];
}, $catCountData);
$catCountValues = array_map(function ($r) {
  return (int)$r['count'];
}, $catCountData);

// Data para gráfico: items por proveedor
$supplierStmt = $pdo->prepare("SELECT COALESCE(s.name,'Sin proveedor') as supplier, COUNT(*) as count, COALESCE(SUM(i.quantity),0) as total_qty FROM items i LEFT JOIN suppliers s ON i.supplier_id=s.id WHERE i.user_id=? GROUP BY IFNULL(i.supplier_id,0) ORDER BY count DESC LIMIT 8");
$supplierStmt->execute([$target_user_id]);
$supplierData = $supplierStmt->fetchAll();
$supplierLabels = array_map(function ($r) {
  return $r['supplier'];
}, $supplierData);
$supplierCounts = array_map(function ($r) {
  return (int)$r['count'];
}, $supplierData);
$supplierQtys = array_map(function ($r) {
  return (int)$r['total_qty'];
}, $supplierData);

// Data para gráfico: distribución de existencias (bajo stock, medio, alto)
// Usando percentiles para determinar los niveles de stock
$stockStmt = $pdo->prepare("
    SELECT 
        quantity
    FROM items 
    WHERE user_id = ? AND quantity > 0
    ORDER BY quantity
");
$stockStmt->execute([$target_user_id]);
$allQuantities = $stockStmt->fetchAll(PDO::FETCH_COLUMN);

// Calcular niveles de stock basados en percentiles
$low_stock = 0;
$medium_stock = 0;
$high_stock = 0;

if (!empty($allQuantities)) {
  $totalItemsWithStock = count($allQuantities);
  $percentile_33 = $allQuantities[floor($totalItemsWithStock * 0.33)];
  $percentile_66 = $allQuantities[floor($totalItemsWithStock * 0.66)];

  foreach ($allQuantities as $qty) {
    if ($qty <= $percentile_33) {
      $low_stock++;
    } elseif ($qty <= $percentile_66) {
      $medium_stock++;
    } else {
      $high_stock++;
    }
  }
}

$stockLabels = ['Stock Bajo', 'Stock Medio', 'Stock Alto'];
$stockValues = [$low_stock, $medium_stock, $high_stock];

// Data para gráfico: valor total por proveedor
$supplierValueStmt = $pdo->prepare("SELECT COALESCE(s.name,'Sin proveedor') as supplier, COALESCE(SUM(i.quantity*i.unit_price),0) as total_value FROM items i LEFT JOIN suppliers s ON i.supplier_id=s.id WHERE i.user_id=? GROUP BY IFNULL(i.supplier_id,0) ORDER BY total_value DESC LIMIT 8");
$supplierValueStmt->execute([$target_user_id]);
$supplierValueData = $supplierValueStmt->fetchAll();
$supplierValueLabels = array_map(function ($r) {
  return $r['supplier'];
}, $supplierValueData);
$supplierValueValues = array_map(function ($r) {
  return (float)$r['total_value'];
}, $supplierValueData);

// Data para gráfico: top 10 items más valiosos
$topItemsStmt = $pdo->prepare("SELECT name, (quantity * unit_price) as total_value FROM items WHERE user_id = ? ORDER BY total_value DESC LIMIT 10");
$topItemsStmt->execute([$target_user_id]);
$topItemsData = $topItemsStmt->fetchAll();
$topItemsLabels = array_map(function ($r) {
  return $r['name'];
}, $topItemsData);
$topItemsValues = array_map(function ($r) {
  return (float)$r['total_value'];
}, $topItemsData);

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
<div class="card-fluid">
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
            <?php foreach ($manageable_users as $user): ?>
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
                            $target_user_data['role'] === ROLE_ADMIN ? 'primary' : ($target_user_data['role'] === ROLE_OPERATOR ? 'info' : 'success')
                            ?>">
          <?= get_user_role_name($target_user_data['role']) ?>
        </span>
      </div>
      <div>
        <strong>Usuario:</strong> <?= h($target_user_data['username']) ?>
      </div>
    </div>
  </div>
  
  <?php
  // Obtener información del cliente empresarial si existe
  $business_client = null;
  if (isset($target_user_data['client_id']) && !empty($target_user_data['client_id'])) {
    $client_stmt = $pdo->prepare("
        SELECT bc.*, bt.name as business_type_name 
        FROM business_clients bc 
        JOIN business_types bt ON bc.business_type_id = bt.id 
        WHERE bc.id = ?
    ");
    $client_stmt->execute([$target_user_data['client_id']]);
    $business_client = $client_stmt->fetch();
  }
  ?>

  <?php if ($business_client): ?>
    <div class="card" style="margin-bottom:15px; background: #8db0e7ff;">
      <h3>📊 Información del Cliente Empresarial</h3>
      <div class="form-grid three">
        <div>
          <strong>Empresa:</strong> <?= h($business_client['business_name']) ?>
        </div>
        <div>
          <strong>Identificador:</strong> <?= h($business_client['business_id']) ?>
        </div>
        <div>
          <strong>Tipo:</strong> <?= h($business_client['business_type_name']) ?>
        </div>
        <div>
          <strong>Contacto:</strong> <?= h($business_client['personal_first_name'] . ' ' . $business_client['personal_last_name']) ?>
        </div>
        <div>
          <strong>Cargo:</strong> <?= h($business_client['business_position']) ?>
        </div>
        <div>
          <strong>Estado:</strong>
          <span class="badge <?= $business_client['status'] === 'active' ? 'success' : 'secondary' ?>">
            <?= $business_client['status'] === 'active' ? 'Activo' : 'Inactivo' ?>
          </span>
        </div>
      </div>
    </div>
  <?php endif; ?>

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
      <div class="stat-value">Bs <?= number_format($sumVal, 2) ?></div>
      <div class="stat-change positive">
        <span>Valoración</span>
      </div>
    </div>
  </div>

  <!-- Sección de Gráficos Mejorada -->
  <div class="charts-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-top: 20px;">

    <!-- Gráfico 1: Valor por Categoría -->
    <div class="card">
      <h3>Valor por Categoría</h3>
      <canvas id="catChart" width="400" height="250"></canvas>
    </div>

    <!-- Gráfico 2: Cantidad de Ítems por Categoría -->
    <div class="card">
      <h3>Ítems por Categoría</h3>
      <canvas id="catCountChart" width="400" height="250"></canvas>
    </div>

    <!-- Gráfico 3: Ítems por Proveedor -->
    <div class="card">
      <h3>Ítems por Proveedor</h3>
      <canvas id="supplierChart" width="400" height="250"></canvas>
    </div>

    <!-- Gráfico 4: Valor por Proveedor -->
    <div class="card">
      <h3>Valor por Proveedor</h3>
      <canvas id="supplierValueChart" width="400" height="250"></canvas>
    </div>
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
  // Colores para gráficos
  const chartColors = {
    primary: 'rgba(30, 64, 175, 0.7)',
    secondary: 'rgba(59, 130, 246, 0.7)',
    success: 'rgba(16, 185, 129, 0.7)',
    danger: 'rgba(239, 68, 68, 0.7)',
    warning: 'rgba(245, 158, 11, 0.7)',
    info: 'rgba(6, 182, 212, 0.7)',
    light: 'rgba(209, 213, 219, 0.7)',
    dark: 'rgba(55, 65, 81, 0.7)'
  };

  const borderColors = {
    primary: 'rgba(30, 64, 175, 1)',
    secondary: 'rgba(59, 130, 246, 1)',
    success: 'rgba(16, 185, 129, 1)',
    danger: 'rgba(239, 68, 68, 1)',
    warning: 'rgba(245, 158, 11, 1)',
    info: 'rgba(6, 182, 212, 1)',
    light: 'rgba(209, 213, 219, 1)',
    dark: 'rgba(55, 65, 81, 1)'
  };

  // Gráfico 1: Valor por Categoría
  const catChart = document.getElementById('catChart');
  if (catChart) {
    new Chart(catChart, {
      type: 'bar',
      data: {
        labels: <?= json_encode($catLabels) ?>,
        datasets: [{
          label: 'Valor ($)',
          data: <?= json_encode($catValues) ?>,
          backgroundColor: Object.values(chartColors),
          borderColor: Object.values(borderColors),
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                return `$${context.raw.toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
              }
            }
          }
        },
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

  // Gráfico 2: Cantidad de Ítems por Categoría
  const catCountChart = document.getElementById('catCountChart');
  if (catCountChart) {
    new Chart(catCountChart, {
      type: 'doughnut',
      data: {
        labels: <?= json_encode($catCountLabels) ?>,
        datasets: [{
          label: 'Cantidad de Ítems',
          data: <?= json_encode($catCountValues) ?>,
          backgroundColor: Object.values(chartColors),
          borderColor: Object.values(borderColors),
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'right',
          }
        }
      }
    });
  }

  // Gráfico 4: Ítems por Proveedor
  const supplierChart = document.getElementById('supplierChart');
  if (supplierChart) {
    new Chart(supplierChart, {
      type: 'bar',
      data: {
        labels: <?= json_encode($supplierLabels) ?>,
        datasets: [{
          label: 'Cantidad de Ítems',
          data: <?= json_encode($supplierCounts) ?>,
          backgroundColor: chartColors.info,
          borderColor: borderColors.info,
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0
            }
          }
        }
      }
    });
  }

  // Gráfico 5: Valor por Proveedor
  const supplierValueChart = document.getElementById('supplierValueChart');
  if (supplierValueChart) {
    new Chart(supplierValueChart, {
      type: 'bar',
      data: {
        labels: <?= json_encode($supplierValueLabels) ?>,
        datasets: [{
          label: 'Valor Total ($)',
          data: <?= json_encode($supplierValueValues) ?>,
          backgroundColor: chartColors.success,
          borderColor: borderColors.success,
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                return `$${context.raw.toLocaleString('es-ES', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
              }
            }
          }
        },
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