<?php
require_once __DIR__ . '/../config/db.php';
require __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// ===============================================
// 🔸 Validar ID del listado recibido
// ===============================================
if (!isset($_POST['id_listado'])) {
    die('No se recibió el parámetro id_listado.');
}
$id_listado = intval($_POST['id_listado']);

// ===============================================
// 🔹 Consultar encabezado del listado
// ===============================================
$sql_info = "
    SELECT l.empresa, l.producto, l.fecha, u.nombre AS encargado, u.apellidos
    FROM listados_asistencias l
    JOIN usuarios u ON l.id_encargado = u.id
    WHERE l.id = '$id_listado'
    LIMIT 1
";
$res_info = mysqli_query($conexion, $sql_info);
if (!$res_info || mysqli_num_rows($res_info) === 0) {
    die('No se encontró el listado.');
}
$info = mysqli_fetch_assoc($res_info);

// ===============================================
// 🔹 Consultar datos de asistencia
// ===============================================
$sql = "
    SELECT t.nombre, t.apellidos, t.dni, a.asistencia, a.Bandeja, a.Horas, a.Observaciones
    FROM asistencias a
    JOIN trabajadores t ON a.id_trabajador = t.id
    WHERE a.id_listado = '$id_listado'
    ORDER BY t.apellidos ASC
";
$res = mysqli_query($conexion, $sql);

// ===============================================
// 🔸 Crear archivo Excel
// ===============================================
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Parte Asistencia');

// Encabezado principal
$sheet->setCellValue('A1', 'PARTE DE ASISTENCIA - INTEREMPLEO');
$sheet->mergeCells('A1:G1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setRGB('FF671D');
$sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

// Datos del parte
$sheet->setCellValue('A3', 'Empresa:')->setCellValue('B3', $info['empresa']);
$sheet->setCellValue('A4', 'Producto:')->setCellValue('B4', $info['producto']);
$sheet->setCellValue('A5', 'Fecha:')->setCellValue('B5', $info['fecha']);
$sheet->setCellValue('A6', 'Encargado:')->setCellValue('B6', $info['encargado'] . ' ' . $info['apellidos']);

// Encabezados de tabla
$headers = ['Nombre', 'Apellidos', 'DNI', 'Asistencia', 'Bandejas', 'Horas', 'Observaciones'];
$col = 'A';
$row = 8;
foreach ($headers as $header) {
    $sheet->setCellValue($col . $row, $header);
    $sheet->getStyle($col . $row)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
    $sheet->getStyle($col . $row)->getFill()->setFillType('solid')->getStartColor()->setRGB('FF671D');
    $sheet->getStyle($col . $row)->getAlignment()->setHorizontal('center');
    $col++;
}

// Variables de totales
$total = 0;
$presentes = 0;
$ausentes = 0;
$total_bandejas = 0;
$total_horas = 0;

// Datos de asistencia
$row = 9;
while ($fila = mysqli_fetch_assoc($res)) {
    $sheet->setCellValue('A' . $row, $fila['nombre']);
    $sheet->setCellValue('B' . $row, $fila['apellidos']);
    $sheet->setCellValue('C' . $row, $fila['dni']);
    $sheet->setCellValue('D' . $row, $fila['asistencia'] === 'si' ? 'PRESENTE' : 'AUSENTE');
    $sheet->setCellValue('E' . $row, $fila['Bandeja']);
    $sheet->setCellValue('F' . $row, $fila['Horas']);
    $sheet->setCellValue('G' . $row, $fila['Observaciones']);

    // Calcular totales
    $total++;
    if ($fila['asistencia'] === 'si') $presentes++;
    else $ausentes++;
    $total_bandejas += floatval($fila['Bandeja']);
    $total_horas += floatval($fila['Horas']);

    $row++;
}

// Línea de resumen
$row += 2;
$sheet->getStyle("A$row")->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('FF671D');
$sheet->setCellValue("A$row", "Resumen del Parte:");
$row++;

$sheet->setCellValue("A$row", "Total de trabajadores:")->setCellValue("B$row", $total); $row++;
$sheet->setCellValue("A$row", "Presentes:")->setCellValue("B$row", $presentes); $row++;
$sheet->setCellValue("A$row", "Ausentes:")->setCellValue("B$row", $ausentes); $row++;
$sheet->setCellValue("A$row", "Total Bandejas:")->setCellValue("B$row", $total_bandejas); $row++;
$sheet->setCellValue("A$row", "Total Horas:")->setCellValue("B$row", $total_horas);

// Autoajuste de columnas
foreach (range('A', 'G') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Descargar
$filename = 'parte_asistencia_' . date('Y-m-d') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
