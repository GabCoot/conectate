<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// HOY EXACTO
$hoy = date("Y-m-d");


//PAGOS QUE VENCEN HOY

$sql = "
    SELECT 
        u.id AS usuario_id,
        u.nombre,
        u.correo,
        f.id AS fecha_pago_id,
        f.fecha_pago_esperada,
        f.monto
    FROM fechas_pagos f
    JOIN contratos c ON c.id = f.contrato_id
    JOIN usuarios u ON u.id = c.usuario_id
    WHERE f.estado = 'Pendiente'
      AND f.fecha_pago_esperada = '$hoy'
      AND NOT EXISTS (
            SELECT 1 FROM notificaciones n
            WHERE n.fecha_pago_id = f.id
        )
";

// Ejecutar consulta
$result = $conexion->query($sql);

// Si falla consulta → log de error
if (!$result) {
    error_log(" ERROR SQL en notificar_pagos.php: " . $conexion->error);
    return;
}

// Si no hay pagos → log y salir
if ($result->num_rows === 0) {
    error_log(" No hay pagos para enviar notificación hoy ($hoy).");
    return;
}
// ENVIAR CORREO POR CADA PAGO

while ($row = $result->fetch_assoc()) {

    $correo = $row['correo'];
    $nombre = $row['nombre'];
    $fecha = $row['fecha_pago_esperada'];
    $monto = $row['monto'];

    $mail = new PHPMailer(true);

    try {
        
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'julio2escuela19@gmail.com';
        $mail->Password = 'ohtoakqknhpybltw';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('julio2escuela19@gmail.com', 'Conectate Internet');
        $mail->addAddress($correo);

        $mail->Subject = "Recordatorio de Pago - Vence Hoy";
        $mail->Body = "
Estimado/a $nombre,

Este es un recordatorio de que tu pago por el monto de $".number_format($monto,2)." vence el dia de HOY (".date('d/m/Y', strtotime($fecha)).").

Tienes un periodo adicional de hasta 5 días después de la fecha de vencimiento para realizar tu pago sin afectar tu servicio. 
En caso de no efectuarlo dentro de este plazo, tu servicio podría ser suspendido temporalmente.

Por favor realiza tu pago a la brevedad para evitar inconvenientes.

Gracias por su atencion,
Equipo Conectate Internet
        ";

        $mail->send();

        
        $conexion->query("
            INSERT INTO notificaciones (usuario_id, fecha_pago_id, tipo, mensaje)
            VALUES ({$row['usuario_id']}, {$row['fecha_pago_id']}, 'Recordatorio', 'Pago vence hoy')
        ");

        error_log(" CORREO ENVIADO a $correo (fecha_pago_id={$row['fecha_pago_id']})");

    } catch (Exception $e) {
        error_log("ERROR enviando correo a $correo: " . $mail->ErrorInfo);
    }
}

?>
