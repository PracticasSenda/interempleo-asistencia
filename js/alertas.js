document.addEventListener('DOMContentLoaded', () => {
  const banner = document.querySelector('.banner');
  if (!banner) return;

  // animación inicial
  banner.classList.add('auto-hide');

  // auto-ocultar solo éxito
  if (banner.classList.contains('ok')) {
    setTimeout(() => {
      banner.style.transition = 'opacity 0.5s, transform 0.5s';
      banner.style.opacity = '0';
      banner.style.transform = 'translateY(-8px)';
      setTimeout(() => banner.remove(), 500);
    }, 5000);
  }

  // cerrar manual ✖
  const closeBtn = banner.querySelector('.close-banner');
  if (closeBtn) {
    closeBtn.addEventListener('click', () => {
      banner.style.transition = 'opacity 0.3s, transform 0.3s';
      banner.style.opacity = '0';
      banner.style.transform = 'translateY(-8px)';
      setTimeout(() => banner.remove(), 300);
    });
  }

  // limpiar ?code & ?ok de la URL (evita que reaparezca tras F5)
  const url = new URL(window.location.href);
  if (url.searchParams.has('code') || url.searchParams.has('ok')) {
    url.searchParams.delete('code');
    url.searchParams.delete('ok');
    const newUrl = url.pathname + (url.searchParams.toString() ? '?' + url.searchParams.toString() : '');
    window.history.replaceState({}, document.title, newUrl);
  }
});

