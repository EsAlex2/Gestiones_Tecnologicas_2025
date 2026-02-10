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
  'signup.php' => 'Crear Cuenta',
  'perfil.php' => 'Perfil de Usuario',
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
          <img class="logo" src="../assets/images/iuti.png" style="width: auto;">
          <!--<div class="logo">GT</div>-->
          <div class="brand-text">
            <h1>Gestiones Tecnológicas</h1>
            <p>Instituto Universitario de Tecnología Industrial</p>
          </div>
        </div>


        <div class="toolbar">
          <div class="toolbar-left">
            <button class="button ghost" data-theme-toggle title="Cambiar tema">
              ☀️
            </button>
          </div>

          <div class="nav-actions">
            <a class="button ghost" href="dashboard.php">
              <span>Dashboard</span>
            </a>
            <a class="button ghost" href="items.php">
              <span>Inventario</span>
            </a>

            <a class="button ghost" href="perfil.php"><span>Perfil</span></a>

            <!-- Solo mostrar el enlace de Movimientos a Operadores y Administradores -->
            <?php if (is_admin() || is_operator()): ?>
              <div class="dropdown">
                <button class="button ghost">
                  <span>Configuración</span> ▼
                </button>
                <div class="dropdown-menu">
                  <a class="dropdown-item" href="movements.php"><span>Movimientos</span></a>
                  <a class="dropdown-item" href="users.php"><span>Usuarios</span></a>
                  <?php if(is_admin()): ?>
                  <a class="dropdown-item" href="business_clients.php">Clientes Empresariales</a>
                  <a class="dropdown-item" href="client_selector.php">Selector de Clientes</a>
                  <a href="categories.php" class="dropdown-item">Categorías</a>
                  <a href="suppliers.php" class="dropdown-item">Proveedores</a>
                  <div class="dropdown-divider"></div>
                  <a href="#" class="dropdown-item">Ajustes Generales</a>
                  <?php endif; ?>
                </div>
                
              </div>
            <?php endif; ?>

            <a class="button primary" href="logout.php">Cerrar Sesión</a>
          </div>
        </div>
      </header>
    <?php else: ?>
      <div class="auth-wrap"
        style="background-image: url('../assets/images/comunidad.png'); background-repeat: no-repeat; background-position: center; background-size: cover;">
      <?php endif; ?>