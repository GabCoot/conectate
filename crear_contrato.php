<?php
session_start();
include 'conexion.php';

// Validar login
if (!isset($_SESSION['usuario_id'])) {
    die("No has iniciado sesión");
}

$usuario_id = $_SESSION['usuario_id'];
$paquete_id = $_POST['paquete_id'] ?? null;

if (!$paquete_id) {
    die("No se recibió el paquete");
}

// Traer precio del paquete
$stmt = $conexion->prepare("SELECT precio FROM paquetes WHERE id = ?");
$stmt->bind_param("i", $paquete_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Paquete no encontrado");
}

$paquete = $result->fetch_assoc();
$precio = $paquete['precio'];

// Crear contrato
$fecha_inicio = date("Y-m-d");
$duracion_meses = 6;
$estado = "Inactivo"; // después puedes activarlo manualmente

$stmt2 = $conexion->prepare("
    INSERT INTO contratos (usuario_id, paquete_id, fecha_inicio, precio_mensual, estado, duracion_meses)
    VALUES (?, ?, ?, ?, ?, ?)
");

$stmt2->bind_param("iisdsi", $usuario_id, $paquete_id, $fecha_inicio, $precio, $estado, $duracion_meses);

if ($stmt2->execute()) {
    echo "
    <script>
        alert('Contrato creado correctamente. Un administrador lo activará pronto.');
        window.location.href = 'principal.html';
    </script>
    ";
} else {
    echo "Error al crear contrato: " . $stmt2->error;
}

$stmt->close();
$stmt2->close();
$conn->close();
?>
