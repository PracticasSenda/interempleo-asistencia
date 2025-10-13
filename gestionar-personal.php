<?php
// gestionar-personal.php
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

// Parámetros UI
$tipo   = $_GET['tipo']   ?? 'trabajadores';        // 'trabajadores' | 'encargados'
$vista  = $_GET['vista']  ?? 'lista';               // 'lista' | 'alta'
$estado = $_GET['estado'] ?? 'activo';              // valor por defecto ahora es 'activo'
$q      = trim($_GET['q'] ?? '');                   // búsqueda servidor: nombre/apellidos/dni

// Encargado no puede ver encargados
if ($rol === 'encargado' && $tipo === 'encargados') {
    header("Location: gestionar-personal.php?tipo=trabajadores&vista=lista");
    exit();
}

// Mensajería
$code = $_GET['code'] ?? '';
$ok   = ($_GET['ok'] ?? '') === '1';
$MSG = [
    'alta_ok_trab'   => 'Trabajador dado de alta correctamente.',
    'alta_ok_enc'    => 'Encargado dado de alta correctamente.',
    'react_ok_trab'  => 'Trabajador reactivado correctamente.',
    'react_ok_enc'   => 'Encargado reactivado correctamente.',
    'dup_activo'     => 'El DNI ya existe y está activo. No se creó un duplicado.',
    'baja_ok_trab'   => 'Trabajador dado de baja.',
    'baja_ok_enc'    => 'Encargado dado de baja.',
    'err_campos'     => 'Todos los campos son obligatorios.',
    'err_sql'        => 'Ocurrió un error al operar en la base de datos.',
    'sin_permiso'    => 'No tienes permiso para esta acción.'
];

function h($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
function redirect_with($params)
{
    $base = 'gestionar-personal.php';
    header("Location: $base?" . http_build_query($params));
    exit();
}

/* ============================
   ACCIONES POST (BAJA / ALTA)
   ============================ */

// Dar de baja (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'baja') {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        if ($tipo === 'trabajadores') {
            $stmt = $conexion->prepare("UPDATE trabajadores SET activo=0 WHERE id=?");
            $stmt->bind_param("i", $id);
            $ok_exec = $stmt->execute();
            $stmt->close();
            redirect_with(['tipo' => $tipo, 'vista' => 'lista', 'estado' => $estado, 'q' => $q, 'code' => $ok_exec ? 'baja_ok_trab' : 'err_sql', 'ok' => $ok_exec ? '1' : '0']);
        } elseif ($tipo === 'encargados' && $rol === 'administrador') {
            $stmt = $conexion->prepare("UPDATE usuarios SET activo=0 WHERE id=? AND rol='encargado'");
            $stmt->bind_param("i", $id);
            $ok_exec = $stmt->execute();
            $stmt->close();
            redirect_with(['tipo' => $tipo, 'vista' => 'lista', 'estado' => $estado, 'q' => $q, 'code' => $ok_exec ? 'baja_ok_enc' : 'err_sql', 'ok' => $ok_exec ? '1' : '0']);
        } else {
            redirect_with(['tipo' => 'trabajadores', 'vista' => 'lista', 'code' => 'sin_permiso', 'ok' => '0']);
        }
    }
}

// Dar de alta (POST) + reactivar si corresponde
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'alta') {
    $n = trim($_POST['nombre'] ?? '');
    $a = trim($_POST['apellidos'] ?? '');
    $d = trim($_POST['dni'] ?? '');
    $c = ($tipo === 'encargados') ? trim($_POST['contraseña'] ?? '') : '';

    if ($n === '' || $a === '' || $d === '' || ($tipo === 'encargados' && $c === '')) {
        redirect_with(['tipo' => $tipo, 'vista' => 'alta', 'estado' => $estado, 'q' => $q, 'code' => 'err_campos', 'ok' => '0']);
    }

    if ($tipo === 'trabajadores') {
        // Buscar por DNI
        $stmt = $conexion->prepare("SELECT id, activo FROM trabajadores WHERE dni=? LIMIT 1");
        $stmt->bind_param("s", $d);
        $stmt->execute();
        $stmt->bind_result($id_found, $act_found);
        $exists = $stmt->fetch();
        $stmt->close();

        if ($exists) {
            if (intval($act_found) === 1) {
                redirect_with(['tipo' => $tipo, 'vista' => 'alta', 'estado' => $estado, 'q' => $q, 'code' => 'dup_activo', 'ok' => '0']);
            } else {
                // Reactivar + actualizar nombre/apellidos
                $stmt = $conexion->prepare("UPDATE trabajadores SET nombre=?, apellidos=?, activo=1 WHERE id=?");
                $stmt->bind_param("ssi", $n, $a, $id_found);
                $ok_exec = $stmt->execute();
                $stmt->close();
                redirect_with(['tipo' => $tipo, 'vista' => 'lista', 'estado' => $estado, 'q' => $q, 'code' => $ok_exec ? 'react_ok_trab' : 'err_sql', 'ok' => $ok_exec ? '1' : '0']);
            }
        } else {
            // Insert nuevo
            $stmt = $conexion->prepare("INSERT INTO trabajadores (nombre, apellidos, dni, activo) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("sss", $n, $a, $d);
            $ok_exec = $stmt->execute();
            $stmt->close();
            redirect_with(['tipo' => $tipo, 'vista' => 'lista', 'estado' => $estado, 'q' => $q, 'code' => $ok_exec ? 'alta_ok_trab' : 'err_sql', 'ok' => $ok_exec ? '1' : '0']);
        }
    } elseif ($tipo === 'encargados' && $rol === 'administrador') {
        // Buscar por DNI en usuarios (rol=encargado)
        $stmt = $conexion->prepare("SELECT id, activo FROM usuarios WHERE DNI=? AND rol='encargado' LIMIT 1");
        $stmt->bind_param("s", $d);
        $stmt->execute();
        $stmt->bind_result($id_found, $act_found);
        $exists = $stmt->fetch();
        $stmt->close();

        if ($exists) {
            if (intval($act_found) === 1) {
                redirect_with(['tipo' => $tipo, 'vista' => 'alta', 'estado' => $estado, 'q' => $q, 'code' => 'dup_activo', 'ok' => '0']);
            } else {
                // Reactivar + actualizar nombre/apellidos/contraseña
                // IMPORTANTE: en producción usa password_hash()
                $stmt = $conexion->prepare("UPDATE usuarios SET nombre=?, apellidos=?, contraseña=?, activo=1 WHERE id=? AND rol='encargado'");
                $stmt->bind_param("sssi", $n, $a, $c, $id_found);
                $ok_exec = $stmt->execute();
                $stmt->close();
                redirect_with(['tipo' => $tipo, 'vista' => 'lista', 'estado' => $estado, 'q' => $q, 'code' => $ok_exec ? 'react_ok_enc' : 'err_sql', 'ok' => $ok_exec ? '1' : '0']);
            }
        } else {
            // Insert nuevo encargado (considera password_hash() para producción)
            $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, apellidos, DNI, rol, contraseña, activo) VALUES (?, ?, ?, 'encargado', ?, 1)");
            $stmt->bind_param("ssss", $n, $a, $d, $c);
            $ok_exec = $stmt->execute();
            $stmt->close();
            redirect_with(['tipo' => $tipo, 'vista' => 'lista', 'estado' => $estado, 'q' => $q, 'code' => $ok_exec ? 'alta_ok_enc' : 'err_sql', 'ok' => $ok_exec ? '1' : '0']);
        }
    } else {
        redirect_with(['tipo' => 'trabajadores', 'vista' => 'lista', 'code' => 'sin_permiso', 'ok' => '0']);
    }
}

/* ============================
   CONSULTA LISTADO (SERVIDOR)
   ============================ */

if ($tipo === 'encargados' && $rol === 'administrador') {
    $titulo = "Gestionar Encargados";
    $base  = "FROM usuarios WHERE rol='encargado'";
    $cols  = "id, nombre, apellidos, DNI AS dni, rol, activo";
} else {
    $titulo = "Gestionar Trabajadores";
    $base  = "FROM trabajadores WHERE 1=1";
    $cols  = "id, nombre, apellidos, dni, activo";
}
// Filtro estado
if ($estado === 'activo') {
    $base .= " AND activo=1";
}
if ($estado === 'inactivo') {
    $base .= " AND activo=0";
}

// Filtro búsqueda (nombre/apellidos/dni)
$params = [];
$types = '';
if ($q !== '') {
    $base .= " AND (nombre LIKE ? OR apellidos LIKE ? OR dni LIKE ?)";
    $like = "%{$q}%";
    $params = [$like, $like, $like];
    $types  = 'sss';
}

$sql = "SELECT $cols $base ORDER BY id DESC";
$stmt = $conexion->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($titulo) ?> - Interempleo</title>
    <style>
       :root {
  --color-principal: #FF671D;
  --color-fondo: #FFFFFF;
  --color-texto: #333333;
  --color-borde: #CCCCCC;
  --color-input-bg: #F9F9F9;
}


        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--bg);
            color: var(--texto)
        }

        .topbar {
            background: var(--naranja);
            color: #fff;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .brand {
            font-weight: 700
        }

        .user {
            font-size: 14px
        }

        .wrap {
            max-width: 1000px;
            margin: 18px auto;
            padding: 0 16px
        }

        .actions {
            display: flex;
            gap: 10px;
            margin: 8px 0 12px
        }

        .btn {
            border-radius: 8px;
            padding: 10px 18px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }

        /* Botón activo (vista actual) */
        .btn-primary,
        .btn.active {
            background: var(--naranja);
            color: #fff;
            border-color: var(--naranja);
            box-shadow: 0 3px 10px rgba(255, 103, 29, 0.3);
        }

        .btn-primary:hover,
        .btn.active:hover {
            background: #ff7f3d;
            /* tono más claro */
            color: #fff;
            transform: none;
            /* sin moverse */
        }

        /* Botón inactivo */
        .btn-secondary {
            background: #fff;
            color: var(--naranja);
            border: 2px solid var(--naranja);
            box-shadow: none;
        }

        .btn-secondary:hover {
            background: #ffe8dc;
            /* tono suave que no se confunde */
            color: var(--naranja);
        }


        .panel {
            border: 1px solid var(--borde);
            border-radius: 12px;
            padding: 16px
        }

        .banner {
            padding: 10px 12px;
            border-radius: 10px;
            margin-bottom: 10px;
            font-weight: 700
        }

        .ok {
            background: #e8f8ee;
            color: #116c2f;
            border: 1px solid #bfe8cc
        }

        .err {
            background: #fdecec;
            color: #8a1f1f;
            border: 1px solid #f5bdbd
        }

        .filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 10px;
            align-items: center
        }

        .filters input,
        .filters select {
            border: 1px solid var(--borde);
            border-radius: 8px;
            padding: 8px;
            min-width: 220px
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        th,
        td {
            border: 1px solid var(--borde);
            padding: 10px;
            text-align: center
        }

        th {
            background: #f2f2f2;
            /* gris claro profesional */
            color: #333;
            /* texto oscuro legible */
            font-weight: 700;
        }


        tr:nth-child(even) {
            background: #fafafa
        }

        .estado {
            font-weight: 700
        }

        .act {
            color: #0a8f3a
        }

        .inact {
            color: #c72626
        }

        .pill {
            background: var(--naranja);
            color: #fff;
            border: none;
            border-radius: 999px;
            padding: 8px 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(255, 103, 29, .25);
            transition: filter .2s
        }

        .pill:hover {
            filter: brightness(1.03)
        }

        .form-alta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px
        }

        .form-alta input {
            border: 1px solid var(--borde);
            border-radius: 8px;
            padding: 10px;
            flex: 1 1 240px
        }

        .submit {
            flex: 0 0 auto
        }

        /* ===== MENÚ HAMBURGUESA ===== */
        .menu-container {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 1000;
        }

        .menu-toggle {
            font-size: 28px;
            cursor: pointer;
            color: white;
            background: var(--naranja);
            border-radius: 6px;
            padding: 6px 10px;
            transition: background 0.3s;
        }

        .menu-toggle:hover {
            background: #ff7f3d;
        }

        .menu-dropdown {
            display: none;
            position: absolute;
            top: 38px;
            left: 0;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            overflow: hidden;
        }

        .menu-dropdown a {
            padding: 0.5rem 0;
            color: var(--texto);
            text-decoration: none;
            border-bottom: 1px solid #eee;
            background-color: transparent;
        }


        .menu-dropdown a:hover {
            background: #f9f9f9;
        }

        .menu-dropdown a:last-child {
            border-bottom: none;
        }

        /* ===== BARRA SUPERIOR ===== */
        .barra-superior {
            background-color: var(--naranja);
            color: white;
            padding: 1.5rem 2rem;
            font-size: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .barra-superior p {
            margin: 0;
            font-weight: normal;
            font-size: 1.4rem;
        }

        .barra-superior span {
            font-weight: bold;
        }

       .menu-toggle {
  font-size: 1.8rem;
  cursor: pointer;
  margin-right: 1rem;
  user-select: none;
}

.menu-dropdown {
  display: none;
  flex-direction: column;
  position: absolute;
  top: 70px; /* ajusta según el alto de la barra */
  left: 1rem;
  background-color: white;
  border: 1px solid #ccc;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
  z-index: 9999;
  padding: 1rem;
  border-radius: 6px;
  min-width: 200px;
}

.menu-dropdown a {
  padding: 0.5rem 0;
  color: var(--color-texto);
  text-decoration: none;
  border-bottom: 1px solid #eee;
}

.menu-dropdown a:last-child {
  border-bottom: none;
}

.menu-dropdown a:hover {
  color: var(--color-principal);
}

/* Mostrar el menú cuando se activa */
.menu-dropdown.show {
  display: flex;
}

       

        .menu-dropdown a:last-child {
            border-bottom: none;
        }


        /* Responsive ajustes */
        @media (max-width: 768px) {
            .barra-superior {
                flex-direction: row;
                justify-content: flex-start;
                align-items: center;
                padding: 1rem;
                gap: 1rem;
            }

            .barra-superior p {
                font-size: 1.2rem;
            }
        }
    </style>
</head>

<!-- 🔎 Buscador en tiempo real (ignora tildes, busca por nombre + apellido o DNI) -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const buscador = document.querySelector('input[name="q"]');
        if (!buscador) return;

        function quitarTildes(str) {
            return str
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "") // elimina tildes
                .replace(/ñ/g, "n") // trata ñ como n
                .replace(/Ñ/g, "N")
                .toLowerCase()
                .trim();
        }

        buscador.addEventListener("keyup", function() {
            const texto = quitarTildes(this.value);
            const partes = texto.split(/\s+/); // divide en palabras: ej. "juan pe"
            const filas = document.querySelectorAll("table tbody tr");

            filas.forEach(fila => {
                const contenido = quitarTildes(fila.textContent);
                const coincide = partes.every(p => contenido.includes(p)); // todas las partes deben aparecer
                fila.style.display = coincide ? "" : "none";
            });
        });
    });
</script>





<body>
   <!-- 🔸 Barra superior unificada -->
<div class="barra-superior">
  <div name="en_linea" style="text-align:left">
    <div style="display:inline-block; width:10%; margin-right:40%; vertical-align:top;" class="menu-toggle" onclick="toggleMenu()">☰</div>
    <p style="text-align:center; display:inline-block; width:45%;"><span>Inter</span>empleo - Asistencia</p>
  </div>

  <div class="menu-dropdown" id="menuDropdown">
    <a href="gestionar-personal.php?tipo=trabajadores&vista=lista">Gestión de trabajadores</a>
    <?php
    if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador') {
        echo '<a href="gestionar-personal.php?tipo=encargados&vista=lista">Gestión de encargados</a>';
    }
    ?>
    <a href="exportar_excel.php">Exportar excel/PDF</a>
    <a href="cerrar_sesion.php">Cerrar sesión</a>
  </div>
</div>




    <div class="wrap">

        <div class="actions">
            <a class="btn <?= $vista === 'lista' ? 'btn-primary active' : 'btn-secondary' ?>" href="?tipo=<?= h($tipo) ?>&vista=lista&estado=<?= h($estado) ?>&q=<?= h($q) ?>">Ver Listado</a>
            <a class="btn <?= $vista === 'alta' ? 'btn-primary active' : 'btn-secondary' ?>" href="?tipo=<?= h($tipo) ?>&vista=alta&estado=<?= h($estado) ?>&q=<?= h($q) ?>">Dar de Alta</a>
        </div>




        <?php if ($code): ?>
            <div class="banner <?= $ok ? 'ok' : 'err' ?>"><?= h($MSG[$code] ?? 'Operación realizada.') ?></div>
        <?php endif; ?>

        <div class="panel">
            <?php if ($vista === 'alta'): ?>
                <form class="form-alta" method="POST">
                    <input type="hidden" name="accion" value="alta">
                    <input type="text" name="nombre" placeholder="Nombre" required>
                    <input type="text" name="apellidos" placeholder="Apellidos" required>
                    <input type="text" name="dni" placeholder="DNI" required>
                    <?php if ($tipo === 'encargados' && $rol === 'administrador'): ?>
                        <input type="password" name="contraseña" placeholder="Contraseña" required>
                    <?php endif; ?>
                    <button class="btn submit" type="submit">Guardar</button>
                </form>
            <?php else: ?>
                <!-- Filtros solo en "Ver Listado" -->
                <form class="filters" method="GET">
                    <input type="hidden" name="tipo" value="<?= h($tipo) ?>">
                    <input type="hidden" name="vista" value="lista">
                    <label>Estado:
                        <select name="estado" onchange="this.form.submit()">
                            <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                            <option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= $estado === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </label>
                    <input type="text" name="q" value="<?= h($q) ?>" placeholder="Buscador por DNI o Nombre + Apellido">
                    <button class="btn outline" type="submit">Filtrar</button>
                </form>

                <table>
                    <tr>
                        <th>Nombre</th>
                        <th>Apellidos</th>
                        <th>DNI</th>
                        <?php if ($tipo === 'encargados' && $rol === 'administrador'): ?>
                            <th>Rol</th>
                        <?php endif; ?>
                        <th>Estado</th>
                        <th>Dar de baja</th>
                    </tr>
                    <?php while ($fila = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?= h($fila['nombre']) ?></td>
                            <td><?= h($fila['apellidos']) ?></td>
                            <td><?= h($fila['dni']) ?></td>
                            <?php if ($tipo === 'encargados' && $rol === 'administrador'): ?>
                                <td><?= h($fila['rol']) ?></td>
                            <?php endif; ?>
                            <?php $activo = intval($fila['activo']) === 1; ?>
                            <td class="estado <?= $activo ? 'act' : 'inact' ?>"><?= $activo ? '● Activo' : '● Inactivo' ?></td>
                            <td>
                                <?php if ($activo): ?>
                                    <form method="POST" onsubmit="return confirm('¿Dar de baja este registro?');" style="margin:0;">
                                        <input type="hidden" name="accion" value="baja">
                                        <input type="hidden" name="id" value="<?= h($fila['id']) ?>">
                                        <button type="submit" class="pill">Dar de baja</button>
                                    </form>
                                <?php else: ?>
                                    <span style="opacity:.6;">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile;
                    $stmt->close(); ?>
                </table>
            <?php endif; ?>
        </div>

        <div style="text-align:center;margin:14px 0;">
            <a class="btn outline" href="index.php">← Volver al inicio</a>
        </div>
    </div>
    <script>
        function toggleMenu() {
            const menu = document.getElementById('menuDropdown');
            menu.classList.toggle('show');
        }

        // Cierra el menú si haces clic fuera
        document.addEventListener('click', function(e) {
            const menu = document.getElementById('menuDropdown');
            const toggle = document.querySelector('.menu-toggle');

            if (!menu.contains(e.target) && e.target !== toggle) {
                menu.classList.remove('show');
            }
        });
    </script>




</body>

</html>