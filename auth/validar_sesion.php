<?php
// ===============================================
// 🛡️ Configuración segura de sesión
// ===============================================

// Cookies de sesión seguras y solo accesibles por HTTP
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Solo enviar cookie por HTTPS si el sitio usa SSL
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

session_start();

// ===============================================
// ⏳ Control de inactividad
// ===============================================

// Si el usuario lleva más de 20 minutos sin actividad → cerrar sesión
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1200)) {
    session_unset();
    session_destroy();
    header("Location: login.php?expired=1");
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// ===============================================
// 🔄 Regeneración periódica del ID de sesión
// ===============================================
if (!isset($_SESSION['CREATED'])) {
    $_SESSION['CREATED'] = time();
} elseif (time() - $_SESSION['CREATED'] > 600) { // cada 10 minutos
    session_regenerate_id(true);
    $_SESSION['CREATED'] = time();
}

// ===============================================
// 🔐 Validación de sesión activa
// ===============================================
if (!isset($_SESSION['nombre'])) {
    header("Location: login.php");
    exit();
}

// ===============================================
// 🍪 Control de “mantener sesión” temporal
// ===============================================
if (
    isset($_SESSION["sesion"]) &&
    $_SESSION["sesion"] === "no" &&
    !isset($_COOKIE["sesion_temporal"])
) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
