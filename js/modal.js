// Cerrar modal por id
function cerrarModal(id) {
  const m = document.getElementById(id);
  if (m) m.style.display = 'none';
}

// Cerrar haciendo click fuera (para todos los modales)
window.addEventListener("click", (e) => {
  document.querySelectorAll('.modal').forEach(modal => {
    if (e.target === modal) cerrarModal(modal.id);
  });
});
