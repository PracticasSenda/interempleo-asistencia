<!-- 🔹 FOOTER GENERAL -->
<style>
  html, body {
    height: 100%;
    margin: 0;
    padding: 0;
  }

  body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background: #fff;
    font-family: Arial, sans-serif;
  }

  main {
    flex: 1; /* Empuja el footer hacia abajo */
  }

  footer {
    background: #fff;
    text-align: center;
    padding: 0.6rem 0;  /* compacto para móvil */
    color: #777;
    font-size: 0.75rem; /* texto pequeño y legible */
    border-top: 1px solid #eee;
  }
</style>

<footer>
  © <?= date('Y') ?> Interempleo · Todos los derechos reservados
</footer>
