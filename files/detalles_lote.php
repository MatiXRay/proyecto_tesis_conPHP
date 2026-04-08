<?php
/**
 * BIALYSTOK BREWING CO — Detalle de lote
 * Reemplaza: detalles_lote.php
 *
 * Correcciones:
 *  - $lote_id = $_GET['id_lote'] sin validar → getIntParam() con redirect si inválido
 *  - SQL injection en TODAS las queries (usaban $lote_id directamente interpolado)
 *  - obtenerNombreEstilo() usaba SQL injection internamente → eliminada, JOIN directo
 *  - XSS: todos los echo usan e()
 *  - Queries duplicadas eliminadas (el original hacía 2x la misma query)
 *  - Diseño: nuevo sistema CSS con secciones colapsables
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }

require_once 'conexion.php';

$menu_activo = 'lotes';

// ── Validar ID del lote ───────────────────────────────────────────────────────
$lote_id = getIntParam('id_lote');
if ($lote_id === null) {
    header('Location: lotes?error=' . urlencode('ID de lote inválido.'));
    exit;
}

// ── Queries ───────────────────────────────────────────────────────────────────
try {
    $pdo = getPDO();

    // Datos principales del lote
    $stmt = $pdo->prepare(
        "SELECT lc.*,
                ec.nombre AS estilo_nombre,
                f.nombre  AS fermentador_nombre
         FROM lotes_cerveza lc
         LEFT JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
         LEFT JOIN fermentadores   f  ON lc.fermentador_id = f.id
         WHERE lc.id = ?"
    );
    $stmt->execute([$lote_id]);
    $lote = $stmt->fetch();

    if (!$lote) {
        header('Location: lotes?error=' . urlencode('Lote no encontrado.'));
        exit;
    }

    // Maltas
    $stmt = $pdo->prepare(
        "SELECT lm.cantidad, lm.tiempo, lm.lote_malta,
                vm.nombre AS nombre_malta, vm.marca AS marca_malta
         FROM lotes_maltas lm
         INNER JOIN variedades_malta vm ON lm.malta_id = vm.id
         WHERE lm.lote_id = ?"
    );
    $stmt->execute([$lote_id]);
    $maltas = $stmt->fetchAll();

    // Lúpulos
    $stmt = $pdo->prepare(
        "SELECT ll.cantidad, ll.tiempo, ll.ibu, ll.lote_lupulo,
                vl.nombre AS nombre_lupulo, vl.marca
         FROM lotes_lupulos ll
         INNER JOIN variedades_lupulo vl ON ll.lupulo_id = vl.id
         WHERE ll.lote_id = ?"
    );
    $stmt->execute([$lote_id]);
    $lupulos = $stmt->fetchAll();

    // Levadura
    $stmt = $pdo->prepare(
        "SELECT ll.gen, ll.temp_inoculacion, ll.tasa_inoculacion,
                ll.viabilidad, ll.kilos_biomasa, ll.oxigenacion,
                cl.cepa AS nombre_levadura, cl.marca AS marca_levadura
         FROM lotes_levaduras ll
         INNER JOIN cepas_levadura cl ON ll.cepa_id = cl.id
         WHERE ll.lote_id = ?"
    );
    $stmt->execute([$lote_id]);
    $levadura = $stmt->fetch();

    // Tratamiento agua Mash/Sparge
    $stmt = $pdo->prepare(
        "SELECT * FROM tratamiento_agua_mash_sparge WHERE lote_id = ?"
    );
    $stmt->execute([$lote_id]);
    $aguas = $stmt->fetchAll();

    // Batches (log del día de cocción)
    $stmt = $pdo->prepare(
        "SELECT * FROM batches WHERE lote_id = ? ORDER BY id"
    );
    $stmt->execute([$lote_id]);
    $batches = $stmt->fetchAll();

    // Seguimiento de fermentación
    $stmt = $pdo->prepare(
        "SELECT * FROM seguimiento_fermentacion WHERE lote_id = ? ORDER BY fecha, hora"
    );
    $stmt->execute([$lote_id]);
    $seguimiento = $stmt->fetchAll();

    // Reportes de agua asociados
    $reporteRED  = $lote['reporteRED']  ?? null;
    $reporteOSMO = $lote['reporteOSMO'] ?? null;

} catch (PDOException $ex) {
    error_log('[Bialystok detalle_lote] ' . $ex->getMessage());
    header('Location: lotes?error=' . urlencode('Error al cargar el lote.'));
    exit;
}

$fecha_elab   = $lote['fecha_elaboracion'] ? date('d/m/Y', strtotime($lote['fecha_elaboracion'])) : '—';
$dia_envasado = (!empty($lote['dia_envasado']) && $lote['dia_envasado'] !== '0000-00-00')
    ? date('d/m/Y', strtotime($lote['dia_envasado'])) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lote <?= e(strtoupper($lote['numero_lote'] ?? '')) ?> · Bialystok Brewing</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/bialy-design-system.css">
</head>
<body>

<?php require 'menu.php'; ?>
<?php require 'info_user.php'; ?>

<div id="contenido" class="main-content">

  <!-- ── Header ───────────────────────────────────────────────────────────── -->
  <div class="page-header fade-in">
    <div>
      <h1><?= e(strtoupper($lote['numero_lote'] ?? 'Sin número')) ?></h1>
      <p class="page-subtitle">
        <?= e($lote['estilo_nombre'] ?? '—') ?>
        &nbsp;·&nbsp; <?= e($fecha_elab) ?>
        <?php if ($lote['fermentador_nombre']): ?>
          &nbsp;·&nbsp; <?= e($lote['fermentador_nombre']) ?>
        <?php endif; ?>
      </p>
    </div>
    <div style="display:flex;gap:.5rem">
      <button class="btn btn-secondary btn-sm" onclick="window.location.href='planilla_cata?id=<?= $lote_id ?>'">
        Ver notas de cata
      </button>
      <a href="lotes" class="btn btn-ghost btn-sm">← Volver</a>
    </div>
  </div>

  <!-- ── Grid principal ────────────────────────────────────────────────────── -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem" class="fade-in">

    <!-- Parámetros vitales -->
    <div class="card">
      <div class="card-title">Parámetros vitales esperados</div>
      <div class="tabla-containerpv">
        <table>
          <tbody>
            <tr><td>OG</td><td><?= e($lote['og'] ?? '—') ?></td></tr>
            <tr><td>FG</td><td><?= e($lote['fg'] ?? '—') ?></td></tr>
            <tr><td>IBU</td><td><?= e($lote['ibu'] ?? '—') ?></td></tr>
            <tr><td>ABV</td><td><?= $lote['abv'] ? e($lote['abv']) . ' %' : '—' ?></td></tr>
            <tr><td>CO₂ / Carb.</td><td><?= e($lote['co2'] ?? '—') ?></td></tr>
            <?php if ($dia_envasado): ?>
            <tr><td>Día envasado</td><td><?= e($dia_envasado) ?></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Parámetros de agua de la receta -->
    <div class="card">
      <div class="card-title">Perfil de H₂O de la receta</div>
      <div class="tabla-containerpv">
        <table>
          <tbody>
            <tr><td>Calcio Ca²⁺</td><td><?= e($lote['ca_mas_2'] ?? '—') ?> ppm</td></tr>
            <tr><td>Magnesio Mg²⁺</td><td><?= e($lote['mg_mas_2'] ?? '—') ?> ppm</td></tr>
            <tr><td>Sodio Na⁺</td><td><?= e($lote['na_mas_2'] ?? '—') ?> ppm</td></tr>
            <tr><td>Cloruro Cl⁻</td><td><?= e($lote['cl_menos'] ?? '—') ?> ppm</td></tr>
            <tr><td>Sulfato SO₄²⁻</td><td><?= e($lote['so04_menos_2'] ?? '—') ?> ppm</td></tr>
          </tbody>
        </table>
      </div>
      <?php if ($reporteRED || $reporteOSMO): ?>
      <div style="margin-top:.75rem;display:flex;gap:.5rem">
        <?php if ($reporteRED): ?>
          <button class="btn btn-ghost btn-sm"
                  onclick="window.location.href='detalle_reporteh2o?id=<?= (int)$reporteRED ?>'">
            RED
          </button>
        <?php endif; ?>
        <?php if ($reporteOSMO): ?>
          <button class="btn btn-ghost btn-sm"
                  onclick="window.location.href='detalle_reporteh2o?id=<?= (int)$reporteOSMO ?>'">
            Ósmosis
          </button>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── Maltas ─────────────────────────────────────────────────────────────── -->
  <div class="card fade-in" style="margin-bottom:1rem">
    <div class="card-title">Maltas</div>
    <?php if ($maltas): ?>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Variedad</th><th>Marca</th><th>N° Lote malta</th><th>Cantidad</th><th>Uso / Tiempo</th></tr></thead>
        <tbody>
          <?php foreach ($maltas as $m): ?>
          <tr>
            <td><?= e(ucwords(strtolower($m['nombre_malta']))) ?></td>
            <td style="color:var(--text-muted)"><?= e(ucwords(strtolower($m['marca_malta']))) ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e(strtoupper($m['lote_malta'] ?? '')) ?></td>
            <td style="font-family:'DM Mono',monospace"><?= e($m['cantidad'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e(strtoupper($m['tiempo'] ?? '—')) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <p style="color:var(--text-muted);font-size:.85rem">Sin maltas registradas para este lote.</p>
    <?php endif; ?>
  </div>

  <!-- ── Lúpulos ────────────────────────────────────────────────────────────── -->
  <div class="card fade-in" style="margin-bottom:1rem">
    <div class="card-title">Lúpulos</div>
    <?php if ($lupulos): ?>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Variedad</th><th>Marca</th><th>N° Lote lúpulo</th><th>Cantidad (g)</th><th>IBU</th><th>Tiempo / Técnica</th></tr></thead>
        <tbody>
          <?php foreach ($lupulos as $l): ?>
          <tr>
            <td><?= e($l['nombre_lupulo']) ?></td>
            <td style="color:var(--text-muted)"><?= e($l['marca']) ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e(strtolower($l['lote_lupulo'] ?? '')) ?></td>
            <td style="font-family:'DM Mono',monospace"><?= e($l['cantidad'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace"><?= e($l['ibu'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e(strtoupper($l['tiempo'] ?? '—')) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <p style="color:var(--text-muted);font-size:.85rem">Sin lúpulos registrados para este lote.</p>
    <?php endif; ?>
  </div>

  <!-- ── Levadura ───────────────────────────────────────────────────────────── -->
  <?php if ($levadura): ?>
  <div class="card fade-in" style="margin-bottom:1rem">
    <div class="card-title">Levadura</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
      <div class="tabla-containerpv">
        <table>
          <tr><td>Cepa</td><td><?= e($levadura['nombre_levadura']) ?></td></tr>
          <tr><td>Marca</td><td><?= e($levadura['marca_levadura']) ?></td></tr>
          <tr><td>Generación</td><td><?= e($levadura['gen'] ?? '—') ?></td></tr>
          <tr><td>Temp. inoculación</td><td><?= e($levadura['temp_inoculacion'] ?? '—') ?> °C</td></tr>
        </table>
      </div>
      <div class="tabla-containerpv">
        <table>
          <tr><td>Tasa inoculación</td><td><?= e($levadura['tasa_inoculacion'] ?? '—') ?></td></tr>
          <tr><td>Viabilidad</td><td><?= e($levadura['viabilidad'] ?? '—') ?></td></tr>
          <tr><td>Biomasa</td><td><?= e($levadura['kilos_biomasa'] ?? '—') ?> kg</td></tr>
          <tr><td>Oxigenación</td><td><?= e($levadura['oxigenacion'] ?? '—') ?> ppm</td></tr>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Tratamiento agua ───────────────────────────────────────────────────── -->
  <?php if ($aguas): foreach ($aguas as $agua): ?>
  <div class="card fade-in" style="margin-bottom:1rem">
    <div class="card-title">Tratamiento H₂O Mash</div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Total</th><th>% RO</th><th>Temp</th><th>pH</th><th>Fosfórico</th><th>CaSO₄</th><th>CaCl₂</th><th>MgCl₂</th><th>Otro</th></tr></thead>
        <tbody>
          <tr>
            <td style="font-family:'DM Mono',monospace"><?= e($agua['total_agua_mash'] ?? '—') ?> L</td>
            <td style="font-family:'DM Mono',monospace"><?= e($agua['porcentaje_ro_mash'] ?? '—') ?>%</td>
            <td style="font-family:'DM Mono',monospace"><?= e($agua['temperatura_mash'] ?? '—') ?>°C</td>
            <td style="font-family:'DM Mono',monospace"><?= e($agua['ph_mash'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace"><?= e($agua['fosforico_mash'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace"><?= e($agua['caso4_mash'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace"><?= e($agua['cacl2_mash'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace"><?= e($agua['mgcl_mash'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace"><?= e($agua['otro_mash'] ?? '—') ?></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="card-title" style="margin-top:1rem">Tratamiento H₂O Sparge</div>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Total</th><th>% RO</th><th>Temp</th><th>pH</th><th>Fosfórico</th><th>CaSO₄</th><th>CaCl₂</th><th>MgCl</th><th>Otro</th></tr></thead>
        <tbody>
          <tr>
            <td style="font-family:'DM Mono',monospace"><?= e($agua['total_agua_sparge'] ?? '—') ?> L</td>
            <td style="font-family:'DM Mono',monospace"><?= e($agua['porcentaje_ro_sparge'] ?? '—') ?>%</td>
            <td style="font-family:'DM Mono',monospace"><?= e($agua['temperatura_sparge'] ?? '—') ?>°C</td>
            <td style="font-family:'DM Mono',monospace"><?= e($agua['ph_sparge'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace"><?= e($agua['fosforico_sparge'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace"><?= e($agua['caso4_sparge'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace"><?= e($agua['cacl2_sparge'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace"><?= e($agua['mgcl_sparge'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace"><?= e($agua['otro_sparge'] ?? '—') ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; endif; ?>

  <!-- ── Batches / Log del día de cocción ──────────────────────────────────── -->
  <?php if ($batches): ?>
  <div class="card fade-in" style="margin-bottom:1rem">
    <div class="card-title">LOG del día de cocción</div>
    <div class="table-wrapper" style="overflow-x:auto">
      <table style="min-width:900px">
        <thead>
          <tr>
            <th>Batch</th>
            <th>T° Mash 1</th><th>pH Mash 1</th>
            <th>T° Mash 2</th><th>pH Mash 2</th>
            <th>T° Mash 3</th><th>pH Mash 3</th>
            <th>Dens 1° Mosto</th><th>Dens Last Run</th><th>pH Last Run</th>
            <th>T° Sparge</th><th>pH Sparge</th>
            <th>Vol ini Boil</th><th>Dens pre-Boil</th><th>pH ini Boil</th>
            <th>Vol fin Boil</th><th>Dens post-Boil</th><th>pH fin</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($batches as $i => $b): ?>
          <tr>
            <td style="font-family:'DM Mono',monospace;font-weight:600"><?= $i + 1 ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($b['temp_mash'] ?? '—') ?>°C</td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($b['ph_mash'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($b['temp2_mash'] ?? '—') ?>°C</td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($b['ph2_mash'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($b['temp3_mash'] ?? '—') ?>°C</td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($b['ph3_mash'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($b['dens_primer_mosto'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($b['dens_last_run'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($b['ph_last_run'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($b['temp_sparge'] ?? '—') ?>°C</td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($b['ph_sparge'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($b['vol_inicial_boil'] ?? '—') ?> L</td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($b['dens_pre_boil'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($b['ph_inicio_boil'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($b['vol_final_boil'] ?? '—') ?> L</td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($b['dens_post_boil'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e($b['ph_fin'] ?? '—') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Seguimiento de fermentación ───────────────────────────────────────── -->
  <?php if ($seguimiento): ?>
  <div class="card fade-in" style="margin-bottom:1rem">
    <div class="card-title">Seguimiento de fermentación</div>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr><th>#</th><th>Fecha</th><th>Hora</th><th>Densidad</th><th>pH</th><th>Temperatura</th><th>Purga</th><th>Comentarios</th></tr>
        </thead>
        <tbody>
          <?php foreach ($seguimiento as $i => $sf): ?>
          <tr>
            <td style="font-family:'DM Mono',monospace;color:var(--text-muted)"><?= $i + 1 ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e(date('d/m', strtotime($sf['fecha']))) ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem"><?= e(date('H:i', strtotime($sf['hora']))) ?></td>
            <td style="font-family:'DM Mono',monospace;color:var(--text-amber)"><?= e($sf['densidad'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace"><?= e($sf['ph'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace"><?= e($sf['temperatura'] ?? '—') ?>°C</td>
            <td style="font-size:.82rem;color:var(--text-muted)"><?= e($sf['purga'] ?? '—') ?></td>
            <td style="font-size:.8rem;color:var(--text-secondary)"><?= e($sf['comentarios'] ?? '') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <div style="margin-bottom:2rem">
    <a href="lotes" class="btn btn-ghost">← Volver a lotes</a>
  </div>

</div><!-- /contenido -->

<script>
function loadContent(page) { window.location.href = page; }
</script>
</body>
</html>
