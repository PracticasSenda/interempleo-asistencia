<?php
include("validar_sesion.php");
if ($_SESSION['rol'] !== 'administrador') {
  header("Location: login_responsive.php");
  exit();
}

include("conexion_bd.php");

$accion = $_GET['accion'] ?? 'listar';
$mensaje = "";

// ---- LÓGICA ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($accion === 'alta') {
    // 🔧 Normalizamos los valores para evitar duplicados por mayúsculas o espacios
    $nombre = ucwords(strtolower(trim($_POST['nombre'])));
    $apellidos = ucwords(strtolower(trim($_POST['apellidos'])));
    $dni = strtoupper(trim($_POST['dni']));
    $password = $_POST['password'];

    // 🔍 Verificar si ya existe un encargado con mismo nombre, apellidos y dni
    $stmtCheck = $conexion->prepare("
        SELECT id, activo 
        FROM usuarios 
        WHERE rol = 'encargado' 
          AND nombre = ? 
          AND apellidos = ? 
          AND dni = ?
        LIMIT 1
    ");
    $stmtCheck->bind_param("sss", $nombre, $apellidos, $dni);
    $stmtCheck->execute();
    $stmtCheck->store_result();  // ✅ Carga los resultados en memoria y libera el cursor
    $stmtCheck->bind_result($idExistente, $activo);

    if ($stmtCheck->fetch()) {
      // ✅ Ya existe → reactivar y actualizar contraseña
      $stmtCheck->close();
      $stmtUpdate = $conexion->prepare("
          UPDATE usuarios 
          SET activo = 1, `contraseña` = ?
          WHERE id = ?
        ");
      $stmtUpdate->bind_param("si", $password, $idExistente);
      if ($stmtUpdate->execute()) {
        if ($activo == 0) {
          $mensaje = "<p style='color:green;font-weight:bold;'>🔄 Encargado reactivado correctamente</p>";
        } else {
          $mensaje = "<p style='color:orange;font-weight:bold;'>⚠️ El encargado ya estaba activo, se actualizó la contraseña</p>";
        }
      } else {
        $mensaje = "<p style='color:red;font-weight:bold;'>❌ Error al actualizar encargado</p>";
      }
      $stmtUpdate->close();
    } else {
      // 🆕 No existe → crear nuevo registro
      $stmtCheck->close();
      $stmtInsert = $conexion->prepare("
            INSERT INTO usuarios (nombre, apellidos, dni, rol, `contraseña`, activo)
            VALUES (?, ?, ?, 'encargado', ?, 1)
        ");
      $stmtInsert->bind_param("ssss", $nombre, $apellidos, $dni, $password);
      if ($stmtInsert->execute()) {
        $mensaje = "<p style='color:green;font-weight:bold;'>✅ Encargado registrado correctamente</p>";
      } else {
        $mensaje = "<p style='color:red;font-weight:bold;'>❌ Error: " . htmlspecialchars($stmtInsert->error) . "</p>";
      }
      $stmtInsert->close();
    }
  }


  if ($accion === 'baja' && isset($_POST['encargado_id'])) {
    $idEncargado = intval($_POST['encargado_id']);
    $dniInput = trim($_POST['dni']);

    $stmtCheck = $conexion->prepare("SELECT nombre, apellidos, dni FROM usuarios WHERE id = ? AND rol = 'encargado'");
    $stmtCheck->bind_param("i", $idEncargado);
    $stmtCheck->execute();
    $stmtCheck->bind_result($nombre, $apellidos, $dniReal);

    if ($stmtCheck->fetch() && strcasecmp($dniInput, $dniReal) === 0) {
      $stmtCheck->close();

      $stmtDel = $conexion->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
      $stmtDel->bind_param("i", $idEncargado);
      $stmtDel->execute();

      if ($stmtDel->affected_rows > 0) {
        $mensaje = "<p style='color:orange;font-weight:bold;'>Encargado <strong>$apellidos $nombre</strong> dado de baja (inactivo) ✅</p>";
      } else {
        $mensaje = "<p style='color:red;font-weight:bold;'>❌ Error al eliminar encargado</p>";
      }
      $stmtDel->close();
    } else {
      $mensaje = "<p style='color:red;font-weight:bold;'>❌ DNI no coincide con encargado seleccionado</p>";
      $stmtCheck->close();
    }
  }
}

// Obtener lista encargados
// 🧩 NUEVOS FILTROS
$filtro_dni = isset($_GET['dni']) ? trim($_GET['dni']) : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : 'activos';

// 🔧 Base de la consulta
$sql = "SELECT id, nombre, apellidos, dni, activo
        FROM usuarios
        WHERE rol = 'encargado'";

// 🧠 Aplicamos filtros dinámicamente
if ($filtro_estado === 'activos') {
  $sql .= " AND activo = 1";
} elseif ($filtro_estado === 'inactivos') {
  $sql .= " AND activo = 0";
}

if ($filtro_dni !== '') {
  $dni_like = $conexion->real_escape_string($filtro_dni);
  $sql .= " AND dni LIKE '%$dni_like%'";
}

$sql .= " ORDER BY activo DESC, apellidos, nombre ASC";

$result = $conexion->query($sql);
$encargados = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="Panel de gestión de encargados de Interempleo. Alta, baja y filtrado de encargados activos e inactivos.">
  <title>Gestión Encargados | Interempleo</title>
  <link rel="icon" href="favicon.ico" type="image/x-icon" />
  <style>
    :root {
      --color-principal: #FF671D;
      --color-fondo: #FFFFFF;
      --color-texto: #333333;
      --color-borde: #CCCCCC;
      --color-input-bg: #F9F9F9;
    }

    /* ----- ESTRUCTURA GENERAL ----- */
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background: var(--color-fondo);
      color: var(--color-texto);
    }

    /* ----- HEADER & NAV ----- */
    header {
      background: var(--color-principal);
      color: white;
      padding: 1.2rem 2rem;
    }

    .contenedor-barra {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }

    header p {
      margin: 0;
      font-size: 1.5rem;
    }

    header span {
      font-weight: bold;
    }

    nav {
      margin-top: 1rem;
      display: flex;
      gap: 1rem;
    }

    nav a {
      padding: 0.5rem 1rem;
      border-radius: 4px;
      text-decoration: none;
      background: white;
      color: var(--color-principal);
      font-weight: bold;
    }

    nav a.active {
      background: var(--color-principal);
      color: white;
    }

    /* ----- CONTENIDO PRINCIPAL ----- */
    main {
      max-width: 800px;
      margin: 2rem auto;
      padding: 0 1rem;
    }

    h2 {
      text-align: center;
      color: var(--color-principal);
    }

    /* ----- TARJETAS Y FORMULARIOS ----- */
    .tarjeta-asistencia {
      background: var(--color-principal);
      color: white;
      padding: 1rem;
      border-radius: 8px;
      margin: 1rem 0;
    }

    label {
      font-weight: bold;
      display: block;
      margin-bottom: 0.3rem;
    }

    input,
    select,
    textarea {
      width: 100%;
      padding: 0.6rem;
      border: none;
      border-radius: 4px;
      margin-bottom: 1rem;
      font-size: 1rem;
      box-sizing: border-box;
    }

    /* ----- BOTONES ----- */
    button,
    .btn-limpiar {
      border-radius: 4px;
      font-weight: bold;
      cursor: pointer;
      transition: 0.2s ease;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      height: 38px;
    }

    button {
      width: 100%;
      padding: 0.9rem;
      background: var(--color-principal);
      color: white;
      border: none;
      font-size: 1rem;
    }

    button:hover,
    .buscador-flex button:hover {
      background: #e65c17;
    }

    /* Botón Reiniciar */
    .btn-limpiar {
      background: none;
      border: 1px solid var(--color-principal);
      color: var(--color-principal);
      padding: 0.35rem 0.7rem;
      margin-left: 6px;
      width: auto;
    }

    .btn-limpiar:hover {
      background: var(--color-principal);
      color: white;
    }

    /* ----- TABLA ----- */
    table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid var(--color-borde);
      background: var(--color-fondo);
      box-shadow: 0 2px 8px rgba(255, 103, 29, 0.1);
    }

    th,
    td {
      text-align: center;
      padding: 8px;
      border-bottom: 1px solid var(--color-borde);
    }

    th {
      background: var(--color-principal);
      color: white;
      font-weight: bold;
      position: sticky;
      top: 0;
      z-index: 2;
    }

    tr.inactivo td {
      opacity: 0.7;
    }

    tr:hover td {
      background-color: #fff4ee;
      transition: background-color 0.2s ease-in-out;
    }

    /* ----- FORMULARIO DE FILTROS ----- */
    form[method="get"] {
      background: #fff4ee;
      border: 1px solid #ffe0cc;
      border-radius: 8px;
      padding: 0.8rem;
      box-shadow: 0 2px 6px rgba(255, 103, 29, 0.1);
      text-align: center;
      margin-bottom: 1rem;
    }

    .buscador-flex {
      display: inline-flex;
      align-items: stretch;
      justify-content: center;
      gap: 6px;
      margin-bottom: 0.5rem;
      vertical-align: middle;
    }

    .buscador-flex input {
      padding: 0.45rem 0.6rem;
      border: 1px solid var(--color-borde);
      border-radius: 4px 0 0 4px;
      width: 180px;
      height: 38px;
    }

    .buscador-flex button {
      background: var(--color-principal);
      color: white;
      border: 1px solid var(--color-borde);
      border-left: none;
      border-radius: 0 4px 4px 0;
      padding: 0.45rem 0.9rem;
      font-weight: bold;
      height: 38px;
    }

    /* ----- FOOTER ----- */
    footer {
      margin-top: 2rem;
      text-align: center;
      font-size: 0.9rem;
      color: #666;
      padding: 1rem;
      border-top: 1px solid var(--color-borde);
    }

    /* ----- RESPONSIVE ----- */
    @media (max-width: 768px) {
      .tabla-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }

      table {
        min-width: 600px;
      }

      .buscador-flex {
        flex-direction: column;
        align-items: stretch;
      }
    }

    .sr-only {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      border: 0;
    }
  </style>
</head>

<body>
  <header>
    <div class="contenedor-barra">
      <p><span>Inter</span>empleo - Gestión Encargados</p>
      <a class="boton-enlace" href="asistencia_responsive.php" style="color:white;">Volver a asistencias</a>
    </div>
    <nav>
      <a href="?accion=alta" class="<?= $accion === 'alta' ? 'active' : '' ?>">➕ Alta</a>
      <a href="?accion=baja" class="<?= $accion === 'baja' ? 'active' : '' ?>">❌ Baja</a>
      <a href="?accion=listar" class="<?= $accion === 'listar' ? 'active' : '' ?>">📋 Lista</a>
    </nav>
  </header>

  <main>
    <?php if ($accion === 'alta'): ?>
      <h2>Dar de alta encargado</h2>
      <form method="post">
        <div class="tarjeta-asistencia">
          <label>Nombre</label>
          <input type="text" name="nombre" required>
          <label>Apellidos</label>
          <input type="text" name="apellidos" required>
          <label>DNI / NIE</label>
          <input type="text" name="dni" required>
          <label>Contraseña</label>
          <input type="password" name="password" required>
        </div>
        <button type="submit">Registrar</button>
      </form>

    <?php elseif ($accion === 'baja'): ?>
      <h2>Dar de baja encargado</h2>
      <?php if (empty($encargados)): ?>
        <p>No hay encargados registrados.</p>
      <?php else: ?>
        <form method="post" onsubmit="return confirmarEliminacion();">
          <div class="tarjeta-asistencia">
            <label>Seleccione encargado</label>
            <select name="encargado_id" id="encargado_id" required>
              <option value="" disabled selected>-- Seleccione encargado --</option>
              <?php foreach ($encargados as $e): ?>
                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['apellidos'] . " " . $e['nombre']) . " - " . $e['dni'] ?></option>
              <?php endforeach; ?>
            </select>
            <label>DNI/NIE</label>
            <input type="text" name="dni" id="dni" required>
          </div>
          <button type="submit" name="enviar">Dar de baja encargado</button>
        </form>
        <script>
          const select = document.getElementById('encargado_id');
          const dniInput = document.getElementById('dni');
          const encargados = <?= json_encode($encargados) ?>;
          select.addEventListener('change', () => {
            const enc = encargados.find(e => e.id == select.value);
            dniInput.value = enc ? enc.dni : '';
          });

          function confirmarEliminacion() {
            if (!select.value) {
              alert("Seleccione un encargado");
              return false;
            }
            const txt = select.options[select.selectedIndex].text;
            return confirm(`¿Eliminar encargado: ${txt}?`);
          }
        </script>
      <?php endif; ?>

    <?php elseif ($accion === 'listar'): ?>
      <h2>Lista de encargados</h2>
      <form id="form-filtros" method="get" style="text-align:center; margin-bottom:1rem;">
        <input type="hidden" name="accion" value="listar">

        <div class="buscador-flex">
          <label for="buscar-dni" class="sr-only">Buscar encargado por DNI o nombre</label>
          <input id="buscar-dni" type="text" name="dni" placeholder="Introduce DNI/NIE"
            value="<?= htmlspecialchars($filtro_dni) ?>">
          <button type="submit">Buscar</button>
        </div>
        <select name="estado" style="padding:0.4rem; border:1px solid #ccc; border-radius:4px;">
          <option value="activos" <?= $filtro_estado === 'activos' ? 'selected' : '' ?>>🟩 Activos</option>
          <option value="inactivos" <?= $filtro_estado === 'inactivos' ? 'selected' : '' ?>>🟥 Inactivos</option>
          <option value="todos" <?= $filtro_estado === 'todos' ? 'selected' : '' ?>>👁️ Todos</option>
        </select>

        <button type="button" onclick="window.location='?accion=listar'" class="btn-limpiar">
          🔄 Reiniciar
        </button>

      </form>

      <script>
        // ✅ Cuando cambias el estado (Activos/Inactivos/Todos), se actualiza automáticamente
        const selectEstado = document.querySelector('select[name="estado"]');
        const formFiltros = document.getElementById('form-filtros');

        if (selectEstado && formFiltros) {
          selectEstado.addEventListener('change', () => {
            formFiltros.submit();
          });
        }
      </script>



      <?php if (empty($encargados)): ?>
        <p>No hay encargados registrados.</p>
      <?php else: ?>
        <div class="tabla-scroll">
          <table class="tabla-encargados">
            <thead>
              <tr>
                <th scope="col">Nombre</th>
                <th scope="col">Apellidos</th>
                <th scope="col">DNI/NIE</th>
                <th scope="col">Estado</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($encargados as $e): ?>
                <tr class="<?= $e['activo'] ? '' : 'inactivo' ?>">

                  <td><?= htmlspecialchars($e['nombre']) ?></td>
                  <td><?= htmlspecialchars($e['apellidos']) ?></td>
                  <td><?= htmlspecialchars($e['dni']) ?></td>
                  <td aria-label="<?= $e['activo'] ? 'Encargado activo' : 'Encargado inactivo' ?>">
                    <?= $e['activo'] ? '🟩 Activo' : '🟥 Inactivo' ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <?= $mensaje ?>
  </main>

  <footer>
    <p>&copy; <?= date("Y") ?> <strong>Interempleo</strong>. Todos los derechos reservados.</p>
  </footer>

</body>

</html>