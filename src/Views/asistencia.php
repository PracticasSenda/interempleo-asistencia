<?php
include(__DIR__ . '/../auth/validar_sesion.php');
include(__DIR__ . '/../config/db.php');
include(__DIR__ . '/../config/csrf.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Parte de Asistencia - Interempleo</title>

  <!-- Estilos -->
  <link rel="stylesheet" href="../../public/css/style-global.css">
  <link rel="stylesheet" href="../../public/css/modal.css">
  <link rel="stylesheet" href="../../public/css/asistencia.css">

  <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
</head>

<body>
<?php include(__DIR__ . '/header.php'); ?>

<div class="wrap">
  <h2 class="titulo-seccion">Parte de Asistencia</h2>

  <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div class="banner ok" style="
      background:#e8f9ef;border:1px solid #2ecc71;color:#27ae60;
      padding:12px 16px;border-radius:8px;margin-bottom:15px;
      font-weight:500;display:flex;align-items:center;gap:8px;">
      <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path d="M20 6L9 17l-5-5" />
      </svg>
      Parte de asistencia guardado correctamente.
    </div>
  <?php endif; ?>

  <div class="panel sombra-blanca">
    <form id="form_asistencia" action="../controllers/guardar_asistencia.php" method="POST" enctype="multipart/form-data" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

      <!-- ====================== Información general ====================== -->
      <div class="info-general">
        <?php if (strtolower($_SESSION['rol'] ?? '') === 'administrador'): ?>
          <!-- Campo ENCARGADO (admin elige desde sugerencias) -->
          <div class="campo-encargado" style="position:relative">
            <label for="nombre_encargado">Encargado</label>
            <input type="text" id="nombre_encargado" name="nombre_encargado"
                   placeholder="Escriba el nombre del encargado" autocomplete="off" required>
            <input type="hidden" id="id_encargado" name="id_encargado" value="">
            <div id="sugerencias_encargado" class="sugerencias" style="display:none"></div>
            <small style="color:#666;font-size:.8em">(Este parte será emitido desde la cuenta del administrador)</small>
          </div>
        <?php else: ?>
          <!-- Si es encargado, queda fijado su propio ID -->
          <label>Encargado
            <input type="text" id="nombre_encargado" name="nombre_encargado"
                   value="<?= htmlspecialchars(trim(($_SESSION['nombre'] ?? '') . ' ' . ($_SESSION['apellidos'] ?? ''))) ?>"
                   readonly>
            <input type="hidden" id="id_encargado" name="id_encargado" value="<?= (int)($_SESSION['id'] ?? 0) ?>">
          </label>
        <?php endif; ?>

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

      <div class="separador-limpio"><span>Opciones de búsqueda</span></div>

      <!-- ====================== Orden y buscador ====================== -->
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

      <!-- ====================== Aplicar a todos ====================== -->
      <div class="aplicar-todos">
        <label>Aplicar bandejas y horas a todos:</label>
        <input type="number" id="bandejas_global" placeholder="Bandejas (todos)">
        <input type="number" id="horas_global" placeholder="Horas (todos)">
        <button type="button" id="btn_aplicar_todos" class="btn-aplicar">Aplicar</button>
      </div>

      <!-- ====================== Tabla ====================== -->
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

      <!-- ====================== Contador + Firma + Envío ====================== -->
      <div class="acciones-finales">
        <div id="contador_asistencia" class="contador-asistencia">
          <strong>Total:</strong> <span id="count-total">0</span> |
          <strong>Presentes:</strong> <span id="count-pres">0</span> |
          <strong>Ausentes:</strong> <span id="count-aus">0</span>
        </div>

        <!-- Firma del encargado -->
        <div class="firma-container" style="margin-top:16px;">
          <h3 style="margin:0 0 8px;">Firma del encargado</h3>
          <p style="font-size:.9em;color:#555;margin:0 0 8px;">Firme aquí para validar y emitir el parte</p>

          <div style="max-width:420px;">
            <canvas id="canvasFirma"
                    style="border:2px solid #ccc;border-radius:8px;background:#fff;width:100%;height:160px;touch-action:none;"></canvas>
          </div>

          <div class="firma-controles" style="margin-top:8px;display:flex;gap:8px;">
            <button type="button" id="btnBorrarFirma" class="btn-secundario">Borrar firma</button>
          </div>

          <input type="hidden" name="firma_base64" id="firmaBase64">
        </div>

        <!-- Hidden para detalle -->
        <input type="hidden" name="detalle_json" id="detalle_json">

        <button type="submit" id="btnGuardarParte" class="btn-principal" disabled style="margin-top:12px;">
          Emitir parte
        </button>
      </div>
    </form>
  </div>
</div>

<?php include(__DIR__ . '/footer.php'); ?>

<!-- ====================== Scripts ====================== -->
<script src="../public/js/asistencia.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script src="../public/js/firma.js"></script>

<!-- Submit único: valida firma y empaqueta detalle_json -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('form_asistencia');

  form.addEventListener('submit', (e) => {
    // Recorre SÓLO filas principales (no las de detalle)
    const filas = Array.from(document.querySelectorAll(
      '#tabla_asistencia tbody tr'
    )).filter(tr => !tr.classList.contains('fila-detalle'));

    const detalle = filas.map(tr => {
      // en cada fila el checkbox tiene data-dni (así lo genera asistencia.js)
      const chk    = tr.querySelector('.check-asistencia');
      const dni    = chk?.dataset?.dni || tr.querySelector('td:nth-child(3)')?.textContent?.trim() || '';

      // busca inputs del panel detalle si están abiertos / ya cargados
      const filaDetalle = tr.nextElementSibling?.classList?.contains('fila-detalle')
        ? tr.nextElementSibling : null;

      const inpBan = filaDetalle?.querySelector('input[name^="Bandeja_"]');
      const inpHor = filaDetalle?.querySelector('input[name^="Horas_"]');
      const inpObs = filaDetalle?.querySelector('input[name^="Observaciones_"]') 
                  || filaDetalle?.querySelector('textarea[name^="Observaciones_"]');

      return {
        dni: String(dni || '').trim(),
        asistencia: (chk && chk.checked) ? 'si' : 'no',
        bandejas: Number(inpBan?.value ?? 0) || 0,
        horas:    Number(inpHor?.value ?? 0) || 0,
        observaciones: String(inpObs?.value ?? '').trim()
      };
    }).filter(r => r.dni); // ← ahora validamos por dni, no por id

    if (!detalle.length) {
      e.preventDefault();
      alert('No hay trabajadores en la lista.');
      return;
    }

    document.getElementById('detalle_json').value = JSON.stringify(detalle);
  });
});
</script>



<!-- Desvanece el banner de éxito -->
<script>
document.addEventListener("DOMContentLoaded", () => {
  const banner = document.querySelector(".banner.ok");
  if (banner) {
    setTimeout(() => {
      banner.style.transition = "opacity .8s ease";
      banner.style.opacity = "0";
      setTimeout(() => banner.remove(), 800);
    }, 4000);
  }
});
</script>

<?php if (strtolower($_SESSION['rol'] ?? '') === 'administrador'): ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const input       = document.getElementById("nombre_encargado");
  const inputHidden = document.getElementById("id_encargado");
  const box         = document.getElementById("sugerencias_encargado");
  const form        = document.getElementById("form_asistencia");
  if (!input || !inputHidden || !box || !form) return;

  let lockPick = false;

  // ⬇️ Ajusta AQUÍ la URL real de tu endpoint
  // const ENDPOINT = '../ajax/acciones.php?action=buscar_encargado'; // si devuelve HTML
  const ENDPOINT = '../controllers/buscar_encargado.php';              // si devuelve JSON

  // ---------- BÚSQUEDA ----------
  input.addEventListener("input", async () => {
    if (lockPick) { lockPick = false; return; }
    inputHidden.value = "";
    const q = input.value.trim();
    box.innerHTML = "";
    if (q.length < 2) { box.style.display = "none"; return; }

    try {
      const url = `${ENDPOINT}${ENDPOINT.includes('?') ? '&' : '?'}q=${encodeURIComponent(q)}`;
      const res = await fetch(url, { headers: { "Accept": "*/*" }});
      const raw = await res.text();

      // 1) Intentar JSON
      let rendered = false;
      try {
        const data = JSON.parse(raw);
        if (Array.isArray(data) && data.length) {
          const items = data.map(it => {
            const id  = it.id ?? it.id_usuario ?? it.id_encargado ?? it.ID ?? "";
            const nom = it.nombre_completo ?? it.nombrec ?? it.nombre ?? "";
            const ape = it.apellidos ?? "";
            const dni = it.dni ?? it.DNI ?? "";
            const label = (nom && ape) ? `${nom} ${ape}` : (nom || dni);
            return { id: String(id), label: String(label || "").trim(), dni: String(dni || "") };
          }).filter(x => x.id && x.label);

          if (items.length) {
            box.innerHTML = items.map(it => `
              <div class="sugerencia-item" data-id="${it.id}" data-label="${it.label}">
                <strong>${it.label}</strong><br>
                <small style="color:#666">DNI: ${it.dni}</small>
              </div>
            `).join("");
            box.style.display = "block";
            rendered = true;
          }
        }
      } catch(_) { /* no era JSON */ }

      // 2) Si no había JSON válido, tratamos como HTML
      if (!rendered) {
        box.innerHTML = raw.trim();
        box.style.display = box.innerHTML ? "block" : "none";
      }
    } catch (err) {
      console.error("Autocompletado encargados:", err);
      box.style.display = "none";
    }
  });

  // ---------- SELECCIÓN ----------
  const pick = (node) => {
    const id    = (node.dataset.id || "").trim();
    const label = (node.dataset.label || node.textContent || "").trim();
    if (!id || !label) return;

    lockPick = true;
    input.value = label;
    inputHidden.value = id;
    box.style.display = "none";

    input.style.border = "2px solid #2ecc71";
    input.style.background = "#e8f9ef";
    setTimeout(() => { input.style.border = ""; input.style.background = ""; }, 1200);
  };

  box.addEventListener("mousedown", (e) => {
    const node = e.target.closest("[data-id]");
    if (!node) return;
    e.preventDefault();
    pick(node);
  });
  box.addEventListener("click", (e) => {
    const node = e.target.closest("[data-id]");
    if (!node) return;
    pick(node);
  });

  document.addEventListener("click", (e) => {
    if (!e.target.closest(".campo-encargado")) box.style.display = "none";
  });

  // (Este submit convive con el de firma+detalle sin problema)
  form.addEventListener("submit", (e) => {
    if (!(inputHidden.value || "").trim()) {
      e.preventDefault();
      alert("⚠️ Debe seleccionar un encargado desde la lista de sugerencias (no escribirlo manualmente).");
      input.focus();
    }
  });
});
</script>
<?php endif; ?>

</body>
</html>
