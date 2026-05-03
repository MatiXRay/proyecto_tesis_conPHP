<?php
/**
 * BIALYSTOK BREWING CO — Variedades de Malta
 * Reemplaza: variedades_malta.php
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }
require_once 'conexion.php';

$menu_activo  = 'maltas';
$searchTerm   = getStringParam('searchTerm', 'GET', 100);
$pagina       = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina   = 20;
$offset       = ($pagina - 1) * $por_pagina;
$like         = '%' . $searchTerm . '%';

try {
    $pdo   = getPDO();
    $total = (int) $pdo->prepare("SELECT COUNT(*) FROM variedades_malta WHERE nombre LIKE ? OR marca LIKE ?")->execute([$like,$like]) ? $pdo->prepare("SELECT COUNT(*) FROM variedades_malta WHERE nombre LIKE ? OR marca LIKE ?")->execute([$like,$like]) : 0;

    $stmt_total = $pdo->prepare("SELECT COUNT(*) FROM variedades_malta WHERE nombre LIKE ? OR marca LIKE ?");
    $stmt_total->execute([$like, $like]);
    $total = (int) $stmt_total->fetchColumn();
    $total_paginas = max(1, (int) ceil($total / $por_pagina));

    $stmt = $pdo->prepare("SELECT id, nombre, marca FROM variedades_malta WHERE nombre LIKE ? OR marca LIKE ? ORDER BY nombre ASC LIMIT $offset, $por_pagina");
    $stmt->execute([$like, $like]);
    $maltas = $stmt->fetchAll();
} catch (PDOException $ex) {
    error_log('[BRAUMEISTER maltas] ' . $ex->getMessage());
    $maltas = []; $total = $total_paginas = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Maltas · BRAUMEISTER</title>
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
      <h1>Maltas</h1>
      <p class="page-subtitle"><?= $total ?> variedad<?= $total !== 1 ? 'es' : '' ?> registrada<?= $total !== 1 ? 's' : '' ?></p>
    </div>
    <button class="btn btn-primary" onclick="abrirModal()">+ Nueva malta</button>
  </div>

  <div class="toolbar fade-in">
    <form action="maltas" method="GET" style="display:contents">
      <div class="search-box">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--text-muted)"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" name="searchTerm" placeholder="Buscar por nombre o marca…" value="<?= e($searchTerm) ?>">
      </div>
      <button type="submit" class="btn btn-ghost btn-sm">Buscar</button>
      <?php if ($searchTerm): ?><a href="maltas" class="btn btn-ghost btn-sm">✕ Limpiar</a><?php endif; ?>
    </form>
    <div class="toolbar-right">
      <?php if (isAdmin()): ?>
      <button class="btn btn-danger btn-sm" id="btnEliminar">Eliminar</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="table-wrapper fade-in">
    <table id="tabla-maltas">
      <thead><tr><th style="width:30px"></th><th>Nombre</th><th>Marca</th></tr></thead>
      <tbody>
        <?php if ($maltas): ?>
          <?php foreach ($maltas as $m): ?>
          <tr style="cursor:pointer" onclick="selRow(this)">
            <td><input type="radio" name="sel" value="<?= (int)$m['id'] ?>" style="accent-color:var(--amber-400)"></td>
            <td><?= e(ucwords(strtolower($m['nombre']))) ?></td>
            <td style="color:var(--text-muted)"><?= e(ucwords(strtolower($m['marca']))) ?></td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="3" style="text-align:center;padding:2rem;color:var(--text-muted)">
            <?= $searchTerm ? 'Sin resultados para "' . e($searchTerm) . '"' : 'No hay maltas registradas.' ?>
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

<!-- Modal nueva malta -->
<div id="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:1000;align-items:center;justify-content:center">
  <div class="card" style="width:400px;position:relative">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem">
      <h2 style="margin:0;font-size:1rem">Nueva malta</h2>
      <button onclick="cerrarModal()" style="background:none;border:none;color:var(--text-muted);font-size:1.3rem;cursor:pointer">✕</button>
    </div>
    <div class="form-group">
      <label class="form-label">Nombre</label>
      <input type="text" id="inp-nombre" placeholder="Ej: Pale Ale" autocomplete="off">
    </div>
    <div class="form-group">
      <label class="form-label">Marca</label>
      <input type="text" id="inp-marca" placeholder="Ej: Maltería Pampa" autocomplete="off">
    </div>
    <div style="display:flex;gap:.75rem;margin-top:1rem">
      <button class="btn btn-primary" onclick="guardar(false)">Guardar</button>
      <button class="btn btn-secondary" onclick="guardar(true)">Guardar y agregar otro</button>
      <button class="btn btn-ghost" onclick="cerrarModal()">Cancelar</button>
    </div>
  </div>
</div>

<script>
function selRow(row) { row.querySelector('input[type="radio"]').checked = true; }

function getSelected() {
  const r = document.querySelector('#tabla-maltas input[type="radio"]:checked');
  return r ? r.value : null;
}

document.getElementById('btnEliminar')?.addEventListener('click', function() {
  const id = getSelected();
  if (!id) { alert('Seleccioná una malta primero.'); return; }
  if (!confirm('¿Eliminar esta malta?')) return;
  fetch('eliminar_registro', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ id, tabla: 'variedades_malta', csrf_token: '<?= e(getCsrfToken()) ?>' })
  }).then(r => r.json()).then(d => {
    if (d.success) window.location.reload();
    else alert('Error: ' + (d.message || 'No se pudo eliminar.'));
  }).catch(() => alert('Error de red.'));
});

function abrirModal() {
  document.getElementById('modal').style.display = 'flex';
  document.getElementById('inp-nombre').focus();
}
function cerrarModal() { document.getElementById('modal').style.display = 'none'; }

function guardar(continuar) {
  const nombre = document.getElementById('inp-nombre').value.trim();
  const marca  = document.getElementById('inp-marca').value.trim();
  if (!nombre || !marca) { alert('Completá nombre y marca.'); return; }

  fetch('anadir_registro', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ tabla: 'variedades_malta', nombre, marca, csrf_token: '<?= e(getCsrfToken()) ?>' })
  }).then(r => r.json()).then(d => {
    if (d.success) {
      if (continuar) {
        document.getElementById('inp-nombre').value = '';
        document.getElementById('inp-marca').value = '';
        document.getElementById('inp-nombre').focus();
      } else {
        cerrarModal();
        window.location.reload();
      }
    } else alert('Error: ' + (d.message || 'No se pudo guardar.'));
  }).catch(() => alert('Error de red.'));
}

function loadContent(page) { window.location.href = page; }
</script>
</body>
</html>
