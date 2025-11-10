document.addEventListener('DOMContentLoaded', async () => {
  const input = document.getElementById('fecha_buscar');
  if (!input) return;

  try {
    // Obtener fechas con partes desde el servidor
    const response = await fetch('../controllers/obtener_partes_fechas.php');
    const fechasPartes = await response.json();

    // Inicializar Flatpickr
    flatpickr(input, {
      dateFormat: "Y-m-d",
      locale: "es",
      disableMobile: false, // usa Flatpickr incluso en móviles
      defaultDate: new Date(),
      onDayCreate: (dObj, dStr, fp, dayElem) => {
        const fechaISO = dayElem.dateObj.toISOString().split('T')[0];
        if (fechasPartes[fechaISO]) {
          const indicador = document.createElement('span');
          indicador.textContent = fechasPartes[fechaISO] > 1 
            ? fechasPartes[fechaISO]
            : '•';
          indicador.className = 'marcador-partes';
          dayElem.classList.add('has-event');
          dayElem.appendChild(indicador);
        }
      },
      onOpen: () => {
        document.body.style.overflow = 'hidden'; // evita desplazamiento al abrir
      },
      onClose: () => {
        document.body.style.overflow = ''; // restaura desplazamiento
      }
    });
  } catch (error) {
    console.error('Error al cargar fechas de partes:', error);
  }
});
