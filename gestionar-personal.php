<?php
// gestionar-personal.php
include("validar_sesion.php");
include("conexion_bd.php");
include("csrf.php");

// Protección CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!function_exists('csrf_check') || !csrf_check($_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('CSRF token inválido.');
    }
}

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

// Parámetros UI
$tipo   = $_GET['tipo']   ?? 'trabajadores';
$estado = $_GET['estado'] ?? 'activo';
$q      = trim($_GET['q'] ?? '');

// Redirigir si no hay vista
if (!isset($_GET['vista'])) {
    header("Location: gestionar-personal.php?tipo=" . urlencode($tipo) . "&vista=lista&estado=" . urlencode($estado) . "&q=" . urlencode($q));
    exit();
}

// Normalizar vistas
$vista_param = trim($_GET['vista']);
$vista = match ($vista_param) {
    'ver_listado' => 'lista',
    'dar_alta' => 'alta',
    default => $vista_param
};

// Evitar acceso del encargado a encargados
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

function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function redirect_with($params)
{
    header("Location: gestionar-personal.php?" . http_build_query($params));
    exit();
}

/* ============================
   ACCIONES POST (ALTA / BAJA)
   ============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'baja') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            if ($tipo === 'trabajadores') {
                $stmt = $conexion->prepare("UPDATE trabajadores SET activo=0 WHERE id=?");
                $stmt->bind_param("i", $id);
                $ok_exec = $stmt->execute();
                $stmt->close();
                redirect_with(['tipo' => $tipo, 'vista' => 'lista', 'estado' => $estado, 'q' => $q,
                    'code' => $ok_exec ? 'baja_ok_trab' : 'err_sql', 'ok' => $ok_exec ? '1' : '0']);
            } elseif ($tipo === 'encargados' && $rol === 'administrador') {
                $stmt = $conexion->prepare("UPDATE usuarios SET activo=0 WHERE id=? AND rol='encargado'");
                $stmt->bind_param("i", $id);
                $ok_exec = $stmt->execute();
                $stmt->close();
                redirect_with(['tipo' => $tipo, 'vista' => 'lista', 'estado' => $estado, 'q' => $q,
                    'code' => $ok_exec ? 'baja_ok_enc' : 'err_sql', 'ok' => $ok_exec ? '1' : '0']);
            }
        }
    }

    if ($accion === 'alta') {
        $n = strtoupper(trim($_POST['nombre'] ?? ''));
        $a = strtoupper(trim($_POST['apellidos'] ?? ''));
        $d = strtoupper(trim($_POST['dni'] ?? ''));
        $c = ($tipo === 'encargados') ? trim($_POST['contraseña'] ?? '') : '';
        $hash = ($tipo === 'encargados' && !empty($c)) ? password_hash($c, PASSWORD_DEFAULT) : '';

        if ($n === '' || $a === '' || $d === '' || ($tipo === 'encargados' && $c === '')) {
            redirect_with(['tipo' => $tipo, 'vista' => 'alta', 'code' => 'err_campos', 'ok' => '0']);
        }

        if ($tipo === 'trabajadores') {
            $stmt = $conexion->prepare("SELECT id, activo FROM trabajadores WHERE dni=? LIMIT 1");
            $stmt->bind_param("s", $d);
            $stmt->execute();
            $stmt->bind_result($id_found, $act_found);
            $exists = $stmt->fetch();
            $stmt->close();

            if ($exists) {
                if (intval($act_found) === 1) {
                    redirect_with(['tipo' => $tipo, 'vista' => 'alta', 'code' => 'dup_activo', 'ok' => '0']);
                } else {
                    $stmt = $conexion->prepare("UPDATE trabajadores SET nombre=?, apellidos=?, activo=1 WHERE id=?");
                    $stmt->bind_param("ssi", $n, $a, $id_found);
                    $ok_exec = $stmt->execute();
                    $stmt->close();
                    redirect_with(['tipo' => $tipo, 'vista' => 'lista', 'code' => $ok_exec ? 'react_ok_trab' : 'err_sql', 'ok' => $ok_exec ? '1' : '0']);
                }
            } else {
                $stmt = $conexion->prepare("INSERT INTO trabajadores (nombre, apellidos, dni, activo) VALUES (?, ?, ?, 1)");
                $stmt->bind_param("sss", $n, $a, $d);
                $ok_exec = $stmt->execute();
                $stmt->close();
                redirect_with(['tipo' => $tipo, 'vista' => 'lista', 'code' => $ok_exec ? 'alta_ok_trab' : 'err_sql', 'ok' => $ok_exec ? '1' : '0']);
            }
        } elseif ($tipo === 'encargados' && $rol === 'administrador') {
            $stmt = $conexion->prepare("SELECT id, activo FROM usuarios WHERE DNI=? AND rol='encargado' LIMIT 1");
            $stmt->bind_param("s", $d);
            $stmt->execute();
            $stmt->bind_result($id_found, $act_found);
            $exists = $stmt->fetch();
            $stmt->close();

            if ($exists) {
                if (intval($act_found) === 1) {
                    redirect_with(['tipo' => $tipo, 'vista' => 'alta', 'code' => 'dup_activo', 'ok' => '0']);
                } else {
                    $stmt = $conexion->prepare("UPDATE usuarios SET nombre=?, apellidos=?, contraseña=?, activo=1 WHERE id=? AND rol='encargado'");
                    $stmt->bind_param("sssi", $n, $a, $hash, $id_found);
                    $ok_exec = $stmt->execute();
                    $stmt->close();
                    redirect_with(['tipo' => $tipo, 'vista' => 'lista', 'code' => $ok_exec ? 'react_ok_enc' : 'err_sql', 'ok' => $ok_exec ? '1' : '0']);
                }
            } else {
                $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, apellidos, DNI, rol, contraseña, activo) VALUES (?, ?, ?, 'encargado', ?, 1)");
                $stmt->bind_param("ssss", $n, $a, $d, $hash);
                $ok_exec = $stmt->execute();
                $stmt->close();
                redirect_with(['tipo' => $tipo, 'vista' => 'lista', 'code' => $ok_exec ? 'alta_ok_enc' : 'err_sql', 'ok' => $ok_exec ? '1' : '0']);
            }
        }
    }
}

/* ============================
   CONSULTA LISTADO
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

if ($estado === 'activo') $base .= " AND activo=1";
if ($estado === 'inactivo') $base .= " AND activo=0";

$params = [];
$types = '';
if ($q !== '') {
    $base .= " AND (nombre LIKE ? OR apellidos LIKE ? OR dni LIKE ?)";
    $like = "%{$q}%";
    $params = [$like, $like, $like];
    $types  = 'sss';
}

$orden = $_GET['orden'] ?? 'recientes';
$sql = ($orden === 'alfabetico')
    ? "SELECT $cols $base ORDER BY nombre ASC, apellidos ASC"
    : "SELECT $cols $base ORDER BY id DESC";

$stmt = $conexion->prepare($sql);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($titulo) ?> - Interempleo</title>
    <link rel="stylesheet" href="css/style-global.css">
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
            <form class="form-alta" method="POST">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="accion" value="alta">
                <input type="text" name="nombre" placeholder="Nombre" required oninput="this.value=this.value.toUpperCase()">
                <input type="text" name="apellidos" placeholder="Apellidos" required oninput="this.value=this.value.toUpperCase()">
                <input type="text" name="dni" placeholder="DNI" required oninput="this.value=this.value.toUpperCase()">
               <?php if ($tipo === 'encargados' && $rol === 'administrador'): ?>
    <div class="grupo-password">
        <input type="password" name="contraseña" id="contraseña" placeholder="Contraseña" required>
        <button type="button" class="toggle-pass" onclick="togglePassword('contraseña', this)">👁️</button>
    </div>

    <div class="grupo-password">
        <input type="password" name="confirmar_contraseña" id="confirmar_contraseña" placeholder="Confirmar contraseña" required>
        <button type="button" class="toggle-pass" onclick="togglePassword('confirmar_contraseña', this)">👁️</button>
    </div>
<?php endif; ?>

                <button class="btn submit" type="submit">Guardar</button>
            </form>
        <?php else: ?>
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
                    <tr>
                        <td><?= h($fila['nombre']) ?></td>
                        <td><?= h($fila['apellidos']) ?></td>
                        <td><?= h($fila['dni']) ?></td>
                        <?php $activo = intval($fila['activo']) === 1; ?>
                        <td class="estado <?= $activo ? 'act' : 'inact' ?>"><?= $activo ? '● Activo' : '● Inactivo' ?></td>
                        <td>
                            <?php if ($activo): ?>
                                <form method="POST" onsubmit="return abrirModalBaja(this, '<?= addslashes(h($fila['nombre'].' '.$fila['apellidos'])) ?>');" style="margin:0;">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="accion" value="baja">
                                    <input type="hidden" name="id" value="<?= h($fila['id']) ?>">
                                    <button type="submit" class="pill">Dar de baja</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" onsubmit="return abrirModalAlta(this, '<?= addslashes(h($fila['nombre'].' '.$fila['apellidos'])) ?>');" style="margin:0;">
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
                <?php endwhile; $stmt->close(); ?>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Modales -->
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

<script>
// Buscador en tiempo real
document.addEventListener("DOMContentLoaded", function() {
    const buscador = document.getElementById("buscador");
    if (!buscador) return;
    function quitarTildes(str) {
        return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "")
                  .replace(/ñ/g, "n").replace(/Ñ/g, "N").toLowerCase().trim();
    }
    buscador.addEventListener("keyup", function() {
        const texto = quitarTildes(this.value);
        const partes = texto.split(/\s+/);
        const filas = document.querySelectorAll("table tbody tr");
        filas.forEach(fila => {
            const contenido = quitarTildes(fila.textContent);
            fila.style.display = partes.every(p => contenido.includes(p)) ? "" : "none";
        });
    });
});

// Modales
let formPendiente = null;
function abrirModalBaja(form, nombreCompleto) {
    formPendiente = form;
    document.getElementById("modalTexto").innerHTML =
        `¿Estás seguro de que deseas dar de baja a ${nombreCompleto}?`;
    document.getElementById("modalBaja").style.display = "flex";
    return false;
}

let formAltaPendiente = null;
function abrirModalAlta(form, nombreCompleto) {
    formAltaPendiente = form;
    document.getElementById("modalTextoAlta").innerHTML =
        `¿Deseas volver a dar de alta a ${nombreCompleto}?`;
    document.getElementById("modalAlta").style.display = "flex";
    return false;
}

document.getElementById("btnConfirmar").onclick = () => {
    if (formPendiente) formPendiente.submit();
    cerrarModal("modalBaja");
};
document.getElementById("btnCancelar").onclick = () => cerrarModal("modalBaja");
document.getElementById("btnConfirmarAlta").onclick = () => {
    if (formAltaPendiente) formAltaPendiente.submit();
    cerrarModal("modalAlta");
};
document.getElementById("btnCancelarAlta").onclick = () => cerrarModal("modalAlta");

function cerrarModal(id) {
    document.getElementById(id).style.display = "none";
}
</script>

<script>
function togglePassword(id, btn) {
    const input = document.getElementById(id);
    if (input.type === "password") {
        input.type = "text";
        btn.textContent = "🙈";
    } else {
        input.type = "password";
        btn.textContent = "👁️";
    }
}

// Verifica que ambas contraseñas coincidan antes de enviar el formulario
document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector(".form-alta");
    if (!form) return;

    form.addEventListener("submit", (e) => {
        const pass = document.getElementById("contraseña");
        const confirm = document.getElementById("confirmar_contraseña");
        if (pass && confirm && pass.value !== confirm.value) {
            e.preventDefault();
            alert("Las contraseñas no coinciden. Por favor, verifícalas.");
            confirm.focus();
        }
    });
});
</script>


<?php include("footer.php"); ?>
</body>
</html>
