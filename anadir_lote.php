<?php
/**
 * BIALYSTOK BREWING CO — Añadir nuevo lote (paso 1)
 * Reemplaza: anadir_lote.php
 *
 * Correcciones:
 *  - Auth via auth.php
 *  - XSS: options del select escapadas con e()
 *  - Token CSRF en el formulario
 *  - Diseño: nuevo sistema CSS
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }

require_once 'conexion.php';

$menu_activo = 'lotes';

// Cargar estilos disponibles
try {
    $estilos = getPDO()->query(
        "SELECT id, nombre FROM estilos_cerveza ORDER BY nombre"
    )->fetchAll();
} catch (PDOException $ex) {
    error_log('[Bialystok anadir_lote] ' . $ex->getMessage());
    $estilos = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nuevo lote · Bialystok Brewing</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/bialy-design-system.css">
</head>
<body>

<?php require 'menu.php'; ?>
<?php require 'info_user.php'; ?>

<div id="contenido" class="main-content">

  <div class="page-header fade-in">
    <div>
      <h1>Nuevo lote</h1>
      <p class="page-subtitle">Paso 1 de 2 · Datos básicos</p>
    </div>
    <a href="lotes" class="btn btn-ghost">← Volver a lotes</a>
  </div>

  <div style="max-width:520px" class="fade-in">
    <div class="card">
      <form action="guardar_lote" method="POST">
        <?= csrfField() ?>

        <div class="form-group">
          <label class="form-label" for="nombre">Número / nombre de lote</label>
          <input type="text" id="nombre" name="nombre"
                 placeholder="Ej: BCO-025" required
                 style="font-family:'DM Mono',monospace">
        </div>

        <div class="form-group">
          <label class="form-label" for="estilo">Estilo de cerveza</label>
          <select id="estilo" name="estilo" required>
            <option value="" disabled selected>Seleccioná un estilo…</option>
            <?php if ($estilos): ?>
              <?php foreach ($estilos as $est): ?>
                <option value="<?= (int)$est['id'] ?>"><?= e($est['nombre']) ?></option>
              <?php endforeach; ?>
            <?php else: ?>
              <option value="" disabled>No hay estilos registrados</option>
            <?php endif; ?>
          </select>
          <span style="font-size:.75rem;color:var(--text-muted);margin-top:.3rem;display:block">
            ¿No está el estilo? <a href="configuracion">Agregar en Configuración</a>
          </span>
        </div>

        <div class="form-group">
          <label class="form-label" for="fecha">Fecha de elaboración</label>
          <input type="date" id="fecha" name="fecha"
                 value="<?= date('Y-m-d') ?>" required
                 style="width:auto;min-width:200px">
        </div>

        <div style="display:flex;gap:.75rem;margin-top:1.5rem">
          <button type="submit" class="btn btn-primary">Continuar →</button>
          <a href="lotes" class="btn btn-ghost">Cancelar</a>
        </div>
      </form>
    </div>
  </div>

</div>

<script>
function loadContent(page) { window.location.href = page; }
</script>
</body>
</html>
