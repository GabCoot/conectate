<?php
// export_contracts.php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/conexion.php';

use Dompdf\Dompdf;
use Dompdf\Options;

set_time_limit(300);

$tmpDir = __DIR__ . '/tmp_export_' . uniqid();
if (!mkdir($tmpDir, 0777, true) && !is_dir($tmpDir)) {
    die("No se pudo crear carpeta temporal.");
}

$pdfFiles = [];
$zipPath = null;

try {
    $sql = "SELECT c.id AS contrato_id, c.usuario_id, c.paquete_id, c.fecha_inicio, c.duracion_meses, c.precio_mensual, c.estado,
                   u.nombre AS cliente_nombre, u.correo AS cliente_correo, u.telefono AS cliente_telefono, u.direccion AS cliente_direccion,
                   p.nombre AS paquete_nombre
            FROM contratos c
            LEFT JOIN usuarios u ON u.id = c.usuario_id
            LEFT JOIN paquetes p ON p.id = c.paquete_id
            ORDER BY c.id ASC";
    $res = $conexion->query($sql);
    if (!$res) throw new Exception("Error en consulta: " . $conexion->error);

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    while ($row = $res->fetch_assoc()) {
        $fechaInicio = $row['fecha_inicio'] ?? '';
        $duracion = intval($row['duracion_meses'] ?? 0);
        $fechaFin = $fechaInicio ? date('Y-m-d', strtotime("+$duracion months", strtotime($fechaInicio))) : '';

        $cliente = htmlspecialchars($row['cliente_nombre'] ?? 'N/A');
        $correo = htmlspecialchars($row['cliente_correo'] ?? '-');
        $telefono = htmlspecialchars($row['cliente_telefono'] ?? '-');
        $direccion = htmlspecialchars($row['cliente_direccion'] ?? '-');
        $paquete = htmlspecialchars($row['paquete_nombre'] ?? '-');
        $precio = number_format($row['precio_mensual'] ?? 0, 2);

        $html = "
        <html><head><meta charset='utf-8'/><style>
        body{font-family: DejaVu Sans, Arial, sans-serif; font-size:12px; color:#222}
        .title{font-weight:bold; margin-bottom:8px; font-size:16px}
        .section{margin-bottom:10px}
        .small{font-size:11px;color:#666}
        table.sig{width:100%; margin-top:40px}
        </style></head><body>
        <div class='title'>Contrato de Servicio — Conect@T</div>
        <div class='small'>Contrato ID: ".htmlspecialchars($row['contrato_id'])."</div>
        <div class='section'><strong>Cliente:</strong> $cliente<br/><strong>Correo:</strong> $correo — <strong>Tel:</strong> $telefono<br/><strong>Dirección:</strong> $direccion</div>
        <div class='section'><strong>Paquete:</strong> $paquete — <strong>Precio mensual:</strong> $$precio<br/><strong>Inicio:</strong> $fechaInicio — <strong>Duración:</strong> $duracion meses — <strong>Fin aproximado:</strong> $fechaFin</div>
        <div class='section'><p>Este documento es el contrato de servicios. (Reemplaza este párrafo con el texto legal real si lo deseas.)</p></div>
        <table class='sig'><tr><td>_________________________<br/>Firma Cliente</td><td>_________________________<br/>Firma Proveedor</td></tr></table>
        </body></html>";

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4','portrait');
        $dompdf->render();

        $filename = 'contrato_' . $row['contrato_id'] . '_' . preg_replace('/[^a-z0-9-_]/i','_', $cliente) . '.pdf';
        $filepath = $tmpDir . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($filepath, $dompdf->output());
        $pdfFiles[] = $filepath;
    }

    if (count($pdfFiles) === 0) throw new Exception("No hay contratos para exportar.");

    $zipName = 'contratos_' . date('Ymd_His') . '.zip';
    $zipPath = $tmpDir . DIRECTORY_SEPARATOR . $zipName;
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE) throw new Exception("No se pudo crear el ZIP.");
    foreach ($pdfFiles as $file) $zip->addFile($file, basename($file));
    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipName . '"');
    header('Content-Length: ' . filesize($zipPath));
    readfile($zipPath);

} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . htmlspecialchars($e->getMessage());
} finally {
    // limpieza
    foreach ($pdfFiles as $f) if (file_exists($f)) unlink($f);
    if ($zipPath && file_exists($zipPath)) unlink($zipPath);
    if (is_dir($tmpDir)) rmdir($tmpDir);
    if (isset($conexion) && $conexion) $conexion->close();
}
