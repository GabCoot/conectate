<?php
header('Content-Type: application/json');
include('conexion.php');

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$nombre = $_POST['nombre'] ?? '';
$correo = $_POST['correo'] ?? '';
$telefono = $_POST['telefono'] ?? null;
$direccion = $_POST['direccion'] ?? null;
$activo = isset($_POST['activo']) ? intval($_POST['activo']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

// Validaciones básicas
if (trim($nombre) === '' || trim($correo) === '') {
    echo json_encode(['success' => false, 'message' => 'Nombre y correo son obligatorios']);
    exit;
}

$stmt = $conexion->prepare("UPDATE usuarios SET nombre = ?, correo = ?, telefono = ?, direccion = ?, activo = ? WHERE id = ?");
$stmt->bind_param('ssssii', $nombre, $correo, $telefono, $direccion, $activo, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cliente actualizado correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $conexion->error]);
}
$conexion->close();
