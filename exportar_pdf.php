<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;
include('conexion_bd.php');

// Configurar Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Consulta
$query = "SELECT * FROM asistencias";
$resultado = $conexion->query($query);

// Generar HTML
$html = '<h2 style="text-align:center;">Listado de Asistencias</h2>
<table border="1" cellspacing="0" cellpadding="6" width="100%">
<thead style="background-color:#007BFF;color:white;">
<tr>
  <th>ID</th>
  <th>Encargado</th>
  <th>Trabajador</th>
  <th>Fecha</th>
</tr>
</thead>
<tbody>';

while ($fila = $resultado->fetch_assoc()) {
    $html .= "<tr>
        <td>{$fila['id']}</td>
        <td>{$fila['encargado']}</td>
        <td>{$fila['trabajador']}</td>
        <td>{$fila['fecha']}</td>
    </tr>";
}

$html .= '</tbody></table>';

// Generar PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Descargar PDF
$dompdf->stream("asistencias.pdf", ["Attachment" => true]);
exit;
?>
