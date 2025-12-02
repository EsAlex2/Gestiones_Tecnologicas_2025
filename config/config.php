<?php
// config/config.php
// Ajusta estas constantes a tu entorno local (XAMPP/MAMP/WAMP).
define('DB_HOST', 'localhost');
define('DB_NAME', 'inventario_v1');
define('DB_USER', 'root');
define('DB_PASS', '');

// URL base (sin la barra final). Ejemplo: http://localhost/gestec/public
define('BASE_URL', '/Gestiones_Tecnologicas_2025/public');

// SMTP (PHPMailer) - configura estos valores para que el sistema envíe correos.
// Si usas Gmail con autenticación moderna, crea una contraseña de aplicación o usa un SMTP relay.
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'usuario@example.com');
define('SMTP_PASS', 'tu_contraseña_smtp');
define('SMTP_FROM', 'no-reply@example.com');
define('SMTP_FROM_NAME', 'Inventario v3');

// Roles de usuario
define('ROLE_ADMIN', 'admin');
define('ROLE_OPERATOR', 'operator');
define('ROLE_CLIENT', 'client');

// Permisos de gestión multi-usuario
define('CAN_MANAGE_ALL_USERS', 'manage_all_users');
define('CAN_MANAGE_CLIENT_USERS', 'manage_client_users');
define('CAN_VIEW_ALL_INVENTORY', 'view_all_inventory');
define('CAN_MANAGE_CLIENT_INVENTORY', 'manage_client_inventory');
?>