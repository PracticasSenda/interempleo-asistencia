<?php
ob_start();
error_reporting(E_ERROR | E_PARSE);
header('Content-Type: text/plain; charset=utf-8');

include(__DIR__ . '/../config/db.php');
include(__DIR__ . '/../funciones/funciones.php');
mysqli_set_charset($conexion, "utf8mb4");

$action = $_GET['action'] ?? $_POST['action'] ?? '';

/* ============================================================
   BUSCAR ENCARGADO (tabla usuarios)
============================================================ */
if ($action === 'buscar_encargado') {
    $q = mysqli_real_escape_string($conexion, mb_strtoupper($_GET['q'] ?? '', 'UTF-8'));

    $sql = "SELECT id, nombre, apellidos, dni 
            FROM usuarios 
            WHERE rol = 'encargado' 
              AND (
                UPPER(nombre) LIKE '%$q%' OR
                UPPER(apellidos) LIKE '%$q%' OR
                UPPER(CONCAT(nombre,' ',apellidos)) LIKE '%$q%' OR
                UPPER(dni) LIKE '%$q%'
              )
            ORDER BY nombre ASC 
            LIMIT 10";
    $res = mysqli_query($conexion, $sql);

    if (!$res || mysqli_num_rows($res) === 0) {
        echo "<div class='sugerencia-item sin-resultados'>No se encontraron coincidencias</div>";
        exit;
    }

    while ($fila = mysqli_fetch_assoc($res)) {
        $id     = (int)$fila['id'];
        $nombre = htmlspecialchars($fila['nombre'] . ' ' . $fila['apellidos'], ENT_QUOTES, 'UTF-8');
        $dni    = htmlspecialchars($fila['dni'], ENT_QUOTES, 'UTF-8');

        echo "<div class='sugerencia-item' data-id='{$id}' data-nombre='{$nombre}' data-dni='{$dni}'>"
           . "{$nombre} <small style='color:#666'>({$dni})</small>"
           . "</div>";
    }
    exit;
}

/* ============================================================
   BUSCADOR DE TRABAJADORES (para sugerencias flotantes)
============================================================ */
if ($action === 'buscar_trabajadores') {
    $q = mysqli_real_escape_string($conexion, mb_strtoupper($_GET['q'] ?? '', 'UTF-8'));

    $sql = "SELECT id, nombre, apellidos, dni 
            FROM trabajadores 
            WHERE activo = 1 
              AND (
                UPPER(nombre) LIKE '%$q%' OR
                UPPER(apellidos) LIKE '%$q%' OR
                UPPER(CONCAT(nombre,' ',apellidos)) LIKE '%$q%' OR
                UPPER(dni) LIKE '%$q%'
              )
            ORDER BY nombre ASC 
            LIMIT 10";

    $res = mysqli_query($conexion, $sql);

    if (!$res || mysqli_num_rows($res) === 0) {
        echo "<div class='sugerencia-item sin-resultados'>No se encontraron coincidencias</div>";
        exit;
    }

    while ($fila = mysqli_fetch_assoc($res)) {
        $id   = (int)$fila['id'];
        $nomC = htmlspecialchars($fila['nombre'] . ' ' . $fila['apellidos'], ENT_QUOTES, 'UTF-8');
        $dni  = htmlspecialchars($fila['dni'], ENT_QUOTES, 'UTF-8');

        echo "<div class='sugerencia-item' data-id='{$id}' data-nombre='{$nomC}' data-dni='{$dni}'>
                {$nomC} <small style='color:#666'>({$dni})</small>
              </div>";
    }
    exit;
}

/* ============================================================
   CARGAR DETALLE DE TRABAJADOR
============================================================ */
if ($action === 'detalle_trabajador') {
    $dni   = mysqli_real_escape_string($conexion, $_GET['dni'] ?? '');
    $fecha = mysqli_real_escape_string($conexion, $_GET['fecha'] ?? '');

    $sql = "SELECT Bandeja, Horas, Observaciones 
            FROM asistencias 
            WHERE dni='$dni' AND fecha='$fecha' 
            LIMIT 1";
    $res  = mysqli_query($conexion, $sql);
    $data = ['Bandeja' => '', 'Horas' => '', 'Observaciones' => ''];

    if ($res && mysqli_num_rows($res) > 0) {
        $data = mysqli_fetch_assoc($res);
    }

    echo '
<div class="detalle-grid">
  <label>Bandejas
    <input type="number" name="Bandeja_' . $dni . '" placeholder="0" min="0" step="1" value="' . htmlspecialchars((string)$data['Bandeja']) . '">
  </label>
  <label>Horas
    <input type="number" name="Horas_' . $dni . '" placeholder="0" min="0" step="0.5" value="' . htmlspecialchars((string)$data['Horas']) . '">
  </label>
  <label class="observacion-full">Observaciones
    <input type="text" name="Observaciones_' . $dni . '" placeholder="Escribe aquí..." value="' . htmlspecialchars((string)$data['Observaciones']) . '">
  </label>
</div>
<button type="button" class="btn-guardar-detalle">Guardar</button>
';
    exit;
}

/* ============================================================
   GUARDAR DETALLE DE TRABAJADOR  (MODIFICADO)
============================================================ */
if ($action === 'guardar_detalle') {
    $dni        = mysqli_real_escape_string($conexion, $_POST['dni'] ?? '');
    $empresa    = mysqli_real_escape_string($conexion, $_POST['empresa'] ?? '');
    $fecha      = mysqli_real_escape_string($conexion, $_POST['fecha'] ?? '');
    $producto   = mysqli_real_escape_string($conexion, $_POST['producto'] ?? '');
    $asistencia = ($_POST['asistencia'] ?? 'no') === 'si' ? 'si' : 'no';
    $bandeja    = (string) max(0, (int)($_POST['Bandeja'] ?? 0));
$horas = (float) max(0, (float)($_POST['Horas'] ?? 0));
    $obs        = mysqli_real_escape_string($conexion, substr(trim($_POST['Observaciones'] ?? ''), 0, 255));

    if ($dni === '' || $empresa === '' || $producto === '' || $fecha === '') {
        http_response_code(400);
        exit('FALTAN_CAMPOS');
    }

    if ($asistencia !== 'si') { $bandeja = 0; $horas = 0; }

    // id_trabajador
    $resTrab = mysqli_query($conexion, "SELECT id FROM trabajadores WHERE dni='$dni' LIMIT 1");
    if (!$resTrab || mysqli_num_rows($resTrab) === 0) {
        http_response_code(404);
        exit('No se encontró el trabajador');
    }
    $id_trabajador = (int) mysqli_fetch_assoc($resTrab)['id'];

    // Buscar listado coincidente por empresa/fecha/producto
    $id_listado = null;
    $resListado = mysqli_query(
        $conexion,
        "SELECT id FROM listados_asistencias 
         WHERE empresa='$empresa' AND fecha='$fecha' AND producto='$producto'
         ORDER BY id DESC LIMIT 1"
    );
    if ($resListado && mysqli_num_rows($resListado) > 0) {
        $id_listado = (int) mysqli_fetch_assoc($resListado)['id'];
    }

    if ($id_listado) {
        // ¿Ya hay asistencia para ese listado y trabajador?
        $check = mysqli_query(
            $conexion,
            "SELECT id FROM asistencias 
             WHERE id_listado='$id_listado' AND id_trabajador='$id_trabajador' LIMIT 1"
        );
        if ($check && mysqli_num_rows($check) > 0) {
            $id_asistencia = (int) mysqli_fetch_assoc($check)['id'];
            $sql = "
                UPDATE asistencias SET
                    empresa='$empresa',
                    fecha='$fecha',
                    producto='$producto',
                    asistencia='$asistencia',
                    Bandeja='$bandeja',
                    Horas='$horas',
                    Observaciones='$obs'
                WHERE id='$id_asistencia'
            ";
        } else {
            $sql = "
                INSERT INTO asistencias
                    (id_listado, empresa, fecha, producto, asistencia, id_trabajador, dni, Bandeja, Horas, Observaciones)
                VALUES
                    ('$id_listado', '$empresa', '$fecha', '$producto', '$asistencia', '$id_trabajador', '$dni', '$bandeja', '$horas', '$obs')
            ";
        }
    } else {
        // Sin listado coincidente: insert suelto pero con empresa/fecha/producto correctos
        $sql = "
            INSERT INTO asistencias
                (id_trabajador, dni, empresa, fecha, producto, asistencia, Bandeja, Horas, Observaciones)
            VALUES
                ('$id_trabajador', '$dni', '$empresa', '$fecha', '$producto', '$asistencia', '$bandeja', '$horas', '$obs')
        ";
    }

    if (mysqli_query($conexion, $sql)) {
        echo "OK";
    } else {
        echo "ERROR_SQL: " . mysqli_error($conexion);
    }
    exit;
}

/* ===========================================================
   GUARDAR PARTE COMPLETO (con firma + resumen)
============================================================ */
if ($action === 'guardar_parte_completo') {
    $encargado_nombre = mysqli_real_escape_string($conexion, $_POST['encargado'] ?? '');
    $empresa  = mysqli_real_escape_string($conexion, $_POST['empresa']  ?? '');
    $fecha    = mysqli_real_escape_string($conexion, $_POST['fecha']    ?? '');
    $producto = mysqli_real_escape_string($conexion, $_POST['producto'] ?? '');
    $trabajadores = json_decode($_POST['trabajadores'] ?? '[]', true);
    $firma_base64 = $_POST['firma_base64'] ?? '';

    if (!$encargado_nombre || !$empresa || !$fecha || !$producto || empty($trabajadores)) {
        ob_clean();
        http_response_code(400);
        exit('Datos incompletos.');
    }

    // ID del encargado (tu consulta original)
    $resEncargado = mysqli_query($conexion, "SELECT id FROM usuarios 
        WHERE CONCAT(nombre, ' ', apellidos) LIKE '%$encargado_nombre%' 
          AND rol='encargado' LIMIT 1");
    if (!$resEncargado || mysqli_num_rows($resEncargado) === 0) {
        ob_clean();
        http_response_code(404);
        exit('No se encontró el encargado especificado.');
    }
    $id_encargado = (int) mysqli_fetch_assoc($resEncargado)['id'];

    // Crear/recuperar el listado (tu lógica original)
    $checkParte = mysqli_query($conexion, "
        SELECT id FROM listados_asistencias 
        WHERE id_encargado='$id_encargado' AND fecha='$fecha' AND empresa='$empresa' AND producto='$producto'
        LIMIT 1
    ");
    if ($checkParte && mysqli_num_rows($checkParte) > 0) {
        $id_listado = (int) mysqli_fetch_assoc($checkParte)['id'];
    } else {
        $sqlListado = "INSERT INTO listados_asistencias (id_encargado, empresa, fecha, producto, encargado_nombre)
                       VALUES ('$id_encargado', '$empresa', '$fecha', '$producto', '".mysqli_real_escape_string($conexion,$encargado_nombre)."')";
        if (!mysqli_query($conexion, $sqlListado)) {
            ob_clean();
            http_response_code(500);
            exit('Error al crear el listado: ' . mysqli_error($conexion));
        }
        $id_listado = mysqli_insert_id($conexion);
    }

    /* ---------- 2.1 Guardar/actualizar asistencias (tu bucle con minimos ajustes) ---------- */
    $total_trabajadores = 0;
    $total_presentes    = 0;
    $total_ausentes     = 0;
    $total_bandejas     = 0;
    $total_horas        = 0.0;

    foreach ($trabajadores as $t) {
        $dni  = mysqli_real_escape_string($conexion, $t['dni']);
        $asis = ($t['asistencia'] ?? 'no') === 'si' ? 'si' : 'no';

        $bRaw = $t['bandeja'] ?? 0;
        $hRaw = $t['horas']   ?? 0;

        $bandeja = $asis === 'si' ? (int)   max(0, (int)$bRaw)   : 0;
        $horas   = $asis === 'si' ? (float) max(0, (float)$hRaw) : 0.0;

        $observaciones = mysqli_real_escape_string($conexion, substr(trim($t['observaciones'] ?? ''), 0, 255));

        $resTrab = mysqli_query($conexion, "SELECT id FROM trabajadores WHERE dni='$dni' LIMIT 1");
        if (!$resTrab || mysqli_num_rows($resTrab) === 0) {
            error_log("⚠️ Trabajador con DNI $dni no encontrado al guardar parte.");
            continue;
        }
        $id_trabajador = (int) mysqli_fetch_assoc($resTrab)['id'];

        $check = mysqli_query($conexion, "SELECT id FROM asistencias 
             WHERE id_listado='$id_listado' AND id_trabajador='$id_trabajador' LIMIT 1");

        if ($check && mysqli_num_rows($check) > 0) {
            $id_asistencia = (int) mysqli_fetch_assoc($check)['id'];
            $sqlAsistencia = "UPDATE asistencias SET
                    empresa='$empresa', fecha='$fecha', producto='$producto',
                    asistencia='$asis', Bandeja='$bandeja', Horas='$horas', Observaciones='$observaciones'
                WHERE id='$id_asistencia'";
        } else {
            $sqlAsistencia = "INSERT INTO asistencias
                (id_listado, empresa, fecha, producto, asistencia, id_trabajador, dni, Bandeja, Horas, Observaciones)
             VALUES
                ('$id_listado', '$empresa', '$fecha', '$producto', '$asis', '$id_trabajador', '$dni', '$bandeja', '$horas', '$observaciones')";
        }
        mysqli_query($conexion, $sqlAsistencia) or error_log('❌ Error SQL asistencia: ' . mysqli_error($conexion));

        // totales
        $total_trabajadores++;
        if ($asis === 'si') {
            $total_presentes++;
            $total_bandejas += (int)$bandeja;
            $total_horas    += (float)$horas;
        } else {
            $total_ausentes++;
        }
    }

    /* ---------- 2.2 Guardar firma en carpeta y ruta en DB ---------- */
    $firma_path_sql = "NULL";
    if ($firma_base64 && strpos($firma_base64, 'data:image') === 0) {
        $rutaDir = realpath(__DIR__ . '/../uploads/firmas');
        if ($rutaDir === false) {
            $rutaDir = __DIR__ . '/../uploads/firmas';
            @mkdir($rutaDir, 0775, true);
        }
        $mime = 'png';
        if (preg_match('#^data:image/(png|jpg|jpeg);base64,#i', $firma_base64, $m)) {
            $mime = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
        }
        $raw = preg_replace('#^data:image/[^;]+;base64,#', '', $firma_base64);
        $bin = base64_decode($raw);

        $nombre = 'firma_parte_' . $id_listado . '_' . date('Ymd_His') . '.' . $mime;
        $ruta   = $rutaDir . '/' . $nombre;
        if (file_put_contents($ruta, $bin) !== false) {
            // ruta relativa para FPDF
            $rel = '../uploads/firmas/' . $nombre;
            $firma_path_sql = "'" . mysqli_real_escape_string($conexion, $rel) . "'";
        }
    }

    /* ---------- 2.3 Actualizar resumen + firma en listados_asistencias ---------- */
    $sqlUpd = "UPDATE listados_asistencias SET
        encargado_nombre = '".mysqli_real_escape_string($conexion,$encargado_nombre)."',
        total_trabajadores = $total_trabajadores,
        total_presentes    = $total_presentes,
        total_ausentes     = $total_ausentes,
        total_bandejas     = $total_bandejas,
        total_horas        = $total_horas";
    if ($firma_path_sql !== "NULL") {
        $sqlUpd .= ", firma_path = $firma_path_sql";
    }
    $sqlUpd .= " WHERE id = $id_listado LIMIT 1";
    mysqli_query($conexion, $sqlUpd) or error_log('❌ Error SQL resumen/firma: ' . mysqli_error($conexion));

    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'ok',
        'mensaje' => 'Parte guardado correctamente.',
        'id_listado' => $id_listado,
        'totales' => [
            'trabajadores' => $total_trabajadores,
            'presentes'    => $total_presentes,
            'ausentes'     => $total_ausentes,
            'bandejas'     => $total_bandejas,
            'horas'        => $total_horas,
        ]
    ]);
    exit;
}


/* ============================================================
   ACCIÓN DESCONOCIDA
============================================================ */
http_response_code(400);
echo 'Acción no válida.';
