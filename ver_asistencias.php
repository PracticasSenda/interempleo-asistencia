<?php
include("conexion_bd.php");

if (!isset($_GET['id_listado']) || empty($_GET['id_listado'])) {
    die("ID de listado no especificado");
}

$id_listado = intval($_GET['id_listado']);

// Obtener datos básicos del listado para mostrar título
$sql = "SELECT empresa, fecha, producto FROM listados_asistencias WHERE id = $id_listado LIMIT 1";
$result = mysqli_query($conexion, $sql);
if (!$result || mysqli_num_rows($result) == 0) {
    die("Listado no encontrado");
}
$listado = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>Asistencias del Listado #<?= $id_listado ?></title>
<style>
  body { font-family: Arial, sans-serif; padding: 20px; }
  h2 { margin-bottom: 0; }
  .info-listado { margin-bottom: 20px; font-style: italic; color: #555; }
  table { border-collapse: collapse; width: 100%; margin-top: 10px; }
  th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
  th { background-color: #4F81BD; color: white; }
  tbody tr:nth-child(even) { background-color: #f9f9f9; }
  #mensaje { margin-top: 20px; color: #666; }
</style>
</head>
<body>

<h2>Asistencias del Listado #<?= $id_listado ?></h2>
<div class="info-listado">
  Empresa: <?= htmlspecialchars($listado['empresa']) ?> | Fecha: <?= htmlspecialchars($listado['fecha']) ?> | Producto: <?= htmlspecialchars($listado['producto']) ?>
</div>

<table id="tabla-asistencias">
  <thead>
    <tr>
      <th>ID</th>
      <th>Empresa</th>
      <th>Fecha</th>
      <th>Producto</th>
      <th>Asistencia</th>
      <th>Nombre Trabajador</th>
      <th>DNI</th>
      <th>Bandejas</th>
      <th>Horas</th>
      <th>Observaciones</th>
    </tr>
  </thead>
  <tbody>
    <!-- Aquí se cargan las filas vía JS -->
  </tbody>
</table>

<div id="mensaje"></div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const tbody = document.querySelector("#tabla-asistencias tbody");
    const mensaje = document.getElementById("mensaje");

    fetch("buscar_asistencias_por_listado.php?id_listado=<?= $id_listado ?>")
        .then(response => response.json())
        .then(data => {
            if (!data || data.length === 0) {
                mensaje.textContent = "No se encontraron asistencias para este listado.";
                return;
            }

            data.forEach(asistencia => {
                const tr = document.createElement("tr");

                tr.innerHTML = `
                    <td>${asistencia.id}</td>
                    <td>${escapeHtml(asistencia.empresa)}</td>
                    <td>${escapeHtml(asistencia.fecha)}</td>
                    <td>${escapeHtml(asistencia.producto)}</td>
                    <td>${escapeHtml(asistencia.asistencia)}</td>
                    <td>${escapeHtml(asistencia.nombre_trabajador || asistencia.nombre || '')}</td>
                    <td>${escapeHtml(asistencia.dni)}</td>
                    <td>${escapeHtml(asistencia.bandeja)}</td>
                    <td>${escapeHtml(asistencia.horas)}</td>
                    <td>${escapeHtml(asistencia.observaciones)}</td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(err => {
            mensaje.textContent = "Error al cargar las asistencias.";
            console.error(err);
        });

    // Función para escapar HTML y evitar inyección
    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/&/g, "&amp;")
                   .replace(/</g, "&lt;")
                   .replace(/>/g, "&gt;")
                   .replace(/"/g, "&quot;")
                   .replace(/'/g, "&#039;");
    }
});
</script>

</body>
</html>
