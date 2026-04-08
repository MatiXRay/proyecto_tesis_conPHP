<?php
/**
 * BIALYSTOK BREWING CO — Reportes de Agua
 * Reemplaza: reportes_h2o.php
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }
require_once 'conexion.php';

$menu_activo = 'reportes_agua';
$searchTerm  = getStringParam('searchTerm', 'GET', 100);
$pagina      = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina  = 20;
$offset      = ($pagina - 1) * $por_pagina;
$like        = '%' . $searchTerm . '%';

try {
    $pdo = getPDO();

    $stmt_total = $pdo->prepare(
        "SELECT COUNT(*) FROM reportesagua WHERE origen LIKE ? OR laboratorio LIKE ? OR fecha LIKE ?"
    );
    $stmt_total->execute([$like, $like, $like]);
    $total = (int) $stmt_total->fetchColumn();
    $total_paginas = max(1, (int) ceil($total / $por_pagina));

    $stmt = $pdo->prepare(
        "SELECT * FROM reportesagua
         WHERE origen LIKE ? OR laboratorio LIKE ? OR fecha LIKE ?
         ORDER BY fecha DESC
         LIMIT $offset, $por_pagina"
    );
    $stmt->execute([$like, $like, $like]);
    $reportes = $stmt->fetchAll();

} catch (PDOException $ex) {
    error_log('[Bialystok reportes_h2o] ' . $ex->getMessage());
    $reportes = []; $total = $total_paginas = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reportes H₂O · Bialystok Brewing</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/bialy-design-system.css">
  <style>
    .mono { font-family:'DM Mono',monospace; font-size:.78rem; }
    .param-val { color:var(--text-amber); }
  </style>
</head>
<body>
<?php require 'menu.php'; ?>
<?php require 'info_user.php'; ?>

<div id="contenido" class="main-content">
  <div class="page-header fade-in">
    <div>
      <h1>Reportes H₂O</h1>
      <p class="page-subtitle"><?= $total ?> reporte<?= $total !== 1 ? 's' : '' ?> registrado<?= $total !== 1 ? 's' : '' ?></p>
    </div>
    <a href="anadir_reporte_agua" class="btn btn-primary">+ Nuevo reporte</a>
  </div>

  <div class="toolbar fade-in">
    <form action="reportes_agua" method="GET" style="display:contents">
      <div class="search-box">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--text-muted)"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" name="searchTerm" placeholder="Buscar por origen, laboratorio o fecha…" value="<?= e($searchTerm) ?>">
      </div>
      <button type="submit" class="btn btn-ghost btn-sm">Buscar</button>
      <?php if ($searchTerm): ?><a href="reportes_agua" class="btn btn-ghost btn-sm">✕ Limpiar</a><?php endif; ?>
    </form>
    <div class="toolbar-right">
      <?php if (isAdmin()): ?>
      <button class="btn btn-danger btn-sm" id="btnEliminar">Eliminar</button>
      <?php endif; ?>
      <button class="btn btn-secondary btn-sm" id="btnVerDetalle">Ver detalle</button>
    </div>
  </div>

  <div class="table-wrapper fade-in" style="overflow-x:auto">
    <table id="tabla-h2o" style="min-width:900px">
      <thead>
        <tr>
          <th style="width:30px"></th>
          <th>Fecha</th>
          <th>Origen</th>
          <th>Lab.</th>
          <th>pH</th>
          <th>Ca²⁺</th>
          <th>Mg²⁺</th>
          <th>Cl⁻</th>
          <th>SO₄²⁻</th>
          <th>Na⁺</th>
          <th>HCO₃⁻</th>
          <th>Dureza</th>
          <th>Alcalinidad</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($reportes): ?>
          <?php foreach ($reportes as $r): ?>
          <tr style="cursor:pointer" onclick="selRow(this)" ondblclick="verDetalle(<?= (int)$r['id'] ?>)">
            <td><input type="radio" name="sel" value="<?= (int)$r['id'] ?>" style="accent-color:var(--amber-400)"></td>
            <td class="mono"><?= e(date('d/m/Y', strtotime($r['fecha']))) ?></td>
            <td><?= e($r['origen'] ?? '—') ?></td>
            <td style="color:var(--text-muted);font-size:.8rem"><?= e($r['laboratorio'] ?? '—') ?></td>
            <td class="mono param-val"><?= e($r['ph'] ?? '—') ?></td>
            <td class="mono"><?= e($r['calcio'] ?? '—') ?></td>
            <td class="mono"><?= e($r['magnesio'] ?? '—') ?></td>
            <td class="mono"><?= e($r['cloruro'] ?? '—') ?></td>
            <td class="mono"><?= e($r['sulfato'] ?? '—') ?></td>
            <td class="mono"><?= e($r['sodio'] ?? '—') ?></td>
            <td class="mono"><?= e($r['bicarbonato'] ?? '—') ?></td>
            <td class="mono"><?= e($r['dureza'] ?? '—') ?></td>
            <td class="mono"><?= e($r['alcalinidad'] ?? '—') ?></td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="13" style="text-align:center;padding:2rem;color:var(--text-muted)">
            <?= $searchTerm ? 'Sin resultados para "'.e($searchTerm).'"' : 'No hay reportes de agua registrados.' ?>
          </td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total_paginas > 1): ?>
  <div class="pagination fade-in">
    <?php if ($pagina > 1): ?><a href="?pagina=<?= $pagina-1 ?>&searchTerm=<?= urlencode($searchTerm) ?>">← Anterior</a><?php endif; ?>
    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
      <a href="?pagina=<?= $i ?>&searchTerm=<?= urlencode($searchTerm) ?>" <?= $i===$pagina ? 'class="active"' : '' ?>><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($pagina < $total_paginas): ?><a href="?pagina=<?= $pagina+1 ?>&searchTerm=<?= urlencode($searchTerm) ?>">Siguiente →</a><?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<script>
function selRow(row) { row.querySelector('input[type="radio"]').checked = true; }
function getSelected() { return document.querySelector('#tabla-h2o input[type="radio"]:checked')?.value; }
function verDetalle(id) { window.location.href = 'detalle_reporte_agua?id=' + (id || getSelected()); }

document.getElementById('btnVerDetalle')?.addEventListener('click', function() {
  const id = getSelected();
  if (!id) { alert('Seleccioná un reporte primero.'); return; }
  verDetalle(id);
});

document.getElementById('btnEliminar')?.addEventListener('click', function() {
  const id = getSelected();
  if (!id) { alert('Seleccioná un reporte primero.'); return; }
  if (!confirm('¿Eliminar este reporte de agua?')) return;
  fetch('eliminar_registro', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ id, tabla: 'reportesagua', csrf_token: '<?= e(getCsrfToken()) ?>' })
  }).then(r => r.json()).then(d => {
    if (d.success) location.reload();
    else alert('Error: ' + (d.message || 'No se pudo eliminar.'));
  });
});

function loadContent(page) { window.location.href = page; }
</script>
</body>
</html>
