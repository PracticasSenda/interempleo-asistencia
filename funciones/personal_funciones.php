<?php
/**
 * Archivo de funciones para gestionar trabajadores y encargados.
 * Mantiene la lógica separada de la vista principal (gestionar-personal.php).
 */

if (!defined('APP_VALID')) {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

/**
 * Obtener listado de trabajadores o encargados con filtros.
 */
function obtener_listado($conexion, $tipo, $estado, $q, $orden, $rol)
{
    // Selección de tabla y columnas
    if ($tipo === 'encargados' && $rol === 'administrador') {
        $base  = "FROM usuarios WHERE rol='encargado'";
        $cols  = "id, nombre, apellidos, DNI AS dni, activo";
    } else {
        $base  = "FROM trabajadores WHERE 1=1";
        $cols  = "id, nombre, apellidos, dni, activo";
    }

    // Filtrado por estado
    if ($estado === 'activo') {
        $base .= " AND activo=1";
    } elseif ($estado === 'inactivo') {
        $base .= " AND activo=0";
    }

    // Búsqueda por texto
    $params = [];
    $types = '';
    if ($q !== '') {
        $base .= " AND (nombre LIKE ? OR apellidos LIKE ? OR dni LIKE ?)";
        $like = "%{$q}%";
        $params = [$like, $like, $like];
        $types  = 'sss';
    }

    // Ordenamiento
    $sql = ($orden === 'alfabetico')
        ? "SELECT $cols $base ORDER BY nombre ASC, apellidos ASC"
        : "SELECT $cols $base ORDER BY id DESC";

    // Ejecución
    $stmt = $conexion->prepare($sql);
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Dar de baja (trabajador o encargado)
 */
function dar_de_baja($conexion, $tipo, $rol, $id)
{
    if ($id <= 0) return false;

    if ($tipo === 'trabajadores') {
        $stmt = $conexion->prepare("UPDATE trabajadores SET activo=0 WHERE id=?");
    } elseif ($tipo === 'encargados' && $rol === 'administrador') {
        $stmt = $conexion->prepare("UPDATE usuarios SET activo=0 WHERE id=? AND rol='encargado'");
    } else {
        return false;
    }

    $stmt->bind_param("i", $id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

/**
 * Dar de alta o reactivar trabajador / encargado.
 */
function dar_de_alta($conexion, $tipo, $rol, $nombre, $apellidos, $dni, $contraseña = null)
{
    $nombre = strtoupper(trim($nombre));
    $apellidos = strtoupper(trim($apellidos));
    $dni = strtoupper(trim($dni));

    if ($nombre === '' || $apellidos === '' || $dni === '') return false;

    // === TRABAJADORES ===
    if ($tipo === 'trabajadores') {
        $stmt = $conexion->prepare("SELECT id, activo FROM trabajadores WHERE dni=? LIMIT 1");
        $stmt->bind_param("s", $dni);
        $stmt->execute();

        // Evita advertencias en Intelephense
        $id_found = null;
        $act_found = null;

        $stmt->bind_result($id_found, $act_found);
        $exists = $stmt->fetch();
        $stmt->close();

        if ($exists) {
            if ($act_found == 1) return 'duplicado';
            $stmt = $conexion->prepare("UPDATE trabajadores SET nombre=?, apellidos=?, activo=1 WHERE id=?");
            $stmt->bind_param("ssi", $nombre, $apellidos, $id_found);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok ? 'reactivado' : false;
        } else {
            $stmt = $conexion->prepare("INSERT INTO trabajadores (nombre, apellidos, dni, activo) VALUES (?, ?, ?, 1)");
            $stmt->bind_param("sss", $nombre, $apellidos, $dni);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok ? 'nuevo' : false;
        }
    }

    // === ENCARGADOS ===
    if ($tipo === 'encargados' && $rol === 'administrador') {
        if (empty($contraseña)) return false;
        $hash = password_hash($contraseña, PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, apellidos, DNI, rol, contraseña, activo)
                                    VALUES (?, ?, ?, 'encargado', ?, 1)");
        $stmt->bind_param("ssss", $nombre, $apellidos, $dni, $hash);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok ? 'nuevo' : false;
    }

    return false;
}
