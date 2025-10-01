<?php
include("validar_sesion.php")
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
  </style>
</head>
<body>

  <div class="barra-superior">
    <p><span>Inter</span>empleo - Asistencia</p>
    <a href="darse_de_alta_responsive.php">Darse de alta</a>
    <a href="darse_de_baja_responsive.php">Darse de baja</a>
    <a href="cerrar_sesion.php">Cerrar sesión</a>

    <?php
    // Mostrar enlaces solo para administradores
    if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador') {
        echo '<a href="darse_de_alta_responsive_encargados.php">Darse de alta encargados</a>';
        echo '<a href="darse_de_baja_responsive_encargados.php">Darse de baja encargados</a>';
    }
    ?>
</div>

  <!-- CONTENIDO -->
  <div class="contenido">
    <h2>Parte de asistencia</h2>

    <form method="post" action="asistencia_responsive.php">
      <table class="formulario-tabla">
        <tr>
          <td><label for="nombre_encargado">Nombre:</label></td>
        </tr>
        <tr>
          <td><input type="text" name="nombre_encargado" id="nombre_encargado" required></td>
        </tr>
        <tr>
          <td><label for="empresa">Empresa usuaria:</label></td>
        </tr>
        <tr>
          <td><input type="text" name="empresa" id="empresa" required></td>
        </tr>
        <tr>
          <td><label for="fecha">Fecha:</label></td>
        </tr>
        <tr>
          <td><input type="date" name="fecha" id="fecha" required></td>
        </tr>
        <tr>
          <td><label for="producto">Producto:</label></td>
        </tr>
        <tr>
          <td><input type="text" name="producto" id="producto" required></td>
        </tr>
      </table>

      <div class="tarjeta-asistencia">
        <table>
          <tr>
            <td style="text-align: center;">
              <label for="asistencia">Asistencia:</label>
              <input type="checkbox" id="asistencia" name="asistencia" value="si">
            </td>
            <td colspan="2">
              <label for="nombre">Nombre completo:</label>
              <input type="text" id="nombre" name="nombre" required>
            </td>
          </tr>
          <tr>
            <td>
              <label for="dni">DNI:</label>
              <input type="text" id="dni" name="dni" required>
            </td>
            <td>
              <label for="bandejas">Bandejas:</label>
              <input type="text" id="bandejas" name="bandejas" required>
            </td>
            <td>
              <label for="horas">Horas:</label>
              <input type="text" id="horas" name="horas" required>
            </td>
          </tr>
          <tr>
            <td colspan="3">
              <label for="observaciones">Observaciones:</label>
              <input type="text" id="observaciones" name="observaciones" >
            </td>
          </tr>
        </table>
      </div>

      <button type="submit" name="enviar">Guardar</button>
    </form>
  </div>

  <?php 
  if(isset($_POST['enviar'])) {
      $nombre_encargado = strip_tags($_POST['nombre_encargado']);
      $empresa = strip_tags($_POST['empresa']);
      $fecha = strip_tags($_POST['fecha']);
      $producto = strip_tags($_POST['producto']);
      $asistencia = isset($_POST['asistencia']) ? "si" : "no";
      $dni = strip_tags($_POST['dni']);
      $nombre = strip_tags($_POST['nombre']);
      $bandejas = strip_tags($_POST['bandejas']);
      $horas = strip_tags($_POST['horas']);
      $observaciones = strip_tags($_POST['observaciones']) ?? "";

      include("conexion_bd.php");
      $nombre_encargado=mysqli_real_escape_string($conexion, $nombre_encargado);
      $empresa=mysqli_real_escape_string($conexion, $empresa);
      $fecha=mysqli_real_escape_string($conexion, $fecha);
      $producto=mysqli_real_escape_string($conexion, $producto);
      $dni=mysqli_real_escape_string($conexion, $dni);
      $nombre=mysqli_real_escape_string($conexion, $nombre);
      $bandejas=mysqli_real_escape_string($conexion, $bandejas);
      $horas=mysqli_real_escape_string($conexion, $horas);
      $observaciones=mysqli_real_escape_string($conexion, $observaciones);

      $consulta = "INSERT INTO asistencias VALUES (NULL, '$nombre_encargado', '$empresa', '$fecha', '$producto', '$asistencia','$nombre', '$dni', '$bandejas', '$horas', '$observaciones')";
      mysqli_query($conexion, $consulta);
      mysqli_close($conexion);
      echo "<p style='margin: 3rem auto; color: green; font-weight: bold; text-align: center;'>Asistencia guardada correctamente</p>";
  }
  ?>
</body>
</html>
