<?php
session_start();
include 'conexion.php';

$correo   = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($correo) || empty($password)) {
    die("Faltan datos");
}

$stmt = $conexion->prepare("
    SELECT id, nombre, telefono, direccion, correo, password, activo
    FROM usuarios
    WHERE correo = ?
");
$stmt->bind_param("s", $correo);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Usuario no encontrado");
}

$usuario = $result->fetch_assoc();

// ❗ Contraseña SIN ENCRIPTAR — comparación directa
if ($password !== $usuario['password']) {
    die("Contraseña incorrecta");
}

// Verificar si la cuenta está activa
if ($usuario['activo'] != 1) {
    die("Usuario no verificado");
}

// Sesión del usuario
$_SESSION['usuario_id'] = $usuario['id'];
$_SESSION['nombre']      = $usuario['nombre'];
$_SESSION['correo']      = $usuario['correo'];
$_SESSION['telefono']    = $usuario['telefono'];
$_SESSION['direccion']   = $usuario['direccion'];

header("Location: principal.php");
exit;
?>
