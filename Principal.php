<?php
session_start();

// Si no está logueado → afuera
if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.html");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conect@T - Paquetes de Internet</title>
    <link rel="stylesheet" href="estilo.css">

    <script>
        function toggleMenu() {
            const menu = document.getElementById('dropdownUser');
            menu.classList.toggle('show');
        }

        // Cerrar si hace clic fuera
        document.addEventListener('click', function(e) {
            const userMenu = document.querySelector('.user-menu');
            const dropdown = document.getElementById('dropdownUser');

            if (!userMenu.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    </script>


    <style>
        .user-box {
            background: #e9f1ff;
            padding: 15px;
            border-radius: 10px;
            width: fit-content;
            margin: 20px auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            font-family: Poppins, sans-serif;
            text-align: center;
        }
        .user-box h3 {
            margin: 0;
        }
        .logout-btn {
            margin-top: 10px;
            background: #d9534f;
            color: white;
            padding: 6px 12px;
            border-radius: 7px;
            cursor: pointer;
        }

        /* Estilo del menú de usuario */
        .user-menu {
            position: relative;
            cursor: pointer;
            user-select: none;
        }

        .user-name {
            font-weight: bold;
            color: #fff;
            padding: 10px;
        }

        /* Oculto por defecto */
        .dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 40px;
            background: #ffffff;
            padding: 15px;
            border-radius: 10px;
            width: 220px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 999;
            color: black;
        }

        .dropdown.show {
            display: block;
        }

        .logout-btn {
            width: 100%;
            padding: 8px;
            background: #d9534f;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .logout-btn:hover {
            background: #c9302c;
        }



    </style>
</head>
<body>
    
<header>
    <h1>Conect@T Internet</h1>

    <nav>
        <ul>
            <li><a href="principal.php">Inicio</a></li>
            <li><a href="Contacto.html">Contacto</a></li>

            <!-- MENÚ DE USUARIO -->
            <li class="user-menu" onclick="toggleMenu()">
                <span class="user-name">👤 <?php echo $_SESSION['nombre']; ?> ▾</span>

                <div id="dropdownUser" class="dropdown">
                    <p><b>Correo:</b> <?php echo $_SESSION['correo']; ?></p>
                    <p><b>Tel:</b> <?php echo $_SESSION['telefono']; ?></p>

                    <form action="logout.php" method="POST">
                        <button class="logout-btn">Cerrar sesión</button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>
</header>


<section class="intro">
    <h2>Nuestros Paquetes</h2>
    <p>Elige el plan que mejor se adapte a tus necesidades. Sin complicaciones, sin límites, solo velocidad y estabilidad.</p>
</section>

<section class="paquetes" id="contenedor-paquetes"></section>

<script>
fetch('traer_paquetes.php')
    .then(res => res.json())
    .then(paquetes => {
        const contenedor = document.getElementById('contenedor-paquetes');
        paquetes.forEach(p => {
            const card = document.createElement('div');
            card.className = 'card';
            card.innerHTML = `
                <h2>${p.nombre}</h2>
                <p class="precio">$${parseFloat(p.precio).toFixed(2)}/mes</p>
                <ul class="beneficios">
                    <li>Velocidad: ${p.velocidad}</li>
                    <li>${p.descripcion.replace(/\\n/g, '<br>')}</li>
                </ul>
                <button class="btn" onclick="window.location.href='contratar.php?id_paquete=${p.id}'">
                    Contratar
                </button>
            `;
            contenedor.appendChild(card);
        });
    })
    .catch(err => console.error('Error cargando paquetes:', err));
</script>

</body>
</html>
