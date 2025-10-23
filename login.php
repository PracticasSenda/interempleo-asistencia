<?php
session_start();

if (isset($_SESSION['nombre'])) {
    header("Location: asistencia.php");
    exit();
}

include("conexion_bd.php");
include("funciones.php");

$error = "";

if (isset($_POST['enviar'])) {
  $dni = mysqli_real_escape_string($conexion, strip_tags($_POST['dni']));
  $password = mysqli_real_escape_string($conexion, strip_tags($_POST['password']));
  $sesion = isset($_POST["sesion"]) ? "si" : "no";
    if (validar_usuario($conexion, $dni, $password)) {
      
    // Nueva versión: obtener también nombre y apellidos del usuario
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



   /*
    $_SESSION['nombre'] = $dni;

    // Obtener el rol del usuario
    $consulta_rol = "SELECT rol FROM usuarios WHERE dni = '$dni'";
    $resultado_rol = mysqli_query($conexion, $consulta_rol);

 
    
    if ($resultado_rol && mysqli_num_rows($resultado_rol) === 1) {
        $fila_rol = mysqli_fetch_row($resultado_rol); // fetch_row devuelve array numérico
        $_SESSION['rol'] = $fila_rol[0];
    } else {
        $_SESSION['rol'] = '';
    }

       */

    // Guardamos si el usuario quiere mantener la sesión o no
    $_SESSION['sesion'] = $sesion; // "si" o "no"

    // Si no quiere mantener sesión, se crea una cookie temporal de 1 minuto
    if ($sesion === "no") {
        setcookie("sesion_temporal", "1", time() + 300, "/");
    } else {
        // Si se marcó que sí, eliminamos cualquier cookie anterior
        if (isset($_COOKIE["sesion_temporal"])) {
            setcookie("sesion_temporal", "", time() - 3600, "/"); // borrar cookie
        }
    }

    header("Location: asistencia.php");
    exit();
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
  </style>
</head>
<body>
  <?php
  if (isset($_SESSION['nombre'])) {
    header("Location: asistencia.php");
  }
  ?>

  <!-- BANDA NARANJA CON "Interempleo" -->
  <div class="barra-superior">
    <p style="color:white; font-size:23px;"><span>Inter</span>empleo</p>
  </div>

  <!-- FORMULARIO CENTRADO -->
  <div class="login-box">
    <h2>Iniciar Sesión</h2>

    <form method="post" action="login.php" onsubmit="return validar()">
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

<?php include("footer.php");?>

</body>
</html>
    
