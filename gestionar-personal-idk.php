<?php
include("validar_sesion.php");
include("conexion_bd.php");

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'encargado'])) {
    header("Location: login_responsive.php");
    exit();
}

$rol = $_SESSION['rol'];
$nombre = $_SESSION['nombre'] ?? '';
$apellidos = $_SESSION['apellidos'] ?? '';
$nombre_completo = trim("$nombre $apellidos");

$tipo = $_GET['tipo'] ?? 'trabajadores';

// Si un encargado intenta acceder a encargados, redirigimos
if ($rol === 'encargado' && $tipo === 'encargados') {
    header("Location: gestionar-personal.php?tipo=trabajadores");
    exit();
}

$mensaje = "";

// --- DAR DE BAJA (misma página) ---
if (isset($_GET['baja'])) {
    $id = intval($_GET['baja']);
    if ($tipo === 'trabajadores') {
        $sql_baja = "UPDATE trabajadores SET activo=0 WHERE id=$id";
    } elseif ($tipo === 'encargados' && $rol === 'administrador') {
        $sql_baja = "UPDATE usuarios SET activo=0 WHERE id=$id";
    }
    if (mysqli_query($conexion, $sql_baja)) {
        $mensaje = "🟠 Registro dado de baja correctamente.";
    } else {
        $mensaje = "❌ Error al dar de baja: " . mysqli_error($conexion);
    }
}

// --- DAR DE ALTA (formulario) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($tipo === 'trabajadores') {
        $nombre_t = trim($_POST['nombre']);
        $apellidos_t = trim($_POST['apellidos']);
        $dni_t = trim($_POST['dni']);

        if ($nombre_t && $apellidos_t && $dni_t) {
            $stmt = $conexion->prepare("INSERT INTO trabajadores (nombre, apellidos, dni, activo) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("sss", $nombre_t, $apellidos_t, $dni_t);
            if ($stmt->execute()) {
                $mensaje = "✅ Trabajador dado de alta correctamente.";
            } else {
                $mensaje = "❌ Error al registrar el trabajador.";
            }
            $stmt->close();
        } else {
            $mensaje = "⚠️ Todos los campos son obligatorios.";
        }
    }

    if ($tipo === 'encargados' && $rol === 'administrador') {
        $nombre_e = trim($_POST['nombre']);
        $apellidos_e = trim($_POST['apellidos']);
        $dni_e = trim($_POST['dni']);
        $contraseña = trim($_POST['contraseña']);

        if ($nombre_e && $apellidos_e && $dni_e && $contraseña) {
            $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, apellidos, DNI, rol, contraseña, activo) VALUES (?, ?, ?, 'encargado', ?, 1)");
            $stmt->bind_param("ssss", $nombre_e, $apellidos_e, $dni_e, $contraseña);
            if ($stmt->execute()) {
                $mensaje = "✅ Encargado dado de alta correctamente.";
            } else {
                $mensaje = "❌ Error al registrar el encargado.";
            }
            $stmt->close();
        } else {
            $mensaje = "⚠️ Todos los campos son obligatorios.";
        }
    }
}

// --- CONSULTA SEGÚN TIPO ---
if ($tipo === 'encargados' && $rol === 'administrador') {
    $titulo = "Gestión de Encargados";
    $sql = "SELECT id, nombre, apellidos, DNI, rol, activo FROM usuarios WHERE rol='encargado'";
} else {
    $titulo = "Gestión de Trabajadores";
    $sql = "SELECT id, nombre, apellidos, dni, activo FROM trabajadores";
}

$resultado = mysqli_query($conexion, $sql);
if (!$resultado) {
    die("Error en la consulta: " . mysqli_error($conexion));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $titulo; ?> - Interempleo</title>
<style>
  :root {
    --color-principal: #FF671D;
    --color-fondo: #FFFFFF;
    --color-texto: #333333;
    --color-borde: #CCCCCC;
  }
  body {
    font-family: Arial, sans-serif;
    background-color: var(--color-fondo);
    margin: 0;
    padding: 0;
    color: var(--color-texto);
  }
  .barra-superior {
    background-color: var(--color-principal);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
  }
  .barra-superior h1 {
    margin: 0;
    font-size: 1.2rem;
    letter-spacing: 0.5px;
  }
  .barra-superior .usuario {
    font-size: 0.9rem;
  }
  .contenedor {
    max-width: 900px;
    margin: 30px auto;
    background-color: #fff;
    padding: 20px;
    border: 1px solid var(--color-borde);
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
  }
  h2 {
    text-align: center;
    color: var(--color-principal);
    margin-bottom: 20px;
  }
  form {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
    margin-bottom: 20px;
  }
  input[type="text"], input[type="password"] {
    padding: 8px;
    border: 1px solid var(--color-borde);
    border-radius: 6px;
    width: 28%;
  }
  input[type="submit"] {
    background-color: var(--color-principal);
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
  }
  input[type="submit"]:hover {
    background-color: #e65c1a;
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
  }
  th, td {
    border: 1px solid var(--color-borde);
    padding: 10px;
    text-align: center;
  }
  th {
    background-color: var(--color-principal);
    color: white;
  }
  tr:nth-child(even) {
    background-color: #f9f9f9;
  }
  .acciones a {
    text-decoration: none;
    color: var(--color-principal);
    font-weight: bold;
  }
  .acciones a:hover {
    text-decoration: underline;
  }
  .mensaje {
    text-align: center;
    font-weight: bold;
    margin-bottom: 10px;
  }
  .volver {
    text-align: center;
    margin-top: 20px;
  }
  .volver a {
    text-decoration: none;
    color: var(--color-principal);
    font-weight: bold;
  }
  @media (max-width: 700px) {
    table, th, td { font-size: 14px; }
    input[type="text"], input[type="password"] { width: 100%; }
  }
</style>
</head>
<body>

<div class="barra-superior">
  <h1>INTEREMPLEO — <?php echo strtoupper($titulo); ?></h1>
  <div class="usuario">
    Bienvenido, <?php echo htmlspecialchars($nombre_completo); ?> |
    Rol: <strong><?php echo htmlspecialchars($rol); ?></strong>
  </div>
</div>

<div class="contenedor">
  <h2><?php echo $titulo; ?></h2>

  <?php if ($mensaje): ?>
    <p class="mensaje"><?php echo $mensaje; ?></p>
  <?php endif; ?>

  <!-- Formulario de alta -->
  <?php if ($tipo === 'trabajadores' || ($tipo === 'encargados' && $rol === 'administrador')): ?>
  <form method="POST">
    <input type="text" name="nombre" placeholder="Nombre" required>
    <input type="text" name="apellidos" placeholder="Apellidos" required>
    <input type="text" name="dni" placeholder="DNI" required>
    <?php if ($tipo === 'encargados'): ?>
      <input type="password" name="contraseña" placeholder="Contraseña" required>
    <?php endif; ?>
    <input type="submit" value="Dar de alta <?php echo $tipo === 'encargados' ? 'encargado' : 'trabajador'; ?>">
  </form>
  <?php endif; ?>

  <!-- Listado -->
  <table>
    <tr>
      <th>ID</th>
      <th>Nombre</th>
      <th>Apellidos</th>
      <th>DNI</th>
      <?php if ($tipo === 'encargados' && $rol === 'administrador'): ?>
        <th>Rol</th>
      <?php endif; ?>
      <th>Estado</th>
      <th>Acción</th>
    </tr>

    <?php while ($fila = mysqli_fetch_assoc($resultado)): ?>
    <tr>
      <td><?php echo htmlspecialchars($fila['id']); ?></td>
      <td><?php echo htmlspecialchars($fila['nombre']); ?></td>
      <td><?php echo htmlspecialchars($fila['apellidos']); ?></td>
      <td><?php echo htmlspecialchars($fila['DNI'] ?? $fila['dni']); ?></td>
      <?php if ($tipo === 'encargados' && $rol === 'administrador'): ?>
        <td><?php echo htmlspecialchars($fila['rol']); ?></td>
      <?php endif; ?>
      <td><?php echo $fila['activo'] ? '🟢 Activo' : '⚫ Inactivo'; ?></td>
      <td class="acciones">
        <?php if ($fila['activo']): ?>
          <a href="?tipo=<?php echo $tipo; ?>&baja=<?php echo $fila['id']; ?>" onclick="return confirm('¿Dar de baja este registro?')">Dar de baja</a>
        <?php else: ?>
          <span style="opacity:0.6;">Dado de baja</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>

  <div class="volver">
    <a href="index.php">← Volver al inicio</a>
  </div>
</div>
</body>
</html>
