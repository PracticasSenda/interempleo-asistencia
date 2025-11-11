<?php
// ===============================================
// 🔒 Cierre de sesión seguro
// ===============================================

// Asegura que las cookies de sesión no puedan ser accedidas por JS
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Si el sitio usa HTTPS, asegura la cookie también
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

session_start();

// 🔸 Eliminar todas las variables de sesión
$_SESSION = [];

// 🔸 Si se está usando una cookie de sesión, eliminarla correctamente
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 🔸 Destruir la sesión
session_destroy();

// 🔸 Eliminar también la cookie personalizada si existe
if (isset($_COOKIE["sesion_temporal"])) {
    setcookie("sesion_temporal", "", time() - 3600, "/", "", isset($_SERVER['HTTPS']), true);
}

// 🔸 Redirigir al login con mensaje opcional
header("Location: login.php?logout=1");
exit();
