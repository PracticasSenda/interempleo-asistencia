<?php
include("conexion_bd.php");

if (!isset($_GET['term'])) {
    echo json_encode([]);
    exit;
}

$term = mysqli_real_escape_string($conexion, $_GET['term']);

$sql = "SELECT dni, nombre FROM trabajadores 
        WHERE dni LIKE '$term%' OR nombre LIKE '%$term%' 
        LIMIT 10";
$res = mysqli_query($conexion, $sql);

$sugerencias = [];

while ($fila = mysqli_fetch_assoc($res)) {
    $sugerencias[] = [
        'dni' => $fila['dni'],
        'nombre' => $fila['nombre']
    ];
}

echo json_encode($sugerencias);
?>
