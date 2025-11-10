<?php
session_start();
require __DIR__ . '/../vendor/autoload.php';
include(__DIR__ . '/../config/db.php'); // Conexión segura con dotenv

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mensaje = "";

// ⏱️ Límite de intentos por IP (protege contra fuerza bruta)
$ip = $_SERVER['REMOTE_ADDR'];
if (!isset($_SESSION['intentos'][$ip])) {
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
        $domain = explode('@', $email)[1];
        if (!checkdnsrr($domain, 'MX')) {
            $mensaje = "<p style='color:red;'>⚠️ El dominio del correo no tiene servidor de correo válido.</p>";
        } else {
            // ✅ Usar consultas preparadas
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

                // ✅ Insertar token con prepared statement
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

                // ✅ Enlace seguro con HTTPS
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
    $_SESSION['intentos'][$ip]['count'] = 0;
    $_SESSION['intentos'][$ip]['time'] = time();
    
                } catch (Exception $e) {
                    // No revelar errores internos al usuario
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
