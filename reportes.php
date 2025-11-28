<?php

$conexion = new mysqli("localhost", "root", "", "conexxionbuena");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}


// OBTENER REPORTE DE CLIENTES

function obtener_reporte_clientes($conexion, $filtro = '') {
    $filtro = $conexion->real_escape_string($filtro);
    
    $sql = "
        SELECT 
            u.id,
            u.nombre,
            u.correo,
            u.telefono,
            u.direccion,
            u.rol,
            u.activo,
            COUNT(DISTINCT c.id) AS num_contratos,
            COUNT(CASE WHEN c.estado = 'Activo' THEN 1 END) AS contratos_activos,
            COUNT(CASE WHEN c.estado = 'Suspendido' THEN 1 END) AS contratos_suspendidos,
            COALESCE(SUM(CASE WHEN p.estado = 'Pagado' THEN p.monto ELSE 0 END), 0) AS total_pagado,
            COALESCE(SUM(CASE WHEN p.estado = 'Pendiente' THEN p.monto ELSE 0 END), 0) AS total_pendiente
        FROM usuarios u
        LEFT JOIN contratos c ON u.id = c.usuario_id
        LEFT JOIN pagos p ON u.id = p.usuario_id
        WHERE u.rol = 'cliente'
        " . (!empty($filtro) ? "AND (u.nombre LIKE '%$filtro%' OR u.correo LIKE '%$filtro%' OR u.direccion LIKE '%$filtro%')" : "") . "
        GROUP BY u.id
        ORDER BY u.nombre ASC
    ";
    
    return $conexion->query($sql);
}

// OBTENER REPORTE DE CONTRATOS

function obtener_reporte_contratos($conexion, $filtro = '') {
    $filtro = $conexion->real_escape_string($filtro);
    
    $sql = "
        SELECT 
            c.id AS contrato_id,
            u.nombre AS cliente,
            u.correo,
            u.telefono,
            u.direccion,
            p.nombre AS paquete,
            p.velocidad,
            c.precio_mensual,
            c.fecha_inicio,
            c.duracion_meses,
            c.estado AS estado_contrato,
            c.fecha_ultimo_pago,
            COUNT(DISTINCT fp.id) AS total_pagos,
            COUNT(CASE WHEN fp.estado = 'Pagado' THEN 1 END) AS pagos_completados,
            COUNT(CASE WHEN fp.estado = 'Pendiente' THEN 1 END) AS pagos_pendientes,
            COUNT(CASE WHEN fp.estado = 'Atrasado' THEN 1 END) AS pagos_atrasados,
            COALESCE(SUM(CASE WHEN fp.estado = 'Pagado' THEN fp.monto ELSE 0 END), 0) AS total_pagado,
            COALESCE(SUM(CASE WHEN fp.estado IN ('Pendiente', 'Atrasado') THEN fp.monto ELSE 0 END), 0) AS total_adeudo
        FROM contratos c
        JOIN usuarios u ON c.usuario_id = u.id
        JOIN paquetes p ON c.paquete_id = p.id
        LEFT JOIN fechas_pagos fp ON c.id = fp.contrato_id
        " . (!empty($filtro) ? "WHERE (u.nombre LIKE '%$filtro%' OR p.nombre LIKE '%$filtro%' OR c.estado LIKE '%$filtro%')" : "") . "
        GROUP BY c.id
        ORDER BY c.fecha_inicio DESC
    ";
    
    return $conexion->query($sql);
}


// OBTENER REPORTE DE PAGOS

function obtener_reporte_pagos($conexion, $filtro = '', $estado = '') {
    $filtro = $conexion->real_escape_string($filtro);
    $estado = $conexion->real_escape_string($estado);
    
    $where = [];
    if (!empty($filtro)) {
        $where[] = "(u.nombre LIKE '%$filtro%' OR p.referencia LIKE '%$filtro%')";
    }
    if (!empty($estado)) {
        $where[] = "p.estado = '$estado'";
    }
    
    $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    
    $sql = "
        SELECT 
            p.id AS pago_id,
            p.referencia,
            p.fecha_pago,
            p.monto,
            p.metodo_pago,
            p.estado AS estado_pago,
            u.nombre AS cliente,
            u.correo,
            u.telefono,
            u.direccion,
            c.id AS contrato_id,
            c.estado AS estado_contrato,
            paq.nombre AS paquete,
            COUNT(pd.id) AS num_pagos_incluidos
        FROM pagos p
        JOIN usuarios u ON p.usuario_id = u.id
        JOIN contratos c ON p.contrato_id = c.id
        JOIN paquetes paq ON c.paquete_id = paq.id
        LEFT JOIN pagos_detalle pd ON p.id = pd.pago_id
        $where_clause
        GROUP BY p.id
        ORDER BY p.fecha_pago DESC, p.id DESC
    ";
    
    return $conexion->query($sql);
}


// OBTENER ESTADÍSTICAS GENERALES

function obtener_estadisticas_reportes($conexion) {
    $stats = [];
    
    // Clientes
    $result = $conexion->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'cliente'");
    $stats['total_clientes'] = $result->fetch_assoc()['total'];
    
    $result = $conexion->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'cliente' AND activo = 1");
    $stats['clientes_activos'] = $result->fetch_assoc()['total'];
    
    // Contratos
    $result = $conexion->query("SELECT COUNT(*) as total FROM contratos");
    $stats['total_contratos'] = $result->fetch_assoc()['total'];
    
    $result = $conexion->query("SELECT COUNT(*) as total FROM contratos WHERE estado = 'Activo'");
    $stats['contratos_activos'] = $result->fetch_assoc()['total'];
    
    $result = $conexion->query("SELECT COUNT(*) as total FROM contratos WHERE estado = 'Suspendido'");
    $stats['contratos_suspendidos'] = $result->fetch_assoc()['total'];
    
    // Pagos
    $result = $conexion->query("SELECT COUNT(*) as total, COALESCE(SUM(monto), 0) as monto FROM pagos WHERE estado = 'Pagado'");
    $pagos_completados = $result->fetch_assoc();
    $stats['total_pagos_completados'] = $pagos_completados['total'];
    $stats['monto_pagos_completados'] = $pagos_completados['monto'];
    
    $result = $conexion->query("SELECT COUNT(*) as total, COALESCE(SUM(monto), 0) as monto FROM pagos WHERE estado = 'Pendiente'");
    $pagos_pendientes = $result->fetch_assoc();
    $stats['total_pagos_pendientes'] = $pagos_pendientes['total'];
    $stats['monto_pagos_pendientes'] = $pagos_pendientes['monto'];
    
    // Ingresos del mes
    $result = $conexion->query("
        SELECT COALESCE(SUM(monto), 0) as total 
        FROM pagos 
        WHERE estado = 'Pagado' 
        AND MONTH(fecha_pago) = MONTH(CURDATE()) 
        AND YEAR(fecha_pago) = YEAR(CURDATE())
    ");
    $stats['ingresos_mes'] = $result->fetch_assoc()['total'];
    
    return $stats;
}


// MOSTRAR TABLA DE CLIENTES

function mostrar_tabla_clientes($resultado) {
    if (!$resultado || $resultado->num_rows == 0) {
        echo '<p class="sin-resultados">No se encontraron clientes.</p>';
        return;
    }
    
    echo '<div class="tabla-container">';
    echo '<table class="tabla-reporte">';
    echo '<thead>
            <tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Contacto</th>
                <th>Dirección</th>
                <th>Contratos</th>
                <th>Estado</th>
                <th>Total Pagado</th>
                <th>Total Pendiente</th>
            </tr>
          </thead>
          <tbody>';
    
    while ($cliente = $resultado->fetch_assoc()) {
        $estado_clase = $cliente['activo'] ? 'activo' : 'inactivo';
        
        echo '<tr>';
        echo '<td>' . $cliente['id'] . '</td>';
        echo '<td><strong>' . htmlspecialchars($cliente['nombre']) . '</strong></td>';
        echo '<td>
                 ' . htmlspecialchars($cliente['correo']) . '<br>
                 ' . htmlspecialchars($cliente['telefono'] ?? 'N/A') . '
              </td>';
        echo '<td>' . htmlspecialchars($cliente['direccion'] ?? 'N/A') . '</td>';
        echo '<td>
                <span class="badge badge-info">Total: ' . $cliente['num_contratos'] . '</span><br>
                <small>Activos: ' . $cliente['contratos_activos'] . ' | Suspendidos: ' . $cliente['contratos_suspendidos'] . '</small>
              </td>';
        echo '<td><span class="badge badge-' . $estado_clase . '">' . ($cliente['activo'] ? 'Activo' : 'Inactivo') . '</span></td>';
        echo '<td class="monto-positivo">$' . number_format($cliente['total_pagado'], 2) . '</td>';
        echo '<td class="monto-negativo">$' . number_format($cliente['total_pendiente'], 2) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>';
}


// CONTRATOS

function mostrar_tabla_contratos($resultado) {
    if (!$resultado || $resultado->num_rows == 0) {
        echo '<p class="sin-resultados">No se encontraron contratos.</p>';
        return;
    }
    
    echo '<div class="tabla-container">';
    echo '<table class="tabla-reporte">';
    echo '<thead>
            <tr>
                <th>Contrato</th>
                <th>Cliente</th>
                <th>Paquete</th>
                <th>Precio/Mes</th>
                <th>Inicio</th>
                <th>Duración</th>
                <th>Estado</th>
                <th>Progreso Pagos</th>
                <th>Total Pagado</th>
                <th>Adeudo</th>
            </tr>
          </thead>
          <tbody>';
    
    while ($contrato = $resultado->fetch_assoc()) {
        $estado_clase = '';
        switch($contrato['estado_contrato']) {
            case 'Activo': $estado_clase = 'activo'; break;
            case 'Suspendido': $estado_clase = 'suspendido'; break;
            case 'Inactivo': $estado_clase = 'inactivo'; break;
        }
        
        $porcentaje = $contrato['total_pagos'] > 0 ? round(($contrato['pagos_completados'] / $contrato['total_pagos']) * 100) : 0;
        
        echo '<tr>';
        echo '<td><strong>#' . $contrato['contrato_id'] . '</strong></td>';
        echo '<td>
                <strong>' . htmlspecialchars($contrato['cliente']) . '</strong><br>
                <small>' . htmlspecialchars($contrato['direccion'] ?? 'Sin dirección') . '</small>
              </td>';
        echo '<td>
                ' . htmlspecialchars($contrato['paquete']) . '<br>
                <small>' . htmlspecialchars($contrato['velocidad']) . '</small>
              </td>';
        echo '<td><strong>$' . number_format($contrato['precio_mensual'], 2) . '</strong></td>';
        echo '<td>' . date('d/m/Y', strtotime($contrato['fecha_inicio'])) . '</td>';
        echo '<td>' . $contrato['duracion_meses'] . ' meses</td>';
        echo '<td><span class="badge badge-' . $estado_clase . '">' . $contrato['estado_contrato'] . '</span></td>';
        echo '<td>
                <div class="progreso-container">
                    <div class="progreso-barra" style="width: ' . $porcentaje . '%"></div>
                </div>
                <small>' . $contrato['pagos_completados'] . '/' . $contrato['total_pagos'] . ' (' . $porcentaje . '%)</small>
              </td>';
        echo '<td class="monto-positivo">$' . number_format($contrato['total_pagado'], 2) . '</td>';
        echo '<td class="' . ($contrato['total_adeudo'] > 0 ? 'monto-negativo' : '') . '">$' . number_format($contrato['total_adeudo'], 2) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>';
}


//PAGOS

function mostrar_tabla_pagos($resultado) {
    if (!$resultado || $resultado->num_rows == 0) {
        echo '<p class="sin-resultados">No se encontraron pagos.</p>';
        return;
    }
    
    echo '<div class="tabla-container">';
    echo '<table class="tabla-reporte">';
    echo '<thead>
            <tr>
                <th>Referencia</th>
                <th>Cliente</th>
                <th>Contacto</th>
                <th>Paquete</th>
                <th>Monto</th>
                <th>Método</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th># Pagos</th>
            </tr>
          </thead>
          <tbody>';
    
    while ($pago = $resultado->fetch_assoc()) {
        $estado_clase = $pago['estado_pago'] == 'Pagado' ? 'pagado' : 'pendiente';
        
        echo '<tr>';
        echo '<td><small>' . htmlspecialchars($pago['referencia']) . '</small></td>';
        echo '<td>
                <strong>' . htmlspecialchars($pago['cliente']) . '</strong><br>
                <small>' . htmlspecialchars($pago['direccion'] ?? 'Sin dirección') . '</small>
              </td>';
        echo '<td>
                <small> ' . htmlspecialchars($pago['correo']) . '</small><br>
                <small> ' . htmlspecialchars($pago['telefono'] ?? 'N/A') . '</small>
              </td>';
        echo '<td>' . htmlspecialchars($pago['paquete']) . '</td>';
        echo '<td><strong>$' . number_format($pago['monto'], 2) . '</strong></td>';
        echo '<td>' . htmlspecialchars($pago['metodo_pago']) . '</td>';
        echo '<td>' . ($pago['fecha_pago'] ? date('d/m/Y', strtotime($pago['fecha_pago'])) : '-') . '</td>';
        echo '<td><span class="badge badge-' . $estado_clase . '">' . htmlspecialchars($pago['estado_pago']) . '</span></td>';
        echo '<td>' . $pago['num_pagos_incluidos'] . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>';
}
?>