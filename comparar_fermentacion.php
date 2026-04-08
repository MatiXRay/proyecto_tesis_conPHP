<?php
/**
 * BIALYSTOK BREWING CO — Comparar fermentación entre dos lotes
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }
require_once 'conexion.php';

$id1 = getIntParam('id1');
$id2 = getIntParam('id2');
if (!$id1 || !$id2) { header('Location: lotes'); exit; }

$menu_activo = 'lotes';

try {
    $pdo = getPDO();

    function getLote(PDO $pdo, int $id): array {
        $stmt = $pdo->prepare(
            "SELECT lc.id, lc.numero_lote, lc.fecha_elaboracion, lc.og, lc.fg, lc.abv,
                    ec.nombre AS estilo
             FROM lotes_cerveza lc
             LEFT JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
             WHERE lc.id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: [];
    }

    function getFerm(PDO $pdo, int $id): array {
        $stmt = $pdo->prepare(
            "SELECT fecha, densidad, temperatura, ph, comentarios
             FROM seguimiento_fermentacion
             WHERE lote_id = ?
             ORDER BY fecha ASC, hora ASC"
        );
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    $lote1 = getLote($pdo, $id1);
    $lote2 = getLote($pdo, $id2);
    $ferm1 = getFerm($pdo, $id1);
    $ferm2 = getFerm($pdo, $id2);

    if (!$lote1 || !$lote2) { header('Location: lotes'); exit; }

} catch (PDOException $ex) {
    error_log('[comparar_ferm] ' . $ex->getMessage());
    header('Location: lotes'); exit;
}

// Construir series normalizadas por día relativo (día 1, día 2...)
function buildDayLabels(array $rows): array {
    if (!$rows) return [];
    $inicio = strtotime($rows[0]['fecha']);
    return array_map(fn($r) => 'Día ' . (int)(( strtotime($r['fecha']) - $inicio ) / 86400 + 1), $rows);
}
function buildSeries(array $rows, string $campo): array {
    return array_map(fn($r) => ($r[$campo] !== null && $r[$campo] !== '') ? (float)$r[$campo] : null, $rows);
}

$labels1 = buildDayLabels($ferm1);
$labels2 = buildDayLabels($ferm2);
$maxLen  = max(count($labels1), count($labels2));

// Generar eje x como día 1..N
$labels_x = array_map(fn($i) => 'Día '.($i+1), range(0, $maxLen - 1));

function padNull(array $arr, int $len): array {
    while (count($arr) < $len) $arr[] = null;
    return $arr;
}

$d1 = padNull(buildSeries($ferm1, 'densidad'),    $maxLen);
$d2 = padNull(buildSeries($ferm2, 'densidad'),    $maxLen);
$t1 = padNull(buildSeries($ferm1, 'temperatura'), $maxLen);
$t2 = padNull(buildSeries($ferm2, 'temperatura'), $maxLen);
$p1 = padNull(buildSeries($ferm1, 'ph'),          $maxLen);
$p2 = padNull(buildSeries($ferm2, 'ph'),          $maxLen);

$n1 = strtoupper($lote1['numero_lote']);
$n2 = strtoupper($lote2['numero_lote']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comparar fermentación · <?= e($n1) ?> vs <?= e($n2) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/bialy-design-system.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>
<?php require 'menu.php'; ?>
<?php require 'info_user.php'; ?>

<div id="contenido" class="main-content">

  <div class="page-header fade-in">
    <div>
      <h1>Comparar fermentación</h1>
      <p class="page-subtitle">
        <span style="color:#4aaa4a;font-weight:600"><?= e($n1) ?></span>
        <?= e($lote1['estilo'] ?? '') ?>
        <span style="color:var(--text-muted)"> vs </span>
        <span style="color:#4a90d9;font-weight:600"><?= e($n2) ?></span>
        <?= e($lote2['estilo'] ?? '') ?>
      </p>
    </div>
    <a href="lotes" class="btn btn-ghost btn-sm">← Volver</a>
  </div>

  <!-- Info comparativa -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem" class="fade-in">
    <?php foreach ([[$lote1,'#4aaa4a'],[$lote2,'#4a90d9']] as [$lote,$color]): ?>
    <div class="card" style="border-top:3px solid <?= $color ?>">
      <div style="font-weight:600;font-size:.95rem;color:<?= $color ?>"><?= e(strtoupper($lote['numero_lote'])) ?></div>
      <div style="font-size:.82rem;color:var(--text-muted);margin-bottom:.5rem"><?= e($lote['estilo'] ?? '—') ?> · <?= e(date('d/m/Y', strtotime($lote['fecha_elaboracion']))) ?></div>
      <div style="display:flex;gap:1rem;font-family:'DM Mono',monospace;font-size:.8rem">
        <span>OG: <strong><?= e($lote['og'] ?? '—') ?></strong></span>
        <span>FG: <strong><?= e($lote['fg'] ?? '—') ?></strong></span>
        <span>ABV: <strong><?= $lote['abv'] ? e($lote['abv']).'%' : '—' ?></strong></span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Gráfico Densidad -->
  <div class="card fade-in" style="margin-bottom:1rem">
    <div class="card-title">Densidad</div>
    <div style="position:relative;height:200px">
      <canvas id="chartDens"></canvas>
    </div>
  </div>

  <!-- Gráfico Temperatura -->
  <div class="card fade-in" style="margin-bottom:1rem">
    <div class="card-title">Temperatura (°C)</div>
    <div style="position:relative;height:200px">
      <canvas id="chartTemp"></canvas>
    </div>
  </div>

  <!-- Gráfico pH -->
  <div class="card fade-in" style="margin-bottom:1rem">
    <div class="card-title">pH</div>
    <div style="position:relative;height:200px">
      <canvas id="chartPh"></canvas>
    </div>
  </div>

  <!-- Leyenda -->
  <div style="display:flex;gap:1.5rem;justify-content:center;margin-bottom:1.5rem;font-size:.8rem" class="fade-in">
    <span style="display:flex;align-items:center;gap:.4rem">
      <span style="width:20px;height:3px;background:#4aaa4a;display:inline-block;border-radius:2px"></span>
      <?= e($n1) ?>
    </span>
    <span style="display:flex;align-items:center;gap:.4rem">
      <span style="width:20px;height:3px;background:#4a90d9;display:inline-block;border-radius:2px;border-top:2px dashed #4a90d9"></span>
      <?= e($n2) ?>
    </span>
  </div>

</div>

<script>
const LABELS = <?= json_encode($labels_x) ?>;
const D1 = <?= json_encode($d1) ?>;
const D2 = <?= json_encode($d2) ?>;
const T1 = <?= json_encode($t1) ?>;
const T2 = <?= json_encode($t2) ?>;
const P1 = <?= json_encode($p1) ?>;
const P2 = <?= json_encode($p2) ?>;

const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
const grid   = isDark ? 'rgba(255,240,200,0.07)' : 'rgba(0,0,0,0.08)';
const txt    = isDark ? '#6b6150' : '#9e8060';
const tooltip = {
  backgroundColor: isDark ? '#2a271e' : '#fff',
  borderColor: grid, borderWidth:1,
  titleColor: isDark ? '#e8e0cc' : '#1e1608',
  bodyColor:  isDark ? '#a09880' : '#4a3820',
};

Chart.defaults.color = txt;
Chart.defaults.borderColor = grid;

function mkChart(id, label, s1, s2, color1, color2, yLabel) {
  new Chart(document.getElementById(id), {
    type: 'line',
    data: {
      labels: LABELS,
      datasets: [
        { label: '<?= e($n1) ?>', data: s1, borderColor: color1, backgroundColor: color1+'22',
          tension:.35, pointRadius:3, fill:true, spanGaps:true },
        { label: '<?= e($n2) ?>', data: s2, borderColor: color2, backgroundColor: color2+'11',
          tension:.35, pointRadius:3, fill:false, borderDash:[5,3], spanGaps:true },
      ]
    },
    options: {
      responsive:true, maintainAspectRatio:false,
      interaction:{ mode:'index', intersect:false },
      plugins:{ legend:{ position:'top', labels:{ font:{size:11}, boxWidth:14 } }, tooltip },
      scales:{
        x:{ grid:{ color:grid }, ticks:{ font:{size:10} } },
        y:{ title:{ display:true, text:yLabel, font:{size:10} }, grid:{ color:grid }, ticks:{ font:{size:10} } }
      }
    }
  });
}

mkChart('chartDens', 'Densidad',     D1, D2, '#4aaa4a', '#4a90d9', 'Densidad');
mkChart('chartTemp', 'Temperatura',  T1, T2, '#4aaa4a', '#4a90d9', '°C');
mkChart('chartPh',   'pH',           P1, P2, '#4aaa4a', '#4a90d9', 'pH');

function loadContent(page) { window.location.href = page; }
</script>
</body>
</html>
