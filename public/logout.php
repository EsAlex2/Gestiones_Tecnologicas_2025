<?php
// public/logout.php
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../config/config.php';
logout_user();
header("Location: ".BASE_URL."/index.php?msg=Sesión cerrada&type=info");
exit;
