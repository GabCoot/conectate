<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "conexxionbuena";

$conexion = new mysqli($host, $user, $pass, $db);

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
?>
