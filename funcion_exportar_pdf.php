<?php
session_start(); // para mostrar nombre del encargado si está logueado
include("conexion_bd.php");

if (!isset($_GET['id_listado']) || empty($_GET['id_listado'])) {
    die("ID de listado no especificado");
}

$id_listado = intval($_GET['id_listado']);

// Obtener el nombre del encargado desde la tabla usuarios
$sql_encargado = "SELECT u.nombre 
                  FROM listados_asistencias la
                  JOIN usuarios u ON la.id_encargado = u.id
                  WHERE la.id = $id_listado
                  LIMIT 1";

$result_encargado = mysqli_query($conexion, $sql_encargado);
if ($result_encargado && mysqli_num_rows($result_encargado) > 0) {
    $row_encargado = mysqli_fetch_assoc($result_encargado);
    $_SESSION['encargado'] = $row_encargado['nombre'];
} else {
    $_SESSION['encargado'] = 'Desconocido';
}

// Consulta de datos
$sql = "SELECT a.id, a.empresa, a.fecha, a.producto, a.asistencia,
               CONCAT(t.nombre, ' ', t.apellidos) AS nombre_trabajador,
               a.dni, a.bandeja, a.horas, a.observaciones
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
        if (file_exists('logo.png')) {
            $this->Image('logo.png', 10, 6, 20);
        }

        $this->SetFont('Arial', 'B', 16);
        $titulo = utf8_decode('Listado de Asistencias');
        $ancho = $this->GetStringWidth($titulo) + 6;
        $this->SetX(($this->w - $ancho) / 2);
        $this->Cell($ancho, 10, $titulo, 0, 1, 'C');

        $this->Ln(4);

        $this->SetFont('Arial', '', 10);
        $encargado = isset($_SESSION['encargado']) ? $_SESSION['encargado'] : 'Desconocido';
        $fecha = date('d/m/Y');
        $textoInfo = utf8_decode("Encargado: $encargado - Generado el $fecha");

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

   function TablaAsistencias($header, $data)
{
    $this->SetFillColor(52, 73, 94);
    $this->SetTextColor(255);
    $this->SetFont('Arial', 'B', 9);

    $w = [20, 20, 25, 18, 30, 22, 20, 15, 55];

    $available = $this->w - $this->lMargin - $this->rMargin;
    $total = array_sum($w);
    if ($total > 0 && $total != $available) {
        $scale = $available / $total;
        foreach ($w as $i => $wi) {
            $w[$i] = $wi * $scale;
        }
    }

    for ($i = 0; $i < count($header); $i++) {
        $this->Cell($w[$i], 7, utf8_decode($header[$i]), 1, 0, 'C', true);
    }
    $this->Ln();

    $this->SetFont('Arial', '', 8);
    $this->SetFillColor(245, 245, 245);
    $this->SetTextColor(0);
    $fill = false;

    foreach ($data as $row) {
    $fields = [
        utf8_decode($row['empresa']),
        utf8_decode($row['fecha']),
        utf8_decode($row['producto']),
        utf8_decode($row['asistencia']),
        utf8_decode($row['nombre_trabajador']),
        utf8_decode($row['dni']),
        utf8_decode($row['bandeja']),
        utf8_decode($row['horas']),
        utf8_decode($row['observaciones']),
    ];

    // 1️⃣ Calculamos la altura máxima de la fila
    $nbLines = [];
    foreach ($fields as $i => $text) {
        $nbLines[] = $this->NbLines($w[$i], $text);
    }
    $maxNbLines = max($nbLines);
    $h = 5 * $maxNbLines;

    // 2️⃣ Guardamos posición inicial de la fila
    $yStart = $this->GetY();

    // 3️⃣ Dibujamos cada celda
    for ($i = 0; $i < count($fields); $i++) {
        $x = $this->GetX();
        $this->MultiCell($w[$i], $h / $nbLines[$i], $fields[$i], 1, 'C', $fill);
        $this->SetXY($x + $w[$i], $yStart);
    }

    // 4️⃣ Avanzamos a la siguiente fila
    $this->Ln($h);
    $fill = !$fill;
}


    $this->Cell(array_sum($w), 0, '', 'T');
    $this->Ln(2);
}


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

$pdf = new PDF();
$pdf->AddPage();

$header = ['Empresa', 'Fecha', 'Producto', 'Asistencia', 'Trabajador', 'DNI', 'Bandejas', 'Horas', 'Observaciones'];

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

$pdf->TablaAsistencias($header, $data);
$pdf->Output('D', 'asistencias_listado_' . $id_listado . '.pdf');
exit();
?>
