<?php
// lib/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: ".BASE_URL."/index.php?msg=auth_required&type=warning");
        exit;
    }
}

function login_user($id, $name, $role='client', $client_id=null) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $id;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_role'] = $role;
    $_SESSION['client_id'] = $client_id;
}

function logout_user() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === ROLE_ADMIN;
}

function is_operator() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === ROLE_OPERATOR;
}

function is_client() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === ROLE_CLIENT;
}

function require_admin() {
    if (!is_admin()) {
        header("Location: ".BASE_URL."/dashboard.php?msg=admin_required&type=danger");
        exit;
    }
}

function require_operator_or_admin() {
    if (!is_admin() && !is_operator()) {
        header("Location: ".BASE_URL."/dashboard.php?msg=operator_required&type=danger");
        exit;
    }
}

function get_user_role_name($role) {
    switch($role) {
        case ROLE_ADMIN: return 'Administrador';
        case ROLE_OPERATOR: return 'Operador';
        case ROLE_CLIENT: return 'Cliente';
        default: return 'Desconocido';
    }
}

// Funciones de permisos para gestión multi-usuario
function can_manage_user($target_user_id, $pdo) {
    if (!is_logged_in()) return false;
    
    $current_user_id = $_SESSION['user_id'];
    $current_role = $_SESSION['user_role'];
    
    // Un usuario siempre puede gestionarse a sí mismo
    if ($target_user_id == $current_user_id) return true;
    
    // Administradores pueden gestionar a todos
    if ($current_role === ROLE_ADMIN) return true;
    
    // Operadores solo pueden gestionar clientes
    if ($current_role === ROLE_OPERATOR) {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$target_user_id]);
        $target_role = $stmt->fetch()['role'] ?? '';
        
        return $target_role === ROLE_CLIENT;
    }
    
    // Clientes no pueden gestionar a otros usuarios
    return false;
}

function can_manage_user_inventory($target_user_id, $pdo) {
    if (!is_logged_in()) return false;
    
    $current_user_id = $_SESSION['user_id'];
    $current_role = $_SESSION['user_role'];
    
    // Un usuario siempre puede gestionar su propio inventario
    if ($target_user_id == $current_user_id) return true;
    
    // Administradores pueden gestionar todo el inventario
    if ($current_role === ROLE_ADMIN) return true;
    
    // Operadores solo pueden gestionar inventario de clientes
    if ($current_role === ROLE_OPERATOR) {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$target_user_id]);
        $target_role = $stmt->fetch()['role'] ?? '';
        
        return $target_role === ROLE_CLIENT;
    }
    
    // Clientes no pueden gestionar inventario de otros
    return false;
}

function get_manageable_users($pdo) {
    if (!is_logged_in()) return [];
    
    $current_user_id = $_SESSION['user_id'];
    $current_role = $_SESSION['user_role'];
    
    if ($current_role === ROLE_ADMIN) {
        // Administradores ven todos los usuarios
        $stmt = $pdo->query("SELECT id, username, first_name, last_name, email, role FROM users ORDER BY first_name, last_name");
        return $stmt->fetchAll();
    } elseif ($current_role === ROLE_OPERATOR) {
        // Operadores ven solo clientes
        $stmt = $pdo->prepare("SELECT id, username, first_name, last_name, email, role FROM users WHERE role = ? ORDER BY first_name, last_name");
        $stmt->execute([ROLE_CLIENT]);
        return $stmt->fetchAll();
    } else {
        // Clientes solo se ven a sí mismos
        $stmt = $pdo->prepare("SELECT id, username, first_name, last_name, email, role FROM users WHERE id = ?");
        $stmt->execute([$current_user_id]);
        return $stmt->fetchAll();
    }
}

// Agregar después de las funciones existentes
function has_business_client() {
    return isset($_SESSION['client_id']) && $_SESSION['client_id'] !== null;
}

function require_business_client() {
    if (!has_business_client()) {
        header("Location: ".BASE_URL."/dashboard.php?msg=client_required&type=warning");
        exit;
    }
}

function get_business_client_id() {
    return $_SESSION['client_id'] ?? null;
}
?>