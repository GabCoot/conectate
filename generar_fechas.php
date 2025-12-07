<?php
include 'conexion.php';
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contrato_id = $_POST['contrato_id'] ?? null;

    if (!$contrato_id) {
        echo json_encode(["status" => "error", "msg" => "Falta el ID del contrato"]);
        exit;
    }

    // Obtener datos del contrato
    $query = $conn->prepare("
        SELECT fecha_inicio, duracion_meses, precio_mensual
        FROM contratos WHERE id = ?
    ");
    $query->bind_param("i", $contrato_id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(["status" => "error", "msg" => "Contrato no encontrado"]);
        exit;
    }

    $contrato = $result->fetch_assoc();
    $fecha_inicio = new DateTime($contrato['fecha_inicio']);
    $duracion_meses = intval($contrato['duracion_meses']);
    $precio_mensual = floatval($contrato['precio_mensual']);

    // Calcular fecha de último pago
    $fechaUltimoPago = clone $fecha_inicio;
    $fechaUltimoPago->modify("+" . ($duracion_meses - 1) . " month");
    $fechaUltimoPagoStr = $fechaUltimoPago->format('Y-m-d');

    // Actualizar fecha de último pago
    $update = $conn->prepare("UPDATE contratos SET fecha_ultimo_pago = ? WHERE id = ?");
    $update->bind_param("si", $fechaUltimoPagoStr, $contrato_id);
    $update->execute();

    // Eliminar fechas de pagos anteriores
    $conn->query("DELETE FROM fechas_pagos WHERE contrato_id = $contrato_id");

    // Insertar fechas mensuales
    $insert = $conn->prepare("
        INSERT INTO fechas_pagos (contrato_id, numero_pago, fecha_pago_esperada, monto, estado)
        VALUES (?, ?, ?, ?, 'Pendiente')
    ");

    $fechasGeneradas = [];

    for ($i = 0; $i < $duracion_meses; $i++) {
        $fechaPago = clone $fecha_inicio;
        $fechaPago->modify("+$i month");
        $fechaPagoStr = $fechaPago->format('Y-m-d');

        $numeroPago = $i + 1;
        $insert->bind_param("iisd", $contrato_id, $numeroPago, $fechaPagoStr, $precio_mensual);
        $insert->execute();

        $fechasGeneradas[] = [
            'numero_pago' => $numeroPago,
            'fecha_pago_esperada' => $fechaPagoStr,
            'monto' => $precio_mensual,
            'estado' => 'Pendiente'
        ];
    }

    echo json_encode([
        'status' => 'success',
        'message' => 'Calendario de pagos generado correctamente',
        'fecha_ultimo_pago' => $fechaUltimoPagoStr,
        'fechas_pagos' => $fechasGeneradas
    ]);

    $query->close();
    $update->close();
    $insert->close();
    $conn->close();
}
?>
