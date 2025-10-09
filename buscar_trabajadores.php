<?php
include("conexion_bd.php");

$termino = isset($_GET['q']) ? mysqli_real_escape_string($conexion, $_GET['q']) : '';

if (strlen($termino) < 2) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT nombre, dni FROM trabajadores WHERE activo = 1 AND (nombre LIKE '%$termino%' OR dni LIKE '%$termino%') LIMIT 10";
$result = mysqli_query($conexion, $sql);

$sugerencias = [];
while ($row = mysqli_fetch_assoc($result)) {
    $sugerencias[] = [
        "label" => $row['nombre'] . " (" . $row['dni'] . ")",
        "nombre" => $row['nombre'],
        "dni" => $row['dni']
    ];
}

header('Content-Type: application/json');
echo json_encode($sugerencias);
