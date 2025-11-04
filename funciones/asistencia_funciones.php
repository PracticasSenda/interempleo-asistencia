<?php
// funciones/asistencia_funciones.php
if (!defined('APP_VALID')) define('APP_VALID', true);

/* =======================
   Utilidades
======================= */
function limpiarTexto($conexion, $texto) {
  return mysqli_real_escape_string($conexion, mb_strtoupper(trim((string)$texto), 'UTF-8'));
}
function campoVacio($valor) { return (!isset($valor) || trim($valor) === ''); }
function formatearNombreCompleto($nombre, $apellidos) {
  return trim(mb_strtoupper($nombre . ' ' . $apellidos, 'UTF-8'));
}
function json_response($arr, $code=200) {
  http_response_code($code);
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

/* =======================
   Buscadores (AJAX)
======================= */
function buscarEncargados($conexion) {
  $q = $_GET['q'] ?? '';
  $orden = $_GET['orden'] ?? 'alfabetico';
  $q_safe = limpiarTexto($conexion, $q);
  $orden_sql = ($orden === 'recientes') ? "ORDER BY id DESC" : "ORDER BY nombre ASC, apellidos ASC";

  $sql = "SELECT id, nombre, apellidos, dni
          FROM usuarios
          WHERE rol='encargado' AND (
            UPPER(nombre) LIKE '%$q_safe%' OR
            UPPER(apellidos) LIKE '%$q_safe%' OR
            UPPER(dni) LIKE '%$q_safe%'
          )
          $orden_sql LIMIT 20";
  $res = mysqli_query($conexion, $sql);

  if (!$res || mysqli_num_rows($res) === 0) {
    echo "<div class='sugerencia-item sin-resultados'>No se encontraron encargados.</div>";
    exit;
  }

  echo "<div class='sugerencias-tabla'>";
  while ($f = mysqli_fetch_assoc($res)) {
    $nombre = htmlspecialchars($f['nombre'], ENT_QUOTES, 'UTF-8');
    $ap = htmlspecialchars($f['apellidos'], ENT_QUOTES, 'UTF-8');
    $dni = htmlspecialchars($f['dni'], ENT_QUOTES, 'UTF-8');
    $full = formatearNombreCompleto($nombre, $ap);
    echo "<div class='sugerencia-item' data-nombre='$nombre' data-apellidos='$ap' data-dni='$dni'>$full ($dni)</div>";
  }
  echo "</div>";
  exit;
}

function buscarTrabajadores($conexion) {
  // Dos modos: sugerencias compactas (si q) o tabla completa (si todos=1)
  $orden = $_GET['orden'] ?? 'alfabetico';
  $orden_sql = ($orden === 'recientes') ? "ORDER BY id DESC" : "ORDER BY nombre ASC, apellidos ASC";

  if (!empty($_GET['q'])) {
    $q_safe = limpiarTexto($conexion, $_GET['q']);
    $sql = "SELECT id, nombre, apellidos, dni FROM trabajadores
            WHERE activo=1 AND (
              UPPER(nombre) LIKE '%$q_safe%' OR
              UPPER(apellidos) LIKE '%$q_safe%' OR
              UPPER(dni) LIKE '%$q_safe%'
            ) $orden_sql LIMIT 20";
    $res = mysqli_query($conexion, $sql);
    if (!$res || mysqli_num_rows($res) === 0) {
      echo "<div class='sugerencia-item sin-resultados'>No se encontraron trabajadores.</div>";
      exit;
    }
    echo "<div class='sugerencias-tabla'>";
    while ($f = mysqli_fetch_assoc($res)) {
      $nombre = htmlspecialchars($f['nombre'], ENT_QUOTES, 'UTF-8');
      $ap = htmlspecialchars($f['apellidos'], ENT_QUOTES, 'UTF-8');
      $dni = htmlspecialchars($f['dni'], ENT_QUOTES, 'UTF-8');
      $full = formatearNombreCompleto($nombre, $ap);
      echo "<div class='sugerencia-item' data-nombre='$nombre' data-apellidos='$ap' data-dni='$dni'>$full ($dni)</div>";
    }
    echo "</div>";
    exit;
  }

  // Si no hay q, devolver tabla completa (para "mostrar todos")
  $sql = "SELECT id, nombre, apellidos, dni FROM trabajadores WHERE activo=1 $orden_sql";
  $res = mysqli_query($conexion, $sql);
  if (!$res || mysqli_num_rows($res) === 0) {
    echo "<tr><td colspan='5'>No hay trabajadores activos.</td></tr>";
    exit;
  }
  while ($f = mysqli_fetch_assoc($res)) {
    $dni = htmlspecialchars($f['dni'], ENT_QUOTES, 'UTF-8');
    $full = formatearNombreCompleto($f['nombre'], $f['apellidos']);
    $full = htmlspecialchars($full, ENT_QUOTES, 'UTF-8');
    echo "<tr id='fila_$dni'>
            <td><input type='checkbox' class='check-asistencia' data-dni='$dni' checked></td>
            <td class='col-nombre'>$full</td>
            <td class='col-dni'>$dni</td>
            <td class='col-acciones'>
              <button type='button' class='btn-detalle-toggle' data-dni='$dni' aria-label='Ver detalles'>▼</button>
              <button type='button' class='btn-eliminar' data-dni='$dni' aria-label='Quitar de la lista'>🗑</button>
            </td>
          </tr>
          <tr class='fila-detalle' data-dni='$dni'>
            <td colspan='4'>
              <div class='detalle-content'></div>
              <div class='feedback-guardar'></div>
            </td>
          </tr>";
  }
  exit;
}

/* =======================
   Detalles y guardado
======================= */
function detalleTrabajador($conexion) {
  $dni = limpiarTexto($conexion, $_GET['dni'] ?? '');
  // Parte nuevo: campos en blanco por defecto
  echo "
    <div class='detalle-grid'>
      <label>Bandejas
        <input type='number' name='Bandeja_$dni' min='0' step='1' value=''>
      </label>
      <label>Horas
        <input type='number' name='Horas_$dni' min='0' step='0.5' value=''>
      </label>
      <label class='observacion-full'>Observaciones
        <input type='text' name='Observaciones_$dni' value=''>
      </label>
      <div class='detalle-actions'>
        <button type='button' class='btn-guardar-detalle' data-dni='$dni'>Guardar</button>
      </div>
    </div>";
  exit;
}

function guardarDetalle($conexion) {
  // Guardado rápido por trabajador (opcional). Mantiene historial: inserta registro nuevo.
  $dni = limpiarTexto($conexion, $_POST['dni'] ?? '');
  $empresa = limpiarTexto($conexion, $_POST['empresa'] ?? '');
  $fecha = limpiarTexto($conexion, $_POST['fecha'] ?? '');
  $producto = limpiarTexto($conexion, $_POST['producto'] ?? '');
  $asistencia = limpiarTexto($conexion, $_POST['asistencia'] ?? 'no');
  $bandeja = limpiarTexto($conexion, $_POST['Bandeja'] ?? '0');
  $horas = limpiarTexto($conexion, $_POST['Horas'] ?? '0');
  $obs = limpiarTexto($conexion, $_POST['Observaciones'] ?? '');

  $resTrab = mysqli_query($conexion, "SELECT id FROM trabajadores WHERE dni='$dni' LIMIT 1");
  if (!$resTrab || mysqli_num_rows($resTrab) === 0) {
    http_response_code(404);
    echo "Trabajador no encontrado";
    exit;
  }
  $id_trabajador = mysqli_fetch_assoc($resTrab)['id'];

  $sql = "INSERT INTO asistencias (id_trabajador, dni, empresa, fecha, producto, asistencia, Bandeja, Horas, Observaciones)
          VALUES ('$id_trabajador', '$dni', '$empresa', '$fecha', '$producto', '$asistencia', '$bandeja', '$horas', '$obs')";
  if (mysqli_query($conexion, $sql)) {
    echo "OK";
  } else {
    http_response_code(500);
    echo "Error SQL";
  }
  exit;
}

function guardarParteCompleto($conexion) {
  // Acepta JSON (application/json) o FormData (trabajadores como JSON string)
  $data = [];
  $raw = file_get_contents('php://input');
  $ct = $_SERVER['CONTENT_TYPE'] ?? '';

  if (stripos($ct, 'application/json') !== false) {
    $data = json_decode($raw, true);
  } else {
    $data = $_POST;
    if (isset($data['trabajadores']) && is_string($data['trabajadores'])) {
      $data['trabajadores'] = json_decode($data['trabajadores'], true);
    }
  }

  $empresa = limpiarTexto($conexion, $data['empresa'] ?? '');
  $fecha = limpiarTexto($conexion, $data['fecha'] ?? '');
  $producto = limpiarTexto($conexion, $data['producto'] ?? '');
  $encargado_nombre = limpiarTexto($conexion, $data['nombre_encargado'] ?? $data['encargado'] ?? '');
  $trabajadores = $data['trabajadores'] ?? [];

  if (campoVacio($empresa) || campoVacio($fecha) || campoVacio($producto) || campoVacio($encargado_nombre)) {
    http_response_code(400);
    echo "Datos generales incompletos.";
    exit;
  }
  if (!is_array($trabajadores) || empty($trabajadores)) {
    http_response_code(400);
    echo "No hay trabajadores en el parte.";
    exit;
  }

  // Validar encargado (por nombre + apellidos exactos o por dni si vino)
  $resEnc = mysqli_query($conexion, "SELECT id FROM usuarios WHERE rol='encargado' AND (UPPER(CONCAT(nombre,' ',apellidos))=UPPER('$encargado_nombre') OR UPPER(nombre)=UPPER('$encargado_nombre')) LIMIT 1");
  if (!$resEnc || mysqli_num_rows($resEnc) === 0) {
    http_response_code(404);
    echo "Encargado no válido.";
    exit;
  }
  $id_encargado = mysqli_fetch_assoc($resEnc)['id'];

  // Crear nuevo listado
  $sql_listado = "INSERT INTO listados_asistencias (id_encargado, empresa, fecha, producto)
                  VALUES ('$id_encargado', '$empresa', '$fecha', '$producto')";
  if (!mysqli_query($conexion, $sql_listado)) {
    http_response_code(500);
    echo "Error al crear el parte.";
    exit;
  }
  $id_listado = mysqli_insert_id($conexion);

  foreach ($trabajadores as $t) {
    $dni = limpiarTexto($conexion, $t['dni'] ?? '');
    $asistencia = (isset($t['asistencia']) && strtolower($t['asistencia']) === 'si') ? 'si' : 'no';
    $bandeja = limpiarTexto($conexion, $t['bandeja'] ?? '');
    $horas = limpiarTexto($conexion, $t['horas'] ?? '');
    $obs = limpiarTexto($conexion, $t['obs'] ?? $t['observaciones'] ?? '');

    if ($dni === '') continue;

    $qTrab = mysqli_query($conexion, "SELECT id FROM trabajadores WHERE dni='$dni' LIMIT 1");
    if ($qTrab && mysqli_num_rows($qTrab) > 0) {
      $id_trabajador = mysqli_fetch_assoc($qTrab)['id'];
      mysqli_query($conexion, "INSERT INTO asistencias
        (id_listado, empresa, fecha, producto, asistencia, id_trabajador, dni, Bandeja, Horas, Observaciones)
        VALUES
        ('$id_listado', '$empresa', '$fecha', '$producto', '$asistencia', '$id_trabajador', '$dni', '$bandeja', '$horas', '$obs')");
    }
  }

  echo "OK";
  exit;
}
