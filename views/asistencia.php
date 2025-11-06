<?php
include(__DIR__ . '/../auth/validar_sesion.php');
include(__DIR__ . '/../config/db.php');
include(__DIR__ . '/../config/csrf.php');
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Parte de Asistencia - Interempleo</title>
  <link rel="stylesheet" href="../css/style-global.css">
  <link rel="stylesheet" href="../css/modal.css">
  <link rel="stylesheet" href="../css/asistencia.css">
  <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

</head>

<body>
  <?php include(__DIR__ . '/header.php'); ?>

  <div class="wrap">
    <h2 class="titulo-seccion">Parte de Asistencia</h2>

    <div class="panel sombra-blanca">
      <form id="form_asistencia" action="../controllers/guardar_asistencia.php" method="POST" enctype="multipart/form-data" autocomplete="off">
        <!-- token CSRF -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

        <!-- Información general -->
        <div class="info-general">
          <label>Encargado
            <input type="text"
              id="nombre_encargado"
              name="nombre_encargado"
              value="<?= htmlspecialchars($_SESSION['nombre'] ?? '') ?>"
              readonly>
          </label>


          <label>Empresa
            <input type="text" id="empresa" name="empresa" placeholder="EMPRESA USUARIA" required>
          </label>

          <label>Fecha
            <input type="date" id="fecha" name="fecha" value="<?= date('Y-m-d') ?>" required>
          </label>

          <label>Producto
            <input type="text" id="producto" name="producto" placeholder="PRODUCTO" required>
          </label>
          <br>
        </div>
        <div class="separador-limpio">
          <span>Opciones de búsqueda</span>
        </div>
        <!-- Orden y buscador -->
        <div class="panel-control">

          <div class="buscador-global" style="flex:2;">
            <input type="text" id="buscador_trabajador" placeholder="Buscar por NOMBRE, APELLIDOS o DNI">
            <div id="sugerencias_trabajador" class="sugerencias" style="display:none;"></div>
          </div>

          <label>Ordenar
            <select id="orden_tabla">
              <option value="alfabetico" selected>A-Z (alfabético)</option>
              <option value="recientes">Más recientes</option>
              <option value="asistencia">Asistentes primero</option>
            </select>


          </label>


        </div>

        <!-- Aplicar bandejas -->
        <div class="aplicar-todos">
          <label>Aplicar bandejas y horas a todos:</label>
          <input type="number" id="bandejas_global" placeholder="Bandejas (todos)">
          <input type="number" id="horas_global" placeholder="Horas (todos)">
          <button type="button" id="btn_aplicar_todos" class="btn-aplicar">Aplicar</button>
        </div>

        <!-- Tabla -->
        <div class="tabla-container">
          <table id="tabla_asistencia" class="tabla-asistencia">
            <thead>
              <tr>
                <th>Asistencia</th>
                <th>Nombre completo</th>
                <th>DNI</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>


        <!-- Contador + Firma + Botón -->
        <div class="acciones-finales">
          <div id="contador_asistencia" class="contador-asistencia">
            <strong>Total:</strong> <span id="count-total">0</span> |
            <strong>Presentes:</strong> <span id="count-pres">0</span> |
            <strong>Ausentes:</strong> <span id="count-aus">0</span>
          </div>

          <!-- 🔹 Bloque de FIRMA DEL ENCARGADO -->
          <div class="firma-container" style="margin-top:16px;">
            <h3 style="margin:0 0 8px;">Firma del encargado</h3>
            <p style="font-size:0.9em;color:#555;margin:0 0 8px;">Firme aquí para validar y emitir el parte</p>

            <!-- Canvas de firma -->
            <div style="max-width:420px;">
              <canvas id="canvasFirma"
                style="border:2px solid #ccc;border-radius:8px;background:#fff;width:100%;height:160px;touch-action:none;"></canvas>
            </div>

            <div class="firma-controles" style="margin-top:8px;display:flex;gap:8px;">
              <button type="button" id="btnBorrarFirma" class="btn-secundario">Borrar firma</button>
            </div>

            <!-- Campo oculto para enviar la firma -->
            <input type="hidden" name="firma_base64" id="firmaBase64">
          </div>

          <!-- 🔸 Botón final deshabilitado hasta que haya firma -->
          <button type="submit" id="btnGuardarParte" class="btn-principal" disabled style="margin-top:12px;">
            Emitir parte
          </button>

        </div>


      </form>

      <!-- Banner confirmación -->
      <div id="banner_confirmacion" class="banner ok" style="display:none;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path d="M20 6L9 17l-5-5" />
        </svg>
        Parte de asistencia guardada correctamente.
      </div>
    </div>
  </div>


  <!-- Modal de resumen antes de guardar -->
  <div id="modal-resumen-parte" class="modal-mini" style="display:none;">
    <div class="modal-mini-content resumen-content">
      <h3>Confirmar parte de asistencia</h3>
      <div id="resumen-parte"></div>
      <div class="modal-mini-buttons">
        <button id="btn-cancelar-resumen" class="btn-secundario">Cancelar</button>
        <button id="btn-confirmar-resumen" class="btn-principal">Confirmar y guardar</button>
      </div>
    </div>
  </div>




  <?php include(__DIR__ . '/footer.php'); ?>
  <script src="../js/asistencia.js"></script>

  <!-- Librería SignaturePad (desde CDN) -->
  <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>

  <!-- Script personalizado para manejar la firma -->
  <script src="../js/firma.js"></script>

  <script>
    // Evitar envío sin firma (seguridad extra)
    document.getElementById("btnGuardarParte").addEventListener("click", function(e) {
      if (!document.getElementById("firmaBase64").value) {
        e.preventDefault();
        alert("⚠️ Debe firmar antes de emitir el parte.");
      }
    });
  </script>


</body>

</html>