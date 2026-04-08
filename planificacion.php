<?php
/**
 * BIALYSTOK BREWING CO — Planificación (Timeline tipo BrewPlanner)
 * Eje Y = fermentadores, Eje X = días
 * Drag chips de estilo → drop en fermentador+fecha
 * Drag bloques para mover, resize para ajustar duración
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }
require_once 'conexion.php';

$menu_activo = 'planificacion';

$COLORES   = ['#4a8f4a','#2e7db5','#8b5e3c','#7b5ea7','#c8922a','#3a9e8a','#b54a4a','#5a7a9e','#7a9e5a','#9e5a7a'];
// Mapa color por estilo_id (desde DB, con fallback a paleta)
$estilo_color_map = [];
$PX_DIA  = 32;
$DIAS    = 90;

try {
    $pdo = getPDO();
    $fermentadores = $pdo->query("SELECT id, nombre FROM fermentadores ORDER BY nombre")->fetchAll();
    $estilos       = $pdo->query("SELECT id, nombre, duracion_dias, color FROM estilos_cerveza ORDER BY nombre")->fetchAll();

    $stmt = $pdo->prepare(
        "SELECT p.*, ec.nombre AS estilo_nombre,
                COALESCE(p.duracion_dias, ec.duracion_dias, 21) AS dur_efectiva
         FROM planificacion p
         LEFT JOIN estilos_cerveza ec ON p.estilo_id = ec.id
         WHERE p.fecha_coccion IS NOT NULL
         ORDER BY p.fecha_coccion ASC"
    );
    $stmt->execute();
    $lotes = $stmt->fetchAll();

    $tareas_map = [];
    if ($lotes) {
        $ids   = implode(',', array_map(fn($l) => (int)$l['id'], $lotes));
        $rows  = $pdo->query("SELECT * FROM planificacion_tareas WHERE plan_id IN ($ids) ORDER BY fecha_estimada, orden")->fetchAll();
        foreach ($rows as $t) $tareas_map[(int)$t['plan_id']][] = $t;
    }

    // Construir mapa de colores por estilo_id
    foreach ($estilos as $idx => $est) {
        $estilo_color_map[(int)$est['id']] = $est['color'] ?: $COLORES[$idx % count($COLORES)];
    }

    $tareas_predefs = ['Dry hop','Gelatina','Gasificado','Enfriado','Purga','Muestra QC','Filtrado','Carbonatación','Trasvase'];

} catch (PDOException $ex) {
    error_log('[plan] ' . $ex->getMessage());
    $fermentadores = $estilos = $lotes = [];
    $tareas_map = [];
    $tareas_predefs = [];
}

// Rango: desde fecha elegida (default = 2 meses atrás) hasta 4 meses adelante
$DIAS = 180; // 6 meses totales

$fecha_desde_param = isset($_GET['desde']) ? $_GET['desde'] : null;
if ($fecha_desde_param && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_desde_param)) {
    $inicio = new DateTime($fecha_desde_param);
} else {
    $inicio = new DateTime();
    $inicio->modify('-2 months');
}

$hoy    = new DateTime();
$fechas = [];
$d      = clone $inicio;
for ($i = 0; $i < $DIAS; $i++) { $fechas[] = clone $d; $d->modify('+1 day'); }

function dayOffset(DateTime $inicio, ?string $fecha): ?int {
    if (!$fecha) return null;
    $dt   = new DateTime($fecha);
    $diff = $inicio->diff($dt);
    return $diff->invert ? -$diff->days : $diff->days;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Planificación · Bialystok Brewing</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/bialy-design-system.css">
  <style>
    .plan-layout   { display:flex; gap:0; height:calc(100vh - 200px); overflow:hidden; border:1px solid var(--color-border); border-radius:var(--radius-lg); }
    .timeline-area { flex:1; overflow:auto; position:relative; }
    .side-panel    { width:0; overflow:hidden; transition:width .2s; flex-shrink:0; background:var(--color-surface); border-left:1px solid var(--color-border); }
    .side-panel.open { width:300px; overflow-y:auto; }

    /* Grid */
    .tl-grid { display:flex; }
    .tl-axis { width:64px; flex-shrink:0; position:sticky; left:0; z-index:20; background:var(--color-surface-2); border-right:1px solid var(--color-border); }
    .tl-axis-head { height:48px; position:sticky; top:0; z-index:21; background:var(--color-surface-2); border-bottom:1px solid var(--color-border); }
    .axis-row { height:<?= $PX_DIA ?>px; display:flex; align-items:center; justify-content:center; font-size:.65rem; font-family:'DM Mono',monospace; color:var(--text-muted); border-bottom:1px solid rgba(255,255,255,0.03); }
    .axis-row.hoy    { color:var(--text-amber); font-weight:600; background:rgba(74,170,74,0.06); }
    .axis-row.finde  { color:var(--text-secondary); background:rgba(255,255,255,0.015); }

    /* Columnas FV */
    .fv-cols { display:flex; flex:1; }
    .fv-col  { flex:1; min-width:140px; border-right:1px solid var(--color-border); position:relative; }
    .fv-col:last-child { border-right:none; }
    .fv-head { height:48px; display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:600; color:var(--text-secondary); border-bottom:1px solid var(--color-border); background:var(--color-surface-2); position:sticky; top:0; z-index:9; text-transform:uppercase; letter-spacing:.06em; }
    .fv-body { position:relative; height:<?= $DIAS * $PX_DIA ?>px; }
    .fv-body::before { content:''; position:absolute; inset:0; background-image:repeating-linear-gradient(to bottom, transparent, transparent <?= $PX_DIA-1 ?>px, rgba(255,255,255,0.09) <?= $PX_DIA-1 ?>px, rgba(255,255,255,0.09) <?= $PX_DIA ?>px); pointer-events:none; z-index:1; }
    .fv-body::after { content:''; position:absolute; inset:0; background-image:repeating-linear-gradient(to bottom, transparent, transparent <?= ($PX_DIA*7)-1 ?>px, rgba(255,255,255,0.2) <?= ($PX_DIA*7)-1 ?>px, rgba(255,255,255,0.2) <?= $PX_DIA*7 ?>px); pointer-events:none; z-index:2; }

    /* Línea hoy */
    .hoy-line { position:absolute; left:0; right:0; height:2px; background:rgba(74,170,74,0.5); top:0; z-index:5; pointer-events:none; }

    /* Bloques de lote */
    .lote-blk { position:absolute; left:3px; right:3px; border-radius:5px; cursor:grab; z-index:6; overflow:visible; transition:box-shadow .12s; user-select:none; }
    .lote-blk:hover { box-shadow:0 3px 14px rgba(0,0,0,.55); z-index:7; }
    .lote-blk.dragging { opacity:.5; cursor:grabbing; }
    .lote-inner { padding:5px 7px; height:100%; overflow:hidden; border-radius:5px; }
    .lote-nombre { font-size:.75rem; font-weight:600; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .lote-sub    { font-size:.62rem; color:rgba(255,255,255,.65); }
    .tarea-pin   { position:absolute; left:3px; right:3px; height:18px; background:rgba(0,0,0,.4); border-radius:2px; display:flex; align-items:center; padding:0 5px; font-size:.58rem; color:rgba(255,255,255,.9); white-space:nowrap; overflow:hidden; z-index:8; pointer-events:none; }

    /* Bandeja de tareas (drag source) */
    .tareas-band { display:flex; flex-wrap:wrap; gap:.3rem; }
    .t-chip { padding:.22rem .55rem; border-radius:4px; font-size:.72rem; font-weight:500; cursor:grab; color:#fff; user-select:none; background:rgba(0,0,0,.45); border:1px solid rgba(255,255,255,.2); }
    .t-chip:hover { background:rgba(0,0,0,.6); }
    .t-chip.dragging { opacity:.4; }
    .lote-blk.drop-target { outline:2px dashed rgba(255,255,255,.5); outline-offset:-2px; }
    .resize-h    { position:absolute; bottom:0; left:0; right:0; height:7px; cursor:s-resize; background:rgba(255,255,255,.12); border-radius:0 0 5px 5px; z-index:9; }
    .resize-h:hover { background:rgba(255,255,255,.28); }

    /* Bandeja estilos */
    .estilos-band { display:flex; flex-wrap:wrap; gap:.35rem; }
    .e-chip { padding:.28rem .65rem; border-radius:99px; font-size:.75rem; font-weight:500; cursor:grab; color:#fff; user-select:none; }
    .e-chip:hover { filter:brightness(1.1); }
    .e-chip.dragging { opacity:.4; }

    /* Panel lateral */
    .sp-inner { width:300px; padding:1.1rem; }
    .sp-title { font-size:.95rem; font-weight:500; color:var(--text-amber); margin-bottom:1rem; }

    /* Tooltip */
    #tip { position:fixed; background:var(--color-surface-3); border:1px solid var(--color-border-md); border-radius:8px; padding:.5rem .75rem; font-size:.75rem; color:var(--text-primary); pointer-events:none; z-index:9999; display:none; max-width:200px; }
  </style>
</head>
<body>
<?php require 'menu.php'; ?>
<?php require 'info_user.php'; ?>

<div id="contenido" class="main-content" style="padding-bottom:0">

  <div class="page-header fade-in" style="margin-bottom:.75rem">
    <div>
      <h1>Planificación</h1>
      <p class="page-subtitle">Arrastrá un estilo al fermentador · Resize para ajustar días</p>
    </div>
    <div style="display:flex;gap:.5rem;align-items:center">
      <div style="display:flex;align-items:center;gap:.4rem">
        <label style="font-size:.75rem;color:var(--text-muted)">Desde:</label>
        <input type="date" id="inputDesde"
               value="<?= $inicio->format('Y-m-d') ?>"
               style="font-size:.78rem;padding:.3rem .5rem;background:var(--color-surface-2);border:1px solid var(--color-border-md);border-radius:var(--radius-sm);color:var(--text-primary);height:28px"
               onchange="cambiarFecha(this.value)">
      </div>
      <button class="btn btn-ghost btn-sm" onclick="irHoy()">Ir a hoy</button>
      <button class="btn btn-primary btn-sm" onclick="abrirNuevo()">+ Nuevo lote</button>
    </div>
  </div>

  <!-- Bandeja de estilos -->
  <div class="card fade-in" style="margin-bottom:.75rem;padding:.75rem 1rem">
    <span style="font-size:.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.1em;color:var(--text-muted);margin-right:.75rem">Estilos →</span>
    <span class="estilos-band" style="display:inline-flex">
      <?php foreach ($estilos as $i => $est): ?>
      <?php $chip_color = $est['color'] ?: $COLORES[$i % count($COLORES)]; ?>
      <span class="e-chip"
            draggable="true"
            data-eid="<?= (int)$est['id'] ?>"
            data-enombre="<?= e($est['nombre']) ?>"
            data-edur="<?= (int)($est['duracion_dias']??21) ?>"
            data-color="<?= e($chip_color) ?>"
            style="background:<?= $chip_color ?>">
        <?= e($est['nombre']) ?>
      </span>
      <?php endforeach; ?>
    </span>
  </div>

  <!-- Bandeja de tareas -->
  <div class="card fade-in" style="margin-bottom:.75rem;padding:.75rem 1rem">
    <span style="font-size:.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.1em;color:var(--text-muted);margin-right:.75rem">Tareas → arrastrá al lote</span>
    <span class="tareas-band" style="display:inline-flex;flex-wrap:wrap;gap:.3rem">
      <?php foreach ($tareas_predefs as $tp): ?>
      <span class="t-chip"
            draggable="true"
            data-tnombre="<?= e($tp) ?>">
        ▸ <?= e($tp) ?>
      </span>
      <?php endforeach; ?>
    </span>
  </div>

  <!-- Layout principal -->
  <div class="plan-layout fade-in">
    <div class="timeline-area" id="tl-area">
      <div class="tl-grid">

        <!-- Eje fechas -->
        <div class="tl-axis">
          <div class="tl-axis-head"></div>
          <?php foreach ($fechas as $i => $f):
            $es_hoy   = $f->format('Ymd') === $hoy->format('Ymd');
            $es_finde = in_array((int)$f->format('N'), [6,7]);
          ?>
          <div class="axis-row <?= $es_hoy ? 'hoy' : ($es_finde ? 'finde' : '') ?>"
               data-date="<?= $f->format('Y-m-d') ?>" data-idx="<?= $i ?>">
            <?= $f->format('d/m') ?>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Columnas FV -->
        <div class="fv-cols">
          <?php
          // Agregar columna "Sin asignar"
          $cols = array_merge([['id'=>0,'nombre'=>'Sin asignar']], $fermentadores);
          foreach ($cols as $fv):
          ?>
          <div class="fv-col" data-fv="<?= (int)$fv['id'] ?>">
            <div class="fv-head"><?= e($fv['nombre']) ?></div>
            <div class="fv-body" id="fvb-<?= (int)$fv['id'] ?>">
              <div class="hoy-line"></div>

              <?php foreach ($lotes as $idx => $lote):
                $fv_match = ((int)($lote['fermentador_id']??0)) === (int)$fv['id'];
                if (!$fv_match) continue;
                $off = dayOffset($inicio, $lote['fecha_coccion']);
                if ($off === null) continue;
                $dur    = (int)$lote['dur_efectiva'];
                $top    = $off * $PX_DIA;
                $height = max($PX_DIA, $dur * $PX_DIA);
                $color  = $lote['color'] ?: ($estilo_color_map[(int)($lote['estilo_id'] ?? 0)] ?? $COLORES[$idx % count($COLORES)]);
                $tareas = $tareas_map[(int)$lote['id']] ?? [];
                $fin_dt = (new DateTime($lote['fecha_coccion']))->modify("+$dur days");
              ?>
              <div class="lote-blk"
                   id="lb<?= (int)$lote['id'] ?>"
                   data-id="<?= (int)$lote['id'] ?>"
                   data-fv="<?= (int)$fv['id'] ?>"
                   data-fecha="<?= e($lote['fecha_coccion']) ?>"
                   data-dur="<?= $dur ?>"
                   style="top:<?= $top ?>px;height:<?= $height ?>px;background:<?= $color ?>"
                   draggable="true">
                <div class="lote-inner">
                  <div class="lote-nombre"><?= e($lote['nombre']) ?></div>
                  <?php if ($lote['estilo_nombre']): ?>
                  <div class="lote-sub"><?= e($lote['estilo_nombre']) ?></div>
                  <?php endif; ?>
                  <div class="lote-sub" style="font-family:'DM Mono',monospace;font-size:.6rem">
                    <?= e(date('d/m', strtotime($lote['fecha_coccion']))) ?> → <?= e($fin_dt->format('d/m')) ?>
                  </div>
                </div>

                <?php foreach ($tareas as $t):
                  if (!$t['fecha_estimada']) continue;
                  $t_off = dayOffset(new DateTime($lote['fecha_coccion']), $t['fecha_estimada']);
                  if ($t_off === null || $t_off < 0) continue;
                  $t_top = $t_off * $PX_DIA + 2;
                ?>
                <div class="tarea-pin" style="top:<?= $t_top ?>px">▸ <?= e($t['nombre']) ?></div>
                <?php endforeach; ?>

                <div class="resize-h"></div>
              </div>
              <?php endforeach; ?>

            </div>
          </div>
          <?php endforeach; ?>
        </div>

      </div>
    </div>

    <!-- Panel lateral -->
    <div class="side-panel" id="side-panel">
      <div class="sp-inner">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.9rem">
          <div class="sp-title" id="sp-titulo">Nuevo lote</div>
          <button onclick="cerrarPanel()" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:1.1rem">✕</button>
        </div>

        <input type="hidden" id="ei">
        <input type="hidden" id="ecolor">

        <div class="form-group">
          <label class="form-label">Nombre *</label>
          <input type="text" id="enombre" placeholder="Ej: NEIPA #25">
        </div>
        <div class="form-group">
          <label class="form-label">Estilo</label>
          <select id="eestilo" onchange="onEstilo(this)">
            <option value="">Sin estilo</option>
            <?php foreach ($estilos as $est): ?>
            <option value="<?= (int)$est['id'] ?>" data-dur="<?= (int)($est['duracion_dias']??21) ?>" data-color="<?= e($est['color'] ?: $COLORES[$i % count($COLORES)]) ?>">
              <?= e($est['nombre']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
          <div class="form-group">
            <label class="form-label">Fecha cocción</label>
            <input type="date" id="efecha" onchange="calcFin()">
          </div>
          <div class="form-group">
            <label class="form-label">Duración (días)</label>
            <input type="number" id="edur" value="21" min="1" max="365" onchange="calcFin()">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Fecha fin estimada</label>
          <input type="date" id="efin" style="color:var(--text-amber)">
        </div>
        <div class="form-group">
          <label class="form-label">Fermentador</label>
          <select id="efv">
            <option value="">Sin asignar</option>
            <?php foreach ($fermentadores as $fv): ?>
            <option value="<?= (int)$fv['id'] ?>"><?= e($fv['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Notas</label>
          <textarea id="enotas" rows="2" style="resize:vertical"></textarea>
        </div>

        <!-- Tareas -->
        <div class="form-group">
          <label class="form-label">Tareas</label>
          <div style="display:flex;flex-wrap:wrap;gap:.25rem;margin-bottom:.4rem">
            <?php foreach ($tareas_predefs as $tp): ?>
            <button type="button" class="btn btn-ghost btn-sm" onclick="addTarea('<?= e(addslashes($tp)) ?>')" style="font-size:.68rem;padding:.2rem .5rem">+<?= e($tp) ?></button>
            <?php endforeach; ?>
          </div>
          <div id="tlista"></div>
          <div style="display:flex;gap:.35rem;margin-top:.3rem">
            <input type="text" id="tcustom" placeholder="Custom…" style="flex:1;font-size:.8rem">
            <button class="btn btn-ghost btn-sm" onclick="addTareaCustom()">+</button>
          </div>
        </div>

        <div style="display:flex;gap:.5rem;margin-top:1rem">
          <button class="btn btn-primary btn-sm" style="flex:1" onclick="guardar()">Guardar</button>
          <button class="btn btn-danger btn-sm" id="btnElim" style="display:none" onclick="eliminar()">Eliminar</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="tip"></div>

<script>
const CSRF   = '<?= e(getCsrfToken()) ?>';
const PX     = <?= $PX_DIA ?>;
const HOY    = '<?= $hoy->format('Y-m-d') ?>';
const INICIO = '<?= $inicio->format('Y-m-d') ?>';
const COLORS = <?= json_encode($COLORES) ?>;
let tareas    = [];
let chipDrag  = null;
let tareaDrag = null;  // chip de tarea arrastrándose

// ── Init ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  irHoy();
  initBlocks();
  initDropZones();
  document.querySelectorAll('.e-chip').forEach(initChip);
  document.querySelectorAll('.t-chip').forEach(initTareaChip);
});

function irHoy() {
  // Calcular posición de hoy en el timeline
  const rows = document.querySelectorAll('.axis-row[data-date]');
  for (const row of rows) {
    if (row.dataset.date === HOY) {
      const idx = row.dataset.idx ? +row.dataset.idx : 0;
      document.getElementById('tl-area').scrollTop = idx * PX - 100;
      return;
    }
  }
  document.getElementById('tl-area').scrollTop = 0;
}

function cambiarFecha(fecha) {
  const url = new URL(window.location.href);
  url.searchParams.set('desde', fecha);
  window.location.href = url.toString();
}

// ── Bloques existentes ────────────────────────────────────
function initBlocks() {
  document.querySelectorAll('.lote-blk').forEach(b => { initBlock(b); initResize(b.querySelector('.resize-h')); });
}

function initBlock(b) {
  b.addEventListener('dragstart', e => {
    b.classList.add('dragging');
    e.dataTransfer.setData('tipo', 'mover');
    e.dataTransfer.setData('id',   b.dataset.id);
    e.dataTransfer.setData('oy',   e.offsetY);
  });
  b.addEventListener('dragend',  () => b.classList.remove('dragging'));
  b.addEventListener('click', e => { if (!e.target.classList.contains('resize-h')) abrirEditar(+b.dataset.id); });
  b.addEventListener('mouseenter', e => showTip(e, b));
  b.addEventListener('mouseleave', () => hideTip());

  // Drop de tarea sobre el bloque
  b.addEventListener('dragover', e => {
    if (!tareaDrag) return;
    e.preventDefault(); e.stopPropagation();
    b.classList.add('drop-target');
  });
  b.addEventListener('dragleave', () => b.classList.remove('drop-target'));
  b.addEventListener('drop', e => {
    if (!tareaDrag) return;
    e.preventDefault(); e.stopPropagation();
    b.classList.remove('drop-target');
    const loteId = +b.dataset.id;
    // Calcular fecha según posición del drop dentro del bloque
    const rect   = b.getBoundingClientRect();
    const relY   = e.clientY - rect.top;
    const diaOff = Math.max(0, Math.floor(relY / PX));
    const fecha  = offsetDate(b.dataset.fecha, diaOff);
    // Guardar la tarea
    guardarTareaEnLote(loteId, tareaDrag, fecha);
    tareaDrag = null;
  });
}

// ── Resize ────────────────────────────────────────────────
function initResize(h) {
  if (!h) return;
  h.addEventListener('mousedown', e => {
    e.stopPropagation(); e.preventDefault();
    const b    = h.closest('.lote-blk');
    const id   = +b.dataset.id;
    const sy   = e.clientY;
    const sh   = parseInt(b.style.height);

    const mv = ev => {
      const nh = Math.max(PX, sh + ev.clientY - sy);
      b.style.height = nh + 'px';
      b.dataset.dur  = Math.round(nh / PX);
      updateFechasFin(b);
    };
    const up = () => {
      document.removeEventListener('mousemove', mv);
      document.removeEventListener('mouseup',   up);
      save({ accion:'mover_timeline', id, fecha_coccion: b.dataset.fecha, fermentador_id: b.dataset.fv||'', duracion_dias: b.dataset.dur });
    };
    document.addEventListener('mousemove', mv);
    document.addEventListener('mouseup',   up);
  });
}

function updateFechasFin(b) {
  const sub = b.querySelectorAll('.lote-sub');
  if (sub.length < 2) return;
  const fin = offsetDate(b.dataset.fecha, +b.dataset.dur);
  const fi  = b.dataset.fecha.split('-');
  sub[sub.length-1].textContent = fi[2]+'/'+fi[1]+' → '+fin.slice(8)+'/'+fin.slice(5,7);
}

// ── Chips de tarea (drag source) ─────────────────────────
function initTareaChip(c) {
  c.addEventListener('dragstart', e => {
    c.classList.add('dragging');
    e.dataTransfer.setData('tipo', 'tarea');
    tareaDrag = c.dataset.tnombre;
  });
  c.addEventListener('dragend', () => { c.classList.remove('dragging'); });
}

function guardarTareaEnLote(loteId, nombre, fecha) {
  fetch('planificacion_update', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ accion: 'agregar_tarea', id: loteId, nombre, fecha, csrf_token: CSRF })
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      // Agregar pin visualmente sin recargar
      const b = document.querySelector(`.lote-blk[data-id="${loteId}"]`);
      if (!b) return;
      const inicio = b.dataset.fecha;
      const diaOff = Math.round((new Date(fecha) - new Date(inicio)) / 86400000);
      const pin = document.createElement('div');
      pin.className = 'tarea-pin';
      pin.style.top  = (diaOff * PX + 2) + 'px';
      pin.style.pointerEvents = 'none';
      pin.textContent = '▸ ' + nombre;
      b.appendChild(pin);
    }
  })
  .catch(console.error);
}

// ── Drop zones ────────────────────────────────────────────
function initDropZones() {
  document.querySelectorAll('.fv-body').forEach(zone => {
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.style.background='rgba(74,170,74,0.04)'; });
    zone.addEventListener('dragleave', () => { zone.style.background=''; });
    zone.addEventListener('drop', e => {
      e.preventDefault(); zone.style.background='';
      const tipo = e.dataTransfer.getData('tipo');
      const fvId = +zone.closest('.fv-col').dataset.fv;
      const rect = zone.getBoundingClientRect();
      const relY = e.clientY - rect.top + zone.parentElement.parentElement.scrollTop;
      const idx  = Math.max(0, Math.floor(relY / PX));
      const fecha = getFechaByIdx(idx);

      if (tipo === 'mover') {
        const id  = +e.dataTransfer.getData('id');
        const oy  = +e.dataTransfer.getData('oy');
        const dof = Math.floor(oy / PX);
        const fi  = offsetDate(fecha, -dof);
        const b   = document.getElementById('lb'+id);
        if (!b) return;
        zone.appendChild(b);
        b.style.top      = (idx - dof) * PX + 'px';
        b.dataset.fv     = fvId;
        b.dataset.fecha  = fi;
        updateFechasFin(b);
        save({ accion:'mover_timeline', id, fecha_coccion: fi, fermentador_id: fvId||'', duracion_dias: b.dataset.dur });

      } else if (tipo === 'estilo' && chipDrag) {
        abrirNuevoTimeline(chipDrag.eid, chipDrag.nombre, chipDrag.dur, fecha, fvId, chipDrag.color);
        chipDrag = null;
      }
    });
  });
}

function getFechaByIdx(idx) {
  const rows = document.querySelectorAll('.axis-row[data-date]');
  return rows[Math.min(idx, rows.length-1)]?.dataset.date || HOY;
}

// ── Chips estilo ──────────────────────────────────────────
function initChip(c) {
  c.addEventListener('dragstart', e => {
    c.classList.add('dragging');
    e.dataTransfer.setData('tipo','estilo');
    chipDrag = { eid:+c.dataset.eid, nombre:c.dataset.enombre, dur:+c.dataset.edur, color:c.dataset.color };
  });
  c.addEventListener('dragend', () => c.classList.remove('dragging'));
}

// ── Panel nuevo ───────────────────────────────────────────
function abrirNuevo() {
  resetPanel();
  document.getElementById('sp-titulo').textContent = 'Nuevo lote';
  document.getElementById('side-panel').classList.add('open');
  document.getElementById('enombre').focus();
}

function abrirNuevoTimeline(eid, nombre, dur, fecha, fvId, color) {
  resetPanel();
  document.getElementById('sp-titulo').textContent = 'Nuevo lote';
  document.getElementById('eestilo').value = eid;
  document.getElementById('edur').value    = dur;
  document.getElementById('efecha').value  = fecha;
  document.getElementById('efv').value     = fvId || '';
  document.getElementById('ecolor').value   = color || '';
  calcFin();
  document.getElementById('side-panel').classList.add('open');
  document.getElementById('enombre').focus();
}

function abrirEditar(id) {
  save({ accion:'get', id }, r => {
    if (!r.success) return;
    const d = r.lote;
    document.getElementById('sp-titulo').textContent = 'Editar lote';
    document.getElementById('ei').value     = d.id;
    document.getElementById('enombre').value = d.nombre;
    document.getElementById('eestilo').value = d.estilo_id||'';
    document.getElementById('efecha').value  = d.fecha_coccion||'';
    document.getElementById('edur').value    = d.duracion_dias || d.estilo_duracion || 21;
    document.getElementById('efin').value    = d.fecha_fin||'';
    document.getElementById('efv').value     = d.fermentador_id||'';
    document.getElementById('enotas').value  = d.notas||'';
    document.getElementById('ecolor').value  = d.color||'';
    tareas = (r.tareas||[]).map(t => ({ id:t.id, nombre:t.nombre, fecha:t.fecha_estimada||'' }));
    renderTareas();
    document.getElementById('btnElim').style.display = 'inline-flex';
    document.getElementById('side-panel').classList.add('open');
  });
}

function cerrarPanel() { document.getElementById('side-panel').classList.remove('open'); }

function resetPanel() {
  ['ei','ecolor','enombre','eestilo','efecha','efin','enotas'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('efv').value  = '';
  document.getElementById('edur').value = '21';
  document.getElementById('btnElim').style.display = 'none';
  tareas = [];
  renderTareas();
}

function onEstilo(sel) {
  const opt = sel.options[sel.selectedIndex];
  if (opt?.dataset.dur) { document.getElementById('edur').value = opt.dataset.dur; calcFin(); }
  if (opt?.dataset.color) { document.getElementById('ecolor').value = opt.dataset.color; }
}

function calcFin() {
  const f = document.getElementById('efecha').value;
  const d = +document.getElementById('edur').value || 21;
  if (!f) return;
  document.getElementById('efin').value = offsetDate(f, d);
}

// ── Tareas ────────────────────────────────────────────────
function renderTareas() {
  const el = document.getElementById('tlista');
  el.innerHTML = '';
  tareas.forEach((t, i) => {
    const div = document.createElement('div');
    div.style.cssText = 'display:flex;align-items:center;gap:.3rem;margin-bottom:.3rem';
    div.innerHTML = `
      <span style="flex:1;font-size:.78rem;color:var(--text-secondary)">${esc(t.nombre)}</span>
      <input type="date" value="${t.fecha}" onchange="tareas[${i}].fecha=this.value"
             style="font-size:.68rem;width:110px;padding:.18rem .35rem">
      <button onclick="tareas.splice(${i},1);renderTareas()"
              style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:.85rem">✕</button>
    `;
    el.appendChild(div);
  });
}

function addTarea(n)  { tareas.push({ nombre:n, fecha:'' }); renderTareas(); }
function addTareaCustom() {
  const el = document.getElementById('tcustom');
  if (el.value.trim()) { addTarea(el.value.trim()); el.value=''; }
}

document.getElementById('tcustom')?.addEventListener('keydown', e => { if (e.key==='Enter') { e.preventDefault(); addTareaCustom(); } });

// ── Guardar ───────────────────────────────────────────────
function guardar() {
  const nombre = document.getElementById('enombre').value.trim();
  if (!nombre) { alert('El nombre es obligatorio.'); return; }
  const id = document.getElementById('ei').value;
  let color = document.getElementById('ecolor').value;
  // Usar el color del estilo seleccionado si está disponible
  if (!color) {
    const estiloSel = document.getElementById('eestilo');
    const estiloOpt = estiloSel?.options[estiloSel.selectedIndex];
    color = estiloOpt?.dataset.color || COLORS[Math.floor(Math.random()*COLORS.length)];
  }

  save({
    accion: id ? 'editar' : 'crear',
    id, nombre,
    estilo_id:      document.getElementById('eestilo').value,
    fecha_coccion:  document.getElementById('efecha').value,
    duracion_dias:  document.getElementById('edur').value,
    fecha_fin:      document.getElementById('efin').value,
    fermentador_id: document.getElementById('efv').value,
    notas:          document.getElementById('enotas').value,
    color, tareas,
  }, r => {
    if (r.success) { cerrarPanel(); location.reload(); }
    else alert('Error: '+(r.message||'No se pudo guardar.'));
  }, true);
}

function eliminar() {
  const id = document.getElementById('ei').value;
  if (!id || !confirm('¿Eliminar este lote?')) return;
  save({ accion:'eliminar', id }, r => {
    if (r.success) { cerrarPanel(); document.getElementById('lb'+id)?.remove(); }
  });
}

// ── HTTP helper ───────────────────────────────────────────
function save(data, cb, json=false) {
  data.csrf_token = CSRF;
  const opts = json
    ? { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(data) }
    : { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams(data) };
  fetch('planificacion_update', opts)
    .then(r => r.json())
    .then(r => cb && cb(r))
    .catch(console.error);
}

// ── Tooltip ───────────────────────────────────────────────
function showTip(e, b) {
  const t = document.getElementById('tip');
  t.innerHTML = `<strong>${esc(b.querySelector('.lote-nombre')?.textContent||'')}</strong><br><span style="color:var(--text-muted);font-size:.68rem">${b.dataset.dur} días</span>`;
  t.style.display='block';
  t.style.left=(e.clientX+10)+'px';
  t.style.top=(e.clientY-8)+'px';
}
function hideTip() { document.getElementById('tip').style.display='none'; }

// ── Utils ─────────────────────────────────────────────────
function offsetDate(s, d) {
  const dt = new Date(s); dt.setDate(dt.getDate()+d);
  return dt.toISOString().slice(0,10);
}
function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function loadContent(p) { location.href=p; }
</script>
</body>
</html>
