<?php
$host = "192.168.1.225";  // Servidor remoto
$usuario = "root3";       // Usuario de la base de datos
$clave = ""; // Aquí pon la contraseña real
$base_datos = "usuarios"; // Por ejemplo 'interempleo' o 'asistencias'

$conexion = mysqli_connect($host, $usuario, $clave, $base_datos);

$conexion->set_charset("utf8mb4");
?>
