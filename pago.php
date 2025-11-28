<?php
<<<<<<< HEAD
$conexion = new mysqli("localhost", "root", "", "conexxionbuena");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}



// Marcar pagos como "Atrasado" despues de 5 dias
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
    
    // Buscar el pago por referencia
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
            // 1. Actualizar estado del pago a "Pagado"
            $conexion->query("
                UPDATE pagos 
                SET estado = 'Pagado',
                    fecha_pago = CURDATE()
                WHERE id = $pago_id
            ");
            
            // 2. Obtener las fechas de pago relacionadas con este pago
            $fechas_query = $conexion->query("
                SELECT fecha_pago_id 
                FROM pagos_detalle 
                WHERE pago_id = $pago_id
            ");
            
            // 3. Actualizar estado de todas las fechas de pago a "Pagado"
            while ($detalle = $fechas_query->fetch_assoc()) {
                $fecha_pago_id = $detalle['fecha_pago_id'];
                $conexion->query("
                    UPDATE fechas_pagos 
                    SET estado = 'Pagado' 
                    WHERE id = $fecha_pago_id
                ");
            }
            
            // 4. Actualizar contrato (activar y registrar último pago)
            $conexion->query("
                UPDATE contratos 
                SET estado = 'Activo', 
                    fecha_ultimo_pago = CURDATE() 
                WHERE id = $contrato_id
            ");
            
            // Confirmar transacción
            $conexion->commit();
            
            header("Location: cobro.php?msg=✅ Pago confirmado correctamente&ref=$referencia");
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
=======
include("conexion.php");

// Verificar conexión
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

// --- Registrar pago si se envía desde el modal ---
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["contrato_id"])) {
    $contrato_id = intval($_POST["contrato_id"]);
    $monto = floatval($_POST["monto"]);
    $fecha = date("Y-m-d");

    $insertar = "INSERT INTO calendario_pagos (contrato_id, fecha_pago, monto, estado)
                 VALUES ($contrato_id, '$fecha', $monto, 'Pagado')";
    mysqli_query($conexion, $insertar);
>>>>>>> 275ed7fa9bf82bb5bd7a7332539a1445c5ab42bf
}

// --- Consulta principal: mostrar contratos y su último estado de pago ---
$query = "
    SELECT 
        c.id AS contrato_id,
        u.nombre AS cliente,
        u.direccion,
        pk.nombre AS paquete,
        c.precio_mensual,
        COALESCE(cp.estado, 'Pendiente') AS estado_pago
    FROM contratos c
    INNER JOIN usuarios u ON c.usuario_id = u.id
    INNER JOIN paquetes pk ON c.paquete_id = pk.id
    LEFT JOIN (
        SELECT contrato_id, estado 
        FROM calendario_pagos 
        WHERE id IN (SELECT MAX(id) FROM calendario_pagos GROUP BY contrato_id)
    ) cp ON c.id = cp.contrato_id
    ORDER BY c.id DESC
";

<<<<<<< HEAD

function buscar_pago_por_referencia($conexion, $referencia) {
    $referencia = $conexion->real_escape_string($referencia);
    
    // Buscar información del pago
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
    
    

    // Obtener detalles de los pagos mensuales incluidos
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

    if (!$resultado || $resultado->num_rows == 0) {
        return [];
    }

    $pagos = [];

    while ($pago = $resultado->fetch_assoc()) {

        // Obtener detalles del pago (fechas mensuales incluidas)
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
    echo '<h3>Detalles del Pago</h3>';
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

    // Pdf
echo '<div class="comprobante-pdf">';
echo '<a href="comprobante.php?referencia='.urlencode($pago['referencia']).'" 
        target="_blank" class="btn-pdf">
        <i class="bi bi-filetype-pdf"></i>
         Descargar Comprobante PDF
      </a>';
echo '</div>';

    
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
    
    // Botón de confirmación
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



function obtener_estadisticas($conexion) {
    $stats = [];
    
    // Total de pagos pendientes de confirmar
    $result = $conexion->query("
        SELECT COUNT(*) as total, COALESCE(SUM(monto), 0) as monto_total 
        FROM pagos 
        WHERE estado = 'Pendiente'
    ");
    $stats['pendientes'] = $result->fetch_assoc();
    
    // Total de pagos confirmados hoy
    $result = $conexion->query("
        SELECT COUNT(*) as total, COALESCE(SUM(monto), 0) as monto_total 
        FROM pagos 
        WHERE estado = 'Pagado' 
        AND DATE(fecha_pago) = CURDATE()
    ");
    $stats['hoy'] = $result->fetch_assoc();
    
    // Total del mes
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
?>
=======
$resultado = mysqli_query($conexion, $query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Pagos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background-color: #e9f1fb;
            font-family: 'Poppins', sans-serif;
        }
        .navbar {
            background-color: #0d6efd;
        }
        .navbar-brand {
            color: #fff !important;
            font-weight: 600;
        }
        th {
            background-color: #0d6efd;
            color: white;
            text-align: center;
        }
        td {
            text-align: center;
            vertical-align: middle;
        }
        .estado-pagado {
            color: green;
            font-weight: bold;
        }
        .estado-pendiente {
            color: red;
            font-weight: bold;
        }
        .container-table {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .btn-regresar {
            margin-top: 20px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <span class="navbar-brand">Panel de Administración | Gestión de Pagos</span>
    </div>
</nav>

<!-- Contenido principal -->
<div class="container mt-4">

    <!-- Buscador -->
    <form class="d-flex mb-3" method="GET">
        <input class="form-control me-2" type="text" name="buscar" placeholder="Buscar cliente o dirección..." value="<?php echo isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : ''; ?>">
        <button class="btn btn-primary" type="submit">Buscar</button>
    </form>

    <!-- Tabla principal -->
    <div class="container-table">
        <table class="table table-bordered align-middle">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Dirección</th>
                    <th>Paquete</th>
                    <th>Precio Mensual</th>
                    <th>Estado de Pago</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (mysqli_num_rows($resultado) > 0) {
                    while ($fila = mysqli_fetch_assoc($resultado)) {
                        $estadoPago = $fila['estado_pago'] === 'Pagado'
                            ? "<span class='estado-pagado'>Pagado</span>"
                            : "<span class='estado-pendiente'>Pendiente</span>";

                        echo "<tr>";
                        echo "<td>{$fila['cliente']}</td>";
                        echo "<td>{$fila['direccion']}</td>";
                        echo "<td>{$fila['paquete']}</td>";
                        echo "<td>$" . number_format($fila['precio_mensual'], 2) . "</td>";
                        echo "<td>$estadoPago</td>";
                        echo "<td>";
                        echo "<button class='btn btn-success btn-sm' data-bs-toggle='modal' 
                                     data-bs-target='#modalPagar'
                                     data-id='{$fila['contrato_id']}'
                                     data-cliente='{$fila['cliente']}'
                                     data-precio='{$fila['precio_mensual']}'
                                     " . ($fila['estado_pago'] === 'Pagado' ? "disabled" : "") . ">
                                     Registrar Pago
                              </button>";
                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No hay contratos registrados.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

    <!-- Botón Regresar -->
    <div class="text-center">
        <a href="admin.php" class="btn btn-primary btn-regresar">← Regresar</a>
    </div>
</div>

<!-- Modal de Pago -->
<div class="modal fade" id="modalPagar" tabindex="-1" aria-labelledby="modalPagarLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="modalPagarLabel">Registrar Pago</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body text-center">
          <p><strong>Cliente:</strong> <span id="clienteSeleccionado"></span></p>
          <p><strong>Precio Mensual:</strong> $<span id="precioSeleccionado"></span></p>
          <input type="hidden" name="contrato_id" id="inputContrato">
          <input type="hidden" name="monto" id="inputMonto">
          <p>¿Deseas registrar el pago de este mes?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-success">Confirmar Pago</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
    // Captura datos al abrir el modal
    const modalPagar = document.getElementById('modalPagar');
    modalPagar.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const cliente = button.getAttribute('data-cliente');
        const contratoId = button.getAttribute('data-id');
        const precio = button.getAttribute('data-precio');

        document.getElementById('clienteSeleccionado').textContent = cliente;
        document.getElementById('precioSeleccionado').textContent = precio;
        document.getElementById('inputContrato').value = contratoId;
        document.getElementById('inputMonto').value = precio;
    });
</script>

</body>
</html>
>>>>>>> 275ed7fa9bf82bb5bd7a7332539a1445c5ab42bf
