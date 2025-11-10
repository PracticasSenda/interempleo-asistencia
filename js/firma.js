document.addEventListener("DOMContentLoaded", function () {
  const canvas = document.getElementById("canvasFirma");
  const inputBase64 = document.getElementById("firmaBase64");
  const btnBorrar = document.getElementById("btnBorrarFirma");
  const btnGuardar = document.getElementById("btnGuardarParte");

  // Verifica que los elementos existan antes de inicializar
  if (!canvas || !inputBase64 || !btnBorrar || !btnGuardar || !window.SignaturePad) {
    console.warn("Faltan elementos o la librería SignaturePad no está cargada.");
    return;
  }

  // 🔹 Ajustar el tamaño del canvas para pantallas móviles y retina
  function resizeCanvas() {
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const width = canvas.clientWidth || canvas.offsetWidth || 400;
    const height = 160;

    canvas.width = width * ratio;
    canvas.height = height * ratio;

    const ctx = canvas.getContext("2d");
    ctx.scale(ratio, ratio);

    canvas.style.width = width + "px";
    canvas.style.height = height + "px";
  }

  resizeCanvas();
  window.addEventListener("resize", resizeCanvas);

  // 🔹 Inicializar SignaturePad
  const firmaPad = new SignaturePad(canvas, {
    backgroundColor: "rgb(255,255,255)",
    penColor: "black"
  });

  // 🔹 Función que guarda la firma o desactiva el botón si está vacía
  function actualizarEstado() {
    if (!firmaPad.isEmpty()) {
      inputBase64.value = firmaPad.toDataURL("image/png");
      btnGuardar.disabled = false;
    } else {
      inputBase64.value = "";
      btnGuardar.disabled = true;
    }
  }

  // 🔹 Detectar cuando termina de firmar
  canvas.addEventListener("mouseup", actualizarEstado);
  canvas.addEventListener("touchend", actualizarEstado);
  canvas.addEventListener("mouseleave", actualizarEstado);

  // 🔹 Botón para borrar la firma
  btnBorrar.addEventListener("click", () => {
    firmaPad.clear();
    actualizarEstado();
  });

  // Inicialmente desactivado
  actualizarEstado();
});
