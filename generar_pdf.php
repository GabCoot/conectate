<?php
require_once "dompdf/autoload.inc.php";

use Dompdf\Dompdf;
use Dompdf\Options;

include 'reportes.php';


$tipo_reporte = isset($_GET['tipo']) ? $_GET['tipo'] : 'clientes';
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';

$resultado = null;
switch($tipo_reporte) {
    case 'clientes':
        $resultado = obtener_reporte_clientes($conexion, $filtro);
        $titulo = 'REPORTE DE CLIENTES';
        
        break;
    case 'contratos':
        $resultado = obtener_reporte_contratos($conexion, $filtro);
        $titulo = 'REPORTE DE CONTRATOS';
        
        break;
    case 'pagos':
        $resultado = obtener_reporte_pagos($conexion, $filtro, $estado);
        $titulo = 'REPORTE DE PAGOS';
       
        break;
}

$estadisticas = obtener_estadisticas_reportes($conexion);



ob_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $titulo ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #333;
            line-height: 1.4;
        }
        
        .header {
            background:  #667eea;
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .header .empresa {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .header .fecha {
            font-size: 11px;
            opacity: 0.9;
        }
        
        .estadisticas {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        
        .stat-card {
            display: table-cell;
            width: 25%;
            padding: 15px;
            text-align: center;
            border: 2px solid #dee2e6;
            background: #f8f9fa;
        }
        
        .stat-card h3 {
            font-size: 10px;
            color: #666;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        .stat-card .numero {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-card .detalle {
            font-size: 9px;
            color: #666;
        }
        
        .stat-card.azul { border-top: 4px solid #667eea; }
        .stat-card.verde { border-top: 4px solid #28a745; }
        .stat-card.amarillo { border-top: 4px solid #ffc107; }
        .stat-card.cyan { border-top: 4px solid #17a2b8; }
        
        .seccion-titulo {
            background: #f8f9fa;
            padding: 10px 15px;
            margin: 20px 0 10px 0;
            border-left: 4px solid #667eea;
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
        
        table.tabla-datos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9px;
        }
        
        table.tabla-datos thead {
            background: #667eea;
            color: white;
        }
        
        table.tabla-datos th {
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #667eea;
        }
        
        table.tabla-datos td {
            padding: 8px;
            border: 1px solid #dee2e6;
        }
        
        table.tabla-datos tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        table.tabla-datos tbody tr:hover {
            background: #e9ecef;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
        }
        
        .badge-activo {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-inactivo {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .badge-suspendido {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-pagado {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-pendiente {
            background: #fff3cd;
            color: #856404;
        }
        
        .monto-positivo {
            color: #28a745;
            font-weight: bold;
        }
        
        .monto-negativo {
            color: #dc3545;
            font-weight: bold;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .progreso-bar {
            width: 100%;
            height: 15px;
            background: #e9ecef;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }
        
        .progreso-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            float: left;
        }
        
        .progreso-text {
            position: absolute;
            width: 100%;
            text-align: center;
            line-height: 15px;
            font-size: 8px;
            font-weight: bold;
            color: #333;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 9px;
            color: #666;
            padding: 10px 0;
            border-top: 1px solid #dee2e6;
        }
        
        .sin-datos {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 12px;
            font-style: italic;
        }
    </style>
</head>
<body>


<div class="header">
    <div class="empresa">CONECTATE</div>
    <h1><?= $titulo ?></h1>
    <div class="fecha">Generado el <?= date('d/m/Y H:i') ?></div>
</div>


<div class="estadisticas">
    <div class="stat-card azul">
        <h3>Total Clientes</h3>
        <div class="numero"><?= $estadisticas['total_clientes'] ?></div>
        <div class="detalle">Activos: <?= $estadisticas['clientes_activos'] ?></div>
    </div>
    <div class="stat-card verde">
        <h3>Contratos</h3>
        <div class="numero"><?= $estadisticas['total_contratos'] ?></div>
        <div class="detalle">Activos: <?= $estadisticas['contratos_activos'] ?></div>
    </div>
    <div class="stat-card amarillo">
        <h3>Pagos Completados</h3>
        <div class="numero"><?= $estadisticas['total_pagos_completados'] ?></div>
        <div class="detalle">$<?= number_format($estadisticas['monto_pagos_completados'], 2) ?></div>
    </div>
    <div class="stat-card cyan">
        <h3>Ingresos del Mes</h3>
        <div class="numero">$<?= number_format($estadisticas['ingresos_mes'], 2) ?></div>
    </div>
</div>


<div class="seccion-titulo"> Datos Detallados</div>

<?php if ($resultado && $resultado->num_rows > 0): ?>

    <?php if ($tipo_reporte == 'clientes'): ?>
   
    <table class="tabla-datos">
        <thead>
            <tr>
                <th width="5%">ID</th>
                <th width="20%">Cliente</th>
                <th width="20%">Correo</th>
                <th width="12%">Teléfono</th>
                <th width="18%">Dirección</th>
                <th width="10%">Contratos</th>
                <th width="8%">Estado</th>
                <th width="7%">Pagado</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $resultado->fetch_assoc()): ?>
            <tr>
                <td class="text-center"><?= $row['id'] ?></td>
                <td><strong><?= htmlspecialchars($row['nombre']) ?></strong></td>
                <td><?= htmlspecialchars($row['correo']) ?></td>
                <td><?= htmlspecialchars($row['telefono'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars(substr($row['direccion'] ?? 'N/A', 0, 30)) ?></td>
                <td class="text-center">
                    <strong><?= $row['num_contratos'] ?></strong><br>
                    <small>A: <?= $row['contratos_activos'] ?> | S: <?= $row['contratos_suspendidos'] ?></small>
                </td>
                <td class="text-center">
                    <span class="badge badge-<?= $row['activo'] ? 'activo' : 'inactivo' ?>">
                        <?= $row['activo'] ? 'Activo' : 'Inactivo' ?>
                    </span>
                </td>
                <td class="text-right monto-positivo">$<?= number_format($row['total_pagado'], 2) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    
    <?php elseif ($tipo_reporte == 'contratos'): ?>
   
    <table class="tabla-datos">
        <thead>
            <tr>
                <th width="5%">ID</th>
                <th width="18%">Cliente</th>
                <th width="15%">Paquete</th>
                <th width="8%">$/Mes</th>
                <th width="10%">Inicio</th>
                <th width="7%">Meses</th>
                <th width="10%">Estado</th>
                <th width="15%">Progreso</th>
                <th width="12%">Pagado</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $resultado->fetch_assoc()): 
            $porcentaje = $row['total_pagos'] > 0 ? round(($row['pagos_completados'] / $row['total_pagos']) * 100) : 0;
        ?>
            <tr>
                <td class="text-center"><strong>#<?= $row['contrato_id'] ?></strong></td>
                <td><?= htmlspecialchars(substr($row['cliente'], 0, 25)) ?></td>
                <td>
                    <?= htmlspecialchars($row['paquete']) ?><br>
                    <small><?= htmlspecialchars($row['velocidad']) ?></small>
                </td>
                <td class="text-right"><strong>$<?= number_format($row['precio_mensual'], 2) ?></strong></td>
                <td class="text-center"><?= date('d/m/Y', strtotime($row['fecha_inicio'])) ?></td>
                <td class="text-center"><?= $row['duracion_meses'] ?></td>
                <td class="text-center">
                    <span class="badge badge-<?= strtolower($row['estado_contrato']) ?>">
                        <?= $row['estado_contrato'] ?>
                    </span>
                </td>
                <td>
                    <div class="pago-status">
                        <strong><?= $porcentaje ?>%</strong> completado
                        <span>(<?= $row['pagos_completados'] ?>/<?= $row['total_pagos'] ?>)</span>
                    </div>
                </td>
                <td class="text-right monto-positivo">$<?= number_format($row['total_pagado'], 2) ?></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    
    <?php elseif ($tipo_reporte == 'pagos'): ?>
   
    <table class="tabla-datos">
        <thead>
            <tr>
                <th width="18%">Referencia</th>
                <th width="18%">Cliente</th>
                <th width="18%">Correo</th>
                <th width="12%">Paquete</th>
                <th width="10%">Monto</th>
                <th width="10%">Método</th>
                <th width="9%">Fecha</th>
                <th width="5%">Estado</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $resultado->fetch_assoc()): ?>
            <tr>
                <td><small><?= htmlspecialchars(substr($row['referencia'], 0, 30)) ?></small></td>
                <td><?= htmlspecialchars(substr($row['cliente'], 0, 25)) ?></td>
                <td><?= htmlspecialchars($row['correo']) ?></td>
                <td><?= htmlspecialchars($row['paquete']) ?></td>
                <td class="text-right"><strong>$<?= number_format($row['monto'], 2) ?></strong></td>
                <td class="text-center"><?= htmlspecialchars($row['metodo_pago']) ?></td>
                <td class="text-center"><?= $row['fecha_pago'] ? date('d/m/Y', strtotime($row['fecha_pago'])) : '-' ?></td>
                <td class="text-center">
                    <span class="badge badge-<?= strtolower($row['estado_pago']) ?>">
                        <?= htmlspecialchars($row['estado_pago']) ?>
                    </span>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    <?php endif; ?>

<?php else: ?>
    <div class="sin-datos">
        No se encontraron registros para mostrar en este reporte.
    </div>
<?php endif; ?>

<div class="footer">
    Sistema Conéctate - Reporte generado el <?= date('d/m/Y H:i:s') ?>
</div>

</body>
</html>
<?php
$html = ob_get_clean();


$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = 'Reporte_' . ucfirst($tipo_reporte) . '_' . date('Ymd_His') . '.pdf';
$dompdf->stream($filename, array("Attachment" => true));
?>