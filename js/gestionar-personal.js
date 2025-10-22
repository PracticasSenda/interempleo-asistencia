/* =========================================================
🧠 BUSCADOR EN TIEMPO REAL + EVENTOS MODALES
========================================================= */
document.addEventListener("DOMContentLoaded", () => {
  /* --- Buscador (ignora tildes y mayúsculas) --- */
  const buscador = document.getElementById("buscador");
  if (buscador) {
    const quitarTildes = (str) =>
      str.normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/ñ/g, "n").replace(/Ñ/g, "N")
        .toLowerCase().trim();

    buscador.addEventListener("keyup", function () {
      const texto = quitarTildes(this.value);
      const partes = texto.split(/\s+/);
      const filas = document.querySelectorAll("table tbody tr");
      filas.forEach((fila) => {
        const contenido = quitarTildes(fila.textContent);
        fila.style.display = partes.every((p) => contenido.includes(p)) ? "" : "none";
      });
    });
  }

  /* =========================================================
  🔘 BOTONES DE LOS MODALES
  ========================================================= */
  const btnConfirmar = document.getElementById("btnConfirmar");
  const btnCancelar = document.getElementById("btnCancelar");
  if (btnConfirmar) btnConfirmar.onclick = () => { if (formPendiente) formPendiente.submit(); cerrarModal("modalBaja"); };
  if (btnCancelar) btnCancelar.onclick = () => cerrarModal("modalBaja");

  const btnConfirmarAlta = document.getElementById("btnConfirmarAlta");
  const btnCancelarAlta = document.getElementById("btnCancelarAlta");
  if (btnConfirmarAlta) btnConfirmarAlta.onclick = () => { if (formAltaPendiente) formAltaPendiente.submit(); cerrarModal("modalAlta"); };
  if (btnCancelarAlta) btnCancelarAlta.onclick = () => cerrarModal("modalAlta");

  const btnConfirmarAltaNueva = document.getElementById("btnConfirmarAltaNueva");
  const btnCancelarAltaNueva = document.getElementById("btnCancelarAltaNueva");
  if (btnConfirmarAltaNueva) btnConfirmarAltaNueva.onclick = () => {
    if (formAltaNuevaPendiente) {
      console.log("✅ Enviando formulario de alta nueva");
      formAltaNuevaPendiente.submit();
      cerrarModal("modalAltaNueva");
    }
  };
  if (btnCancelarAltaNueva) btnCancelarAltaNueva.onclick = () => cerrarModal("modalAltaNueva");
});

/* =========================================================
⚙️ VARIABLES GLOBALES PARA FORMULARIOS
========================================================= */
let formPendiente = null;          // baja
let formAltaPendiente = null;      // reactivar (desde listado)
let formAltaNuevaPendiente = null; // alta desde formulario

/* =========================================================
🔻 CONFIRMAR BAJA
========================================================= */
function abrirModalBaja(form, nombreCompleto) {
  formPendiente = form;
  const modal = document.getElementById("modalBaja");
  const texto = document.getElementById("modalTexto");
  if (!modal || !texto)
    return confirm(`¿Estás seguro de que deseas dar de baja a ${nombreCompleto}?`);
  texto.textContent = `¿Estás seguro de que deseas dar de baja a ${nombreCompleto}?`;
  modal.style.display = "flex";
  return false;
}

/* =========================================================
🔺 CONFIRMAR REACTIVACIÓN (ALTA DESDE LISTADO)
========================================================= */
function abrirModalAlta(form, nombreCompleto) {
  formAltaPendiente = form;
  const modal = document.getElementById("modalAlta");
  const texto = document.getElementById("modalTextoAlta");
  if (!modal || !texto)
    return confirm(`¿Deseas dar de alta a ${nombreCompleto}?`);
  texto.textContent = `¿Deseas dar de alta a ${nombreCompleto}?`;
  modal.style.display = "flex";
  return false;
}

/* =========================================================
🟢 CONFIRMAR ALTA NUEVA (FORMULARIO)
========================================================= */
function abrirModalAltaNueva(form) {
  const nombre = form.querySelector('[name="nombre"]')?.value.trim().toUpperCase() || "";
  const apellidos = form.querySelector('[name="apellidos"]')?.value.trim().toUpperCase() || "";
  const dni = form.querySelector('[name="dni"]')?.value.trim().toUpperCase() || "";

  if (!nombre || !apellidos || !dni) {
    mostrarBanner("❌ Todos los campos son obligatorios.", "err");
    return false;
  }

  const params = new URLSearchParams(window.location.search);
  const esEncargado = params.get("tipo") === "encargados";

  if (esEncargado) {
    const pass1 = form.querySelector('[name="contraseña"]')?.value ?? "";
    const pass2 = form.querySelector('[name="confirmar_contraseña"]')?.value ?? "";
    if (!pass1 || !pass2) {
      mostrarBanner("⚠️ Debes completar ambas contraseñas.", "warn");
      return false;
    }
    if (pass1 !== pass2) {
      mostrarBanner("❌ Las contraseñas no coinciden.", "err");
      return false;
    }
  }

  formAltaNuevaPendiente = form;
  const modal = document.getElementById("modalAltaNueva");
  const texto = document.getElementById("modalTextoAltaNueva");

  if (!modal || !texto)
    return confirm("¿Confirmas dar de alta al nuevo registro?");

  const tipoTxt = esEncargado ? "encargado" : "trabajador";
  texto.innerHTML = `
    <span style="display:block; margin-bottom:10px;">¿Deseas dar de alta al siguiente ${tipoTxt}?</span>
    <p style="text-align:left; margin:0;">
      <b>🪪 DNI:</b> ${dni}<br>
      <b>👤 Nombre:</b> ${nombre} ${apellidos}
    </p>
  `;
  modal.style.display = "flex";
  return false;
}

function mostrarBanner(mensaje, tipo = "ok", duracion = 4000) {
  // Eliminar banners anteriores temporales
  document.querySelectorAll(".banner.temp").forEach(b => b.remove());

  // Crear el banner
  const banner = document.createElement("div");
  banner.className = `banner ${tipo} temp`;

  // Contenido del banner (idéntico a los PHP)
  banner.innerHTML = `
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none"
         stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
         style="flex-shrink:0; margin-right:8px;">
      ${
        tipo === "ok"
          ? '<path d="M20 6L9 17l-5-5"/>'
          : tipo === "warn"
          ? '<path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a1 1 0 0 0 .86 1.5h18.64a1 1 0 0 0 .86-1.5L13.71 3.86a1 1 0 0 0-1.72 0z"/>'
          : '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>'
      }
    </svg>
    ${mensaje}
    <button type="button" class="close-banner" aria-label="Cerrar" onclick="this.parentElement.remove()">✖</button>
  `;

  // Estilos coherentes con banners PHP
  banner.style.display = "flex";
  banner.style.alignItems = "center";
  banner.style.gap = "8px";
  banner.style.margin = "10px auto";
  banner.style.padding = "10px 12px";
  banner.style.borderRadius = "10px";
  banner.style.maxWidth = "520px";
  banner.style.fontWeight = "700";
  banner.style.fontSize = "0.95rem";
  banner.style.boxShadow = "0 2px 6px rgba(0,0,0,0.08)";
  banner.style.transition = "opacity 0.4s ease, transform 0.4s ease";
  banner.style.opacity = "0";
  banner.style.transform = "translateY(-6px)";

  // Insertar en el contenedor principal
  const contenedor = document.querySelector(".wrap") || document.body;
  contenedor.prepend(banner);

  // Animación de entrada
  setTimeout(() => {
    banner.style.opacity = "1";
    banner.style.transform = "translateY(0)";
  }, 20);

  // Autoocultar
  setTimeout(() => {
    banner.style.opacity = "0";
    banner.style.transform = "translateY(-6px)";
    setTimeout(() => banner.remove(), 400);
  }, duracion);
}

/* =========================================================
👁️ MOSTRAR / OCULTAR CONTRASEÑAS
========================================================= */
function togglePassword(id, btn) {
  const input = document.getElementById(id);
  if (!input) return;
  const showing = input.type === "text";
  input.type = showing ? "password" : "text";
  btn.classList.toggle("active", !showing);
  btn.setAttribute("data-tooltip", showing ? "Mostrar contraseña" : "Ocultar contraseña");
}

/* =========================================================
🧼 CERRAR MODAL HELPER
========================================================= */
function cerrarModal(id) {
  const m = document.getElementById(id);
  if (m) m.style.display = "none";
}
