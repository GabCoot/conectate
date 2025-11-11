<?php
include("conexion.php");

// Traer contratos con info de usuario y paquete
$query = "
SELECT c.id, u.nombre AS cliente, u.correo, u.telefono,
       p.nombre AS paquete, p.velocidad, c.precio_mensual, c.fecha_inicio, c.fecha_fin, c.estado
FROM contratos c
JOIN usuarios u ON c.usuario_id = u.id
JOIN paquetes p ON c.paquete_id = p.id
ORDER BY c.id DESC
";
$result = $conexion->query($query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contratos — Conect@T</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
body { background: #f7f9fb; font-family: "Poppins", sans-serif; padding: 40px 20px; }
.card-table { border-radius: 1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
th { background-color: #0d6efd; color: white; }
td { vertical-align: middle; }
</style>
</head>
<body>

<div class="container">
    <div class="text-center mb-3">
        <h1 class="text-primary fw-bold">Contratos</h1>
    </div>

    <div class="mb-3 text-end">
        <a href="admin.html" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Regresar
        </a>
    </div>

    <div class="card card-table p-3 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Paquete</th>
                        <th>Inicio</th>
                        <th>Fin</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['cliente']) ?></td>
                                <td><?= htmlspecialchars($row['paquete']) ?> — <?= $row['velocidad'] ?></td>
                                <td><?= $row['fecha_inicio'] ?></td>
                                <td><?= $row['fecha_fin'] ?? '-' ?></td>
                                <td>$<?= number_format($row['precio_mensual'],2) ?></td>
                                <td>
                                    <?php
                                    $estado_class = match($row['estado']){
                                        'Activo'=>'success',
                                        'Inactivo'=>'danger',
                                        'Suspendido'=>'warning'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $estado_class ?>"><?= $row['estado'] ?></span>
                                </td>
                                <td>
                                    <a href="contrato_pdf.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="bi bi-file-earmark-pdf"></i> Ver/Descargar
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center">No hay contratos registrados.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conexion->close(); ?>
