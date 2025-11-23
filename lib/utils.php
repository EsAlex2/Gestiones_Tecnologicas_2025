<?php
// lib/utils.php
function redirect_with($path, $msg, $type='info') {
    header("Location: ".BASE_URL.$path."?msg=".urlencode($msg)."&type=".urlencode($type));
    exit;
}

function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function post($key, $default=null) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function get($key, $default=null) {
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}
