<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../fpdf/fpdf.php'; // ✅ versión clásica de FPDF

// ===============================================
// 🔹 Validar parámetro
// ===============================================
if (!isset($_POST['id_listado'])) {
    die('No se recibió el parámetro id_listado.');
}
$id_listado = intval($_POST['id_listado']);

// ===============================================
// 🔹 Obtener información del parte
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
// 🔹 Consultar asistencias
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
// 🔸 Clase personalizada para encabezado/pie
// ===============================================
class PDF_Interempleo extends FPDF {
function Header() {
    // 🔹 Logo real de Interempleo
    $this->Image(__DIR__ . '/../img/logo_interempleo.jpg', 10, 8, 25); // (ruta relativa + ancho 25mm)
    $this->SetFont('Arial', 'B', 14);
    $this->SetTextColor(255, 103, 29);
    $this->Cell(0, 10, utf8_decode("INTEREMPLEO - PARTE DE ASISTENCIA"), 0, 1, 'C');
    $this->Ln(15); // separa el logo del texto
}



    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(130, 130, 130);
        $this->Cell(0, 10, utf8_decode('Documento generado automáticamente por Interempleo · ' . date('d/m/Y')), 0, 0, 'C');
    }
}

// ===============================================
// 🔸 Crear PDF (orientación horizontal)
// ===============================================
$pdf = new PDF_Interempleo('L', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(0, 0, 0);

// ===============================================
// 🔹 Información general del parte
// ===============================================
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(30, 8, utf8_decode('Empresa:'), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(60, 8, utf8_decode($info['empresa']), 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(25, 8, utf8_decode('Fecha:'), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(30, 8, utf8_decode($info['fecha']), 0, 1);

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(30, 8, utf8_decode('Producto:'), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(60, 8, utf8_decode($info['producto']), 0, 0);
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(25, 8, utf8_decode('Encargado:'), 0, 0);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(60, 8, utf8_decode($info['encargado'] . ' ' . $info['apellidos']), 0, 1);
$pdf->Ln(6);

// ===============================================
// 🔹 Cabecera de la tabla
// ===============================================
$pdf->SetFillColor(255, 103, 29);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 10);

$headers = ['Nombre', 'Apellidos', 'DNI', 'Asistencia', 'Bandejas', 'Horas', 'Observaciones'];
$widths  = [38, 40, 28, 28, 25, 25, 55]; // ajustado a formato horizontal

foreach ($headers as $i => $col) {
    $pdf->Cell($widths[$i], 8, utf8_decode($col), 1, 0, 'C', true);
}
$pdf->Ln();

// ===============================================
// 🔹 Filas de asistencias
// ===============================================
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(0, 0, 0);

$total = $presentes = $ausentes = 0;
$total_bandejas = $total_horas = 0;

while ($fila = mysqli_fetch_assoc($res)) {
    $pdf->Cell($widths[0], 7, utf8_decode($fila['nombre']), 1);
    $pdf->Cell($widths[1], 7, utf8_decode($fila['apellidos']), 1);
    $pdf->Cell($widths[2], 7, utf8_decode($fila['dni']), 1);
    $pdf->Cell($widths[3], 7, $fila['asistencia'] === 'si' ? 'Presente' : 'Ausente', 1);
    $pdf->Cell($widths[4], 7, $fila['Bandeja'], 1, 0, 'C');
    $pdf->Cell($widths[5], 7, $fila['Horas'], 1, 0, 'C');
    $pdf->Cell($widths[6], 7, utf8_decode($fila['Observaciones']), 1);
    $pdf->Ln();

    // Calcular totales
    $total++;
    if ($fila['asistencia'] === 'si') $presentes++;
    else $ausentes++;
    $total_bandejas += floatval($fila['Bandeja']);
    $total_horas += floatval($fila['Horas']);
}

// ===============================================
// 🔹 Resumen final
// ===============================================
$pdf->Ln(6);
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(255, 103, 29);
$pdf->Cell(0, 8, utf8_decode('Resumen del Parte'), 0, 1, 'L');
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(60, 8, utf8_decode('Total de trabajadores:'), 0, 0);
$pdf->Cell(20, 8, $total, 0, 1);
$pdf->Cell(60, 8, utf8_decode('Presentes:'), 0, 0);
$pdf->Cell(20, 8, $presentes, 0, 1);
$pdf->Cell(60, 8, utf8_decode('Ausentes:'), 0, 0);
$pdf->Cell(20, 8, $ausentes, 0, 1);
$pdf->Cell(60, 8, utf8_decode('Total Bandejas:'), 0, 0);
$pdf->Cell(20, 8, $total_bandejas, 0, 1);
$pdf->Cell(60, 8, utf8_decode('Total Horas:'), 0, 0);
$pdf->Cell(20, 8, $total_horas, 0, 1);

// ===============================================
// 🔹 Salida final
// ===============================================
$filename = 'parte_asistencia_' . date('Y-m-d') . '.pdf';
$pdf->Output('D', $filename);
exit;
