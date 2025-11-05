<?php
include(__DIR__ . '/../auth/validar_sesion.php');
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Exportar asistencia por fecha</title>

   <link rel="stylesheet" href="../css/gestionar-asistencia.css">
  

</head>

<body>


<?php include(__DIR__ . '/../views/header.php');?>
  <!-- Dropdown del menú -->
<div class="menu-dropdown" id="menuDropdown">

<?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador'): ?>
  <a href="/interempleo-asistencia/views/gestionar-personal.php?tipo=encargados&vista=lista">Gestión de encargados</a>
<?php endif; ?>  
<a href="/interempleo-asistencia/views/gestionar-personal.php?tipo=trabajadores&vista=lista">Gestión de trabajadores</a>
  <a href="/interempleo-asistencia/views/asistencia.php">Parte de asistencia</a>
  <a href="/interempleo-asistencia/auth/cerrar_sesion.php">Cerrar sesión</a>
</div>


  <div class="contenedor-central">

    <form id="form-buscar" method="GET" action="buscar_listado_por_fecha.php">
      <label for="fecha_buscar">Buscar listados por fecha:</label>
      <input type="date" id="fecha_buscar" name="fecha" required />
      <input type="hidden" id="id_listado" name="id_listado" />

    </form>

    <h2 class="titulo-listado">Selecciona un listado</h2>


    <table id="tabla_listados" style="display:none;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Empresa</th>
          <th>Producto</th>
          <th>Fecha</th>
          <th>Encargado</th>

          <th>Opciones</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>

    <!-- MODAL para confirmación de exportar -->
<div id="modalExportar" style="display:none;">
  <div class="modal-content">
    <p id="modal-text">¿Quieres exportar este listado?</p>
    <div class="modal-buttons">
      <button id="confirmarExportar" class="btn-confirmar">Confirmar</button>
      <button id="cancelarExportar" class="btn-cancelar">Cancelar</button>
    </div>
  </div>
</div>


     <!-- Tabla de asistencias (oculta inicialmente) -->
<div id="contenedor-asistencias" style="display:none;">
  <button id="btn_volver_listados" style="display: none;">← Volver a listados</button>
  <h2 class="titulo-listado">Asistencias del listado</h2>
  <div style="overflow-x: auto;">
    <table id="tabla_asistencias">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Apellidos</th>
          <th>DNI</th>
          <th>Asistencia</th>
          <th>Empresa</th>
          <th>Fecha</th>
          <th>Producto</th>
          <th>Bandeja</th>
          <th>Horas</th>
          <th style="min-width:200px;">Observaciones</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

    
   
    



   


  </div>

</body>

<script>
const fechaInput = document.getElementById('fecha_buscar');
const tabla = document.getElementById('tabla_listados');
const tbody = tabla.querySelector('tbody');
const inputIdListado = document.getElementById('id_listado');
const contenedorAsistencias = document.getElementById('contenedor-asistencias');
const tablaListados = document.getElementById('tabla_listados');
const tbodyAsistencias = document.querySelector('#tabla_asistencias tbody');
const tituloListado = document.querySelector('.titulo-listado');

let seleccionado = null;

// Variables modal
const modal = document.getElementById('modalExportar');
const modalText = document.getElementById('modal-text');
const btnConfirmar = document.getElementById('confirmarExportar');
const btnCancelar = document.getElementById('cancelarExportar');

let exportarTipo = null; // "pdf" o "excel"
let exportarIdListado = null;

// Al cambiar la fecha
fechaInput.addEventListener('change', () => {
  const fecha = fechaInput.value;

  // Resetear vista
  contenedorAsistencias.style.display = 'none';
  tablaListados.style.display = 'none';

  if (!fecha) {
    tabla.style.display = 'none';
    tbody.innerHTML = '';

    inputIdListado.value = '';

    seleccionado = null;
    return;
  }

  fetch('/interempleo-asistencia/funciones/funciones_buscar.php?accion=buscar_listados_por_fecha&fecha=' + fecha)
    .then(res => res.json())
    .then(data => {
      tbody.innerHTML = '';

      if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No se encontraron listados para esta fecha.</td></tr>';
        tabla.style.display = 'table';

        inputIdListado.value = '';

        seleccionado = null;
        return;
      }

      data.forEach(listado => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${listado.id}</td>
          <td>${listado.empresa}</td>
          <td>${listado.producto}</td>
          <td>${listado.fecha}</td>
          <td>${listado.encargado}</td>
          <td style="position: relative;">
            <button class="btn-opciones" title="Más opciones">⋮</button>
            <div class="menu-opciones" style="display: none;" data-id-listado="${listado.id}">
              <button class="menu-item exportar-pdf">Exportar PDF</button>
              <button class="menu-item exportar-excel">Exportar Excel</button>
              <button class="menu-item ver-asistencias">Ver asistencias</button>
            </div>
          </td>
        `;

        tr.addEventListener('click', () => {
          if (seleccionado) {
            seleccionado.classList.remove('selected');
          }

          tr.classList.add('selected');
          seleccionado = tr;
          inputIdListado.value = listado.id;
        });

        tbody.appendChild(tr);
      });

      tabla.style.display = 'table';

      inputIdListado.value = '';
      seleccionado = null;
    })
    .catch(err => {
      console.error('Error al buscar listados:', err);
      tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Error al cargar los listados.</td></tr>';
      tabla.style.display = 'table';

      inputIdListado.value = '';

      seleccionado = null;
    });
});

// Mostrar asistencias de un listado
function mostrarAsistencias(idListado) {
  console.log('Mostrar asistencias para listado:', idListado);
  fetch('/interempleo-asistencia/funciones/funciones_buscar.php?accion=buscar_asistencias_por_listado&id_listado=' + idListado)
    .then(res => res.json())
    .then(data => {
      tbodyAsistencias.innerHTML = '';

      if (data.length === 0) {
        tbodyAsistencias.innerHTML = '<tr><td colspan="10" style="text-align:center;">No se encontraron asistencias.</td></tr>';
      } else {
        data.forEach(asistencia => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td>${asistencia.id}</td>
            <td>${asistencia.nombre} </td>
            <td>${asistencia.apellidos}</td>
            <td>${asistencia.dni}</td>
            <td>${asistencia.asistencia}</td>
            <td>${asistencia.empresa}</td>
            <td>${asistencia.fecha}</td>
            <td>${asistencia.producto}</td>
            <td>${asistencia.Bandeja}</td>
            <td>${asistencia.Horas}</td>
            <td class="observaciones">${asistencia.Observaciones}</td>
          `;
          tbodyAsistencias.appendChild(tr);
        });
      }

      tablaListados.style.display = 'none';
      contenedorAsistencias.style.display = 'block';
      btnVolver.style.display = 'inline-block';

      tituloListado.style.display = 'none';
    })
    .catch(err => {
      console.error('Error al cargar asistencias:', err);
      tbodyAsistencias.innerHTML = '<tr><td colspan="10">Error al cargar asistencias.</td></tr>';
    });
}

// Menú desplegable por fila
document.addEventListener('click', function (e) {
  // Cerrar cualquier menú abierto
  document.querySelectorAll('.menu-opciones').forEach(menu => {
    menu.style.display = 'none';
  });

  // Si se hace clic en el botón de opciones (⋮)
  if (e.target.matches('.btn-opciones')) {
    e.stopPropagation();

    const btn = e.target;
    const row = btn.closest('tr');
    const idListado = row.querySelector('td').textContent.trim();

    let menu = document.getElementById('menu-flotante');
    if (!menu) {
      menu = document.createElement('div');
      menu.id = 'menu-flotante';
      menu.className = 'menu-opciones';
      document.body.appendChild(menu);
    }

    menu.innerHTML = `
      <button class="menu-item exportar-pdf" data-id="${idListado}">Exportar PDF</button>
      <button class="menu-item exportar-excel" data-id="${idListado}">Exportar Excel</button>
      <button class="menu-item ver-asistencias" data-id="${idListado}">Ver listados</button>
    `;

    menu.dataset.idListado = idListado;

    const rect = btn.getBoundingClientRect();
    menu.style.left = `${rect.left}px`;
    menu.style.top = `${rect.bottom + window.scrollY}px`;
    menu.style.display = 'block';
  }

  // Si se hace clic en una opción del menú
  if (e.target.matches('.menu-item')) {
    const idListado = e.target.dataset.id;

    if (e.target.classList.contains('exportar-pdf')) {
      // Mostrar modal antes de exportar
      exportarTipo = 'pdf';
      exportarIdListado = idListado;
      modalText.textContent = `¿Quieres exportar el listado #${idListado} en PDF?`;
      modal.style.display = 'flex';
    }

    if (e.target.classList.contains('exportar-excel')) {
      // Mostrar modal antes de exportar
      exportarTipo = 'excel';
      exportarIdListado = idListado;
      modalText.textContent = `¿Quieres exportar el listado #${idListado} en Excel?`;
      modal.style.display = 'flex';
    }

    if (e.target.classList.contains('ver-asistencias')) {
      mostrarAsistencias(idListado);
    }

    // Cerrar el menú después de una acción
    const menu = document.getElementById('menu-flotante');
    if(menu) menu.style.display = 'none';
  }
});

// Botón Confirmar modal
btnConfirmar.addEventListener('click', () => {
  if (exportarTipo && exportarIdListado) {
    let url = '';
    if (exportarTipo === 'pdf') {
      url = `../export/funcion_exportar_pdf.php?id_listado=${exportarIdListado}`;
    } else if (exportarTipo === 'excel') {
      url = `../export/funcion_exportar_excel.php?id_listado=${exportarIdListado}`;
    }
    window.open(url, '_blank');
  }
  modal.style.display = 'none';
  exportarTipo = null;
  exportarIdListado = null;
});

// Botón Cancelar modal
btnCancelar.addEventListener('click', () => {
  modal.style.display = 'none';
  exportarTipo = null;
  exportarIdListado = null;
});

function toggleMenu() {
  const menu = document.getElementById('menuDropdown');
  menu.classList.toggle('show');
}

// Cierra el menú si haces clic fuera
document.addEventListener('click', function (e) {
  const menu = document.getElementById('menuDropdown');
  const toggle = document.querySelector('.menu-toggle');

  if (!menu.contains(e.target) && e.target !== toggle) {
    menu.classList.remove('show');
  }
});

// Cierra el menú al hacer clic en un enlace
document.querySelectorAll('.menu-dropdown a').forEach(enlace => {
  enlace.addEventListener('click', () => {
    document.getElementById('menuDropdown').classList.remove('show');
  });
});

const btnVolver = document.getElementById('btn_volver_listados');

btnVolver.addEventListener('click', () => {
  contenedorAsistencias.style.display = 'none';
  tablaListados.style.display = 'table';
  btnVolver.style.display = 'none';
  tituloListado.style.display = 'block';

  if (seleccionado) {
    seleccionado.classList.remove('selected');
    seleccionado = null;
  }
  inputIdListado.value = '';
});

</script>

  <?php include(__DIR__ . '/../views/footer.php'); ?>
</body>

</html>
