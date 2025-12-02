<?php
// public/_layout_top.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';

// Determinar el título de la página
$page_titles = [
  'index.php' => 'Iniciar Sesión',
  'dashboard.php' => 'Dashboard',
  'items.php' => 'Gestión de Inventario',
  'users.php' => 'Gestión de Usuarios',
  'movements.php' => 'Movimientos',
  'clients.php' => 'Clientes',
  'categories.php' => 'Categorías',
  'suppliers.php' => 'Proveedores',
  'signup.php' => 'Crear Cuenta'
];

$current_page = basename($_SERVER['PHP_SELF']);
$page_title = $page_titles[$current_page] ?? 'Sistema de Gestión';
?>
<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($page_title) ?> - Gestiones Tecnológicas</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>

<body>
  <div id="toast" class="toast"></div>

  <?php if (is_logged_in()): ?>
    <div class="container">
      <header class="header">
        <div class="brand">
          <div class="logo">GT</div>
          <div class="brand-text">
            <h1>Gestiones Tecnológicas</h1>
            <p>Sistema de Gestión Empresarial</p>
          </div>
        </div>

        <div class="toolbar">
          <div class="toolbar-left">
            <button class="button ghost" data-theme-toggle title="Cambiar tema">
              ☀️
            </button>

            <span class="badge <?=
              is_admin() ? 'primary' : (is_operator() ? 'info' : 'success')
              ?>">
              <?= get_user_role_name($_SESSION['user_role']) ?>
            </span>
          </div>

          <div class="user-info-section">
            <span class="user-info">
              Hola, <strong><?= h($_SESSION['user_name']) ?></strong>
            </span>
          </div>

          <div class="nav-actions">
            <a class="button ghost" href="dashboard.php">
              <span>Dashboard</span>
            </a>
            <a class="button ghost" href="items.php">
              <span>Inventario</span>
            </a>
            <a class="button ghost" href="movements.php">
              <span>Movimientos</span>
            </a>



            <?php if (is_admin()): ?>
              <div class="dropdown">
                <button class="button ghost">
                  <span>Configuración</span> ▼
                </button>
                <div class="dropdown-menu">
                  <a class="dropdown-item" href="business_clients.php">Clientes Empresariales</a>
                  <a href="client_selector.php" class="dropdown-item">Selector de Clientes</a>
                  <?php if (is_admin() || is_operator()): ?>
                    <a class="dropdown-item" href="users.php"><span>Usuarios</span></a>
                  <?php endif; ?>
                  <a href="categories.php" class="dropdown-item">Categorías</a>
                  <a href="suppliers.php" class="dropdown-item">Proveedores</a>
                  <div class="dropdown-divider"></div>
                  <a href="../reports/export_excel.php" class="dropdown-item">Exportar Excel</a>
                  <a href="../reports/export_pdf.php" class="dropdown-item">Exportar PDF</a>
                </div>
              </div>
            <?php endif; ?>

            <a class="button primary" href="logout.php">Cerrar Sesión</a>
          </div>
        </div>
      </header>
    <?php else: ?>
      <div class="auth-wrap">
      <?php endif; ?>