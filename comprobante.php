<?php
require_once "dompdf/autoload.inc.php";

use Dompdf\Dompdf;
use Dompdf\Options;

include "pago.php";

if (!isset($_GET['referencia'])) {
    die("No se recibió la referencia.");
}

$referencia = $conexion->real_escape_string($_GET['referencia']);
$pago = buscar_pago_por_referencia($conexion, $referencia);

if (!$pago) {
    die("Pago no encontrado.");
}

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);

$logo = __DIR__ . "/Logo.jpg";
$logo_base64 = base64_encode(file_get_contents($logo));
$logo_src = "data:image/jpeg;base64,$logo_base64";

$html = '
<style>
body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 12px;
    position: relative;
}

.logo-top {
    position: absolute;
    top: 10px;
    right: 10px;
}

h1 {
    text-align: center;
    margin-top: 15px;
    margin-bottom: 15px;
}

.info-compacta {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 10px;
}

.info-compacta td {
    padding: 4px 6px;
    vertical-align: top;
    font-size: 12px;
}

.info-compacta strong {
    font-weight: bold;
}

.detalles-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}
.detalles-table th, .detalles-table td {
    padding: 6px;
    border: 1px solid #333;
    font-size: 11px;
}

.total {
    margin-top: 12px;
    font-size: 14px;
    font-weight: bold;
}
</style>

<div class="logo-top">
    <img src="'.$logo_src.'" width="140">
</div>

<h1>Comprobante de Pago</h1>

<table class="info-compacta">
    <tr>
        <td><strong>Referencia:</strong> '.$pago["referencia"].'</td>
        <td><strong>Cliente:</strong> '.$pago["cliente"].'</td>
    </tr>
    <tr>
        <td><strong>Correo:</strong> '.$pago["correo"].'</td>
        <td><strong>Fecha de Pago:</strong> '.($pago["fecha_pago"] ? date("d/m/Y", strtotime($pago["fecha_pago"])) : "Pendiente").'</td>
    </tr>
    <tr>
        <td><strong>Método:</strong> '.$pago["metodo_pago"].'</td>
        <td><strong>Contrato:</strong> '.$pago["contrato_id"].'</td>
    </tr>
    <tr>
        <td><strong>Paquete:</strong> '.$pago["paquete"].'</td>
        <td></td>
    </tr>
</table>

<h3>Pagos Mensuales Incluidos</h3>

<table class="detalles-table">
<thead>
<tr>
    <th>#</th>
    <th>Fecha Esperada</th>
    <th>Monto</th>
    <th>Estado</th>
</tr>
</thead>
<tbody>';

foreach ($pago["detalles"] as $d) {
    $html .= "
    <tr>
        <td>{$d['numero_pago']}</td>
        <td>".date("d/m/Y", strtotime($d['fecha_pago_esperada']))."</td>
        <td>$".number_format($d['monto'],2)."</td>
        <td>{$d['estado']}</td>
    </tr>";
}

$html .= '
</tbody>
</table>

<p class="total">TOTAL PAGADO: $'.number_format($pago["total_pago"], 2).'</p>

<hr>
<p></p>
';

$dompdf->loadHtml($html);
$dompdf->setPaper("A4", "landscape"); //portrait o landscape ok
$dompdf->render();

$pdf_name = "Comprobante_$referencia.pdf";
$dompdf->stream($pdf_name, ["Attachment" => true]);
?>
