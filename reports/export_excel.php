<?php
// reports/export_excel.php - Versión profesional mejorada
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_login();

$uid = $_SESSION['user_id'];
$current_role = $_SESSION['user_role'];

// Determinar el usuario objetivo
$target_user_id = $_GET['user_id'] ?? $uid;

// Verificar permisos
if ($target_user_id != $uid && !can_manage_user_inventory($target_user_id, $pdo)) {
    die('No tienes permisos para acceder a este reporte');
}

// Obtener información del usuario objetivo
$target_user = $pdo->prepare("SELECT username, first_name, last_name, email, role, phone FROM users WHERE id = ?");
$target_user->execute([$target_user_id]);
$target_user_data = $target_user->fetch();

// Obtener datos completos del inventario
$stmt = $pdo->prepare("SELECT 
    i.sku, 
    i.name, 
    i.description, 
    i.quantity, 
    i.unit_price, 
    (i.quantity * i.unit_price) as total,
    c.name as category_name,
    s.name as supplier_name,
    i.created_at,
    i.updated_at
FROM items i 
LEFT JOIN categories c ON i.category_id = c.id 
LEFT JOIN suppliers s ON i.supplier_id = s.id 
WHERE i.user_id = ? 
ORDER BY i.name");
$stmt->execute([$target_user_id]);
$rows = $stmt->fetchAll();

// Calcular estadísticas
$total_items = count($rows);
$total_quantity = array_sum(array_column($rows, 'quantity'));
$total_value = array_sum(array_column($rows, 'total'));
$avg_price = $total_items > 0 ? $total_value / $total_quantity : 0;

// Configurar headers para Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=reporte_inventario_" . $target_user_data['username'] . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Iniciar contenido Excel con formato HTML
?>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .header {
            background: #1e40af;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .report-title {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .user-info {
            background: #f1f5f9;
            padding: 15px;
            border-left: 4px solid #1e40af;
            margin-bottom: 20px;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .stat-card {
            display: table-cell;
            background: #1e40af;
            color: white;
            padding: 15px;
            text-align: center;
            border: 1px solid white;
        }
        .stat-card.success {
            background: #059669;
        }
        .stat-card.warning {
            background: #d97706;
        }
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            margin: 5px 0;
        }
        .stat-label {
            font-size: 12px;
            opacity: 0.9;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table.data-table th {
            background: #1e40af;
            color: white;
            padding: 12px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #1e3a8a;
        }
        table.data-table td {
            padding: 10px 8px;
            border: 1px solid #e2e8f0;
        }
        table.data-table tr:nth-child(even) {
            background: #f8fafc;
        }
        table.data-table tr:hover {
            background: #f1f5f9;
        }
        .total-row {
            background: #0f172a !important;
            color: white;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .category-summary {
            background: #f1f5f9;
            padding: 15px;
            margin: 20px 0;
            border-radius: 6px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #e2e8f0;
            color: #64748b;
            font-size: 11px;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="company-name">GESTIONES TECNOLÓGICAS</div>
    <div class="report-title">REPORTE DETALLADO DE INVENTARIO</div>
    <div>Generado el: <?= date('d/m/Y H:i:s') ?></div>
</div>

<div class="user-info">
    <strong>INFORMACIÓN DEL USUARIO:</strong><br>
    <table style="width: 100%; margin-top: 10px;">
        <tr>
            <td style="width: 30%;"><strong>Nombre:</strong> <?= htmlspecialchars($target_user_data['first_name'] . ' ' . $target_user_data['last_name']) ?></td>
            <td style="width: 30%;"><strong>Usuario:</strong> <?= htmlspecialchars($target_user_data['username']) ?></td>
            <td style="width: 40%;"><strong>Email:</strong> <?= htmlspecialchars($target_user_data['email']) ?></td>
        </tr>
        <tr>
            <td><strong>Teléfono:</strong> <?= htmlspecialchars($target_user_data['phone'] ?: 'No especificado') ?></td>
            <td><strong>Rol:</strong> <?= get_user_role_name($target_user_data['role']) ?></td>
            <td><strong>ID Usuario:</strong> <?= $target_user_id ?></td>
        </tr>
    </table>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">TOTAL DE ÍTEMS</div>
        <div class="stat-value"><?= number_format($total_items) ?></div>
        <div class="stat-label">Productos únicos</div>
    </div>
    <div class="stat-card success">
        <div class="stat-label">EXISTENCIAS TOTALES</div>
        <div class="stat-value"><?= number_format($total_quantity) ?></div>
        <div class="stat-label">Unidades en stock</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-label">VALOR TOTAL</div>
        <div class="stat-value">$<?= number_format($total_value, 2) ?></div>
        <div class="stat-label">Valoración total</div>
    </div>
    <div class="stat-card" style="background: #7c3aed;">
        <div class="stat-label">PRECIO PROMEDIO</div>
        <div class="stat-value">$<?= number_format($avg_price, 2) ?></div>
        <div class="stat-label">Por unidad</div>
    </div>
</div>

<!-- Resumen por Categorías -->
<?php
// Obtener resumen por categorías
$category_stmt = $pdo->prepare("SELECT 
    COALESCE(c.name, 'Sin categoría') as category,
    COUNT(i.id) as item_count,
    SUM(i.quantity) as total_quantity,
    SUM(i.quantity * i.unit_price) as total_value
FROM items i 
LEFT JOIN categories c ON i.category_id = c.id 
WHERE i.user_id = ?
GROUP BY COALESCE(c.name, 'Sin categoría')
ORDER BY total_value DESC");
$category_stmt->execute([$target_user_id]);
$category_summary = $category_stmt->fetchAll();
?>

<div class="category-summary">
    <strong>RESUMEN POR CATEGORÍAS:</strong>
    <table style="width: 100%; margin-top: 10px; border-collapse: collapse;">
        <tr style="background: #1e40af; color: white;">
            <th style="padding: 8px; text-align: left;">Categoría</th>
            <th style="padding: 8px; text-align: center;">Ítems</th>
            <th style="padding: 8px; text-align: center;">Cantidad</th>
            <th style="padding: 8px; text-align: right;">Valor Total</th>
            <th style="padding: 8px; text-align: right;">% del Total</th>
        </tr>
        <?php foreach ($category_summary as $cat): ?>
        <?php $percentage = $total_value > 0 ? ($cat['total_value'] / $total_value) * 100 : 0; ?>
        <tr>
            <td style="padding: 6px 8px; border-bottom: 1px solid #e2e8f0;"><?= htmlspecialchars($cat['category']) ?></td>
            <td style="padding: 6px 8px; text-align: center; border-bottom: 1px solid #e2e8f0;"><?= $cat['item_count'] ?></td>
            <td style="padding: 6px 8px; text-align: center; border-bottom: 1px solid #e2e8f0;"><?= number_format($cat['total_quantity']) ?></td>
            <td style="padding: 6px 8px; text-align: right; border-bottom: 1px solid #e2e8f0;">$<?= number_format($cat['total_value'], 2) ?></td>
            <td style="padding: 6px 8px; text-align: right; border-bottom: 1px solid #e2e8f0;"><?= number_format($percentage, 1) ?>%</td>
        </tr>
        <?php endforeach; ?>
        <tr style="background: #0f172a; color: white; font-weight: bold;">
            <td style="padding: 8px;">TOTAL GENERAL</td>
            <td style="padding: 8px; text-align: center;"><?= $total_items ?></td>
            <td style="padding: 8px; text-align: center;"><?= number_format($total_quantity) ?></td>
            <td style="padding: 8px; text-align: right;">$<?= number_format($total_value, 2) ?></td>
            <td style="padding: 8px; text-align: right;">100%</td>
        </tr>
    </table>
</div>

<!-- Tabla principal de inventario -->
<table class="data-table">
    <thead>
        <tr>
            <th>SKU</th>
            <th>Nombre del Producto</th>
            <th>Descripción</th>
            <th>Categoría</th>
            <th>Proveedor</th>
            <th style="text-align: center;">Cantidad</th>
            <th style="text-align: right;">Precio Unitario</th>
            <th style="text-align: right;">Valor Total</th>
            <th style="text-align: center;">Última Actualización</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $index => $item): ?>
        <tr>
            <td><?= htmlspecialchars($item['sku']) ?></td>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td><?= htmlspecialchars($item['description'] ?: 'Sin descripción') ?></td>
            <td><?= htmlspecialchars($item['category_name'] ?: 'Sin categoría') ?></td>
            <td><?= htmlspecialchars($item['supplier_name'] ?: 'Sin proveedor') ?></td>
            <td style="text-align: center;"><?= number_format($item['quantity']) ?></td>
            <td style="text-align: right;">$<?= number_format($item['unit_price'], 2) ?></td>
            <td style="text-align: right;">$<?= number_format($item['total'], 2) ?></td>
            <td style="text-align: center;"><?= date('d/m/Y', strtotime($item['updated_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        
        <!-- Fila de totales -->
        <tr class="total-row">
            <td colspan="5"><strong>TOTALES GENERALES</strong></td>
            <td style="text-align: center;"><strong><?= number_format($total_quantity) ?></strong></td>
            <td style="text-align: right;"></td>
            <td style="text-align: right;"><strong>$<?= number_format($total_value, 2) ?></strong></td>
            <td style="text-align: center;"></td>
        </tr>
    </tbody>
</table>

<!-- Análisis de valor por rangos -->
<?php
// Análisis por rangos de valor
$value_ranges = [
    'Alto Valor (> $1,000)' => 0,
    'Medio Valor ($100 - $1,000)' => 0,
    'Bajo Valor (< $100)' => 0
];

$range_counts = [
    'Alto Valor (> $1,000)' => 0,
    'Medio Valor ($100 - $1,000)' => 0,
    'Bajo Valor (< $100)' => 0
];

foreach ($rows as $item) {
    if ($item['total'] > 1000) {
        $value_ranges['Alto Valor (> $1,000)'] += $item['total'];
        $range_counts['Alto Valor (> $1,000)']++;
    } elseif ($item['total'] >= 100) {
        $value_ranges['Medio Valor ($100 - $1,000)'] += $item['total'];
        $range_counts['Medio Valor ($100 - $1,000)']++;
    } else {
        $value_ranges['Bajo Valor (< $100)'] += $item['total'];
        $range_counts['Bajo Valor (< $100)']++;
    }
}
?>

<div style="margin-top: 30px;">
    <strong>ANÁLISIS DE VALOR POR RANGOS:</strong>
    <table style="width: 100%; margin-top: 10px; border-collapse: collapse;">
        <tr style="background: #1e40af; color: white;">
            <th style="padding: 8px; text-align: left;">Rango de Valor</th>
            <th style="padding: 8px; text-align: center;">N° de Ítems</th>
            <th style="padding: 8px; text-align: right;">Valor Total</th>
            <th style="padding: 8px; text-align: right;">% del Total</th>
        </tr>
        <?php foreach ($value_ranges as $range_name => $range_value): ?>
        <?php $percentage = $total_value > 0 ? ($range_value / $total_value) * 100 : 0; ?>
        <tr>
            <td style="padding: 6px 8px; border-bottom: 1px solid #e2e8f0;"><?= $range_name ?></td>
            <td style="padding: 6px 8px; text-align: center; border-bottom: 1px solid #e2e8f0;"><?= $range_counts[$range_name] ?></td>
            <td style="padding: 6px 8px; text-align: right; border-bottom: 1px solid #e2e8f0;">$<?= number_format($range_value, 2) ?></td>
            <td style="padding: 6px 8px; text-align: right; border-bottom: 1px solid #e2e8f0;"><?= number_format($percentage, 1) ?>%</td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- Top 10 productos más valiosos -->
<?php
$top_products = array_slice($rows, 0, 10);
usort($top_products, function($a, $b) {
    return $b['total'] - $a['total'];
});
?>

<div style="margin-top: 30px;">
    <strong>TOP 10 PRODUCTOS MÁS VALIOSOS:</strong>
    <table style="width: 100%; margin-top: 10px; border-collapse: collapse;">
        <tr style="background: #1e40af; color: white;">
            <th style="padding: 8px; text-align: left;">Producto</th>
            <th style="padding: 8px; text-align: left;">Categoría</th>
            <th style="padding: 8px; text-align: center;">Cantidad</th>
            <th style="padding: 8px; text-align: right;">Precio Unit.</th>
            <th style="padding: 8px; text-align: right;">Valor Total</th>
            <th style="padding: 8px; text-align: right;">% del Total</th>
        </tr>
        <?php foreach ($top_products as $product): ?>
        <?php $percentage = $total_value > 0 ? ($product['total'] / $total_value) * 100 : 0; ?>
        <tr>
            <td style="padding: 6px 8px; border-bottom: 1px solid #e2e8f0;"><?= htmlspecialchars($product['name']) ?></td>
            <td style="padding: 6px 8px; border-bottom: 1px solid #e2e8f0;"><?= htmlspecialchars($product['category_name'] ?: 'Sin categoría') ?></td>
            <td style="padding: 6px 8px; text-align: center; border-bottom: 1px solid #e2e8f0;"><?= number_format($product['quantity']) ?></td>
            <td style="padding: 6px 8px; text-align: right; border-bottom: 1px solid #e2e8f0;">$<?= number_format($product['unit_price'], 2) ?></td>
            <td style="padding: 6px 8px; text-align: right; border-bottom: 1px solid #e2e8f0;">$<?= number_format($product['total'], 2) ?></td>
            <td style="padding: 6px 8px; text-align: right; border-bottom: 1px solid #e2e8f0;"><?= number_format($percentage, 1) ?>%</td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="footer">
    <table style="width: 100%;">
        <tr>
            <td style="width: 50%;">
                <strong>Información del Reporte:</strong><br>
                - Generado por: <?= htmlspecialchars($_SESSION['user_name']) ?><br>
                - Rol: <?= get_user_role_name($_SESSION['user_role']) ?><br>
                - Fecha de generación: <?= date('d/m/Y H:i:s') ?>
            </td>
            <td style="width: 50%; text-align: right;">
                <strong>Sistema de Gestión:</strong><br>
                Gestiones Tecnológicas<br>
                © <?= date('Y') ?> - Todos los derechos reservados<br>
                Reporte ID: <?= uniqid() ?>
            </td>
        </tr>
    </table>
</div>

</body>
</html>