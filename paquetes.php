<?php
include("conexion.php");

// Agregar o editar paquete
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'] ?? '';
    $nombre = $_POST['nombre'];
    $velocidad = $_POST['velocidad'];
    $precio = $_POST['precio'];
    $descripcion = $_POST['descripcion'];

    if ($id) {
        // Editar
        $stmt = $conexion->prepare("UPDATE paquetes SET nombre=?, velocidad=?, precio=?, descripcion=? WHERE id=?");
        $stmt->bind_param("ssdsi", $nombre, $velocidad, $precio, $descripcion, $id);
        $stmt->execute();
    } else {
        // Agregar
        $stmt = $conexion->prepare("INSERT INTO paquetes (nombre, velocidad, precio, descripcion) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssds", $nombre, $velocidad, $precio, $descripcion);
        $stmt->execute();
    }
    header("Location: paquetes.php");
    exit;
}

// Borrar paquete
if (isset($_GET['borrar'])) {
    $id = intval($_GET['borrar']);
    $conexion->query("DELETE FROM paquetes WHERE id=$id");
    header("Location: paquetes.php");
    exit;
}

// Consultar paquetes
$resultado = $conexion->query("SELECT * FROM paquetes ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestión de Paquetes — Conect@T</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <style>
    body { background: #f4f6f9; font-family: "Poppins", sans-serif; }
    .container { margin-top: 40px; }
    .card { border-radius: 1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    .btn-custom { border-radius: 10px; font-weight: 500; }
    table { border-radius: 10px; overflow: hidden; }
  </style>
</head>
<body>

<div class="container">
  <div class="card p-4 bg-white">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="d-flex align-items-center gap-2">
        <a href="admin.php" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-arrow-left-circle"></i> Regresar
        </a>
        <h3 class="fw-bold text-primary m-0"><i class="bi bi-hdd-network"></i> Gestión de Paquetes</h3>
      </div>
      <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalPaquete">
        <i class="bi bi-plus-circle"></i> Nuevo Paquete
      </button>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-primary">
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Velocidad</th>
            <th>Precio</th>
            <th>Descripción</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($fila = $resultado->fetch_assoc()): ?>
            <tr>
              <td><?= $fila['id'] ?></td>
              <td><?= htmlspecialchars($fila['nombre']) ?></td>
              <td><?= htmlspecialchars($fila['velocidad']) ?></td>
              <td>$<?= number_format($fila['precio'], 2) ?></td>
              <td><?= htmlspecialchars($fila['descripcion'] ?? '-') ?></td>
              <td>
                <button class="btn btn-warning btn-sm" 
                        onclick="editarPaquete(<?= $fila['id'] ?>, '<?= addslashes($fila['nombre']) ?>', '<?= addslashes($fila['velocidad']) ?>', <?= $fila['precio'] ?>, '<?= addslashes($fila['descripcion']) ?>')">
                  <i class="bi bi-pencil-square"></i>
                </button>
                <a href="?borrar=<?= $fila['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que deseas eliminar este paquete?')">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal para agregar/editar paquete -->
<div class="modal fade" id="modalPaquete" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Gestionar Paquete</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id" id="id">
        <div class="mb-3">
          <label class="form-label">Nombre</label>
          <input type="text" name="nombre" id="nombre" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Velocidad</label>
          <input type="text" name="velocidad" id="velocidad" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Precio</label>
          <input type="number" step="0.01" name="precio" id="precio" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Descripción</label>
          <textarea name="descripcion" id="descripcion" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Guardar</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editarPaquete(id, nombre, velocidad, precio, descripcion) {
  document.getElementById('id').value = id;
  document.getElementById('nombre').value = nombre;
  document.getElementById('velocidad').value = velocidad;
  document.getElementById('precio').value = precio;
  document.getElementById('descripcion').value = descripcion;
  new bootstrap.Modal(document.getElementById('modalPaquete')).show();
}
</script>

</body>
</html>
<?php $conexion->close(); ?>
