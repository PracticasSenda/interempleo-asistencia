
<?php
session_start();
require __DIR__ . '/vendor/autoload.php';
include("conexion_bd.php"); // Conexión a la BD

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanear datos
    $dni = mysqli_real_escape_string($conexion, strip_tags($_POST['dni']));
    $email = trim($_POST['email']);

    // 1️⃣ Validar formato del correo
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "<p style='color:red;'>⚠️ El correo no tiene un formato válido.</p>";
    } else {
        // 2️⃣ Verificar que el dominio tenga registro MX
        $domain = explode('@', $email)[1];
        if (!checkdnsrr($domain, 'MX')) {
            $mensaje = "<p style='color:red;'>⚠️ El dominio del correo no tiene servidor de correo válido.</p>";
        } else {
            // 3️⃣ Comprobar si el DNI existe en la tabla usuarios
            $sql = "SELECT * FROM usuarios WHERE dni='$dni'";
            $resultado = mysqli_query($conexion, $sql);

            if ($resultado && mysqli_num_rows($resultado) > 0) {
                $_SESSION['dni_recuperacion'] = $dni; // Guardar temporalmente DNI

                // Generar token y expiración (10 minutos)
                $token = bin2hex(random_bytes(32));
                $expira = date("Y-m-d H:i:s", strtotime("+10 minutes"));

                // Insertar token en tabla 'tokens'
                $insert = "INSERT INTO tokens (dni, token, expira) VALUES ('$dni', '$token', '$expira')";
                mysqli_query($conexion, $insert);

                // Crear enlace de cambio de contraseña
                $link = "http://localhost/interempleo-asistencia/cambiar_contrasena_responsive.php?token=$token"; // // CAMBIAR URL si es otra

                // Enviar correo con PHPMailer
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'interempleotucorreo@gmail.com'; // // CAMBIAR correo del sistema
                    $mail->Password   = 'khdimluhledqvwzl';          // // CAMBIAR contraseña de aplicación sin espacios
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->Port       = 587;

                    // ✅ Añadir control de codificación aquí
                    $mail->CharSet = 'UTF-8';       // Para que los acentos y la ñ se vean bien
                    $mail->Encoding = 'base64';     // Para que el cuerpo del mensaje se codifique correctamente


                    $mail->setFrom('interempleotucorreo@gmail.com', 'Sistema Interempleo'); // // CAMBIAR remitente
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = 'Recuperación de contraseña';
                    $mail->Body = "
<html>
<head><meta http-equiv='Content-Type' content='text/html; charset=UTF-8'></head>
<body>
    <h2 style='color:#FF671D;'>Recuperación de contraseña</h2>
    <p>Haz clic en el siguiente enlace para cambiar tu contraseña. Solo es válido 10 minutos:</p>
    <p><a href='$link' style='color:#FF671D;'>$link</a></p>
    <hr>
    <p style='font-size:12px;color:#777;'>© Interempleo - Sistema de recuperación de acceso</p>
</body>

";


                    $mail->send();
                    $mensaje = "<p style='color:green;'>✅ Correo enviado correctamente a $email. Revisa tu bandeja.</p>";
                } catch (Exception $e) {
                    $mensaje = "<p style='color:red;'>❌ Error al enviar correo: {$mail->ErrorInfo}</p>";
                }
            } else {
                $mensaje = "<p style='color:red;'>⚠️ El DNI no existe en la base de datos.</p>";
            }

            mysqli_free_result($resultado);
            mysqli_close($conexion);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Recuperar contraseña</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Copia tu CSS completo aquí */
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

        .barra-superior {
            background-color: var(--color-principal);
            color: white;
            padding: 20px 100px;
            font-size: 24px;
            text-align: left;
        }

        .barra-superior span {
            font-weight: bold;
        }

        .contenido {
            max-width: 500px;
            width: 90%;
            margin: 40px auto;
            padding: 20px;
            text-align: center;
        }

        h2 {
            color: var(--color-principal);
            margin-bottom: 15px;
            font-size: 24px;
        }

        p {
            color: var(--color-texto);
            margin-bottom: 25px;
            font-size: 16px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: stretch;
        }

        label {
            text-align: left;
            font-size: 16px;
            color: var(--color-texto);
        }

        input[type="text"],
        input[type="email"] {
            padding: 12px;
            border: 1px solid var(--color-borde);
            border-radius: 4px;
            font-size: 16px;
            background-color: var(--color-input-bg);
            width: 100%;
        }

        button {
            padding: 14px;
            background-color: var(--color-principal);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 17px;
            cursor: pointer;
            width: 100%;
        }

        button:hover {
            background-color: #e65c17;
        }

        a {
            font-size: 15px;
        }

        @media (max-width: 768px) {
            .barra-superior {
                padding: 20px 40px;
                font-size: 22px;
                text-align: center;
            }

            h2 {
                font-size: 22px;
            }

            p,
            label {
                font-size: 15px;
            }

            button {
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {
            .barra-superior {
                padding: 15px 20px;
                font-size: 20px;
                text-align: center;
            }

            .contenido {
                margin: 30px auto;
                padding: 15px;
            }

            h2 {
                font-size: 20px;
            }

            p,
            label {
                font-size: 14px;
            }

            input[type="text"],
            input[type="email"] {
                font-size: 15px;
                padding: 10px;
            }

            button {
                font-size: 15px;
                padding: 12px;
            }

            a {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <div class="barra-superior">
        <p style="color:white; font-size:23px;"><span>Inter</span>empleo - Recuperar contraseña</p>
    </div>

    <div class="contenido">
        <h2>¿Olvidaste tu contraseña?</h2>
        <p>Introduce tu DNI y correo electrónico para restablecer tu contraseña.</p>

        <form method="post" action="">
            <label for="dni">DNI: </label>
            <input type="text" id="dni" name="dni" required>
            <label for="email">Correo electrónico:</label>
            <input type="text" id="email" name="email" required>
            <button type="submit">Enviar instrucciones</button>
        </form>

        <div style="margin-top: 20px;">
            <a href="login_responsive.php" style="color: var(--color-principal); text-decoration: none;">← Volver al login</a>
        </div>

        <hr>
        <?= $mensaje ?>
    </div>

    <?php include("footer.php"); ?>
</body>


</html>