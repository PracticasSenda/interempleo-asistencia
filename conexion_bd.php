<?php 

// Datos de conexión
$host = "localhost";         // o 127.0.0.1
$usuario = "root";           // Usuario de MySQL
$clave = "";                 // Contraseña (vacía por defecto en XAMPP)
$basedatos = "usuarios";     // Nombre de la base de datos

// Crear la conexión (procedimental)
$conexion = mysqli_connect($host, $usuario, $clave, $basedatos);

// Verificar si la conexión fue exitosa
if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

// Si todo va bien
echo "Conexión exitosa a la base de datos.";

?>