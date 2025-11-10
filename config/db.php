<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Cargar el archivo credenciales.env desde la carpeta config
$dotenv = Dotenv::createImmutable(__DIR__, ['credenciales.env']);

$dotenv->load();

// Leer las variables del entorno
$host = $_ENV['DB_HOST'];
$usuario = $_ENV['DB_USER'];
$clave = $_ENV['DB_PASS'];
$base_datos = $_ENV['DB_NAME'];

// Crear la conexión
$conexion = mysqli_connect($host, $usuario, $clave, $base_datos);

$conexion->set_charset("utf8mb4");
?>
