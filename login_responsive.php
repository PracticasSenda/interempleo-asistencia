<?php
session_start();

if (isset($_SESSION['nombre'])) {
    header("Location: asistencia_responsive.php");
    exit();
}

include("conexion_bd.php");
include("funciones.php");

$error = "";

if (isset($_POST['enviar'])) {
    $dni = $_POST['dni'];
    $password = $_POST['password'];
    $sesion = isset($_POST["sesion"]) ? "si" : "no";

    if (validar_usuario($conexion, $dni, $password)) {
        $_SESSION['nombre'] = $dni; // Puedes guardar el DNI o cualquier otro dato
        if ($sesion == "si") {
            // Opcional: configura algo para mantener la sesión más tiempo
        }
        header("Location: asistencia_responsive.php");
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión</title>

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

    /* BARRA SUPERIOR */
    .barra-superior {
      background-color: var(--color-principal);
      color: white;
      padding: 1.5rem 8rem;
      font-size: 1.5rem;
      font-weight: normal;
      text-align: left;
    }

    .barra-superior span {
      font-weight: bold;
    }

    /* FORMULARIO */
    .login-box {
      background-color: var(--color-input-bg);
      padding: 2rem;
      border: 1px solid var(--color-borde);
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      width: 90%;
      max-width: 350px;
      margin: 5rem auto;
      text-align: left;
    }

    .login-box h2 {
      color: var(--color-principal);
      text-align: center;
      margin-bottom: 1.5rem;
      font-size: 1.8rem;
    }

    label {
      display: block;
      margin-top: 1rem;
      margin-bottom: 0.5rem;
      color: var(--color-texto);
      font-weight: bold;
      font-size: 1rem;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid var(--color-borde);
      border-radius: 4px;
      background-color: var(--color-fondo);
      font-size: 1rem;
    }

    button {
      width: 100%;
      margin-top: 1rem;
      padding: 0.75rem;
      background-color: var(--color-principal);
      color: #FFFFFF;
      border: none;
      border-radius: 4px;
      font-size: 1.1rem;
      cursor: pointer;
    }

    button:hover {
      background-color: #e65c17;
    }

    a {
      color: var(--color-principal);
      text-decoration: none;
      font-size: 0.95rem;
    }

    p {
      font-size: 0.95rem;
      color: var(--color-texto);
    }

    /* RESPONSIVE: TABLETS */
    @media (max-width: 768px) {
      .barra-superior {
        padding: 1.5rem 2rem;
        font-size: 1.3rem;
        text-align: center;
      }

      .login-box {
        margin: 4rem auto;
        padding: 1.5rem;
      }

      .login-box h2 {
        font-size: 1.6rem;
      }
    }

    /* RESPONSIVE: MÓVILES */
    @media (max-width: 480px) {
      .barra-superior {
        padding: 1rem;
        font-size: 1.2rem;
        text-align: center;
      }

      .login-box {
        margin: 3rem auto;
        padding: 1rem;
      }

      .login-box h2 {
        font-size: 1.4rem;
      }

      label, input, button, p, a {
        font-size: 1rem;
      }

      button {
        padding: 0.65rem;
        font-size: 1rem;
      }
    }
  </style>
</head>
<body>
  <?php
  if (isset($_SESSION['nombre'])) {
    header("Location: asistencia_responsive.php");
  }
  ?>

  <!-- BANDA NARANJA CON "Interempleo" -->
  <div class="barra-superior">
    <p style="color:white;"><span>Inter</span>empleo</p>
  </div>

  <!-- FORMULARIO CENTRADO -->
  <div class="login-box">
    <h2>Iniciar Sesión</h2>

    <form method="post" action="login_responsive.php" onsubmit="return validar()">
      <label for="dni">DNI:</label>
      <input type="text" id="dni" name="dni" required>

      <label for="password">Contraseña</label>
      <input type="password" id="password" name="password" required>

      <a href="recuperar_contraseña_responsive.php">¿Has olvidado tu contraseña?</a>

      <p>
        <input type="checkbox" id="sesion" value="si" name="sesion">
        <span>Mantener la sesión abierta</span>
      </p>

      <button type="submit" name="enviar">Entrar</button>
    </form>
  </div>
    <!-- Volveremos aqui luego -->

</body>
</html>
    
