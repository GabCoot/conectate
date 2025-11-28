<?php

include 'pago.php'; 
include 'notificar_pagos.php';
$estadisticas = obtener_estadisticas($conexion);

$buscar_ref = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$resultado_pago = null;

$buscar_nombre = isset($_GET['buscar_nombre']) ? trim($_GET['buscar_nombre']) : '';
$resultados_nombre = [];

if (!empty($buscar_ref)) {
    $resultado_pago = buscar_pago_por_referencia($conexion, $buscar_ref);
}

if (!empty($buscar_nombre)) {
    $resultados_nombre = buscar_pagos_por_nombre($conexion, $buscar_nombre);
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Confirmación de Pagos - Conéctate</title>
<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px;
}

header {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

header h1 {
    color: #333;
    font-size: 28px;
}

.contenedor {
    max-width: 1200px;
    margin: 0 auto;
}

.estadisticas {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    border-left: 4px solid;
}

.stat-card.pendientes { border-left-color: #ffa500; }
.stat-card.hoy { border-left-color: #28a745; }
.stat-card.mes { border-left-color: #667eea; }

.stat-card h3 {
    font-size: 14px;
    color: #666;
    margin-bottom: 10px;
}

.stat-card .numero {
    font-size: 32px;
    font-weight: bold;
    color: #333;
}

.stat-card .monto {
    font-size: 18px;
    color: #666;
    margin-top: 5px;
}


.mensaje {
    background: #d4edda;
    color: #155724;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid #c3e6cb;
    font-size: 16px;
}

.mensaje.error {
    background: #f8d7da;
    color: #721c24;
    border-color: #f5c6cb;
}

.buscador {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    text-align: center;
}

.buscador h2 {
    color: #333;
    margin-bottom: 10px;
    font-size: 24px;
}

.buscador p {
    color: #666;
    margin-bottom: 20px;
}

.buscador form {
    display: flex;
    gap: 10px;
    max-width: 600px;
    margin: 0 auto;
}

.buscador input[type="text"] {
    flex: 1;
    padding: 15px;
    border: 2px solid #ddd;
    border-radius: 8px;
    font-size: 18px;
    text-align: center;
}

.buscador input[type="text"]:focus {
    border-color: #667eea;
    outline: none;
}

.buscador button {
    padding: 15px 40px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 18px;
    font-weight: bold;
    transition: background 0.3s;
}

.buscador button:hover {
    background: #5568d3;
}


.resultado-pago {
    background: white;
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.resultado-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 20px;
    margin-bottom: 20px;
}

.resultado-header h2 {
    font-size: 26px;
    color: #333;
}

.referencia-grande {
    font-size: 18px;
    color: #667eea;
    font-weight: bold;
    margin-top: 5px;
}

.badge-grande {
    padding: 10px 25px;
    border-radius: 25px;
    font-size: 16px;
    font-weight: bold;
}

.badge-activo {
    background: #d4edda;
    color: #155724;
}

.badge-pendiente {
    background: #fff3cd;
    color: #856404;
}

.info-cliente {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.info-cliente h3 {
    font-size: 18px;
    color: #333;
    margin-bottom: 15px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.info-item {
    color: #333;
    font-size: 15px;
}

.info-item strong {
    color: #667eea;
}

.info-pago {
    background: #e7f3ff;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.info-pago h3 {
    font-size: 18px;
    color: #333;
    margin-bottom: 15px;
}

.pago-resumen {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.pago-item {
    font-size: 15px;
    color: #333;
}

.pago-item.total {
    grid-column: 1 / -1;
    text-align: center;
    font-size: 24px;
    padding: 15px;
    background: white;
    border-radius: 8px;
    margin-top: 10px;
}

.monto-total {
    color: #667eea;
    font-size: 32px;
    font-weight: bold;
}

.pagos-incluidos {
    margin-bottom: 20px;
}

.pagos-incluidos h3 {
    font-size: 18px;
    color: #333;
    margin-bottom: 15px;
}

.tabla-detalles {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
}

.tabla-detalles thead {
    background: #f8f9fa;
}

.tabla-detalles th {
    padding: 15px;
    text-align: left;
    font-weight: bold;
    color: #333;
    border-bottom: 2px solid #dee2e6;
}

.tabla-detalles td {
    padding: 15px;
    border-bottom: 1px solid #dee2e6;
}

.tabla-detalles tbody tr:hover {
    background: #f8f9fa;
}

.form-confirmar {
    text-align: center;
    padding: 20px;
}

.btn-confirmar-pago {
    padding: 15px 50px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 20px;
    font-weight: bold;
    transition: all 0.3s;
}

.btn-confirmar-pago:hover {
    background: #218838;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40,167,69,0.4);
}

.pago-confirmado {
    text-align: center;
    padding: 20px;
    background: #d4edda;
    border-radius: 8px;
    color: #155724;
    font-size: 18px;
    font-weight: bold;
}

.resultado-vacio {
    background: white;
    border-radius: 10px;
    padding: 40px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    text-align: center;
}

.resultado-vacio p {
    font-size: 18px;
    color: #666;
    margin-bottom: 10px;
}

.regresar {
    text-align: center;
    margin-top: 30px;
}

.regresar button {
    padding: 12px 30px;
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    transition: all 0.3s;
}

.regresar button:hover {
    background: #667eea;
    color: white;
}

@media (max-width: 768px) {
    .estadisticas {
        grid-template-columns: 1fr;
    }
    
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .pago-resumen {
        grid-template-columns: 1fr;
    }
    
    .resultado-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .buscador form {
        flex-direction: column;
    }
}
</style>
</head>
<body>

<header>
    <h1> Confirmación de Pagos - Conéctate</h1>
</header>

<div class="contenedor">

    <div class="estadisticas">
        <div class="stat-card pendientes">
            <h3>Pagos Pendientes de Confirmar</h3>
            <div class="numero"><?= $estadisticas['pendientes']['total'] ?? 0 ?></div>
            <div class="monto">$<?= number_format($estadisticas['pendientes']['monto_total'] ?? 0, 2) ?></div>
        </div>
        <div class="stat-card hoy">
            <h3>Confirmados Hoy</h3>
            <div class="numero"><?= $estadisticas['hoy']['total'] ?? 0 ?></div>
            <div class="monto">$<?= number_format($estadisticas['hoy']['monto_total'] ?? 0, 2) ?></div>
        </div>
        <div class="stat-card mes">
            <h3>Total del Mes</h3>
            <div class="numero"><?= $estadisticas['mes_actual']['total'] ?? 0 ?></div>
            <div class="monto">$<?= number_format($estadisticas['mes_actual']['monto_total'] ?? 0, 2) ?></div>
        </div>
       <div class="stat-card mes">
    <h3>Reportes</h3>
    
    <?php
    //excel
        echo '<div class="reporte-excel" style="margin-top:10px;">';
        echo '  <a href="reporte_pagos.php" 
                    target="_blank" 
                    class="btn-pdf">
                    <i class="bi bi-file-earmark-spreadsheet"></i>
                    Descargar Reporte de Pagos (Excel)
                </a>';
        echo '</div>';
    ?>
</div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="mensaje <?= strpos($_GET['msg'], 'Error') !== false ? 'error' : '' ?>">
            <?= htmlspecialchars($_GET['msg']); ?>
            <?php if(isset($_GET['ref'])): ?>
                <br><strong>Referencia confirmada:</strong> <?= htmlspecialchars($_GET['ref']) ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="buscador">
        <h2>Buscar Pago por Referencia</h2>
        <p>Ingrese la referencia del pago que el cliente desea confirmar</p>
        <form method="GET" action="cobro.php">
            <input type="text" 
                   name="buscar" 
                   placeholder="Ej: CON-25-U146-1763079158255-2792" 
                   value="<?= htmlspecialchars($buscar_ref) ?>"
                   required
                   autofocus>
            <button type="submit">Buscar</button>
        </form>
    </div>
                
<div class="buscador">
    <h2>Buscar Pago por Nombre del Cliente</h2>
    <p>Puede escribir el nombre completo o parcial</p>

    <form method="GET" action="cobro.php">
        <input type="text"
               name="buscar_nombre"
               placeholder="Ej: Pedro, Gaspar, Jimena"
               value="<?= htmlspecialchars($buscar_nombre ?? '') ?>">
        <button type="submit">Buscar</button>
    </form>
</div>


    <?php if (!empty($buscar_ref)): ?>
        <?php mostrar_resultado_busqueda($resultado_pago);
         ?>
    <?php endif; ?>

      <?php 
if (!empty($buscar_nombre)) {

    if (empty($resultados_nombre)) {
        echo "<div class='resultado-vacio'>
                <p>No se encontraron pagos para ese nombre.</p>
              </div>";
    } else {

        foreach ($resultados_nombre as $pago) {
            mostrar_resultado_busqueda($pago);
        }

    }
}
?>
    <div class="regresar">
        <button onclick="window.location.href='admin.html'">← Regresar al Panel de Administración</button>
    </div>
</div>

</body>
</html>