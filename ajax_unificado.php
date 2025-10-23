<?php
header('Content-Type: application/json');
include("conexion_bd.php");
include("validar_sesion.php"); // Solo para los que lo necesitan

$accion = $_GET['accion'] ?? '';

switch($accion) {

    case 'buscar_trabajador':
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

    case 'buscar_sugerencias':
        $term = $_GET['term'] ?? '';
        $term = mysqli_real_escape_string($conexion, $term);
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
        exit;

    case 'buscar_listados_por_fecha':
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
        echo json_encode($listados);
        exit;

    case 'buscar_asistencias_por_listado':
        if (!isset($_GET['id_listado'])) {
            http_response_code(400);
            echo json_encode(["error" => "ID de listado no proporcionado"]);
            exit;
        }
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
            $asistencias[] = [
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'apellidos' => $row['apellidos'],
                'dni' => $row['dni'],
                'asistencia' => $row['asistencia'],
                'empresa' => $row['empresa'],
                'fecha' => $row['fecha'],
                'producto' => $row['producto'],
                'Bandeja' => $row['Bandeja'],
                'Horas' => $row['Horas'],
                'Observaciones' => $row['Observaciones']
            ];
        }
        echo json_encode($asistencias);
        $stmt->close();
        $conexion->close();
        exit;

    case 'buscar_encargado':
        $term = $_GET['term'] ?? '';
        $term = mysqli_real_escape_string($conexion, $term);
        $sql = "SELECT nombre FROM usuarios WHERE nombre LIKE '%$term%' LIMIT 10";
        $result = mysqli_query($conexion, $sql);
        $sugerencias = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $sugerencias[] = $row['nombre'];
        }
        echo json_encode($sugerencias);
        exit;

    default:
        echo json_encode(['error' => 'Acción no válida']);
        exit;
}
