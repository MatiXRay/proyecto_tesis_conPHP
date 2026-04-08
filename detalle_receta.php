<?php
/**
 * BIALYSTOK BREWING CO — Detalle de receta / estilo
 * Reemplaza: detalle_receta.php
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }
require_once 'conexion.php';

$menu_activo = 'recetas';

$estilo_id = getIntParam('id_receta');
if ($estilo_id === null) { header('Location: recetas'); exit; }

try {
    $pdo = getPDO();

    $stmt = $pdo->prepare(
        "SELECT r.*, e.nombre, e.descripcion, e.duracion_dias
         FROM recetas_estilos r
         JOIN estilos_cerveza e ON r.estilo_id = e.id
         WHERE r.estilo_id = ?"
    );
    $stmt->execute([$estilo_id]);
    $receta = $stmt->fetch();

    if (!$receta) { header('Location: recetas?error=no_encontrado'); exit; }

    // Maltas
    $maltas = $pdo->prepare(
        "SELECT rm.cantidad, rm.tiempo, vm.nombre, vm.marca
         FROM recetasmalta rm JOIN variedades_malta vm ON rm.malta_id = vm.id
         WHERE rm.id_receta = ? ORDER BY vm.nombre"
    );
    $maltas->execute([$receta['id']]);
    $maltas = $maltas->fetchAll();

    // Lúpulos
    $lupulos = $pdo->prepare(
        "SELECT rl.cantidad, rl.ibu, rl.tiempo, vl.nombre, vl.marca
         FROM recetaslupulo rl JOIN variedades_lupulo vl ON rl.lupulo_id = vl.id
         WHERE rl.id_receta = ? ORDER BY vl.nombre"
    );
    $lupulos->execute([$receta['id']]);
    $lupulos = $lupulos->fetchAll();

    // Levadura
    $levadura = $pdo->prepare(
        "SELECT rl.*, cl.cepa, cl.marca
         FROM recetaslevadura rl JOIN cepas_levadura cl ON rl.cepa_id = cl.id
         WHERE rl.id_receta = ? LIMIT 1"
    );
    $levadura->execute([$receta['id']]);
    $levadura = $levadura->fetch();

    // Lotes elaborados con este estilo
    $lotes = $pdo->prepare(
        "SELECT id, numero_lote, fecha_elaboracion, litros_envasados
         FROM lotes_cerveza WHERE estilo_id = ? ORDER BY fecha_elaboracion DESC LIMIT 10"
    );
    $lotes->execute([$estilo_id]);
    $lotes = $lotes->fetchAll();

} catch (PDOException $ex) {
    error_log('[Bialystok detalle_receta] ' . $ex->getMessage());
    header('Location: recetas'); exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($receta['nombre']) ?> · Bialystok Brewing</title>
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
      <h1><?= e($receta['nombre']) ?></h1>
      <p class="page-subtitle"><?= e($receta['descripcion'] ?: '—') ?></p>
    </div>
    <a href="recetas" class="btn btn-ghost btn-sm">← Volver</a>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">

    <!-- Parámetros vitales -->
    <div class="card fade-in">
      <div class="card-title">Parámetros vitales esperados</div>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem">
        <?php foreach (['og'=>'OG','fg'=>'FG','ibu'=>'IBU','abv'=>'ABV','carb_level'=>'Carb level','duracion_dias'=>'Días est.'] as $col => $label): ?>
        <div class="stat-card" style="padding:.75rem">
          <div style="font-size:.65rem;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:.2rem"><?= $label ?></div>
          <div style="font-family:'DM Mono',monospace;font-size:1rem;font-weight:600;color:var(--text-amber)"><?= e($receta[$col] ?? '—') ?><?= $col==='abv' ? '%' : '' ?><?= $col==='duracion_dias' ? ' días' : '' ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Perfil H2O -->
    <div class="card fade-in">
      <div class="card-title">Perfil H₂O</div>
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.5rem">
        <?php foreach (['ca_mas_2'=>'Ca²⁺','mg_mas_2'=>'Mg²⁺','na_mas_2'=>'Na⁺','cl_menos'=>'Cl⁻','so04_menos_2'=>'SO₄²⁻'] as $col => $label): ?>
        <div style="background:var(--color-surface-2);border-radius:6px;padding:.5rem .75rem">
          <div style="font-size:.65rem;color:var(--text-muted)"><?= $label ?></div>
          <div style="font-family:'DM Mono',monospace;font-weight:500"><?= e($receta[$col] ?? '—') ?> ppm</div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Maltas -->
    <div class="card fade-in">
      <div class="card-title">Maltas</div>
      <?php if ($maltas): ?>
      <div class="table-wrapper">
        <table style="font-size:.82rem">
          <thead><tr><th>Variedad</th><th>Marca</th><th>Cantidad</th><th>Uso</th></tr></thead>
          <tbody>
            <?php foreach ($maltas as $m): ?>
            <tr>
              <td style="font-weight:500"><?= e($m['nombre']) ?></td>
              <td style="color:var(--text-muted)"><?= e($m['marca']) ?></td>
              <td style="font-family:'DM Mono',monospace"><?= e($m['cantidad']) ?> kg</td>
              <td style="color:var(--text-muted)"><?= e($m['tiempo'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?><p style="color:var(--text-muted);font-size:.85rem">Sin maltas cargadas.</p><?php endif; ?>
    </div>

    <!-- Lúpulos -->
    <div class="card fade-in">
      <div class="card-title">Lúpulos</div>
      <?php if ($lupulos): ?>
      <div class="table-wrapper">
        <table style="font-size:.82rem">
          <thead><tr><th>Variedad</th><th>Marca</th><th>Cantidad</th><th>IBU</th><th>Técnica</th></tr></thead>
          <tbody>
            <?php foreach ($lupulos as $l): ?>
            <tr>
              <td style="font-weight:500"><?= e($l['nombre']) ?></td>
              <td style="color:var(--text-muted)"><?= e($l['marca']) ?></td>
              <td style="font-family:'DM Mono',monospace"><?= e($l['cantidad']) ?> g</td>
              <td style="font-family:'DM Mono',monospace"><?= e($l['ibu'] ?? '—') ?></td>
              <td style="color:var(--text-muted)"><?= e($l['tiempo'] ?? '—') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?><p style="color:var(--text-muted);font-size:.85rem">Sin lúpulos cargados.</p><?php endif; ?>
    </div>

    <!-- Levadura -->
    <?php if ($levadura): ?>
    <div class="card fade-in">
      <div class="card-title">Levadura</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
        <div style="background:var(--color-surface-2);border-radius:6px;padding:.5rem .75rem">
          <div style="font-size:.65rem;color:var(--text-muted)">Cepa</div>
          <div style="font-weight:500"><?= e($levadura['cepa']) ?> (<?= e($levadura['marca']) ?>)</div>
        </div>
        <?php foreach (['temp_inoculacion'=>'Temp. inoculación','tasa_inoculacion'=>'Tasa inoculación','viabilidad'=>'Viabilidad','kilos_biomasa'=>'Biomasa (kg)','oxigenacion'=>'PPM O₂'] as $col => $label): ?>
        <div style="background:var(--color-surface-2);border-radius:6px;padding:.5rem .75rem">
          <div style="font-size:.65rem;color:var(--text-muted)"><?= $label ?></div>
          <div style="font-family:'DM Mono',monospace"><?= e($levadura[$col] ?? '—') ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Lotes elaborados -->
    <div class="card fade-in">
      <div class="card-title">Últimos lotes elaborados (<?= count($lotes) ?>)</div>
      <?php if ($lotes): ?>
      <div class="table-wrapper">
        <table style="font-size:.82rem">
          <thead><tr><th>N° Lote</th><th>Fecha</th><th>Litros env.</th></tr></thead>
          <tbody>
            <?php foreach ($lotes as $l): ?>
            <tr style="cursor:pointer" onclick="window.location.href='detalles_lote?id=<?= (int)$l['id'] ?>'">
              <td style="font-family:'DM Mono',monospace;font-weight:500"><?= e(strtoupper($l['numero_lote'])) ?></td>
              <td style="color:var(--text-muted)"><?= e(date('d/m/Y', strtotime($l['fecha_elaboracion']))) ?></td>
              <td style="font-family:'DM Mono',monospace;color:var(--text-amber)"><?= $l['litros_envasados'] ? e($l['litros_envasados']).' L' : '—' ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?><p style="color:var(--text-muted);font-size:.85rem">Ningún lote elaborado con este estilo todavía.</p><?php endif; ?>
    </div>

  </div>
</div>

<script>function loadContent(page) { window.location.href = page; }</script>
</body>
</html>
