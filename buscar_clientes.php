<?php
include("conexion.php");

$buscar = $_GET['buscar'] ?? '';

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
    $result = $conexion->query("SELECT id, nombre, rol, correo, telefono, direccion, activo
                                FROM usuarios
                                WHERE rol = 'cliente'
                                ORDER BY id ASC");
}
?>

<?php if ($result->num_rows > 0): ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['nombre']) ?></td>
            <td><?= htmlspecialchars($row['correo']) ?></td>
            <td><?= htmlspecialchars($row['telefono'] ?? '-') ?></td>
            <td><?= htmlspecialchars($row['direccion'] ?? '-') ?></td>
            <td>
                <?php if ($row['activo'] == 1): ?>
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