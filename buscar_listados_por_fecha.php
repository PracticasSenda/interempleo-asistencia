<?php
include("db.php");

if (!isset($_GET['fecha']) || empty($_GET['fecha'])) {
    echo json_encode([]);
    exit;
}

$fecha = mysqli_real_escape_string($conexion, $_GET['fecha']);

$sql = "
    SELECT DISTINCT 
        a.id_listado AS id, 
        a.empresa, 
        a.producto, 
        a.fecha,
        CONCAT(u.nombre, ' ', u.apellidos) AS encargado
    FROM asistencias a
    INNER JOIN listados_asistencias la ON a.id_listado = la.id
    INNER JOIN usuarios u ON la.id_encargado = u.id
    WHERE a.fecha = '$fecha'
";

$result = mysqli_query($conexion, $sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en la consulta SQL: ' . mysqli_error($conexion)]);
    exit;
}

$listados = [];
while ($row = mysqli_fetch_assoc($result)) {
    $listados[] = $row;
}

header('Content-Type: application/json');
echo json_encode($listados);
exit;
