<?php
$host = "192.168.1.225";  // Servidor remoto
$usuario = "root2";       // Usuario de la base de datos
$clave = ""; // Aquí pon la contraseña real
$base_datos = "usuarios"; // Por ejemplo 'interempleo' o 'asistencias'

$conexion = mysqli_connect($host, $usuario, $clave, $base_datos);

if (!$conexion) {
    die("❌ Error de conexión: " . mysqli_connect_error());
}
// echo "✅ Conexión correcta a la base de datos";
?>
