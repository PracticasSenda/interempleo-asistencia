<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exportar excel</title>
</head>
<body>
    <h3>Exportar asistencia</h3>

<!-- Buscador de listados -->
<div style="position: relative; max-width: 400px; margin-bottom: 1rem;">
  <label for="buscar_listado"><strong>Buscar listado por empresa, producto o fecha:</strong></label>
  <input type="text" id="buscar_listado" placeholder="Ej: Frutas, 2025-10-01, ACME" autocomplete="off" style="width: 100%; padding: 0.5rem;">
  <div id="sugerencias_listado" style="position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ccc; max-height: 200px; overflow-y: auto; z-index: 1000;"></div>
</div>

<!-- Formulario para exportar -->
<form id="form_exportar" method="get" action="funcion_exportar_excel.php" target="_blank">
  <input type="hidden" name="id_listado" id="id_listado">

  <label for="fecha_listado"><strong>Fecha del listado:</strong></label>
  <input type="text" name="fecha" id="fecha_listado" readonly style="margin-bottom: 1rem; display: block; padding: 0.5rem; width: 200px;">

  <button type="submit" disabled id="btn_exportar">Exportar a Excel</button>
</form>

<script>
const inputListado = document.getElementById('buscar_listado');
const sugerenciasListado = document.getElementById('sugerencias_listado');
const inputIdListado = document.getElementById('id_listado');
const inputFechaListado = document.getElementById('fecha_listado');
const btnExportar = document.getElementById('btn_exportar');

inputListado.addEventListener('input', function () {
  const term = this.value.trim();

  if (term.length < 2) {
    sugerenciasListado.innerHTML = '';
    inputIdListado.value = '';
    inputFechaListado.value = '';
    btnExportar.disabled = true;
    return;
  }

  fetch('buscar_listado.php?term=' + encodeURIComponent(term))
    .then(res => res.json())
    .then(data => {
      sugerenciasListado.innerHTML = '';

      if (data.length === 0) {
        sugerenciasListado.innerHTML = '<div style="padding: 0.5rem; color: #888;">No se encontraron coincidencias</div>';
        btnExportar.disabled = true;
        return;
      }

      data.forEach(listado => {
        const div = document.createElement('div');
        div.textContent = `ID: ${listado.id} - ${listado.empresa} / ${listado.producto} (${listado.fecha})`;
        div.style.padding = '0.5rem';
        div.style.cursor = 'pointer';
        div.style.borderBottom = '1px solid #eee';

        div.addEventListener('click', function () {
          inputListado.value = `${listado.empresa} / ${listado.producto} (${listado.fecha})`;
          inputIdListado.value = listado.id;
          inputFechaListado.value = listado.fecha;
          sugerenciasListado.innerHTML = '';
          btnExportar.disabled = false;
        });

        sugerenciasListado.appendChild(div);
      });
    })
    .catch(err => {
      console.error('Error al buscar listados:', err);
      btnExportar.disabled = true;
    });
});

document.addEventListener('click', function (e) {
  if (!sugerenciasListado.contains(e.target) && e.target !== inputListado) {
    sugerenciasListado.innerHTML = '';
  }
});
</script>

    
</body>
</html>