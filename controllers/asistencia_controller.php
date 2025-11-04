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

    $sql = "SELECT nombre, apellidos, dni 
            FROM usuarios 
            WHERE rol = 'encargado' 
            AND (
                UPPER(nombre) LIKE '%$q%' OR
                UPPER(apellidos) LIKE '%$q%' OR
                UPPER(CONCAT(nombre, ' ', apellidos)) LIKE '%$q%' OR
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
        $nombreCompleto = htmlspecialchars($fila['nombre'] . ' ' . $fila['apellidos']);
        $dni = htmlspecialchars($fila['dni']);
        echo "<div class='sugerencia-item' data-nombre='$nombreCompleto' data-dni='$dni'>$nombreCompleto ($dni)</div>";
    }
    exit;
}



/* ============================================================
   BUSCADOR DE TRABAJADORES (para sugerencias flotantes)
============================================================ */
if ($action === 'buscar_trabajadores') {
    $q = mysqli_real_escape_string($conexion, mb_strtoupper($_GET['q'] ?? '', 'UTF-8'));

    $sql = "SELECT nombre, apellidos, dni 
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
        $nombreCompleto = htmlspecialchars($fila['nombre'] . ' ' . $fila['apellidos']);
        $dni = htmlspecialchars($fila['dni']);
        echo "<div class='sugerencia-item' data-nombre='$nombreCompleto' data-dni='$dni'>$nombreCompleto ($dni)</div>";
    }
    exit;
}

/* ============================================================
   CARGAR DETALLE DE TRABAJADOR
============================================================ */
if ($action === 'detalle_trabajador') {
    $dni = mysqli_real_escape_string($conexion, $_GET['dni'] ?? '');
    $fecha = mysqli_real_escape_string($conexion, $_GET['fecha'] ?? '');

    // Comprobamos si hay un registro anterior para esa fecha y trabajador
    $sql = "SELECT Bandeja, Horas, Observaciones 
            FROM asistencias 
            WHERE dni='$dni' AND fecha='$fecha' 
            LIMIT 1";
    $res = mysqli_query($conexion, $sql);
    $data = ['Bandeja' => '', 'Horas' => '', 'Observaciones' => ''];

    if ($res && mysqli_num_rows($res) > 0) {
        $data = mysqli_fetch_assoc($res);
    }
echo '
<div class="detalle-grid">
  <label>Bandejas
    <input type="number" name="Bandeja_' . $dni . '" placeholder="0" min="0" step="1">
  </label>
  <label>Horas
    <input type="number" name="Horas_' . $dni . '" placeholder="0" min="0" step="0.5">
  </label>
  <label class="observacion-full">Observaciones
    <input type="text" name="Observaciones_' . $dni . '" placeholder="Escribe aquí...">
  </label>
</div>
<button type="button" class="btn-guardar-detalle">Guardar</button>
';

    exit;
}

/* ============================================================
   GUARDAR DETALLE DE TRABAJADOR
============================================================ */
if ($action === 'guardar_detalle') {
    $dni = mysqli_real_escape_string($conexion, $_POST['dni'] ?? '');
    $empresa = mysqli_real_escape_string($conexion, $_POST['empresa'] ?? '');
    $fecha = mysqli_real_escape_string($conexion, $_POST['fecha'] ?? '');
    $producto = mysqli_real_escape_string($conexion, $_POST['producto'] ?? '');
    $asistencia = mysqli_real_escape_string($conexion, $_POST['asistencia'] ?? '');
    $bandeja = mysqli_real_escape_string($conexion, $_POST['Bandeja'] ?? '');
    $horas = mysqli_real_escape_string($conexion, $_POST['Horas'] ?? '');
    $obs = mysqli_real_escape_string($conexion, $_POST['Observaciones'] ?? '');

 //  Si no asistió, permitir solo observaciones
if ($asistencia !== 'si') {
    // Si intenta enviar bandejas u horas, las forzamos a 0
    $bandeja = 0;
    $horas = 0;
}


    // Buscar id_trabajador
    $resTrab = mysqli_query($conexion, "SELECT id FROM trabajadores WHERE dni='$dni' LIMIT 1");
    if (!$resTrab || mysqli_num_rows($resTrab) === 0) {
        http_response_code(404);
        exit('No se encontró el trabajador');
    }
    $id_trabajador = mysqli_fetch_assoc($resTrab)['id'];

    // Verificar si ya hay un registro para ese trabajador y fecha
    $check_sql = "SELECT id FROM asistencias WHERE dni='$dni' AND DATE(fecha)=DATE('$fecha') LIMIT 1";
    $check_res = mysqli_query($conexion, $check_sql);

    if ($check_res && mysqli_num_rows($check_res) > 0) {
        // Si ya hay uno, no sobrescribir: crear otro registro con timestamp actual
        $fecha_actual = date("Y-m-d H:i:s");
        $sql = "INSERT INTO asistencias 
                (id_trabajador, dni, empresa, fecha, producto, asistencia, Bandeja, Horas, Observaciones)
                VALUES 
                ('$id_trabajador', '$dni', '$empresa', '$fecha_actual', '$producto', '$asistencia', '$bandeja', '$horas', '$obs')";
    } else {
      // Intentar asociar el registro al último listado creado (si existe)
$id_listado = null;
$resListado = mysqli_query($conexion, "SELECT id FROM listados_asistencias ORDER BY id DESC LIMIT 1");
if ($resListado && mysqli_num_rows($resListado) > 0) {
    $id_listado = mysqli_fetch_assoc($resListado)['id'];
}

// Insertar asistencia (con o sin id_listado)
if ($id_listado) {
    $sql = "INSERT INTO asistencias 
            (id_listado, id_trabajador, dni, empresa, fecha, producto, asistencia, Bandeja, Horas, Observaciones)
            VALUES 
            ('$id_listado', '$id_trabajador', '$dni', '$empresa', '$fecha', '$producto', '$asistencia', '$bandeja', '$horas', '$obs')";
} else {
    $sql = "INSERT INTO asistencias 
            (id_trabajador, dni, empresa, fecha, producto, asistencia, Bandeja, Horas, Observaciones)
            VALUES 
            ('$id_trabajador', '$dni', '$empresa', '$fecha', '$producto', '$asistencia', '$bandeja', '$horas', '$obs')";
}

    }

    if (mysqli_query($conexion, $sql)) {
        echo "OK"; // ✅ Respuesta limpia sin espacios ni texto adicional
    } else {
        // 🚨 Mostrar error exacto para depurar
        echo "ERROR_SQL: " . mysqli_error($conexion);
    }
    exit;
}


/* ===========================================================
 GUARDAR PARTE COMPLETO (usando listados_asistencias)
=========================================================== */
if ($action === 'guardar_parte_completo') {
    $encargado_nombre = mysqli_real_escape_string($conexion, $_POST['encargado'] ?? '');
    $empresa = mysqli_real_escape_string($conexion, $_POST['empresa'] ?? '');
    $fecha = mysqli_real_escape_string($conexion, $_POST['fecha'] ?? '');
    $producto = mysqli_real_escape_string($conexion, $_POST['producto'] ?? '');
    $trabajadores = json_decode($_POST['trabajadores'] ?? '[]', true);

    if (!$encargado_nombre || !$empresa || !$fecha || !$producto || empty($trabajadores)) {
        ob_clean();
        http_response_code(400);
        exit('Datos incompletos.');
    }

    // 🔹 Buscar ID del encargado
    $resEncargado = mysqli_query($conexion, "SELECT id FROM usuarios WHERE CONCAT(nombre, ' ', apellidos) LIKE '%$encargado_nombre%' AND rol='encargado' LIMIT 1");
    if (!$resEncargado || mysqli_num_rows($resEncargado) === 0) {
        ob_clean();
        http_response_code(404);
        exit('No se encontró el encargado especificado.');
    }
    $id_encargado = mysqli_fetch_assoc($resEncargado)['id'];

    // 🔹 Evitar duplicados exactos del mismo encargado en la misma fecha
    $checkParte = mysqli_query($conexion, "
        SELECT id FROM listados_asistencias 
        WHERE id_encargado='$id_encargado' AND fecha='$fecha' AND empresa='$empresa' AND producto='$producto'
        LIMIT 1
    ");
    if ($checkParte && mysqli_num_rows($checkParte) > 0) {
        $id_listado = mysqli_fetch_assoc($checkParte)['id'];
    } else {
        $sqlListado = "INSERT INTO listados_asistencias (id_encargado, empresa, fecha, producto)
                       VALUES ('$id_encargado', '$empresa', '$fecha', '$producto')";
        if (!mysqli_query($conexion, $sqlListado)) {
            ob_clean();
            http_response_code(500);
            exit('Error al crear el listado: ' . mysqli_error($conexion));
        }
        $id_listado = mysqli_insert_id($conexion);
    }

    // 🔹 Insertar cada trabajador en la tabla asistencias
    foreach ($trabajadores as $t) {
        $dni = mysqli_real_escape_string($conexion, $t['dni']);
        $asistencia = mysqli_real_escape_string($conexion, $t['asistencia']);
        $bandeja = mysqli_real_escape_string($conexion, $t['bandeja']);
        $horas = mysqli_real_escape_string($conexion, $t['horas']);
        $observaciones = mysqli_real_escape_string($conexion, $t['observaciones']);

        $resTrab = mysqli_query($conexion, "SELECT id FROM trabajadores WHERE dni='$dni' LIMIT 1");
        if ($resTrab && mysqli_num_rows($resTrab) > 0) {
            $id_trabajador = mysqli_fetch_assoc($resTrab)['id'];

            $sqlAsistencia = "INSERT INTO asistencias 
                (id_listado, empresa, fecha, producto, asistencia, id_trabajador, dni, Bandeja, Horas, Observaciones)
                VALUES 
                ('$id_listado', '$empresa', '$fecha', '$producto', '$asistencia', '$id_trabajador', '$dni', '$bandeja', '$horas', '$observaciones')";
            
            mysqli_query($conexion, $sqlAsistencia);
        } else {
            error_log("⚠️ Trabajador con DNI $dni no encontrado al guardar parte.");
        }
    }


    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'ok',
        'mensaje' => 'Parte guardado correctamente.',
        'trabajadores_guardados' => count($trabajadores)
    ]);
    exit;
}




/* ============================================================
 ACCIÓN DESCONOCIDA
============================================================ */
http_response_code(400);
echo 'Acción no válida.';
