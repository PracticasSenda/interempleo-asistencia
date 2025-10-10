<?php include("validar_sesion.php"); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Gestión de Trabajadores</title>

  <style>
    :root {
      --color-principal: #FF671D;
      --color-fondo: #FFFFFF;
      --color-texto: #333333;
      --color-borde: #CCCCCC;
      --color-input-bg: #F9F9F9;
    }

    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: var(--color-fondo);
    }
    *, *::before, *::after {
  box-sizing: border-box;
}
.formulario {
  max-width: 400px; /* Limita ancho del formulario */
  margin: 0 auto; /* Centra horizontalmente */
  padding: 1rem;
  border-radius: 8px;
  background-color: var(--color-principal);
  color: white;
  box-sizing: border-box;
}

.formulario input[type="text"],
.formulario input[type="number"] {
  width: 100%;
  max-width: 100%;
  padding: 0.6rem;
  border: 1px solid var(--color-borde);
  border-radius: 4px;
  background-color: var(--color-input-bg);
  box-sizing: border-box;
  margin-bottom: 0.8rem;
  font-size: 1rem;
  display: block;
}


    .barra-superior {
      background-color: var(--color-principal);
      color: white;
      padding: 1.5rem 2rem;
      font-size: 1.5rem;
    }

    .barra-superior span {
      font-weight: bold;
    }

    .contenido {
      max-width: 900px;
      margin: 2rem auto;
      padding: 1rem;
    }

    h2 {
      color: var(--color-principal);
      text-align: center;
    }

    .botones {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-bottom: 2rem;
      flex-wrap: wrap;
    }

    .botones button {
      padding: 0.8rem 1.5rem;
      font-size: 1rem;
      border: none;
      border-radius: 5px;
      background-color: var(--color-principal);
      color: white;
      cursor: pointer;
    }

    .botones button:hover {
      background-color: #e65c17;
    }

    .seccion {
      display: none;
    }

    .visible {
      display: block;
    }

    .estado {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .estado.activo::before {
      content: "";
      width: 12px;
      height: 12px;
      background-color: green;
      border-radius: 50%;
      display: inline-block;
    }

    .estado.inactivo::before {
      content: "";
      width: 12px;
      height: 12px;
      background-color: red;
      border-radius: 50%;
      display: inline-block;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
    }

    th, td {
      padding: 0.8rem;
      border: 1px solid #ccc;
      text-align: left;
    }

    th {
      background-color: #f0f0f0;
    }

    input[type="text"] {
      width: 100%;
      padding: 0.6rem;
      border: 1px solid var(--color-borde);
      border-radius: 4px;
      background-color: var(--color-input-bg);
    }

    .formulario {
      background-color: var(--color-principal);
      padding: 1rem;
      border-radius: 8px;
      color: white;
    }

    .formulario label {
      font-weight: bold;
      display: block;
      margin: 0.8rem 0 0.2rem;
    }

    .formulario button {
      margin-top: 1rem;
      width: 100%;
    }

    .mensaje {
      margin-top: 1rem;
      font-weight: bold;
    }

    @media (max-width: 600px) {
      .botones {
        flex-direction: column;
        align-items: center;
      }
    }
    #tabla-trabajadores {
  width: 100%;
  border-collapse: collapse;
}

#tabla-trabajadores th, #tabla-trabajadores td {
  padding: 10px;
  border-bottom: 1px solid #ddd;
  text-align: center;
}
.filtros {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: center;
  margin-bottom: 20px;
}

.filtros label {
  font-weight: bold;
  margin-right: 5px;
  white-space: nowrap;
}

.filtros input[type="text"],
.filtros input[type="number"] {
  padding: 0.5rem;
  border: 1px solid var(--color-borde);
  border-radius: 4px;
  background-color: var(--color-input-bg);
  width: 200px;
  max-width: 100%;
  box-sizing: border-box;
}
@media (max-width: 450px) {
  .formulario {
    max-width: 100%;
    padding: 1rem 0.5rem;
  }
}
.formulario button {
  padding: 0.8rem 1.5rem;
  font-size: 1rem;
  border: none;
  border-radius: 5px;
  background-color: var(--color-principal);
  color: white;
  cursor: pointer;
  width: 100%;
  margin-top: 1rem;
  transition: background-color 0.3s ease;
}

.formulario button:hover {
  background-color: #e65c17;
}
.btn-volver {
  display: inline-block;
  margin-top: 10px;
  padding: 0.4rem 1rem;
  border: 2px solid white;
  color: white;
  background-color: transparent;
  text-decoration: none;
  border-radius: 5px;
  font-size: 1rem;
  transition: background-color 0.3s ease, color 0.3s ease;
}

.btn-volver:hover {
  background-color: white;
  color: var(--color-principal); /* Cambia el texto al color principal al hacer hover */
}
.formulario input[type="text"],
.formulario input[type="number"] {
  color: black;
}
#baja_nombre {
  color: #000; /* o el color que quieras */
  background-color: #F9F9F9; /* asegúrate que contraste */
}
#baja_nombre,
#baja_dni {
  color: black !important;
  background-color: #F9F9F9 !important;
  -webkit-text-fill-color: black !important; /* Safari/Chrome autocomplete fix */
}

#baja_nombre::placeholder,
#baja_dni::placeholder {
  color: #888 !important;
}

input:-webkit-autofill {
  -webkit-box-shadow: 0 0 0px 1000px #F9F9F9 inset !important;
  -webkit-text-fill-color: black !important;
}
.formulario {
  position: relative;
}
#baja_nombre {
  position: relative; /* Por si acaso */
}
select {
  padding: 0.5rem;
  border: 1px solid var(--color-borde);
  border-radius: 4px;
  background-color: var(--color-input-bg);
  width: 200px;
  max-width: 100%;
  box-sizing: border-box;
  font-size: 1rem;
  color: black;
}










  </style>
</head>

<body>
  <div class="barra-superior" style="display: flex; justify-content: space-between; align-items: center;">
  <p style="margin: 0;"><span>Inter</span>empleo - Gestión de trabajadores</p>
  <a href="asistencia_responsive.php" class="btn-volver">Volver a asistencias</a>
</div>

  <div class="contenido">
    <div class="botones">
      <button onclick="mostrarSeccion('alta')">Dar de Alta</button>
      <button onclick="mostrarSeccion('baja')">Dar de Baja</button>
      <button onclick="mostrarSeccion('listado')">Ver Listado</button>
    </div>

    <!-- Aquí irán las secciones (Parte 2, 3 y 4) -->
    <!-- SECCIÓN DAR DE ALTA -->
    <div id="alta" class="seccion">
      <h2>Registrar trabajador</h2>

      <form method="post">
        <input type="hidden" name="seccion_activa" value="alta">
        <div class="formulario">
          <label for="alta_nombre">Nombre:</label>
          <input type="text" id="alta_nombre" name="alta_nombre" required>

          <label for="alta_apellidos">Apellidos:</label>
          <input type="text" id="alta_apellidos" name="alta_apellidos" required>

          <label for="alta_dni">DNI / NIE:</label>
          <input type="text" id="alta_dni" name="alta_dni" required>

          <button type="submit" name="alta_enviar">Registrar</button>
        </div>
      </form>

      <?php
      if (isset($_POST['alta_enviar'])) {
        include("conexion_bd.php");

        $nombre = mysqli_real_escape_string($conexion, strip_tags($_POST['alta_nombre']));
        $apellidos = mysqli_real_escape_string($conexion, strip_tags($_POST['alta_apellidos']));
        $dni = mysqli_real_escape_string($conexion, strip_tags($_POST['alta_dni']));

        $consulta_existencia = "SELECT * FROM trabajadores WHERE dni = '$dni'";
        $resultado = mysqli_query($conexion, $consulta_existencia);

        if (mysqli_num_rows($resultado) > 0) {
          $fila = mysqli_fetch_assoc($resultado);

          if ($fila['activo'] == 0) {
            // Reactivar trabajador
            $activar = "UPDATE trabajadores SET activo = 1, nombre = '$nombre', apellidos = '$apellidos' WHERE dni = '$dni'";
            if (mysqli_query($conexion, $activar)) {
              echo "<p class='mensaje' style='color: green;'>✅ Trabajador reactivado correctamente.</p>";
            } else {
              echo "<p class='mensaje' style='color: red;'>❌ Error al reactivar al trabajador.</p>";
            }
          } else {
            echo "<p class='mensaje' style='color: orange;'>⚠️ Este trabajador ya está dado de alta.</p>";
          }
        } else {
          // Nuevo trabajador
          $insertar = "INSERT INTO trabajadores (nombre, apellidos, dni, activo) VALUES ('$nombre', '$apellidos', '$dni', 1)";
          if (mysqli_query($conexion, $insertar)) {
            echo "<p class='mensaje' style='color: green;'>✅ Trabajador registrado correctamente.</p>";
          } else {
            echo "<p class='mensaje' style='color: red;'>❌ Error al registrar al trabajador.</p>";
          }
        }

        mysqli_close($conexion);
      }
      ?>
    </div>
        <!-- SECCIÓN DAR DE BAJA -->
    <div id="baja" class="seccion">
      <h2>Eliminar trabajador</h2>

      <form method="post">
        <input type="hidden" name="seccion_activa" value="baja">
        <div class="formulario">
          <label for="baja_nombre">Nombre:</label>
          <input type="text" id="baja_nombre" name="baja_nombre" required>

          <label for="baja_dni">DNI / NIE:</label>
          <input type="text" id="baja_dni" name="baja_dni" required>

          <button type="submit" name="baja_enviar">Eliminar</button>
        </div>
      </form>

      <?php
      if (isset($_POST['baja_enviar'])) {
        include("conexion_bd.php");

        $nombre = mysqli_real_escape_string($conexion, strip_tags($_POST['baja_nombre']));
        $dni = mysqli_real_escape_string($conexion, strip_tags($_POST['baja_dni']));

        $consulta = "SELECT id, activo FROM trabajadores WHERE nombre = '$nombre' AND dni = '$dni' LIMIT 1";
        $resultado = mysqli_query($conexion, $consulta);

        if ($resultado && mysqli_num_rows($resultado) > 0) {
          $trabajador = mysqli_fetch_assoc($resultado);

          if ($trabajador['activo'] == 0) {
            echo "<p class='mensaje' style='color: orange;'>⚠️ Este trabajador ya está dado de baja.</p>";
          } else {
            $update = "UPDATE trabajadores SET activo = 0 WHERE id = {$trabajador['id']}";
            if (mysqli_query($conexion, $update)) {
              echo "<p class='mensaje' style='color: green;'>✅ Trabajador dado de baja correctamente.</p>";
            } else {
              echo "<p class='mensaje' style='color: red;'>❌ No se pudo dar de baja al trabajador.</p>";
            }
          }
        } else {
          echo "<p class='mensaje' style='color: red;'>❌ No se encontró ningún trabajador con ese nombre y DNI.</p>";
        }

        mysqli_close($conexion);
      }
      ?>
    </div>
<!-- Parte 4: Listado con filtros -->
<div id="listado" class="seccion">

  <div class="contenido">
    <h2>Listado de trabajadores</h2>

    <div class="filtros">
      <label for="filtro-dni">Filtrar por DNI:</label>
      <input type="text" id="filtro-dni" placeholder="Introduce DNI..." oninput="filtrarTabla()">
      
      <label for="filtro-activo">Activo:</label>
      <select id="filtro-activo">
   <option value="">Todos</option>
   <option value="1">Activo</option>
   <option value="0">Inactivo</option>
</select>

    </div>

    <div class="tarjeta-asistencia" style="overflow-x:auto;">
      <table id="tabla-trabajadores">
        <thead>
          <tr style="background-color: white; color: black;">
            <th>ID</th>
            <th>Nombre</th>
            <th>Apellidos</th>
            <th>DNI</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php
            include("conexion_bd.php");
            $consulta = "SELECT * FROM trabajadores";
            $resultado = mysqli_query($conexion, $consulta);

            while ($fila = mysqli_fetch_assoc($resultado)) {
              $estado = $fila['activo'] == 1 
                ? "<span style='color: green; font-weight: bold;'>● Activo</span>"
                : "<span style='color: red; font-weight: bold;'>● Inactivo</span>";
              echo "<tr>
        <td>{$fila['id']}</td>
        <td>{$fila['nombre']}</td>
        <td>{$fila['apellidos']}</td>
        <td data-dni=\"{$fila['dni']}\">{$fila['dni']}</td>
        <td data-activo=\"{$fila['activo']}\">$estado</td>
      </tr>";

            }
            mysqli_close($conexion);
          ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const filtroDNI = document.getElementById("filtro-dni");
    const filtroActivo = document.getElementById("filtro-activo");
    const filas = document.querySelectorAll("#tabla-trabajadores tbody tr");

    function filtrarTabla() {
      const dniValor = filtroDNI.value.toLowerCase().trim();
      const activoValor = filtroActivo.value.trim();

      filas.forEach(fila => {
        const dni = fila.querySelector("td[data-dni]").textContent.toLowerCase().trim();
        const activo = fila.querySelector("td[data-activo]").dataset.activo;

        const coincideDNI = dni.includes(dniValor);
        const coincideActivo = activoValor === "" || activo === activoValor;

        if (coincideDNI && coincideActivo) {
          fila.style.display = "";
        } else {
          fila.style.display = "none";
        }
      });
    }

    filtroDNI.addEventListener("input", filtrarTabla);
    filtroActivo.addEventListener("change", filtrarTabla);
  });
  

</script>
<script>
function mostrarSeccion(id) {
  const secciones = document.querySelectorAll(".seccion");
  secciones.forEach(sec => sec.style.display = "none");

  const mostrar = document.getElementById(id);
  if (mostrar) {
    mostrar.style.display = "block";
  }
}
</script>
<?php
  $seccion_activa = $_POST['seccion_activa'] ?? '';
?>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    const seccion = "<?php echo $seccion_activa; ?>";
    if (seccion) {
      mostrarSeccion(seccion);
    }
  });
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const inputNombre = document.getElementById("baja_nombre");
  const inputDNI = document.getElementById("baja_dni");

  // Crear contenedor de sugerencias
  const sugerencias = document.createElement("div");
  sugerencias.id = "sugerencias-autocompletado";
  sugerencias.style.position = "absolute";
  sugerencias.style.backgroundColor = "rgba(255, 255, 255, 0.95)";
  sugerencias.style.border = "none";
  sugerencias.style.zIndex = "1000";
  sugerencias.style.maxHeight = "150px";
  sugerencias.style.overflowY = "auto";
  sugerencias.style.display = "none";

  inputNombre.parentNode.appendChild(sugerencias);

  inputNombre.addEventListener("input", function() {
    const valor = this.value;

    if (valor.length < 2) {
      sugerencias.style.display = "none";
      return;
    }

    fetch(`buscar_trabajador_baja.php?query=${encodeURIComponent(valor)}`)
      .then(response => response.json())
      .then(data => {
        sugerencias.innerHTML = "";
        if (data.length > 0) {
          data.forEach(trabajador => {
            const opcion = document.createElement("div");
            opcion.textContent = `${trabajador.dni} - ${trabajador.nombre}`;
            opcion.style.padding = "8px";
            opcion.style.cursor = "pointer";
            opcion.style.color = "black";

            opcion.addEventListener("click", function() {
              inputNombre.value = '';
              inputNombre.offsetHeight; // Forzar reflow
              inputNombre.value = trabajador.nombre;
              inputDNI.value = trabajador.dni;

              // Espera un poco antes de ocultar sugerencias
              setTimeout(() => {
                sugerencias.style.display = "none";
              }, 100);
            });

            sugerencias.appendChild(opcion);
          });

          const rect = inputNombre.getBoundingClientRect();
          const parentRect = inputNombre.parentNode.getBoundingClientRect();

          sugerencias.style.width = rect.width + "px";
          sugerencias.style.left = (rect.left - parentRect.left) + "px";
          sugerencias.style.top = (rect.top - parentRect.top + inputNombre.offsetHeight) + "px";

          sugerencias.style.display = "block";
        } else {
          sugerencias.style.display = "none";
        }
      })
      .catch(error => {
        console.error("Error en la solicitud AJAX:", error);
        sugerencias.style.display = "none";
      });
  });

  // Ocultar sugerencias si se hace clic fuera
  document.addEventListener("click", function(e) {
    if (!sugerencias.contains(e.target) && e.target !== inputNombre) {
      sugerencias.style.display = "none";
    }
  });
});
</script>


</body>
</html>



