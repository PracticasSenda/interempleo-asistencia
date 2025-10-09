<?php
include("validar_sesion.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dar de baja</title>

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

    .tarjeta-asistencia {
      background-color: var(--color-principal);
      color: white;
      padding: 1.5rem;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      font-size: 1rem;
      margin-bottom: 2rem;
    }

    .tarjeta-asistencia table {
      width: 100%;
      border-collapse: collapse;
    }

    .tarjeta-asistencia td {
      padding: 0.75rem;
    }

    .tarjeta-asistencia label {
      display: block;
      margin-bottom: 0.4rem;
      font-weight: bold;
      color: white;
    }

    .tarjeta-asistencia input[type="text"] {
      width: 100%;
      padding: 0.6rem;
      border: none;
      border-radius: 4px;
      font-size: 1rem;
    }

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

    @media (max-width: 768px) {
      h2 {
        font-size: 1.5rem;
      }

      .tarjeta-asistencia td {
        display: block;
        width: 100%;
        margin-bottom: 1rem;
      }
    }

    @media (max-width: 480px) {
      .barra-superior {
        font-size: 1.2rem;
        padding: 1rem;
      }

      button {
        font-size: 1rem;
        padding: 0.8rem;
      }
    }

.contenedor-barra {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.boton-enlace {
  color: white;
  background-color: transparent;
  border: 2px solid white;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  text-decoration: none;
  font-size: 1rem;
}
@media (max-width: 600px) {
  .contenedor-barra {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .boton-enlace {
    align-self: flex-end;
    font-size: 0.9rem;
    padding: 0.4rem 0.8rem;
  }
}


  </style>
</head>

<body>
  <!-- Encabezado -->
  <div class="barra-superior">
  <div class="contenedor-barra">
    <p><span>Inter</span>empleo - Eliminar</p>
    <a class="boton-enlace" href="asistencia_responsive.php">Volver a asistencias</a>
  </div>
</div>

  <!-- Contenido -->
  <div class="contenido">
    <h2>Eliminar usuario</h2>

    <form method="post" action="darse_de_baja_responsive.php">
      <div class="tarjeta-asistencia">
        <table>
          <tr>
            <td colspan="3">
              <label for="nombre">Nombre:</label>
              <input type="text" id="nombre" name="nombre" required>
            </td>
            
          </tr>
          <tr>
            <td colspan="3">
              <label for="dni">DNI / NIE:</label>
              <input type="text" id="dni" name="dni" required>
            </td>
          </tr>
        </table>
      </div>

      <button type="submit" name="enviar">Eliminar</button>
    </form>

    <?php
if (isset($_POST['enviar'])) {
    $nombre = strip_tags($_POST['nombre']);
    $dni = strip_tags($_POST['dni']);

    include("conexion_bd.php");

    $nombre = mysqli_real_escape_string($conexion, $nombre);
    $dni = mysqli_real_escape_string($conexion, $dni);

    // Buscar el trabajador por nombre y dni
    $query = "SELECT id, activo FROM trabajadores WHERE nombre = '$nombre' AND dni = '$dni' LIMIT 1";
    $result = mysqli_query($conexion, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $trabajador = mysqli_fetch_assoc($result);

        if ($trabajador['activo'] == 0) {
            echo "<p style='margin-top: 1rem; color: orange; font-weight: bold;'>⚠️ Este trabajador ya está dado de baja.</p>";
        } else {
            // Dar de baja (cambio lógico)
            $update = "UPDATE trabajadores SET activo = 0 WHERE id = {$trabajador['id']}";
            mysqli_query($conexion, $update);

            if (mysqli_affected_rows($conexion) > 0) {
                echo "<p style='margin-top: 1rem; color: green; font-weight: bold;'>✅ Trabajador dado de baja correctamente.</p>";
            } else {
                echo "<p style='margin-top: 1rem; color: red; font-weight: bold;'>❌ No se pudo actualizar el estado del trabajador.</p>";
            }
        }
    } else {
        echo "<p style='margin-top: 1rem; color: red; font-weight: bold;'>❌ No se encontró ningún trabajador con ese nombre y DNI.</p>";
    }

    mysqli_close($conexion);
}
?>

  </div>

  <script>
document.addEventListener("DOMContentLoaded", function () {
  const nombreInput = document.getElementById("nombre");
  const dniInput = document.getElementById("dni");
  let sugerenciasBox;

  // Crear caja de sugerencias
  sugerenciasBox = document.createElement("div");
  sugerenciasBox.style.position = "absolute";
  sugerenciasBox.style.backgroundColor = "white";
  sugerenciasBox.style.border = "1px solid #ccc";
  sugerenciasBox.style.zIndex = "9999";
  sugerenciasBox.style.width = nombreInput.offsetWidth + "px";
  sugerenciasBox.style.maxHeight = "200px";
  sugerenciasBox.style.overflowY = "auto";
  sugerenciasBox.style.display = "none";
  document.body.appendChild(sugerenciasBox);

  nombreInput.addEventListener("input", function () {
    const query = this.value;
    if (query.length < 2) {
      sugerenciasBox.style.display = "none";
      return;
    }

    fetch(`buscar_trabajadores.php?q=${encodeURIComponent(query)}`)
      .then(res => res.json())
      .then(data => {
        if (data.length === 0) {
          sugerenciasBox.style.display = "none";
          return;
        }

        // Posicionar la caja debajo del input
        const rect = nombreInput.getBoundingClientRect();
        sugerenciasBox.style.top = window.scrollY + rect.bottom + "px";
        sugerenciasBox.style.left = window.scrollX + rect.left + "px";
        sugerenciasBox.innerHTML = "";

        data.forEach(item => {
          const opcion = document.createElement("div");
          opcion.textContent = item.label;
          opcion.style.padding = "8px";
          opcion.style.cursor = "pointer";

          opcion.addEventListener("click", () => {
            nombreInput.value = item.nombre;
            dniInput.value = item.dni;
            sugerenciasBox.style.display = "none";
          });

          sugerenciasBox.appendChild(opcion);
        });

        sugerenciasBox.style.display = "block";
      });
  });

  // Ocultar sugerencias si se hace clic fuera
  document.addEventListener("click", function (e) {
    if (!sugerenciasBox.contains(e.target) && e.target !== nombreInput) {
      sugerenciasBox.style.display = "none";
    }
  });
});
</script>

</body>
</html>
