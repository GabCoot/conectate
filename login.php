<?php
include("conexion.php");

$correo = $_POST['email'] ?? '';
$contrasena = $_POST['password'] ?? '';

if ($correo && $contrasena) {
    // Buscar usuario con correo y contraseña igualitos
$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE correo = ? AND password = ?");

    $stmt->bind_param("ss", $correo, $contrasena);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $usuario = $resultado->fetch_assoc();

        if ($usuario['rol'] === 'admin' || $usuario['rol'] === 'admin') {
            echo "<script>window.location.href='admin.html';</script>";
        } else {
            echo "<script>alert('Bienvenido Cliente'); window.location.href='cliente.php';</script>";
        }
    } else {
        echo "<script>alert('Correo o contraseña incorrectos'); window.history.back();</script>";
    }

    $stmt->close();
} else {
    echo "<script>alert('Por favor completa todos los campos'); window.history.back();</script>";
}

$conexion->close();
?>
