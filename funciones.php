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
        $hash_guardado = $fila['contraseña'];

        // ✅ Verificamos el hash con password_verify()
        if (password_verify($password, $hash_guardado)) {

            // Opcional: re-hashear si el algoritmo cambia o los parámetros se actualizan
            if (password_needs_rehash($hash_guardado, PASSWORD_DEFAULT)) {
                $nuevo_hash = password_hash($password, PASSWORD_DEFAULT);
                $update = $conexion->prepare("UPDATE usuarios SET contraseña=? WHERE dni=?");
                $update->bind_param("ss", $nuevo_hash, $dni);
                $update->execute();
                $update->close();
            }

            return true; // Contraseña correcta
        }
    }

    return false; // Usuario no encontrado o contraseña incorrecta
}
?>

