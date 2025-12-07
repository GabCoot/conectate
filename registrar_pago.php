<?php
header("Content-Type: application/json; charset=utf-8");
ob_clean();
require_once("conexion.php");

$response = [];

try {
    // Validar parámetros
    $required = ['usuario_id','contrato_id','metodo_pago','monto_total','referencia','fecha_pago_ids'];
    foreach ($required as $r) {
        if (!isset($_POST[$r])) {
            throw new Exception("Falta parámetro: $r");
        }
    }

    $usuario_id = intval($_POST['usuario_id']);
    $contrato_id = intval($_POST['contrato_id']);
    $metodo_pago = trim($_POST['metodo_pago']);
    $monto_total = floatval($_POST['monto_total']);
    $referencia = trim($_POST['referencia']);

    $fecha_pago_ids = json_decode($_POST['fecha_pago_ids'], true);
    if (!is_array($fecha_pago_ids) || count($fecha_pago_ids) == 0) {
        throw new Exception("fecha_pago_ids inválido");
    }

    $fecha_pago = date("Y-m-d H:i:s");

    // Iniciar transacción
    $conn->begin_transaction();

    // Insertar en pagos
    $sql_pago = "INSERT INTO pagos (fecha_pago, monto, metodo_pago, referencia, usuario_id, contrato_id)
                 VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql_pago);
    if (!$stmt) throw new Exception("Error prepare pagos: ".$conn->error);

    $stmt->bind_param("sdssii", $fecha_pago, $monto_total, $metodo_pago, $referencia, $usuario_id, $contrato_id);

    if (!$stmt->execute()) {
        throw new Exception("Error execute pagos: " . $stmt->error);
    }

    $pago_id = $stmt->insert_id;
    $stmt->close();

    // Insertar detalles
    $sql_detalle = "INSERT INTO pagos_detalle (pago_id, fecha_pago_id, fecha_registro)
                    VALUES (?, ?, NOW())";
    $stmt_detalle = $conn->prepare($sql_detalle);
    if (!$stmt_detalle) throw new Exception("Error prepare detalles: ".$conn->error);

    foreach ($fecha_pago_ids as $fid) {
        $fid = intval($fid);
        $stmt_detalle->bind_param("ii", $pago_id, $fid);
        if (!$stmt_detalle->execute()) {
            throw new Exception("Error execute detalle: " . $stmt_detalle->error);
        }
    }
    $stmt_detalle->close();

    $conn->commit();

    $response["success"] = true;
    $response["pago_id"] = $pago_id;
    $response["referencia"] = $referencia;

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    $response["success"] = false;
    $response["error"] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
