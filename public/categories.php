<?php
// public/categories.php - Gestión de categorías (solo administradores)
require_once __DIR__ . '/_layout_top.php';
require_admin();

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_category') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (!$name) {
            redirect_with("/categories.php", "El nombre de la categoría es requerido", "warning");
        }
        
        // Verificar si ya existe
        $exists = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $exists->execute([$name]);
        if ($exists->fetch()) {
            redirect_with("/categories.php", "La categoría ya existe", "danger");
        }
        
        $ins = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $ins->execute([$name, $description]);
        redirect_with("/categories.php", "Categoría creada exitosamente", "success");
        
    } elseif ($action === 'update_category') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if ($id <= 0 || !$name) {
            redirect_with("/categories.php", "Datos inválidos", "danger");
        }
        
        // Verificar si el nombre ya existe en otra categoría
        $exists = $pdo->prepare("SELECT id FROM categories WHERE name = ? AND id != ?");
        $exists->execute([$name, $id]);
        if ($exists->fetch()) {
            redirect_with("/categories.php", "El nombre de categoría ya está en uso", "danger");
        }
        
        $upd = $pdo->prepare("UPDATE categories SET name = ?, description = ?, updated_at = NOW() WHERE id = ?");
        $upd->execute([$name, $description, $id]);
        redirect_with("/categories.php", "Categoría actualizada", "success");
        
    } elseif ($action === 'delete_category') {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            redirect_with("/categories.php", "ID inválido", "danger");
        }
        
        // Verificar si hay ítems usando esta categoría
        $items_count = $pdo->prepare("SELECT COUNT(*) as count FROM items WHERE category_id = ?");
        $items_count->execute([$id]);
        $count = $items_count->fetch()['count'];
        
        if ($count > 0) {
            redirect_with("/categories.php", "No se puede eliminar: hay $count ítem(s) usando esta categoría", "danger");
        }
        
        $del = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $del->execute([$id]);
        redirect_with("/categories.php", "Categoría eliminada", "success");
    }
}

// Obtener lista de categorías
$categories = $pdo->query("SELECT c.*, 
    (SELECT COUNT(*) FROM items WHERE category_id = c.id) as items_count
    FROM categories c 
    ORDER BY c.name")->fetchAll();
?>
<div class="card">
    <div class="card-header">
        <h2>Gestión de Categorías</h2>
        <a href="suppliers.php" class="button secondary">Ir a Proveedores</a>
    </div>

    <!-- Formulario para crear categoría -->
    <form method="post" class="card" data-validate>
        <h3>Nueva Categoría</h3>
        <input type="hidden" name="action" value="create_category">
        
        <div class="form-grid two">
            <div class="input-group">
                <label class="input-label">Nombre de la Categoría *</label>
                <input class="input" type="text" name="name" placeholder="Ej: Electrónicos, Ropa, Herramientas..." required maxlength="100">
            </div>
            <div class="input-group">
                <label class="input-label">Descripción</label>
                <input class="input" type="text" name="description" placeholder="Descripción opcional..." maxlength="255">
            </div>
        </div>
        
        <div style="margin-top:10px; display:flex; gap:8px; justify-content:flex-end;">
            <input type="submit" class="button primary" value="Crear Categoría">
        </div>
    </form>

    <!-- Lista de categorías -->
    <div class="card" style="margin-top:20px;">
        <h3>Lista de Categorías (<?= count($categories) ?>)</h3>
        
        <?php if (empty($categories)): ?>
            <div style="text-align:center; padding:40px; color:var(--text-muted);">
                No hay categorías registradas. Crea la primera categoría ↑
            </div>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Ítems Asociados</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($categories as $cat): ?>
                    <tr>
                        <td><?= h($cat['id']) ?></td>
                        <td>
                            <strong><?= h($cat['name']) ?></strong>
                        </td>
                        <td><?= h($cat['description'] ?: '-') ?></td>
                        <td>
                            <span class="badge <?= $cat['items_count'] > 0 ? 'info' : 'secondary' ?>">
                                <?= h($cat['items_count']) ?> ítem(s)
                            </span>
                        </td>
                        <td><?= h($cat['created_at']) ?></td>
                        <td>
                            <details>
                                <summary class="button ghost small">Editar</summary>
                                <form method="post" style="margin-top:8px; padding:12px; background:var(--bg-alt); border-radius:8px;">
                                    <input type="hidden" name="action" value="update_category">
                                    <input type="hidden" name="id" value="<?= h($cat['id']) ?>">
                                    
                                    <div class="form-grid two" style="gap:8px;">
                                        <div class="input-group">
                                            <input class="input" type="text" name="name" value="<?= h($cat['name']) ?>" required>
                                        </div>
                                        <div class="input-group">
                                            <input class="input" type="text" name="description" value="<?= h($cat['description']) ?>" placeholder="Descripción...">
                                        </div>
                                    </div>
                                    
                                    <div style="display:flex; gap:6px; margin-top:8px;">
                                        <button type="submit" class="button primary small">Guardar</button>
                                        
                                        <?php if ($cat['items_count'] == 0): ?>
                                        <button type="button" class="button danger small" 
                                                onclick="if(confirm('¿Eliminar esta categoría?')) { 
                                                    document.getElementById('deleteForm<?= h($cat['id']) ?>').submit(); 
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
                                <form id="deleteForm<?= h($cat['id']) ?>" method="post" style="display:none;">
                                    <input type="hidden" name="action" value="delete_category">
                                    <input type="hidden" name="id" value="<?= h($cat['id']) ?>">
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