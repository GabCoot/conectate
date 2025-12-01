<?php
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include 'conexion.php';

$correo = $_POST['correo'] ?? '';

if (empty($correo)) {
    die("❌ Debes enviar un correo");
}

// Verificar si el usuario existe
$stmt = $conn->prepare("SELECT nombre, email FROM usuarios WHERE correo = ?");
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status"=>"error","mensaje"=>"Usuario no encontrado"]);
    exit;
}

$row = $result->fetch_assoc();
$nombre = $row['nombre'];
$email = $row['email']; // asegurarnos de usar el email correcto
$stmt->close();

// Generar token seguro
$token = bin2hex(random_bytes(32));
$expiracion = date("Y-m-d H:i:s", strtotime('+30 minutes'));

// Guardar token en base
$stmt = $conn->prepare("INSERT INTO reset_tokens (correo, token, expiracion) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $correo, $token, $expiracion);
$stmt->execute();
$stmt->close();
$conn->close();

// Preparar link de recuperación
$reset_link = "http://tusitio.com/reset_password.php?token=$token";

// Configurar PHPMailer
$mail = new PHPMailer(true);

try {
    // Servidor
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'julio2escuela19@gmail.com'; // tu Gmail
    $mail->Password   = 'cacmgdilaptuihbd';          // contraseña de aplicación
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Destinatario
    $mail->setFrom('julio2escuela19@gmail.com', 'MiApp');
    $mail->addAddress($email, $nombre);

    // Contenido
    $mail->isHTML(true);
    $mail->Subject = 'Recuperación de contraseña';
    $mail->Body = "Hola <b>$nombre</b>,<br><br>"
                . "Has solicitado restablecer tu contraseña. Haz clic en el siguiente enlace para crear una nueva contraseña:<br><br>"
                . "<a href='$reset_link'>Restablecer contraseña</a><br><br>"
                . "Este enlace expirará en 30 minutos.<br><br>"
                . "Si no solicitaste este cambio, ignora este correo.";

    $mail->send();
    echo json_encode(["status"=>"ok","mensaje"=>"Link de recuperación enviado, revisa tu correo"]);
} catch (Exception $e) {
    echo json_encode(["status"=>"error","mensaje"=>"No se pudo enviar el correo: {$mail->ErrorInfo}"]);
}
?>
