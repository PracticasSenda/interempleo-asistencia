<?php
include("conexion_bd.php");

// Verifica si la conexión es válida
if (!$conexion) {
    http_response_code(500);
    echo json_encode(["error" => "Error de conexión con la base de datos"]);
    exit;
}

// Verifica que se ha enviado un ID de listado
if (!isset($_GET['id_listado'])) {
    http_response_code(400);
    echo json_encode(["error" => "ID de listado no proporcionado"]);
    exit;
}

header('Content-Type: application/json');

$id_listado = intval($_GET['id_listado']);

$sql = "SELECT a.*, t.nombre, t.apellidos
        FROM asistencias a
        LEFT JOIN trabajadores t ON a.id_trabajador = t.id
        WHERE a.id_listado = ?";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["error" => "Error al preparar la consulta"]);
    exit;
}

$stmt->bind_param("i", $id_listado);
$stmt->execute();
$result = $stmt->get_result();

$asistencias = [];

while ($row = $result->fetch_assoc()) {
    $asistencias[] = $row;
}

echo json_encode($asistencias);

// Limpieza
$stmt->close();
$conexion->close();
?>

