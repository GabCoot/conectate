<?php
include 'pago.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel de Cobros - Conéctate</title>
<link rel="stylesheet" href="estilo3.css">
</head>
<body>
<header>
  <h1>Panel de Cobros - Conéctate</h1>
</header>

<div class="contenedor">
  <?php if(isset($_GET['msg'])): ?>
    <div class="mensaje"><?= htmlspecialchars($_GET['msg']); ?></div>
  <?php endif; ?>

  <div class="buscador">
    <form method="GET" action="cobro.php">
      <input type="text" name="buscar" placeholder="Buscar cliente o dirección..." 
             value="<?= isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : '' ?>">
      <button type="submit">Buscar</button>
    </form>
  </div>

  <?php mostrar_tabla($conexion, isset($_GET['buscar']) ? $_GET['buscar'] : ''); ?>

  <div class="regresar">
    <button onclick="window.location.href='admin.html'">← Regresar</button>
  </div>
</div>


</body>
</html>
