<?php
include("conexion.php");
ini_set('display_errors', 1);
error_reporting(E_ALL);

$usuario_id = isset($_GET['usuario_id']) ? intval($_GET['usuario_id']) : 0;
// Cargar datos del usuario para mostrar nombre
$user = null;
if ($usuario_id) {
    $rq = $conexion->prepare("SELECT id, nombre FROM usuarios WHERE id = ?");
    $rq->bind_param("i", $usuario_id);
    $rq->execute();
    $res = $rq->get_result();
    $user = $res->fetch_assoc();
    $rq->close();
}

// Cargar paquetes disponibles para el select
$paquetes = [];
$rp = $conexion->query("SELECT id, nombre, precio FROM paquetes ORDER BY id ASC");
if ($rp) {
    while ($p = $rp->fetch_assoc()) $paquetes[] = $p;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id = intval($_POST['usuario_id']);
    $paquete_id = intval($_POST['paquete_id']);
    $fecha_inicio = $_POST['fecha_inicio'] ?: date('Y-m-d');
    $precio_mensual = floatval($_POST['precio_mensual']);
    $duracion = intval($_POST['duracion_meses']) ?: 6;
    $timestamp_inicio = strtotime($fecha_inicio);
    $fecha_fin = date('Y-m-d', strtotime("+{$duracion} months", $timestamp_inicio));
    $estado = $_POST['estado'] ?: 'Activo';

    // Insert contrato
    $conexion->begin_transaction();
    try {
        $stmt = $conexion->prepare("INSERT INTO contratos (usuario_id, paquete_id, fecha_inicio, fecha_fin, precio_mensual, estado, duracion_meses) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissdsi", $usuario_id, $paquete_id, $fecha_inicio, $fecha_fin, $precio_mensual, $estado, $duracion);
        $stmt->execute();
        $stmt->close();
        $conexion->commit();
        header("Location: contratos.php"); // volver a lista de contratos
        exit;
    } catch (Exception $e) {
        $conexion->rollback();
        $error = "Error creando contrato: " . $e->getMessage();
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Crear Contrato</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="p-4">
    <div class="container">
        <h3>Crear contrato <?= $user ? 'para ' . htmlspecialchars($user['nombre']) : '' ?></h3>
        <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="usuario_id" value="<?= htmlspecialchars($usuario_id) ?>">
            <div class="mb-3">
                <label>Paquete</label>
                <select name="paquete_id" class="form-select" required>
                    <?php foreach ($paquetes as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?> — <?= $p['precio'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label>Fecha inicio</label>
                <input type="date" name="fecha_inicio" value="<?= date('Y-m-d') ?>" class="form-control">
            </div>
            <div class="mb-3">
                <label>Precio mensual</label>
                <input type="number" step="0.01" name="precio_mensual" class="form-control" required>
            </div>
            <div class="mb-3">
                <label>Duración (meses)</label>
                <input type="number" name="duracion_meses" class="form-control" value="6">
            </div>
            <div class="mb-3">
                <label>Estado</label>
                <select name="estado" class="form-select">
                    <option value="Activo">Activo</option>
                    <option value="Inactivo">Inactivo</option>
                    <option value="Suspendido">Suspendido</option>
                </select>
            </div>
            <button class="btn btn-primary">Crear contrato</button>
            <a href="ver_clientes.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>

</html>