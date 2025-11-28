<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);

try {
    // Configuración SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'julio2escuela19@gmail.com'; 
    $mail->Password = 'ohtoakqknhpybltw'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Remitente
    $mail->setFrom('julio2escuela19@gmail.com', 'Prueba PHPMailer');

    // Destinatario
    $mail->addAddress('julio2escuela19@gmail.com');

    // Contenido
    $mail->isHTML(true);
    $mail->Subject = 'Correo de prueba';
    $mail->Body    = '<h2>PHPMailer funciona correctamente 🎉</h2>';
    $mail->AltBody = 'PHPMailer funciona correctamente 🎉';

    $mail->send();
    echo '✔️ CORREO ENVIADO CORRECTAMENTE';
} catch (Exception $e) {
    echo "❌ Error al enviar: {$mail->ErrorInfo}";
}
