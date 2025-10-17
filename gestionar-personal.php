<?php
// ===============================================
// 🔸 Control de acceso y configuración base
// ===============================================
include("validar_sesion.php");
include("conexion_bd.php");
include("csrf.php");

// Bandera de seguridad para las funciones
define('APP_VALID', true);
include("funciones/personal_funciones.php");

// ===============================================
// 🔐 Validación de sesión y permisos
// ===============================================
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'encargado'])) {
    header("Location: login_responsive.php");
    exit();
}

$rol = $_SESSION['rol'];
$nombre = $_SESSION['nombre'] ?? '';
$apellidos = $_SESSION['apellidos'] ?? '';
$nombre_completo = trim("$nombre $apellidos");

// Solo el administrador puede gestionar encargados
$__tipo_req = $_GET['tipo'] ?? $_POST['tipo'] ?? 'trabajadores';
if ($__tipo_req === 'encargados' && $rol !== 'administrador') {
    header('Location: gestionar-personal.php?tipo=trabajadores&code=perm_denegado');
    exit();
}

// ===============================================
// ⚙️ Parámetros de vista
// ===============================================
$tipo   = $_GET['tipo']   ?? 'trabajadores';
$estado = $_GET['estado'] ?? 'activo';
$q      = trim($_GET['q'] ?? '');
$orden  = $_GET['orden']  ?? 'recientes';

// Redirigir si no hay vista
if (!isset($_GET['vista'])) {
    header("Location: gestionar-personal.php?tipo=" . urlencode($tipo) . "&vista=lista&estado=" . urlencode($estado) . "&q=" . urlencode($q));
    exit();
}

$vista_param = trim($_GET['vista']);
$vista = match ($vista_param) {
    'ver_listado' => 'lista',
    'dar_alta'    => 'alta',
    default       => $vista_param
};

// ===============================================
// 💬 Sistema de mensajes
// ===============================================
$code = $_GET['code'] ?? '';
$ok   = ($_GET['ok'] ?? '') === '1';
$MSG = [
    'alta_ok_trab'   => 'Trabajador dado de alta correctamente.',
    'alta_ok_enc'    => 'Encargado dado de alta correctamente.',
    'react_ok_trab'  => 'Trabajador reactivado correctamente.',
    'react_ok_enc'   => 'Encargado reactivado correctamente.',
    'dup_activo'     => 'El DNI ya existe y está activo.',
    'baja_ok_trab'   => 'Trabajador dado de baja.',
    'baja_ok_enc'    => 'Encargado dado de baja.',
    'err_campos'     => 'Todos los campos son obligatorios.',
    'err_sql'        => 'Error al ejecutar la operación.',
    'sin_permiso'    => 'No tienes permiso para esta acción.'
];

function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function redirect_with($params) {
    header("Location: gestionar-personal.php?" . http_build_query($params));
    exit();
}

// ===============================================
// 🧩 Acciones POST (alta / baja)
// ===============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('CSRF token inválido.');
    }

    $accion = $_POST['accion'] ?? '';

    // 🔻 Dar de baja
    if ($accion === 'baja') {
        $id = intval($_POST['id'] ?? 0);
        $ok_exec = dar_de_baja($conexion, $tipo, $rol, $id);
        redirect_with([
            'tipo' => $tipo,
            'vista' => 'lista',
            'estado' => $estado,
            'q' => $q,
            'code' => $ok_exec ? "baja_ok_" . substr($tipo, 0, 3) : 'err_sql',
            'ok' => $ok_exec ? '1' : '0'
        ]);
    }

    // 🔺 Dar de alta
    if ($accion === 'alta') {
        $n = $_POST['nombre'] ?? '';
        $a = $_POST['apellidos'] ?? '';
        $d = $_POST['dni'] ?? '';
        $c = ($_POST['contraseña'] ?? null);

        $resultado = dar_de_alta($conexion, $tipo, $rol, $n, $a, $d, $c);
        $code_res = match ($resultado) {
            'nuevo'      => "alta_ok_" . substr($tipo, 0, 3),
            'reactivado' => "react_ok_" . substr($tipo, 0, 3),
            'duplicado'  => 'dup_activo',
            default      => 'err_sql'
        };
        redirect_with(['tipo' => $tipo, 'vista' => 'lista', 'code' => $code_res, 'ok' => $resultado ? '1' : '0']);
    }
}

// ===============================================
// 📋 Consulta del listado
// ===============================================
$res = obtener_listado($conexion, $tipo, $estado, $q, $orden, $rol);
$titulo = ($tipo === 'encargados') ? "Gestionar Encargados" : "Gestionar Trabajadores";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($titulo) ?> - Interempleo</title>
    <link rel="stylesheet" href="css/style-global.css">
    <link rel="stylesheet" href="css/modal.css">

</head>
<body>
    <?php include("header.php"); ?>

    <div class="wrap">
        <h2 class="titulo-seccion"><?= h($titulo) ?></h2>

        <div class="actions">
            <a class="btn <?= $vista === 'lista' ? 'btn-primary active' : 'btn-secondary' ?>" href="?tipo=<?= h($tipo) ?>&vista=lista">Ver Listado</a>
            <a class="btn <?= $vista === 'alta' ? 'btn-primary active' : 'btn-secondary' ?>" href="?tipo=<?= h($tipo) ?>&vista=alta">Dar de Alta</a>
        </div>

        <?php if ($code): ?>
            <div class="banner <?= $ok ? 'ok' : 'err' ?>"><?= h($MSG[$code] ?? 'Operación realizada.') ?></div>
        <?php endif; ?>

        <div class="panel">
            <?php if ($vista === 'alta'): ?>
                <!-- ==============================
                     FORMULARIO DAR DE ALTA
                ============================== -->
                <form class="form-alta" method="POST">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="accion" value="alta">

                    <input type="text" name="nombre" placeholder="Nombre" required oninput="this.value=this.value.toUpperCase()">
                    <input type="text" name="apellidos" placeholder="Apellidos" required oninput="this.value=this.value.toUpperCase()">
                    <input type="text" name="dni" placeholder="DNI" required oninput="this.value=this.value.toUpperCase()">

                    <?php if ($tipo === 'encargados' && $rol === 'administrador'): ?>
                        <div class="grupo-password">
                            <input type="password" name="contraseña" id="contraseña" placeholder="Contraseña" required>
                            <button type="button" class="toggle-pass" data-tooltip="Mostrar contraseña" onclick="togglePassword('contraseña', this)">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>

                        <div class="grupo-password">
                            <input type="password" name="confirmar_contraseña" id="confirmar_contraseña" placeholder="Confirmar contraseña" required>
                            <button type="button" class="toggle-pass" data-tooltip="Mostrar contraseña" onclick="togglePassword('confirmar_contraseña', this)">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    <?php endif; ?>

                    <button class="btn submit" type="submit">Guardar</button>
                </form>
            <?php else: ?>
                <!-- ==============================
                     LISTADO DE TRABAJADORES/ENCARGADOS
                ============================== -->
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

                    <input type="text" name="q" id="buscador" value="<?= h($q) ?>" placeholder="Buscar por DNI o Nombre" autocomplete="off">

                    <label>Ordenar:
                        <select name="orden" onchange="this.form.submit()">
                            <option value="recientes" <?= ($orden === 'recientes') ? 'selected' : '' ?>>Más recientes</option>
                            <option value="alfabetico" <?= ($orden === 'alfabetico') ? 'selected' : '' ?>>A-Z (alfabético)</option>
                        </select>
                    </label>
                </form>

                <table>
                    <tr>
                        <th>Nombre</th>
                        <th>Apellidos</th>
                        <th>DNI</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                    <?php while ($fila = $res->fetch_assoc()): ?>
                        <?php $activo = intval($fila['activo']) === 1; ?>
                        <tr>
                            <td><?= h($fila['nombre']) ?></td>
                            <td><?= h($fila['apellidos']) ?></td>
                            <td><?= h($fila['dni']) ?></td>
                            <td class="estado <?= $activo ? 'act' : 'inact' ?>"><?= $activo ? '● Activo' : '● Inactivo' ?></td>
                            <td>
                                <?php if ($activo): ?>
                                    <form method="POST" onsubmit="return abrirModalBaja(this, '<?= addslashes(h($fila['nombre'].' '.$fila['apellidos'])) ?>');">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="accion" value="baja">
                                        <input type="hidden" name="id" value="<?= h($fila['id']) ?>">
                                        <button type="submit" class="pill">Dar de baja</button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" onsubmit="return abrirModalAlta(this, '<?= addslashes(h($fila['nombre'].' '.$fila['apellidos'])) ?>');">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
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
                    <?php endwhile; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <?php include("footer.php"); ?>
    <script src="js/gestionar-personal.js"></script>
</body>
</html>
