<?php
include("conexion_bd.php");

if (!isset($_GET['fecha']) || empty($_GET['fecha'])) {
    echo json_encode([]);
    exit;
}

$fecha = mysqli_real_escape_string($conexion, $_GET['fecha']);

// Aquí ajusta tu consulta para sacar los listados únicos de esa fecha
$sql = "SELECT DISTINCT id_listado AS id, empresa, producto, fecha 
        FROM asistencias 
        WHERE fecha = '$fecha'";

$result = mysqli_query($conexion, $sql);

$listados = [];
while ($row = mysqli_fetch_assoc($result)) {
    $listados[] = $row;
}

header('Content-Type: application/json');
echo json_encode($listados);
exit;


