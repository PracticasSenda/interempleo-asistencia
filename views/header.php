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
<style>
/* 🔹 Fondo del modal */
.modal_cerrarsesion {
  display: none;
  position: fixed;
  z-index: 9999;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.5);
  animation: modal_cerrarsesion_aparecer 0.2s ease;
}

/* 🔹 Caja del contenido */
.modal_cerrarsesion_contenido {
  background: #fff;
  color: #333;
  width: 320px;
  margin: 15% auto;
  padding: 20px 25px;
  border-radius: 12px;
  text-align: center;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  border: 1px solid var(--borde);
}

.modal_cerrarsesion_contenido p {
  font-size: 1rem;
  margin-bottom: 15px;
}

/* 🔹 Botones */
.modal_cerrarsesion_botones {
  display: flex;
  justify-content: space-around;
}

.modal_cerrarsesion_botones button {
  padding: 8px 18px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: 0.2s ease;
}

.modal_cerrarsesion_btn_confirmar {
  background-color: #ff671d;
  color: #fff;
}
.modal_cerrarsesion_btn_confirmar:hover {
  background-color: #e35b16;
}

.modal_cerrarsesion_btn_cancelar {
  background-color: #f1f1f1;
  color: var(--texto);
}
.modal_cerrarsesion_btn_cancelar:hover {
  background-color: #e5e5e5;
}

/* 🔹 Animación */
@keyframes modal_cerrarsesion_aparecer {
  from { opacity: 0; }
  to { opacity: 1; }
}
</style>

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

        <a href="../export/exportar_excel_pdf.php"
           class="<?= ($pagina_actual === 'exportar_excel_pdf.php') ? 'activo' : '' ?>">
           Gestionar asistencias
        </a>

        <!-- 🔸 Enlace con modal -->
        <a href="#" onclick="abrir_modal_cerrarsesion()">Cerrar sesión</a>
    </div>
</div>

<!-- 🔸 MODAL DE CONFIRMACIÓN -->
<div id="modal_cerrarsesion" class="modal_cerrarsesion">
  <div class="modal_cerrarsesion_contenido">
    <p>¿Seguro que quieres cerrar sesión?</p>
    <div class="modal_cerrarsesion_botones">
      <button class="modal_cerrarsesion_btn_confirmar" onclick="confirmar_cerrarsesion()">Sí, cerrar</button>
      <button class="modal_cerrarsesion_btn_cancelar" onclick="cerrar_modal_cerrarsesion()">Cancelar</button>
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

function abrir_modal_cerrarsesion() {
  document.getElementById('modal_cerrarsesion').style.display = 'block';
}

function cerrar_modal_cerrarsesion() {
  document.getElementById('modal_cerrarsesion').style.display = 'none';
}

function confirmar_cerrarsesion() {
  window.location.href = "/interempleo-asistencia/auth/cerrar_sesion.php";
}
</script>

