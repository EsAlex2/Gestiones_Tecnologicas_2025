<?php
// public/suppliers.php - Gestión de proveedores (solo administradores)
require_once __DIR__ . '/_layout_top.php';
require_admin();

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_supplier') {
        $name = trim($_POST['name'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        if (!$name) {
            redirect_with("/suppliers.php", "El nombre del proveedor es requerido", "warning");
        }
        
        // Verificar si ya existe
        $exists = $pdo->prepare("SELECT id FROM suppliers WHERE name = ?");
        $exists->execute([$name]);
        if ($exists->fetch()) {
            redirect_with("/suppliers.php", "El proveedor ya existe", "danger");
        }
        
        $ins = $pdo->prepare("INSERT INTO suppliers (name, contact, phone, email, address) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([$name, $contact, $phone, $email, $address]);
        redirect_with("/suppliers.php", "Proveedor creado exitosamente", "success");
        
    } elseif ($action === 'update_supplier') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        
        if ($id <= 0 || !$name) {
            redirect_with("/suppliers.php", "Datos inválidos", "danger");
        }
        
        // Verificar si el nombre ya existe en otro proveedor
        $exists = $pdo->prepare("SELECT id FROM suppliers WHERE name = ? AND id != ?");
        $exists->execute([$name, $id]);
        if ($exists->fetch()) {
            redirect_with("/suppliers.php", "El nombre de proveedor ya está en uso", "danger");
        }
        
        $upd = $pdo->prepare("UPDATE suppliers SET name = ?, contact = ?, phone = ?, email = ?, address = ?, updated_at = NOW() WHERE id = ?");
        $upd->execute([$name, $contact, $phone, $email, $address, $id]);
        redirect_with("/suppliers.php", "Proveedor actualizado", "success");
        
    } elseif ($action === 'delete_supplier') {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            redirect_with("/suppliers.php", "ID inválido", "danger");
        }
        
        // Verificar si hay ítems usando este proveedor
        $items_count = $pdo->prepare("SELECT COUNT(*) as count FROM items WHERE supplier_id = ?");
        $items_count->execute([$id]);
        $count = $items_count->fetch()['count'];
        
        if ($count > 0) {
            redirect_with("/suppliers.php", "No se puede eliminar: hay $count ítem(s) usando este proveedor", "danger");
        }
        
        $del = $pdo->prepare("DELETE FROM suppliers WHERE id = ?");
        $del->execute([$id]);
        redirect_with("/suppliers.php", "Proveedor eliminado", "success");
    }
}

// Obtener lista de proveedores
$suppliers = $pdo->query("SELECT s.*, 
    (SELECT COUNT(*) FROM items WHERE supplier_id = s.id) as items_count
    FROM suppliers s 
    ORDER BY s.name")->fetchAll();
?>
<div class="card">
    <div class="card-header">
        <h2>Gestión de Proveedores</h2>
        <a href="categories.php" class="button secondary">Ir a Categorías</a>
    </div>

    <!-- Formulario para crear proveedor -->
    <form method="post" class="card" data-validate>
        <h3>Nuevo Proveedor</h3>
        <input type="hidden" name="action" value="create_supplier">
        
        <div class="form-grid two">
            <div class="input-group">
                <label class="input-label">Nombre del Proveedor *</label>
                <input class="input" type="text" name="name" placeholder="Nombre de la empresa..." required maxlength="100">
            </div>
            <div class="input-group">
                <label class="input-label">Persona de Contacto</label>
                <input class="input" type="text" name="contact" placeholder="Nombre del contacto..." maxlength="100">
            </div>
        </div>
        
        <div class="form-grid two" style="margin-top:8px;">
            <div class="input-group">
                <label class="input-label">Teléfono</label>
                <input class="input" type="text" name="phone" placeholder="Número de teléfono..." maxlength="20">
            </div>
            <div class="input-group">
                <label class="input-label">Email</label>
                <input class="input" type="email" name="email" placeholder="correo@proveedor.com" maxlength="100">
            </div>
        </div>
        
        <div class="input-group" style="margin-top:8px;">
            <label class="input-label">Dirección</label>
            <textarea class="input" name="address" rows="2" placeholder="Dirección completa..." maxlength="255"></textarea>
        </div>
        
        <div style="margin-top:10px; display:flex; gap:8px; justify-content:flex-end;">
            <input type="submit" class="button primary" value="Crear Proveedor">
        </div>
    </form>

    <!-- Lista de proveedores -->
    <div class="card" style="margin-top:20px;">
        <h3>Lista de Proveedores (<?= count($suppliers) ?>)</h3>
        
        <?php if (empty($suppliers)): ?>
            <div style="text-align:center; padding:40px; color:var(--text-muted);">
                No hay proveedores registrados. Crea el primer proveedor ↑
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Ítems Asociados</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($suppliers as $sup): ?>
                    <tr>
                        <td><?= h($sup['id']) ?></td>
                        <td>
                            <strong><?= h($sup['name']) ?></strong>
                            <?php if ($sup['address']): ?>
                                <br><small style="color:var(--text-muted);"><?= h($sup['address']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= h($sup['contact'] ?: '-') ?></td>
                        <td><?= h($sup['phone'] ?: '-') ?></td>
                        <td><?= h($sup['email'] ?: '-') ?></td>
                        <td>
                            <span class="badge <?= $sup['items_count'] > 0 ? 'info' : 'secondary' ?>">
                                <?= h($sup['items_count']) ?> ítem(s)
                            </span>
                        </td>
                        <td>
                            <details>
                                <summary class="button ghost small">Editar</summary>
                                <form method="post" style="margin-top:8px; padding:12px; background:var(--bg-alt); border-radius:8px;">
                                    <input type="hidden" name="action" value="update_supplier">
                                    <input type="hidden" name="id" value="<?= h($sup['id']) ?>">
                                    
                                    <div class="form-grid two" style="gap:8px;">
                                        <div class="input-group">
                                            <input class="input" type="text" name="name" value="<?= h($sup['name']) ?>" required>
                                        </div>
                                        <div class="input-group">
                                            <input class="input" type="text" name="contact" value="<?= h($sup['contact']) ?>" placeholder="Contacto...">
                                        </div>
                                    </div>
                                    
                                    <div class="form-grid two" style="gap:8px; margin-top:8px;">
                                        <div class="input-group">
                                            <input class="input" type="text" name="phone" value="<?= h($sup['phone']) ?>" placeholder="Teléfono...">
                                        </div>
                                        <div class="input-group">
                                            <input class="input" type="email" name="email" value="<?= h($sup['email']) ?>" placeholder="Email...">
                                        </div>
                                    </div>
                                    
                                    <div class="input-group" style="margin-top:8px;">
                                        <textarea class="input" name="address" rows="2" placeholder="Dirección..."><?= h($sup['address']) ?></textarea>
                                    </div>
                                    
                                    <div style="display:flex; gap:6px; margin-top:8px;">
                                        <button type="submit" class="button primary small">Guardar</button>
                                        
                                        <?php if ($sup['items_count'] == 0): ?>
                                        <button type="button" class="button danger small" 
                                                onclick="if(confirm('¿Eliminar este proveedor?')) { 
                                                    document.getElementById('deleteSupplierForm<?= h($sup['id']) ?>').submit(); 
                                                }">
                                            Eliminar
                                        </button>
                                        <?php else: ?>
                                        <span class="badge warning" style="font-size:11px;">
                                            No se puede eliminar
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </form>
                                
                                <!-- Formulario oculto para eliminar -->
                                <form id="deleteSupplierForm<?= h($sup['id']) ?>" method="post" style="display:none;">
                                    <input type="hidden" name="action" value="delete_supplier">
                                    <input type="hidden" name="id" value="<?= h($sup['id']) ?>">
                                </form>
                            </details>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>