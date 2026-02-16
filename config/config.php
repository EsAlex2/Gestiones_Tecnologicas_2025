<?php
// config/config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'inventario_v1');
define('DB_USER', 'admin_sql');
define('DB_PASS', 'TuPassword');
define('DB', 'mysql'); // o 'pgsql', 'sqlite', etc.

// URL base (sin la barra final)
define('BASE_URL', '/Gestiones_Tecnologicas_2025/public');

// SMTP (PHPMailer) - configuracion inicial de envio de correos electronicos, solo para gmail como lo indica el programa
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'alexmadrid326@gmail.com');
define('SMTP_PASS', 'tihgngtlkqpuwyzt');
define('SMTP_FROM', 'alexmadrid326@gmail.com');
define('SMTP_FROM_NAME', 'Gestiones Tecnologicas');

// Roles de usuario
define('ROLE_ADMIN', 'admin');
define('ROLE_OPERATOR', 'operator');
define('ROLE_ANALYST', 'analyst');

// Permisos de gestión multi-usuario
define('CAN_MANAGE_ALL_USERS', 'manage_all_users');
define('CAN_MANAGE_CLIENT_USERS', 'manage_client_users');
define('CAN_VIEW_ALL_INVENTORY', 'view_all_inventory');
define('CAN_MANAGE_CLIENT_INVENTORY', 'manage_client_inventory');
?>