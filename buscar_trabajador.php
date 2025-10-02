<?php
header('Content-Type: application/json');
include("validar_sesion.php");
include("conexion_bd.php");

if (!isset($_GET['dni']) || empty(trim($_GET['dni']))) {
    echo json_encode(['error' => 'No se proporcionó DNI']);
    exit;
}

$dni = mysqli_real_escape_string($conexion, strip_tags($_GET['dni']));

$sql = "SELECT nombre FROM trabajadores WHERE dni = '$dni' LIMIT 1";
$res = mysqli_query($conexion, $sql);

if (!$res) {
    echo json_encode(['error' => 'Error en la consulta SQL']);
    exit;
}

if (mysqli_num_rows($res) === 0) {
    echo json_encode(['error' => 'No existe trabajador con ese DNI']);
    exit;
}

$fila = mysqli_fetch_assoc($res);
echo json_encode(['nombre' => $fila['nombre']]);
exit;
?>