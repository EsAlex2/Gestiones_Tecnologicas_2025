<?php
// lib/utils.php


// Función para redirigir con un mensaje flash
function redirect_with($path, $msg, $type='info') {
    header("Location: ".BASE_URL.$path."?msg=".urlencode($msg)."&type=".urlencode($type));
    exit;
}

// Función para mostrar mensajes flash
function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}


// Funciones para obtener datos de POST y GET de forma segura
function post($key, $default=null) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}


// Función para obtener datos de GET de forma segura
function get($key, $default=null) {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}
