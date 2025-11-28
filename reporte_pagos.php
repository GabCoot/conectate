<?php
require_once "vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

include "pago.php";



$sql_pagados = "
    SELECT 
        p.id, p.referencia, p.monto, p.metodo_pago, p.fecha_pago,
        u.nombre AS cliente, u.correo,
        c.id AS contrato_id, paq.nombre AS paquete
    FROM pagos p
    JOIN usuarios u ON p.usuario_id = u.id
    JOIN contratos c ON p.contrato_id = c.id
    JOIN paquetes paq ON c.paquete_id = paq.id
    WHERE p.estado = 'Pagado'
    ORDER BY p.fecha_pago DESC
";
$pagados = $conexion->query($sql_pagados);

$sql_pendientes = "
    SELECT 
        p.id, p.referencia, p.monto, p.metodo_pago, f.fecha_pago_esperada,
        u.nombre AS cliente, u.correo,
        c.id AS contrato_id, paq.nombre AS paquete
    FROM pagos p
    JOIN pagos_detalle pd ON pd.pago_id = p.id
    JOIN fechas_pagos f ON f.id = pd.fecha_pago_id
    JOIN usuarios u ON p.usuario_id = u.id
    JOIN contratos c ON p.contrato_id = c.id
    JOIN paquetes paq ON c.paquete_id = paq.id
    WHERE f.estado = 'Pendiente'
    ORDER BY f.fecha_pago_esperada ASC
";
$pendientes = $conexion->query($sql_pendientes);





$excel = new Spreadsheet();


// HOJA 1 — PAGOS COMPLETADOS


$sheet1 = $excel->getActiveSheet();
$sheet1->setTitle("Pagos Completados");

// Título
$sheet1->setCellValue("A1", "REPORTE DE PAGOS COMPLETADOS");
$sheet1->mergeCells("A1:H1");
$sheet1->getStyle("A1")->getFont()->setSize(18)->setBold(true);
$sheet1->getStyle("A1")->getAlignment()->setHorizontal("center");

// Encabezado
$headers = ["Referencia", "Cliente", "Correo", "Monto", "Método", "Fecha Pago", "Contrato", "Paquete"];
$sheet1->fromArray($headers, null, "A3");

//encabezado
$sheet1->getStyle("A3:H3")->applyFromArray([
    "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"]],
    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "0044AA"]],
    "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
]);


$row = 4;
while ($p = $pagados->fetch_assoc()) {
    $sheet1->fromArray([
        $p["referencia"],
        $p["cliente"],
        $p["correo"],
        $p["monto"],
        $p["metodo_pago"],
        $p["fecha_pago"],
        $p["contrato_id"],
        $p["paquete"]
    ], null, "A$row");

    $row++;
}


$sheet1->getStyle("A3:H" . ($row - 1))->applyFromArray([
    "borders" => [
        "allBorders" => [
            "borderStyle" => Border::BORDER_THIN,
            "color" => ["rgb" => "000000"]
        ]
    ]
]);


foreach (range('A', 'H') as $col) {
    $sheet1->getColumnDimension($col)->setAutoSize(true);
}



// HOJA 2 — PAGOS PENDIENTES


$sheet2 = $excel->createSheet();
$sheet2->setTitle("Pagos Pendientes");

$sheet2->setCellValue("A1", "REPORTE DE PAGOS PENDIENTES");
$sheet2->mergeCells("A1:H1");
$sheet2->getStyle("A1")->getFont()->setSize(18)->setBold(true);
$sheet2->getStyle("A1")->getAlignment()->setHorizontal("center");

// Encabezados
$sheet2->fromArray($headers, null, "A3");

// Estilo encabezados
$sheet2->getStyle("A3:H3")->applyFromArray([
    "font" => ["bold" => true, "color" => ["rgb" => "FFFFFF"]],
    "fill" => ["fillType" => Fill::FILL_SOLID, "startColor" => ["rgb" => "AA0000"]],
    "alignment" => ["horizontal" => Alignment::HORIZONTAL_CENTER],
]);

$row = 4;
while ($p = $pendientes->fetch_assoc()) {
    $sheet2->fromArray([
        $p["referencia"],
        $p["cliente"],
        $p["correo"],
        $p["monto"],
        $p["metodo_pago"],
        $p["fecha_pago_esperada"],
        $p["contrato_id"],
        $p["paquete"]
    ], null, "A$row");

    $row++;
}


$sheet2->getStyle("A3:H" . ($row - 1))->applyFromArray([
    "borders" => [
        "allBorders" => [
            "borderStyle" => Border::BORDER_THIN,
            "color" => ["rgb" => "000000"]
        ]
    ]
]);


foreach (range('A', 'H') as $col) {
    $sheet2->getColumnDimension($col)->setAutoSize(true);
}



header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=Reporte_Pagos.xlsx");

$writer = new Xlsx($excel);
$writer->save("php://output");
exit;

?>
