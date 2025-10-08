<?php
require 'vendor/autoload.php'; // Carga PhpSpreadsheet
include("conexion_bd.php");

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Validar ID del listado
if (!isset($_GET['id_listado']) || empty($_GET['id_listado'])) {
    die("ID de listado no especificado");
}

$id_listado = intval($_GET['id_listado']);

// Consulta para obtener datos de asistencias según el listado
$sql = "SELECT a.id, a.empresa, a.fecha, a.producto, a.asistencia, t.nombre AS nombre_trabajador, a.dni, a.bandeja, a.horas, a.observaciones
        FROM asistencias a
        JOIN trabajadores t ON a.id_trabajador = t.id
        WHERE a.id_listado = $id_listado";

$result = mysqli_query($conexion, $sql);

if (!$result) {
    die("Error en la consulta: " . mysqli_error($conexion));
}

// Crear nuevo documento Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Listado de Asistencia');

// Cabeceras
$headers = ['ID', 'Empresa', 'Fecha', 'Producto', 'Asistencia', 'Nombre Trabajador', 'DNI', 'Bandejas', 'Horas', 'Observaciones'];
$sheet->fromArray($headers, NULL, 'A1');

// Estilo para la cabecera
$styleHeader = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF']
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '4F81BD']
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
        ],
    ],
];
$sheet->getStyle('A1:J1')->applyFromArray($styleHeader);

// Insertar datos fila por fila
$rowIndex = 2;
while ($row = mysqli_fetch_assoc($result)) {
    $sheet->fromArray(array_values($row), NULL, 'A' . $rowIndex);
    $rowIndex++;
}

// Autoajustar columnas
foreach (range('A', 'J') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Configurar encabezados para descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="asistencias_listado_' . $id_listado . '.xlsx"');
header('Cache-Control: max-age=0');

// Guardar y enviar archivo Excel
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

mysqli_close($conexion);
exit;
