<?php
include __DIR__ . '/conexion.php';

$token = $_GET['token'] ?? '';

if(!$token) {
    die("<h2>❌ Token inválido o faltante</h2>");
    exit;
}

// Buscar usuario con ese token y activo=0
$stmt = $conn->prepare("SELECT id, nombre, codigo_verificacion FROM usuarios WHERE reset_token = ? AND activo = 0");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if($row = $result->fetch_assoc()) {
    $idUsuario = $row['id'];
    $nombre    = htmlspecialchars($row['nombre']);
    $codigoBD  = $row['codigo_verificacion'];

    // Si el usuario ya envió el código
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $codigoIngresado = $_POST['codigo'] ?? '';

        if ($codigoIngresado === $codigoBD) {
            // Activar usuario
            $update = $conn->prepare("UPDATE usuarios SET activo = 1, reset_token = NULL, codigo_verificacion = NULL WHERE id = ?");
            $update->bind_param("i", $idUsuario);
            if($update->execute()) {
                echo "<h2>✅ ¡Hola $nombre! Tu cuenta ha sido activada correctamente</h2>";
            } else {
                echo "<h2>❌ Error al activar cuenta</h2>";
            }
            exit;
        } else {
            $error = "Código incorrecto, intenta de nuevo.";
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Verificación de cuenta</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #71b7e6, #9b59b6);
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .container {
                background: #fff;
                padding: 30px 40px;
                border-radius: 15px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                text-align: center;
                width: 300px;
            }
            input {
                width: 100%;
                padding: 10px;
                margin: 10px 0;
                border: 1px solid #ccc;
                border-radius: 5px;
                text-align: center;
                font-size: 18px;
                letter-spacing: 5px;
            }
            button {
                width: 100%;
                padding: 12px;
                background: #28a745;
                color: #fff;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-size: 16px;
            }
            button:hover { background: #218838; }
            .error { color: red; margin-top: 10px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Hola <?= $nombre ?> 👋</h2>
            <p>Ingresa tu código de verificación:</p>
            <form method="POST">
                <input type="text" name="codigo" maxlength="4" required>
                <button type="submit">Verificar</button>
            </form>
            <?php if(isset($error)) { echo "<p class='error'>$error</p>"; } ?>
        </div>
    </body>
    </html>
    <?php
} else {
    die("<h2>❌ Token inválido o cuenta ya activada</h2>");
}

$stmt->close();
$conn->close();
?>
