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
        $nombre = trim($_POST['nombre']);
        $apellidos = trim($_POST['apellidos']);
        $dni = trim($_POST['dni']);
        $password = $_POST['password'];
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, apellidos, dni, rol, contraseña) VALUES (?, ?, ?, 'encargado', ?)");
        $stmt->bind_param("ssss", $nombre, $apellidos, $dni, $hash);

        if ($stmt->execute()) {
            $mensaje = "<p style='color:green;font-weight:bold;'>✅ Encargado registrado correctamente</p>";
        } else {
            $mensaje = "<p style='color:red;font-weight:bold;'>❌ Error: " . htmlspecialchars($stmt->error) . "</p>";
        }
        $stmt->close();
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

            $stmtDel = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmtDel->bind_param("i", $idEncargado);
            $stmtDel->execute();

            if ($stmtDel->affected_rows > 0) {
                $mensaje = "<p style='color:green;font-weight:bold;'>Encargado <strong>$apellidos $nombre</strong> eliminado correctamente ✅</p>";
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
$result = $conexion->query("SELECT id, nombre, apellidos, dni FROM usuarios WHERE rol = 'encargado' ORDER BY apellidos, nombre ASC");
$encargados = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Gestión Encargados | Interempleo</title>
  <style>
      :root {
          --color-principal: #FF671D;
          --color-fondo: #FFFFFF;
          --color-texto: #333333;
          --color-borde: #CCCCCC;
          --color-input-bg: #F9F9F9;
      }
      body {
          font-family: Arial, sans-serif;
          margin: 0; padding: 0;
          background: var(--color-fondo);
          color: var(--color-texto);
      }
      header {
          background-color: var(--color-principal);
          color: white; padding: 1.2rem 2rem;
      }
      .contenedor-barra {
          display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;
      }
      header p { margin:0; font-size:1.5rem; }
      header span { font-weight: bold; }
      nav { margin-top:1rem; display:flex; gap:1rem; }
      nav a {
          padding:0.5rem 1rem; border-radius:4px; text-decoration:none;
          background:white; color:var(--color-principal); font-weight:bold;
      }
      nav a.active { background: var(--color-principal); color:white; }
      main { max-width:800px; margin:2rem auto; padding:0 1rem; }
      h2 { text-align:center; color:var(--color-principal); }
      .tarjeta-asistencia {
          background: var(--color-principal); color:white;
          padding:1rem; border-radius:8px; margin:1rem 0;
      }
      label { font-weight:bold; display:block; margin-bottom:0.3rem; }
      input, select {
          width:100%; padding:0.6rem; border:none; border-radius:4px; margin-bottom:1rem;
      }
      button {
          width:100%; padding:0.9rem; background:var(--color-principal);
          color:white; border:none; border-radius:4px; font-size:1rem; cursor:pointer;
      }
      button:hover { background:#e65c17; }
      footer {
          margin-top:2rem; text-align:center; font-size:0.9rem; color:#666; padding:1rem;
          border-top:1px solid var(--color-borde);
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
    <a href="?accion=alta" class="<?= $accion==='alta'?'active':'' ?>">➕ Alta</a>
    <a href="?accion=baja" class="<?= $accion==='baja'?'active':'' ?>">❌ Baja</a>
    <a href="?accion=listar" class="<?= $accion==='listar'?'active':'' ?>">📋 Lista</a>
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
              <?php foreach($encargados as $e): ?>
                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['apellidos']." ".$e['nombre'])." - ".$e['dni'] ?></option>
              <?php endforeach; ?>
          </select>
          <label>DNI/NIE</label>
          <input type="text" name="dni" id="dni" required>
        </div>
        <button type="submit" name="enviar">Eliminar usuario</button>
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
            if (!select.value) { alert("Seleccione un encargado"); return false; }
            const txt = select.options[select.selectedIndex].text;
            return confirm(`¿Eliminar encargado: ${txt}?`);
        }
      </script>
    <?php endif; ?>

  <?php elseif ($accion === 'listar'): ?>
    <h2>Lista de encargados</h2>
    <?php if (empty($encargados)): ?>
      <p>No hay encargados registrados.</p>
    <?php else: ?>
      <table border="1" cellpadding="8" style="width:100%; border-collapse:collapse;">
        <tr style="background:#eee;"><th>Nombre</th><th>Apellidos</th><th>DNI/NIE</th></tr>
        <?php foreach($encargados as $e): ?>
          <tr>
            <td><?= htmlspecialchars($e['nombre']) ?></td>
            <td><?= htmlspecialchars($e['apellidos']) ?></td>
            <td><?= htmlspecialchars($e['dni']) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>
  <?php endif; ?>

  <?= $mensaje ?>
</main>

<footer>
  &copy; <?= date("Y") ?> Interempleo. Todos los derechos reservados.
</footer>
</body>
</html>
