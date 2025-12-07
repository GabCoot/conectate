<?php
session_start();
include 'conexion.php';

// Usuario NO logueado → fuera
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit;
}

// Validar paquete
$id_paquete = $_GET['id_paquete'] ?? null;

if (!$id_paquete) {
    die("Paquete no especificado");
}

// Traer datos del paquete
$stmt = $conexion->prepare("SELECT * FROM paquetes WHERE id = ?");
$stmt->bind_param("i", $id_paquete);
$stmt->execute();
$paquete = $stmt->get_result()->fetch_assoc();

if (!$paquete) {
    die("El paquete no existe");
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Confirmar Contrato</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">

    <div class="card shadow-lg p-4">
        <h2 class="text-center mb-4">Confirmación de Contrato</h2>

        <h4 class="text-primary">📦 Paquete seleccionado</h4>
        <ul>
            <li><b>Nombre:</b> <?= $paquete['nombre'] ?></li>
            <li><b>Velocidad:</b> <?= $paquete['velocidad'] ?></li>
            <li><b>Precio mensual:</b> $<?= number_format($paquete['precio'], 2) ?></li>
            <li><b>Descripción:</b> <?= nl2br($paquete['descripcion']) ?></li>
            <li><b>Duración:</b> 6 meses</li>
        </ul>

        <hr>

        <h4 class="text-primary">👤 Tus datos</h4>
        <ul>
            <li><b>Nombre:</b> <?= $_SESSION['nombre'] ?></li>
            <li><b>Correo:</b> <?= $_SESSION['correo'] ?></li>
            <li><b>Teléfono:</b> <?= $_SESSION['telefono'] ?></li>
            <li><b>Dirección:</b> <?= $_SESSION['direccion'] ?></li>
        </ul>

        <form action="crear_contrato.php" method="POST" class="mt-4">

            <input type="hidden" name="paquete_id" value="<?= $id_paquete ?>">

            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="terminos" required>
                <label class="form-check-label" for="terminos">
                    Acepto los términos del contrato
                </label>
            </div>

            <button class="btn btn-primary btn-lg w-100 mt-3">
                Aceptar y Crear Contrato
            </button>
        </form>

    </div>
</div>

</body>
</html>
