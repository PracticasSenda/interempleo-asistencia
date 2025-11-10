<?php
include(__DIR__ . '/../config/db.php');
session_start();

// ✅ Solo los administradores pueden usar este buscador
if (!isset($_SESSION['rol']) || strtolower($_SESSION['rol']) !== 'administrador') {
    http_response_code(403);
    echo json_encode(["error" => "Acceso denegado"]);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// ✅ Obtener el texto de búsqueda
$q = trim($_GET['q'] ?? '');
if ($q === '') {
    echo json_encode([]);
    exit;
}

// ✅ Buscar encargados por nombre, apellidos o DNI
$stmt = $conexion->prepare("
    SELECT id, nombre, apellidos, dni
    FROM usuarios
    WHERE rol = 'encargado'
      AND (nombre LIKE CONCAT('%', ?, '%') OR apellidos LIKE CONCAT('%', ?, '%') OR dni LIKE CONCAT('%', ?, '%'))
    ORDER BY nombre ASC
    LIMIT 10
");

$stmt->bind_param("sss", $q, $q, $q);
$stmt->execute();
$result = $stmt->get_result();

$sugerencias = [];
while ($row = $result->fetch_assoc()) {
    $sugerencias[] = [
        'id' => $row['id'],
        'nombre' => $row['nombre'] . ' ' . $row['apellidos'],
        'dni' => $row['dni']
    ];
}

echo json_encode($sugerencias);
