<?php
// controllers/guardar_asistencia.php

include(__DIR__ . '/../auth/validar_sesion.php');
include(__DIR__ . '/../config/db.php');
include(__DIR__ . '/../config/csrf.php');

// ===============================
// 🔒 Seguridad básica
// ===============================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Método no permitido.');
}
if (!csrf_check($_POST['csrf_token'] ?? '')) {
    exit('Token CSRF inválido.');
}
if (!isset($_SESSION['id']) || !in_array(strtolower($_SESSION['rol']), ['encargado','administrador'])) {
    exit('Acceso denegado.');
}

// ===============================
// 📋 Datos del formulario
// ===============================
$empresa   = trim($_POST['empresa']   ?? '');
$fecha     = trim($_POST['fecha']     ?? '');
$producto  = trim($_POST['producto']  ?? '');
$firma64   = $_POST['firma_base64']   ?? '';
$detalleJS = $_POST['detalle_json']   ?? '[]';

if ($empresa === '' || $fecha === '' || $producto === '' || $firma64 === '') {
    exit('Faltan datos o la firma.');
}

// Encargado real
if (strtolower($_SESSION['rol']) === 'administrador') {
    $id_encargado = (int)($_POST['id_encargado'] ?? 0);
    if ($id_encargado <= 0) {
        exit('⚠️ Debe seleccionar un encargado válido de la lista desplegable.');
    }
} else {
    $id_encargado = (int)$_SESSION['id'];
}

// ===============================
// ✍️ Guardar firma en disco
// ===============================
if (strpos($firma64, 'data:image/png;base64,') !== 0) {
    exit('Formato de firma inválido.');
}
[$meta, $data] = explode(',', $firma64, 2);
$firma_bin     = base64_decode($data);

$dir_firmas = __DIR__ . '/../storage/firmas/';
if (!is_dir($dir_firmas)) {
    mkdir($dir_firmas, 0775, true);
}

$nombre_firma = 'firma_' . $id_encargado . '_' . date('Ymd_His') . '.png';
$ruta_firma   = $dir_firmas . $nombre_firma;

if (file_put_contents($ruta_firma, $firma_bin) === false) {
    exit('No se pudo guardar la firma.');
}

// ===============================
// 💾 Insertar parte + detalles (TX)
// ===============================
mysqli_begin_transaction($conexion);

try {
    // ---------- Encabezado del parte ----------
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $stmt = $conexion->prepare("
        INSERT INTO listados_asistencias
            (id_encargado, empresa, fecha, producto, firma_encargado, ip_firma, fecha_firma)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    if (!$stmt) { throw new Exception('Error preparando INSERT del parte.'); }

    $stmt->bind_param("isssss",
        $id_encargado, $empresa, $fecha, $producto, $nombre_firma, $ip
    );
    $stmt->execute();
    if ($stmt->affected_rows !== 1) {
        throw new Exception('No se pudo crear el parte.');
    }
    $id_listado = $conexion->insert_id;
    $stmt->close();

    // ---------- Detalle (asistencias) ----------
    $detalle = json_decode($detalleJS, true);
    if (!is_array($detalle) || !count($detalle)) {
        throw new Exception('El detalle del parte está vacío.');
    }

    // Buscador de id_trabajador por DNI (por si llega sólo dni)
    $stmtFind = $conexion->prepare("SELECT id FROM trabajadores WHERE dni = ? LIMIT 1");
    if (!$stmtFind) { throw new Exception('Error preparando SELECT de trabajador.'); }

    // Insert de cada fila del detalle
    $stmtDet = $conexion->prepare("
        INSERT INTO asistencias
            (id_listado, id_trabajador, asistencia, Bandeja, Horas, Observaciones)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmtDet) { throw new Exception('Error preparando INSERT de asistencias.'); }

    foreach ($detalle as $r) {
        // Acepta id o dni
        $idTrab = (int)($r['id'] ?? 0);
        $dni    = trim($r['dni'] ?? '');

        if ($idTrab <= 0 && $dni !== '') {
            // Resolver id por DNI
            $stmtFind->bind_param("s", $dni);
            if ($stmtFind->execute()) {
                $rs = $stmtFind->get_result();
                if ($rs && $rs->num_rows) {
                    $idTrab = (int)$rs->fetch_assoc()['id'];
                }
            }
        }

        // Si no hay id_trabajador, saltamos la fila
        if ($idTrab <= 0) {
            // Opcional: loguear o lanzar excepción si quieres que sea obligatorio
            continue;
        }

        $asiste = (isset($r['asistencia']) && $r['asistencia'] === 'si') ? 'si' : 'no';
        $ban    = (float)($r['bandejas'] ?? 0);
        $hor    = (float)($r['horas'] ?? 0);
        $obs    = substr((string)($r['observaciones'] ?? ''), 0, 255);

        // i i s d s s  -> id_listado, id_trabajador, asistencia, Bandeja, Horas, Observaciones
        $stmtDet->bind_param("iisdss", $id_listado, $idTrab, $asiste, $ban, $hor, $obs);
        $stmtDet->execute();
        // Si quieres comprobar errores por fila: if ($stmtDet->errno) { ... }
    }

    $stmtDet->close();
    $stmtFind->close();

    mysqli_commit($conexion);

} catch (Throwable $e) {
    mysqli_rollback($conexion);
    // Si prefieres borrar la firma en fallo, descomenta:
    // if (file_exists($ruta_firma)) { unlink($ruta_firma); }
    error_log('❌ guardar_asistencia: ' . $e->getMessage());
    http_response_code(500);
    exit('Error al guardar el parte: ' . $e->getMessage());
}

// ===============================
// ↪️ Redirigir a exportar PDF
// (Si quieres borrar la firma tras generar el PDF, hazlo
//  al final de export/funcion_exportar_pdf.php)
// ===============================
header('Location: ../export/funcion_exportar_pdf.php?id_listado=' . urlencode($id_listado));
exit;
