<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';
include(__DIR__ . '/../config/db.php'); // Conexión segura con dotenv

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mensaje = "";

// ⚡ Asegurarnos de que $_SESSION['intentos'] es siempre un array
if (!isset($_SESSION['intentos']) || !is_array($_SESSION['intentos'])) {
    $_SESSION['intentos'] = [];
}

// ⏱️ Límite de intentos por IP
$ip = $_SERVER['REMOTE_ADDR'];
if (!isset($_SESSION['intentos'][$ip]) || !is_array($_SESSION['intentos'][$ip])) {
    $_SESSION['intentos'][$ip] = ['count' => 0, 'time' => time()];
}

// Bloquear si hay demasiados intentos en poco tiempo
if ($_SESSION['intentos'][$ip]['count'] >= 5 && (time() - $_SESSION['intentos'][$ip]['time']) < 300) {
    $mensaje = "<p style='color:red;'>⚠️ Has realizado demasiados intentos. Espera 5 minutos e inténtalo de nuevo.</p>";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dni = mysqli_real_escape_string($conexion, strip_tags($_POST['dni']));
    $email = trim($_POST['email']);

    // Contar intentos
    $_SESSION['intentos'][$ip]['count']++;
    $_SESSION['intentos'][$ip]['time'] = time();

    // ✅ Validar email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "<p style='color:red;'>⚠️ El correo no tiene un formato válido.</p>";
    } else {
        $domain = explode('@', $email)[1] ?? '';
        if (!checkdnsrr($domain, 'MX')) {
            $mensaje = "<p style='color:red;'>⚠️ El dominio del correo no tiene servidor de correo válido.</p>";
        } else {
            // ✅ Consultas preparadas
            $stmt = $conexion->prepare("SELECT dni FROM usuarios WHERE dni = ?");
            $stmt->bind_param("s", $dni);
            $stmt->execute();
            $resultado = $stmt->get_result();

            // ✅ Mensaje genérico (evita enumeración de usuarios)
            $mensaje = "<p style='color:green;'>✅ Si los datos ingresados son correctos, recibirás un correo con las instrucciones.</p>";

            if ($resultado && $resultado->num_rows > 0) {
                $_SESSION['dni_recuperacion'] = $dni;

                // ✅ Generar token seguro
                $token = bin2hex(random_bytes(32));
                $token_hash = password_hash($token, PASSWORD_DEFAULT);
                $expira = date("Y-m-d H:i:s", strtotime("+10 minutes"));

                // ✅ Insertar token
                $insert = $conexion->prepare("INSERT INTO tokens (dni, token, expira) VALUES (?, ?, ?)");
                $insert->bind_param("sss", $dni, $token_hash, $expira);
                $insert->execute();

                // ✅ Leer variables de entorno
                $app_url = $_ENV['APP_URL'];
                $mail_host = $_ENV['MAIL_HOST'];
                $mail_user = $_ENV['MAIL_USER'];
                $mail_pass = $_ENV['MAIL_PASS'];
                $mail_port = $_ENV['MAIL_PORT'];
                $mail_from = $_ENV['MAIL_FROM'];
                $mail_from_name = $_ENV['MAIL_FROM_NAME'];

                $link = str_replace("http://", "https://", "{$app_url}/auth/cambiar_contrasena.php?token=$token");

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = $mail_host;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $mail_user;
                    $mail->Password   = $mail_pass;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = $mail_port;

                    $mail->CharSet = 'UTF-8';
                    $mail->Encoding = 'base64';
                    $mail->setFrom($mail_from, $mail_from_name);
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = 'Recuperación de contraseña';
                    $mail->Body = "
                    <html><body>
                    <h2 style='color:#FF671D;'>Recuperación de contraseña</h2>
                    <p>Haz clic en el siguiente enlace para cambiar tu contraseña (válido por 10 minutos):</p>
                    <p><a href='$link' style='color:#FF671D;'>$link</a></p>
                    <hr>
                    <p style='font-size:12px;color:#777;'>© Interempleo - Sistema seguro de recuperación de acceso</p>
                    </body></html>";

                    $mail->send();

                    // ✅ Reiniciar contador de intentos al éxito
                    $_SESSION['intentos'][$ip] = ['count' => 0, 'time' => time()];
                } catch (Exception $e) {
                    error_log("Error PHPMailer: {$mail->ErrorInfo}");
                }
            }

            $stmt->close();
            if (isset($insert)) $insert->close();
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
            <a href="login.php" style="color: var(--color-principal); text-decoration: none;">← Volver al login</a>
        </div>

        <hr>
        <?= $mensaje ?>
    </div>

    <?php include(__DIR__ . '/../views/footer.php'); ?>
</body>


</html>
