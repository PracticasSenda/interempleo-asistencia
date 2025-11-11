<?php
include(__DIR__ . '/../auth/validar_sesion.php');
include(__DIR__ . '/../config/db.php');

$fecha = $_GET['fecha'] ?? null;
$id_listado = $_GET['id_listado'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gestionar Asistencia - Interempleo</title>

  <!-- Estilos -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <link rel="stylesheet" href="../../public/css/gestionar-asistencia.css">
</head>

<body>
  <?php include(__DIR__ . '/header.php'); ?>

  <main>
    <div class="buscador-fecha">
      <label for="fecha_buscar"><strong>Selecciona una fecha:</strong></label>
      <input type="text" id="fecha_buscar" placeholder="Selecciona fecha" readonly>
      <button class="btn-aplicar" id="btnBuscar">Buscar</button>
    </div>

    <?php
    if ($fecha && !$id_listado) {
      echo "<h2>Selecciona un listado</h2>";

      $sql = "
        SELECT l.id, l.empresa, l.producto, l.fecha, u.nombre AS encargado
        FROM listados_asistencias l
        JOIN usuarios u ON l.id_encargado = u.id
        WHERE l.fecha = '$fecha'
        ORDER BY l.id DESC
      ";
      $res = mysqli_query($conexion, $sql);

      if ($res && mysqli_num_rows($res) > 0) {
        echo "<div class='tabla-responsive'>
                <table>
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Empresa</th>
                      <th>Producto</th>
                      <th>Fecha</th>
                      <th>Encargado</th>
                      <th>Opciones</th>
                    </tr>
                  </thead>
                  <tbody>";
        while ($fila = mysqli_fetch_assoc($res)) {
          echo "<tr>
                  <td>{$fila['id']}</td>
                  <td>{$fila['empresa']}</td>
                  <td>{$fila['producto']}</td>
                  <td>{$fila['fecha']}</td>
                  <td>{$fila['encargado']}</td>
                  <td><a href='?id_listado={$fila['id']}&fecha={$fila['fecha']}' class='btn-ver'>👁️ Ver</a></td>
                </tr>";
        }
        echo "</tbody></table></div>";
      } else {
        echo "<p class='mensaje-vacio'>No hay listados para esta fecha.</p>";
      }
    }

    // 🔹 Mostrar detalle del listado
    elseif ($id_listado) {
      echo "<a href='?fecha=$fecha' class='btn-volver'>← Volver a listados</a>";

      $sql_info = "
        SELECT l.empresa, l.producto, l.fecha, u.nombre AS nombre_encargado, u.apellidos AS apellidos_encargado
        FROM listados_asistencias l
        JOIN usuarios u ON l.id_encargado = u.id
        WHERE l.id = '$id_listado'
        LIMIT 1
      ";
      $res_info = mysqli_query($conexion, $sql_info);

      if ($res_info && mysqli_num_rows($res_info) > 0) {
        $info = mysqli_fetch_assoc($res_info);
        echo "
        <div class='info-parte'>
          <p><strong>Empresa:</strong> {$info['empresa']}</p>
          <p><strong>Producto:</strong> {$info['producto']}</p>
          <p><strong>Fecha:</strong> {$info['fecha']}</p>
          <p><strong>Encargado:</strong> {$info['nombre_encargado']} {$info['apellidos_encargado']}</p>
        </div>

        <!-- 🔸 Botones de exportación -->
        <div class='export-buttons'>
          <form action='../export/funcion_exportar_excel.php' method='POST' target='_blank'>
            <input type='hidden' name='id_listado' value='{$id_listado}'>
            <button type='submit' class='btn-export excel'>📊 Exportar Excel</button>
          </form>

          <form action='../export/funcion_exportar_pdf.php' method='POST' target='_blank'>
            <input type='hidden' name='id_listado' value='{$id_listado}'>
            <button type='submit' class='btn-export pdf'>📄 Exportar PDF</button>
          </form>
        </div>";
      }

      echo "<h2>Asistencias del listado</h2>";

$sql_asistencias = "
    SELECT 
        t.id AS id_trabajador,
        t.nombre,
        t.apellidos,
        t.dni,
        a.asistencia,
        a.Bandeja,
        a.Horas,
        a.Observaciones
    FROM asistencias a
    INNER JOIN trabajadores t ON a.id_trabajador = t.id
    WHERE a.id_listado = '$id_listado'
    GROUP BY a.id_trabajador
    ORDER BY t.apellidos ASC, t.nombre ASC
";

      $res_asistencias = mysqli_query($conexion, $sql_asistencias);

      echo "<div class='tabla-responsive'>
              <table>
                <thead>
                  <tr>
                    <th>Nombre</th>
                    <th>Apellidos</th>
                    <th>DNI</th>
                    <th>Asistencia</th>
                    <th>Bandejas</th>
                    <th>Horas</th>
                    <th>Observaciones</th>
                  </tr>
                </thead>
                <tbody>";

      if ($res_asistencias && mysqli_num_rows($res_asistencias) > 0) {
        while ($fila = mysqli_fetch_assoc($res_asistencias)) {
          echo "<tr>
                  <td>{$fila['nombre']}</td>
                  <td>{$fila['apellidos']}</td>
                  <td>{$fila['dni']}</td>
                  <td>" . ($fila['asistencia'] === 'si' ? '✅' : '❌') . "</td>
                  <td>{$fila['Bandeja']}</td>
                  <td>{$fila['Horas']}</td>
                  <td>{$fila['Observaciones']}</td>
                </tr>";
        }
      } else {
        echo "<tr><td colspan='7' class='mensaje-vacio'>No hay registros de asistencia.</td></tr>";
      }

      echo "</tbody></table></div>";
    }

    else {
      echo "<p class='mensaje-vacio'>Selecciona una fecha para ver los listados.</p>";
    }
    ?>
  </main>

  <?php include(__DIR__ . '/footer.php'); ?>

  <script>
    flatpickr("#fecha_buscar", {
      dateFormat: "Y-m-d",
      defaultDate: "<?php echo $fecha ?: date('Y-m-d'); ?>",
      allowInput: false
    });

    document.getElementById("btnBuscar").addEventListener("click", () => {
      const fecha = document.getElementById("fecha_buscar").value.trim();
      if (fecha) window.location.href = "?fecha=" + encodeURIComponent(fecha);
    });
  </script>
</body>
</html>
