<?php
/**
 * ===============================================
 * 🔹 FUNCIONES DE GESTIÓN DE PERSONAL
 * Archivo compartido por gestionar-personal.php
 * ===============================================
 */

if (!defined('APP_VALID')) {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

/* =========================================================
🧩 OBTENER LISTADO DE PERSONAL
========================================================= */
function obtener_listado($conexion, $tipo, $estado, $q, $orden, $rol)
{
    // ✅ Validar valores permitidos (protección adicional)
    $tipos_validos = ['trabajadores', 'encargados'];
    $orden_validos = ['alfabetico', 'recientes'];
    $estado_validos = ['activo', 'inactivo', 'todos'];

    if (!in_array($tipo, $tipos_validos)) $tipo = 'trabajadores';
    if (!in_array($orden, $orden_validos)) $orden = 'recientes';
    if (!in_array($estado, $estado_validos)) $estado = 'activo';

    // ===================================================
    
    if ($tipo === 'encargados' && $rol === 'administrador') {
        $base  = "FROM usuarios WHERE rol='encargado'";
        $cols  = "id, nombre, apellidos, DNI AS dni, activo";
    } else {
        $base  = "FROM trabajadores WHERE 1=1";
        $cols  = "id, nombre, apellidos, dni, activo";
    }

    // 🔸 Filtro por estado
    if ($estado === 'activo') {
        $base .= " AND activo=1";
    } elseif ($estado === 'inactivo') {
        $base .= " AND activo=0";
    }

    // 🔸 Filtro por búsqueda
    $params = [];
    $types = '';
    if ($q !== '') {
        $base .= " AND (nombre LIKE ? OR apellidos LIKE ? OR dni LIKE ?)";
        $like = "%{$q}%";
        $params = [$like, $like, $like];
        $types  = 'sss';
    }

    // 🔸 Ordenamiento
    $sql = ($orden === 'alfabetico')
        ? "SELECT $cols $base ORDER BY nombre ASC, apellidos ASC"
        : "SELECT $cols $base ORDER BY id DESC";

    $stmt = $conexion->prepare($sql);
    if (!empty($params)) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result();
}

/* =========================================================
🔻 DAR DE BAJA (TRABAJADOR O ENCARGADO)
========================================================= */
function dar_de_baja($conexion, $tipo, $rol, $id)
{
    if (!is_numeric($id) || $id <= 0) {
        return 'err_sql';
    }

    // 🔸 TRABAJADOR
    if ($tipo === 'trabajadores') {
        $stmt = $conexion->prepare("UPDATE trabajadores SET activo = 0 WHERE id = ?");
        if (!$stmt) return 'err_sql';
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok ? 'baja_ok' : 'err_sql';
    }

    // 🔹 ENCARGADO (solo admin)
    if ($tipo === 'encargados') {
        if ($rol !== 'administrador') return 'sin_permiso';
        $stmt = $conexion->prepare("UPDATE usuarios SET activo = 0 WHERE id = ? AND rol = 'encargado'");
        if (!$stmt) return 'err_sql';
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok ? 'baja_ok' : 'err_sql';
    }

    return 'err_sql';
}

/* =========================================================
🔺 DAR DE ALTA O REACTIVAR (TRABAJADOR / ENCARGADO)
========================================================= */
function dar_de_alta($conexion, $tipo, $rol, $nombre, $apellidos, $dni, $contraseña = null)
{
    // 🔸 Normalización
    $nombre    = strtoupper(trim($nombre));
    $apellidos = strtoupper(trim($apellidos));
    $dni       = strtoupper(trim($dni));

    // Validación básica
    if ($nombre === '' || $apellidos === '' || $dni === '') {
        return 'campos_vacios';
    }

    // =====================================================
    // 🔹 ENCARGADOS (solo administrador)
    // =====================================================
    if ($tipo === 'encargados') {
        if ($rol !== 'administrador') return 'sin_permiso';
        if (empty($contraseña)) return 'campos_vacios';

        $hash = password_hash($contraseña, PASSWORD_BCRYPT);

        // Verificar existencia
        $stmt = $conexion->prepare("SELECT id, activo FROM usuarios WHERE dni=? AND rol='encargado' LIMIT 1");
        if (!$stmt) return 'err_sql';
        $id_found = null;
        $activo_found = null;
        $stmt->bind_param("s", $dni);
        $stmt->execute();
        $stmt->bind_result($id_found, $activo_found);
        $exists = $stmt->fetch();
        $stmt->close();

        if ($exists) {
            if ($activo_found == 1) {
                return 'duplicado';
            } else {
                // Reactivar encargado
                $stmt = $conexion->prepare("UPDATE usuarios SET nombre=?, apellidos=?, contraseña=?, activo=1 WHERE id=? AND rol='encargado'");
                if (!$stmt) return 'err_sql';
                $stmt->bind_param("sssi", $nombre, $apellidos, $hash, $id_found);
                $ok = $stmt->execute();
                $stmt->close();
                return $ok ? 'reactivado' : 'err_sql';
            }
        }

        // Alta nueva
        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, apellidos, DNI, rol, contraseña, activo) VALUES (?, ?, ?, 'encargado', ?, 1)");
        if (!$stmt) return 'err_sql';
        $stmt->bind_param("ssss", $nombre, $apellidos, $dni, $hash);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok ? 'nuevo' : 'err_sql';
    }

    // =====================================================
    // 🔸 TRABAJADORES
    // =====================================================
    $stmt = $conexion->prepare("SELECT id, activo FROM trabajadores WHERE dni=? LIMIT 1");
    if (!$stmt) return 'err_sql';
    $id_found = null;
    $activo_found = null;
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    $stmt->bind_result($id_found, $activo_found);
    $exists = $stmt->fetch();
    $stmt->close();

    if ($exists) {
        if ($activo_found == 1) {
            return 'duplicado';
        } else {
            // Reactivar trabajador
            $stmt = $conexion->prepare("UPDATE trabajadores SET nombre=?, apellidos=?, activo=1 WHERE id=?");
            if (!$stmt) return 'err_sql';
            $stmt->bind_param("ssi", $nombre, $apellidos, $id_found);
            $ok = $stmt->execute();
            $stmt->close();
            return $ok ? 'reactivado' : 'err_sql';
        }
    }

    // Alta nueva
    $stmt = $conexion->prepare("INSERT INTO trabajadores (nombre, apellidos, dni, activo) VALUES (?, ?, ?, 1)");
    if (!$stmt) return 'err_sql';
    $stmt->bind_param("sss", $nombre, $apellidos, $dni);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok ? 'nuevo' : 'err_sql';
}
