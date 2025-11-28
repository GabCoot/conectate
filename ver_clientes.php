<?php
include("conexion.php");

// Traer solo los clientes y columnas existentes
$query = "
SELECT id, nombre, rol, correo, telefono, direccion, activo
FROM usuarios
WHERE rol = 'cliente'
ORDER BY id ASC
";
$result = $conexion->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Clientes — Conect@T</title>

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<style>
    .card-table { border-radius: 1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
    th { background-color: #0d6efd; color: white; }
    td { vertical-align: middle; }
 
body { 
    background: #f7f9fb; 
    font-family: "Poppins", sans-serif; 
}

.card-table { 
    border-radius: 1rem; 
    box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
}

th { 
    background-color: #0d6efd; 
    color: white; 
}

td { 
    vertical-align: middle; 
}

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
<nav class="navbar navbar-expand-lg navbar-dark bg-primary d-none d-lg-flex px-4 py-2" style="box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
  <a class="navbar-brand d-flex align-items-center" href="admin.html">
    <img src="logo.jpg" alt="Logo" width="40" height="40" class="me-2">
    <span class="fw-bold">Conect@T Internet</span>
  </a>

  <div class="mx-auto text-white fw-semibold h5 m-0"><h2>Panel de Clientes</h2></div>

  <a href="admin.php" class="btn btn-outline-light">
    <i class="bi bi-arrow-left"></i> Regresar</a>
</nav>
<br>
<div class="container">
    
   <div class="d-flex align-items-center mb-3">
        <a href="admin.php" class="btn btn-outline-primary me-3">
        <i class="bi bi-arrow-left"></i> Regresar
        </a>
        <h2 class="text-primary fw-bold mb-0">Panel De Clientes registrados</h2>
    </div>

    <div class="card card-table p-3 bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Correo</th>
                        <th>Teléfono</th>
                        <th>Dirección</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['nombre']) ?></td>
                                <td><?= htmlspecialchars($row['correo']) ?></td>
                                <td><?= htmlspecialchars($row['telefono'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['direccion'] ?? '-') ?></td>
                                <td>
                                    <?php if($row['activo'] == 1): ?>
                                        <span class="badge bg-success">Sí</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">No</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modificarModal" 
                                            data-id="<?= $row['id'] ?>"
                                            data-nombre="<?= htmlspecialchars($row['nombre']) ?>"
                                            data-correo="<?= htmlspecialchars($row['correo']) ?>"
                                            data-telefono="<?= htmlspecialchars($row['telefono'] ?? '') ?>"
                                            data-direccion="<?= htmlspecialchars($row['direccion'] ?? '') ?>"
                                            data-activo="<?= $row['activo'] ?>">
                                        <i class="bi bi-pencil-square"></i> Modificar
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay clientes registrados.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal de modificación -->
<div class="modal fade" id="modificarModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="formModificarCliente" method="POST">
        <div class="modal-header">
          <h5 class="modal-title">Modificar Cliente</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="id" id="modal-id">
            
            <div class="mb-3">
                <label for="modal-nombre" class="form-label">Nombre</label>
                <input type="text" class="form-control" name="nombre" id="modal-nombre" required>
            </div>
            <div class="mb-3">
                <label for="modal-correo" class="form-label">Correo</label>
                <input type="email" class="form-control" name="correo" id="modal-correo" required>
            </div>
            <div class="mb-3">
                <label for="modal-telefono" class="form-label">Teléfono</label>
                <input type="text" class="form-control" name="telefono" id="modal-telefono">
            </div>
            <div class="mb-3">
                <label for="modal-direccion" class="form-label">Dirección</label>
                <input type="text" class="form-control" name="direccion" id="modal-direccion">
            </div>
            <div class="mb-3">
                <label for="modal-activo" class="form-label">Activo</label>
                <select class="form-select" name="activo" id="modal-activo">
                    <option value="1">Sí</option>
                    <option value="0">No</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Contenedor para toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="toast-container"></div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Pasar datos del botón al modal
var modificarModal = document.getElementById('modificarModal');
modificarModal.addEventListener('show.bs.modal', function (event) {
  var button = event.relatedTarget;
  document.getElementById('modal-id').value = button.getAttribute('data-id');
  document.getElementById('modal-nombre').value = button.getAttribute('data-nombre');
  document.getElementById('modal-correo').value = button.getAttribute('data-correo');
  document.getElementById('modal-telefono').value = button.getAttribute('data-telefono');
  document.getElementById('modal-direccion').value = button.getAttribute('data-direccion');
  document.getElementById('modal-activo').value = button.getAttribute('data-activo');
});

// Enviar formulario sin recargar
document.getElementById('formModificarCliente').addEventListener('submit', function(e){
    e.preventDefault(); 
    let formData = new FormData(this);

    fetch('actualizar_cliente.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        const toastContainer = document.getElementById('toast-container');
        const toastEl = document.createElement('div');
        toastEl.className = `toast align-items-center text-bg-${data.success ? 'success' : 'danger'} border-0`;
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${data.message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        toastContainer.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();

        if(data.success){
            const row = document.querySelector(`button[data-id='${formData.get('id')}']`).closest('tr');
            row.querySelector('td:nth-child(2)').innerText = formData.get('nombre');
            row.querySelector('td:nth-child(3)').innerText = formData.get('correo');
            row.querySelector('td:nth-child(4)').innerText = formData.get('telefono') || '-';
            row.querySelector('td:nth-child(5)').innerText = formData.get('direccion') || '-';
            row.querySelector('td:nth-child(6)').innerHTML = formData.get('activo') == 1
                ? '<span class="badge bg-success">Sí</span>'
                : '<span class="badge bg-danger">No</span>';
        }

        // Cerrar modal
        const modalEl = bootstrap.Modal.getInstance(document.getElementById('modificarModal'));
        modalEl.hide();
    })
    .catch(err => console.error(err));
});
</script>

</body>
</html>

<?php $conexion->close(); ?>
