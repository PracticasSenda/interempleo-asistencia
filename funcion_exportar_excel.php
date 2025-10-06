<?php
include("conexion_bd.php");

if (!isset($_GET['id_listado']) || empty($_GET['id_listado'])) {
    die("ID de listado no especificado");
}

$id_listado = intval($_GET['id_listado']);

// Consulta para obtener datos de asistencias según el listado
$sql = "SELECT a.id, a.empresa, a.fecha, a.producto, a.asistencia, t.nombre AS nombre_trabajador, a.dni, a.bandeja, a.horas, a.observaciones
        FROM asistencias a
        JOIN trabajadores t ON a.id_trabajador = t.id
        WHERE a.id_listado = $id_listado";

$result = mysqli_query($conexion, $sql);

if (!$result) {
    die("Error en la consulta: " . mysqli_error($conexion));
}

// Encabezados para descarga CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=asistencias_listado_' . $id_listado . '.csv');

$output = fopen('php://output', 'w');

// Cabecera CSV
fputcsv($output, ['ID', 'Empresa', 'Fecha', 'Producto', 'Asistencia', 'Nombre Trabajador', 'DNI', 'Bandeja', 'Horas', 'Observaciones']);

// Datos
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['id'],
        $row['empresa'],
        $row['fecha'],
        $row['producto'],
        $row['asistencia'],
        $row['nombre_trabajador'],
        $row['dni'],
        $row['bandeja'],
        $row['horas'],
        $row['observaciones']
    ]);
}

fclose($output);
mysqli_close($conexion);
exit;
?>
