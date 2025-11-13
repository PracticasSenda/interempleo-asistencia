<link rel="stylesheet" href="../css/style-global.css">

<style>
/* 🎨 Estilos del modal de cierre de sesión */
.cerrarSesion-modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.4);
  justify-content: center;
  align-items: center;
  font-family: Arial, sans-serif;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.cerrarSesion-modal.show {
  display: flex;
  opacity: 1;
}

.cerrarSesion-contenido {
  background-color: var(--bg, #fff);
  border: 1px solid var(--borde, #DDDDDD);
  border-radius: 8px;
  padding: 25px 30px;
  text-align: center;
  max-width: 400px;
  width: 90%;
  color: var(--texto, #333);
  box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}

.cerrarSesion-contenido h3 {
  margin-top: 0;
  color: var(--texto, #333);
  font-size: 20px;
}

.cerrarSesion-contenido p {
  margin: 15px 0 25px;
  color: var(--texto, #333);
  font-size: 15px;
}

.cerrarSesion-botones {
  display: flex;
  justify-content: space-around;
  gap: 10px;
}

/* Botones */
.btnConfirmarCerrarSesion {
  background-color: var(--naranja, #FF671D);
  color: white;
  border: none;
  padding: 10px 18px;
  border-radius: 5px;
  cursor: pointer;
  font-size: 15px;
  transition: background-color 0.2s;
}

.btnConfirmarCerrarSesion:hover {
  background-color: #e65c17;
}

.btnCancelarCerrarSesion {
  background-color: #f0f0f0;
  color: var(--texto, #333);
  border: 1px solid var(--borde, #DDDDDD);
  padding: 10px 18px;
  border-radius: 5px;
  cursor: pointer;
  font-size: 15px;
  transition: background-color 0.2s;
}

.btnCancelarCerrarSesion:hover {
  background-color: #e9e9e9;
}
</style>

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

    <a href="/interempleo-asistencia/auth/cerrar_sesion.php" id="linkCerrarSesion">Cerrar sesión</a>
  </div>
</div>

<!-- 🔸 MODAL DE CONFIRMACIÓN DE CIERRE DE SESIÓN -->
<div class="cerrarSesion-modal" id="cerrarSesionModal">
  <div class="cerrarSesion-contenido">
    <h3>¿Deseas cerrar la sesión?</h3>
    <p>Se cerrará tu sesión actual y deberás iniciar sesión nuevamente.</p>
    <div class="cerrarSesion-botones">
      <button id="btnConfirmarCerrarSesion" class="btnConfirmarCerrarSesion">Sí, cerrar sesión</button>
      <button id="btnCancelarCerrarSesion" class="btnCancelarCerrarSesion">Cancelar</button>
    </div>
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

// 🧠 Lógica del modal de cierre de sesión
document.addEventListener("DOMContentLoaded", () => {
  const linkCerrarSesion = document.getElementById('linkCerrarSesion');
  const modal = document.getElementById('cerrarSesionModal');
  const btnConfirmar = document.getElementById('btnConfirmarCerrarSesion');
  const btnCancelar = document.getElementById('btnCancelarCerrarSesion');

  if (!linkCerrarSesion || !modal) return;

  linkCerrarSesion.addEventListener('click', (e) => {
    e.preventDefault();
    modal.classList.add('show');
    modal.style.display = 'flex';
  });

  btnConfirmar.addEventListener('click', () => {
    window.location.href = linkCerrarSesion.href;
  });

  btnCancelar.addEventListener('click', () => {
    modal.classList.remove('show');
    setTimeout(() => modal.style.display = 'none', 300);
  });

  window.addEventListener('click', (e) => {
    if (e.target === modal) {
      modal.classList.remove('show');
      setTimeout(() => modal.style.display = 'none', 300);
    }
  });
});
</script>
