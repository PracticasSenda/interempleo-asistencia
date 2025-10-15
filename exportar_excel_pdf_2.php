
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Exportar asistencia por fecha</title>

  <style>
    :root {
      --color-principal: #FF671D;
      /* naranja principal */
      --color-principal-claro: #FF8A3D;
      /* naranja más claro para th */
      --color-fondo: #FFFFFF;
      --color-texto: #333333;
      --color-borde: #FF671D;
      /* borde tabla naranja */
      --color-input-bg: #F9F9F9;
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      background-color: var(--color-fondo);
      margin: 0;
      padding: 0;
    }

    /* BARRA SUPERIOR */
    .barra-superior {
      background-color: var(--color-principal);
      color: white;
      padding: 1.5rem 8rem;
      font-size: 1.5rem;
      font-weight: normal;
      text-align: left;
      position: relative;
      /* para que btn-volver no desplace */
    }

    .barra-superior span {
      font-weight: bold;
    }

    /* BOTÓN VOLVER A ASISTENCIAS */
    .btn-volver {
      background-color: #fff;
      color: var(--color-principal);
      padding: 0.4rem 1rem;
      border-radius: 4px;
      text-decoration: none;
      font-weight: bold;
      font-size: 1rem;
      border: 2px solid var(--color-principal);
      transition: background-color 0.3s ease, color 0.3s ease;
      position: absolute;
      /* lo posicionamos a la derecha */
      top: 50%;
      right: 8rem;
      transform: translateY(-50%);
    }

    .btn-volver:hover {
      background-color: var(--color-principal);
      color: #fff;
      cursor: pointer;
    }

    /* CONTENEDOR CENTRAL PARA INPUT, BOTÓN Y TÍTULO */
    .contenedor-central {
      max-width: 900px;
      margin: 3rem auto;
      /* margen arriba/abajo y centrado horizontal */
      padding: 0 1rem;
      text-align: center;
      /* centramos el contenido en general */
    }

    /* TÍTULO - dentro del contenedor, alineado a la izquierda con margen */
    h2.titulo-listado {
      color: var(--color-principal);
      font-size: 1.8rem;
      font-weight: bold;
      margin-bottom: 1rem;
      text-align: left;
      /* alineamos texto a la izquierda */
      max-width: 600px;
      margin-left: auto;
      margin-right: auto;
    }

    /* FORMULARIO - input fecha y botón exportar */
    #form-buscar {
      margin-bottom: 2rem;
    }

    #form-buscar input[type="date"] {
      padding: 0.5rem 0.75rem;
      font-size: 1rem;
      border: 1.5px solid var(--color-borde);
      border-radius: 4px;
      background-color: var(--color-fondo);
      width: 200px;
      max-width: 90%;
      margin-right: 1rem;
      vertical-align: middle;
    }

    #form-buscar button {
      background-color: var(--color-principal);
      color: #FFFFFF;
      border: none;
      border-radius: 4px;
      font-size: 1.1rem;
      cursor: pointer;
      padding: 0.5rem 1.5rem;
      display: inline-block;
      vertical-align: middle;
      transition: background-color 0.3s ease;
    }

    #form-buscar button:hover {
      background-color: #e65c17;
    }

    /* TABLA centrada con bordes naranjas */
    table {
      border-collapse: collapse;
      width: 100%;
      max-width: 900px;
      margin: 0 auto 2rem auto;
      border: 2px solid var(--color-borde);
      border-radius: 8px;
      overflow: hidden;
    }

    /* Encabezados con color naranja más claro */
    th {
      background-color: var(--color-principal-claro);
      color: white;
      padding: 0.75rem 1rem;
      border: 1px solid var(--color-borde);
      font-weight: bold;
      text-align: center;
    }

    /* Celdas con borde naranja */
    td {
      border: 1px solid var(--color-borde);
      padding: 0.75rem 1rem;
      text-align: center;
    }

    /* Filas seleccionables */
    tbody tr:hover {
      background-color: #ffe6d1;
      /* naranja claro al pasar mouse */
      cursor: pointer;
    }

    tbody tr.selected {
      background-color: var(--color-principal);
      color: white;
    }

    /* RESPONSIVE: TABLETS */
    @media (max-width: 768px) {
      .barra-superior {
        padding: 1.5rem 2rem;
        font-size: 1.3rem;
        text-align: center;
      }

      .btn-volver {
        position: static;
        transform: none;
        display: inline-block;
        margin-top: 1rem;
      }

      .contenedor-central {
        margin: 2rem 1rem;
      }

      h2.titulo-listado {
        font-size: 1.5rem;
        max-width: 100%;
      }

      #form-buscar input[type="date"] {
        width: 150px;
        margin-bottom: 1rem;
        margin-right: 0;
      }

      #form-buscar button {
        width: 100%;
        padding: 0.6rem 0;
      }

      table {
        font-size: 0.9rem;
      }
    }

    /* RESPONSIVE: MÓVILES */
    @media (max-width: 480px) {
      .barra-superior {
        padding: 1rem;
        font-size: 1.2rem;
        text-align: center;
      }

      .btn-volver {
        margin-top: 1rem;
      }

      .contenedor-central {
        margin: 1.5rem 0.5rem;
      }

      h2.titulo-listado {
        font-size: 1.3rem;
      }

      #form-buscar input[type="date"] {
        width: 100%;
      }

      #form-buscar button {
        width: 100%;
        padding: 0.5rem 0;
      }

      table {
        font-size: 0.85rem;
      }
    }

    /* Otros estilos que tenías */
    .password-container {
      position: relative;
    }

    .password-container input {
      padding-right: 2.5rem;
      /* espacio para el icono */
    }

    .toggle-password {
      position: absolute;
      top: 50%;
      right: 0.75rem;
      transform: translateY(-50%);
      cursor: pointer;
      font-size: 1.1rem;
      color: var(--color-texto);
      user-select: none;
    }

    #btn_exportar {
      background-color: var(--color-principal);
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 1rem;
      padding: 0.6rem 1.2rem;
      margin-top: 1rem;
      cursor: pointer;
      display: block;
      margin-left: auto;
      margin-right: auto;
      transition: background-color 0.3s ease;
    }

    #btn_exportar:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    #btn_exportar:hover:enabled {
      background-color: #e65c17;
    }

    #btn_exportar_pdf {
      background-color: #e04e2b;
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 1rem;
      padding: 0.6rem 1.2rem;
      margin-top: 1rem;
      margin-left: 0.5rem;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    #btn_exportar_pdf:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    #btn_exportar_pdf:hover:enabled {
      background-color: #c84323;
    }
    /* Botón + para ver asistencias */
.btn-ver-asistencias {
  background-color: #ff671d;
  border: none;
  color: white;
  font-weight: bold;
  border-radius: 50%;
  width: 30px;
  height: 30px;
  cursor: pointer;
  margin-left: 0.5rem;
  position: relative;
}

.btn-ver-asistencias::after {
  content: attr(data-tooltip);
  position: absolute;
  top: -35px;
  left: 50%;
  transform: translateX(-50%);
  background-color: #333;
  color: #fff;
  font-size: 0.8rem;
  padding: 0.3rem 0.6rem;
  border-radius: 4px;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s ease;
  z-index: 1;
}

.btn-ver-asistencias:hover::after {
  opacity: 1;
}

/* Botón volver */
#btn_volver_listados {
  background-color: #555;
  color: white;
  padding: 0.6rem 1.2rem;
  border: none;
  border-radius: 4px;
  margin-top: 1rem;
  cursor: pointer;
}

/* Scroll vertical en observaciones */
td.observaciones {
  max-height: 80px;
  overflow-y: auto;
}#btn_toggle_asistencias {
  background-color: #28a745; /* verde */
  color: white;
  font-size: 1.5rem;
  font-weight: bold;
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  cursor: pointer;
  margin: 1rem auto;
  display: block;
  transition: background-color 0.3s ease;
}

#btn_toggle_asistencias.ver {
  background-color: #28a745; /* verde */
}

#btn_toggle_asistencias.volver {
  background-color: #6c757d; /* gris */
}

#btn_toggle_asistencias:hover {
  opacity: 0.85;
}
.btn-opciones {
  background-color: transparent;
  border: none;
  color: black;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0;
  margin: 0;
  line-height: 1;
}

.btn-opciones:hover {
  color: #e65c17;
}
.menu-opciones {
  position: absolute;
  top: 100%;
  right: 0;
  background-color: white;
  border: 1px solid var(--color-borde);
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  border-radius: 4px;
  z-index: 1000; /* subir bastante para que quede encima */
  padding: 0.3rem 0;
  min-width: 160px;
}

.menu-item {
  background: none;
  border: none;
  width: 100%;
  text-align: left;
  padding: 0.5rem 1rem;
  cursor: pointer;
  font-size: 0.95rem;
  color: var(--color-texto);
}

.menu-item:hover {
  background-color: #ffe6d1;
}
td {
  position: relative; /* <-- agregar */
}
.menu-opciones {
  position: absolute;
  background-color: white;
  border: 1px solid var(--color-borde);
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  border-radius: 4px;
  z-index: 9999;
  padding: 0.3rem 0;
  min-width: 160px;
  display: none;
}





  </style>

</head>

<body>

  <div class="barra-superior">
    <p><span>Interempleo</span> - exportar</p>
    <a href="asistencia_responsive.php" class="btn-volver">Volver a asistencias</a>
  </div>

  <div class="contenedor-central">

    <form id="form-buscar" method="GET" action="buscar_listado_por_fecha.php">
      <label for="fecha_buscar">Buscar listados por fecha:</label>
      <input type="date" id="fecha_buscar" name="fecha" required />
    </form>

    <h2 class="titulo-listado">Selecciona un listado</h2>

    <table id="tabla_listados" style="display:none;">
      <thead>
        <tr>
          <th>ID</th>
          <th>Empresa</th>
          <th>Producto</th>
          <th>Fecha</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>

     <!-- Tabla de asistencias (oculta inicialmente) -->
<div id="contenedor-asistencias" style="display:none;">
  <h2 class="titulo-listado">Asistencias del listado</h2>
  <div style="overflow-x: auto;">
    <table id="tabla_asistencias">
      <thead>
        <tr>
          <th>ID</th>
          <th>Trabajador</th>
          <th>DNI</th>
          <th>Asistencia</th>
          <th>Empresa</th>
          <th>Fecha</th>
          <th>Producto</th>
          <th>Bandeja</th>
          <th>Horas</th>
          <th style="min-width:200px;">Observaciones</th>
          <th>Opciones</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>
    
    <!-- Botón fijo + para ver asistencias -->
    <button id="btn_toggle_asistencias" style="display: none;">+</button>



    <form id="form_exportar" method="get" action="funcion_exportar_excel.php" target="_blank">
      <input type="hidden" name="id_listado" id="id_listado" />
      <button type="submit" id="btn_exportar" disabled>Exportar listado seleccionado a Excel</button>
      <button type="button" id="btn_exportar_pdf" disabled>Exportar a PDF</button>
    </form>

   


  </div>

</body>


<script>
const fechaInput = document.getElementById('fecha_buscar');
const tabla = document.getElementById('tabla_listados');
const tbody = tabla.querySelector('tbody');
const btnExportar = document.getElementById('btn_exportar');
const btnExportarPDF = document.getElementById('btn_exportar_pdf');
const inputIdListado = document.getElementById('id_listado');
const btnToggle = document.getElementById('btn_toggle_asistencias');
const contenedorAsistencias = document.getElementById('contenedor-asistencias');
const tablaListados = document.getElementById('tabla_listados');
const tbodyAsistencias = document.querySelector('#tabla_asistencias tbody');

let seleccionado = null;

// Al cambiar la fecha
fechaInput.addEventListener('change', () => {
  const fecha = fechaInput.value;

  // Resetear vista
  contenedorAsistencias.style.display = 'none';
  tablaListados.style.display = 'none';
  btnToggle.style.display = 'none';
  btnToggle.textContent = '+';
  btnToggle.setAttribute('data-mode', 'ver');
  btnToggle.classList.remove('volver');
  btnToggle.classList.add('ver');
  btnToggle.removeAttribute('data-id');
  btnToggle.setAttribute('title', '');

  if (!fecha) {
    tabla.style.display = 'none';
    tbody.innerHTML = '';
    btnExportar.disabled = true;
    btnExportarPDF.disabled = true;
    inputIdListado.value = '';
    return;
  }

  fetch('buscar_listados_por_fecha.php?fecha=' + fecha)
    .then(res => res.json())
    .then(data => {
      tbody.innerHTML = '';

      if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No se encontraron listados para esta fecha.</td></tr>';
        tabla.style.display = 'table';
        btnExportar.disabled = true;
        btnExportarPDF.disabled = true;
        inputIdListado.value = '';
        return;
      }

      data.forEach(listado => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${listado.id}</td>
          <td>${listado.empresa}</td>
          <td>${listado.producto}</td>
          <td>${listado.fecha}</td>
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
          btnExportar.disabled = false;
          btnExportarPDF.disabled = false;

          // Configurar botón de asistencias
          btnToggle.style.display = 'inline-block';
          btnToggle.setAttribute('data-id', listado.id);
          btnToggle.setAttribute('data-mode', 'ver');
          btnToggle.textContent = '+';
          btnToggle.classList.remove('volver');
          btnToggle.classList.add('ver');
          btnToggle.setAttribute('title', 'Ver asistencias');
        });

        tbody.appendChild(tr);
      });

      tabla.style.display = 'table';
    })
    .catch(err => {
      console.error('Error al buscar listados:', err);
      tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Error al cargar los listados.</td></tr>';
      tabla.style.display = 'table';
      btnExportar.disabled = true;
      btnExportarPDF.disabled = true;
      inputIdListado.value = '';
    });
});

// Exportar PDF desde botón general (si se quiere mantener)
btnExportarPDF.addEventListener('click', () => {
  const idListado = inputIdListado.value;
  if (!idListado) return alert('Selecciona un listado primero.');
  window.open('funcion_exportar_pdf.php?id_listado=' + idListado, '_blank');
});

// Mostrar asistencias de un listado
function mostrarAsistencias(idListado) {
  fetch('buscar_asistencias_por_listado.php?id_listado=' + idListado)
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
            <td>${asistencia.nombre} ${asistencia.apellidos}</td>
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
    })
    .catch(err => {
      console.error('Error al cargar asistencias:', err);
      tbodyAsistencias.innerHTML = '<tr><td colspan="10">Error al cargar asistencias.</td></tr>';
    });
}

// Botón toggle para ver/ocultar asistencias
btnToggle.addEventListener('click', () => {
  const modo = btnToggle.getAttribute('data-mode');
  const idListado = btnToggle.getAttribute('data-id');

  if (modo === 'ver') {
    if (!idListado) {
      alert('No se ha seleccionado ningún listado.');
      return;
    }

    mostrarAsistencias(idListado);
    btnToggle.textContent = '−';
    btnToggle.classList.remove('ver');
    btnToggle.classList.add('volver');
    btnToggle.setAttribute('data-mode', 'volver');
    btnToggle.setAttribute('title', 'Ver listados');
  } else {
    contenedorAsistencias.style.display = 'none';
    tablaListados.style.display = 'table';
    btnToggle.textContent = '+';
    btnToggle.classList.remove('volver');
    btnToggle.classList.add('ver');
    btnToggle.setAttribute('data-mode', 'ver');
    btnToggle.setAttribute('title', 'Ver asistencias');
  }
});

// Menú desplegable por fila
document.addEventListener('click', function (e) {
  // Cerrar cualquier menú abierto
  document.querySelectorAll('.menu-opciones').forEach(menu => {
    menu.style.display = 'none';
  });

  // Si se hace clic en el botón de opciones (⋮)
  if (e.target.matches('.btn-opciones')) {
    e.stopPropagation();

    // Obtener el botón y su fila
    const btn = e.target;
    const row = btn.closest('tr');
    const idListado = row.querySelector('td').textContent.trim();

    // Crear menú flotante si no existe
    let menu = document.getElementById('menu-flotante');
    if (!menu) {
      menu = document.createElement('div');
      menu.id = 'menu-flotante';
      menu.className = 'menu-opciones';
      document.body.appendChild(menu);
    }

    // Poner el contenido
    menu.innerHTML = `
      <button class="menu-item exportar-pdf">Exportar PDF</button>
      <button class="menu-item exportar-excel">Exportar Excel</button>
      <button class="menu-item ver-asistencias">Ver listados</button>
    `;
    menu.dataset.idListado = idListado;

    // Posicionar el menú justo debajo del botón
    const rect = btn.getBoundingClientRect();
    menu.style.left = `${rect.left}px`;
    menu.style.top = `${rect.bottom + window.scrollY}px`;
    menu.style.display = 'block';
  }

  // Si se hace clic en una opción del menú
  if (e.target.matches('.menu-item')) {
    const menu = e.target.closest('.menu-opciones');
    const idListado = menu.dataset.idListado;

    if (e.target.classList.contains('exportar-pdf')) {
      window.open(`funcion_exportar_pdf.php?id_listado=${idListado}`, '_blank');
    }

    if (e.target.classList.contains('exportar-excel')) {
      window.open(`funcion_exportar_excel.php?id_listado=${idListado}`, '_blank');
    }

    if (e.target.classList.contains('ver-asistencias')) {
      mostrarAsistencias(idListado);
      btnToggle.textContent = '−';
      btnToggle.classList.remove('ver');
      btnToggle.classList.add('volver');
      btnToggle.setAttribute('data-mode', 'volver');
      btnToggle.setAttribute('title', 'Ver listados');
      btnToggle.style.display = 'inline-block';
      btnToggle.setAttribute('data-id', idListado);
    }

    // Cerrar el menú después de una acción
    menu.style.display = 'none';
  }
});

</script>

</body>

</html>
