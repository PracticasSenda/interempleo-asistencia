<?php
session_start();

// Si no hay sesión iniciada, redirigir al login
if (!isset($_SESSION['nombre'])) {
    header("Location: login.php");
    exit();
}

// Si el usuario NO marcó "mantener sesión" y la cookie ha expirado
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
