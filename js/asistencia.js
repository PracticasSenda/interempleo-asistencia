document.addEventListener("DOMContentLoaded", () => {
  const tablaBody = document.querySelector("#tabla_asistencia tbody");
  const buscador = document.getElementById("buscador_trabajador");
  const sugerencias = document.getElementById("sugerencias_trabajador");
  const banner = document.getElementById("banner_confirmacion");

  /* =====================================================
     🔸 BUSCADOR DE TRABAJADORES (lista flotante)
  ===================================================== */
  if (buscador && sugerencias) {
    buscador.addEventListener("input", async () => {
      const texto = buscador.value.trim();
      if (texto.length < 2) {
        sugerencias.innerHTML = "";
        sugerencias.style.display = "none";
        return;
      }

      const params = new URLSearchParams({
        action: "buscar_trabajadores",
        q: texto
      });

      const resp = await fetch("../controllers/asistencia_controller.php?" + params);
      let html = await resp.text();

      // 🧩 Crear contenedor temporal para manipular las sugerencias
      const tempDiv = document.createElement("div");
      tempDiv.innerHTML = html;

      // 🧹 Filtrar trabajadores que ya están en la tabla
      const trabajadoresEnTabla = Array.from(document.querySelectorAll("tr[id^='fila_']")).map(f => f.id.replace("fila_", ""));
      tempDiv.querySelectorAll(".sugerencia-item").forEach(item => {
        const dni = item.dataset.dni;
        if (trabajadoresEnTabla.includes(dni)) {
          item.remove(); // eliminar de la lista los que ya están agregados
        }
      });

      // Mostrar solo los que faltan
      sugerencias.innerHTML = tempDiv.innerHTML.trim() !== "" ? tempDiv.innerHTML : "<div class='sugerencia-item sin-resultados'>No hay más trabajadores disponibles</div>";
      sugerencias.style.display = "block";

      // Activar clic para agregar trabajadores
      document.querySelectorAll(".sugerencia-item").forEach(item => {
        if (!item.classList.contains("sin-resultados")) {
          item.addEventListener("click", () => {
            const dni = item.dataset.dni;
            const nombre = item.dataset.nombre;
            agregarTrabajador(nombre, dni);
            buscador.value = "";
            sugerencias.innerHTML = "";
            sugerencias.style.display = "none";
          });
        }
      });
    });

    // Ocultar sugerencias si haces clic fuera
    document.addEventListener("click", e => {
      if (!sugerencias.contains(e.target) && e.target !== buscador) {
        sugerencias.style.display = "none";
      }
    });
  }

  /* =====================================================
     🔸 FUNCIÓN PARA AGREGAR TRABAJADOR A LA TABLA
  ===================================================== */
  function agregarTrabajador(nombre, dni) {
    if (!tablaBody) return;

    // Verificar si ya está en la tabla
    if (document.getElementById("fila_" + dni)) {
      alert("Este trabajador ya está en la lista.");
      return;
    }

    const fila = document.createElement("tr");
    fila.id = "fila_" + dni;
    fila.innerHTML = `
      <td><input type="checkbox" class="check-asistencia" data-dni="${dni}"></td>
      <td>${nombre}</td>
      <td>${dni}</td>
      <td class="acciones">
        <div class="menu-acciones">
          <button type="button" class="btn-menu" aria-haspopup="true" aria-expanded="false" title="Acciones">⋮</button>
          <div class="menu-list" role="menu">
            <button type="button" class="menu-item btn-detalle-toggle" data-dni="${dni}" role="menuitem">Detalles</button>
            <button type="button" class="menu-item btn-eliminar-fila" data-dni="${dni}" role="menuitem">Quitar trabajador</button>
          </div>
        </div>
      </td>
    `;

    const filaDetalle = document.createElement("tr");
    filaDetalle.classList.add("fila-detalle");
    filaDetalle.innerHTML = `
      <td colspan="4">
        <div class="detalle-content"></div>
        <div class="feedback-guardar"></div>
      </td>
    `;

    tablaBody.appendChild(fila);
    tablaBody.appendChild(filaDetalle);
    actualizarPlaceholder();
    activarEventosFila(fila, filaDetalle);
  }

  /* =====================================================
   🔸 ACTIVAR EVENTOS DE CADA FILA
  ===================================================== */
  function activarEventosFila(fila, filaDetalle) {
    const btnDetalle = fila.querySelector(".btn-detalle-toggle");
    const btnEliminar = fila.querySelector(".btn-eliminar-fila");

    if (!btnDetalle || !btnEliminar) return;

    // Mostrar u ocultar detalle
    btnDetalle.addEventListener("click", async () => {
      const dni = btnDetalle.dataset.dni;
      const abierto = filaDetalle.classList.contains("open");

      // Cerrar otros abiertos
      document.querySelectorAll(".fila-detalle.open").forEach(f => f.classList.remove("open"));

      if (!abierto) {
        filaDetalle.classList.add("open");

        // Cargar detalle si no está cargado
        if (!filaDetalle.dataset.cargado) {
          const fecha = document.getElementById("fecha")?.value;
          const params = new URLSearchParams({ action: "detalle_trabajador", dni, fecha });
          const resp = await fetch("../controllers/asistencia_controller.php?" + params);
          const html = await resp.text();
          filaDetalle.querySelector(".detalle-content").innerHTML = html;

          // 🧩 Aplicar los valores generales si existen (cuando se abre el detalle)
          const bandejasPend = fila.dataset.bandejasPendientes || "";
          const horasPend = fila.dataset.horasPendientes || "";

          if (bandejasPend || horasPend) {
            const inputB = filaDetalle.querySelector(`input[name='Bandeja_${dni}']`);
            const inputH = filaDetalle.querySelector(`input[name='Horas_${dni}']`);
            if (inputB && bandejasPend) inputB.value = bandejasPend;
            if (inputH && horasPend) inputH.value = horasPend;
          }

          filaDetalle.dataset.cargado = "1";

          // 🔹 Desactivar bandejas y horas si no asistió (pero permitir observaciones)
          const checkAsistencia = fila.querySelector(`.check-asistencia[data-dni="${dni}"]`);
          const inputB = filaDetalle.querySelector(`input[name='Bandeja_${dni}']`);
          const inputH = filaDetalle.querySelector(`input[name='Horas_${dni}']`);

          function actualizarCampos() {
            const asistio = checkAsistencia && checkAsistencia.checked;
            if (!asistio) {
              if (inputB) inputB.disabled = true;
              if (inputH) inputH.disabled = true;
            } else {
              if (inputB) inputB.disabled = false;
              if (inputH) inputH.disabled = false;
            }
          }

          // Ejecutar al abrir el detalle
          actualizarCampos();
          // Escuchar cambios del checkbox
          checkAsistencia?.addEventListener("change", actualizarCampos);

          // Guardar detalle individual
          const btnGuardar = filaDetalle.querySelector(".btn-guardar-detalle");
          if (btnGuardar) {
            btnGuardar.addEventListener("click", async () => {
              await guardarDetalle(dni, filaDetalle);
              filaDetalle.classList.remove("open"); // cerrar tras guardar
            });
          }
        }
      } else {
        filaDetalle.classList.remove("open");
      }
    });

    // Eliminar trabajador de la tabla
    btnEliminar.addEventListener("click", () => {
      if (confirm("¿Seguro que quieres quitar este trabajador del parte?")) {
        fila.remove();
        filaDetalle.remove();
        actualizarPlaceholder();
      }
    });
  }

  async function guardarDetalle(dni, contenedor) {
    const empresa = document.getElementById("empresa")?.value.trim();
    const fecha = document.getElementById("fecha")?.value.trim();
    const producto = document.getElementById("producto")?.value.trim();

    if (!empresa || !fecha || !producto) {
      alert("⚠️ Todos los campos generales (empresa, fecha y producto) son obligatorios.");
      return;
    }

    const asistencia = document.querySelector(`.check-asistencia[data-dni="${dni}"]`)?.checked ? "si" : "no";
    const bandeja = contenedor.querySelector(`input[name="Bandeja_${dni}"]`)?.value || "";
    const horas = contenedor.querySelector(`input[name="Horas_${dni}"]`)?.value || "";
    const obs = contenedor.querySelector(`input[name="Observaciones_${dni}"]`)?.value || "";

    const formData = new FormData();
    formData.append("action", "guardar_detalle");
    formData.append("dni", dni);
    formData.append("empresa", empresa);
    formData.append("fecha", fecha);
    formData.append("producto", producto);
    formData.append("asistencia", asistencia);
    formData.append("Bandeja", bandeja);
    formData.append("Horas", horas);
    formData.append("Observaciones", obs);

    const feedback = contenedor.querySelector(".feedback-guardar");

    try {
      const res = await fetch("../controllers/asistencia_controller.php", { method: "POST", body: formData });
      const texto = await res.text();

      if (res.ok && texto.trim() === "OK") {
        if (feedback) {
          feedback.textContent = "✓ Guardado correctamente";
          feedback.className = "feedback-guardar guardado-ok";
        }

        // 🔹 Actualiza los valores en la fila para que se reflejen en el resumen modal
        const fila = document.getElementById(`fila_${dni}`);
        if (fila) {
          fila.dataset.bandejasPendientes = bandeja;
          fila.dataset.horasPendientes = horas;
          fila.dataset.bandejaFinal = bandeja;
          fila.dataset.horasFinal = horas;
        }

        // 🔹 Cierra el panel visualmente
        setTimeout(() => {
          if (feedback) feedback.textContent = "";
          contenedor.classList.remove("open");
        }, 1200);

      } else {
        console.warn("⚠️ Respuesta del servidor:", texto);
        if (feedback) {
          feedback.textContent = "⚠️ Error al guardar";
          feedback.className = "feedback-guardar guardado-error";
        }
      }
    } catch (err) {
      console.error("Error al guardar:", err);
      if (feedback) {
        feedback.textContent = "⚠️ Error de conexión.";
        feedback.className = "feedback-guardar guardado-error";
      }
    }
  }

  // ===============================================
  // 🔸 BOTÓN APLICAR BANDEJAS Y HORAS A TODOS
  // ===============================================
  const btnAplicar = document.querySelector("#btn_aplicar_todos");
  const inputBandejas = document.querySelector("#bandejas_global");
  const inputHoras = document.querySelector("#horas_global");

  if (btnAplicar) {
    btnAplicar.addEventListener("click", () => {
      const bandejas = inputBandejas?.value.trim();
      const horas = inputHoras?.value.trim();

      if (!bandejas && !horas) {
        mostrarBanner("⚠️ Debes introducir al menos un valor de bandejas u horas.", "error");
        return;
      }

      // Confirmación
      if (!confirm(`¿Aplicar estos valores a los trabajadores con asistencia marcada?\n\nBandejas: ${bandejas || "—"}\nHoras: ${horas || "—"}`))
        return;

      const filas = document.querySelectorAll("tr[id^='fila_']");
      if (filas.length === 0) {
        mostrarBanner("⚠️ No hay trabajadores en la lista.", "error");
        return;
      }

      let aplicados = 0;

      filas.forEach(fila => {
        const dni = fila.id.replace("fila_", "");
        const checkAsistencia = fila.querySelector(`.check-asistencia[data-dni="${dni}"]`);

        // Solo aplicar a los que están marcados
        if (checkAsistencia && checkAsistencia.checked) {
          const filaDetalle = fila.nextElementSibling;

          // 🔸 Guardar valores en dataset (aunque el detalle no esté abierto)
          fila.dataset.bandejasPendientes = bandejas || "";
          fila.dataset.horasPendientes = horas || "";

          // 🔸 Si el detalle ya está abierto, actualiza sus campos
          const inputB = filaDetalle?.querySelector(`input[name='Bandeja_${dni}']`);
          const inputH = filaDetalle?.querySelector(`input[name='Horas_${dni}']`);
          if (inputB && bandejas) inputB.value = bandejas;
          if (inputH && horas) inputH.value = horas;

          aplicados++;
        }
      });

      if (aplicados === 0) {
        mostrarBanner("⚠️ No hay trabajadores con asistencia marcada.", "error");
        return;
      }

      if (inputBandejas) inputBandejas.value = "";
      if (inputHoras) inputHoras.value = "";

      mostrarBanner(`✅ Valores aplicados a ${aplicados} trabajador${aplicados > 1 ? "es" : ""}.`, "ok");
    });
  }

  /* =====================================================
     🔸 FUNCIÓN VISUAL DE BANNER
  ===================================================== */
  function mostrarBanner(mensaje, tipo = "ok") {
    let banner = document.getElementById("banner-aplicar");
    if (!banner) {
      banner = document.createElement("div");
      banner.id = "banner-aplicar";
      banner.className = "banner-animado";
      document.body.appendChild(banner);
    }

    banner.textContent = mensaje;
    banner.classList.remove("ok", "error");
    banner.classList.add(tipo);
    banner.style.display = "flex";

    setTimeout(() => {
      banner.style.opacity = "0";
      setTimeout(() => {
        banner.style.display = "none";
        banner.style.opacity = "1";
      }, 500);
    }, 2500);
  }

  /* =====================================================
     🔸 FUNCIÓN VISUAL DE BANNER (duplicada a propósito)
  ===================================================== */
  function mostrarBanner2(mensaje, tipo = "ok") {
    let banner = document.getElementById("banner-aplicar");
    if (!banner) {
      banner = document.createElement("div");
      banner.id = "banner-aplicar";
      banner.className = "banner-animado";
      document.body.appendChild(banner);
    }

    banner.textContent = mensaje;
    banner.classList.remove("ok", "error");
    banner.classList.add(tipo);
    banner.style.display = "flex";

    setTimeout(() => {
      banner.style.opacity = "0";
      setTimeout(() => {
        banner.style.display = "none";
        banner.style.opacity = "1";
      }, 500);
    }, 2500);
  }

  /* =====================================================
     🔸 MENSAJE CUANDO NO HAY TRABAJADORES
  ===================================================== */
  function actualizarPlaceholder() {
    if (!tablaBody) return;
    // Contar solo las filas principales (no las de detalles)
    const filas = tablaBody.querySelectorAll("tr[id^='fila_']").length;

    // Si no hay trabajadores, mostrar el mensaje
    if (filas === 0) {
      tablaBody.innerHTML = `
      <tr class="placeholder-row">
        <td colspan="4" style="text-align:center;color:#888;">
          No hay trabajadores agregados.
        </td>
      </tr>`;
    } else {
      // Si hay trabajadores, eliminar el mensaje placeholder
      const placeholder = tablaBody.querySelector(".placeholder-row");
      if (placeholder) placeholder.remove();
    }
  }

  // Mostrar mensaje inicial vacío
  actualizarPlaceholder();

  /* =====================================================
     🔸 CONTADOR (Total, Presentes, Ausentes)
  ===================================================== */
  const contador = document.getElementById("contador_asistencia");

  function actualizarContador() {
    if (!contador) return;
    const filas = document.querySelectorAll("tr[id^='fila_']").length;
    const presentes = document.querySelectorAll(".check-asistencia:checked").length;
    const ausentes = filas - presentes;

    contador.innerHTML = `
      <strong>Total:</strong> ${filas} | 
      <strong>Presentes:</strong> ${presentes} | 
      <strong>Ausentes:</strong> ${ausentes}
    `;
  }

  // Escuchar los cambios de asistencia
  document.addEventListener("change", e => {
    if (e.target.classList.contains("check-asistencia")) {
      actualizarContador();
    }
  });

  // Actualizar cuando se agrega o elimina una fila
  if (tablaBody) {
    const observer = new MutationObserver(actualizarContador);
    observer.observe(tablaBody, { childList: true, subtree: true });
  }

  // Inicial
  actualizarContador();

  /* =====================================================
     🔁 Ordenar tabla (alfabético, recientes, asistencia)
  ===================================================== */
  const selectOrden = document.getElementById("orden_tabla");

  if (selectOrden) {
    selectOrden.addEventListener("change", () => {
      const tipoOrden = selectOrden.value;
      const tbody = document.querySelector("#tabla_asistencia tbody");
      if (!tbody) return;

      // Obtener todas las filas principales
      const filas = Array.from(tbody.querySelectorAll("tr[id^='fila_']"));

      if (tipoOrden === "alfabetico") {
        filas.sort((a, b) => {
          const nombreA = a.querySelector("td:nth-child(2)").textContent.trim().toLowerCase();
          const nombreB = b.querySelector("td:nth-child(2)").textContent.trim().toLowerCase();
          return nombreA.localeCompare(nombreB, "es");
        });
      } else if (tipoOrden === "recientes") {
        filas.reverse();
      } else if (tipoOrden === "asistencia") {
        filas.sort((a, b) => {
          const checkA = a.querySelector(".check-asistencia")?.checked ? 1 : 0;
          const checkB = b.querySelector(".check-asistencia")?.checked ? 1 : 0;
          return checkB - checkA; // 1 (asistió) primero
        });
      }

      // 🔹 Reconstruir el tbody respetando las filas detalle
      tbody.innerHTML = "";
      filas.forEach(fila => {
        const detalle = fila.nextElementSibling;
        tbody.appendChild(fila);
        if (detalle && detalle.classList.contains("fila-detalle")) {
          tbody.appendChild(detalle);
        }
      });

      mostrarBanner(`✅ Orden cambiado a: ${selectOrden.options[selectOrden.selectedIndex].text}`, "ok");
    });
  }

  /* =====================================================
     🔸 MENÚ DE TRES PUNTOS (DETALLES / ELIMINAR)
  ===================================================== */
  document.addEventListener('click', (e) => {
    // Cerrar menús si haces clic fuera
    if (!e.target.closest('.menu-acciones')) {
      document.querySelectorAll('.menu-acciones.open').forEach(m => m.classList.remove('open'));
      return;
    }

    const cont = e.target.closest('.menu-acciones');
    const btn = e.target.closest('.btn-menu');

    if (btn) {
      const abierto = cont.classList.contains('open');
      document.querySelectorAll('.menu-acciones.open').forEach(m => m.classList.remove('open'));
      cont.classList.toggle('open', !abierto);
    }

    if (e.target.classList.contains('menu-item')) {
      cont.classList.remove('open');
    }
  });

  /* =====================================================
     🔸 GUARDAR PARTE COMPLETO CON RESUMEN MODAL
  ===================================================== */
  const btnGuardarParte = document.getElementById("btnGuardarParte"); // ← coincide con tu HTML
  sincronizarValoresGlobales();

  if (btnGuardarParte) {
    btnGuardarParte.addEventListener("click", () => {
      sincronizarValoresGlobales();
      const encargado = document.getElementById("nombre_encargado")?.value.trim();
      const empresa = document.getElementById("empresa")?.value.trim();
      const fecha = document.getElementById("fecha")?.value.trim();
      const producto = document.getElementById("producto")?.value.trim();

      if (!encargado || !empresa || !fecha || !producto) {
        mostrarBanner("⚠️ Todos los campos generales son obligatorios.", "error");
        return;
      }

      const filas = document.querySelectorAll("tr[id^='fila_']");
      if (filas.length === 0) {
        mostrarBanner("⚠️ No hay trabajadores agregados.", "error");
        return;
      }

      const asistentes = [];
      const ausentes = [];

      filas.forEach(fila => {
        const dni = fila.id.replace("fila_", "");
        const nombre = fila.querySelector("td:nth-child(2)")?.textContent.trim();
        const check = fila.querySelector(`.check-asistencia[data-dni="${dni}"]`);
        const filaDetalle = fila.nextElementSibling;

        const asistencia = check && check.checked ? "si" : "no";
        const bandeja =
          filaDetalle?.querySelector(`input[name='Bandeja_${dni}']`)?.value ||
          fila.dataset.bandejaFinal ||
          fila.dataset.bandejasPendientes ||
          "0";

        const horas =
          filaDetalle?.querySelector(`input[name='Horas_${dni}']`)?.value ||
          fila.dataset.horasFinal ||
          fila.dataset.horasPendientes ||
          "0";

        if (asistencia === "si") {
          asistentes.push({ dni, nombre, bandeja, horas });
        } else {
          ausentes.push({ dni, nombre });
        }
      });

      // Construir resumen visual
      let html = `
        <div class="resumen-general">
          <p><strong>Encargado:</strong> ${encargado}</p>
          <p><strong>Empresa:</strong> ${empresa}</p>
          <p><strong>Fecha:</strong> ${fecha}</p>
          <p><strong>Producto:</strong> ${producto}</p>
        </div>
        <hr>
      `;

      if (asistentes.length > 0) {
        html += `<h4>🟢 Trabajadores que ASISTEN (${asistentes.length})</h4><ul>`;
        asistentes.forEach(t =>
          html += `<li>${t.nombre} — <strong>${t.bandeja}</strong> bandejas — <strong>${t.horas}</strong> horas</li>`
        );
        html += `</ul>`;
      }

      if (ausentes.length > 0) {
        html += `<h4>⚪ Trabajadores AUSENTES (${ausentes.length})</h4><ul>`;
        ausentes.forEach(t =>
          html += `<li>${t.nombre}</li>`
        );
        html += `</ul>`;
      }

      const resumen = document.getElementById("resumen-parte");
      const modal = document.getElementById("modal-resumen-parte");
      if (resumen && modal) {
        resumen.innerHTML = html;
        modal.style.display = "flex";
      }
    });
  }

  // Confirmar y guardar
  const btnConfirmarResumen = document.getElementById("btn-confirmar-resumen");
  if (btnConfirmarResumen) {
    btnConfirmarResumen.addEventListener("click", async () => {
      const encargado = document.getElementById("nombre_encargado")?.value.trim();
      const empresa = document.getElementById("empresa")?.value.trim();
      const fecha = document.getElementById("fecha")?.value.trim();
      const producto = document.getElementById("producto")?.value.trim();

      const trabajadores = [];
      document.querySelectorAll("tr[id^='fila_']").forEach(fila => {
        const dni = fila.id.replace("fila_", "");
        const checkAsistencia = fila.querySelector(".check-asistencia");
        const asistencia = checkAsistencia?.checked ? "si" : "no";

        const detalle = fila.nextElementSibling;
        const inputB = detalle?.querySelector(`input[name='Bandeja_${dni}']`);
        const inputH = detalle?.querySelector(`input[name='Horas_${dni}']`);
        const inputO = detalle?.querySelector(`input[name='Observaciones_${dni}']`);

        trabajadores.push({
          dni,
          asistencia,
          bandeja: inputB?.value || fila.dataset.bandejaFinal || "0",
          horas: inputH?.value || fila.dataset.horasFinal || "0",
          observaciones: inputO?.value || ""
        });
      });

      const formData = new FormData();
      formData.append("action", "guardar_parte_completo");
      formData.append("encargado", encargado || "");
      formData.append("empresa", empresa || "");
      formData.append("fecha", fecha || "");
      formData.append("producto", producto || "");
      formData.append("trabajadores", JSON.stringify(trabajadores));

      try {
        const res = await fetch("../controllers/asistencia_controller.php", {
          method: "POST",
          body: formData
        });

        if (res.ok) {
          mostrarBanner("✅ Parte de asistencia guardado correctamente.", "ok");
          const modal = document.getElementById("modal-resumen-parte");
          if (modal) modal.style.display = "none";

          // Reiniciar visualmente
          const nombreEnc = document.getElementById("nombre_encargado");
          if (nombreEnc) nombreEnc.value = "";
          const empresaEl = document.getElementById("empresa");
          if (empresaEl) empresaEl.value = "";
          const productoEl = document.getElementById("producto");
          if (productoEl) productoEl.value = "";

          const tbody = document.querySelector("#tabla_asistencia tbody");
          if (tbody) {
            tbody.innerHTML =
              '<tr class="placeholder-row"><td colspan="4" style="text-align:center;color:#888;">No hay trabajadores agregados.</td></tr>';
          }

          // 🔹 Limpiar datasets internos
          document.querySelectorAll("tr[id^='fila_']").forEach(fila => {
            delete fila.dataset.bandejasPendientes;
            delete fila.dataset.horasPendientes;
            delete fila.dataset.bandejaFinal;
            delete fila.dataset.horasFinal;
          });

        } else {
          mostrarBanner("⚠️ Error al guardar el parte.", "error");
        }
      } catch (error) {
        console.error(error);
        mostrarBanner("⚠️ Error inesperado al guardar el parte.", "error");
      }
    });
  }

  const btnCancelarResumen = document.getElementById("btn-cancelar-resumen");
  if (btnCancelarResumen) {
    btnCancelarResumen.addEventListener("click", () => {
      const modal = document.getElementById("modal-resumen-parte");
      if (modal) modal.style.display = "none";
    });
  }

  /* =====================================================
     🔁 SINCRONIZAR VALORES GLOBALES A CADA TRABAJADOR
  ===================================================== */
  function sincronizarValoresGlobales() {
    document.querySelectorAll("tr[id^='fila_']").forEach(fila => {
      const dni = fila.id.replace("fila_", "");
      const bandejasPend = fila.dataset.bandejasPendientes || "";
      const horasPend = fila.dataset.horasPendientes || "";

      // Buscar el detalle correspondiente
      const detalle = fila.nextElementSibling;
      if (detalle && detalle.classList.contains("fila-detalle")) {
        const inputB = detalle.querySelector(`input[name='Bandeja_${dni}']`);
        const inputH = detalle.querySelector(`input[name='Horas_${dni}']`);

        if (inputB && bandejasPend) inputB.value = bandejasPend;
        if (inputH && horasPend) inputH.value = horasPend;
      }

      // Guardar también en dataset los valores definitivos
      fila.dataset.bandejaFinal = bandejasPend || "0";
      fila.dataset.horasFinal = horasPend || "0";
    });
  }
});
