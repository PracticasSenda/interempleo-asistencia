<link rel="stylesheet" href="../../public/css/style-global.css">

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$rol = $_SESSION['rol'] ?? '';
$nombre = $_SESSION['nombre'] ?? '';
$apellidos = $_SESSION['apellidos'] ?? '';
$nombre_completo = trim("$nombre ");

// Detectar página actual (para marcar en naranja el menú activo)
$pagina_actual = basename($_SERVER['PHP_SELF']);
$tipo = $_GET['tipo'] ?? '';
?>

<!-- 🔸 HEADER GENERAL -->
<div class="barra-superior">
    <div class="contenedor-barra">
        <div class="lado-izquierdo">
            <button class="menu-toggle" onclick="toggleMenu()">☰</button>
            <p><span>Inter</span>empleo</p>
        </div>

        <div class="bienvenida">
            Bienvenido, <?= htmlspecialchars($nombre_completo) ?>
        </div>
    </div>

    <div class="menu-dropdown" id="menuDropdown">
        <a href="/interempleo-asistencia/views/gestionar-personal.php?tipo=trabajadores&vista=lista"
           class="<?= ($pagina_actual === 'gestionar-personal.php' && $tipo === 'trabajadores') ? 'activo' : '' ?>">
            Gestión de trabajadores
        </a>

        <?php if ($rol === 'administrador'): ?>
            <a href="/interempleo-asistencia/views/gestionar-personal.php?tipo=encargados&vista=lista"
               class="<?= ($pagina_actual === 'gestionar-personal.php' && $tipo === 'encargados') ? 'activo' : '' ?>">
               Gestión de encargados
            </a>
        <?php endif; ?>

        <a href="../views/asistencia.php"
           class="<?= ($pagina_actual === 'asistencia.php') ? 'activo' : '' ?>">
           Nuevas asistencias
        </a>

        <a href="../views/gestionar-asistencia.php"
           class="<?= ($pagina_actual === 'gestionar-asistencia.php') ? 'activo' : '' ?>">
           Gestionar asistencias
        </a>

        <a href="/interempleo-asistencia/auth/cerrar_sesion.php">Cerrar sesión</a>
    </div>
</div>

<script>
function toggleMenu() {
    const menu = document.getElementById('menuDropdown');
    menu.classList.toggle('show');
}
document.addEventListener('click', function(e) {
    const menu = document.getElementById('menuDropdown');
    const toggle = document.querySelector('.menu-toggle');
    if (!menu.contains(e.target) && e.target !== toggle) {
        menu.classList.remove('show');
    }
});
</script>
