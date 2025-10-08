<?php
session_start(); // para mostrar nombre del encargado si está logueado
include("conexion_bd.php");

if (!isset($_GET['id_listado']) || empty($_GET['id_listado'])) {
    die("ID de listado no especificado");
}

$id_listado = intval($_GET['id_listado']);

// Consulta de datos
$sql = "SELECT a.id, a.empresa, a.fecha, a.producto, a.asistencia,
               t.nombre AS nombre_trabajador, a.dni, a.bandeja, a.horas, a.observaciones
        FROM asistencias a
        JOIN trabajadores t ON a.id_trabajador = t.id
        WHERE a.id_listado = $id_listado";

$result = mysqli_query($conexion, $sql);
if (!$result) {
    die("Error en la consulta: " . mysqli_error($conexion));
}

require('fpdf/fpdf.php');

class PDF extends FPDF
{
    function Header()
{
    // Logo
    if (file_exists('logo.png')) {
        $this->Image('logo.png', 10, 6, 20);
    }

    // Fuente del título
    $this->SetFont('Arial', 'B', 16);

    // Calcula ancho del texto y posición centrada
    $titulo = utf8_decode('Listado de Asistencias');
    $ancho = $this->GetStringWidth($titulo) + 6;
    $this->SetX(($this->w - $ancho) / 2); // centrado horizontal real
    $this->Cell($ancho, 10, $titulo, 0, 1, 'C');

    $this->Ln(4);

    // Encargado y fecha
    $this->SetFont('Arial', '', 10);
    $encargado = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Desconocido';
    $fecha = date('d/m/Y');
    $textoInfo = utf8_decode("Encargado: $encargado - Generado el $fecha");

    // Centrar también esta línea
    $anchoInfo = $this->GetStringWidth($textoInfo) + 6;
    $this->SetX(($this->w - $anchoInfo) / 2);
    $this->Cell($anchoInfo, 8, $textoInfo, 0, 1, 'C');

    $this->Ln(5);
}


    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo(), 0, 0, 'C');
    }

    // ✅ Tabla profesional con ajuste automático
    function TablaAsistencias($header, $data)
    {
        // Cabecera
        $this->SetFillColor(52, 73, 94);
        $this->SetTextColor(255);
        $this->SetFont('Arial', 'B', 9);

        // Anchos base (proporcionales)
        $w = [20, 20, 25, 18, 30, 22, 20, 15, 55];

        // Escalar automáticamente al ancho imprimible
        $available = $this->w - $this->lMargin - $this->rMargin; // ancho útil (~190mm)
        $total = array_sum($w);
        if ($total > 0 && $total != $available) {
            $scale = $available / $total;
            foreach ($w as $i => $wi) {
                $w[$i] = $wi * $scale;
            }
        }

        // Cabecera de tabla
        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 7, utf8_decode($header[$i]), 1, 0, 'C', true);
        }
        $this->Ln();

        // Cuerpo
        $this->SetFont('Arial', '', 8);
        $this->SetFillColor(245, 245, 245);
        $this->SetTextColor(0);
        $fill = false;

        foreach ($data as $row) {
            $x = $this->GetX();
            $y = $this->GetY();

            // Texto Observaciones y altura dinámica
            $obs = utf8_decode($row['observaciones']);
            $nb  = $this->NbLines($w[8], $obs);
            $h   = 6 * max(1, $nb);

            // Ocho primeras celdas
            $this->Cell($w[0], $h, utf8_decode($row['empresa']),            1, 0, 'C', $fill);
            $this->Cell($w[1], $h, utf8_decode($row['fecha']),              1, 0, 'C', $fill);
            $this->Cell($w[2], $h, utf8_decode($row['producto']),           1, 0, 'C', $fill);
            $this->Cell($w[3], $h, utf8_decode($row['asistencia']),         1, 0, 'C', $fill);
            $this->Cell($w[4], $h, utf8_decode($row['nombre_trabajador']),  1, 0, 'C', $fill);
            $this->Cell($w[5], $h, utf8_decode($row['dni']),                1, 0, 'C', $fill);
            $this->Cell($w[6], $h, utf8_decode($row['bandeja']),            1, 0, 'C', $fill);
            $this->Cell($w[7], $h, utf8_decode($row['horas']),              1, 0, 'C', $fill);

            // Observaciones con salto de línea
            $this->MultiCell($w[8], 6, $obs, 1, 'L', $fill);

            // Avanzar correctamente
            $this->SetXY($x, $y + $h);
            $fill = !$fill;
        }

        // Línea de cierre
        $this->Cell(array_sum($w), 0, '', 'T');
        $this->Ln(2);
    }

    // ✅ Función auxiliar para calcular número de líneas (MultiCell)
    function NbLines($w, $txt)
    {
        $cw = &$this->CurrentFont['cw'];
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ')
                $sep = $i;
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j)
                        $i++;
                } else
                    $i = $sep + 1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else
                $i++;
        }
        return $nl;
    }
}

// Crear el PDF (modo retrato, puedes usar 'L' para horizontal)
$pdf = new PDF();
$pdf->AddPage();

$header = ['Empresa', 'Fecha', 'Producto', 'Asistencia', 'Trabajador', 'DNI', 'Bandeja', 'Horas', 'Observaciones'];

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

$pdf->TablaAsistencias($header, $data);
$pdf->Output('D', 'asistencias_listado_' . $id_listado . '.pdf');
exit();
?>
