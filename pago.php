<?php
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
