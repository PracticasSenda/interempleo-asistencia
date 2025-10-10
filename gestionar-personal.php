<?php
include("validar_sesion.php");
if ($_SESSION['rol'] !== 'administrador') {
    header("Location: login_responsive.php");
    exit();
}

include("conexion_bd.php");

// ---- PARÁMETROS ----
$tipo = $_GET['tipo'] ?? 'encargado';
$accion = $_GET['accion'] ?? 'listar';
$mensaje = "";

// Determinar tabla y estructura según el tipo
if ($tipo === 'trabajador') {
    $tabla = "trabajadores";
    $tienePassword = false;
} else {
    $tabla = "usuarios";
    $tienePassword = true;
    $rol_bd = 'encargado';
}

// ---- LÓGICA ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($accion === 'alta') {
        $nombre = ucwords(strtolower(trim($_POST['nombre'])));
        $apellidos = ucwords(strtolower(trim($_POST['apellidos'])));
        $dni = strtoupper(trim($_POST['dni']));
        $password = $_POST['password'] ?? null;

        // Verificar duplicado por DNI
        if ($tipo === 'trabajador') {
            $stmtCheck = $conexion->prepare("SELECT id, activo FROM trabajadores WHERE dni = ? LIMIT 1");
            $stmtCheck->bind_param("s", $dni);
        } else {
            $stmtCheck = $conexion->prepare("SELECT id, activo FROM usuarios WHERE dni = ? AND rol = 'encargado' LIMIT 1");
            $stmtCheck->bind_param("s", $dni);
        }

        $stmtCheck->execute();
        $stmtCheck->store_result();

        if ($stmtCheck->num_rows > 0) {
            $stmtCheck->bind_result($idExistente, $activo);
            $stmtCheck->fetch();
            $stmtCheck->close();

            if ($activo == 0) {
                // Reactivar registro
                if ($tipo === 'trabajador') {
                    $stmt = $conexion->prepare("UPDATE trabajadores SET activo = 1, nombre = ?, apellidos = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $nombre, $apellidos, $idExistente);
                } else {
                    $stmt = $conexion->prepare("UPDATE usuarios SET activo = 1, `contraseña` = ? WHERE id = ?");
                    $stmt->bind_param("si", $password, $idExistente);
                }
                if ($stmt->execute()) {
                    $mensaje = "<p style='color:green;font-weight:bold;'>🔄 $tipo reactivado correctamente.</p>";
                } else {
                    $mensaje = "<p style='color:red;font-weight:bold;'>❌ Error al reactivar $tipo.</p>";
                }
                $stmt->close();
            } else {
                $mensaje = "<p style='color:orange;font-weight:bold;'>⚠️ El $tipo con DNI <strong>$dni</strong> ya está activo.</p>";
            }
        } else {
            $stmtCheck->close();
            // Insertar nuevo
            if ($tipo === 'trabajador') {
                $stmtInsert = $conexion->prepare("INSERT INTO trabajadores (nombre, apellidos, dni, activo) VALUES (?, ?, ?, 1)");
                $stmtInsert->bind_param("sss", $nombre, $apellidos, $dni);
            } else {
                $stmtInsert = $conexion->prepare("INSERT INTO usuarios (nombre, apellidos, dni, rol, `contraseña`, activo) VALUES (?, ?, ?, 'encargado', ?, 1)");
                $stmtInsert->bind_param("ssss", $nombre, $apellidos, $dni, $password);
            }
            if ($stmtInsert->execute()) {
                $mensaje = "<p style='color:green;font-weight:bold;'>✅ $tipo registrado correctamente.</p>";
            } else {
                $mensaje = "<p style='color:red;font-weight:bold;'>❌ Error al registrar $tipo.</p>";
            }
            $stmtInsert->close();
        }
    }

    // ---- BAJA ----
    if ($accion === 'baja' && isset($_POST['usuario_id'])) {
        $id = intval($_POST['usuario_id']);
        $dniInput = trim($_POST['dni']);

        if ($tipo === 'trabajador') {
            $stmt = $conexion->prepare("SELECT nombre, apellidos, dni FROM trabajadores WHERE id = ?");
            $stmt->bind_param("i", $id);
        } else {
            $stmt = $conexion->prepare("SELECT nombre, apellidos, dni FROM usuarios WHERE id = ? AND rol = 'encargado'");
            $stmt->bind_param("i", $id);
        }

        $stmt->execute();
        $stmt->bind_result($nombre, $apellidos, $dniReal);
        if ($stmt->fetch() && strcasecmp($dniInput, $dniReal) === 0) {
            $stmt->close();
            $update = $conexion->prepare("UPDATE $tabla SET activo = 0 WHERE id = ?");
            $update->bind_param("i", $id);
            $update->execute();
            $mensaje = "<p style='color:orange;font-weight:bold;'>✅ $tipo <strong>$apellidos $nombre</strong> dado de baja correctamente.</p>";
            $update->close();
        } else {
            $mensaje = "<p style='color:red;font-weight:bold;'>❌ DNI incorrecto o $tipo no encontrado.</p>";
        }
    }
}

// ---- LISTADO ----
$filtro_dni = isset($_GET['dni']) ? trim($_GET['dni']) : '';
$filtro_estado = $_GET['estado'] ?? 'activos';

$sql = "SELECT id, nombre, apellidos, dni, activo FROM $tabla";
if ($tipo === 'encargado') $sql .= " WHERE rol = 'encargado'";

if ($filtro_estado === 'activos') $sql .= " AND activo = 1";
elseif ($filtro_estado === 'inactivos') $sql .= " AND activo = 0";

if ($filtro_dni !== '') {
    $dni_like = $conexion->real_escape_string($filtro_dni);
    $sql .= " AND dni LIKE '%$dni_like%'";
}

$sql .= " ORDER BY activo DESC, apellidos, nombre ASC";
$result = $conexion->query($sql);
$usuarios = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Personal | Interempleo</title>
    <style>
        :root {
            --color-principal: #FF671D;
            --color-fondo: #FFFFFF;
            --color-texto: #333333;
            --color-borde: #CCCCCC;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: var(--color-fondo);
            color: var(--color-texto);
        }

        header {
            background: var(--color-principal);
            color: white;
            padding: 1.2rem 2rem;
        }

        .contenedor-barra {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav {
            margin-top: 0.8rem;
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        nav a {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            border: 1px solid var(--color-principal);
            transition: 0.2s ease;
        }

        .tipo-usuario a {
            background: #fff;
            color: var(--color-principal);
        }

        .tipo-usuario a.active {
            background: var(--color-principal);
            color: #fff;
        }

        .acciones a {
            background: white;
            color: var(--color-principal);
        }

        .acciones a.active {
            background: var(--color-principal);
            color: white;
        }

        main {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        h2 {
            text-align: center;
            color: var(--color-principal);
        }

        .tarjeta {
            background: var(--color-principal);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }

        label {
            font-weight: bold;
            display: block;
            margin-bottom: .3rem;
        }

        input,
        select {
            width: 100%;
            padding: .6rem;
            border-radius: 4px;
            border: none;
            margin-bottom: 1rem;
        }

        button {
            width: 100%;
            padding: .9rem;
            background: var(--color-principal);
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #e65c17;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid var(--color-borde);
            background: white;
        }

        th {
            background: var(--color-principal);
            color: white;
            padding: .5rem;
        }

        td {
            text-align: center;
            padding: .5rem;
            border-bottom: 1px solid var(--color-borde);
        }

        tr.inactivo td {
            opacity: .7;
        }

        footer {
            text-align: center;
            font-size: .9rem;
            color: #666;
            padding: 1rem;
            border-top: 1px solid var(--color-borde);
            margin-top: 2rem;
        }
    </style>
</head>

<body>
    <header>
        <div class="contenedor-barra">
            <p><span>Inter</span>empleo - Gestión de Personal</p>
            <a class="boton-enlace" href="asistencia_responsive.php" style="color:white;">Volver a asistencias</a>
        </div>
        <nav class="tipo-usuario">
            <a href="?tipo=encargado&accion=<?= $accion ?>" class="<?= $tipo === 'encargado' ? 'active' : '' ?>">🟧 Encargados</a>
            <a href="?tipo=trabajador&accion=<?= $accion ?>" class="<?= $tipo === 'trabajador' ? 'active' : '' ?>">🟩 Trabajadores</a>
        </nav>
        <nav class="acciones">
            <a href="?tipo=<?= $tipo ?>&accion=alta" class="<?= $accion === 'alta' ? 'active' : '' ?>">➕ Alta</a>
            <a href="?tipo=<?= $tipo ?>&accion=baja" class="<?= $accion === 'baja' ? 'active' : '' ?>">❌ Baja</a>
            <a href="?tipo=<?= $tipo ?>&accion=listar" class="<?= $accion === 'listar' ? 'active' : '' ?>">📋 Lista</a>
        </nav>
    </header>

    <main>
        <?php if ($accion === 'alta'): ?>
            <h2>Dar de alta <?= $tipo ?></h2>
            <form method="post">
                <div class="tarjeta">
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
            <h2>Dar de baja <?= $tipo ?></h2>
            <?php if (empty($usuarios)): ?>
                <p>No hay <?= $tipo ?>s registrados.</p>
            <?php else: ?>
                <form method="post" onsubmit="return confirmarBaja();">
                    <div class="tarjeta">
                        <label>Seleccione <?= $tipo ?></label>
                        <select name="usuario_id" id="usuario_id" required>
                            <option value="" disabled selected>-- Seleccione <?= $tipo ?> --</option>
                            <?php foreach ($usuarios as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['apellidos'] . " " . $u['nombre']) . " - " . $u['dni'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label>DNI/NIE</label>
                        <input type="text" name="dni" id="dni" required>
                    </div>
                    <button type="submit">Dar de baja</button>
                </form>
                <script>
                    const select = document.getElementById('usuario_id');
                    const dniInput = document.getElementById('dni');
                    const lista = <?= json_encode($usuarios) ?>;
                    select.addEventListener('change', () => {
                        const u = lista.find(x => x.id == select.value);
                        dniInput.value = u ? u.dni : '';
                    });

                    function confirmarBaja() {
                        if (!select.value) return false;
                        const txt = select.options[select.selectedIndex].text;
                        return confirm(`¿Dar de baja a ${txt}?`);
                    }
                </script>
            <?php endif; ?>

        <?php elseif ($accion === 'listar'): ?>
            <h2>Lista de <?= $tipo ?>s</h2>
            <?php if (empty($usuarios)): ?>
                <p>No hay <?= $tipo ?>s registrados.</p>
            <?php else: ?>
                <div class="tabla-scroll">
                    <table>
                        <tr>
                            <th>Nombre</th>
                            <th>Apellidos</th>
                            <th>DNI</th>
                            <th>Estado</th>
                        </tr>
                        <?php foreach ($usuarios as $u): ?>
                            <tr class="<?= $u['activo'] ? '' : 'inactivo' ?>">
                                <td><?= htmlspecialchars($u['nombre']) ?></td>
                                <td><?= htmlspecialchars($u['apellidos']) ?></td>
                                <td><?= htmlspecialchars($u['dni']) ?></td>
                                <td><?= $u['activo'] ? '🟩 Activo' : '🟥 Inactivo' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?= $mensaje ?>
    </main>
    <footer>&copy; <?= date("Y") ?> Interempleo. Todos los derechos reservados.</footer>
</body>

</html>