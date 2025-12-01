<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(0);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
require __DIR__ . "/ip.php";

// Conexión a la BD
include 'conexion.php';

// Datos POST
$nombre = $_POST['nombre'];
$rol = $_POST['rol'];
$correo = $_POST['correo'];
$telefono = $_POST['telefono'];
$direccion = $_POST['direccion'];
$password = $_POST['password'];

if (empty($nombre) || empty($correo) || empty($telefono) || empty($direccion) || empty($password)) {
    echo json_encode(["status" => "error", "msg" => "Faltan datos"]);
    exit;
}

$plainPass = $password;

// Token de verificación
$verify_token = bin2hex(random_bytes(16));

// Código de verificación de 4 dígitos
$codigo_verificacion = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

// Verificamos si el correo ya existe
$check = $conn->prepare("SELECT id FROM usuarios WHERE correo = ?");
$check->bind_param("s", $correo);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode(["status" => "error", "msg" => "El correo ya está registrado"]);
    $check->close();
    $conn->close();
    exit;
}
$check->close();

// Insertar usuario con token y código
$stmt = $conn->prepare("INSERT INTO usuarios (nombre, rol, correo, telefono, direccion, password, reset_token, codigo_verificacion, activo) 
                        VALUES (?, 'cliente', ?, ?, ?, ?, ?, ?, 0)");
$stmt->bind_param("sssssss", $nombre, $correo, $telefono, $direccion, $plainPass, $verify_token, $codigo_verificacion);

if ($stmt->execute()) {
    // Enviar correo de verificación
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'jesusuxul652@gmail.com'; 
        $mail->Password   = 'zcmqltvhswuvbxay';      
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('jesusuxul652@gmail.com', 'AppUx');
        $mail->addAddress($correo);
        $mail->isHTML(true);
        $mail->Subject = "Verifica tu cuenta";

        $link = "$SERVER_URL/verificar.php?token=$verify_token";

        $mail->Body = "
            <h2>Bienvenido $nombre</h2>
            <p>Gracias por registrarte. Para activar tu cuenta haz clic en el siguiente enlace:</p>
            <a href='$link'>$link</a>
            <br><br>
            <p>Tu código de verificación es: <b>$codigo_verificacion</b></p>
            <small>Si no te registraste, ignora este correo.</small>
        ";

        if ($mail->send()) {
            echo json_encode(["status" => "success", "msg" => "Registro exitoso. Revisa tu correo para activarlo"]);
        } else {
            echo json_encode(["status" => "error", "msg" => "Registro creado, pero error al enviar correo", "info" => $mail->ErrorInfo]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "msg" => "Excepción al enviar correo", "info" => $mail->ErrorInfo]);
    }
} else {
    echo json_encode(["status" => "error", "msg" => "Error en registro", "info" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
