<?php
/**
 * BIALYSTOK BREWING CO — Panel Sensorial (vista admin)
 * Reemplaza: panel_sensorial.php
 *
 * Correcciones:
 *  - Auth via auth.php
 *  - SQL injection: $offset interpolado → prepared statement
 *  - XSS: echo sin e() → e()
 *  - <style> duplicado (había dos <style> anidados) → eliminado
 *  - CSRF en el botón "Deshabilitar cata"
 *  - Diseño: nuevo sistema CSS
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }

require_once 'conexion.php';

$menu_activo = 'panel_sensorial';

$pagina_actual = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina    = 20;
$offset        = ($pagina_actual - 1) * $por_pagina;

try {
    $pdo = getPDO();

    $total = (int) $pdo->query("SELECT COUNT(*) FROM lotes_cerveza WHERE cata_habilitada = 1")->fetchColumn();
    $total_paginas = max(1, (int) ceil($total / $por_pagina));

    $stmt = $pdo->prepare(
        "SELECT lc.id, lc.numero_lote, lc.fecha_elaboracion, lc.comentarios, ec.nombre AS estilo
         FROM lotes_cerveza lc
         INNER JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
         WHERE lc.cata_habilitada = 1
         ORDER BY lc.fecha_elaboracion DESC
         LIMIT $offset, $por_pagina"
    );
    $stmt->execute();
    $lotes = $stmt->fetchAll();

    // Stats rápidas
    $total_catas = (int) $pdo->query("SELECT COUNT(*) FROM notas_cata")->fetchColumn();
    $total_tasters = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE rol_id = 3")->fetchColumn();

} catch (PDOException $ex) {
    error_log('[BRAUMEISTER panel_sensorial] ' . $ex->getMessage());
    $lotes = [];
    $total = $total_paginas = 0;
    $total_catas = $total_tasters = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel Sensorial · BRAUMEISTER</title>
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
      <h1>Panel Sensorial</h1>
      <p class="page-subtitle">Gestión de catas · <?= $total ?> lote<?= $total !== 1 ? 's' : '' ?> habilitado<?= $total !== 1 ? 's' : '' ?></p>
    </div>
    <a href="panel_cata" class="btn btn-secondary">Ir al panel de cata →</a>
  </div>

  <!-- Stats -->
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem" class="fade-in">
    <div class="stat-card">
      <div class="stat-value"><?= $total ?></div>
      <div class="stat-label">Lotes en cata</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= $total_catas ?></div>
      <div class="stat-label">Catas registradas</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= $total_tasters ?></div>
      <div class="stat-label">Tasters registrados</div>
    </div>
  </div>

  <!-- Toolbar -->
  <div class="toolbar fade-in">
    <h3 style="margin:0;color:var(--text-secondary);font-size:.85rem">Lotes habilitados para evaluación</h3>
    <div class="toolbar-right">
      <button class="btn btn-danger btn-sm" id="btnDeshabilitar">Deshabilitar cata</button>
      <button class="btn btn-secondary btn-sm" id="btnVerPlanilla">Ver planilla de cata</button>
    </div>
  </div>

  <!-- Tabla -->
  <div class="table-wrapper fade-in">
    <table id="tabla-sensorial">
      <thead>
        <tr>
          <th style="width:30px"></th>
          <th>N° Lote</th>
          <th>Fecha elaboración</th>
          <th>Estilo</th>
          <th>Comentarios</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($lotes): ?>
          <?php foreach ($lotes as $i => $lote): ?>
          <tr style="cursor:pointer" onclick="toggleCheck(this)">
            <td>
              <input type="checkbox" name="seleccion" value="<?= (int)$lote['id'] ?>"
                     style="accent-color:var(--amber-400)">
            </td>
            <td style="font-family:'DM Mono',monospace;font-weight:500">
              <?= e(strtoupper($lote['numero_lote'] ?? '')) ?>
            </td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem;color:var(--text-secondary)">
              <?= e(date('d/m/Y', strtotime($lote['fecha_elaboracion']))) ?>
            </td>
            <td><span class="badge <?= badgeEstilo($lote['estilo']) ?>"><?= e(strtoupper($lote['estilo'])) ?></span></td>
            <td style="color:var(--text-muted);font-size:.8rem"><?= e($lote['comentarios'] ?: '—') ?></td>
            <td>
              <a href="planilla_cata?id=<?= (int)$lote['id'] ?>&origen=panel_sensorial" class="btn btn-ghost btn-sm"
                 onclick="event.stopPropagation()">Evaluar</a>
              <a href="detalle_planilla?id=<?= (int)$lote['id'] ?>" class="btn btn-secondary btn-sm"
                 onclick="event.stopPropagation()">Ver evaluaciones</a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" style="text-align:center;padding:2rem;color:var(--text-muted)">
              No hay lotes habilitados para cata actualmente.
              <br><small>Habilitá un lote desde la sección <a href="configuracion">Configuración</a>.</small>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Paginación -->
  <?php if ($total_paginas > 1): ?>
  <div class="pagination fade-in">
    <?php if ($pagina_actual > 1): ?>
      <a href="?pagina=<?= $pagina_actual - 1 ?>">← Anterior</a>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
      <a href="?pagina=<?= $i ?>" <?= $i === $pagina_actual ? 'class="active"' : '' ?>><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($pagina_actual < $total_paginas): ?>
      <a href="?pagina=<?= $pagina_actual + 1 ?>">Siguiente →</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</div>

<script>
function toggleCheck(row) {
  const cb = row.querySelector('input[type="checkbox"]');
  if (cb) cb.checked = !cb.checked;
}

function getSeleccionados() {
  return [...document.querySelectorAll('#tabla-sensorial input[type="checkbox"]:checked')]
    .map(cb => parseInt(cb.value));
}

document.getElementById('btnVerPlanilla')?.addEventListener('click', function() {
  const ids = getSeleccionados();
  if (ids.length === 0) { alert('Seleccioná un lote primero.'); return; }
  window.location.href = 'planilla_cata?id=' + ids[0] + '&origen=panel_sensorial';
});

document.getElementById('btnDeshabilitar')?.addEventListener('click', function() {
  const ids = getSeleccionados();
  if (ids.length === 0) { alert('Seleccioná al menos un lote.'); return; }
  if (!confirm(`¿Deshabilitar la cata para ${ids.length} lote(s) seleccionado(s)?`)) return;

  fetch('actualizar_cata', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= e(getCsrfToken()) ?>' },
    body: JSON.stringify({ ids })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      alert(data.message || 'Cata deshabilitada.');
      window.location.reload();
    } else {
      alert('Error: ' + (data.message || 'No se pudo deshabilitar.'));
    }
  })
  .catch(() => alert('Error de red. Intentá de nuevo.'));
});

function loadContent(page) { window.location.href = page; }
</script>

</body>
</html>
