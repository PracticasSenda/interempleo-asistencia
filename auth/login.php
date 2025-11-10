<?php
// --- Configuración de seguridad para las cookies de sesión ---
ini_set('session.cookie_httponly', 1);                 // Evita acceso JS
ini_set('session.use_strict_mode', 1);                 // Evita reutilización de ID
ini_set('session.cookie_samesite', 'Strict');          // Evita envío cross-site
ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); // Solo por HTTPS si aplica

session_start();

// Si el usuario ya tiene sesión iniciada, redirigimos
if (isset($_SESSION['nombre'])) {
  header("Location: ../views/asistencia.php");
  exit();
}

include(__DIR__ . '/../config/db.php');
include(__DIR__ . '/../funciones/funciones.php');

$error = "";

// --- Control de intentos fallidos ---
if (!isset($_SESSION['intentos'])) {
  $_SESSION['intentos'] = 0;
}

// Si está bloqueado temporalmente
if (isset($_SESSION['bloqueado_hasta']) && time() < $_SESSION['bloqueado_hasta']) {
  $faltan = ceil(($_SESSION['bloqueado_hasta'] - time()) / 60);
  die("⛔ Has superado el número máximo de intentos. Vuelve a intentarlo en $faltan minuto(s).");
}

if (isset($_POST['enviar'])) {
  $dni = mysqli_real_escape_string($conexion, strip_tags($_POST['dni']));
  $password = mysqli_real_escape_string($conexion, strip_tags($_POST['password']));
  $sesion = isset($_POST["sesion"]) ? "si" : "no";

  // --- Verificar usuario ---
  if (validar_usuario($conexion, $dni, $password)) {

    // ✅ Reiniciar contador de intentos al iniciar sesión correctamente
    $_SESSION['intentos'] = 0;
    unset($_SESSION['bloqueado_hasta']);

    // Obtener datos del usuario
    $consulta = "SELECT id, nombre, apellidos, rol FROM usuarios WHERE dni = ?";
    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($fila = $resultado->fetch_assoc()) {
      $_SESSION['id'] = $fila['id'];
      $_SESSION['dni'] = $dni;
      $_SESSION['nombre'] = $fila['nombre'];
      $_SESSION['apellidos'] = $fila['apellidos'];
      $_SESSION['rol'] = $fila['rol'];
    } else {
      $_SESSION['rol'] = '';
    }

    $stmt->close();

    // Guardar preferencia de sesión
    $_SESSION['sesion'] = $sesion;

    // --- Cookie temporal protegida ---
    if ($sesion === "no") {
      setcookie(
        "sesion_temporal",
        "1",
        [
          'expires' => time() + 300, // 5 minutos
          'path' => '/',
          'secure' => isset($_SERVER['HTTPS']),
          'httponly' => true,
          'samesite' => 'Strict'
        ]
      );
    } else {
      if (isset($_COOKIE["sesion_temporal"])) {
        setcookie("sesion_temporal", "", time() - 3600, "/"); // eliminar cookie
      }
    }

    header("Location: ../views/asistencia.php");
    exit();
  } else {
    // ❌ Credenciales incorrectas
    $_SESSION['intentos']++;

    if ($_SESSION['intentos'] >= 5) {
      $_SESSION['bloqueado_hasta'] = time() + 180; // Bloqueo 3 minutos
      $error = "Has superado el número máximo de intentos. Espera 3 minutos.";
    } else {
      $restantes = 5 - $_SESSION['intentos'];
      $error = "Credenciales incorrectas. Te quedan $restantes intento(s).";
    }
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

      label,
      input,
      button,
      p,
      a {
        font-size: 1rem;
      }

      button {
        padding: 0.65rem;
        font-size: 1rem;
      }
    }

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
  </style>
</head>

<body>
 

  <!-- BANDA NARANJA CON "Interempleo" -->
  <div class="barra-superior">
    <p style="color:white; font-size:23px;"><span>Inter</span>empleo</p>
  </div>

  <!-- FORMULARIO CENTRADO -->
  <div class="login-box">
    <h2>Iniciar Sesión</h2>

    <form method="post" action="login.php">
      <label for="dni">DNI:</label>
      <input type="text" id="dni" name="dni" required>

      <label for="password">Contraseña</label>
      <div class="password-container">
        <input type="password" id="password" name="password" required>
        <span class="toggle-password" onclick="togglePassword()">👁️</span>
      </div>


      <a href="recuperar_contrasena.php">¿Has olvidado tu contraseña?</a>

      <p>
        <input type="checkbox" id="sesion" value="si" name="sesion">
        <span>Mantener la sesión abierta</span>
      </p>

      <button type="submit" name="enviar">Entrar</button>
    </form>
  </div>

  <script>
    function togglePassword() {
      const input = document.getElementById("password");
      const toggle = document.querySelector(".toggle-password");
      const isPassword = input.type === "password";
      input.type = isPassword ? "text" : "password";
      toggle.textContent = isPassword ? "🙈" : "👁️"; // cambia el icono
    }
  </script>

  <?php include(__DIR__ . '/../views/footer.php'); ?>

</body>

</html>