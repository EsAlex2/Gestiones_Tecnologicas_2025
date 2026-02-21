<?php
// public/items.php (AJAX-enhanced) - Versión multi-usuario
require_once __DIR__ . '/_layout_top.php';
require_login();

$uid = $_SESSION['user_id'];
$current_role = $_SESSION['user_role'];

// Determinar el usuario objetivo
$target_user_id = $_GET['user_id'] ?? $uid;

// Verificar permisos
if ($target_user_id != $uid && !can_manage_user_inventory($target_user_id, $pdo)) {
    header("Location: " . BASE_URL . "/items.php?msg=no_permission&type=danger");
    exit;
}

// Obtener información del usuario objetivo
$target_user = $pdo->prepare("SELECT username, first_name, last_name, email, role FROM users WHERE id = ?");
$target_user->execute([$target_user_id]);
$target_user_data = $target_user->fetch();

// Pagination params
$perPage = 12;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Filtros de búsqueda
$search = trim($_GET['q'] ?? '');
$params = [$target_user_id];
$where = " WHERE i.user_id = ? ";
if ($search) {
    $where .= " AND (i.sku LIKE ? OR i.name LIKE ?) ";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
}

// Obtener total de ítems y lista paginada
$totalStmt = $pdo->prepare("SELECT COUNT(*) c FROM items i $where");
$totalStmt->execute($params);
$total = (int) $totalStmt->fetch()['c'];
$pages = max(1, ceil($total / $perPage));


// Obtener ítems
$stmt = $pdo->prepare("SELECT i.*, c.name as category, s.name as supplier FROM items i LEFT JOIN categories c ON i.category_id=c.id LEFT JOIN suppliers s ON i.supplier_id=s.id $where ORDER BY i.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$items = $stmt->fetchAll();

// Obtener categorías y proveedores para formularios
$cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$sups = $pdo->query("SELECT * FROM suppliers ORDER BY name")->fetchAll();

// Obtener usuarios gestionables para el selector
if (is_admin() || is_operator()) {
    $manageable_users = get_manageable_users($pdo);
}
?>
<div class="card">
    <div class="card-header">
        <div>
            <h2>Gestión de Inventario</h2>
            <p style="color:var(--text-muted); margin:0;">
                <?php if ($target_user_id != $uid): ?>
                    Inventario de:
                    <strong><?= h($target_user_data['first_name'] . ' ' . $target_user_data['last_name']) ?></strong>
                <?php else: ?>
                    Gestiona tu inventario de productos
                <?php endif; ?>
            </p>
        </div>

        <?php if (is_admin() || is_operator()): ?>
            <div class="user-selector">
                <form method="get" class="d-flex align-items-center gap-2">
                    <input type="hidden" name="q" value="<?= h($search) ?>">
                    <select name="user_id" class="input" onchange="this.form.submit()" style="width: auto;">
                        <option value="<?= $uid ?>">Mi Inventario</option>
                        <?php foreach ($manageable_users as $user): ?>
                            <?php if ($user['id'] != $uid): ?>
                                <option value="<?= h($user['id']) ?>" <?= $target_user_id == $user['id'] ? 'selected' : '' ?>>
                                    <?= h($user['first_name'] . ' ' . $user['last_name']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <div style="display:flex; gap:8px; align-items:center; margin-bottom:20px;">
        <form method="get" style="display:flex; gap:8px; flex-grow:1;">
            <input type="hidden" name="user_id" value="<?= h($target_user_id) ?>">
            <input class="input" type="text" name="q" placeholder="Buscar por SKU/Nombre" value="<?= h($search) ?>">
            <button class="button ghost" type="submit">Buscar</button>
        </form>
        <button class="button" id="refreshBtn">Refrescar</button>
    </div>

    <!-- Formulario para crear ítem -->
    <form id="createItemForm" data-validate class="card">
        <h3>Nuevo Ítem</h3>
        <input type="hidden" name="action" value="create_item">
        <input type="hidden" name="target_user_id" value="<?= h($target_user_id) ?>">

        <div class="form-grid two">
            <input class="input" type="text" name="sku" placeholder="SKU" required>
            <input class="input" type="text" name="name" placeholder="Nombre" required>
        </div>
        <div class="form-grid two" style="margin-top:30px;">
            <select class="input" name="category_id">
                <option value="">-- Categoría --</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= h($c['id']) ?>"><?= h($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select class="input" name="supplier_id">
                <option value="">-- Proveedor --</option>
                <?php foreach ($sups as $s): ?>
                    <option value="<?= h($s['id']) ?>"><?= h($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-grid" style="margin-top:30px;">
            <input class="input" type="number" name="quantity" placeholder="Cantidad" min="0" value="0">
        </div>
        <textarea class="input" name="description" rows="3" placeholder="Descripción opcional"
            style="margin-top: 30px"></textarea>
        <div style="margin-top:10px; display:flex; gap:8px; justify-content:flex-end;">
            <input type="submit" class="button primary" value="Agregar Ítem">
        </div>
    </form>

    <!-- Tabla de ítems -->
    <div class="card-body" style="margin-top:50px;">
        <h3>Lista de Ítems (<?= $total ?>)</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Nombre</th>
                    <th>Cat.</th>
                    <th>Prov.</th>
                    <th>Cant.</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="itemsBody">
                <?php foreach ($items as $it): ?>
                    <tr class="fade-in" data-id="<?= h($it['id']) ?>">
                        <td><?= h($it['sku']) ?></td>
                        <td><?= h($it['name']) ?></td>
                        <td><?= h($it['category'] ?? '-') ?></td>
                        <td><?= h($it['supplier'] ?? '-') ?></td>
                        <td><?= h($it['quantity']) ?></td>
                        <td>
                            <details>
                                <summary class="button ghost small">Editar</summary>
                                <form class="editForm" data-id="<?= h($it['id']) ?>"
                                    style="margin-top:8px; display:grid; gap:6px;">
                                    <input type="hidden" name="action" value="update_item">
                                    <input type="hidden" name="id" value="<?= h($it['id']) ?>">
                                    <input type="hidden" name="target_user_id" value="<?= h($target_user_id) ?>">

                                    <input class="input" type="text" name="sku" value="<?= h($it['sku']) ?>" required>
                                    <input class="input" type="text" name="name" value="<?= h($it['name']) ?>" required>

                                    <select class="input" name="category_id">
                                        <option value="">-- Categoría --</option>
                                        <?php foreach ($cats as $c): ?>
                                            <option value="<?= h($c['id']) ?>" <?= ($it['category_id'] == $c['id'] ? 'selected' : '') ?>>
                                                <?= h($c['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <select class="input" name="supplier_id">
                                        <option value="">-- Proveedor --</option>
                                        <?php foreach ($sups as $s): ?>
                                            <option value="<?= h($s['id']) ?>" <?= ($it['supplier_id'] == $s['id'] ? 'selected' : '') ?>>
                                                <?= h($s['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <textarea class="input" name="description"
                                        rows="2"><?= h($it['description']) ?></textarea>

                                    <div style="display:flex; gap:6px;">
                                        <button class="button primary small saveBtn">Guardar</button>
                                        <button class="button danger small deleteBtn" type="button">Eliminar</button>
                                    </div>
                                </form>
                            </details>

                            <div style="margin-top:6px;">
                                <button class="button ghost small movBtn" data-id="<?= h($it['id']) ?>">Movimiento</button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="9" style="color:var(--text-muted); text-align:center; padding:40px;">
                            Sin datos aún. Agrega tu primer ítem ↑
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <?php if ($pages > 1): ?>
            <div style="margin-top:20px; display:flex; gap:8px; justify-content:center; align-items:center;">
                <?php if ($page > 1): ?>
                    <a class="button ghost"
                        href="items.php?page=<?= $page - 1 ?>&q=<?= urlencode($search) ?>&user_id=<?= $target_user_id ?>">
                        « Anterior
                    </a>
                <?php endif; ?>

                <span style="color:var(--text-muted)">
                    Página <?= $page ?> de <?= $pages ?>
                </span>

                <?php if ($page < $pages): ?>
                    <a class="button ghost"
                        href="items.php?page=<?= $page + 1 ?>&q=<?= urlencode($search) ?>&user_id=<?= $target_user_id ?>">
                        Siguiente »
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/_layout_bottom.php'; ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    //Validacion para el campo 'Buscar por SKU/Nombre' que solo acepte letras y numeros (sin caracteres)
    $(document).ready(function () {
        var nameInputs = $('input[name="q"]');
        nameInputs.on('input', function () {
            var value = $(this).val();
            var cleanValue = value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ0-9]/g, ' ');
            if (value !== cleanValue) {
                $(this).val(cleanValue);
            }
        });
    });


    //Validacion para el campo 'SKU' que solo acepte numeros (sin caracteres ni letras)
    $(document).ready(function () {
        var nameInputs = $('input[name="sku"]');
        nameInputs.on('input', function () {
            var value = $(this).val();
            var cleanValue = value.replace(/[^a-zA-Z0-9-]/g, '');
            if (value !== cleanValue) {
                $(this).val(cleanValue);
            }
        });
    });


    //Validacion para el campo 'Nombre' que solo acepte numeros y letras (sin caracteres)
    $(document).ready(function () {
        var nameInputs = $('input[name="name"]');
        nameInputs.on('input', function () {
            var value = $(this).val();
            var cleanValue = value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ0-9' ']/g, '');
            if (value !== cleanValue) {
                $(this).val(cleanValue);
            }
        });
    });
</script>

<script>
    // AJAX helpers
    async function postForm(url, form) {
        const fd = new FormData(form);
        const res = await fetch(url, {
            method: 'POST',
            body: fd
        });
        return res.json();
    }

    // Crear ítem
    document.getElementById('createItemForm')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const res = await postForm('api.php', this);
        if (res.ok) {
            showToast(res.msg, 'success');
            setTimeout(() => location.reload(), 700);
        } else {
            showToast(res.msg, 'danger');
        }
    });

    // Editar y eliminar ítems
    document.querySelectorAll('.editForm').forEach(f => {
        f.querySelector('.saveBtn').addEventListener('click', async (e) => {
            e.preventDefault();
            const res = await postForm('api.php', f);
            if (res.ok) {
                showToast(res.msg, 'success');
                setTimeout(() => location.reload(), 700);
            } else {
                showToast(res.msg, 'danger');
            }
        });

        f.querySelector('.deleteBtn').addEventListener('click', async (e) => {
            if (!confirm('¿Estás seguro de eliminar este ítem?')) return;

            const id = f.querySelector('input[name=id]').value;
            const target_user_id = f.querySelector('input[name=target_user_id]').value;

            const fd = new FormData();
            fd.append('action', 'delete_item');
            fd.append('id', id);
            fd.append('target_user_id', target_user_id);

            const res = await fetch('api.php', {
                method: 'POST',
                body: fd
            }).then(r => r.json());
            if (res.ok) {
                showToast(res.msg, 'success');
                setTimeout(() => location.reload(), 700);
            } else {
                showToast(res.msg, 'danger');
            }
        });
    });

    // Movimientos
    document.querySelectorAll('.movBtn').forEach(b => {
        b.addEventListener('click', () => {
            const id = b.dataset.id;
            const target_user_id = new URLSearchParams(window.location.search).get('user_id') || '<?= $uid ?>';

            const qty = prompt('Cantidad (use negativo para salida o registre tipo):', '1');
            if (!qty) return;

            const type = parseInt(qty) < 0 ? 'out' : 'in';
            const q = Math.abs(parseInt(qty));

            const fd = new FormData();
            fd.append('action', 'create_movement');
            fd.append('item_id', id);
            fd.append('type', type);
            fd.append('quantity', q);
            fd.append('note', 'Movimiento manual');
            fd.append('target_user_id', target_user_id);

            fetch('api.php', {
                method: 'POST',
                body: fd
            })
                .then(r => r.json())
                .then(res => {
                    if (res.ok) {
                        showToast(res.msg, 'success');
                        setTimeout(() => location.reload(), 700);
                    } else {
                        showToast(res.msg, 'danger');
                    }
                });
        });
    });

    document.getElementById('refreshBtn')?.addEventListener('click', () => location.reload());
</script>