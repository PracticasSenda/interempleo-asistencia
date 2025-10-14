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
    $cols  = "id, nombre, apellidos, DNI AS dni, activo";
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
        /* =========================================================
   🎨 VARIABLES Y ESTILOS BASE
   ========================================================= */
        :root {
            --naranja: #FF671D;
            --borde: #DDDDDD;
            --texto: #333;
            --bg: #fff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: var(--bg);
            color: var(--texto);
        }

        /* =========================================================
   🧭 ESTRUCTURA GENERAL
   ========================================================= */
        .wrap {
            max-width: 1000px;
            margin: 18px auto;
            padding: 0 16px;
        }

        /* =========================================================
   🔸 BARRA SUPERIOR / ENCABEZADO
   ========================================================= */
        .barra-superior {
            background-color: var(--naranja);
            color: white;
            padding: 1.2rem 2rem;
            font-size: 1.4rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .contenedor-barra {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .lado-izquierdo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .lado-izquierdo p {
            margin: 0;
            font-size: 1.4rem;
        }

        .lado-izquierdo span {
            font-weight: bold;
        }

        .bienvenida {
            font-size: 1rem;
            font-weight: bold;
            text-align: right;
            white-space: nowrap;
        }

        /* =========================================================
   🍔 MENÚ HAMBURGUESA Y DESPLEGABLE
   ========================================================= */
        .menu-toggle {
            font-size: 1.8rem;
            cursor: pointer;
            background: none;
            border: none;
            color: white;
            transition: transform 0.2s;
        }

        .menu-toggle:hover {
            transform: scale(1.15);
        }

        .menu-dropdown {
            display: none;
            flex-direction: column;
            position: absolute;
            top: 100%;
            left: 2rem;
            background: #fff;
            border: 1px solid #ddd;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            overflow: hidden;
            animation: fadeIn 0.2s ease;
            min-width: 230px;
            z-index: 9999;
        }

        .menu-dropdown.show {
            display: flex;
        }

        .menu-dropdown a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            border-bottom: 1px solid #eee;
            font-weight: 500;
        }

        .menu-dropdown a:hover {
            background-color: #f9f9f9;
            color: var(--naranja);
        }

        .menu-dropdown a:last-child {
            border-bottom: none;
        }

        /* Animación del menú */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* =========================================================
   ⚙️ BOTONES Y ACCIONES
   ========================================================= */
        .actions {
            display: flex;
            gap: 10px;
            margin: 8px 0 12px;
            flex-wrap: wrap;
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
            font-size: 1rem;
        }

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
            color: #fff;
        }

        .btn-secondary {
            background: #fff;
            color: var(--naranja);
            border: 2px solid var(--naranja);
        }

        .btn-secondary:hover {
            background: #ffe8dc;
            color: var(--naranja);
        }

        /* =========================================================
   📋 FORMULARIOS Y PANELES
   ========================================================= */
        .panel {
            border: 1px solid var(--borde);
            border-radius: 12px;
            padding: 16px;
            overflow-x: auto;
            /* permite scroll en móvil */
        }

        .form-alta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .form-alta input {
            border: 1px solid var(--borde);
            border-radius: 8px;
            padding: 10px;
            flex: 1 1 240px;
        }

        .submit {
            flex: 0 0 auto;
        }

        /* =========================================================
   ✅ MENSAJES
   ========================================================= */
        .banner {
            padding: 10px 12px;
            border-radius: 10px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .ok {
            background: #e8f8ee;
            color: #116c2f;
            border: 1px solid #bfe8cc;
        }

        .err {
            background: #fdecec;
            color: #8a1f1f;
            border: 1px solid #f5bdbd;
        }

        /* =========================================================
   🔍 FILTROS Y BUSCADOR
   ========================================================= */
        .filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 10px;
            align-items: center;
        }

        .filters input,
        .filters select {
            border: 1px solid var(--borde);
            border-radius: 8px;
            padding: 8px;
            min-width: 220px;
            font-size: 1rem;
        }

        /* =========================================================
   📊 TABLAS Y ESTADOS
   ========================================================= */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1rem;
        }

        th,
        td {
            border: 1px solid var(--borde);
            padding: 10px;
            text-align: center;
        }

        th {
            background: #f2f2f2;
            color: #333;
            font-weight: 700;
        }

        tr:nth-child(even) {
            background: #fafafa;
        }

        .estado {
            font-weight: 700;
        }

        .act {
            color: #0a8f3a;
        }

        .inact {
            color: #c72626;
        }

        /* =========================================================
   🟠 BOTÓN "DAR DE BAJA"
   ========================================================= */
        .pill {
            background: var(--naranja);
            color: #fff;
            border: none;
            border-radius: 999px;
            padding: 8px 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(255, 103, 29, .25);
            transition: filter .2s;
        }

        .pill:hover {
            filter: brightness(1.03);
        }

        /* =========================================================
   📱 RESPONSIVE - TABLETS Y MÓVILES
   ========================================================= */
        @media (max-width: 900px) {
            .barra-superior {
                flex-wrap: wrap;
                padding: 1rem;
                gap: 0.5rem;
            }

            .bienvenida {
                display: none;
            }

            .actions {
                flex-direction: column;
            }

            table {
                font-size: 0.9rem;
            }

            th,
            td {
                padding: 8px;
            }

            .filters input,
            .filters select {
                min-width: 100%;
            }
        }

        @media (max-width: 600px) {
            .barra-superior {
                flex-direction: column;
                align-items: flex-start;
            }

            .lado-izquierdo p {
                font-size: 1.2rem;
            }

            .actions {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                width: 100%;
            }

            table {
                font-size: 0.85rem;
            }

            th,
            td {
                padding: 6px;
            }
        }


        /* === AJUSTE VISUAL PARA FILTROS === */
        .filters {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            justify-content: flex-start;
        }

        .filters label {
            font-weight: bold;
            font-size: 0.95rem;
        }

        .filters select,
        .filters input {
            border: 1px solid var(--borde);
            border-radius: 6px;
            padding: 8px;
            font-size: 0.95rem;
        }

        .filters input {
            flex: 1;
            min-width: 180px;
        }




        /* 🔹 En pantallas pequeñas: buscador y botón a la misma línea */
        @media (max-width: 768px) {
            .filters {
                flex-direction: column;
                align-items: stretch;
            }

            .filters select,
            .filters input {
                width: 100%;
            }
        }

        /* Diferenciar visualmente el modal de alta */
        #modalAlta .modal-box h3 {
            color: #28a745;
            /* verde */
        }

        #modalAlta .btn-confirmar {
            background: #28a745;
        }

        #modalAlta .btn-confirmar:hover {
            background: #33c754;
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
    <div class="barra-superior">
        <div class="contenedor-barra">
            <div class="lado-izquierdo">
                <button class="menu-toggle" onclick="toggleMenu()">☰</button>
                <p><span>Inter</span>empleo - Gestionar Personal</p>
            </div>
            <div class="bienvenida">
                Bienvenido, <?= htmlspecialchars($nombre_completo) ?>
            </div>
        </div>

        <div class="menu-dropdown" id="menuDropdown">
            <a href="gestionar-personal.php?tipo=trabajadores&vista=lista">Gestión de trabajadores</a>
            <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador'): ?>
                <a href="gestionar-personal.php?tipo=encargados&vista=lista">Gestión de encargados</a>
            <?php endif; ?>
            <a href="exportar_excel_pdf.php">Exportar Excel/PDF</a>
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
                <form class="filters" method="GET" onsubmit="return false;">
                    <input type="hidden" name="tipo" value="<?= h($tipo) ?>">
                    <input type="hidden" name="vista" value="lista">

                    <label>Estado:
                        <select name="estado" onchange="this.form.submit()">
                            <option value="todos" <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
                            <option value="activo" <?= $estado === 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= $estado === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </label>

                    <div style="position: relative; flex:1;">
                        <input
                            type="text"
                            name="q"
                            id="buscador"
                            value="<?= h($q) ?>"
                            placeholder="Buscador por DNI, Nombre o Apellido"
                            autocomplete="off"
                            style="width:100%;">
                    </div>

                </form>


                <table>
                    <tr>
                        <th>Nombre</th>
                        <th>Apellidos</th>
                        <th>DNI</th>
                        <th>Estado</th>
                        <th>Dar de baja</th>
                    </tr>
                    <?php while ($fila = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?= h($fila['nombre']) ?></td>
                            <td><?= h($fila['apellidos']) ?></td>
                            <td><?= h($fila['dni']) ?></td>

                            <?php $activo = intval($fila['activo']) === 1; ?>
                            <td class="estado <?= $activo ? 'act' : 'inact' ?>"><?= $activo ? '● Activo' : '● Inactivo' ?></td>
                            <td>
                                <?php if ($activo): ?>
                                    <form method="POST"
                                        onsubmit="return abrirModalBaja(this, '<?= h($fila['nombre']) ?> <?= h($fila['apellidos']) ?>');"
                                        style="margin:0;">
                                        <input type="hidden" name="accion" value="baja">
                                        <input type="hidden" name="id" value="<?= h($fila['id']) ?>">
                                        <button type="submit" class="pill">Dar de baja</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST"
                                        onsubmit="return abrirModalAlta(this, '<?= h($fila['nombre']) ?> <?= h($fila['apellidos']) ?>');"
                                        style="margin:0;">
                                        <input type="hidden" name="accion" value="alta">
                                        <input type="hidden" name="nombre" value="<?= h($fila['nombre']) ?>">
                                        <input type="hidden" name="apellidos" value="<?= h($fila['apellidos']) ?>">
                                        <input type="hidden" name="dni" value="<?= h($fila['dni']) ?>">
                                        <?php if ($tipo === 'encargados' && $rol === 'administrador'): ?>
                                            <input type="hidden" name="contraseña" value="1234">
                                        <?php endif; ?>
                                        <button type="submit" class="pill" style="background:#28a745;">Reactivar</button>
                                    </form>
                                <?php endif; ?>

                            </td>
                        </tr>
                    <?php endwhile;
                    $stmt->close(); ?>
                </table>
            <?php endif; ?>
        </div>
        <div style="height: 35px;"></div>

        <footer style="
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  background: #fff;
  text-align: center;
  padding: 1rem;
  color: #888;
  font-size: 0.9rem;
  border-top: 1px solid #eee;
  z-index: 100;
">
            © 2025 Interempleo · Todos los derechos reservados
        </footer>



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

        <script>
            // 🔍 Buscador en tiempo real (ignora tildes)
            document.addEventListener("DOMContentLoaded", function() {
                const buscador = document.getElementById("buscador");
                if (!buscador) return;

                function quitarTildes(str) {
                    return str
                        .normalize("NFD")
                        .replace(/[\u0300-\u036f]/g, "")
                        .replace(/ñ/g, "n")
                        .replace(/Ñ/g, "N")
                        .toLowerCase()
                        .trim();
                }

                buscador.addEventListener("keyup", function() {
                    const texto = quitarTildes(this.value);
                    const partes = texto.split(/\s+/);
                    const filas = document.querySelectorAll("table tbody tr");

                    filas.forEach(fila => {
                        const contenido = quitarTildes(fila.textContent);
                        const coincide = partes.every(p => contenido.includes(p));
                        fila.style.display = coincide ? "" : "none";
                    });
                });
            });
        </script>
        <script>
            function confirmarBaja(nombreCompleto) {
                return confirm(`¿Estás seguro de que deseas dar de baja a ${nombreCompleto}?`);
            }
        </script>
        <!-- 🟠 MODAL DE CONFIRMACIÓN DE BAJA -->
        <div id="modalBaja" class="modal-overlay" style="display:none;">
            <div class="modal-box">
                <h3>Confirmar acción</h3>
                <p id="modalTexto"></p>
                <div class="modal-buttons">
                    <button id="btnConfirmar" class="btn-confirmar">Sí, dar de baja</button>
                    <button id="btnCancelar" class="btn-cancelar">Cancelar</button>
                </div>
            </div>
        </div>

        <!-- 🟢 MODAL DE CONFIRMACIÓN DE ALTA -->
        <div id="modalAlta" class="modal-overlay" style="display:none;">
            <div class="modal-box">
                <h3>Confirmar reactivación</h3>
                <p id="modalTextoAlta"></p>
                <div class="modal-buttons">
                    <button id="btnConfirmarAlta" class="btn-confirmar">Sí, dar de alta</button>
                    <button id="btnCancelarAlta" class="btn-cancelar">Cancelar</button>
                </div>
            </div>
        </div>

        <style>
            /* Fondo oscuro difuminado */
            .modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.45);
                backdrop-filter: blur(2px);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 99999;
            }

            /* Caja blanca centrada */
            .modal-box {
                background: #fff;
                border-radius: 10px;
                padding: 2rem;
                width: 90%;
                max-width: 400px;
                text-align: center;
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.25);
                animation: fadeIn 0.25s ease;
            }

            .modal-box h3 {
                margin-top: 0;
                color: #FF671D;
                font-size: 1.3rem;
            }

            .modal-box p {
                font-size: 1.1rem;
                margin: 1rem 0;
            }

            /* Botones */
            .modal-buttons {
                display: flex;
                justify-content: center;
                gap: 10px;
                flex-wrap: wrap;
            }

            .btn-confirmar {
                background: #FF671D;
                color: white;
                border: none;
                padding: 0.6rem 1.2rem;
                border-radius: 6px;
                font-weight: bold;
                cursor: pointer;
                transition: background 0.2s;
            }

            .btn-confirmar:hover {
                background: #ff8842;
            }

            .btn-cancelar {
                background: #ccc;
                border: none;
                padding: 0.6rem 1.2rem;
                border-radius: 6px;
                cursor: pointer;
                font-weight: bold;
            }

            .btn-cancelar:hover {
                background: #bdbdbd;
            }

            /* Verde para el modal de alta */
            #modalAlta .modal-box h3 {
                color: #28a745;
            }

            #modalAlta .btn-confirmar {
                background: #28a745;
            }

            #modalAlta .btn-confirmar:hover {
                background: #33c754;
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        </style>

        <script>
            // Variables para guardar qué formulario está pendiente
            let formPendiente = null;

            // Modal de BAJA
            function abrirModalBaja(form, nombreCompleto) {
                formPendiente = form;
                document.getElementById("modalTexto").innerHTML =
                    `¿Estás seguro de que deseas dar de baja a ${nombreCompleto}?`;
                document.getElementById("modalBaja").style.display = "flex";
                return false;
            }

            // Modal de ALTA
            let formAltaPendiente = null;

            function abrirModalAlta(form, nombreCompleto) {
                formAltaPendiente = form;
                document.getElementById("modalTextoAlta").innerHTML =
                    `¿Deseas volver a dar de alta a ${nombreCompleto}?`;
                document.getElementById("modalAlta").style.display = "flex";
                return false;
            }

            // Botones de confirmación
            document.getElementById("btnConfirmar").onclick = function() {
                if (formPendiente) formPendiente.submit();
                cerrarModal("modalBaja");
            };

            document.getElementById("btnCancelar").onclick = function() {
                cerrarModal("modalBaja");
            };

            document.getElementById("btnConfirmarAlta").onclick = function() {
                if (formAltaPendiente) formAltaPendiente.submit();
                cerrarModal("modalAlta");
            };

            document.getElementById("btnCancelarAlta").onclick = function() {
                cerrarModal("modalAlta");
            };

            // Función genérica para cerrar modales
            function cerrarModal(id) {
                document.getElementById(id).style.display = "none";
            }
        </script>

</body>

</html>