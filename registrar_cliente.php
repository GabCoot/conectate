<?php
include("conexion.php");
ini_set('display_errors',1); error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoger datos
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $telefono = trim($_POST['telefono']);
    $direccion = trim($_POST['direccion']);
    // Validaciones mínimas
    if ($nombre === '' || $correo === '') {
        $error = "Nombre y correo son obligatorios.";
    } else {
        // Transacción: insertar cliente y opcionalmente crear contrato
        $conexion->begin_transaction();
        try {
            $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, rol, correo, telefono, direccion, password, activo) VALUES (?, 'cliente', ?, ?, ?, '', 1)");
            $stmt->bind_param("ssss", $nombre, $correo, $telefono, $direccion);
            $stmt->execute();
            $nuevo_id = $stmt->insert_id;
            $stmt->close();

            $conexion->commit();

            // Redirigir a crear contrato para ese cliente (opcional)
            header("Location: crear_contrato.php?usuario_id={$nuevo_id}");
            exit;
        } catch (Exception $e) {
            $conexion->rollback();
            $error = "Error al crear cliente: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Registrar Cliente</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
  <h3>Registrar nuevo cliente</h3>
  <?php if(!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="post">
    <div class="mb-3">
      <label>Nombre</label>
      <input class="form-control" name="nombre" required>
    </div>
    <div class="mb-3">
      <label>Correo</label>
      <input class="form-control" type="email" name="correo" required>
    </div>
    <div class="mb-3">
      <label>Teléfono</label>
      <input class="form-control" name="telefono">
    </div>
    <div class="mb-3">
      <label>Dirección</label>
      <input class="form-control" name="direccion">
    </div>
    <button class="btn btn-success">Crear cliente y asignar contrato</button>
    <a href="ver_clientes.php" class="btn btn-secondary">Cancelar</a>
  </form>
</div>
</body>
</html>
