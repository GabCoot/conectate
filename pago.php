<?php
$conexion = new mysqli("localhost", "root", "", "conexxionbuena");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Marcar pagos como "Atrasado" después de 5 días
$conexion->query("
    UPDATE fechas_pagos
    SET estado = 'Atrasado'
    WHERE estado = 'Pendiente'
      AND fecha_pago_esperada < DATE_SUB(CURDATE(), INTERVAL 5 DAY)
");

// Suspender contratos que tengan pagos atrasados
$conexion->query("
    UPDATE contratos c
    SET c.estado = 'Suspendido'
    WHERE c.estado = 'Activo'
      AND EXISTS (
          SELECT 1 FROM fechas_pagos f 
          WHERE f.contrato_id = c.id 
          AND f.estado = 'Atrasado'
      )
");

if (isset($_POST['confirmar_pago'])) {
    $referencia = $conexion->real_escape_string($_POST['referencia']);

    $query = $conexion->query("
        SELECT 
            p.id AS pago_id,
            p.usuario_id,
            p.contrato_id,
            p.monto,
            p.estado
        FROM pagos p
        WHERE p.referencia = '$referencia'
        LIMIT 1
    ");

    if ($pago = $query->fetch_assoc()) {

        if ($pago['estado'] == 'Pagado') {
            header("Location: cobro.php?msg=Error: Este pago ya fue confirmado anteriormente&ref=$referencia");
            exit();
        }

        $pago_id = $pago['pago_id'];
        $contrato_id = $pago['contrato_id'];

        // Iniciar transacción
        $conexion->begin_transaction();

        try {
            // 1. Actualizar estado del pago a pagado
            $conexion->query("
                UPDATE pagos 
                SET estado = 'Pagado',
                    fecha_pago = CURDATE()
                WHERE id = $pago_id
            ");

            // 2. Obtener fechas relacionadas
            $fechas_query = $conexion->query("
                SELECT fecha_pago_id 
                FROM pagos_detalle 
                WHERE pago_id = $pago_id
            ");

            // 3. Actualizar estado de las fechas
            while ($detalle = $fechas_query->fetch_assoc()) {
                $fecha_pago_id = $detalle['fecha_pago_id'];
                $conexion->query("
                    UPDATE fechas_pagos 
                    SET estado = 'Pagado' 
                    WHERE id = $fecha_pago_id
                ");
            }

            // 4. Actualizar contrato
            $conexion->query("
                UPDATE contratos 
                SET estado = 'Activo',
                    fecha_ultimo_pago = CURDATE() 
                WHERE id = $contrato_id
            ");

            $conexion->commit();

            header("Location: cobro.php?msg=✓ Pago confirmado correctamente&ref=$referencia");
            exit();

        } catch (Exception $e) {
            $conexion->rollback();
            header("Location: cobro.php?msg=Error al confirmar el pago: " . $e->getMessage());
            exit();
        }

    } else {
        header("Location: cobro.php?msg=Error: No se encontró ningún pago con esa referencia");
        exit();
    }
}
// BUSCAR PAGO POR REFERENCIA
function buscar_pago_por_referencia($conexion, $referencia) {
    $referencia = $conexion->real_escape_string($referencia);

    $sql = "
        SELECT 
            p.id AS pago_id,
            p.referencia,
            p.fecha_pago,
            p.monto AS total_pago,
            p.metodo_pago,
            p.estado AS estado_pago,
            u.nombre AS cliente,
            u.correo,
            u.telefono,
            u.direccion,
            c.id AS contrato_id,
            c.estado AS estado_contrato,
            paq.nombre AS paquete
        FROM pagos p
        JOIN usuarios u ON p.usuario_id = u.id
        JOIN contratos c ON p.contrato_id = c.id
        JOIN paquetes paq ON c.paquete_id = paq.id
        WHERE p.referencia = '$referencia'
        LIMIT 1
    ";

    $resultado = $conexion->query($sql);

    if (!$resultado || $resultado->num_rows == 0) {
        return null;
    }

    $pago = $resultado->fetch_assoc();

    // Obtener detalles
    $sql_detalles = "
        SELECT 
            f.numero_pago,
            f.fecha_pago_esperada,
            f.monto,
            f.estado
        FROM pagos_detalle pd
        JOIN fechas_pagos f ON pd.fecha_pago_id = f.id
        WHERE pd.pago_id = " . $pago['pago_id'] . "
        ORDER BY f.numero_pago ASC
    ";

    $detalles = $conexion->query($sql_detalles);
    $pago['detalles'] = [];

    while ($detalle = $detalles->fetch_assoc()) {
        $pago['detalles'][] = $detalle;
    }

    return $pago;
}
// BUSCAR PAGO POR NOMBRE
function buscar_pagos_por_nombre($conexion, $nombre) {
    $nombre = $conexion->real_escape_string($nombre);

    $sql = "
        SELECT 
            p.id AS pago_id,
            p.referencia,
            p.fecha_pago,
            p.monto AS total_pago,
            p.metodo_pago,
            p.estado AS estado_pago,
            u.nombre AS cliente,
            u.correo,
            u.telefono,
            u.direccion,
            c.id AS contrato_id,
            c.estado AS estado_contrato,
            paq.nombre AS paquete
        FROM pagos p
        JOIN usuarios u ON p.usuario_id = u.id
        JOIN contratos c ON p.contrato_id = c.id
        JOIN paquetes paq ON c.paquete_id = paq.id
        WHERE u.nombre LIKE '%$nombre%'
    ";

    $resultado = $conexion->query($sql);
    $pagos = [];

    if (!$resultado || $resultado->num_rows == 0) {
        return [];
    }

    while ($pago = $resultado->fetch_assoc()) {
        $sql_detalles = "
            SELECT 
                f.numero_pago,
                f.fecha_pago_esperada,
                f.monto,
                f.estado
            FROM pagos_detalle pd
            JOIN fechas_pagos f ON pd.fecha_pago_id = f.id
            WHERE pd.pago_id = " . $pago['pago_id'] . "
            ORDER BY f.numero_pago ASC
        ";

        $detalles = $conexion->query($sql_detalles);
        $pago['detalles'] = [];

        while ($detalle = $detalles->fetch_assoc()) {
            $pago['detalles'][] = $detalle;
        }

        $pagos[] = $pago;
    }

    return $pagos;
}

// --------------------------
// ESTADÍSTICAS
// --------------------------
function obtener_estadisticas($conexion) {
    $stats = [];

    $result = $conexion->query("
        SELECT COUNT(*) as total, COALESCE(SUM(monto), 0) as monto_total 
        FROM pagos 
        WHERE estado = 'Pendiente'
    ");
    $stats['pendientes'] = $result->fetch_assoc();

    $result = $conexion->query("
        SELECT COUNT(*) as total, COALESCE(SUM(monto), 0) as monto_total 
        FROM pagos 
        WHERE estado = 'Pagado' 
        AND DATE(fecha_pago) = CURDATE()
    ");
    $stats['hoy'] = $result->fetch_assoc();

    $result = $conexion->query("
        SELECT COUNT(*) as total, COALESCE(SUM(monto), 0) as monto_total 
        FROM pagos 
        WHERE estado = 'Pagado'
        AND MONTH(fecha_pago) = MONTH(CURDATE()) 
        AND YEAR(fecha_pago) = YEAR(CURDATE())
    ");
    $stats['mes_actual'] = $result->fetch_assoc();

    return $stats;
}
// MOSTRAR RESULTADO DE BÚSQUEDA
function mostrar_resultado_busqueda($pago) {
    if (!$pago) {
        echo '<div class="resultado-vacio">';
        echo '<p> No se encontró ningún pago con esa referencia.</p>';
        echo '<p>Por favor, verifique la referencia e intente nuevamente.</p>';
        echo '</div>';
        return;
    }
    
    $estado_clase = $pago['estado_pago'] == 'Pagado' ? 'badge-activo' : 'badge-pendiente';
    $puede_confirmar = $pago['estado_pago'] == 'Pendiente';
    
    echo '<div class="resultado-pago">';
    
    // Encabezado
    echo '<div class="resultado-header">';
    echo '<div>';
    echo '<h2>Información del Pago</h2>';
    echo '<p class="referencia-grande">Ref: ' . htmlspecialchars($pago['referencia']) . '</p>';
    echo '</div>';
    echo '<span class="badge-grande '.$estado_clase.'">'.htmlspecialchars($pago['estado_pago']).'</span>';
    echo '</div>';
    
    // Información del cliente
    echo '<div class="info-cliente">';
    echo '<h3> Información del Cliente</h3>';
    echo '<div class="info-grid">';
    echo '<div class="info-item"><strong>Nombre:</strong> '.htmlspecialchars($pago['cliente']).'</div>';
    echo '<div class="info-item"><strong>Correo:</strong> '.htmlspecialchars($pago['correo']).'</div>';
    echo '<div class="info-item"><strong>Teléfono:</strong> '.htmlspecialchars($pago['telefono'] ?? 'N/A').'</div>';
    echo '<div class="info-item"><strong>Dirección:</strong> '.htmlspecialchars($pago['direccion'] ?? 'N/A').'</div>';
    echo '<div class="info-item"><strong>Paquete:</strong> '.htmlspecialchars($pago['paquete']).'</div>';
    echo '<div class="info-item"><strong>Contrato:</strong> #'.htmlspecialchars($pago['contrato_id']).'</div>';
    echo '</div>';
    echo '</div>';
    
    // Información del pago
    echo '<div class="info-pago">';
    echo '<h3> Detalles del Pago</h3>';
    echo '<div class="pago-resumen">';
    echo '<div class="pago-item"><strong>Fecha de Pago:</strong> '.($pago['fecha_pago'] ? date('d/m/Y', strtotime($pago['fecha_pago'])) : 'Pendiente').'</div>';
    echo '<div class="pago-item"><strong>Método:</strong> '.htmlspecialchars($pago['metodo_pago']).'</div>';
    echo '<div class="pago-item total"><strong>TOTAL:</strong> <span class="monto-total">$'.number_format($pago['total_pago'], 2).'</span></div>';
    echo '</div>';
    echo '</div>';
    
    // Tabla de pagos mensuales incluidos
    echo '<div class="pagos-incluidos">';
    echo '<h3> Pagos Mensuales Incluidos</h3>';
    echo '<table class="tabla-detalles">';
    echo '<thead>
            <tr>
                <th>Pago #</th>
                <th>Fecha Esperada</th>
                <th>Monto</th>
                <th>Estado</th>
            </tr>
          </thead>
          <tbody>';
    
    foreach ($pago['detalles'] as $detalle) {
        $color_estado = '';
        switch($detalle['estado']) {
            case 'Pagado': $color_estado = 'green'; break;
            case 'Atrasado': $color_estado = 'red'; break;
            case 'Pendiente': $color_estado = 'orange'; break;
        }
        
        echo '<tr>';
        echo '<td><strong>'.$detalle['numero_pago'].'</strong></td>';
        echo '<td>'.date('d/m/Y', strtotime($detalle['fecha_pago_esperada'])).'</td>';
        echo '<td>$'.number_format($detalle['monto'], 2).'</td>';
        echo '<td style="color:'.$color_estado.'; font-weight:bold;">'.htmlspecialchars($detalle['estado']).'</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>';
    
    
    if ($puede_confirmar) {
        echo '<form method="POST" action="cobro.php" class="form-confirmar">';
        echo '<input type="hidden" name="confirmar_pago" value="1">';
        echo '<input type="hidden" name="referencia" value="'.htmlspecialchars($pago['referencia']).'">';
        echo '<button type="submit" class="btn-confirmar-pago" onclick="return confirm(\'¿Confirmar que el cliente pagó $'.number_format($pago['total_pago'], 2).'?\')">
                ✓ Confirmar Pago
              </button>';
        echo '</form>';
    } else {
        echo '<div class="pago-confirmado">';
        echo '<p>Este pago ya fue confirmado el '.date('d/m/Y', strtotime($pago['fecha_pago'])).'</p>';
        echo '</div>';
    }
    
    echo '</div>'; 
    } 
?>