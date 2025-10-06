<?php
include("conexion_bd.php");

$term = isset($_GET['term']) ? mysqli_real_escape_string($conexion, $_GET['term']) : '';

$sql = "SELECT id, empresa, producto, fecha FROM listados_asistencias 
        WHERE empresa LIKE '%$term%' 
           OR producto LIKE '%$term%' 
           OR fecha LIKE '%$term%'
        ORDER BY fecha DESC
        LIMIT 10";

$result = mysqli_query($conexion, $sql);
$listados = [];

while ($row = mysqli_fetch_assoc($result)) {
    $listados[] = $row;
}

echo json_encode($listados);

mysqli_close($conexion);
?>
