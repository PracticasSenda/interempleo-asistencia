<?php
// =======================================================
// ✅ 1. CONEXIÓN A BASE DE DATOS
// =======================================================
include("../config/db.php");
include("../auth/validar_sesion.php"); // Solo para los que lo necesitan


// =======================================================
// ✅ 2. PROCESAMIENTO DEL FORMULARIO (cuando se envía)
// =======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $encargado = mysqli_real_escape_string($conexion, $_POST['encargado']);
  $empresa   = mysqli_real_escape_string($conexion, $_POST['empresa']);
  $fecha     = mysqli_real_escape_string($conexion, $_POST['fecha']);
  $producto  = mysqli_real_escape_string($conexion, $_POST['producto']);

  // --- Obtener id_encargado desde la tabla usuarios ---
  $sqlEnc = "SELECT id FROM usuarios WHERE nombre='$encargado' LIMIT 1";
  $resEnc = mysqli_query($conexion, $sqlEnc);
  $rowEnc = mysqli_fetch_assoc($resEnc);
  $id_encargado = $rowEnc ? $rowEnc['id'] : null;

  // --- Insertar nuevo listado_asistencias ---
  $sqlListado = "INSERT INTO listados_asistencias (id_encargado, empresa, fecha, producto)
                 VALUES ('$id_encargado', '$empresa', '$fecha', '$producto')";
  mysqli_query($conexion, $sqlListado);
  $id_listado = mysqli_insert_id($conexion);

  // --- Insertar asistencias ---
  $nombres = $_POST['nombres'];
  $dnis = $_POST['dnis'];
  $asistencias = $_POST['asistencia'] ?? [];
  $bandejas = $_POST['bandejas'];
  $horas = $_POST['horas'];
  $observaciones = $_POST['observaciones'];

  for ($i = 0; $i < count($nombres); $i++) {
    $nombre = mysqli_real_escape_string($conexion, $nombres[$i]);
    $dni = mysqli_real_escape_string($conexion, $dnis[$i]);
    $asis = isset($asistencias[$i]) ? 'si' : 'no';
    $ban = mysqli_real_escape_string($conexion, $bandejas[$i]);
    $hor = mysqli_real_escape_string($conexion, $horas[$i]);
    $obs = mysqli_real_escape_string($conexion, $observaciones[$i]);

    // Buscar id_trabajador por DNI
    $sqlTrab = "SELECT id FROM trabajadores WHERE dni='$dni' LIMIT 1";
    $resTrab = mysqli_query($conexion, $sqlTrab);
    $rowTrab = mysqli_fetch_assoc($resTrab);
    $id_trabajador = $rowTrab ? $rowTrab['id'] : 'NULL';

    $sqlAsis = "INSERT INTO asistencias 
      (id_listado, empresa, fecha, producto, asistencia, id_trabajador, dni, bandeja, horas, observaciones)
      VALUES 
      ('$id_listado', '$empresa', '$fecha', '$producto', '$asis', '$id_trabajador', '$dni', '$ban', '$hor', '$obs')";
    mysqli_query($conexion, $sqlAsis);
  }

  echo "<div style='background:#d4edda; color:#155724; padding:10px; text-align:center; font-weight:bold;'>
          ✅ Asistencia guardada correctamente.
        </div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registro de Asistencia</title>
<style>
  :root {
    --naranja: #FF671D;
    --borde: #DDDDDD;
    --texto: #333;
    --bg: #fff;
}
body { font-family: Arial; background: #faf6f0; margin:0; padding:0; }
.container {
  max-width: 1100px; margin: 40px auto; background: #fff; padding: 30px;
  border-radius: 15px; box-shadow: 0 0 10px rgba(0,0,0,0.2);
}
h2 { text-align:center; color:#FF671D; }
label { font-weight:bold; margin-right:10px; }
input, button {
  padding:8px; border:1px solid #ccc; border-radius:6px; margin-bottom:10px;
}
button { cursor:pointer; background-color:#FF671D; color:white; border:none; }
button:hover { background-color:#e26f00; }
.form-grupo { display:flex; flex-wrap:wrap; gap:20px; margin-bottom:15px; }
.form-grupo div { flex:1; min-width:200px; }
.sugerencias {
  position:absolute; background:white; border:1px solid #ddd; border-radius:6px;
  box-shadow:0 2px 5px rgba(0,0,0,0.1); width:250px; z-index:100;
}
.sugerencias div { padding:5px 10px; cursor:pointer; }
.sugerencias div:hover { background:#f2f2f2; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { border:1px solid #ddd; padding:10px; text-align:center; }
th { background:#FF671D; color:white; }
.btnToggle, { background:none; border:none; font-weight:bold; cursor:pointer; }
.btnToggle { color:#FF671D; } 
.modal {
  display:none; position:fixed; top:0; left:0; width:100%; height:100%;
  background-color:rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:999;
}
.modal-contenido {
  background:white; padding:20px; border-radius:15px; width:60%;
  max-height:80vh; overflow-y:auto; box-shadow:0 0 15px rgba(0,0,0,0.3);
}
.modal-botones { display:flex; justify-content:space-between; margin-top:20px; }
.modal-botones button { padding:10px 20px; border-radius:8px; cursor:pointer; font-weight:bold; }
#btnCancelar { background-color:#ccc; }
#btnConfirmar { background-color:#FF671D; color:white; }
/* =========================================================
🔸 BARRA SUPERIOR / ENCABEZADO
========================================================= */
.barra-superior {
    background-color: var(--naranja);
    color: white;
    padding: 1.2rem 2rem;
    font-size: 1.4rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    z-index: 1000;
}

.contenedor-barra {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.lado-izquierdo {
    display: flex;
    align-items: center;
    gap: 12px;
}

.lado-izquierdo p {
    margin: 0;
    font-size: 1.4rem;
}

.lado-izquierdo span {
    font-weight: bold;
}

/* === Mensaje de bienvenida === */
.bienvenida {
    font-size: 1rem;
    font-weight: bold;
    text-align: right;
    white-space: nowrap;
}

/* =========================================================
🍔 MENÚ HAMBURGUESA Y DESPLEGABLE
========================================================= */
.menu-toggle {
    font-size: 1.8rem;
    cursor: pointer;
    background: none;
    border: none;
    color: white;
    transition: transform 0.2s;
}

.menu-toggle:hover {
    transform: scale(1.15);
}

.menu-dropdown {
    display: none;
    flex-direction: column;
    position: absolute;
    top: 100%;
    left: 2rem;
    background: #fff;
    border: 1px solid #ddd;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
    border-radius: 8px;
    overflow: hidden;
    animation: fadeIn 0.2s ease;
    min-width: 230px;
    z-index: 9999;
}

.menu-dropdown.show {
    display: flex;
}

.menu-dropdown a {
    color: #333;
    padding: 12px 16px;
    text-decoration: none;
    border-bottom: 1px solid #eee;
    font-weight: 500;
}

.menu-dropdown a:hover {
    background-color: #f9f9f9;
    color: var(--naranja);
}

.menu-dropdown a.activo {
    background-color: #ffe8dc;
    color: var(--naranja);
    font-weight: bold;
}

.menu-dropdown a:last-child {
    border-bottom: none;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-5px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
/* === FORMULARIO CABECERA === */
.form-header {
  background-color: #FFE7D3;
  border-radius: 12px;
  padding: 1rem 1.5rem;
  margin-bottom: 1.5rem;
}

.fila {
  display: flex;
  gap: 1rem;
  margin-bottom: 0.8rem;
}

.campo {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.campo label {
  font-weight: 600;
  color: var(--naranja);
  margin-bottom: 0.3rem;
}

.campo input {
  padding: 0.6rem;
  border: 1px solid #ccc;
  border-radius: 6px;
  font-size: 0.95rem;
}

/* === SEPARADOR === */
hr {
  border: none;
  border-top: 2px solid #f0f0f0;
  margin: 1.5rem 0;
}

/* === TABLA === */
#tablaTrabajadores {
  border-collapse: collapse;
  width: 100%;
  margin-top: 1rem;
}

#tablaTrabajadores th {
  background-color: var(--naranja);
  color: white;
  padding: 10px;
}

#tablaTrabajadores td {
  border: 1px solid #ddd;
  padding: 8px;
  position: relative;
}

.fila-detalle {
  background-color: #FFF8D5; /* amarillo suave */
}

/* === BOTONES === */
.btnToggle {
  background-color: var(--naranja);
  color: white;
  border: none;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  font-size: 1rem;
  font-weight: bold;
  cursor: pointer;
  transition: 0.2s;
}

.btnToggle:hover {
  background-color: #e25e1b;
}


/* === Botón X (eliminar fila, superpuesto en la esquina superior derecha) === */
/* Celda de acciones: crea el contexto posicional */
td.acciones {
  position: relative;
  vertical-align: top; /* mantiene el botón en la esquina superior */
  height: 40px;
}

/* Botón eliminar flotante */
.btnEliminar {
  position: absolute;
  top: -6px;
  right: -6px;
  background: #ff4d4d;
  color: white;
  border: none;
  border-radius: 50%;
  width: 22px;
  height: 22px;
  font-size: 14px;
  font-weight: bold;
  cursor: pointer;
  display: flex;               /* 🔹 centrado perfecto */
  align-items: center;         /* 🔹 centrado vertical */
  justify-content: center;     /* 🔹 centrado horizontal */
  transition: 0.2s ease;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
  z-index: 10;
}

.btnEliminar:hover {
  background: #d90429;
  transform: scale(1.1);
}




/* Grupo de entrada DNI + botón */
.dni-group {
  display: flex;
  align-items: center;
  gap: 0.5rem; /* espacio entre input y botón */
  margin-bottom: 1rem;
}

/* Botón añadir trabajador */
#btnAñadirTrabajador {
  background-color: var(--naranja);
  color: white;
  border: none;
  border-radius: 6px;
  padding: 0.6rem 1rem;
  cursor: pointer;
  font-weight: 600;
  transition: 0.2s;
}

#btnAñadirTrabajador:hover {
  background-color: #e25e1b;
}
/* === CONTENEDOR PRINCIPAL DEL BLOQUE === */
.grupo-buscar-trabajador {
  position: relative;
  margin-bottom: 1.5rem;
  width: 100%;
}

/* === ETIQUETA "Buscar trabajador" === */
.label-buscar {
  display: block;
  font-weight: 600;
  color: var(--texto);
  margin-bottom: 0.4rem;
  font-size: 0.95rem;
}

/* === CONTENEDOR FLEX PARA INPUT + BOTÓN === */
.input-boton-buscar {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

/* === INPUT DE BÚSQUEDA === */
.input-buscar {
  flex: 1;
  padding: 0.6rem 0.8rem;
  border: 1px solid var(--borde);
  border-radius: 6px;
  font-size: 0.95rem;
  color: var(--texto);
  transition: all 0.2s;
}

.input-buscar:focus {
  border-color: var(--naranja);
  box-shadow: 0 0 0 3px rgba(255, 103, 29, 0.2);
  outline: none;
}

/* === BOTÓN NARANJA "Agregar trabajador" === */
.btn-agregar {
  background-color: var(--naranja);
  color: white;
  border: none;
  border-radius: 6px;
  padding: 0.6rem 1rem;
  cursor: pointer;
  font-weight: 600;
  transition: background-color 0.2s, transform 0.2s;
}

.btn-agregar:hover {
  background-color: #e35c15;
  transform: scale(1.05);
}

/* === LISTA DE SUGERENCIAS AJAX === */
.sugerencias {
  display: none;
  position: absolute;
  top: 100%;
  left: 0;
  width: calc(100% - 0.5rem);
  background-color: white;
  border: 1px solid var(--borde);
  border-radius: 6px;
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
  margin-top: 0.3rem;
  z-index: 1000;
  overflow: hidden;
}

.sugerencias div {
  padding: 0.6rem 0.8rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.sugerencias div:hover {
  background-color: var(--fondo-claro);
}

/* Select de ordenar la tabla */
.ordenar-tabla {
  margin: 10px 0 15px 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.ordenar-tabla label {
  font-weight: bold;
  color: var(--texto);
}

.ordenar-tabla select {
  padding: 4px 8px;
  border: 1px solid var(--borde);
  border-radius: 4px;
  background: var(--bg);
  color: var(--texto);
}


</style>
</head>

<body>
  <!--Encabezado -->
  <?php include __DIR__ . '/header.php'; ?>

<div class="container">
  <h2>Registro de Asistencia</h2>

  <form id="formAsistencia" method="POST" action="">
   <!-- 🔸 Encabezado del formulario -->
<div class="form-header">
  <div class="fila">
    <div class="campo" style="position:relative;">
      <label>Encargado:</label>
      <input type="text" name="encargado" id="inputEncargado" autocomplete="off" required>
      <div id="sugerenciasEncargado" class="sugerencias" style="display:none;"></div>
    </div>
    <div class="campo">
      <label>Fecha:</label>
      <input type="date" name="fecha" value="<?php echo date('Y-m-d'); ?>">
    </div>
  </div>

  <div class="fila">
    <div class="campo">
      <label>Empresa:</label>
      <input type="text" name="empresa" required>
    </div>
    <div class="campo">
      <label>Producto:</label>
      <input type="text" name="producto" required>
    </div>
  </div>
</div>

<hr>


    <div class="form-grupo">
     <!-- 🔹 CONTENEDOR DEL BUSCADOR Y BOTÓN -->
<div class="grupo-buscar-trabajador">
  <label for="buscarTrabajador" class="label-buscar">Buscar trabajador:</label>

  <div class="input-boton-buscar">
    <input type="text" id="buscarTrabajador" autocomplete="off" class="input-buscar">
    <button type="button" id="btnAgregar" class="btn-agregar">+ Agregar trabajador</button>
  </div>

  <!-- 🔸 CONTENEDOR DE SUGERENCIAS (se llena por AJAX) -->
  <div id="sugerenciasTrabajador" class="sugerencias"></div>
</div>

</div>

    <!-- 🔽 Selector de orden -->
<div class="ordenar-tabla">
  <label for="selectOrden">Ordenar por:</label>
  <select id="selectOrden">
    <option value="none">Sin ordenar</option>
    <option value="nombre">Nombre</option>
    <option value="dni">DNI</option>
  </select>
</div>
    <!-- 🔸 TABLA DE TRABAJADORES AÑADIDOS -->
    <table id="tablaTrabajadores">
      <thead>
        <tr><th>Asistencia</th><th>Nombre</th><th>DNI</th><th>Acciones</th></tr>
      </thead>
      <tbody></tbody>
    </table>

    <br>
    <button type="submit">Guardar asistencia</button>
  </form>
</div>

<div id="modalConfirmacion" class="modal">
  <div class="modal-contenido">
    <h2>Confirmar envío</h2>
    <div id="resumenDatos"></div>
    <div class="modal-botones">
      <button type="button" id="btnCancelar">Cancelar</button>
      <button type="button" id="btnConfirmar">Confirmar y Guardar</button>
    </div>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>

<script>
// --- Buscar encargados (AJAX) ---
const inputEncargado=document.getElementById('inputEncargado');
const sugerenciasEncargado=document.getElementById('sugerenciasEncargado');
inputEncargado.addEventListener('input',function(){
  const term=this.value.trim();
  if(term.length<1){sugerenciasEncargado.style.display='none';return;}
  fetch('../funciones/funciones_buscar.php?accion=buscar_encargado&term=' + encodeURIComponent(term))
  .then(res=>res.json())
  .then(data=>{
    sugerenciasEncargado.innerHTML='';
    data.forEach(obj=>{
      const div=document.createElement('div');
      div.textContent=obj.nombre+' - '+obj.dni;
      div.onclick=()=>{inputEncargado.value=obj.nombre;sugerenciasEncargado.style.display='none';};
      sugerenciasEncargado.appendChild(div);
    });
    sugerenciasEncargado.style.display='block';
  });
});

// --- Buscar trabajadores (AJAX) ---
const buscar=document.getElementById('buscarTrabajador');
const sugerenciasTrabajador=document.getElementById('sugerenciasTrabajador');
let trabajadorSeleccionado=null;
buscar.addEventListener('input',()=>{
  const term=buscar.value.trim();
  if(term.length<1){sugerenciasTrabajador.style.display='none';return;}
 fetch('../funciones/funciones_buscar.php?accion=buscar_sugerencias&term=' + encodeURIComponent(term))
  .then(res => res.json())
  .then(data => {
    sugerenciasTrabajador.innerHTML = '';

    if (data.length === 0) {
      sugerenciasTrabajador.innerHTML = '<div style="padding:0.5rem;color:#888;">No se encontraron coincidencias</div>';
      sugerenciasTrabajador.style.display = 'block';
      return;
    }

    data.forEach(obj => {
      const div = document.createElement('div');
      div.textContent = obj.nombre + ' - ' + obj.dni;
      div.onclick = () => {
        trabajadorSeleccionado = obj;
        buscar.value = obj.nombre + ' - ' + obj.dni;
        sugerenciasTrabajador.style.display = 'none';
      };
      sugerenciasTrabajador.appendChild(div);
    });

    sugerenciasTrabajador.style.display = 'block';
  })
  .catch(err => console.error('Error al buscar trabajador:', err));
});

// --- Agregar trabajador ---
const tabla=document.querySelector('#tablaTrabajadores tbody');
document.getElementById('btnAgregar').addEventListener('click',()=>{
  if (!trabajadorSeleccionado) {
    alert("Selecciona un trabajador primero");
    return;
  }

  const dniNuevo = trabajadorSeleccionado.dni.trim();

  // 🔍 Verificar si el trabajador ya está en la tabla (por DNI)
  const dniExistentes = Array.from(document.querySelectorAll('input[name="dnis[]"]'))
                            .map(input => input.value.trim());
  
  if (dniExistentes.includes(dniNuevo)) {
    alert("⚠️ Este trabajador ya está en la lista.");
    return; // 🔒 Detiene la ejecución
  }
  const fila1=document.createElement('tr');
  fila1.classList.add('fila-principal');
  fila1.innerHTML = `
  <td><input type="checkbox" name="asistencia[]" checked></td>
  <td><input type="text" name="nombres[]" value="${trabajadorSeleccionado.nombre}" readonly></td>
  <td><input type="text" name="dnis[]" value="${trabajadorSeleccionado.dni}" readonly></td>
  <td class="acciones">
      <button type="button" class="btnToggle">+</button>
      <button type="button" class="btnEliminar">×</button>
  </td>`;



        fila1.style.position = 'relative';

  const fila2=document.createElement('tr');
  fila2.classList.add('fila-detalle');
  fila2.style.display='none';
  fila2.innerHTML=`
    <td colspan="4">
      <label>Bandeja:</label><input type="number" name="bandejas[]" style="width:80px;">
      <label>Horas:</label><input type="number" name="horas[]" style="width:80px;">
      <label>Observaciones:</label><input type="text" name="observaciones[]" style="width:200px;">
    </td>`;
  
  tabla.appendChild(fila1); 
  tabla.appendChild(fila2);
  
  trabajadorSeleccionado=null; buscar.value='';
});
tabla.addEventListener('click',(e)=>{
  if(e.target.classList.contains('btnToggle')){
    const fila=e.target.closest('tr');
    const detalle=fila.nextElementSibling;
    const visible=detalle.style.display==='table-row';
    detalle.style.display=visible?'none':'table-row';
    e.target.textContent=visible?'+':'-';
  }
  if(e.target.classList.contains('btnEliminar')){
    const fila=e.target.closest('tr'); fila.nextElementSibling.remove(); fila.remove();
  }
});

// --- Modal ---
const modal=document.getElementById("modalConfirmacion");
const resumen=document.getElementById("resumenDatos");
document.getElementById("formAsistencia").addEventListener("submit",function(e){
  e.preventDefault(); mostrarResumen();
});
function mostrarResumen(){
  const encargado=document.querySelector("input[name='encargado']").value;
  const empresa=document.querySelector("input[name='empresa']").value;
  const fecha=document.querySelector("input[name='fecha']").value;
  const producto=document.querySelector("input[name='producto']").value;
  let html=`<p><b>Encargado:</b> ${encargado}</p>
  <p><b>Empresa:</b> ${empresa}</p><p><b>Fecha:</b> ${fecha}</p><p><b>Producto:</b> ${producto}</p><hr>
  <table border="1" cellpadding="5"><tr><th>Asistencia</th><th>Nombre</th><th>DNI</th><th>Bandeja</th><th>Horas</th><th>Obs.</th></tr>`;
  document.querySelectorAll(".fila-principal").forEach(f=>{
    const asis=f.querySelector("input[name='asistencia[]']").checked?"Sí":"No";
    const nom=f.querySelector("input[name='nombres[]']").value;
    const dni=f.querySelector("input[name='dnis[]']").value;
    const det=f.nextElementSibling;
    html+=`<tr><td>${asis}</td><td>${nom}</td><td>${dni}</td>
      <td>${det.querySelector("input[name='bandejas[]']").value}</td>
      <td>${det.querySelector("input[name='horas[]']").value}</td>
      <td>${det.querySelector("input[name='observaciones[]']").value}</td></tr>`;
  });
  html+=`</table>`; resumen.innerHTML=html; modal.style.display="flex";
}
document.getElementById("btnCancelar").onclick=()=>modal.style.display="none";
document.getElementById("btnConfirmar").onclick=()=>{modal.style.display="none"; document.getElementById("formAsistencia").submit();};

//Reordenar los trabajadores en la tabla
// --- ORDENAR TABLA ---
// (1) Seleccionamos el <select> del DOM (el control que el usuario cambia)
const selectOrden = document.getElementById('selectOrden');

// (2) Añadimos un "listener" que se ejecuta cada vez que cambia la opción del select
selectOrden.addEventListener('change', () => {

  // (3) Leemos qué opción ha elegido el usuario ("none", "nombre" o "dni")
  const opcion = selectOrden.value;

  // (4) Seleccionamos todas las filas principales de la tabla y las convertimos a array
  //     (usamos .fila-principal para no incluir las filas de detalle que están ocultas)
  const filasPrincipales = Array.from(document.querySelectorAll('#tablaTrabajadores tbody .fila-principal'));

  // (5) Creamos un array de objetos con la información útil de cada fila:
  //     { fila, filaDetalle, nombre, dni }
  const filasDatos = filasPrincipales.map(fila => {
    const filaDetalle = fila.nextElementSibling; // la fila de detalle que sigue justo después
    const nombre = fila.querySelector('input[name="nombres[]"]').value.toLowerCase();
    const dni = fila.querySelector('input[name="dnis[]"]').value.toLowerCase();
    return { fila, filaDetalle, nombre, dni };
  });

  // (6) Según la opción, ordenamos el array por nombre o por dni
  if (opcion === 'nombre') {
    filasDatos.sort((a, b) => a.nombre.localeCompare(b.nombre));
  } else if (opcion === 'dni') {
    filasDatos.sort((a, b) => a.dni.localeCompare(b.dni));
  }
  // Si opcion === 'none', no hacemos sort y mantenemos el orden actual

  // (7) Vaciamos el <tbody> para reinsertar las filas en el orden nuevo
  const tbody = document.querySelector('#tablaTrabajadores tbody');
  tbody.innerHTML = '';

  // (8) Recorremos el array ordenado e insertamos cada par (fila principal + fila detalle)
  filasDatos.forEach(({ fila, filaDetalle }) => {
    tbody.appendChild(fila);
    tbody.appendChild(filaDetalle);
  });
});





</script>


</body>
</html>


