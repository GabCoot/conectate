<?php
include("conexion.php");

$id = $_GET['id'] ?? 0;

$stmt = $conexion->prepare("
SELECT c.id, u.nombre AS cliente, u.correo, u.telefono,
       p.nombre AS paquete, p.velocidad, c.precio_mensual, c.fecha_inicio, c.fecha_fin, c.estado
FROM contratos c
JOIN usuarios u ON c.usuario_id = u.id
JOIN paquetes p ON c.paquete_id = p.id
WHERE c.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$contrato = $result->fetch_assoc();

if(!$contrato){
    die("Contrato no encontrado");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contrato — Conect@T</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<style>
body { background: #f7f9fb; font-family: "Poppins", sans-serif; padding: 40px; }
#contrato { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width:600px; margin:auto; }
#logo { display:block; margin:0 auto 10px; max-width:100px; }
</style>
</head>
<body>

<div id="contrato">
    <img id="logo" src="logo.jpg" alt="Logo Conect@T">
    <h2 class="text-center mb-3">Contrato de Servicio — Conect@T</h2>
    <p><strong>Cliente:</strong> <?= htmlspecialchars($contrato['cliente']) ?></p>
    <p><strong>Correo:</strong> <?= htmlspecialchars($contrato['correo']) ?></p>
    <p><strong>Teléfono:</strong> <?= $contrato['telefono'] ?? '-' ?></p>
    <p><strong>Paquete:</strong> <?= $contrato['paquete'].' — '.$contrato['velocidad'] ?></p>
    <p><strong>Precio Mensual:</strong> $<?= number_format($contrato['precio_mensual'],2) ?></p>
    <p><strong>Fecha Inicio:</strong> <?= $contrato['fecha_inicio'] ?></p>
    <p><strong>Fecha Fin:</strong> <?= $contrato['fecha_fin'] ?? '-' ?></p>
    <p><strong>Estado:</strong> <?= $contrato['estado'] ?></p>
    <hr>
    <p>Este contrato certifica que el cliente ha contratado el paquete mencionado y se compromete a cumplir con los pagos correspondientes en tiempo y forma. Conect@T garantiza la prestación del servicio según lo pactado.</p>
    <div class="text-center mt-3">
        <button id="descargarPDF" class="btn btn-primary"><i class="bi bi-file-earmark-pdf"></i> Descargar PDF</button>
    </div>
</div>

<script>
document.getElementById('descargarPDF').addEventListener('click', () => {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();

    let y = 20; // posición vertical inicial

    // --- Agregar logo ---
    const img = document.getElementById('logo');
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    canvas.width = img.naturalWidth;
    canvas.height = img.naturalHeight;
    ctx.drawImage(img, 0, 0);
    const imgData = canvas.toDataURL('image/jpeg');
    doc.addImage(imgData, 'JPEG', 75, y, 60, 60); // centrado
    y += 70;

    // --- Título ---
    doc.setFontSize(18);
    doc.text("Contrato de Servicio — Conect@T", 105, y, {align: "center"});
    y += 15;

    // --- Datos ---
    doc.setFontSize(12);
    const lineHeight = 8;

    const data = [
        ["Cliente:", "<?= addslashes($contrato['cliente']) ?>"],
        ["Correo:", "<?= addslashes($contrato['correo']) ?>"],
        ["Teléfono:", "<?= addslashes($contrato['telefono'] ?? '-') ?>"],
        ["Paquete:", "<?= addslashes($contrato['paquete'].' — '.$contrato['velocidad']) ?>"],
        ["Precio Mensual:", "$<?= number_format($contrato['precio_mensual'],2) ?>"],
        ["Fecha Inicio:", "<?= $contrato['fecha_inicio'] ?>"],
        ["Fecha Fin:", "<?= $contrato['fecha_fin'] ?? '-' ?>"],
        ["Estado:", "<?= $contrato['estado'] ?>"]
    ];

    data.forEach(item => {
        doc.text(item[0], 20, y);
        doc.text(item[1], 70, y);
        y += lineHeight;
    });

    y += 10;
    const contratoText = "Este contrato certifica que el cliente ha contratado el paquete mencionado y se compromete a cumplir con los pagos correspondientes en tiempo y forma. Conect@T garantiza la prestación del servicio según lo pactado.";
    const splitText = doc.splitTextToSize(contratoText, 170);
    doc.text(splitText, 20, y);

    doc.save("Contrato_<?= $contrato['id'] ?>.pdf");
});
</script>

</body>
</html>
<?php $conexion->close(); ?>
