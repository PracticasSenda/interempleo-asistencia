<?php
/**
 * Archivo: modales-personal.php
 * Descripción: Contiene todos los modales de confirmación
 * (baja, alta, y alta nueva) usados en gestionar-personal.php
 */
?>
<!-- =========================================================
🔻 MODAL DAR DE BAJA
========================================================= -->
<div id="modalBaja" class="modal" style="display:none">
  <div class="modal-contenido">
    <h3>Confirmar baja</h3>
    <p id="modalTexto"></p>
    <div class="modal-botones">
      <button id="btnConfirmar" class="btn btn-primary">Confirmar</button>
      <button id="btnCancelar" class="btn btn-secondary">Cancelar</button>
    </div>
  </div>
</div>

<!-- =========================================================
🔺 MODAL REACTIVAR (ALTA a inactivo)
========================================================= -->
<div id="modalAlta" class="modal" style="display:none">
  <div class="modal-contenido">
    <h3>Confirmar alta</h3>
    <p id="modalTextoAlta"></p>
    <div class="modal-botones">
      <button id="btnConfirmarAlta" class="btn btn-primary">Confirmar</button>
      <button id="btnCancelarAlta" class="btn btn-secondary">Cancelar</button>
    </div>
  </div>
</div>

<!-- =========================================================
🟢 MODAL ALTA NUEVA (Formulario)
========================================================= -->
<div id="modalAltaNueva" class="modal" style="display:none">
  <div class="modal-contenido">
    <h3>Confirmar alta</h3>
    <p id="modalTextoAltaNueva" style="font-size:0.95rem; line-height:1.5;"></p>
    <div class="modal-botones">
      <button id="btnConfirmarAltaNueva" class="btn btn-primary">Confirmar</button>
      <button id="btnCancelarAltaNueva" class="btn btn-secondary">Cancelar</button>
    </div>
  </div>
</div>
