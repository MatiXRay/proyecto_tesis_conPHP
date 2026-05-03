<?php
/**
 * BIALYSTOK BREWING CO — Fermentadores
 * Reemplaza: fermentadores.php
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }
require_once 'conexion.php';

$menu_activo = 'fermentadores';
$searchTerm  = getStringParam('searchTerm', 'GET', 100);
$pagina      = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina  = 20;
$offset      = ($pagina - 1) * $por_pagina;
$like        = '%' . $searchTerm . '%';

function requiereLimpieza(?string $fecha): bool {
    if (!$fecha || $fecha === '0000-00-00') return true;
    try { return (new DateTime())->diff(new DateTime($fecha))->days > 30; }
    catch (Exception $e) { return true; }
}

try {
    $pdo = getPDO();

    $stmt_total = $pdo->prepare("SELECT COUNT(*) FROM fermentadores WHERE nombre LIKE ? OR capacidad LIKE ?");
    $stmt_total->execute([$like, $like]);
    $total = (int) $stmt_total->fetchColumn();
    $total_paginas = max(1, (int) ceil($total / $por_pagina));

    $stmt = $pdo->prepare(
        "SELECT id, nombre, capacidad,
                limp_alcalina_date, limp_acida_date, limp_oxidativa_date, limp_exterior_date
         FROM fermentadores
         WHERE nombre LIKE ? OR capacidad LIKE ?
         ORDER BY nombre ASC
         LIMIT $offset, $por_pagina"
    );
    $stmt->execute([$like, $like]);
    $fvs = $stmt->fetchAll();

} catch (PDOException $ex) {
    error_log('[BRAUMEISTER fermentadores] ' . $ex->getMessage());
    $fvs = []; $total = $total_paginas = 0;
}

function fmtFecha(?string $f): string {
    if (!$f || $f === '0000-00-00') return '—';
    return date('d/m/Y', strtotime($f));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fermentadores · BRAUMEISTER</title>
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
      <h1>Fermentadores</h1>
      <p class="page-subtitle"><?= $total ?> fermentador<?= $total !== 1 ? 'es' : '' ?> registrado<?= $total !== 1 ? 's' : '' ?></p>
    </div>
    <?php if (isAdmin()): ?>
    <button class="btn btn-primary" onclick="abrirModal()">+ Nuevo fermentador</button>
    <?php endif; ?>
  </div>

  <div class="toolbar fade-in">
    <form action="fermentadores" method="GET" style="display:contents">
      <div class="search-box">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--text-muted)"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" name="searchTerm" placeholder="Buscar…" value="<?= e($searchTerm) ?>">
      </div>
      <button type="submit" class="btn btn-ghost btn-sm">Buscar</button>
      <?php if ($searchTerm): ?><a href="fermentadores" class="btn btn-ghost btn-sm">✕ Limpiar</a><?php endif; ?>
    </form>
    <div class="toolbar-right">
      <?php if (isAdmin()): ?>
      <button class="btn btn-ghost btn-sm" id="btnLimpiar">Registrar limpieza</button>
      <button class="btn btn-danger btn-sm" id="btnEliminar">Eliminar</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="table-wrapper fade-in">
    <table id="tabla-fv">
      <thead>
        <tr>
          <th style="width:30px"></th>
          <th>Nombre</th>
          <th>Capacidad</th>
          <th>Últ. Alcalina</th>
          <th>Últ. Ácida</th>
          <th>Últ. Oxidativa</th>
          <th>Últ. Exterior</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($fvs): ?>
          <?php foreach ($fvs as $fv):
            $alc = requiereLimpieza($fv['limp_alcalina_date']);
            $aci = requiereLimpieza($fv['limp_acida_date']);
            $oxi = requiereLimpieza($fv['limp_oxidativa_date']);
            $ext = requiereLimpieza($fv['limp_exterior_date']);
          ?>
          <tr style="cursor:pointer" onclick="selRow(this)">
            <td><input type="radio" name="sel" value="<?= (int)$fv['id'] ?>" style="accent-color:var(--amber-400)"></td>
            <td style="font-weight:500"><?= e($fv['nombre']) ?></td>
            <td style="font-family:'DM Mono',monospace;color:var(--text-muted)"><?= e($fv['capacidad'] ?? '—') ?></td>
            <td class="<?= $alc ? 'estado-alerta' : 'estado-ok' ?>"><?= fmtFecha($fv['limp_alcalina_date']) ?></td>
            <td class="<?= $aci ? 'estado-alerta' : 'estado-ok' ?>"><?= fmtFecha($fv['limp_acida_date']) ?></td>
            <td class="<?= $oxi ? 'estado-alerta' : 'estado-ok' ?>"><?= fmtFecha($fv['limp_oxidativa_date']) ?></td>
            <td class="<?= $ext ? 'estado-alerta' : 'estado-ok' ?>"><?= fmtFecha($fv['limp_exterior_date']) ?></td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted)">No hay fermentadores registrados.</td></tr>
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

<!-- Modal nuevo fermentador -->
<div id="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:1000;align-items:center;justify-content:center">
  <div class="card" style="width:400px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem">
      <h2 style="margin:0;font-size:1rem">Nuevo fermentador</h2>
      <button onclick="cerrarModal()" style="background:none;border:none;color:var(--text-muted);font-size:1.3rem;cursor:pointer">✕</button>
    </div>
    <div class="form-group">
      <label class="form-label">Nombre</label>
      <input type="text" id="inp-nombre" placeholder="Ej: Fv10" autocomplete="off">
    </div>
    <div class="form-group">
      <label class="form-label">Capacidad</label>
      <input type="text" id="inp-cap" placeholder="Ej: 500 lts" autocomplete="off">
    </div>
    <div style="display:flex;gap:.75rem;margin-top:1rem">
      <button class="btn btn-primary" onclick="guardar()">Guardar</button>
      <button class="btn btn-ghost" onclick="cerrarModal()">Cancelar</button>
    </div>
  </div>
</div>

<!-- Modal limpieza -->
<div id="modal-limp" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:1000;align-items:center;justify-content:center">
  <div class="card" style="width:420px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem">
      <h2 style="margin:0;font-size:1rem">Registrar limpieza</h2>
      <button onclick="cerrarLimp()" style="background:none;border:none;color:var(--text-muted);font-size:1.3rem;cursor:pointer">✕</button>
    </div>
    <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:1rem">Marcá los tipos de limpieza realizados hoy:</p>
    <div style="display:flex;flex-direction:column;gap:.6rem;margin-bottom:1.25rem">
      <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-weight:400">
        <input type="checkbox" id="chk-alc" style="accent-color:var(--amber-400)"> Alcalina
      </label>
      <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-weight:400">
        <input type="checkbox" id="chk-aci" style="accent-color:var(--amber-400)"> Ácida
      </label>
      <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-weight:400">
        <input type="checkbox" id="chk-oxi" style="accent-color:var(--amber-400)"> Oxidativa
      </label>
      <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-weight:400">
        <input type="checkbox" id="chk-ext" style="accent-color:var(--amber-400)"> Exterior
      </label>
    </div>
    <div style="display:flex;gap:.75rem">
      <button class="btn btn-primary" onclick="guardarLimpieza()">Registrar</button>
      <button class="btn btn-ghost" onclick="cerrarLimp()">Cancelar</button>
    </div>
  </div>
</div>

<script>
function selRow(row) { row.querySelector('input[type="radio"]').checked = true; }
function getSelected() { return document.querySelector('#tabla-fv input[type="radio"]:checked')?.value; }

// Nuevo fermentador
function abrirModal() { document.getElementById('modal').style.display='flex'; document.getElementById('inp-nombre').focus(); }
function cerrarModal() { document.getElementById('modal').style.display='none'; }

function guardar() {
  const nombre = document.getElementById('inp-nombre').value.trim();
  const cap    = document.getElementById('inp-cap').value.trim();
  if (!nombre) { alert('El nombre es obligatorio.'); return; }
  fetch('anadir_registro', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ tabla:'fermentadores', nombre, marca:cap, csrf_token:'<?= e(getCsrfToken()) ?>' })
  }).then(r=>r.json()).then(d=>{ if(d.success){ cerrarModal(); location.reload(); } else alert('Error: '+(d.message||'No se pudo guardar.')); });
}

// Limpieza
function cerrarLimp() { document.getElementById('modal-limp').style.display='none'; }

document.getElementById('btnLimpiar')?.addEventListener('click', function() {
  const id = getSelected();
  if (!id) { alert('Seleccioná un fermentador primero.'); return; }
  ['alc','aci','oxi','ext'].forEach(t => document.getElementById('chk-'+t).checked = false);
  document.getElementById('modal-limp').style.display='flex';
});

function guardarLimpieza() {
  const id = getSelected();
  if (!id) return;
  fetch('actualizar_fermentador', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({
      id: +id,
      limpAlcalina:  document.getElementById('chk-alc').checked,
      limpAcida:     document.getElementById('chk-aci').checked,
      limpOxidativa: document.getElementById('chk-oxi').checked,
      limpExterior:  document.getElementById('chk-ext').checked,
      csrf_token: '<?= e(getCsrfToken()) ?>'
    })
  }).then(r=>r.json()).then(d=>{ if(d.success){ cerrarLimp(); location.reload(); } else alert('Error al guardar.'); });
}

// Eliminar
document.getElementById('btnEliminar')?.addEventListener('click', function() {
  const id = getSelected();
  if (!id) { alert('Seleccioná un fermentador primero.'); return; }
  if (!confirm('¿Eliminar este fermentador?')) return;
  fetch('eliminar_registro', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ id, tabla:'fermentadores', csrf_token:'<?= e(getCsrfToken()) ?>' })
  }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert('Error: '+(d.message||'No se pudo eliminar.')); });
});

function loadContent(page) { window.location.href = page; }
</script>
</body>
</html>
