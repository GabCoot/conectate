<?php
include("conexion.php");

$conn = $conexion;

$total_clientes_query = mysqli_query($conn, "SELECT COUNT(*) AS total FROM usuarios");
$total_clientes = mysqli_fetch_assoc($total_clientes_query)['total'];

// Obtener clientes activos
$clientes_activos_query = mysqli_query($conn, "SELECT COUNT(*) AS activos FROM contratos WHERE estado = 'activo'");
$clientes_activos = mysqli_fetch_assoc($clientes_activos_query)['activos'];

// Obtener contratos activos
$contratos_query = mysqli_query($conn, "SELECT COUNT(*) AS activos FROM contratos WHERE estado = 'activo'");
$contratos_activos = mysqli_fetch_assoc($contratos_query)['activos'];

// Obtener últimos usuarios registrados
$usuarios_query = mysqli_query($conn, "SELECT nombre, correo, activo FROM usuarios ORDER BY id DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Administración — Conect@T</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <style>
    body {
      background: #f3f6fa;
      font-family: "Poppins", sans-serif;
    }

    .navbar-admin {
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

    .sidebar-float {
      position: fixed;
      top: 100px;
      left: 25px;
      background: white;
      border-radius: 15px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
      padding: 20px;
      width: 180px;
      display: flex;
      flex-direction: column;
      gap: 10px;
      z-index: 100;
    }

    .sidebar-float a {
      text-decoration: none;
      color: #0d6efd;
      font-weight: 500;
      padding: 8px 12px;
      border-radius: 10px;
      transition: all 0.2s ease-in-out;
      display: flex;
      align-items: center;
    }

    .sidebar-float a i {
      margin-right: 8px;
    }

    .sidebar-float a:hover {
      background: #0d6efd;
      color: white;
      transform: translateX(4px);
    }

    .main-container {
      margin-left: 240px;
      padding: 30px;
    }

    .stats-card {
      border-radius: 12px;
      background: white;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      text-align: center;
      padding: 20px;
      transition: all 0.2s ease-in-out;
    }

    .stats-card:hover {
      transform: translateY(-5px);
    }

    .stats-card h2 {
      font-weight: 700;
      margin: 10px 0;
    }

    .card-admin {
      max-width: 1100px;
      margin: 40px auto;
      border: none;
      border-radius: 1rem;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body>

  <!-- Navbar superior -->
  <div class="navbar-admin">
    <div class="d-flex align-items-center">
      <img src="logo.jpg" alt="Logo Conect@T" class="me-3">
      <h3 class="m-0 fw-bold">Panel de Administración — Conect@T</h3>
    </div>
    <span id="fecha-hora" class="fw-light"></span>
  </div>

  <!-- Sidebar -->
  <div class="sidebar-float">
    <a href="ver_clientes.php"><i class="bi bi-people"></i> Clientes</a>
    <a href="contratos.php"><i class="bi bi-file-earmark-text"></i> Contratos</a>
    <a href="pago.php"><i class="bi bi-cash-coin"></i>Cobros</a>
    <a href="paquetes.php"><i class="bi bi-menu-button-wide"></i> Paquetes</a>
    <hr>
    <a href="index.html" class="text-danger"><i class="bi bi-box-arrow-right"></i> Salir</a>
  </div>

  <!-- Contenido principal -->
  <div class="main-container">
    <div class="container">
      <div class="row text-center mb-4">
        <div class="col-md-4">
          <div class="stats-card">
            <i class="bi bi-people text-primary" style="font-size: 2rem;"></i>
            <h6 class="text-muted">Clientes Activos</h6>
            <h2 class="text-success"><?= $clientes_activos ?></h2>
          </div>
        </div>
        <div class="col-md-4">
          <div class="stats-card">
            <i class="bi bi-file-earmark-text text-success" style="font-size: 2rem;"></i>
            <h6 class="text-muted">Contratos Activos</h6>
            <h2 class="text-primary"><?= $contratos_activos ?></h2>
          </div>
        </div>
        <div class="col-md-4">
          <div class="stats-card">
            <i class="bi bi-bar-chart text-warning" style="font-size: 2rem;"></i>
            <h6 class="text-muted">Total Usuarios</h6>
            <h2 class="text-dark"><?= $total_clientes ?></h2>
          </div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="card card-admin bg-white p-4">
        <h4 class="fw-bold mb-3 text-primary"><i class="bi bi-clock-history"></i> Últimos Usuarios Registrados</h4>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-primary">
              <tr>
                
                <th>Nombre</th>
                <th>Correo</th>
                <th>Estado</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody>
              <?php while($row = mysqli_fetch_assoc($usuarios_query)): ?>
                <tr>
            
                  <td><?= htmlspecialchars($row['nombre']) ?></td>
                  <td><?= htmlspecialchars($row['correo']) ?></td>
                  <td>
                    <?php
                      if ($row['activo'] == '1') {
                        echo '<span class="badge bg-success">Activo</span>';
                      } elseif ($row['activo'] == '0') {
                        echo '<span class="badge bg-warning text-dark">Pendiente</span>';
                      } else {
                        echo '<span class="badge bg-danger">Inactivo</span>';
                      }
                    ?>
                  </td>
                  <td>
                    <a href="editar_contrato.php" class="btn btn-outline-primary btn-sm">
                      <i class="bi bi-pencil-square"></i>
                    </a>
                  </td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    function actualizarFechaHora() {
      const ahora = new Date();
      const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
      const fecha = ahora.toLocaleDateString('es-ES', opciones);
      const hora = ahora.toLocaleTimeString('es-ES');
      document.getElementById('fecha-hora').textContent = `${fecha} — ${hora}`;
    }
    setInterval(actualizarFechaHora, 1000);
    actualizarFechaHora();
  </script>
</body>
</html>
