<?php
include("conexion.php");

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$mensaje = "";
$icono = "";

// Si no hay id válido, redirigir o mostrar error
if ($id <= 0) {
    die("ID de contrato inválido.");
}

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validaciones y casteos
    $id_post = isset($_POST['id']) ? intval($_POST['id']) : $id;
    $paquete_id = isset($_POST['paquete_id']) ? intval($_POST['paquete_id']) : 0;
    $numero_pago = isset($_POST['duracion_meses']) ? intval($_POST['duracion_meses']) : 0;
    $duracion_meses = isset($_POST['duracion_meses']) ? intval($_POST['duracion_meses']) : 0;
    $estado = isset($_POST['estado']) ? $conexion->real_escape_string($_POST['estado']) : 'Inactivo';
    $fecha_inicio = isset($_POST['fecha_inicio']) ? $conexion->real_escape_string($_POST['fecha_inicio']) : date('Y-m-d');

    // Obtener precio del paquete seleccionado (si existe)
    $precio_mensual = 0.00;
    $paqRes = $conexion->query("SELECT precio FROM paquetes WHERE id = {$paquete_id} LIMIT 1");
    if ($paqRes && $paqRes->num_rows > 0) {
        $paqRow = $paqRes->fetch_assoc();
        $precio_mensual = floatval($paqRow['precio']);
    } else {
        // Si no hay precio en paquete, intenta usar precio actual del contrato (fallback)
        $cRes = $conexion->query("SELECT precio_mensual FROM contratos WHERE id = {$id_post} LIMIT 1");
        if ($cRes && $cRes->num_rows > 0) {
            $cRow = $cRes->fetch_assoc();
            $precio_mensual = floatval($cRow['precio_mensual']);
        } else {
            $precio_mensual = 0.00;
        }
    }

    // ACTUALIZA CONTRATOS
  
    $sql = "
        UPDATE contratos 
        SET paquete_id = ?, fecha_inicio = ?, precio_mensual = ?, estado = ?, duracion_meses = ?
        WHERE id = ?
    ";

    $stmt = $conexion->prepare($sql);
    if ($stmt === false) {
        // mostrar error de prepare para depuración
        $mensaje = "Error en la consulta (prepare): " . $conexion->error;
        $icono = "error";
    } else {
        
        if (!$stmt->bind_param("isdsii", $paquete_id, $fecha_inicio, $precio_mensual, $estado, $duracion_meses, $id_post)) {
            $mensaje = "Error en bind_param: " . $stmt->error;
            $icono = "error";
        } else {
            if ($stmt->execute()) {

                // borrar calendario anterior
               $conexion->query("DELETE FROM fechas_pagos WHERE contrato_id = {$id_post}");
               // generar nuevo calendario
$insert_sql = "INSERT INTO fechas_pagos 
(contrato_id, numero_pago, fecha_pago_esperada, monto, estado) 
VALUES (?, ?, ?, ?, ?)";

$insert_stmt = $conexion->prepare($insert_sql);

if ($insert_stmt === false) {
    $mensaje = "Error al preparar inserción: " . $conexion->error;
    $icono = "warning";
} else {

    for ($i = 1; $i <= $duracion_meses; $i++) {

        $fecha_pago = date('Y-m-d', strtotime("+$i month", strtotime($fecha_inicio)));
        $monto = floatval($precio_mensual);
        $estado_pago = "Pendiente";

        // contrato_id (i), numero_pago (i), fecha_pago (s), monto (d), estado (s)
        $insert_stmt->bind_param("iisss", $id_post, $i, $fecha_pago, $monto, $estado_pago);

        $insert_stmt->execute();
    }



    $insert_stmt->close();
    $mensaje = "¡Contrato actualizado correctamente!";
    $icono = "success";
}

            } else {
                $mensaje = "Error al ejecutar update: " . $stmt->error;
                $icono = "error";
            }
        }
        $stmt->close();
    }
}

// Obtener información del contrato 
$contratoRes = $conexion->query("
    SELECT c.*, u.nombre AS cliente, p.nombre AS paquete, p.precio AS precio_paquete
    FROM contratos c
    JOIN usuarios u ON c.usuario_id = u.id
    JOIN paquetes p ON c.paquete_id = p.id
    WHERE c.id = {$id}
    LIMIT 1
");
if (!$contratoRes || $contratoRes->num_rows == 0) {
    die("Contrato no encontrado.");
}
$contrato = $contratoRes->fetch_assoc();

// Obtener paquetes
$paquetes = $conexion->query("SELECT * FROM paquetes ORDER BY nombre");

// Obtener calendario
$pagos = $conexion->query("SELECT * FROM fechas_pagos WHERE contrato_id = {$id} ORDER BY fecha_pago_esperada ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Contrato — Conect@T</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
body { background:#f7f9fb;font-family:Poppins,sans-serif;padding:40px 20px; }
.card { border-radius:1rem;box-shadow:0 4px 15px rgba(0,0,0,0.1); }
th { background:#0d6efd;color:white; }
</style>
</head>
<body>

<div class="container">

    <div class="d-flex align-items-center mb-3">
        <a href="contratos.php" class="btn btn-outline-primary me-3">
            <i class="bi bi-arrow-left"></i> Regresar
        </a>
        <h2 class="text-primary fw-bold mb-0">Editar Contrato</h2>
    </div>

    <div class="card p-4 mb-4">
        <h5>Información del Contrato</h5>

        <form method="POST">
            <input type="hidden" name="id" value="<?= htmlspecialchars($contrato['id']) ?>">

            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Cliente</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($contrato['cliente']) ?>" disabled>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Paquete</label>
                    <select name="paquete_id" id="paqueteSelect" class="form-select" required>
                        <?php while($p = $paquetes->fetch_assoc()): ?>
                            <option value="<?= $p['id'] ?>" data-precio="<?= $p['precio'] ?>"
                                <?= $p['id']==$contrato['paquete_id']?'selected':'' ?>>
                                <?= htmlspecialchars($p['nombre']) ?> — $<?= number_format($p['precio'],2) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Duración</label>
                    <select name="duracion_meses" class="form-select">
                        <option value="6" <?= $contrato['duracion_meses']==6?'selected':'' ?>>6 meses</option>
                        <option value="12" <?= $contrato['duracion_meses']==12?'selected':'' ?>>12 meses</option>
                    </select>
                </div>
            </div>

            <div class="row mt-3">

                <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                        <option <?= $contrato['estado']=='Activo'?'selected':'' ?>>Activo</option>
                        <option <?= $contrato['estado']=='Suspendido'?'selected':'' ?>>Suspendido</option>
                        <option <?= $contrato['estado']=='Inactivo'?'selected':'' ?>>Inactivo</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Fecha inicio</label>
                    <input type="date" name="fecha_inicio" class="form-control"
                        value="<?= htmlspecialchars($contrato['fecha_inicio']) ?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Precio mensual</label>
                    <input type="text" id="precioMensual" class="form-control" readonly
                        value="$<?= number_format(floatval($contrato['precio_mensual']),2) ?>">
                </div>

            </div>

            <div class="mt-4 text-end">
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-clockwise"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>

    <div class="card p-4 mb-4">
        <h5>Calendario de Pagos</h5>

        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Mes</th>
                    <th>Fecha pago</th>
                    <th>Monto</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php $i=1; if($pagos): while($pago=$pagos->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($pago['fecha_pago_esperada']) ?></td>
                    <td>$<?= number_format($pago['monto'],2) ?></td>
                    <td>
                        <span class="badge bg-<?= $pago['estado']=='Pendiente'?'warning':'success' ?>">
                            <?= htmlspecialchars($pago['estado']) ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="4">No hay pagos.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="text-end mt-3">
            <button onclick="window.print()" class="btn btn-outline-primary">
                <i class="bi bi-printer"></i> Imprimir Calendario
            </button>
        </div>
    </div>
</div>

<?php if(!empty($mensaje)): ?>
<script>
Swal.fire({
    title: '<?= addslashes($mensaje) ?>',
    icon: '<?= $icono ?>',
    confirmButtonColor: '#0d6efd'
}).then(() => {
    window.location.href = 'editar_contrato.php?id=<?= $id ?>';
});
</script>
<?php endif; ?>

<script>
// Actualizar precio al cambiar paquete
document.addEventListener('DOMContentLoaded', () => {
    const paqueteSelect = document.getElementById('paqueteSelect');
    const precioInput = document.getElementById('precioMensual');

    paqueteSelect.addEventListener('change', function() {
        const precio = this.options[this.selectedIndex].getAttribute('data-precio') || 0;
        precioInput.value = "$" + parseFloat(precio).toFixed(2);
    });
});
</script>

</body>
</html>

<?php $conexion->close(); ?>
