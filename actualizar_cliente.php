<?php
include("conexion.php");

$id       = $_POST['id'] ?? '';
$nombre   = $_POST['nombre'] ?? '';
$correo   = $_POST['correo'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$direccion= $_POST['direccion'] ?? '';
$activo   = $_POST['activo'] ?? 0;

header('Content-Type: application/json');

if ($id && $nombre && $correo) {
    $stmt = $conexion->prepare("UPDATE usuarios SET nombre = ?, correo = ?, telefono = ?, direccion = ?, activo = ? WHERE id = ?");
    $stmt->bind_param("ssssii", $nombre, $correo, $telefono, $direccion, $activo, $id);

    if($stmt->execute()){
        echo json_encode(["success"=>true, "message"=>"Cliente actualizado correctamente"]);
    } else {
        echo json_encode(["success"=>false, "message"=>"Error al actualizar el cliente"]);
    }
    $stmt->close();
} else {
    echo json_encode(["success"=>false, "message"=>"Faltan datos obligatorios"]);
}

$conexion->close();
?>
