<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
include __DIR__ . '/conexion.php';
require __DIR__ . "/ip.php"; #Llamo al ip

$email = $_POST['email'] ?? '';

if (!$email) {
    echo json_encode(["status" => "error", "msg" => "Email vacío"]);
    exit;
}

// Verificar si existe el correo
$stmt = $conn->prepare("SELECT Id FROM usuarios WHERE correo = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo json_encode(["status" => "error", "msg" => "Correo no encontrado"]);
    exit;
}


$token = bin2hex(random_bytes(16));
$expires = date("Y-m-d H:i:s", strtotime("+30 minutes"));


$update = $conn->prepare("UPDATE usuarios SET reset_token = ?, reset_expires = ? WHERE correo = ?");
$update->bind_param("sss", $token, $expires, $email);
$update->execute();


$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'jesusuxul652@gmail.com';
    $mail->Password   = 'zcmqltvhswuvbxay';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    $mail->setFrom('jesusuxul652@gmail.com', 'AppUx');
    $mail->addAddress($email);
    $mail->Subject = "Recuperación de contraseña";
    
    #$link = "http://192.168.1.74/ProyectoBuco/reset.php?token=$token";
    #$link = "http://192.168.1.85/ProyectoBuco/reset.php?token=$token";
    #$link = "http://Uxul/ProyectoBuco/reset.php?token=$token";
    #$link = "$SERVER_URL/verificar.php?token=$verify_token";

    $link = "$SERVER_URL/reset.php?token=$token";
    

    $mail->Body = "Haz clic en este enlace para actualizar tu contraseña: $link \nEste enlace expira en 30 minutos.";

    $mail->send();
    echo json_encode(["status" => "success", "msg" => "Correo enviado"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "msg" => $mail->ErrorInfo ?: "Error al enviar correo"]);
}

$stmt->close();
$update->close();
$conn->close();
