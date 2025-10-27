<?php
// ===============================================
// 🔸 Control de acceso y configuración base
// ===============================================
include(__DIR__ . '/../auth/validar_sesion.php');
include(__DIR__ . '/../config/db.php');
include(__DIR__ . '/../config/csrf.php');

// Bandera de seguridad para las funciones
define('APP_VALID', true);
include(__DIR__ . '/../funciones/funciones.php');
include(__DIR__ . '/../funciones/personal_funciones.php');


// ===============================================
// 🔐 Validación de sesión y permisos
// ===============================================
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['administrador', 'encargado'])) {
    header("Location: ../auth/login.php");
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
    'err_pass'       => 'Las contraseñas no coinciden. Por favor, verifícalas.',
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

function h($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

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

    /* =========================================================
    🔻 DAR DE BAJA
    ========================================================== */
    if ($accion === 'baja') {
        $id = intval($_POST['id'] ?? 0);
        $resultado = dar_de_baja($conexion, $tipo, $rol, $id);

        // Interpretar resultado real devuelto por la función
        if ($resultado === 'baja_ok') {
            $code_res = ($tipo === 'trabajadores') ? 'baja_ok_trab' : 'baja_ok_enc';
            $ok_flag = true;
        } elseif ($resultado === 'sin_permiso') {
            $code_res = 'sin_permiso';
            $ok_flag = false;
        } else {
            $code_res = 'err_sql';
            $ok_flag = false;
        }

        redirect_with([
            'tipo'   => $tipo,
            'vista'  => 'lista',
            'estado' => $estado,
            'q'      => $q,
            'code'   => $code_res,
            'ok'     => $ok_flag ? '1' : '0'
        ]);
    }

    /* =========================================================
    🔺 DAR DE ALTA / REACTIVAR
    ========================================================== */
    if ($accion === 'alta') {
        $n = trim($_POST['nombre'] ?? '');
        $a = trim($_POST['apellidos'] ?? '');
        $d = trim($_POST['dni'] ?? '');
        $c = $_POST['contraseña'] ?? null;
        $confirm = $_POST['confirmar_contraseña'] ?? null;

        // Detectar si es reactivación (desde listado)
        $desdeListado = isset($_POST['id']) 
            || (isset($_POST['dni']) && !empty($_POST['dni']) && isset($_POST['contraseña']) && $_POST['contraseña'] === '1234');

        // Validaciones solo para alta nueva
        if (!$desdeListado) {
            if ($n === '' || $a === '' || $d === '') {
                redirect_with([
                    'tipo' => $tipo,
                    'vista' => 'alta',
                    'code' => 'err_campos',
                    'ok' => '0'
                ]);
            }

            // Validación específica para encargados (solo admin)
            if ($tipo === 'encargados' && $rol === 'administrador') {
                if (empty($c) || empty($confirm)) {
                    redirect_with([
                        'tipo' => $tipo,
                        'vista' => 'alta',
                        'code' => 'err_campos',
                        'ok' => '0'
                    ]);
                }
                if ($c !== $confirm) {
                    redirect_with([
                        'tipo' => $tipo,
                        'vista' => 'alta',
                        'code' => 'err_pass',
                        'ok' => '0'
                    ]);
                }
            }
        }

        // Ejecutar alta o reactivación
        $resultado = dar_de_alta($conexion, $tipo, $rol, $n, $a, $d, $c);

        // Mapeo de resultados a mensajes
        switch ($resultado) {
            case 'nuevo':
                $code_res = ($tipo === 'trabajadores') ? 'alta_ok_trab' : 'alta_ok_enc';
                $ok_flag = true;
                break;
            case 'reactivado':
                $code_res = ($tipo === 'trabajadores') ? 'react_ok_trab' : 'react_ok_enc';
                $ok_flag = true;
                break;
            case 'duplicado':
                $code_res = 'dup_activo';
                $ok_flag = false;
                break;
            case 'campos_vacios':
                $code_res = 'err_campos';
                $ok_flag = false;
                break;
            case 'sin_permiso':
                $code_res = 'sin_permiso';
                $ok_flag = false;
                break;
            case 'err_sql':
            case false:
            default:
                $code_res = 'err_sql';
                $ok_flag = false;
                break;
        }

        redirect_with([
            'tipo'  => $tipo,
            'vista' => 'lista',
            'code'  => $code_res,
            'ok'    => $ok_flag ? '1' : '0'
        ]);
    }
}

$res = obtener_listado($conexion, $tipo, $estado, $q, $orden, $rol);
$titulo = ($tipo === 'encargados') ? "Gestionar Encargados" : "Gestionar Trabajadores";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($titulo) ?> - Interempleo</title>
    <link rel="stylesheet" href="../css/style-global.css">
    <link rel="stylesheet" href="../css/modal.css">
</head>

<body>
<?php include(__DIR__ . '/header.php'); ?>

<div class="wrap">
    <h2 class="titulo-seccion"><?= h($titulo) ?></h2>

    <div class="actions">
        <a class="btn <?= $vista === 'lista' ? 'btn-primary active' : 'btn-secondary' ?>" href="?tipo=<?= h($tipo) ?>&vista=lista">Ver Listado</a>
        <a class="btn <?= $vista === 'alta' ? 'btn-primary active' : 'btn-secondary' ?>" href="?tipo=<?= h($tipo) ?>&vista=alta">Dar de Alta</a>
    </div>

    <?php if ($code): ?>
        <?php
        $msg_texto = $MSG[$code] ?? 'Ocurrió un error inesperado.';
        if (str_starts_with($code, 'alta_ok_') || str_starts_with($code, 'react_ok_') || str_starts_with($code, 'baja_ok_')) {
            $clase = 'ok';
        } elseif ($code === 'dup_activo' || str_starts_with($code, 'perm_')) {
            $clase = 'warn';
        } else {
            $clase = 'err';
        }

        $icono = match ($clase) {
            'ok' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>',
            'warn' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a1 1 0 0 0 .86 1.5h18.64a1 1 0 0 0 .86-1.5L13.71 3.86a1 1 0 0 0-1.72 0z"/></svg>',
            default => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>'
        };
        ?>
        <div class="banner <?= $clase ?>">
            <?= $icono ?> <?= h($msg_texto) ?>
            <button type="button" class="close-banner" aria-label="Cerrar">✖</button>
        </div>
    <?php endif; ?>

    <div class="panel">
        <?php if ($vista === 'alta'): ?>
            <!-- FORMULARIO DAR DE ALTA -->
            <form class="form-alta" method="POST">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="accion" value="alta">

                <input type="text" name="nombre" id="nombre" placeholder="Nombre" required oninput="this.value=this.value.toUpperCase()">
                <input type="text" name="apellidos" id="apellidos" placeholder="Apellidos" required oninput="this.value=this.value.toUpperCase()">
                <input type="text" name="dni" id="dni" placeholder="DNI" required oninput="this.value=this.value.toUpperCase()">

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
                        <button type="button" class="toggle-pass" onclick="togglePassword('confirmar_contraseña', this)">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>

                <button class="btn submit" type="button" onclick="abrirModalAltaNueva(this.form)">Guardar</button>
            </form>
        <?php else: ?>
            <!-- LISTADO -->
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
                                <form method="POST" onsubmit="return abrirModalBaja(this, '<?= addslashes(h($fila['nombre'] . ' ' . $fila['apellidos'])) ?>');">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="accion" value="baja">
                                    <input type="hidden" name="id" value="<?= h($fila['id']) ?>">
                                    <button type="submit" class="pill">Dar de baja</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" onsubmit="return abrirModalAlta(this, '<?= addslashes(h($fila['nombre'] . ' ' . $fila['apellidos'])) ?>');">
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

<!-- MODALES -->
<?php include(__DIR__ . '/modales-personal.php'); ?>

<?php include(__DIR__ . '/footer.php'); ?>
<script src="../js/modal.js"></script>
<script src="../js/gestionar-personal.js"></script>
<script src="../js/alertas.js"></script>
</body>
</html>
