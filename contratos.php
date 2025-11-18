<?php
include("conexion.php");

// Traer contratos con info de usuario y paquete
$query = "
SELECT c.id, u.nombre AS cliente, u.correo, u.telefono,
       p.nombre AS paquete, p.velocidad,
       c.precio_mensual, c.fecha_inicio,
       c.estado, c.duracion_meses, c.fecha_ultimo_pago
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
body { 
    background: #f7f9fb; 
    font-family: "Poppins", sans-serif; 
}

.card-table { border-radius: 1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
th { background-color: #0d6efd; color: white; }
td { vertical-align: middle; }

.navbar-pc {
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 1000;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    padding: 10px 40px;
}
</style>


</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary d-none d-lg-flex px-4 py-2">
  <a class="navbar-brand d-flex align-items-center" href="admin.html">
    <img src="logo.jpg" alt="Logo" width="40" height="40" class="me-2">
    <span class="fw-bold">Conect@T Internet</span>
  </a>

  <div class="mx-auto text-white fw-semibold h5 m-0"><h2>Panel de Contratos</h2></div>

  <a href="admin.php" class="btn btn-outline-light">
    <i class="bi bi-arrow-left"></i> Regresar</a>
</nav>

<br><br>

<div class="container">

    <div class="d-flex align-items-center mb-3">
        <a href="admin.php" class="btn btn-outline-primary me-3">
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
                        <th>Duración</th>
                        <th>Último Pago</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th>Acciones</th>
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

                                <td><?= $row['duracion_meses'] ?> meses</td>

                                <td><?= $row['fecha_ultimo_pago'] ?? '—' ?></td>

                                <td>$<?= number_format($row['precio_mensual'],2) ?></td>

                                <td>
                                    <?php
                                    $estado_class = match($row['estado']){
                                        'Activo'=>'success',
                                        'Inactivo'=>'danger',
                                        'Suspendido'=>'warning',
                                        default=>'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $estado_class ?>"><?= $row['estado'] ?></span>
                                </td>
                                
                                
                                <td>
                                     <a href="editar_contrato.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil-square"></i> Editar
                                     </a>
                                     <a href="contrato_pdf.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="bi bi-file-earmark-pdf"></i> PDF
                                     </a>
                                </td>


                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center">No hay contratos registrados.</td></tr>
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
