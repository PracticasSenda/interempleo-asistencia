<?php
// prueba_phpmailer.php
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cambia aquí tu correo y contraseña de aplicación
$correoSistema = 'interempleotucorreo@gmail.com';
$contrasenaApp = 'twmdbxsqobdkhsul'; // SIN espacios

// Destinatario de prueba
$correoDestino = 'jjosebelmonte280100@gmail.com'; // Cambia a tu correo real

$mail = new PHPMailer(true);

try {
    // Configuración SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $correoSistema;
    $mail->Password   = $contrasenaApp;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Remitente y destinatario
    $mail->setFrom($correoSistema, 'Sistema Interempleo');
    $mail->addAddress($correoDestino);

    // Contenido del correo
    $mail->isHTML(true);
    $mail->Subject = 'Prueba PHPMailer';
    $mail->Body    = '<h2>¡Funciona!</h2><p>Este es un correo de prueba enviado desde PHPMailer.</p>';

    $mail->send();
    echo "✅ Correo enviado correctamente a $correoDestino";

} catch (Exception $e) {
    echo "❌ Error al enviar correo: {$mail->ErrorInfo}";
}
