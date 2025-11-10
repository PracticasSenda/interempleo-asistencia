<?php
// ===============================================
// 🚨 Manejadores globales de errores y excepciones
// ===============================================
// ===============================================
// 🚨 Configuración de errores segura
// ===============================================
ini_set('display_errors', 0);       // No mostrar errores al usuario
ini_set('log_errors', 1);           // Registrar errores en el log del servidor
error_reporting(E_ALL);             // Registrar todos los errores

// 📜 Configuración básica de errores
ini_set('display_errors', 0);       // No mostrar errores al usuario
ini_set('log_errors', 1);           // Registrar errores
error_reporting(E_ALL);             // Registrar todos los niveles

// 🧩 Manejador de excepciones
set_exception_handler(function ($e) {
    error_log("❗ Excepción no controlada: " . $e->getMessage() . " en " . $e->getFile() . " línea " . $e->getLine());
    http_response_code(500);
    echo 'Error interno del servidor. Por favor, inténtelo más tarde.';
    exit;
});

// 🧩 Manejador de errores PHP (notices, warnings, etc.)
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    error_log("⚠️ Error PHP ($errno) en $errfile línea $errline: $errstr");
    http_response_code(500);
    echo 'Error interno del servidor.';
    exit;
});
