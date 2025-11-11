<?php
$conexion = new mysqli("localhost", "root", "", "co");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Cobrar 
if (isset($_GET['pagar'])) {
    $id = intval($_GET['pagar']);
    if ($conexion->query("UPDATE usuarios SET pago = 1 WHERE id = $id")) {
        header("Location: cobro.php?alert=Cliente cobrado correctamente");
    } else {
        header("Location: cobro.php?alert=Error al cobrar al cliente");
    }
    exit();
}


// Desactivar cobro 
if (isset($_GET['desactivar'])) {
    $id = intval($_GET['desactivar']);
    if ($conexion->query("UPDATE usuarios SET pago = 0 WHERE id = $id")) {
        header("Location: cobro.php?alert=Cuenta desactivada correctamente");
    } else {
        header("Location: cobro.php?alert=Error al desactivar cuenta");
    }
    exit();
}


//tabla
function mostrar_tabla($conexion, $busqueda = '', $botones = true) {
    $busqueda = $conexion->real_escape_string($busqueda);
    $sql = "SELECT 
                u.id, 
                u.nombre, 
                u.direccion, 
                COALESCE(p.nombre, 'Sin paquete') AS paquete, 
                COALESCE(p.precio, 0.00) AS precio, 
                u.activo, 
                u.pago
            FROM usuarios u
            LEFT JOIN paquetes p ON u.id_paquete = p.id
            WHERE u.tipo = 'cliente' 
              AND (u.nombre LIKE '%$busqueda%' OR u.direccion LIKE '%$busqueda%')";

    $resultado = $conexion->query($sql);
    if (!$resultado) {
        echo "Error en la consulta SQL: " . $conexion->error;
        return;
    }

    echo '<table>
      <tr>
        <th>Nombre</th>
        <th>Dirección</th>
        <th>Paquete</th>
        <th>Precio</th>
        <th>Estado</th>
        <th>Pago</th>';
    if ($botones) echo '<th>Acciones</th>';
    echo '</tr>';

    while ($fila = $resultado->fetch_assoc()) {
        $estado = $fila['activo'] ? 'Activo' : 'Inactivo';
        $pago = $fila['pago'] ? 'Pagado' : 'Pendiente';

        $color_estado = $fila['activo'] ? 'verde' : 'rojo';
        $color_pago = $fila['pago'] ? 'verde' : 'rojo';

        echo '<tr>
            <td>'.htmlspecialchars($fila['nombre']).'</td>
            <td>'.htmlspecialchars($fila['direccion']).'</td>
            <td>'.htmlspecialchars($fila['paquete']).'</td>
            <td>$'.htmlspecialchars($fila['precio']).'</td>
            <td class="'.$color_estado.'">'.$estado.'</td>
            <td class="'.$color_pago.'">'.$pago.'</td>';

        if ($botones) {
            echo '<td>
                <button class="btn btn-pagar" '.($fila['pago'] ? 'disabled' : 'onclick="window.location=\'cobro.php?pagar='.$fila['id'].'\'"').'>Cobrar</button>
                <button class="btn btn-desactivar" '.(!$fila['activo'] ? 'disabled' : 'onclick="window.location=\'cobro.php?desactivar='.$fila['id'].'\'"').'>Desactivar</button>
            </td>';
        }

        echo '</tr>';
    }

    echo '</table>';
}
?>
