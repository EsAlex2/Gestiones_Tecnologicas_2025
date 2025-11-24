<?php
// reports/export_pdf.php - Versión mejorada con gráficas y estilo profesional
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

// Obtener datos del inventario
$stmt = $pdo->prepare("SELECT i.sku, i.name, i.description, i.quantity, i.unit_price, 
                              c.name as category, s.name as supplier,
                              (i.quantity * i.unit_price) as total
                       FROM items i 
                       LEFT JOIN categories c ON i.category_id = c.id 
                       LEFT JOIN suppliers s ON i.supplier_id = s.id 
                       WHERE i.user_id = ? 
                       ORDER BY i.name");
$stmt->execute([$target_user_id]);
$rows = $stmt->fetchAll();

// Obtener estadísticas para las gráficas
$catStmt = $pdo->prepare("SELECT COALESCE(c.name,'Sin categoría') as cat, 
                                 COALESCE(SUM(i.quantity*i.unit_price),0) as val,
                                 COALESCE(SUM(i.quantity),0) as qty
                          FROM items i 
                          LEFT JOIN categories c ON i.category_id=c.id 
                          WHERE i.user_id=? 
                          GROUP BY IFNULL(i.category_id,0) 
                          ORDER BY val DESC 
                          LIMIT 8");
$catStmt->execute([$target_user_id]); 
$catData = $catStmt->fetchAll();

// Estadísticas generales
$totalItems = count($rows);
$totalQuantity = array_sum(array_column($rows, 'quantity'));
$totalValue = array_sum(array_column($rows, 'total'));

$vendor = __DIR__ . '/../dompdf/vendor/autoload.php';
if (!file_exists($vendor)) {
    die('Dompdf no está instalado. Ejecuta: composer require dompdf/dompdf');
}

require $vendor;
use Dompdf\Dompdf;
use Dompdf\Options;

// Configurar opciones de Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);

// Generar HTML del reporte
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inventario</title>
    <style>
        @page { margin: 50px; }
        body { 
            font-family: DejaVu Sans, sans-serif; 
            line-height: 1.4;
            color: #333;
        }
        .header { 
            border-bottom: 3px solid #1e40af;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo { 
            font-size: 24px; 
            font-weight: bold; 
            color: #1e40af;
            margin-bottom: 5px;
        }
        .company { 
            font-size: 18px; 
            color: #64748b;
            margin-bottom: 20px;
        }
        .report-title { 
            font-size: 28px; 
            font-weight: bold; 
            color: #0f172a;
            margin-bottom: 10px;
        }
        .report-date { 
            color: #64748b; 
            margin-bottom: 30px;
        }
        .user-info {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border-left: 4px solid #1e40af;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #1e40af;
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background: #1e40af;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        tr:nth-child(even) {
            background: #f8fafc;
        }
        .total-row {
            background: #0f172a !important;
            color: white;
            font-weight: bold;
        }
        .charts-section {
            margin: 40px 0;
        }
        .chart-container {
            margin: 20px 0;
            page-break-inside: avoid;
        }
        .chart-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #1e40af;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 12px;
            text-align: center;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">GESTIONES TECNOLÓGICAS</div>
        <div class="company">Sistema de Gestión Empresarial</div>
        <div class="report-title">REPORTE DE INVENTARIO</div>
        <div class="report-date">Generado el: ' . date('d/m/Y H:i:s') . '</div>
    </div>

    <div class="user-info">
        <h3 style="margin:0 0 15px 0; color:#1e40af;">INFORMACIÓN DEL USUARIO</h3>
        <table style="width:100%; background:transparent;">
            <tr>
                <td style="border:none; padding:5px 0; width:30%;"><strong>Nombre:</strong></td>
                <td style="border:none; padding:5px 0;">' . htmlspecialchars($target_user_data['first_name'] . ' ' . $target_user_data['last_name']) . '</td>
            </tr>
            <tr>
                <td style="border:none; padding:5px 0;"><strong>Usuario:</strong></td>
                <td style="border:none; padding:5px 0;">' . htmlspecialchars($target_user_data['username']) . '</td>
            </tr>
            <tr>
                <td style="border:none; padding:5px 0;"><strong>Email:</strong></td>
                <td style="border:none; padding:5px 0;">' . htmlspecialchars($target_user_data['email']) . '</td>
            </tr>
            <tr>
                <td style="border:none; padding:5px 0;"><strong>Teléfono:</strong></td>
                <td style="border:none; padding:5px 0;">' . htmlspecialchars($target_user_data['phone'] ?: 'No especificado') . '</td>
            </tr>
            <tr>
                <td style="border:none; padding:5px 0;"><strong>Rol:</strong></td>
                <td style="border:none; padding:5px 0;">' . get_user_role_name($target_user_data['role']) . '</td>
            </tr>
        </table>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">TOTAL DE ÍTEMS</div>
            <div class="stat-value">' . number_format($totalItems) . '</div>
        </div>
        <div class="stat-card" style="background:#059669;">
            <div class="stat-label">EXISTENCIAS TOTALES</div>
            <div class="stat-value">' . number_format($totalQuantity) . '</div>
        </div>
        <div class="stat-card" style="background:#d97706;">
            <div class="stat-label">VALOR TOTAL</div>
            <div class="stat-value">$' . number_format($totalValue, 2) . '</div>
        </div>
    </div>

    <div class="charts-section">
        <h3 style="color:#1e40af; border-bottom:2px solid #1e40af; padding-bottom:10px;">ANÁLISIS POR CATEGORÍAS</h3>
        
        <!-- Gráfica de valor por categoría -->
        <div class="chart-container">
            <div class="chart-title">Distribución del Valor por Categoría</div>
            <table style="width:100%;">
                <tr>
                    <th style="width:60%;">Categoría</th>
                    <th style="width:20%; text-align:right;">Valor</th>
                    <th style="width:20%; text-align:right;">Porcentaje</th>
                </tr>';

$totalVal = array_sum(array_column($catData, 'val'));
foreach ($catData as $cat) {
    $percentage = $totalVal > 0 ? ($cat['val'] / $totalVal) * 100 : 0;
    $html .= '
                <tr>
                    <td>' . htmlspecialchars($cat['cat']) . '</td>
                    <td style="text-align:right;">$' . number_format($cat['val'], 2) . '</td>
                    <td style="text-align:right;">' . number_format($percentage, 1) . '%</td>
                </tr>';
}

$html .= '
                <tr class="total-row">
                    <td><strong>TOTAL</strong></td>
                    <td style="text-align:right;"><strong>$' . number_format($totalVal, 2) . '</strong></td>
                    <td style="text-align:right;"><strong>100%</strong></td>
                </tr>
            </table>
        </div>

        <!-- Gráfica de cantidad por categoría -->
        <div class="chart-container">
            <div class="chart-title">Distribución de Cantidad por Categoría</div>
            <table style="width:100%;">
                <tr>
                    <th style="width:60%;">Categoría</th>
                    <th style="width:20%; text-align:right;">Cantidad</th>
                    <th style="width:20%; text-align:right;">Porcentaje</th>
                </tr>';

$totalQty = array_sum(array_column($catData, 'qty'));
foreach ($catData as $cat) {
    $percentage = $totalQty > 0 ? ($cat['qty'] / $totalQty) * 100 : 0;
    $html .= '
                <tr>
                    <td>' . htmlspecialchars($cat['cat']) . '</td>
                    <td style="text-align:right;">' . number_format($cat['qty']) . ' und.</td>
                    <td style="text-align:right;">' . number_format($percentage, 1) . '%</td>
                </tr>';
}

$html .= '
                <tr class="total-row">
                    <td><strong>TOTAL</strong></td>
                    <td style="text-align:right;"><strong>' . number_format($totalQty) . ' und.</strong></td>
                    <td style="text-align:right;"><strong>100%</strong></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="page-break"></div>

    <h3 style="color:#1e40af; border-bottom:2px solid #1e40af; padding-bottom:10px;">DETALLE DEL INVENTARIO</h3>
    
    <table>
        <thead>
            <tr>
                <th>SKU</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Proveedor</th>
                <th style="text-align:right;">Cantidad</th>
                <th style="text-align:right;">Precio Unit.</th>
                <th style="text-align:right;">Valor Total</th>
            </tr>
        </thead>
        <tbody>';

foreach ($rows as $r) {
    $html .= '
            <tr>
                <td>' . htmlspecialchars($r['sku']) . '</td>
                <td>' . htmlspecialchars($r['name']) . '</td>
                <td>' . htmlspecialchars($r['category'] ?? 'Sin categoría') . '</td>
                <td>' . htmlspecialchars($r['supplier'] ?? 'Sin proveedor') . '</td>
                <td style="text-align:right;">' . number_format($r['quantity']) . '</td>
                <td style="text-align:right;">$' . number_format($r['unit_price'], 2) . '</td>
                <td style="text-align:right;">$' . number_format($r['total'], 2) . '</td>
            </tr>';
}

$html .= '
            <tr class="total-row">
                <td colspan="4"><strong>TOTAL GENERAL</strong></td>
                <td style="text-align:right;"><strong>' . number_format($totalQuantity) . '</strong></td>
                <td style="text-align:right;"></td>
                <td style="text-align:right;"><strong>$' . number_format($totalValue, 2) . '</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Reporte generado automáticamente por el Sistema de Gestiones Tecnológicas</p>
        <p>© ' . date('Y') . ' Gestiones Tecnológicas - Todos los derechos reservados</p>
    </div>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Generar nombre del archivo
$filename = 'reporte_inventario_' . $target_user_data['username'] . '_' . date('Y-m-d') . '.pdf';

$dompdf->stream($filename, ['Attachment' => 1]);
exit;