<?php
/**
 * BIALYSTOK BREWING CO — Listado de Lotes
 * Reemplaza: inicioListado.php / lotes (ruta)
 *
 * Correcciones:
 *  - Credenciales hardcodeadas (rocko/Pepito11!) → getPDO()
 *  - SQL injection: $searchTerm directo en LIKE → prepared statement con binding
 *  - $orden interpolado de $_GET → whitelist validada
 *  - XSS: todos los echo usan e()
 *  - Diseño: nuevo sistema CSS
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }

require_once 'conexion.php';

$menu_activo = 'lotes';

// ── Parámetros de entrada validados ──────────────────────────────────────────
$searchTerm    = getStringParam('searchTerm', 'GET', 100);
$orden         = (isset($_GET['orden']) && $_GET['orden'] === 'asc') ? 'ASC' : 'DESC';
$nuevo_orden   = ($orden === 'ASC') ? 'desc' : 'asc';
$pagina_actual = max(1, (int) ($_GET['pagina'] ?? 1));
$por_pagina    = 20;
$offset        = ($pagina_actual - 1) * $por_pagina;

// ── Queries con prepared statements ──────────────────────────────────────────
try {
    $pdo   = getPDO();
    $like  = '%' . $searchTerm . '%';

    $stmt_total = $pdo->prepare(
        "SELECT COUNT(*) FROM lotes_cerveza lc
         INNER JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
         WHERE lc.comentarios LIKE ? OR ec.nombre LIKE ? OR lc.numero_lote LIKE ?"
    );
    $stmt_total->execute([$like, $like, $like]);
    $total_registros = (int) $stmt_total->fetchColumn();
    $total_paginas   = max(1, (int) ceil($total_registros / $por_pagina));

    $stmt = $pdo->prepare(
        "SELECT lc.id, lc.numero_lote, lc.fecha_elaboracion, lc.comentarios,
                lc.og, lc.fg, lc.abv, lc.ibu, ec.nombre AS estilo
         FROM lotes_cerveza lc
         INNER JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
         WHERE lc.comentarios LIKE ? OR ec.nombre LIKE ? OR lc.numero_lote LIKE ?
         ORDER BY lc.fecha_elaboracion $orden
         LIMIT $offset, $por_pagina"
    );
    $stmt->execute([$like, $like, $like]);
    $lotes = $stmt->fetchAll();

} catch (PDOException $ex) {
    error_log('[BRAUMEISTER lotes] ' . $ex->getMessage());
    $lotes = [];
    $total_registros = $total_paginas = 0;
}

// URL base para paginación
function urlPaginacion(int $p, string $search, string $orden): string {
    return 'lotes?' . http_build_query(['pagina' => $p, 'searchTerm' => $search, 'orden' => $orden]);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lotes · BRAUMEISTER</title>
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
      <h1>Lotes</h1>
      <p class="page-subtitle">
        <?= $total_registros ?> lote<?= $total_registros !== 1 ? 's' : '' ?> registrado<?= $total_registros !== 1 ? 's' : '' ?>
        <?= $searchTerm ? ' · Filtrado por "<strong>' . e($searchTerm) . '</strong>"' : '' ?>
      </p>
    </div>
    <button class="btn btn-primary" onclick="window.location.href='anadir_lote'">+ Nuevo lote</button>
  </div>

  <!-- ── Toolbar ───────────────────────────────────────────────────────────── -->
  <div class="toolbar fade-in">
    <form action="lotes" method="GET" id="searchForm" style="display:contents">
      <div class="search-box">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--text-muted)"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
        <input type="text" name="searchTerm" placeholder="Buscar por estilo, lote o comentario…"
               value="<?= e($searchTerm) ?>" id="searchInput">
      </div>
      <input type="hidden" name="orden" value="<?= e(strtolower($orden)) ?>">
      <button type="submit" class="btn btn-ghost btn-sm">Buscar</button>
      <?php if ($searchTerm): ?>
        <a href="lotes" class="btn btn-ghost btn-sm">✕ Limpiar</a>
      <?php endif; ?>
    </form>

    <div class="toolbar-right">
      <a href="lotes?searchTerm=<?= urlencode($searchTerm) ?>&orden=<?= e($nuevo_orden) ?>" class="btn btn-ghost btn-sm">
        <?= $orden === 'DESC' ? '↑ Más antiguos' : '↓ Más recientes' ?>
      </a>
      <button class="btn btn-danger btn-sm" id="btnEliminar">Eliminar</button>
      <button class="btn btn-ghost btn-sm" id="btnEditar">✎ Editar</button>
      <button class="btn btn-ghost btn-sm" id="btnComparar">⇄ Comparar fermentación</button>
      <button class="btn btn-secondary btn-sm" id="btnVerNotas">Notas de cata</button>
      <button class="btn btn-secondary btn-sm" id="btnVerDetalles">Ver detalles</button>
    </div>
  </div>

  <!-- ── Tabla ─────────────────────────────────────────────────────────────── -->
  <div class="table-wrapper fade-in">
    <table id="tabla-lotes">
      <thead>
        <tr>
          <th style="width:30px"></th>
          <th style="width:30px" title="Seleccionar para comparar">⇄</th>
          <th>N° Lote</th>
          <th>
            <a href="lotes?searchTerm=<?= urlencode($searchTerm) ?>&orden=<?= e($nuevo_orden) ?>"
               style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:.3rem">
              Fecha <?= $orden === 'DESC' ? '↓' : '↑' ?>
            </a>
          </th>
          <th>Estilo</th>
          <th>OG</th>
          <th>FG</th>
          <th>ABV</th>
          <th>IBU</th>
          <th>Comentarios</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($lotes): ?>
          <?php foreach ($lotes as $i => $lote): ?>
          <tr style="cursor:pointer" onclick="seleccionarFila(this)">
            <td>
              <input type="radio" name="lote_sel" value="<?= (int)$lote['id'] ?>"
                     style="accent-color:var(--amber-400)"
                     <?= $i === 0 ? 'checked' : '' ?>>
            </td>
            <td onclick="event.stopPropagation()">
              <input type="checkbox" class="cmp-check" value="<?= (int)$lote['id'] ?>"
                     style="accent-color:#4a90d9"
                     onchange="validarComparar(this)">
            </td>
            <td style="font-family:'DM Mono',monospace;font-size:.85rem;font-weight:500">
              <?= e(strtoupper($lote['numero_lote'] ?? '—')) ?>
            </td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem;color:var(--text-secondary);white-space:nowrap">
              <?= e(date('d/m/Y', strtotime($lote['fecha_elaboracion']))) ?>
            </td>
            <td><span class="badge <?= badgeEstilo($lote['estilo']) ?>"><?= e(strtoupper($lote['estilo'])) ?></span></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($lote['og'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($lote['fg'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= $lote['abv'] ? e($lote['abv']) . '%' : '—' ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($lote['ibu'] ?? '—') ?></td>
            <td style="color:var(--text-muted);font-size:.8rem;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
              <?= e($lote['comentarios'] ?: '—') ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="9" style="text-align:center;padding:2rem;color:var(--text-muted)">
              <?= $searchTerm ? 'Sin resultados para "' . e($searchTerm) . '"' : 'No hay lotes registrados aún.' ?>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- ── Paginación ────────────────────────────────────────────────────────── -->
  <?php if ($total_paginas > 1): ?>
  <div class="pagination fade-in">
    <?php if ($pagina_actual > 1): ?>
      <a href="<?= e(urlPaginacion($pagina_actual - 1, $searchTerm, strtolower($orden))) ?>">← Anterior</a>
    <?php endif; ?>

    <?php
    // Mostrar máximo 7 páginas centradas en la actual
    $desde = max(1, $pagina_actual - 3);
    $hasta = min($total_paginas, $pagina_actual + 3);
    if ($desde > 1): ?><span>…</span><?php endif;
    for ($i = $desde; $i <= $hasta; $i++):
    ?>
      <a href="<?= e(urlPaginacion($i, $searchTerm, strtolower($orden))) ?>"
         <?= $i === $pagina_actual ? 'class="active"' : '' ?>><?= $i ?></a>
    <?php endfor;
    if ($hasta < $total_paginas): ?><span>…</span><?php endif; ?>

    <?php if ($pagina_actual < $total_paginas): ?>
      <a href="<?= e(urlPaginacion($pagina_actual + 1, $searchTerm, strtolower($orden))) ?>">Siguiente →</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>

</div><!-- /contenido -->

<script>
function seleccionarFila(row) {
  document.querySelectorAll('#tabla-lotes tbody tr').forEach(r => r.style.background = '');
  const radio = row.querySelector('input[type="radio"]');
  if (radio) { radio.checked = true; }
}

function validarComparar(cb) {
  const checked = [...document.querySelectorAll("#tabla-lotes input[type=\"checkbox\"].cmp-check:checked")];
  if (checked.length > 2) { cb.checked = false; alert("Solo podés seleccionar 2 lotes para comparar."); }
}

function getLotesSeleccionados() {
  return [...document.querySelectorAll("#tabla-lotes input[type=\"checkbox\"].cmp-check:checked")].map(c => c.value);
}

function getLoteSeleccionado() {
  const radio = document.querySelector('#tabla-lotes input[type="radio"]:checked');
  return radio ? radio.value : null;
}

document.getElementById('btnEditar')?.addEventListener('click', function() {
  const id = getLoteSeleccionado();
  if (!id) { alert('Seleccioná un lote primero.'); return; }
  window.location.href = 'editar_lote?id=' + encodeURIComponent(id);
});

document.getElementById('btnVerDetalles')?.addEventListener('click', function() {
  const id = getLoteSeleccionado();
  if (!id) { alert('Seleccioná un lote primero.'); return; }
  window.location.href = 'detalle_lote?id_lote=' + encodeURIComponent(id);
});

document.getElementById('btnVerNotas')?.addEventListener('click', function() {
  const id = getLoteSeleccionado();
  if (!id) { alert('Seleccioná un lote primero.'); return; }
  window.location.href = 'planilla_cata?id=' + encodeURIComponent(id);
});

document.getElementById('btnEliminar')?.addEventListener('click', function() {
  const id = getLoteSeleccionado();
  if (!id) { alert('Seleccioná un lote primero.'); return; }
  if (!confirm('¿Eliminar este lote y todos sus registros asociados? Esta acción no se puede deshacer.')) return;

  fetch('eliminar_registro', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      id:         id,
      tabla:      'lotes_cerveza',
      csrf_token: '<?= e(getCsrfToken()) ?>'
    })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      window.location.reload();
    } else {
      alert('Error: ' + (data.message || 'No se pudo eliminar.'));
    }
  })
  .catch(() => alert('Error de red. Intentá de nuevo.'));
});

// Búsqueda con Enter
document.getElementById('searchInput')?.addEventListener('keydown', function(e) {
  if (e.key === 'Enter') document.getElementById('searchForm').submit();
});

document.getElementById('btnComparar')?.addEventListener('click', function() {
  const ids = getLotesSeleccionados();
  if (ids.length !== 2) { alert('Seleccioná exactamente 2 lotes para comparar.'); return; }
  window.location.href = 'comparar_fermentacion?id1=' + ids[0] + '&id2=' + ids[1];
});

function loadContent(page) { window.location.href = page; }
</script>

</body>
</html>
