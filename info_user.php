<?php
/**
 * BIALYSTOK BREWING CO — Topbar / Info usuario
 * Parcial incluido en todas las páginas protegidas.
 * Requiere auth.php ya cargado.
 */
?>
<header class="topbar">
  <div class="topbar-welcome" id="user-menu-trigger" style="cursor:pointer;user-select:none">
    Bienvenido, <strong><?= e($_SESSION['username'] ?? '') ?></strong>
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left:4px;opacity:.5"><polyline points="6 9 12 15 18 9"/></svg>
    <div class="dropdown-menu" id="user-dropdown">
      <a href="config_user" class="dropdown-item">Mi cuenta</a>
      <a href="logout" class="dropdown-item danger"
         onclick="return confirm('¿Cerrar sesión?')">Cerrar sesión</a>
    </div>
  </div>
</header>

<script>
(function() {
  const trigger  = document.getElementById('user-menu-trigger');
  const dropdown = document.getElementById('user-dropdown');
  if (!trigger || !dropdown) return;

  trigger.addEventListener('click', function(e) {
    e.stopPropagation();
    dropdown.classList.toggle('open');
  });

  document.addEventListener('click', function() {
    dropdown.classList.remove('open');
  });
})();
</script>
