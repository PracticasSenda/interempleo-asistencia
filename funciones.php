<?php
// funciones.php

function validar_usuario($conexion, $dni, $password) {
    // Consulta preparada para mayor seguridad
    $stmt = $conexion->prepare("SELECT contraseña FROM usuarios WHERE dni = ? LIMIT 1");
    if (!$stmt) {
        die("Error en la consulta: " . $conexion->error);
    }
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($fila = $resultado->fetch_assoc()) {
        // Aquí suponemos que la contraseña está almacenada en texto plano.
        // Si tienes hash, cambia a password_verify()
        if ($password === $fila['contraseña']) {
            return true;
        }
    }
    return false;
}
?>
