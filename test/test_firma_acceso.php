<?php
// ===============================================
// 🧩 Test de acceso interno a las firmas
// ===============================================

// Ruta de prueba (ajusta si quieres probar otra)
$firma_path = __DIR__ . '/../img/firmas/';
$firma_file = glob($firma_path . 'firma_*.png');

// Si no hay firmas, avisar
if (empty($firma_file)) {
    exit("⚠️ No hay archivos de firma en: $firma_path");
}

// Tomamos la primera firma encontrada
$firma_file = $firma_file[0];

// Verificar acceso interno con PHP
echo "<h3>🔍 Comprobando acceso interno a la firma:</h3>";
echo "<p>Archivo: <code>" . basename($firma_file) . "</code></p>";

if (file_exists($firma_file)) {
    $info = getimagesize($firma_file);
    echo "<p>✅ Acceso interno permitido por PHP</p>";
    echo "<p><strong>Tipo:</strong> {$info['mime']} | <strong>Tamaño:</strong> {$info[0]}x{$info[1]}</p>";
} else {
    exit("<p>❌ No se pudo acceder al archivo (bloqueado o no existe)</p>");
}

// Intentar mostrarlo con FPDF (si FPDF está instalado)
require_once __DIR__ . '/../fpdf/fpdf.php';

class PDF_Test extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 14);
        $this->Cell(0, 10, utf8_decode('Test de Acceso Interno a la Firma'), 0, 1, 'C');
        $this->Ln(10);
    }
}

$pdf = new PDF_Test();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, utf8_decode('Si ves la firma abajo, el acceso interno está correctamente configurado.'), 0, 1, 'C');
$pdf->Ln(20);

// Mostrar la firma en el PDF
$pdf->Image($firma_file, 85, $pdf->GetY(), 40);
$pdf->Ln(50);
$pdf->Cell(0, 10, utf8_decode('Firma cargada correctamente desde PHP ✅'), 0, 1, 'C');

// Guardar PDF en temporal
$pdf_path = __DIR__ . '/test_firma_resultado.pdf';
$pdf->Output('F', $pdf_path);

echo "<p>✅ El PDF se generó correctamente:</p>";
echo "<a href='test_firma_resultado.pdf' target='_blank'>📄 Abrir PDF generado</a>";
?>
