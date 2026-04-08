<?php
/**
 * BIALYSTOK BREWING CO — Estadísticas de producción
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }
require_once 'conexion.php';

$menu_activo = 'estadisticas';

try {
    $pdo = getPDO();

    // ── KPIs globales ─────────────────────────────────────────
    $kpis = $pdo->query(
        "SELECT
            COUNT(*) AS total_lotes,
            SUM(litros_a_fermentador) AS total_lts_ferm,
            SUM(litros_envasados)     AS total_lts_env,
            AVG(DATEDIFF(dia_envasado, fecha_elaboracion)) AS avg_dias_ferm,
            COUNT(DISTINCT estilo_id) AS estilos_distintos,
            AVG(CASE WHEN litros_a_fermentador > 0 AND litros_envasados > 0
                THEN ((litros_a_fermentador - litros_envasados) / litros_a_fermentador) * 100
                ELSE NULL END) AS merma_global_pct
         FROM lotes_cerveza
         WHERE litros_a_fermentador > 0"
    )->fetch();

    // ── Producción por mes (últimos 12 meses) ─────────────────
    $por_mes = $pdo->query(
        "SELECT DATE_FORMAT(fecha_elaboracion, '%Y-%m') AS mes,
                DATE_FORMAT(fecha_elaboracion, '%b')    AS mes_label,
                COUNT(*) AS lotes,
                SUM(litros_a_fermentador) AS litros
         FROM lotes_cerveza
         WHERE fecha_elaboracion >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
         GROUP BY DATE_FORMAT(fecha_elaboracion, '%Y-%m'), DATE_FORMAT(fecha_elaboracion, '%b')
         ORDER BY mes ASC"
    )->fetchAll();

    // ── Desglose por estilo y mes ────────────────────────────
    $por_mes_estilo = $pdo->query(
        "SELECT DATE_FORMAT(lc.fecha_elaboracion, '%Y-%m') AS mes,
                DATE_FORMAT(lc.fecha_elaboracion, '%b')    AS mes_label,
                ec.nombre AS estilo,
                SUM(lc.litros_a_fermentador) AS litros,
                COUNT(lc.id) AS lotes
         FROM lotes_cerveza lc
         INNER JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
         WHERE lc.fecha_elaboracion >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
           AND lc.litros_a_fermentador > 0
         GROUP BY mes, mes_label, ec.id, ec.nombre
         ORDER BY mes ASC, litros DESC"
    )->fetchAll();

    // Organizar por mes → estilo
    $meses_estilos = [];
    $estilos_vistos = [];
    foreach ($por_mes_estilo as $row) {
        $meses_estilos[$row['mes']][$row['estilo']] = [
            'litros' => $row['litros'],
            'lotes'  => $row['lotes'],
            'label'  => $row['mes_label'],
        ];
        $estilos_vistos[$row['estilo']] = true;
    }
    $estilos_vistos = array_keys($estilos_vistos);
    $meses_ordered  = array_keys($meses_estilos);

    // ── Estilos más elaborados (con avg litros/mes) ────────────
    $estilos = $pdo->query(
        "SELECT ec.nombre,
                COUNT(lc.id) AS cantidad,
                SUM(lc.litros_a_fermentador) AS total_lts,
                AVG(lc.litros_a_fermentador) AS avg_lts_lote,
                AVG(lc.abv) AS avg_abv,
                AVG(lc.ibu) AS avg_ibu,
                -- Promedio de litros por mes (basado en el rango real de fechas)
                ROUND(SUM(lc.litros_a_fermentador) /
                    GREATEST(1, TIMESTAMPDIFF(MONTH,
                        MIN(lc.fecha_elaboracion),
                        GREATEST(MAX(lc.fecha_elaboracion), NOW())
                    ) + 1), 1) AS avg_lts_mes
         FROM lotes_cerveza lc
         INNER JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
         WHERE lc.litros_a_fermentador > 0
         GROUP BY ec.id, ec.nombre
         ORDER BY cantidad DESC
         LIMIT 12"
    )->fetchAll();

    // ── Litros elaborados vs envasados por estilo ─────────────
    $litros = $pdo->query(
        "SELECT ec.nombre,
                SUM(lc.litros_a_fermentador) AS total_elaborados,
                SUM(lc.litros_envasados)     AS total_envasados,
                AVG(lc.litros_a_fermentador) AS avg_elaborados,
                AVG(lc.litros_envasados)     AS avg_envasados
         FROM lotes_cerveza lc
         INNER JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
         WHERE lc.litros_a_fermentador > 0
         GROUP BY ec.id, ec.nombre
         ORDER BY total_elaborados DESC
         LIMIT 10"
    )->fetchAll();

    // ── Merma por estilo ──────────────────────────────────────
    $merma = $pdo->query(
        "SELECT ec.nombre,
                AVG(lc.litros_a_fermentador - lc.litros_envasados) AS merma_avg_lts,
                AVG(CASE WHEN lc.litros_a_fermentador > 0
                    THEN ((lc.litros_a_fermentador - lc.litros_envasados) / lc.litros_a_fermentador) * 100
                    ELSE NULL END) AS merma_pct
         FROM lotes_cerveza lc
         INNER JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
         WHERE lc.litros_a_fermentador > 0 AND lc.litros_envasados > 0
         GROUP BY ec.id, ec.nombre
         ORDER BY merma_pct DESC"
    )->fetchAll();

    // ── Tiempo cocción → envasado ─────────────────────────────
    $tiempos = $pdo->query(
        "SELECT ec.nombre,
                AVG(DATEDIFF(lc.dia_envasado, lc.fecha_elaboracion)) AS dias_promedio,
                MIN(DATEDIFF(lc.dia_envasado, lc.fecha_elaboracion)) AS dias_min,
                MAX(DATEDIFF(lc.dia_envasado, lc.fecha_elaboracion)) AS dias_max,
                COUNT(*) AS cantidad
         FROM lotes_cerveza lc
         INNER JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
         WHERE lc.dia_envasado IS NOT NULL
           AND lc.dia_envasado != '0000-00-00'
           AND lc.dia_envasado > lc.fecha_elaboracion
         GROUP BY ec.id, ec.nombre
         ORDER BY dias_promedio ASC"
    )->fetchAll();

    // ── Rendimiento mash por estilo ───────────────────────────
    $rendimiento = $pdo->query(
        "SELECT ec.nombre,
                AVG(b.dens_primer_mosto) AS avg_primer_mosto,
                AVG(b.dens_pre_boil)     AS avg_pre_boil,
                AVG(b.dens_post_boil)    AS avg_post_boil,
                COUNT(DISTINCT lc.id)    AS lotes
         FROM batches b
         INNER JOIN lotes_cerveza lc ON b.lote_id = lc.id
         INNER JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
         WHERE b.dens_primer_mosto > 0
         GROUP BY ec.id, ec.nombre
         ORDER BY avg_primer_mosto DESC
         LIMIT 10"
    )->fetchAll();

    // ── Puntaje sensorial promedio por estilo ─────────────────
    $catas = $pdo->query(
        "SELECT ec.nombre,
                COUNT(nc.id) AS total_catas,
                AVG(nc.aroma_puntaje)      AS avg_aroma,
                AVG(nc.apariencia_puntaje) AS avg_apariencia,
                AVG(nc.sabor_puntaje)      AS avg_sabor,
                AVG(nc.mouthfeel_puntaje)  AS avg_mouthfeel,
                AVG(nc.impresion_puntaje)  AS avg_impresion,
                AVG(nc.aroma_puntaje + nc.apariencia_puntaje + nc.sabor_puntaje +
                    nc.mouthfeel_puntaje + nc.impresion_puntaje) AS avg_total
         FROM notas_cata nc
         INNER JOIN lotes_cerveza lc ON nc.id_lote = lc.id
         INNER JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
         GROUP BY ec.id, ec.nombre
         HAVING total_catas >= 1
         ORDER BY avg_total DESC
         LIMIT 10"
    )->fetchAll();

    // ── Fallas más frecuentes en cata ────────────────────────
    $fallas_raw = $pdo->query(
        "SELECT fallas FROM notas_cata WHERE fallas IS NOT NULL AND fallas != '' "
    )->fetchAll(PDO::FETCH_COLUMN);

    $fallas_count = [];
    foreach ($fallas_raw as $f) {
        foreach (explode(',', $f) as $falla) {
            $falla = trim($falla);
            if ($falla) $fallas_count[$falla] = ($fallas_count[$falla] ?? 0) + 1;
        }
    }
    arsort($fallas_count);
    $fallas_top = array_slice($fallas_count, 0, 8, true);

    // ── Ocupación de fermentadores ────────────────────────────
    $total_fvs   = (int)$pdo->query("SELECT COUNT(*) FROM fermentadores")->fetchColumn();
    $fvs_ocupados = (int)$pdo->query(
        "SELECT COUNT(DISTINCT fermentador_id) FROM lotes_cerveza
         WHERE fermentador_id IS NOT NULL
           AND (dia_envasado IS NULL OR dia_envasado = '0000-00-00')"
    )->fetchColumn();
    $pct_ocupacion = $total_fvs > 0 ? round(($fvs_ocupados / $total_fvs) * 100) : 0;

} catch (PDOException $ex) {
    error_log('[Bialystok estadisticas] ' . $ex->getMessage());
    $estilos = $litros = $merma = $tiempos = $rendimiento = $por_mes = $por_mes_estilo = $catas = $fallas_top = [];
    $meses_estilos = []; $estilos_vistos = []; $meses_ordered = [];
    $kpis = null;
    $fallas_count = [];
    $total_fvs = $fvs_ocupados = $pct_ocupacion = 0;
}

function fmtNum(?float $n, int $dec = 1): string {
    return $n !== null ? number_format((float)$n, $dec, '.', '') : '—';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Estadísticas · Bialystok Brewing</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/bialy-design-system.css">
  <style>
    .kpi-grid   { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.5rem; }
    .stats-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem; }
    .stats-full { margin-bottom:1rem; }

    /* Barras horizontales */
    .bar-wrap  { display:flex; align-items:center; gap:.6rem; margin-bottom:.45rem; }
    .bar-label { font-size:.78rem; color:var(--text-secondary); width:140px; flex-shrink:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .bar-track { flex:1; height:7px; background:var(--color-surface-3); border-radius:99px; overflow:hidden; position:relative; }
    .bar-fill  { height:100%; border-radius:99px; background:var(--amber-400); }
    .bar-val   { font-family:'DM Mono',monospace; font-size:.75rem; color:var(--text-amber); width:70px; text-align:right; flex-shrink:0; }

    /* Gráfico de columnas mensual */
    .col-chart  { display:flex; align-items:flex-end; gap:5px; height:100px; }
    .col-bar    { flex:1; border-radius:3px 3px 0 0; min-height:3px; position:relative; cursor:default; }
    .col-bar:hover::after {
      content: attr(data-tip);
      position:absolute; bottom:110%; left:50%; transform:translateX(-50%);
      background:var(--color-surface-3); color:var(--text-primary);
      font-size:.68rem; padding:.2rem .45rem; border-radius:4px; white-space:nowrap;
      pointer-events:none; z-index:10; border:1px solid var(--color-border);
    }
    .col-labels { display:flex; gap:5px; margin-top:.2rem; }
    .col-label  { flex:1; font-size:.58rem; color:var(--text-muted); text-align:center; }

    /* Radar sensorial (SVG) */
    .radar-wrap { display:flex; gap:1.5rem; align-items:center; flex-wrap:wrap; }

    /* Score bar sensorial */
    .score-bar-wrap { display:flex; align-items:center; gap:.5rem; margin-bottom:.35rem; }
    .score-label    { font-size:.72rem; color:var(--text-muted); width:90px; flex-shrink:0; }
    .score-track    { flex:1; height:5px; background:var(--color-surface-3); border-radius:99px; overflow:hidden; }
    .score-fill     { height:100%; border-radius:99px; background:var(--amber-400); }
    .score-val      { font-family:'DM Mono',monospace; font-size:.72rem; color:var(--text-amber); width:28px; text-align:right; }
  </style>
</head>
<body>
<?php require 'menu.php'; ?>
<?php require 'info_user.php'; ?>

<div id="contenido" class="main-content">

  <div class="page-header fade-in">
    <div>
      <h1>Estadísticas</h1>
      <p class="page-subtitle">Producción · Calidad · Rendimiento · Bialystok Brewing Co</p>
    </div>
  </div>

  <!-- ── KPIs globales ──────────────────────────────────────── -->
  <?php if ($kpis): ?>
  <div class="kpi-grid fade-in">
    <div class="stat-card">
      <div class="stat-value"><?= (int)$kpis['total_lotes'] ?></div>
      <div class="stat-label">Lotes elaborados</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= fmtNum($kpis['total_lts_ferm'], 0) ?> L</div>
      <div class="stat-label">Litros elaborados total</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= fmtNum($kpis['total_lts_env'], 0) ?> L</div>
      <div class="stat-label">Litros envasados total</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= fmtNum($kpis['merma_global_pct']) ?>%</div>
      <div class="stat-label">Merma global promedio</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= fmtNum($kpis['avg_dias_ferm'], 0) ?> días</div>
      <div class="stat-label">Fermentación promedio</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= $pct_ocupacion ?>%</div>
      <div class="stat-label">Ocupación FVs (<?= $fvs_ocupados ?>/<?= $total_fvs ?>)</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= (int)$kpis['estilos_distintos'] ?></div>
      <div class="stat-label">Estilos distintos</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= $kpis['total_lotes'] > 0 ? fmtNum($kpis['total_lts_ferm'] / $kpis['total_lotes'], 0) : '—' ?> L</div>
      <div class="stat-label">Promedio por lote</div>
    </div>
  </div>
  <?php endif; ?>


  <!-- Producción por mes — full width -->
  <div class="stats-full">
    <div class="card fade-in">
      <div class="card-title">Producción mensual · últimos 12 meses</div>
      <?php if ($por_mes):
        $max_lts = max(array_column($por_mes, 'litros') ?: [1]);
        $max_lts = max($max_lts, 1);
      ?>
      <?php
        $PALETA = ['#4a8f4a','#2e7db5','#c8922a','#7b5ea7','#3a9e8a','#b54a4a','#5a7a9e','#7a9e5a','#9e5a7a','#8b5e3c'];
        $estilo_color = [];
        foreach ($estilos_vistos as $idx => $est) {
            $estilo_color[$est] = $PALETA[$idx % count($PALETA)];
        }
      ?>
      <div style="display:flex;align-items:flex-end;gap:4px;height:120px;margin-top:.5rem">
        <?php foreach ($por_mes as $m):
          $h_total = $m['litros'] > 0 ? max(4, ($m['litros'] / $max_lts) * 100) : 4;
          $estilos_mes = $meses_estilos[$m['mes']] ?? [];
          $total_mes = max($m['litros'], 1);
        ?>
        <div style="flex:1;display:flex;flex-direction:column-reverse;height:<?= $h_total ?>%;position:relative;cursor:default;border-radius:3px 3px 0 0;overflow:hidden"
             title="<?= e($m['mes_label'] . ' · ' . fmtNum($m['litros'],0) . ' L · ' . (int)$m['lotes'] . ' lotes') ?>">
          <?php foreach ($estilos_mes as $estilo => $datos):
            $pct_seg = ($datos['litros'] / $total_mes) * 100;
          ?>
          <div style="width:100%;height:<?= $pct_seg ?>%;background:<?= $estilo_color[$estilo] ?? '#4a8f4a' ?>;min-height:2px"
               title="<?= e($estilo . ': ' . fmtNum($datos['litros'],0) . 'L (' . round($pct_seg) . '%)') ?>">
          </div>
          <?php endforeach; ?>
          <?php if (!$estilos_mes): ?>
          <div style="width:100%;height:100%;background:var(--color-surface-3)"></div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="col-labels">
        <?php foreach ($por_mes as $m): ?>
        <div class="col-label"><?= e($m['mes_label']) ?></div>
        <?php endforeach; ?>
      </div>
      <?php if (!empty($estilo_color)): ?>
      <div style="display:flex;flex-wrap:wrap;gap:.4rem .75rem;margin-top:.75rem">
        <?php foreach ($estilo_color as $est => $color): ?>
        <div style="display:flex;align-items:center;gap:.3rem;font-size:.68rem;color:var(--text-secondary)">
          <span style="width:10px;height:10px;border-radius:2px;background:<?= $color ?>;flex-shrink:0"></span>
          <?= e($est) ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($meses_estilos)): ?>
      <div style="overflow-x:auto;margin-top:1rem">
        <table style="font-size:.72rem;min-width:500px;border-collapse:collapse">
          <thead>
            <tr>
              <th style="text-align:left;padding:.3rem .5rem;color:var(--text-muted);font-weight:600;border-bottom:1px solid var(--color-border)">Estilo</th>
              <?php foreach ($meses_ordered as $mes): ?>
              <th style="text-align:center;padding:.3rem .4rem;color:var(--text-muted);font-weight:600;border-bottom:1px solid var(--color-border);white-space:nowrap">
                <?= e($meses_estilos[$mes][array_key_first($meses_estilos[$mes])]['label']) ?>
              </th>
              <?php endforeach; ?>
              <th style="text-align:right;padding:.3rem .5rem;color:var(--text-muted);font-weight:600;border-bottom:1px solid var(--color-border)">Total · %</th>
            </tr>
          </thead>
          <tbody>
            <?php
              // Total general de todos los estilos en el período
              $gran_total = 0;
              foreach ($estilos_vistos as $_est) {
                  foreach ($meses_ordered as $_mes) {
                      $gran_total += $meses_estilos[$_mes][$_est]['litros'] ?? 0;
                  }
              }
            ?>
            <?php foreach ($estilos_vistos as $estilo):
              $total_estilo = 0;
            ?>
            <tr>
              <td style="padding:.28rem .5rem;font-weight:500;white-space:nowrap;border-bottom:1px solid rgba(255,255,255,0.03)">
                <?= e($estilo) ?>
              </td>
              <?php foreach ($meses_ordered as $mes):
                $val = $meses_estilos[$mes][$estilo]['litros'] ?? null;
                $total_estilo += $val ?? 0;
              ?>
              <td style="text-align:center;padding:.28rem .4rem;font-family:'DM Mono',monospace;border-bottom:1px solid rgba(255,255,255,0.03);<?= $val ? 'color:var(--text-amber)' : 'color:var(--text-muted)' ?>">
                <?= $val ? fmtNum($val, 0).'L' : '—' ?>
              </td>
              <?php endforeach; ?>
              <?php $pct_estilo = $gran_total > 0 ? round(($total_estilo / $gran_total) * 100, 1) : 0; ?>
              <td style="text-align:right;padding:.28rem .5rem;font-family:'DM Mono',monospace;font-weight:600;border-bottom:1px solid rgba(255,255,255,0.03)">
                <span style="color:var(--text-amber)"><?= fmtNum($total_estilo, 0) ?>L</span>
                <span style="color:var(--text-muted);font-size:.68rem;margin-left:.3rem"><?= $pct_estilo ?>%</span>
              </td>
            </tr>
            <?php endforeach; ?>
            <!-- Fila totales -->
            <tr style="border-top:1px solid var(--color-border)">
              <td style="padding:.3rem .5rem;font-weight:600;color:var(--text-secondary)">Total</td>
              <?php foreach ($meses_ordered as $mes):
                $tot_mes = array_sum(array_column($meses_estilos[$mes], 'litros'));
              ?>
              <td style="text-align:center;padding:.3rem .4rem;font-family:'DM Mono',monospace;font-weight:600;color:var(--text-secondary)">
                <?= fmtNum($tot_mes, 0) ?>L
              </td>
              <?php endforeach; ?>
              <td style="text-align:right;padding:.3rem .5rem;font-family:'DM Mono',monospace;font-weight:600;color:var(--text-amber)">
                <?= fmtNum($gran_total, 0) ?>L
                <span style="color:var(--text-muted);font-size:.68rem"> 100%</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <?php else: ?>
        <p style="color:var(--text-muted);font-size:.85rem">Sin datos de los últimos 12 meses.</p>
      <?php endif; ?>
    </div>

  </div><!-- /stats-full -->
  <div class="stats-grid">
    <!-- Estilos más elaborados con avg L/mes -->
    <div class="card fade-in">
      <div class="card-title">Estilos más elaborados</div>
      <?php if ($estilos):
        $max_cant = max(array_column($estilos, 'cantidad') ?: [1]);
        foreach ($estilos as $e):
          $pct = $max_cant > 0 ? ($e['cantidad'] / $max_cant) * 100 : 0;
      ?>
      <div class="bar-wrap">
        <span class="bar-label" title="<?= e($e['nombre']) ?>"><?= e($e['nombre']) ?></span>
        <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
        <span class="bar-val" style="font-size:.7rem;width:90px">
          <?= (int)$e['cantidad'] ?> lotes
          <span style="color:var(--text-muted)">· <?= fmtNum($e['avg_lts_mes'], 0) ?>L/mes</span>
        </span>
      </div>
      <?php endforeach; else: ?>
        <p style="color:var(--text-muted);font-size:.85rem">Sin datos.</p>
      <?php endif; ?>
    </div>

    <!-- Litros elaborados vs envasados -->
    <div class="card fade-in">
      <div class="card-title">Litros elaborados vs envasados · por estilo</div>
      <?php if ($litros):
        $max_lts = max(array_column($litros, 'total_elaborados') ?: [1]);
        foreach ($litros as $l):
          $pct_elab = $max_lts > 0 ? ($l['total_elaborados'] / $max_lts) * 100 : 0;
          $pct_env  = $l['total_elaborados'] > 0 ? (($l['total_envasados'] ?? 0) / $l['total_elaborados']) * $pct_elab : 0;
      ?>
      <div class="bar-wrap" style="margin-bottom:.3rem">
        <span class="bar-label" title="<?= e($l['nombre']) ?>"><?= e($l['nombre']) ?></span>
        <div class="bar-track">
          <div style="position:absolute;width:<?= $pct_elab ?>%;height:100%;background:var(--amber-400);opacity:.25;border-radius:99px"></div>
          <div class="bar-fill" style="width:<?= $pct_env ?>%"></div>
        </div>
        <span class="bar-val" style="font-size:.68rem;width:90px">
          <?= fmtNum($l['total_elaborados'], 0) ?>/<span style="color:var(--text-muted)"><?= fmtNum($l['total_envasados'], 0) ?>L</span>
        </span>
      </div>
      <?php endforeach; else: ?>
        <p style="color:var(--text-muted);font-size:.85rem">Sin datos de litros.</p>
      <?php endif; ?>
      <p style="font-size:.68rem;color:var(--text-muted);margin-top:.5rem">Verde intenso = envasados · Verde claro = elaborados</p>
    </div>

    <!-- Merma por estilo -->
    <div class="card fade-in">
      <div class="card-title">Merma promedio por estilo</div>
      <?php if ($merma):
        $max_merma = max(array_column($merma, 'merma_pct') ?: [1]);
        foreach ($merma as $m):
          $pct = $max_merma > 0 ? (($m['merma_pct'] ?? 0) / $max_merma) * 100 : 0;
      ?>
      <div class="bar-wrap">
        <span class="bar-label" title="<?= e($m['nombre']) ?>"><?= e($m['nombre']) ?></span>
        <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%;background:var(--color-warning)"></div></div>
        <span class="bar-val" style="color:var(--color-warning);width:90px;font-size:.7rem">
          <?= fmtNum($m['merma_pct']) ?>%
          <span style="color:var(--text-muted)">· <?= fmtNum($m['merma_avg_lts'], 0) ?>L</span>
        </span>
      </div>
      <?php endforeach; else: ?>
        <p style="color:var(--text-muted);font-size:.85rem">Sin datos de merma.</p>
      <?php endif; ?>
    </div>

    <!-- Tiempo cocción → envasado -->
    <div class="card fade-in">
      <div class="card-title">Días cocción → envasado · por estilo</div>
      <?php if ($tiempos):
        $max_dias = max(array_column($tiempos, 'dias_promedio') ?: [1]);
        foreach ($tiempos as $t):
          $pct = $max_dias > 0 ? (($t['dias_promedio'] ?? 0) / $max_dias) * 100 : 0;
      ?>
      <div class="bar-wrap">
        <span class="bar-label" title="<?= e($t['nombre']) ?>"><?= e($t['nombre']) ?></span>
        <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%;background:var(--color-info)"></div></div>
        <span class="bar-val" style="color:var(--color-info);width:90px;font-size:.7rem">
          <?= fmtNum($t['dias_promedio'], 0) ?>d
          <span style="color:var(--text-muted)">(<?= (int)$t['dias_min'] ?>–<?= (int)$t['dias_max'] ?>)</span>
        </span>
      </div>
      <?php endforeach; else: ?>
        <p style="color:var(--text-muted);font-size:.85rem">Sin datos de envasado.</p>
      <?php endif; ?>
    </div>

    <!-- Puntaje sensorial por estilo -->
    <div class="card fade-in">
      <div class="card-title">Puntaje sensorial promedio · por estilo</div>
      <?php if ($catas): ?>
        <div style="overflow-x:auto">
          <table style="font-size:.78rem;min-width:480px">
            <thead>
              <tr>
                <th>Estilo</th>
                <th>Aroma</th><th>Apariencia</th><th>Sabor</th><th>Mouthfeel</th><th>Impresión</th>
                <th style="color:var(--text-amber)">Total</th>
                <th>Catas</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($catas as $c): ?>
              <tr>
                <td style="font-weight:500"><?= e($c['nombre']) ?></td>
                <td class="mono"><?= fmtNum($c['avg_aroma']) ?></td>
                <td class="mono"><?= fmtNum($c['avg_apariencia']) ?></td>
                <td class="mono"><?= fmtNum($c['avg_sabor']) ?></td>
                <td class="mono"><?= fmtNum($c['avg_mouthfeel']) ?></td>
                <td class="mono"><?= fmtNum($c['avg_impresion']) ?></td>
                <td style="font-family:'DM Mono',monospace;font-weight:600;color:var(--text-amber)"><?= fmtNum($c['avg_total']) ?></td>
                <td style="color:var(--text-muted)"><?= (int)$c['total_catas'] ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p style="color:var(--text-muted);font-size:.85rem">Sin notas de cata registradas.</p>
      <?php endif; ?>
    </div>

  </div><!-- /stats-grid -->

  <!-- Rendimiento mash + Fallas (fila completa) -->
  <div class="stats-grid">

    <!-- Rendimiento mash -->
    <div class="card fade-in">
      <div class="card-title">Rendimiento de mash · densidades promedio por estilo</div>
      <?php if ($rendimiento): ?>
      <div style="overflow-x:auto">
        <table style="font-size:.78rem">
          <thead>
            <tr>
              <th>Estilo</th>
              <th>1° Mosto</th>
              <th>Pre-Boil</th>
              <th>Post-Boil</th>
              <th>Lotes</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rendimiento as $r): ?>
            <tr>
              <td style="font-weight:500"><?= e($r['nombre']) ?></td>
              <td style="font-family:'DM Mono',monospace;color:var(--text-amber)"><?= fmtNum($r['avg_primer_mosto'], 3) ?></td>
              <td style="font-family:'DM Mono',monospace"><?= fmtNum($r['avg_pre_boil'], 3) ?></td>
              <td style="font-family:'DM Mono',monospace"><?= fmtNum($r['avg_post_boil'], 3) ?></td>
              <td style="color:var(--text-muted)"><?= (int)$r['lotes'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <p style="color:var(--text-muted);font-size:.85rem">Sin datos de log de cocción.</p>
      <?php endif; ?>
    </div>

    <!-- Fallas más frecuentes -->
    <div class="card fade-in">
      <div class="card-title">Fallas más frecuentes detectadas en cata</div>
      <?php if ($fallas_top):
        $max_f = max($fallas_top);
        foreach ($fallas_top as $falla => $count):
          $pct = $max_f > 0 ? ($count / $max_f) * 100 : 0;
      ?>
      <div class="bar-wrap">
        <span class="bar-label" title="<?= e(ucfirst($falla)) ?>"><?= e(ucfirst($falla)) ?></span>
        <div class="bar-track"><div class="bar-fill" style="width:<?= $pct ?>%;background:var(--color-danger)"></div></div>
        <span class="bar-val" style="color:var(--color-danger)"><?= $count ?> veces</span>
      </div>
      <?php endforeach; else: ?>
        <p style="color:var(--text-muted);font-size:.85rem">Sin fallas registradas en cata.</p>
      <?php endif; ?>
    </div>

  </div>

</div>

<style>
  .mono { font-family:'DM Mono',monospace; font-size:.78rem; }
</style>

<script>
function loadContent(page) { window.location.href = page; }
</script>
</body>
</html>
