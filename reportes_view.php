<?php
include 'reportes.php';


$tipo_reporte = isset($_GET['tipo']) ? $_GET['tipo'] : 'clientes';
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';


$estadisticas = obtener_estadisticas_reportes($conexion);

// Obtener datos según el tipo de reporte
$resultado = null;
switch($tipo_reporte) {
    case 'clientes':
        $resultado = obtener_reporte_clientes($conexion, $filtro);
        break;
    case 'contratos':
        $resultado = obtener_reporte_contratos($conexion, $filtro);
        break;
    case 'pagos':
        $resultado = obtener_reporte_pagos($conexion, $filtro, $estado);
        break;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reportes Generales</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}
.navbar-admin {
    position: relative;
    top: -20px;            
    width: 100vw;
    margin-left: calc(50% - 50vw);
    background-color: #0d6efd;
    color: white;
    padding: 15px 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}    
.navbar-admin img {
      height: 45px;
    }
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px;
    
}

.container {
    max-width: 1600px;
    margin: 0 auto;
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

.estadisticas {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

.stat-card.clientes { border-left-color: #667eea; }
.stat-card.contratos { border-left-color: #28a745; }
.stat-card.pagos { border-left-color: #ffc107; }
.stat-card.ingresos { border-left-color: #17a2b8; }

.stat-card h3 {
    font-size: 13px;
    color: #666;
    margin-bottom: 8px;
}

.stat-card .numero {
    font-size: 28px;
    font-weight: bold;
    color: #333;
}

.stat-card .monto {
    font-size: 16px;
    color: #666;
    margin-top: 5px;
}

.tabs-container {
    background: white;
    border-radius: 10px;
    padding: 10px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.tabs {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.tab {
    padding: 12px 30px;
    background: #f8f9fa;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    font-weight: bold;
    color: #666;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-block;
}

.tab:hover {
    background: #e9ecef;
}

.tab.active {
    background: #667eea;
    color: white;
}

.filtros-panel {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.filtros-form {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: end;
}

.filtro-grupo {
    flex: 1;
    min-width: 200px;
}

.filtro-grupo label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
    font-size: 14px;
}

.filtro-grupo input,
.filtro-grupo select {
    width: 100%;
    padding: 10px;
    border: 2px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.btn-buscar {
    padding: 10px 30px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    transition: background 0.3s;
}

.btn-buscar:hover {
    background: #5568d3;
}

.btn-limpiar {
    padding: 10px 30px;
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    transition: background 0.3s;
}

.btn-limpiar:hover {
    background: #5a6268;
}

.reporte-container {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.reporte-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.reporte-header h2 {
    font-size: 24px;
    color: #333;
}

.btn-exportar {
    padding: 10px 20px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: bold;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-exportar:hover {
    background: #218838;
    transform: translateY(-2px);
}

.tabla-container {
    overflow-x: auto;
}

.tabla-reporte {
    width: 100%;
    border-collapse: collapse;
    min-width: 800px;
}

.tabla-reporte thead {
    background: #f8f9fa;
    position: sticky;
    top: 0;
}

.tabla-reporte th {
    padding: 12px;
    text-align: left;
    font-weight: bold;
    color: #333;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
}

.tabla-reporte td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
}

.tabla-reporte tbody tr:hover {
    background: #f8f9fa;
}

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.badge-activo {
    background: #d4edda;
    color: #155724;
}

.badge-inactivo {
    background: #e2e3e5;
    color: #383d41;
}

.badge-suspendido {
    background: #f8d7da;
    color: #721c24;
}

.badge-pagado {
    background: #d4edda;
    color: #155724;
}

.badge-pendiente {
    background: #fff3cd;
    color: #856404;
}

.badge-info {
    background: #d1ecf1;
    color: #0c5460;
}

.monto-positivo {
    color: #28a745;
    font-weight: bold;
}

.monto-negativo {
    color: #dc3545;
    font-weight: bold;
}

.progreso-container {
    width: 100%;
    height: 20px;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 5px;
}

.progreso-barra {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
    transition: width 0.3s;
}

.sin-resultados {
    text-align: center;
    padding: 40px;
    color: #666;
    font-size: 18px;
}

.btn-regresar {
    text-align: center;
    margin-top: 20px;
}

.btn-regresar button {
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

.btn-regresar button:hover {
    background: #667eea;
    color: white;
}

@media (max-width: 768px) {
    .estadisticas {
        grid-template-columns: 1fr;
    }
    
    .tabs {
        flex-direction: column;
    }
    
    .filtros-form {
        flex-direction: column;
    }
    
    .reporte-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
}
</style>
</head>
<body>
<div class="navbar-admin">
    <div class="d-flex align-items-center">
      <img src="logo.jpg" alt="Logo Conect@T" class="me-3">
      <h3 class="m-0 fw-bold">Conect@T</h3>
    </div>
    <span id="fecha-hora" class="fw-light"></span>
  </div>

<div class="container">
  
    <header>
        <h1>Reportes Generales - Conéctate</h1>
    </header>

    <div class="estadisticas">
        <div class="stat-card clientes">
            <h3> Total Clientes</h3>
            <div class="numero"><?= $estadisticas['total_clientes'] ?></div>
            <div class="monto">Activos: <?= $estadisticas['clientes_activos'] ?></div>
        </div>
        <div class="stat-card contratos">
            <h3> Contratos</h3>
            <div class="numero"><?= $estadisticas['total_contratos'] ?></div>
            <div class="monto">Activos: <?= $estadisticas['contratos_activos'] ?> | Suspendidos: <?= $estadisticas['contratos_suspendidos'] ?></div>
        </div>
        <div class="stat-card pagos">
            <h3> Pagos</h3>
            <div class="numero"><?= $estadisticas['total_pagos_completados'] ?></div>
            <div class="monto">Completados: $<?= number_format($estadisticas['monto_pagos_completados'], 2) ?></div>
        </div>
        <div class="stat-card ingresos">
            <h3> Ingresos del Mes</h3>
            <div class="numero">$<?= number_format($estadisticas['ingresos_mes'], 2) ?></div>
        </div>
    </div>


    <div class="tabs-container">
        <div class="tabs">
            <a href="reportes_view.php?tipo=clientes" class="tab <?= $tipo_reporte == 'clientes' ? 'active' : '' ?>">
                 Clientes
            </a>
            <a href="reportes_view.php?tipo=contratos" class="tab <?= $tipo_reporte == 'contratos' ? 'active' : '' ?>">
                 Contratos
            </a>
            <a href="reportes_view.php?tipo=pagos" class="tab <?= $tipo_reporte == 'pagos' ? 'active' : '' ?>">
                 Pagos
            </a>
        </div>
    </div>


    <div class="filtros-panel">
        <form method="GET" action="reportes_view.php" class="filtros-form">
            <input type="hidden" name="tipo" value="<?= $tipo_reporte ?>">
            
            <div class="filtro-grupo">
                <label> Buscar</label>
                <input type="text" 
                       name="filtro" 
                       placeholder="Nombre, correo, dirección..." 
                       value="<?= htmlspecialchars($filtro) ?>">
            </div>
            
            <?php if ($tipo_reporte == 'pagos'): ?>
            <div class="filtro-grupo">
                <label>Estado del Pago</label>
                <select name="estado">
                    <option value="">Todos</option>
                    <option value="Pagado" <?= $estado == 'Pagado' ? 'selected' : '' ?>>Pagado</option>
                    <option value="Pendiente" <?= $estado == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                </select>
            </div>
            <?php endif; ?>
            
            <button type="submit" class="btn-buscar">Buscar</button>
            <button type="button" onclick="window.location.href='reportes_view.php?tipo=<?= $tipo_reporte ?>'" class="btn-limpiar">Limpiar</button>
        </form>
    </div>

    <div class="reporte-container">
        <div class="reporte-header">
            <h2>
                <?php
                switch($tipo_reporte) {
                    case 'clientes': echo ' Reporte de Clientes'; break;
                    case 'contratos': echo ' Reporte de Contratos'; break;
                    case 'pagos': echo ' Reporte de Pagos'; break;
                }
                ?>
            </h2>
            <a href="generar_pdf.php?tipo=<?= $tipo_reporte ?>&filtro=<?= urlencode($filtro) ?>&estado=<?= urlencode($estado) ?>" 
               class="btn-exportar" 
               target="_blank">
                 Exportar PDF
            </a>
        </div>

        <?php

        switch($tipo_reporte) {
            case 'clientes':
                mostrar_tabla_clientes($resultado);
                break;
            case 'contratos':
                mostrar_tabla_contratos($resultado);
                break;
            case 'pagos':
                mostrar_tabla_pagos($resultado);
                break;
        }
        ?>
    </div>
        <div class="btn-regresar">
    <button onclick="window.location.href='admin.php'">
        Regresar al Panel de Administración
    </button>
</div>

</div>

</body>
</html>