<?php
include(__DIR__ . '/../auth/validar_sesion.php');
include(__DIR__ . '/../config/db.php');
include(__DIR__ . '/../config/csrf.php');
require(__DIR__ . '/../fpdf/fpdf.php'); // Asegúrate de tener FPDF instalado

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Método no permitido.');
}
if (!csrf_check($_POST['csrf_token'] ?? '')) {
    exit('Token CSRF inválido.');
}
if (!isset($_SESSION['id']) || $_SESSION['rol'] !== 'encargado') {
    exit('Acceso denegado.');
}

// ================================
// 📥 Datos del formulario
// ================================
$empresa  = trim($_POST['empresa'] ?? '');
$fecha    = trim($_POST['fecha'] ?? '');
$producto = trim($_POST['producto'] ?? '');
$firma_base64 = $_POST['firma_base64'] ?? '';

if (empty($empresa) || empty($fecha) || empty($producto) || empty($firma_base64)) {
    exit('Faltan datos o la firma.');
}

// ================================
// 🖋️ Guardar la firma como imagen
// ================================
if (strpos($firma_base64, 'data:image/png;base64,') !== 0) {
    exit('Formato de firma inválido.');
}

[$meta, $data] = explode(',', $firma_base64);
$firma_bin = base64_decode($data);
$dir_firmas = __DIR__ . '/../img/firmas/';
if (!is_dir($dir_firmas)) mkdir($dir_firmas, 0775, true);

$nombre_firma = 'firma_' . $_SESSION['id'] . '_' . date('Ymd_His') . '.png';
$ruta_abs = $dir_firmas . $nombre_firma;
$ruta_rel = 'img/firmas/' . $nombre_firma;
file_put_contents($ruta_abs, $firma_bin);

// ================================
// 💾 Guardar parte en la base de datos
// ================================
$stmt = $conexion->prepare("
    INSERT INTO listados_asistencias (id_encargado, empresa, fecha, producto, firma_encargado)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param("issss", $_SESSION['id'], $empresa, $fecha, $producto, $ruta_rel);
$stmt->execute();
$id_parte = $stmt->insert_id;
$stmt->close();

// ================================
// 📄 Generar el PDF del parte
// ================================
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,utf8_decode("Parte de Asistencia"),0,1,'C');
$pdf->Ln(10);

$pdf->SetFont('Arial','',12);
$pdf->Cell(0,8,"Encargado: " . utf8_decode($_SESSION['nombre']),0,1);
$pdf->Cell(0,8,"Empresa: " . utf8_decode($empresa),0,1);
$pdf->Cell(0,8,"Fecha: " . $fecha,0,1);
$pdf->Cell(0,8,"Producto: " . utf8_decode($producto),0,1);
$pdf->Ln(10);
$pdf->Cell(0,8,"Parte emitido y firmado digitalmente.",0,1);

// Firma
if (file_exists($ruta_abs)) {
    $pdf->Ln(20);
    $pdf->Image($ruta_abs, 80, $pdf->GetY(), 50);
    $pdf->Ln(30);
    $pdf->Cell(0,8,"Firma del encargado",0,1,'C');
}

// Guardar el PDF en carpeta
$dir_pdfs = __DIR__ . '/../export/pdf_partes/';
if (!is_dir($dir_pdfs)) mkdir($dir_pdfs, 0775, true);
$nombre_pdf = 'parte_' . $id_parte . '_' . date('Ymd_His') . '.pdf';
$pdf->Output('F', $dir_pdfs . $nombre_pdf);

// Redirigir o descargar
header('Location: ../export/pdf_partes/' . $nombre_pdf);
exit;
?>
