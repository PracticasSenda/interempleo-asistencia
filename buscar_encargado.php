<?php
include("conexion_bd.php");

$term = $_GET['term'] ?? '';
$term = mysqli_real_escape_string($conexion, $term);

// Solo muestra encargados (opcional: puedes filtrar por rol si tienes columna 'rol')
$sql = "SELECT nombre FROM usuarios WHERE nombre LIKE '%$term%' LIMIT 10";
$result = mysqli_query($conexion, $sql);

$sugerencias = [];

while ($row = mysqli_fetch_assoc($result)) {
    $sugerencias[] = $row['nombre'];
}

echo json_encode($sugerencias);
?>
