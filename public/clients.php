<?php
// public/clients.php - CRUD sencillo
require_once __DIR__ . '/_layout_top.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if (!$name) redirect_with("/clients.php", "Nombre es requerido", "warning");
    $ins = $pdo->prepare("INSERT INTO clients (name, contact, phone) VALUES (?,?,?)");
    $ins->execute([$name, $contact, $phone]);
    redirect_with("/clients.php", "Cliente creado", "success");
  } elseif ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    if ($id<=0) redirect_with("/clients.php", "ID inválido", "danger");
    $upd = $pdo->prepare("UPDATE clients SET name=?, contact=?, phone=? WHERE id=?");
    $upd->execute([$name, $contact, $phone, $id]);
    redirect_with("/clients.php", "Cliente actualizado", "success");
  }
}

if (isset($_GET['delete'])) {
  $id = (int)$_GET['delete'];
  if ($id>0) { $del = $pdo->prepare("DELETE FROM clients WHERE id=?"); $del->execute([$id]); redirect_with("/clients.php", "Cliente eliminado", "success"); }
}

$rows = $pdo->query("SELECT * FROM clients ORDER BY created_at DESC")->fetchAll();
?>
<div class="card">
  <div class="header"><h2>Clientes</h2></div>
  <form method="post" class="card" data-validate>
    <h3>Nuevo cliente</h3>
    <input type="hidden" name="action" value="create">
    <div class="form-grid two">
      <input class="input" type="text" name="name" placeholder="Nombre" required>
      <input class="input" type="text" name="contact" placeholder="Contacto">
      <input class="input" type="text" name="phone" placeholder="Teléfono">
    </div>
    <div style="margin-top:10px; display:flex; gap:8px; justify-content:flex-end;"><input type="submit" class="button" value="Agregar cliente"></div>
  </form>

  <table class="table" style="margin-top:12px;">
    <thead><tr><th>ID</th><th>Nombre</th><th>Contacto</th><th>Teléfono</th><th>Acciones</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?=h($r['id'])?></td>
          <td><?=h($r['name'])?></td>
          <td><?=h($r['contact'])?></td>
          <td><?=h($r['phone'])?></td>
          <td>
            <details>
              <summary class="button ghost">Editar</summary>
              <form method="post" style="margin-top:8px;">
                <input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?=h($r['id'])?>">
                <input class="input" type="text" name="name" value="<?=h($r['name'])?>" required>
                <input class="input" type="text" name="contact" value="<?=h($r['contact'])?>">
                <input class="input" type="text" name="phone" value="<?=h($r['phone'])?>">
                <div style="display:flex; gap:8px; margin-top:6px;"><input type="submit" class="button" value="Guardar"><a class="button ghost" href="clients.php?delete=<?=h($r['id'])?>" onclick="return confirm('Eliminar cliente?')">Eliminar</a></div>
              </form>
            </details>
          </td>
        </tr>
      <?php endforeach; if(empty($rows)): ?><tr><td colspan="5" style="color:var(--muted)">Sin clientes aún</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/_layout_bottom.php'; ?>