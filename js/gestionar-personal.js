/* =========================================================
🧠 BUSCADOR EN TIEMPO REAL (Ignora tildes y mayúsculas)
========================================================= */
document.addEventListener("DOMContentLoaded", () => {
    const buscador = document.getElementById("buscador");
    if (!buscador) return;

    const quitarTildes = (str) => {
        return str
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .replace(/ñ/g, "n")
            .replace(/Ñ/g, "N")
            .toLowerCase()
            .trim();
    };

    buscador.addEventListener("keyup", function () {
        const texto = quitarTildes(this.value);
        const partes = texto.split(/\s+/);
        const filas = document.querySelectorAll("table tbody tr");
        filas.forEach((fila) => {
            const contenido = quitarTildes(fila.textContent);
            fila.style.display = partes.every((p) => contenido.includes(p)) ? "" : "none";
        });
    });
});

/* =========================================================
⚙️ MODALES DE CONFIRMACIÓN
========================================================= */
let formPendiente = null;
let formAltaPendiente = null;

// 🔻 Confirmar baja
function abrirModalBaja(form, nombreCompleto) {
    formPendiente = form;
    const modal = document.getElementById("modalBaja");
    const texto = document.getElementById("modalTexto");

    if (!modal || !texto) {
        console.warn("⚠️ No se encontró el modal de baja");
        return confirm(`¿Estás seguro de que deseas dar de baja a ${nombreCompleto}?`);
    }

    texto.textContent = `¿Estás seguro de que deseas dar de baja a ${nombreCompleto}?`;
    modal.style.display = "flex";
    return false; // evita envío inmediato
}

// 🔺 Confirmar alta (reactivación o nuevo)
function abrirModalAlta(form, nombreCompleto) {
    formAltaPendiente = form;
    const modal = document.getElementById("modalAlta");
    const texto = document.getElementById("modalTextoAlta");

    if (!modal || !texto) {
        console.warn("⚠️ No se encontró el modal de alta");
        return confirm(`¿Deseas dar de alta a ${nombreCompleto}?`);
    }

    texto.textContent = `¿Deseas dar de alta a ${nombreCompleto}?`;
    modal.style.display = "flex";
    return false;
}

// 🔹 Cerrar modal
function cerrarModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.style.display = "none";
}

// Eventos para botones de los modales
document.addEventListener("DOMContentLoaded", () => {
    const btnConfirmar = document.getElementById("btnConfirmar");
    const btnCancelar = document.getElementById("btnCancelar");
    const btnConfirmarAlta = document.getElementById("btnConfirmarAlta");
    const btnCancelarAlta = document.getElementById("btnCancelarAlta");

    if (btnConfirmar) btnConfirmar.onclick = () => {
        if (formPendiente) formPendiente.submit();
        cerrarModal("modalBaja");
    };

    if (btnCancelar) btnCancelar.onclick = () => cerrarModal("modalBaja");

    if (btnConfirmarAlta) btnConfirmarAlta.onclick = () => {
        if (formAltaPendiente) formAltaPendiente.submit();
        cerrarModal("modalAlta");
    };

    if (btnCancelarAlta) btnCancelarAlta.onclick = () => cerrarModal("modalAlta");
});

/* =========================================================
🔐 MOSTRAR / OCULTAR CONTRASEÑA
========================================================= */
function togglePassword(id, btn) {
    const input = document.getElementById(id);
    if (!input || !btn) return;

    const showing = input.type === "text";
    input.type = showing ? "password" : "text";
    btn.classList.toggle("active", !showing);

    btn.setAttribute("data-tooltip", showing ? "Mostrar contraseña" : "Ocultar contraseña");
}

/* =========================================================
🧩 VALIDAR CONTRASEÑAS ANTES DE ENVIAR FORMULARIO
========================================================= */
document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector(".form-alta");
    if (!form) return;

    form.addEventListener("submit", (e) => {
        const pass = document.getElementById("contraseña");
        const confirm = document.getElementById("confirmar_contraseña");

        if (pass && confirm) {
            if (pass.value !== confirm.value) {
                e.preventDefault();
                alert("❌ Las contraseñas no coinciden. Por favor, verifícalas.");
                confirm.focus();
            } else {
                // Confirmación final antes de alta
                if (!confirm(`¿Deseas guardar el nuevo registro?`)) {
                    e.preventDefault();
                }
            }
        } else {
            // Si no hay contraseñas (ej. alta de trabajador)
            if (!confirm("¿Deseas guardar el nuevo registro?")) {
                e.preventDefault();
            }
        }
    });
});

/* =========================================================
💡 CIERRA MODALES CON CLICK FUERA
========================================================= */
window.addEventListener("click", (e) => {
    const modalBaja = document.getElementById("modalBaja");
    const modalAlta = document.getElementById("modalAlta");

    if (e.target === modalBaja) cerrarModal("modalBaja");
    if (e.target === modalAlta) cerrarModal("modalAlta");
});


