<?php
include("conexion.php");


$result = $conexion->query("SELECT * FROM paquetes ORDER BY id ASC");

$paquetes = [];
while($row = $result->fetch_assoc()){
    $paquetes[] = $row;
}


header('Content-Type: application/json');
echo json_encode($paquetes);

$conexion->close();
?>
