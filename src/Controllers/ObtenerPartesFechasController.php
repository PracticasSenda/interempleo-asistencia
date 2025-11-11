<?php
// /controllers/obtener_partes_fechas.php
include(__DIR__ . '/../db.php');
header('Content-Type: application/json; charset=utf-8');

try {
    // Consulta optimizada: cuántos partes existen por día
    $query = "SELECT fecha, COUNT(*) AS total FROM listados_asistencias GROUP BY fecha";
    $result = $conexion->query($query);

    $fechas = [];
    while ($row = $result->fetch_assoc()) {
        $fechas[$row['fecha']] = (int)$row['total'];
    }

    echo json_encode($fechas);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
