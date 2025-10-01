<?php
session_start();
if (!isset($_SESSION['nombre'])) {
    header("Location: login_responsive.php");
}
?>




<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dar de alta - Encargados </title>

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

    .tarjeta-asistencia input[type="text"],
    .tarjeta-asistencia input[type="password"] {
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
  <!-- ENCABEZADO -->
  <div class="barra-superior">
    <div class="contenedor-barra">
      <p><span>Inter</span>empleo - Registro</p>
      <a class="boton-enlace" href="asistencia_responsive.php">Volver a asistencias</a>
    </div>
  </div>

  <!-- CONTENIDO -->
  <div class="contenido">
    <h2>Registrar usuario</h2>

    <form method="post" action="">
      <div class="tarjeta-asistencia">
        <table>
          <tr>
            <td colspan="2">
              <label for="nombre">Nombre:</label>
              <input type="text" id="nombre" name="nombre" required>
            </td>
            <td>
              <label for="apellidos">Apellidos:</label>
              <input type="text" id="apellidos" name="apellidos" required>
            </td>
          </tr>
          <tr>
            <td colspan="2">
              <label for="dni">DNI / NIE:</label>
              <input type="text" id="dni" name="dni" required>
            </td>
            <td>
              <label for="password">Contraseña:</label>
              <input type="password" id="password" name="password" required>
            </td>
          </tr>
        </table>
      </div>

      <button type="submit" name="enviar">Registrar</button>
    </form>

    <?php
    if (isset($_POST['enviar'])) {
        include("conexion_bd.php");

        $nombre = mysqli_real_escape_string($conexion, strip_tags($_POST['nombre'] ));
        $apellidos = mysqli_real_escape_string($conexion, strip_tags($_POST['apellidos'] ));
        $dni = mysqli_real_escape_string($conexion, strip_tags($_POST['dni'] ));
        $password_raw = strip_tags($_POST['password']);
        $password = mysqli_real_escape_string($conexion, strip_tags($_POST['password'] ));

        $consulta = "INSERT INTO usuarios VALUES (NULL,'$nombre', '$apellidos', '$dni', 'encargado','$password')";

        if (mysqli_query($conexion, $consulta)) {
            echo "<p style='margin-top: 1rem; color: green; font-weight: bold;'>Usuario registrado correctamente</p>";
        } else {
            echo "<p style='margin-top: 1rem; color: red; font-weight: bold;'>Error al registrar: " . mysqli_error($conexion) . "</p>";
        }

        mysqli_close($conexion);
    }
    ?>
  </div>
</body>
</html>
