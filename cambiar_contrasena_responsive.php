<?php
session_start();
include("conexion_bd.php"); 

$mensaje = "";

if (isset($_GET['token'])) {
    $token = mysqli_real_escape_string($conexion, $_GET['token']);
    $sql = "SELECT * FROM tokens WHERE token='$token' AND expira > NOW()";
    $resultado = mysqli_query($conexion, $sql);

    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $usuario = mysqli_fetch_assoc($resultado);
        $_SESSION['dni_recuperacion'] = $usuario['dni'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pass1 = mysqli_real_escape_string($conexion, strip_tags($_POST['pass1']));
            $pass2 = mysqli_real_escape_string($conexion, strip_tags($_POST['pass2']));

            if ($pass1 === $pass2) {
                $update = "UPDATE usuarios SET contraseña='$pass1' WHERE dni='{$_SESSION['dni_recuperacion']}'";
                mysqli_query($conexion, $update);

                $delete = "DELETE FROM tokens WHERE token='$token'";
                mysqli_query($conexion, $delete);

                $mensaje = "<p style='color:green; text-align:center;'>✅ Contraseña cambiada correctamente. Puedes <a href='login_responsive.php'>iniciar sesión</a>.</p>";
            } else {
                $mensaje = "<p style='color:red; text-align:center;'>❌ Las contraseñas no coinciden.</p>";
            }
        }

    } else {
        $mensaje = "<p style='color:red; text-align:center;'>⚠️ Token inválido o expirado.</p>";
    }

    mysqli_free_result($resultado);
    mysqli_close($conexion);

} else {
    $mensaje = "<p style='color:red; text-align:center;'>⚠️ Token no proporcionado.</p>";
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
.input-container { position: relative; }
input[type="password"] { padding: 12px; border: 1px solid var(--color-borde); border-radius: 4px; font-size: 16px; background-color: var(--color-input-bg); width: 100%; }
.toggle-password { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 18px; user-select: none; }
button { padding: 14px; background-color: var(--color-principal); color: white; border: none; border-radius: 4px; font-size: 17px; cursor: pointer; width: 100%; }
button:hover { background-color: #e65c17; }
a { font-size: 15px; color: var(--color-principal); text-decoration: none; }
@media (max-width: 768px) {
    .barra-superior { padding: 20px 40px; font-size: 22px; text-align: center; }
    h2 { font-size: 22px; }
    p, label { font-size: 15px; }
    button { font-size: 16px; }
}
@media (max-width: 480px) {
    .barra-superior { padding: 15px 20px; font-size: 20px; text-align: center; }
    .contenido { margin: 30px auto; padding: 15px; }
    h2 { font-size: 20px; }
    p, label { font-size: 14px; }
    input[type="password"] { font-size: 15px; padding: 10px; }
    button { font-size: 15px; padding: 12px; }
}
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
            <input type="password" id="pass1" name="pass1" required>
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

<?php include("footer.php"); ?>

<script>
function togglePassword(inputId, icon) {
    const input = document.getElementById(inputId);
    const isPassword = input.type === "password";
    input.type = isPassword ? "text" : "password";
    icon.textContent = isPassword ? "🙈" : "👁️";
}
</script>
</body>
</html>
