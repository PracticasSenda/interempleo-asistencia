<?php
// export/funcion_exportar_pdf.php
// Muestra el PDF en la pestaña nueva (inline). Requiere FPDF.

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../fpdf/fpdf.php';

// Asegurar charset de la conexión
if (isset($conexion) && $conexion instanceof mysqli) {
    mysqli_set_charset($conexion, 'utf8mb4');
}

// ---- Seguridad mínima de sesión ----
if (!isset($_SESSION['id'])) {
    header('Content-Type: text/plain; charset=utf-8');
    exit('Acceso no autorizado.');
}

// ---- 1) Recoger id_listado (POST > GET > sesión) ----
$id_listado = 0;
if (isset($_POST['id_listado'])) {
    $id_listado = (int) $_POST['id_listado'];
} elseif (isset($_GET['id_listado'])) {
    $id_listado = (int) $_GET['id_listado'];
} elseif (isset($_SESSION['ultimo_id_listado'])) {
    $id_listado = (int) $_SESSION['ultimo_id_listado'];
}

if ($id_listado <= 0) {
    header('Content-Type: text/plain; charset=utf-8');
    exit('No se recibió el parámetro id_listado.');
}
$_SESSION['ultimo_id_listado'] = $id_listado; // respaldo útil

// ---- 2) Consultas preparadas ----

// Info del parte
$stmt_info = $conexion->prepare("
    SELECT l.empresa, l.producto, l.fecha, l.firma_encargado, l.fecha_firma, l.ip_firma,
           u.nombre AS encargado, u.apellidos, u.rol
    FROM listados_asistencias l
    JOIN usuarios u ON l.id_encargado = u.id
    WHERE l.id = ?
    LIMIT 1
");
$stmt_info->bind_param("i", $id_listado);
$stmt_info->execute();
$res_info = $stmt_info->get_result();
if (!$res_info || $res_info->num_rows === 0) {
    header('Content-Type: text/plain; charset=utf-8');
    exit('No se encontró el listado.');
}
$info = $res_info->fetch_assoc();
$stmt_info->close();

// Asistencias (coalesce para evitar nulls y asegurar decimales)
$stmt_asist = $conexion->prepare("
    SELECT 
        t.nombre,
        t.apellidos,
        t.dni,
        a.asistencia,
        COALESCE(a.Bandeja, 0)         AS bandejas,
        COALESCE(a.Horas,   0)         AS horas,
        COALESCE(a.Observaciones, '')  AS observaciones
    FROM asistencias a
    JOIN trabajadores t ON t.id = a.id_trabajador
    WHERE a.id_listado = ?
    ORDER BY t.apellidos ASC, t.nombre ASC
");
$stmt_asist->bind_param('i', $id_listado);
$stmt_asist->execute();
$asist_res = $stmt_asist->get_result();



// ---- 3) Clase PDF personalizada ----
class PDF_Interempleo extends FPDF
{
    function Header()
    {
        $logoPath = __DIR__ . '/../img/logo_interempleo.jpg';
        if (is_file($logoPath)) {
            $this->Image($logoPath, 10, 8, 25);
        }
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(255, 103, 29);
        $this->Cell(0, 10, utf8_decode('PARTE DE ASISTENCIA'), 0, 1, 'C');

        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 6, utf8_decode('Interempleo · Gestión de Asistencia'), 0, 1, 'C');
        $this->Ln(10);
    }
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(130, 130, 130);
        $this->Cell(0, 10, utf8_decode('Documento generado automáticamente · ' . date('d/m/Y')), 0, 0, 'C');
    }
}

// ---- 4) Construcción del PDF ----
$pdf = new PDF_Interempleo('L', 'mm', 'A4');
$pdf->AddPage();

// Info cabecera
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

$pdf->Ln(4);

// Cabecera de tabla
$pdf->SetFillColor(255, 103, 29);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 10);
$headers = ['Nombre', 'Apellidos', 'DNI', 'Asistencia', 'Bandejas', 'Horas', 'Observaciones'];
$widths  = [38,       40,          28,    28,           25,       25,      55];

foreach ($headers as $i => $col) {
    $pdf->Cell($widths[$i], 8, utf8_decode($col), 1, 0, 'C', true);
}
$pdf->Ln();

// Filas
$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(0, 0, 0);

if ($asist_res && $asist_res->num_rows > 0) {
    while ($fila = $asist_res->fetch_assoc()) {
        $pdf->Cell($widths[0], 7, utf8_decode($fila['nombre']), 1);
        $pdf->Cell($widths[1], 7, utf8_decode($fila['apellidos']), 1);
        $pdf->Cell($widths[2], 7, $fila['dni'], 1);
        $pdf->Cell($widths[3], 7, ($fila['asistencia'] === 'si' ? 'Presente' : 'Ausente'), 1);
        $ban = (int)$fila['bandejas'];
        $hor = is_numeric($fila['horas']) ? rtrim(rtrim(number_format((float)$fila['horas'], 2, '.', ''), '0'), '.') : '0';
        $obs = (string)$fila['observaciones'];

        $pdf->Cell($widths[4], 7, (string)$ban, 1, 0, 'C');
        $pdf->Cell($widths[5], 7, (string)$hor, 1, 0, 'C');

        $pdf->Cell($widths[6], 7, utf8_decode($obs), 1);
        $pdf->Ln();
    }
} else {
    $pdf->Cell(array_sum($widths), 7, utf8_decode('Sin registros'), 1, 1, 'C');
}

// Firma (si existe archivo)
if (!empty($info['firma_encargado'])) {
    $firma_path = __DIR__ . '/../storage/firmas/' . $info['firma_encargado'];
    if (is_file($firma_path)) {
        $pdf->Ln(12);
        $y = $pdf->GetY();
        // Imagen de firma
        $pdf->Image($firma_path, 130, $y, 40);
        // Línea y texto
        $pdf->Ln(30);
        $pdf->Line(120, $y + 32, 170, $y + 32);
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 8, utf8_decode('Firma del encargado'), 0, 1, 'C');
    }
}

// ---- 5) Entregar el PDF al navegador (inline) ----
$nombre = 'parte_' . $id_listado . '.pdf';
$pdf->Output('I', $nombre);   // I = inline (muestra en la pestaña nueva)
exit;
