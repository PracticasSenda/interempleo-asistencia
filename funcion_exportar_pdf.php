<?php
include("conexion_bd.php");

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

// Encabezados para descarga CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=asistencias_listado_' . $id_listado . '.csv');

$output = fopen('php://output', 'w');

// Cabecera CSV
fputcsv($output, ['ID', 'Empresa', 'Fecha', 'Producto', 'Asistencia', 'Nombre Trabajador', 'DNI', 'Bandeja', 'Horas', 'Observaciones']);

// Datos
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['id'],
        $row['empresa'],
        $row['fecha'],
        $row['producto'],
        $row['asistencia'],
        $row['nombre_trabajador'],
        $row['dni'],
        $row['bandeja'],
        $row['horas'],
        $row['observaciones']
    ]);
}

fclose($output);
mysqli_close($conexion);
exit;
?>





<?php
require('fpdf186/fpdf.php'); // asegúrate de tener la carpeta fpdf o cambia la ruta según corresponda
include("conexion_bd.php");

// Recibir parámetros
$id_listado = $_GET['id_listado'] ?? '';
$fecha = $_GET['fecha'] ?? '';

// Consultar los datos del listado
$query = "SELECT t.nombre, t.apellidos, a.hora_entrada, a.hora_salida, a.empresa, a.producto 
          FROM asistencias a
          INNER JOIN trabajadores t ON a.id_trabajador = t.id
          WHERE a.id_listado = '$id_listado'";
$result = mysqli_query($conexion, $query);

// Crear PDF limpio
class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',14);
        $this->Cell(0,10,'Listado de Asistencia',0,1,'C');
        $this->Ln(5);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Pagina '.$this->PageNo().'/{nb}',0,0,'C');
    }
}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',11);

// Información general
$pdf->Cell(0,10,"Fecha del listado: $fecha",0,1,'L');
$pdf->Ln(4);

// Encabezado de tabla
$pdf->SetFont('Arial','B',11);
$pdf->SetFillColor(240,240,240);
$pdf->Cell(40,10,'Nombre',1,0,'C',true);
$pdf->Cell(40,10,'Apellidos',1,0,'C',true);
$pdf->Cell(30,10,'Entrada',1,0,'C',true);
$pdf->Cell(30,10,'Salida',1,0,'C',true);
$pdf->Cell(25,10,'Empresa',1,0,'C',true);
$pdf->Cell(25,10,'Producto',1,1,'C',true);

// Filas de datos
$pdf->SetFont('Arial','',10);
if ($result && mysqli_num_rows($result) > 0) {
    while ($fila = mysqli_fetch_assoc($result)) {
        $pdf->Cell(40,8,$fila['nombre'],1);
        $pdf->Cell(40,8,$fila['apellidos'],1);
        $pdf->Cell(30,8,$fila['hora_entrada'],1);
        $pdf->Cell(30,8,$fila['hora_salida'],1);
        $pdf->Cell(25,8,$fila['empresa'],1);
        $pdf->Cell(25,8,$fila['producto'],1);
        $pdf->Ln();
    }
} else {
    $pdf->Cell(0,10,'No hay registros disponibles.',1,1,'C');
}

// Salida del PDF
$pdf->Output('I', "listado_$id_listado.pdf");
?>
