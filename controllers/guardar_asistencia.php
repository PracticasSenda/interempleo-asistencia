<?php
include(__DIR__ . '/../auth/validar_sesion.php');
include(__DIR__ . '/../config/db.php');
include(__DIR__ . '/../config/csrf.php');

// ==================================================
// 🔒 Seguridad básica
// ==================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Método no permitido.');
}
if (!csrf_check($_POST['csrf_token'] ?? '')) {
    exit('Token CSRF inválido.');
}
if (!isset($_SESSION['id']) || !in_array(strtolower($_SESSION['rol']), ['encargado', 'administrador'])) {
    exit('Acceso denegado.');
}

// ==================================================
// 📋 Datos del formulario
// ==================================================
$empresa  = trim($_POST['empresa'] ?? '');
$fecha    = trim($_POST['fecha'] ?? '');
$producto = trim($_POST['producto'] ?? '');
$firma_base64 = $_POST['firma_base64'] ?? '';
$nombre_encargado = trim($_POST['nombre_encargado'] ?? '');
// ✅ Determinar el encargado real
$id_encargado = 0;

if (strtolower($_SESSION['rol']) === 'administrador') {
    $id_encargado = intval($_POST['id_encargado'] ?? 0);
    if ($id_encargado <= 0) {
        exit('⚠️ Debe seleccionar un encargado válido de la lista desplegable.');
    }
} else {
    // Si es encargado normal, usar su propio ID de sesión
    $id_encargado = intval($_SESSION['id']);
}

if (empty($empresa) || empty($fecha) || empty($producto) || empty($firma_base64)) {
    exit('Faltan datos o la firma.');
}

// ==================================================
// 📁 Carpeta de firmas (segura)
// ==================================================
$dir_firmas = __DIR__ . '/../storage/firmas/';
if (!is_dir($dir_firmas)) mkdir($dir_firmas, 0775, true);

// ==================================================
// 🖋️ Procesar firma (solo guardar internamente)
// ==================================================
if (strpos($firma_base64, 'data:image/png;base64,') !== 0) {
    exit('Formato de firma inválido.');
}

try {
    [$meta, $data] = explode(',', $firma_base64);
    $firma_bin = base64_decode($data);
    $nombre_firma = 'firma_' . $id_encargado . '_' . date('Ymd_His') . '.png';
    $ruta_firma = $dir_firmas . $nombre_firma;

    if (file_put_contents($ruta_firma, $firma_bin) === false) {
        throw new Exception('No se pudo guardar la firma.');
    }
} catch (Exception $e) {
    exit('Error al procesar la firma: ' . $e->getMessage());
}

// ==================================================
// 💾 Guardar en base de datos (solo nombre del archivo)
// ==================================================
$ip_firma = $_SERVER['REMOTE_ADDR'];

$stmt = $conexion->prepare("
    INSERT INTO listados_asistencias (id_encargado, empresa, fecha, producto, firma_encargado, ip_firma)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("isssss", $id_encargado, $empresa, $fecha, $producto, $nombre_firma, $ip_firma);

if (!$stmt->execute()) {
    error_log('❌ Error SQL en guardar_asistencia.php: ' . $stmt->error);
    exit('Ocurrió un error inesperado al guardar el parte. Intente nuevamente.');
}


$id_parte = $stmt->insert_id;
$stmt->close();

// ✅ Eliminar la firma después de generar el PDF
register_shutdown_function(function() use ($ruta_firma) {
    if (file_exists($ruta_firma)) {
        unlink($ruta_firma);
    }
});

// ==================================================
// 🔁 Redirigir a generar el PDF profesional
// ==================================================
header("Location: ../export/funcion_exportar_pdf.php?id_listado=" . urlencode($id_parte));
exit;
?>
