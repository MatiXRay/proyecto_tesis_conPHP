<?php
/**
 * BIALYSTOK BREWING CO — Detalle de reporte de agua
 * Reemplaza: detalle_reporteh2o.php
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }
require_once 'conexion.php';

$menu_activo = 'reportes_agua';

$id = getIntParam('id');
if ($id === null) { header('Location: reportes_agua'); exit; }

try {
    $pdo  = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM reportesagua WHERE id = ?");
    $stmt->execute([$id]);
    $reporte = $stmt->fetch();
    if (!$reporte) { header('Location: reportes_agua?error=no_encontrado'); exit; }
} catch (PDOException $ex) {
    error_log('[Bialystok detalle_h2o] ' . $ex->getMessage());
    header('Location: reportes_agua'); exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reporte H₂O · Bialystok Brewing</title>
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
      <h1>Reporte H₂O</h1>
      <p class="page-subtitle">
        <?= e(date('d/m/Y', strtotime($reporte['fecha']))) ?> ·
        <?= e($reporte['origen'] ?? '—') ?> ·
        <?= e($reporte['laboratorio'] ?? '—') ?>
      </p>
    </div>
    <a href="reportes_agua" class="btn btn-ghost btn-sm">← Volver</a>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem" class="fade-in">

    <div class="card">
      <div class="card-title">Parámetros principales</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
        <?php
        $params = [
          'ph'          => 'pH',
          'calcio'      => 'Ca²⁺ (ppm)',
          'magnesio'    => 'Mg²⁺ (ppm)',
          'sodio'       => 'Na⁺ (ppm)',
          'cloruro'     => 'Cl⁻ (ppm)',
          'sulfato'     => 'SO₄²⁻ (ppm)',
          'carbonato'   => 'CO₃²⁻ (ppm)',
          'bicarbonato' => 'HCO₃⁻ (ppm)',
        ];
        foreach ($params as $col => $label):
        ?>
        <div style="background:var(--color-surface-2);border-radius:6px;padding:.55rem .75rem">
          <div style="font-size:.65rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em"><?= $label ?></div>
          <div style="font-family:'DM Mono',monospace;font-size:1rem;font-weight:500;color:var(--text-amber)"><?= e($reporte[$col] ?? '—') ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-title">Dureza y alcalinidad</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:1rem">
        <div style="background:var(--color-surface-2);border-radius:6px;padding:.55rem .75rem">
          <div style="font-size:.65rem;color:var(--text-muted)">Dureza</div>
          <div style="font-family:'DM Mono',monospace;font-size:1rem;font-weight:500;color:var(--text-amber)"><?= e($reporte['dureza'] ?? '—') ?></div>
        </div>
        <div style="background:var(--color-surface-2);border-radius:6px;padding:.55rem .75rem">
          <div style="font-size:.65rem;color:var(--text-muted)">Alcalinidad</div>
          <div style="font-family:'DM Mono',monospace;font-size:1rem;font-weight:500;color:var(--text-amber)"><?= e($reporte['alcalinidad'] ?? '—') ?></div>
        </div>
      </div>

      <div class="card-title">Nitratos y nitritos</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem">
        <div style="background:var(--color-surface-2);border-radius:6px;padding:.55rem .75rem">
          <div style="font-size:.65rem;color:var(--text-muted)">NO₃⁻ (Nitrato)</div>
          <div style="font-family:'DM Mono',monospace;font-weight:500"><?= e($reporte['nitrato'] ?? '—') ?></div>
        </div>
        <div style="background:var(--color-surface-2);border-radius:6px;padding:.55rem .75rem">
          <div style="font-size:.65rem;color:var(--text-muted)">NO₂⁻ (Nitrito)</div>
          <div style="font-family:'DM Mono',monospace;font-weight:500"><?= e($reporte['nitrito'] ?? '—') ?></div>
        </div>
      </div>
    </div>

  </div>
</div>

<script>function loadContent(page) { window.location.href = page; }</script>
</body>
</html>
