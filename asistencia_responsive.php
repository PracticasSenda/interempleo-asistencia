<?php
include("validar_sesion.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Asistencias</title>
  <style>
    :root {
      --color-principal: #FF671D;
      --color-fondo: #FFFFFF;
      --color-texto: #333333;
      --color-borde: #CCCCCC;
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

    /* ENCABEZADO */
    .barra-superior {
      background-color: var(--color-principal);
      color: white;
      padding: 1.5rem 2rem;
      font-size: 1.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
    }

    .barra-superior p {
      margin: 0;
      font-weight: normal;
      font-size: 1.4rem;
    }

    .barra-superior span {
      font-weight: bold;
    }

    .barra-superior a {
      color: white;
      text-decoration: none;
      border: 2px solid white;
      padding: 0.5rem 1rem;
      border-radius: 4px;
      font-size: 0.95rem;
    }

    /* CONTENIDO */
    .contenido {
      max-width: 800px;
      margin: 3rem auto;
      padding: 0 1rem;
    }

    h2 {
      color: var(--color-principal);
      margin-bottom: 2rem;
      text-align: center;
      font-size: 1.8rem;
    }

    .formulario-tabla {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 2rem;
    }

    .formulario-tabla td {
      padding: 0.5rem 0;
    }

    label {
      font-weight: bold;
      color: var(--color-texto);
      display: block;
      margin-bottom: 0.4rem;
    }

    input[type="text"],
    input[type="date"] {
      width: 100%;
      padding: 0.6rem;
      border: 1px solid var(--color-borde);
      border-radius: 4px;
      background-color: var(--color-fondo);
      font-size: 1rem;
    }

    /* TARJETA ASISTENCIA */
    .tarjeta-asistencia {
      background-color: var(--color-principal);
      color: white;
      padding: 1.5rem;
      margin-bottom: 2rem;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      font-size: 1rem;
    }

    .tarjeta-asistencia table {
      width: 100%;
      border-collapse: collapse;
    }

    .tarjeta-asistencia td {
      padding: 0.75rem;
    }

    .tarjeta-asistencia label {
      color: white;
    }

    .tarjeta-asistencia input[type="text"] {
      width: 100%;
      padding: 0.6rem;
      border: none;
      border-radius: 4px;
      font-size: 1rem;
    }

    .tarjeta-asistencia input[type="checkbox"] {
      transform: scale(1.3);
      cursor: pointer;
    }

    /* BOTÓN */
    button {
      margin-top: 1rem;
      padding: 0.9rem;
      background-color: var(--color-principal);
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 1.1rem;
      cursor: pointer;
      width: 100%;
    }

    button:hover {
      background-color: #e65c17;
    }

    /* RESPONSIVE: TABLETS Y MÓVILES */
    @media (max-width: 768px) {
      .barra-superior {
        flex-direction: column;
        align-items: flex-start;
      }

      .barra-superior p, .barra-superior a {
        font-size: 1.1rem;
        margin-top: 0.5rem;
      }

      h2 {
        font-size: 1.5rem;
      }
    }

    @media (max-width: 600px) {
      .tarjeta-asistencia table,
      .tarjeta-asistencia tr,
      .tarjeta-asistencia td {
        display: block;
        width: 100%;
      }

      .tarjeta-asistencia td {
        margin-bottom: 1rem;
        padding: 0;
      }

      .formulario-tabla td {
        display: block;
        width: 100%;
      }

      button {
        font-size: 1rem;
        padding: 0.8rem;
      }
    }
    #btn_agregar {
  margin-bottom: 1rem;
}#buscar_dni {
  width: 100%;
  padding: 0.5rem;
}

#sugerencias {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #ccc;
  max-height: 150px;
  overflow-y: auto;
  z-index: 1000;
  font-size: 0.9rem;
}

#sugerencias div:hover {
  background-color: #f0f0f0;
  cursor: pointer;
}

  </style>
</head>
<body>

<div class="barra-superior">
  <p><span>Inter</span>empleo - Asistencia</p>
  <a href="darse_de_alta_responsive.php">Darse de alta</a>
  <a href="darse_de_baja_responsive.php">Darse de baja</a>
  <a href="cerrar_sesion.php">Cerrar sesión</a>

  <?php
  if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador') {
      echo '<a href="darse_de_alta_responsive_encargados.php">Darse de alta encargados</a>';
      echo '<a href="darse_de_baja_responsive_encargados.php">Darse de baja encargados</a>';
  }
  ?>
</div>

<div class="contenido">
  <h2>Parte de asistencia</h2>

  <form id="form-general" method="post" action="">
    <table class="formulario-tabla">
      <!-- campos generales: nombre_encargado, empresa, fecha, producto -->
      <tr>
        <td><label for="nombre_encargado">Nombre del encargado:</label></td>
        <td><input type="text" name="nombre_encargado" id="nombre_encargado" required></td>
      </tr>
      <tr>
        <td><label for="empresa">Empresa usuaria:</label></td>
        <td><input type="text" name="empresa" id="empresa" required></td>
      </tr>
      <tr>
        <td><label for="fecha">Fecha:</label></td>
        <td><input type="date" name="fecha" id="fecha" value="<?php echo date('Y-m-d'); ?>" required></td>
      </tr>
      <tr>
        <td><label for="producto">Producto:</label></td>
        <td><input type="text" name="producto" id="producto" required></td>
      </tr>
    </table>

   <!-- Búsqueda para agregar trabajador por DNI -->
<div style="position: relative; width: 250px; display: inline-block;">
  <input type="text" id="buscar_dni" name="buscar_dni" autocomplete="off" placeholder="Introduce DNI">
  <div id="sugerencias"></div>
</div>

<button type="button" id="btn_agregar">Agregar</button>


    <!-- Aquí se van a insertar dinámicamente las tarjetas -->
    <div id="contenedor_tarjetas">
      <!-- tarjetas generadas aparecerán aquí -->
    </div>

    <button type="submit" name="enviar">Guardar asistencias</button>
  </form>
</div>

<script>
// JavaScript para manejar la búsqueda y agregar tarjetas
document.getElementById('btn_agregar').addEventListener('click', function() {
  let dni = document.getElementById('buscar_dni').value.trim();
  if (dni === '') {
    alert('Introduce un DNI');
    return;
  }

  // Comprobar que esa tarjeta no ya esté añadida
  if (document.getElementById('tarjeta_' + dni)) {
    alert('Ya has añadido ese trabajador');
    return;
  }

  // Petición AJAX al servidor para obtener datos del trabajador
  fetch('buscar_trabajador.php?dni=' + encodeURIComponent(dni))
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        alert(data.error);
      } else {
        // Construir la tarjeta HTML
        let cont = document.getElementById('contenedor_tarjetas');
        let div = document.createElement('div');
        div.className = 'tarjeta-asistencia';
        div.id = 'tarjeta_' + dni;

        // puedes construir más elegante con backticks
        div.innerHTML = `
          <table>
            <tr>
              <td style="text-align:center;">
                <label>Asistencia:</label>
                <input type="checkbox" name="asistencia_${dni}" value="si">
              </td>
              <td colspan="2">
                <label>Nombre completo:</label>
                <input type="text" name="nombre_${dni}" value="${data.nombre}" readonly>
              </td>
            </tr>
            <tr>
              <td>
                <label>DNI:</label>
                <input type="text" name="dni_${dni}" value="${dni}" readonly>
              </td>
              <td>
                <label>Bandejas:</label>
                <input type="text" name="bandejas_${dni}">
              </td>
              <td>
                <label>Horas:</label>
                <input type="text" name="horas_${dni}">
              </td>
            </tr>
            <tr>
              <td colspan="3">
                <label>Observaciones:</label>
                <input type="text" name="observaciones_${dni}">
              </td>
            </tr>
          </table>
        `;

        cont.appendChild(div);
      }
    })
    .catch(error => {
      console.error('Error en fetch:', error);
      alert('Error al buscar trabajador');
    });
});
</script>

<?php
if (isset($_POST['enviar'])) {
    include("conexion_bd.php");

    $nombre_encargado = mysqli_real_escape_string($conexion, strip_tags($_POST['nombre_encargado']));
    $empresa = mysqli_real_escape_string($conexion, strip_tags($_POST['empresa']));
    $fecha = mysqli_real_escape_string($conexion, strip_tags($_POST['fecha']));
    $producto = mysqli_real_escape_string($conexion, strip_tags($_POST['producto']));

    // Recorremos los trabajadores añadidos. Una forma es buscar en $_POST todas las claves que empiecen por "dni_"
    foreach ($_POST as $clave => $valor) {
        if (strpos($clave, 'dni_') === 0) {
            // la clave es tipo "dni_12345678"
            $dni = $valor;
            // extraer el sufijo para los otros campos
            $suffix = substr($clave, 4); // por ej. "12345678"
            $nombre = mysqli_real_escape_string($conexion, strip_tags($_POST['nombre_' . $suffix]));
            $asistencia = isset($_POST['asistencia_' . $suffix]) ? 'si' : 'no';
            $bandejas = mysqli_real_escape_string($conexion, strip_tags($_POST['bandejas_' . $suffix]));
            $horas = mysqli_real_escape_string($conexion, strip_tags($_POST['horas_' . $suffix]));
            $observaciones = '';
            if (isset($_POST['observaciones_' . $suffix])) {
                $observaciones = mysqli_real_escape_string($conexion, strip_tags($_POST['observaciones_' . $suffix]));
            }

            $sql = "INSERT INTO asistencias VALUES (NULL, '$nombre_encargado', '$empresa', '$fecha', '$producto', '$asistencia', '$nombre', '$dni', '$bandejas', '$horas', '$observaciones')";
            mysqli_query($conexion, $sql);
        }
    }

    mysqli_close($conexion);

    echo "<p style='margin: 3rem auto; color: green; font-weight: bold; text-align: center;'>Asistencias guardadas correctamente</p>";
}
?>
<script>
const inputDni = document.getElementById('buscar_dni');
const contenedorSugerencias = document.getElementById('sugerencias');

inputDni.addEventListener('input', function () {
  const texto = this.value.trim();

  if (texto.length < 2) {
    contenedorSugerencias.innerHTML = '';
    return;
  }

  fetch('buscar_sugerencias.php?term=' + encodeURIComponent(texto))
    .then(res => res.json())
    .then(data => {
      contenedorSugerencias.innerHTML = '';

      if (data.length === 0) {
        contenedorSugerencias.innerHTML = '<div style="padding: 0.5rem; color: #888;">No se encontraron coincidencias</div>';
        return;
      }

      // Usamos "item" como nombre del elemento para mayor claridad
      data.forEach(item => {
        const opcion = document.createElement('div');
        opcion.textContent = `${item.dni} - ${item.nombre}`;
        opcion.style.padding = '0.5rem';
        opcion.style.cursor = 'pointer';
        opcion.style.borderBottom = '1px solid #ccc';

        opcion.addEventListener('click', function () {
          inputDni.value = item.dni;
          contenedorSugerencias.innerHTML = '';
        });

        contenedorSugerencias.appendChild(opcion);
      });
    })
    .catch(err => {
      console.error('Error en sugerencias:', err);
    });
});

// Oculta sugerencias si haces clic fuera
document.addEventListener('click', function (e) {
  if (!contenedorSugerencias.contains(e.target) && e.target !== inputDni) {
    contenedorSugerencias.innerHTML = '';
  }
});
</script>


</body>
</html>
