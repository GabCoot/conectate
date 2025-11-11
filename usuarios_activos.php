<?php
include("conexion.php");

// Contar clientes activos usando la columna 'activo'
$query = "SELECT COUNT(*) as total FROM usuarios WHERE rol = 'cliente' AND activo = 1";
$result = $conexion->query($query);

if($result){
    $row = $result->fetch_assoc();
    echo $row['total'];
} else {
    echo 0;
}

$conexion->close();
?>
