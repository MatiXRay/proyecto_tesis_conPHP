<?php
/**
 * BIALYSTOK BREWING CO — Recetas / Estilos
 * Reemplaza: recetas.php
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }
require_once 'conexion.php';

$menu_activo = 'recetas';
$searchTerm  = getStringParam('searchTerm', 'GET', 100);
$pagina      = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina  = 20;
$offset      = ($pagina - 1) * $por_pagina;
$like        = '%' . $searchTerm . '%';

try {
    $pdo = getPDO();

    $stmt_total = $pdo->prepare(
        "SELECT COUNT(*) FROM recetas_estilos re
         INNER JOIN estilos_cerveza ec ON re.estilo_id = ec.id
         WHERE ec.nombre LIKE ? OR ec.descripcion LIKE ?"
    );
    $stmt_total->execute([$like, $like]);
    $total = (int) $stmt_total->fetchColumn();
    $total_paginas = max(1, (int) ceil($total / $por_pagina));

    $stmt = $pdo->prepare(
        "SELECT ec.id AS estilo_id, ec.nombre, ec.descripcion,
                re.og, re.fg, re.ibu, re.abv, re.carb_level,
                ec.duracion_dias,
                COUNT(lc.id) AS total_lotes
         FROM recetas_estilos re
         INNER JOIN estilos_cerveza ec ON re.estilo_id = ec.id
         LEFT JOIN lotes_cerveza lc ON lc.estilo_id = ec.id
         WHERE ec.nombre LIKE ? OR ec.descripcion LIKE ?
         GROUP BY ec.id, ec.nombre, ec.descripcion, re.og, re.fg, re.ibu, re.abv, re.carb_level, ec.duracion_dias
         ORDER BY ec.nombre ASC
         LIMIT $offset, $por_pagina"
    );
    $stmt->execute([$like, $like]);
    $recetas = $stmt->fetchAll();

} catch (PDOException $ex) {
    error_log('[Bialystok recetas] ' . $ex->getMessage());
    $recetas = []; $total = $total_paginas = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recetas · Bialystok Brewing</title>
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
      <h1>Recetas</h1>
      <p class="page-subtitle"><?= $total ?> estilo<?= $total !== 1 ? 's' : '' ?> registrado<?= $total !== 1 ? 's' : '' ?></p>
    </div>
    <a href="anadir_receta" class="btn btn-primary">+ Nueva receta</a>
  </div>

  <div class="toolbar fade-in">
    <form action="recetas" method="GET" style="display:contents">
      <div class="search-box">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--text-muted)"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" name="searchTerm" placeholder="Buscar por nombre o descripción…" value="<?= e($searchTerm) ?>">
      </div>
      <button type="submit" class="btn btn-ghost btn-sm">Buscar</button>
      <?php if ($searchTerm): ?><a href="recetas" class="btn btn-ghost btn-sm">✕ Limpiar</a><?php endif; ?>
    </form>
    <div class="toolbar-right">
      <?php if (isAdmin()): ?>
      <button class="btn btn-danger btn-sm" id="btnEliminar">Eliminar</button>
      <?php endif; ?>
      <button class="btn btn-secondary" id="btnVerReceta">Ver receta</button>
    </div>
  </div>

  <div class="table-wrapper fade-in">
    <table id="tabla-recetas">
      <thead>
        <tr>
          <th style="width:30px"></th>
          <th>Estilo</th>
          <th>Descripción</th>
          <th>OG</th><th>FG</th><th>IBU</th><th>ABV</th>
          <th>Días est.</th>
          <th>Lotes</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($recetas): ?>
          <?php foreach ($recetas as $r): ?>
          <tr style="cursor:pointer" onclick="selRow(this)" ondblclick="verReceta(<?= (int)$r['estilo_id'] ?>)">
            <td><input type="radio" name="sel" value="<?= (int)$r['estilo_id'] ?>" style="accent-color:var(--amber-400)"></td>
            <td style="font-weight:500"><?= e($r['nombre']) ?></td>
            <td style="color:var(--text-muted);font-size:.82rem;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
              <?= e($r['descripcion'] ?: '—') ?>
            </td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($r['og'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($r['fg'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($r['ibu'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= $r['abv'] ? e($r['abv']).'%' : '—' ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem;color:var(--text-muted)">
              <?= $r['duracion_dias'] ? e($r['duracion_dias']).' días' : '—' ?>
            </td>
            <td>
              <?php if ($r['total_lotes'] > 0): ?>
                <span class="badge badge-amber"><?= (int)$r['total_lotes'] ?></span>
              <?php else: ?>
                <span style="color:var(--text-muted);font-size:.78rem">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="9" style="text-align:center;padding:2rem;color:var(--text-muted)">
            <?= $searchTerm ? 'Sin resultados para "'.e($searchTerm).'"' : 'No hay recetas registradas.' ?>
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
function getSelected() { return document.querySelector('#tabla-recetas input[type="radio"]:checked')?.value; }
function verReceta(id) { window.location.href = 'detalle_receta?id_receta=' + (id || getSelected()); }

document.getElementById('btnVerReceta')?.addEventListener('click', function() {
  const id = getSelected();
  if (!id) { alert('Seleccioná una receta primero.'); return; }
  verReceta(id);
});

document.getElementById('btnEliminar')?.addEventListener('click', function() {
  const id = getSelected();
  if (!id) { alert('Seleccioná una receta primero.'); return; }
  if (!confirm('¿Eliminar esta receta? Solo es posible si no tiene lotes asociados.')) return;
  fetch('eliminar_registro', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ id, tabla: 'receta', csrf_token: '<?= e(getCsrfToken()) ?>' })
  }).then(r => r.json()).then(d => {
    if (d.success) window.location.reload();
    else alert('Error: ' + (d.message || 'No se pudo eliminar.'));
  }).catch(() => alert('Error de red.'));
});

function loadContent(page) { window.location.href = page; }
</script>
</body>
</html>
