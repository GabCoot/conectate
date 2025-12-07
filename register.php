<?php
include 'conexion.php';

$nombre = $_POST['nombre'];
$rol = $_POST['rol'];
$correo = $_POST['correo'];
$telefono = $_POST['telefono'];
$direccion = $_POST['direccion'];
$password = $_POST['password'];

// Encriptar contraseña
$hash = password_hash($password, PASSWORD_DEFAULT);

$sql = "INSERT INTO usuarios (nombre, rol, correo, telefono, direccion, password) VALUES (?,?,?,?,?,?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssss", $nombre, $rol, $correo, $telefono, $direccion, $hash);

if ($stmt->execute()) {
    echo "success";
} else {
    echo "error: " . $stmt->error;
}

$conn->close();
?>
