<?php
session_start();
include(__DIR__ . '/../config/db.php');

$mensaje = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Consulta preparada para buscar tokens válidos
    $stmt = $conexion->prepare("SELECT dni, token, expira FROM tokens WHERE expira > NOW()");
    $stmt->execute();
    $resultado = $stmt->get_result();

    $dni = null;

    // Validar token mediante password_verify
    while ($fila = $resultado->fetch_assoc()) {
        if (password_verify($token, $fila['token'])) {
            $dni = $fila['dni'];
            break;
        }
    }

    $stmt->close();

    if ($dni) {
        $_SESSION['dni_recuperacion'] = $dni;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pass1 = trim($_POST['pass1']);
            $pass2 = trim($_POST['pass2']);

            if (!esContrasenaFuerte($pass1)) {
                $mensaje = "<p style='color:red; text-align:center;'>⚠️ La contraseña es demasiado débil. Usa mayúsculas, minúsculas, números y símbolos.</p>";
            } elseif ($pass1 === $pass2) {
                $hash = password_hash($pass1, PASSWORD_DEFAULT);

                $update = $conexion->prepare("UPDATE usuarios SET contraseña = ? WHERE dni = ?");
                $update->bind_param("ss", $hash, $_SESSION['dni_recuperacion']);
                $update->execute();
                $update->close();

                $delete = $conexion->prepare("DELETE FROM tokens WHERE dni = ?");
                $delete->bind_param("s", $dni);
                $delete->execute();
                $delete->close();

                $mensaje = "<p style='color:green; text-align:center;'>✅ Contraseña cambiada correctamente. Puedes <a href='login.php'>iniciar sesión</a>.</p>";
            } else {
                $mensaje = "<p style='color:red; text-align:center;'>❌ Las contraseñas no coinciden.</p>";
            }
        }
    } else {
        $mensaje = "<p style='color:red; text-align:center;'>⚠️ Token inválido o expirado.</p>";
    }

    $conexion->close();
} else {
    $mensaje = "<p style='color:red; text-align:center;'>⚠️ Token no proporcionado.</p>";
}

// 🔒 Función para verificar la fuerza de la contraseña
function esContrasenaFuerte($password) {
    $longitud = strlen($password) >= 8;
    $mayus = preg_match('/[A-Z]/', $password);
    $minus = preg_match('/[a-z]/', $password);
    $numero = preg_match('/[0-9]/', $password);
    $simbolo = preg_match('/[\W]/', $password);
    return $longitud && $mayus && $minus && $numero && $simbolo;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cambiar contraseña</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
:root {
    --color-principal: #FF671D;
    --color-fondo: #FFFFFF;
    --color-texto: #333333;
    --color-borde: #CCCCCC;
    --color-input-bg: #F9F9F9;
}
*, *::before, *::after { box-sizing: border-box; }
body { font-family: Arial, sans-serif; background-color: var(--color-fondo); margin: 0; padding: 0; }
.barra-superior { background-color: var(--color-principal); color: white; padding: 20px 100px; font-size: 24px; text-align: left; }
.barra-superior span { font-weight: bold; }
.contenido { max-width: 500px; width: 90%; margin: 40px auto; padding: 20px; text-align: center; }
h2 { color: var(--color-principal); margin-bottom: 15px; font-size: 24px; }
p { color: var(--color-texto); margin-bottom: 25px; font-size: 16px; }
form { display: flex; flex-direction: column; gap: 15px; align-items: stretch; position: relative; }
label { text-align: left; font-size: 16px; color: var(--color-texto); }
.input-container { position: relative; display: flex; flex-direction: column; }
.input-container input[type="password"],
.input-container input[type="text"] {
    width: 100%;
    padding: 12px 40px 12px 12px;
    border: 1px solid var(--color-borde);
    border-radius: 4px;
    font-size: 16px;
    background-color: var(--color-input-bg);
    color: var(--color-texto);
    font-family: inherit;
    outline: none;
}
.toggle-password {
    position: absolute;
    right: 10px;
    top: 35px;
    cursor: pointer;
    font-size: 20px;
    user-select: none;
    color: #555;
}
#strength-bar {
    width: 100%;
    height: 8px;
    border-radius: 4px;
    margin-top: 5px;
    background-color: #ddd;
    transition: background-color 0.3s;
}
#strength-text {
    font-size: 14px;
    margin-top: 5px;
    text-align: left;
    font-weight: bold;
}
button { padding: 14px; background-color: var(--color-principal); color: white; border: none; border-radius: 4px; font-size: 17px; cursor: pointer; width: 100%; }
button:hover { background-color: #e65c17; }
a { font-size: 15px; color: var(--color-principal); text-decoration: none; }
</style>
</head>
<body>
<div class="barra-superior">
    <p style="color:white; font-size:23px;"><span>Inter</span>empleo - Cambiar contraseña</p>
</div>

<div class="contenido">
    <h2>Cambiar contraseña</h2>
    <p>Introduce tu nueva contraseña y repítela para confirmarla.</p>

    <form method="post" action="">
        <div class="input-container">
            <label for="pass1">Nueva contraseña:</label>
            <input type="password" id="pass1" name="pass1" required oninput="verificarFuerza()">
            <div id="strength-bar"></div>
            <div id="strength-text">Fortaleza: —</div>
            <span class="toggle-password" onclick="togglePassword('pass1', this)">👁️</span>
        </div>

        <div class="input-container">
            <label for="pass2">Repetir contraseña:</label>
            <input type="password" id="pass2" name="pass2" required>
            <span class="toggle-password" onclick="togglePassword('pass2', this)">👁️</span>
        </div>

        <button type="submit">Cambiar contraseña</button>
    </form>

    <hr>
    <?= $mensaje ?>
</div>

<?php include(__DIR__ . '/../views/footer.php'); ?>

<script>
// 👁️ Mostrar/Ocultar contraseña
function togglePassword(inputId, icon) {
    const input = document.getElementById(inputId);
    const isPassword = input.type === "password";
    input.type = isPassword ? "text" : "password";
    icon.textContent = isPassword ? "🙈" : "👁️";
}

// 🔐 Evaluar fuerza visual de la contraseña
function verificarFuerza() {
    const pass = document.getElementById("pass1").value;
    const bar = document.getElementById("strength-bar");
    const text = document.getElementById("strength-text");
    let fuerza = 0;

    if (pass.length >= 8) fuerza++;
    if (/[A-Z]/.test(pass)) fuerza++;
    if (/[a-z]/.test(pass)) fuerza++;
    if (/[0-9]/.test(pass)) fuerza++;
    if (/[\W]/.test(pass)) fuerza++;

    const colores = ["#ff4d4d", "#ff944d", "#ffcc00", "#99cc00", "#4CAF50"];
    const niveles = ["Muy débil", "Débil", "Media", "Fuerte", "Muy fuerte"];

    bar.style.backgroundColor = colores[fuerza - 1] || "#ddd";
    text.textContent = fuerza ? `Fortaleza: ${niveles[fuerza - 1]}` : "Fortaleza: —";
    text.style.color = colores[fuerza - 1] || "#333";
}
</script>
</body>
</html>
