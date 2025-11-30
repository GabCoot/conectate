<?php
include("conexion.php");

// Buscar clientes
$buscar = $_GET['buscar'] ?? '';

// Consulta para la lista
if ($buscar) {
    $sql = "SELECT id, nombre, rol, correo, telefono, direccion, activo
            FROM usuarios 
            WHERE rol='cliente' 
              AND (nombre LIKE ? OR direccion LIKE ?)
            ORDER BY id ASC";
    $stmt = $conexion->prepare($sql);
    $param = "%" . $buscar . "%";
    $stmt->bind_param("ss", $param, $param);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "
    SELECT id, nombre, rol, correo, telefono, direccion, activo
    FROM usuarios
    WHERE rol = 'cliente'
    ORDER BY id ASC
    ";
    $result = $conexion->query($query);
}

// --- Estadísticas calculadas por consultas separadas (más fiables) ---

// Total activos / inactivos (desde usuarios)
$total_activos = 0;
$total_inactivos = 0;
$res_counts = $conexion->query("SELECT 
    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) AS activos,
    SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) AS inactivos
    FROM usuarios WHERE rol = 'cliente'");
if ($res_counts) {
    $r = $res_counts->fetch_assoc();
    $total_activos = intval($r['activos'] ?? 0);
    $total_inactivos = intval($r['inactivos'] ?? 0);
}

$total_contrato = $total_activos;

// Guarda filas para la tabla 
$clientes = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $clientes[] = $row;
    }
}
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
        body {
            background: #f7f9fb;
            font-family: "Poppins", sans-serif;
            padding: 40px 20px;
        }

        .card-table {
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        th {
            background-color: #0d6efd;
            color: white;
        }

        td {
            vertical-align: middle;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="text-center mb-3">
            <h1 class="text-primary fw-bold">Lista de Clientes</h1>
        </div>

        <div class="row mb-3" id="estadisticas-clientes">
            <div class="col-md-4">
                <div class="card text-center p-3">
                    <h6>Clientes Activos</h6>
                    <h3 id="total-activos"><?= $total_activos ?></h3>

                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center p-3">
                    <h6>Clientes Inactivos</h6>
                    <h3 id="total-inactivos"><?= $total_inactivos ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-center p-3">
                    <h6>Clientes con Contrato Vigente</h6>
                    <h3 id="total-contratos"><?= $total_contrato ?></h3>
                </div>
            </div>
        </div>

        <div class="mb-3 d-flex justify-content-between align-items-center">
            <input type="text" id="buscarInput" class="form-control me-3" style="max-width: 300px;" placeholder="Buscar por nombre o dirección...">

            <div>
                <a href="registrar_cliente.php" class="btn btn-success me-2">
                    <i class="bi bi-person-plus"></i> Nuevo cliente
                </a>

                <a href="export_contracts.php" class="btn btn-info">
                    <i class="bi bi-download"></i> Descargar contratos (ZIP)
                </a>

                <a href="admin.html" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Regresar
                </a>
            </div>
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
                        <?php if (count($clientes) > 0): ?>
                            <?php foreach ($clientes as $row): ?>
                                <tr>
                                    <td><?= $row['id'] ?></td>
                                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                                    <td><?= htmlspecialchars($row['correo']) ?></td>
                                    <td><?= htmlspecialchars($row['telefono'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['direccion'] ?? '-') ?></td>
                                    <td>
                                        <?= $row['activo'] == 1 ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-danger">No</span>' ?>
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
                            <?php endforeach; ?>
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
        modificarModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            document.getElementById('modal-id').value = button.getAttribute('data-id');
            document.getElementById('modal-nombre').value = button.getAttribute('data-nombre');
            document.getElementById('modal-correo').value = button.getAttribute('data-correo');
            document.getElementById('modal-telefono').value = button.getAttribute('data-telefono');
            document.getElementById('modal-direccion').value = button.getAttribute('data-direccion');
            document.getElementById('modal-activo').value = button.getAttribute('data-activo');
        });

        // Enviar formulario 
        document.getElementById('formModificarCliente').addEventListener('submit', function(e) {
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
                    const toast = new bootstrap.Toast(toastEl, {
                        delay: 3000
                    });
                    toast.show();

                    if (data.success) {
                        // fila y badges
                        const id = formData.get('id');
                        const row = document.querySelector(`button[data-id='${id}']`).closest('tr');

                        // estado anterior
                        const tdActivo = row.querySelector('td:nth-child(6)');
                        const wasActivo = tdActivo && tdActivo.innerText.includes('Sí');

                        // actualizar fila con los nuevos valores 
                        row.querySelector('td:nth-child(2)').innerText = formData.get('nombre');
                        row.querySelector('td:nth-child(3)').innerText = formData.get('correo');
                        row.querySelector('td:nth-child(4)').innerText = formData.get('telefono') || '-';
                        row.querySelector('td:nth-child(5)').innerText = formData.get('direccion') || '-';
                        const nowActivo = formData.get('activo') == 1;
                        row.querySelector('td:nth-child(6)').innerHTML = nowActivo ?
                            '<span class="badge bg-success">Sí</span>' :
                            '<span class="badge bg-danger">No</span>';

                        // === ACTUALIZAR CONTADORES EN LA UI ===
                        // helper para parsear y escribir números en los cards
                        function getIntById(id) {
                            return parseInt(document.getElementById(id).innerText || '0', 10) || 0;
                        }

                        function setIntById(id, n) {
                            document.getElementById(id).innerText = n;
                        }

                        // recuperar valores actuales
                        let activos = getIntById('total-activos');
                        let inactivos = getIntById('total-inactivos');
                        let contratos = getIntById('total-contratos');

                        // Ajuste: si el estado cambió, actualiza activos/inactivos
                        if (wasActivo && !nowActivo) {
                            activos = Math.max(0, activos - 1);
                            inactivos = inactivos + 1;
                        } else if (!wasActivo && nowActivo) {
                            activos = activos + 1;
                            inactivos = Math.max(0, inactivos - 1);
                        }
                        // Según lo acordado, "contratos vigentes" = usuarios activos
                        contratos = activos;

                        // Guardar en DOM
                        setIntById('total-activos', activos);
                        setIntById('total-inactivos', inactivos);
                        setIntById('total-contratos', contratos);
                    }

                    // Cerrar modal 
                    const modalEl = bootstrap.Modal.getInstance(document.getElementById('modificarModal'));
                    if (modalEl) modalEl.hide();
                })

                .catch(err => console.error(err));
        });
    </script>

    <script>
        const inputBuscar = document.getElementById('buscarInput');
        const tbody = document.querySelector('table tbody');

        inputBuscar.addEventListener('input', function() {
            const valor = inputBuscar.value;

            fetch('buscar_clientes.php?buscar=' + encodeURIComponent(valor))
                .then(res => res.text())
                .then(html => {
                    tbody.innerHTML = html;
                })
                .catch(err => console.error(err));
        });
    </script>

</body>

</html>

<?php $conexion->close(); ?>