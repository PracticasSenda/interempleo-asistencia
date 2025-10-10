<?php
header("Content-Type: application/json");
include("conexion_bd.php");

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (strlen($query) < 2) {
  echo json_encode([]);
  exit;
}

$query_escapado = mysqli_real_escape_string($conexion, $query);

$sql = "SELECT dni, nombre FROM trabajadores 
        WHERE nombre LIKE '%$query_escapado%' AND activo = 1
        ORDER BY nombre ASC
        LIMIT 10";

$resultado = mysqli_query($conexion, $sql);

$sugerencias = [];

if ($resultado) {
  while ($fila = mysqli_fetch_assoc($resultado)) {
    $sugerencias[] = [
      'dni' => $fila['dni'],
      'nombre' => $fila['nombre']
    ];
  }
}

echo json_encode($sugerencias);
mysqli_close($conexion);
