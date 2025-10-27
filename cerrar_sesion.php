<?php
session_start();
session_unset();
session_destroy();

// Elimina la cookie si existe
if (isset($_COOKIE["sesion_temporal"])) {
    setcookie("sesion_temporal", "", time() - 3600, "/");
}

header("Location: login.php");
exit();