<?php
// public/api.php - endpoints AJAX (simple, no auth tokens; relies on session)
// public/api.php - endpoints AJAX con soporte multi-usuario
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/utils.php';
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();
if (!is_logged_in()) {
    echo json_encode(['ok'=>false,'msg'=>'No autorizado']); exit;
}

$uid = $_SESSION['user_id'];
$current_role = $_SESSION['user_role'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Determinar el usuario objetivo para las operaciones
$target_user_id = $_POST['target_user_id'] ?? $_GET['target_user_id'] ?? $uid;

// Verificar permisos para operaciones multi-usuario
if ($target_user_id != $uid && !can_manage_user_inventory($target_user_id, $pdo)) {
    echo json_encode(['ok'=>false,'msg'=>'No tienes permisos para gestionar este inventario']); 
    exit;
}

try {
    if ($action === 'search_items') {
        $q = trim($_GET['q'] ?? '');
        $like = "%$q%";
        $stmt = $pdo->prepare("SELECT i.*, c.name as category, s.name as supplier FROM items i LEFT JOIN categories c ON i.category_id=c.id LEFT JOIN suppliers s ON i.supplier_id=s.id WHERE i.user_id=? AND (i.sku LIKE ? OR i.name LIKE ?) ORDER BY i.created_at DESC LIMIT 50");
        $stmt->execute([$target_user_id, $like, $like]);
        echo json_encode(['ok'=>true,'rows'=>$stmt->fetchAll()]);
        exit;
        
    } elseif ($action === 'create_item') {
        $sku = trim($_POST['sku'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $quantity = max(0,(int)($_POST['quantity'] ?? 0));
        $unit_price = number_format((float)($_POST['unit_price'] ?? 0),2,'.','');
        $category = $_POST['category_id'] ?: null;
        $supplier = $_POST['supplier_id'] ?: null;
        
        if (!$sku || !$name) throw new Exception("SKU y nombre requeridos");
        
        $ins = $pdo->prepare("INSERT INTO items (user_id, sku, name, description, quantity, unit_price, category_id, supplier_id) VALUES (?,?,?,?,?,?,?,?)");
        $ins->execute([$target_user_id, $sku, $name, $_POST['description'] ?? '', $quantity, $unit_price, $category, $supplier]);
        echo json_encode(['ok'=>true,'msg'=>'Ítem creado']);
        exit;
        
    } elseif ($action === 'update_item') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id<=0) throw new Exception("ID inválido");
        
        // Verificar que el ítem pertenece al usuario objetivo
        $item_check = $pdo->prepare("SELECT user_id FROM items WHERE id = ?");
        $item_check->execute([$id]);
        $item_user_id = $item_check->fetch()['user_id'] ?? null;
        
        if ($item_user_id != $target_user_id) {
            throw new Exception("El ítem no pertenece al usuario especificado");
        }
        
        $stmt = $pdo->prepare("UPDATE items SET sku=?, name=?, description=?, quantity=?, unit_price=?, category_id=?, supplier_id=?, updated_at=NOW() WHERE id=? AND user_id=?");
        $stmt->execute([
            $_POST['sku'], 
            $_POST['name'], 
            $_POST['description'] ?? '', 
            max(0,(int)$_POST['quantity']), 
            number_format((float)$_POST['unit_price'],2,'.',''), 
            $_POST['category_id'] ?: null, 
            $_POST['supplier_id'] ?: null, 
            $id, 
            $target_user_id
        ]);
        echo json_encode(['ok'=>true,'msg'=>'Ítem actualizado']);
        exit;
        
    } elseif ($action === 'delete_item') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id<=0) throw new Exception("ID inválido");
        
        // Verificar que el ítem pertenece al usuario objetivo
        $item_check = $pdo->prepare("SELECT user_id FROM items WHERE id = ?");
        $item_check->execute([$id]);
        $item_user_id = $item_check->fetch()['user_id'] ?? null;
        
        if ($item_user_id != $target_user_id) {
            throw new Exception("El ítem no pertenece al usuario especificado");
        }
        
        $stmt = $pdo->prepare("DELETE FROM items WHERE id=? AND user_id=?");
        $stmt->execute([$id, $target_user_id]);
        echo json_encode(['ok'=>true,'msg'=>'Ítem eliminado']);
        exit;
        
    } elseif ($action === 'create_movement') {
        $item_id = (int)($_POST['item_id'] ?? 0);
        $type = $_POST['type'] ?? 'in';
        $qty = max(1,(int)($_POST['quantity'] ?? 0));
        $note = $_POST['note'] ?? ''; 
        $supplier = $_POST['supplier_id'] ?: null; 
        $client = $_POST['client_id'] ?: null;
        
        if ($item_id<=0) throw new Exception("Item inválido");
        
        // verify item belongs to target user
        $it = $pdo->prepare("SELECT * FROM items WHERE id=? AND user_id=?");
        $it->execute([$item_id, $target_user_id]);
        $row = $it->fetch();
        if (!$row) throw new Exception("Item no encontrado");
        
        $newQty = $row['quantity'] + ($type==='in' ? $qty : -$qty);
        if ($newQty < 0) throw new Exception("Cantidad insuficiente");
        
        $pdo->beginTransaction();
        $ins = $pdo->prepare("INSERT INTO movements (user_id,item_id,type,quantity,supplier_id,client_id,note) VALUES (?,?,?,?,?,?,?)");
        $ins->execute([$target_user_id, $item_id, $type, $qty, $supplier, $client, $note]);
        $upd = $pdo->prepare("UPDATE items SET quantity=?, updated_at=NOW() WHERE id=?");
        $upd->execute([$newQty, $item_id]);
        $pdo->commit();
        echo json_encode(['ok'=>true,'msg'=>'Movimiento registrado','newQty'=>$newQty]);
        exit;
        
    } elseif ($action === 'list_movements') {
        $stmt = $pdo->prepare("SELECT m.*, i.name as item_name FROM movements m JOIN items i ON m.item_id = i.id WHERE m.user_id=? ORDER BY m.created_at DESC LIMIT 200");
        $stmt->execute([$target_user_id]);
        echo json_encode(['ok'=>true,'rows'=>$stmt->fetchAll()]);
        exit;
        
    } elseif ($action === 'get_user_inventory_stats') {
        // Stats para dashboard
        $totalItems = $pdo->prepare("SELECT COUNT(*) c FROM items WHERE user_id = ?");
        $totalItems->execute([$target_user_id]); 
        $count = (int)$totalItems->fetch()['c'];

        $totalQty = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) s FROM items WHERE user_id = ?");
        $totalQty->execute([$target_user_id]); 
        $sumQty = (int)$totalQty->fetch()['s'];

        $totalValue = $pdo->prepare("SELECT COALESCE(SUM(quantity*unit_price),0) v FROM items WHERE user_id = ?");
        $totalValue->execute([$target_user_id]); 
        $sumVal = (float)$totalValue->fetch()['v'];
        
        echo json_encode([
            'ok'=>true,
            'stats'=>[
                'total_items' => $count,
                'total_quantity' => $sumQty,
                'total_value' => $sumVal
            ]
        ]);
        exit;
    }
    
    echo json_encode(['ok'=>false,'msg'=>'Acción desconocida']);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['ok'=>false,'msg'=> $e->getMessage()]);
}