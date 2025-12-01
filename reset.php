<?php
include __DIR__ . '/conexion.php';
header('Content-Type: text/html; charset=utf-8');

$token = $_GET['token'] ?? '';
if (!$token) {
    die("Token inválido");
}

// Verificar token y expiración
$stmt = $conn->prepare("SELECT Id, reset_expires FROM usuarios WHERE reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->bind_result($id, $expires);

if (!$stmt->fetch()) {
    die("Token inválido");
}

if (strtotime($expires) < time()) {
    die("El token ha expirado");
}

$stmt->close();


$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva_password = $_POST['password'] ?? '';
    if (!$nueva_password) {
        $error = "Ingresa una contraseña válida";
    } else {

        $update = $conn->prepare("UPDATE usuarios SET password = ?, reset_token = NULL, reset_expires = NULL WHERE Id = ?");
        $update->bind_param("si", $nueva_password, $id);
        $update->execute();
        $update->close();
        echo "<h2>✅ Contraseña actualizada correctamente</h2>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Actualizar contraseña</title>
<style>
body { font-family: Arial, sans-serif; background: #f4f4f4; text-align:center; padding:50px;}
form { background: #fff; padding:20px; display:inline-block; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1);}
input { padding:10px; width: 250px; margin-bottom:10px; border-radius:5px; border:1px solid #ccc;}
button { padding:10px 20px; border:none; border-radius:5px; background:#28a745; color:#fff; cursor:pointer;}
button:hover { background:#218838;}
p.error { color:red; }
</style>
</head>
<body>
<h2>Actualizar contraseña</h2>
<?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
<form method="POST">
    <input type="password" name="password" placeholder="Nueva contraseña" required><br>
    <button type="submit">Actualizar contraseña</button>
</form>
</body>
</html>
