<?php
include 'conexion.php';
header("Content-Type: application/json; charset=UTF-8");
date_default_timezone_set('America/Mexico_City');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contrato_id = $_POST['contrato_id'] ?? null;

    if (!$contrato_id) {
        echo json_encode(['status' => 'error', 'message' => 'Falta el ID del contrato']);
        exit;
    }

    $fecha_actual = date('Y-m-d');

    $update = $conn->prepare("
        UPDATE fechas_pagos
        SET estado = CASE
            WHEN estado != 'Pagado' AND fecha_pago_esperada < ? THEN 'Atrasado'
            WHEN estado != 'Pagado' AND fecha_pago_esperada >= ? THEN 'Pendiente'
            ELSE estado
        END
        WHERE contrato_id = ?
    ");
    $update->bind_param("ssi", $fecha_actual, $fecha_actual, $contrato_id);
    $update->execute();

    $query = $conn->prepare("
        SELECT id, numero_pago, fecha_pago_esperada AS fecha_pago, monto, estado
        FROM fechas_pagos
        WHERE contrato_id = ?
        ORDER BY numero_pago ASC
    ");
    $query->bind_param("i", $contrato_id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'No hay fechas de pago registradas']);
        exit;
    }

    $fechas = [];
    while ($row = $result->fetch_assoc()) {
        $fechas[] = [
            'id' => intval($row['id']),
            'numero_pago' => intval($row['numero_pago']),
            'fecha_pago' => $row['fecha_pago'],
            'monto' => floatval($row['monto']),
            'estado' => $row['estado']
        ];
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Fechas obtenidas correctamente',
        'fecha_actual' => $fecha_actual,
        'fechas' => $fechas
    ]);
}
