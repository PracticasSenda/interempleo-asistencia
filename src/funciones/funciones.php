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
          // Comparar correctamente usando password_verify()
        if (password_verify($password, $fila['contraseña'])) {
            return true;
        }
    }
    return false;
}
?>
