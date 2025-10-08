<?php
include("validar_sesion.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Exportar asistencia por fecha</title>

<style>
:root {
  --color-principal: #FF671D; /* naranja principal */
  --color-principal-claro: #FF8A3D; /* naranja más claro para th */
  --color-fondo: #FFFFFF;
  --color-texto: #333333;
  --color-borde: #FF671D; /* borde tabla naranja */
  --color-input-bg: #F9F9F9;
}

*, *::before, *::after {
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
  position: relative; /* para que btn-volver no desplace */
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
  position: absolute; /* lo posicionamos a la derecha */
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
  margin: 3rem auto; /* margen arriba/abajo y centrado horizontal */
  padding: 0 1rem;
  text-align: center; /* centramos el contenido en general */
}

/* TÍTULO - dentro del contenedor, alineado a la izquierda con margen */
h2.titulo-listado {
  color: var(--color-principal);
  font-size: 1.8rem;
  font-weight: bold;
  margin-bottom: 1rem;
  text-align: left;  /* alineamos texto a la izquierda */
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
  background-color: #ffe6d1; /* naranja claro al pasar mouse */
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
  padding-right: 2.5rem; /* espacio para el icono */
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
const inputIdListado = document.getElementById('id_listado');
let seleccionado = null;

fechaInput.addEventListener('change', () => {
  const fecha = fechaInput.value;
  if (!fecha) {
    tabla.style.display = 'none';
    tbody.innerHTML = '';
    btnExportar.disabled = true;
    inputIdListado.value = '';
    return;
  }

  fetch('buscar_listados_por_fecha.php?fecha=' + fecha)
    .then(res => res.json())
    .then(data => {
      tbody.innerHTML = '';

      if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No se encontraron listados para esta fecha.</td></tr>';
        tabla.style.display = 'table';
        btnExportar.disabled = true;
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
        `;

        tr.addEventListener('click', () => {
          if (seleccionado) {
            seleccionado.classList.remove('selected');
          }
          tr.classList.add('selected');
          seleccionado = tr;
          inputIdListado.value = listado.id;
          btnExportar.disabled = false;
        });

        tbody.appendChild(tr);
      });

      tabla.style.display = 'table';
    })
    .catch(err => {
      console.error('Error al buscar listados:', err);
      tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">Error al cargar los listados.</td></tr>';
      tabla.style.display = 'table';
      btnExportar.disabled = true;
      inputIdListado.value = '';
    });
});
</script>

</body>
</html>
