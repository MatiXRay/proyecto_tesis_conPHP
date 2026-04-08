<?php
/**
 * BIALYSTOK BREWING CO — Añadir reporte de agua
 * Reemplaza: anadir_reporte_h2o.php
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }

$menu_activo = 'reportes_agua';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nuevo reporte H₂O · Bialystok Brewing</title>
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
      <h1>Nuevo reporte H₂O</h1>
      <p class="page-subtitle">Análisis de agua</p>
    </div>
    <a href="reportes_agua" class="btn btn-ghost btn-sm">← Volver</a>
  </div>

  <form action="guardar_reporte_h2o" method="POST">
    <?= csrfField() ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">

      <div class="card fade-in">
        <div class="card-title">Información del reporte</div>
        <div class="form-group">
          <label class="form-label">Fecha *</label>
          <input type="date" name="fecha" required value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Laboratorio *</label>
          <input type="text" name="laboratorio" placeholder="Ej: Lab. Central" required>
        </div>
        <div class="form-group">
          <label class="form-label">Origen *</label>
          <select name="origen" required>
            <option value="RED">RED</option>
            <option value="OSMOSIS">OSMOSIS</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">pH</label>
          <input type="text" name="ph" pattern="\d+(\.\d{1,2})?" placeholder="7.2">
        </div>
        <div class="form-group">
          <label class="form-label">Dureza</label>
          <input type="text" inputmode="decimal" name="dureza" placeholder="0">
        </div>
        <div class="form-group">
          <label class="form-label">Alcalinidad</label>
          <input type="text" inputmode="decimal" name="alcalinidad" placeholder="0">
        </div>
      </div>

      <div class="card fade-in">
        <div class="card-title">Parámetros iónicos (ppm)</div>
        <div class="form-group">
          <label class="form-label">Calcio (Ca²⁺)</label>
          <input type="text" inputmode="decimal" name="calcio" placeholder="0">
        </div>
        <div class="form-group">
          <label class="form-label">Magnesio (Mg²⁺)</label>
          <input type="text" inputmode="decimal" name="magnesio" placeholder="0">
        </div>
        <div class="form-group">
          <label class="form-label">Sodio (Na⁺)</label>
          <input type="text" inputmode="decimal" name="sodio" placeholder="0">
        </div>
        <div class="form-group">
          <label class="form-label">Cloruro (Cl⁻)</label>
          <input type="text" inputmode="decimal" name="cloruro" placeholder="0">
        </div>
        <div class="form-group">
          <label class="form-label">Sulfato (SO₄²⁻)</label>
          <input type="text" inputmode="decimal" name="sulfato" placeholder="0">
        </div>
        <div class="form-group">
          <label class="form-label">Carbonato (CO₃²⁻)</label>
          <input type="text" inputmode="decimal" name="carbonato" placeholder="0">
        </div>
        <div class="form-group">
          <label class="form-label">Bicarbonato (HCO₃⁻)</label>
          <input type="text" inputmode="decimal" name="bicarbonato" placeholder="0">
        </div>
        <div class="form-group">
          <label class="form-label">Nitrato (NO₃⁻)</label>
          <input type="text" inputmode="decimal" name="nitrato" placeholder="0">
        </div>
        <div class="form-group">
          <label class="form-label">Nitrito (NO₂⁻)</label>
          <input type="text" inputmode="decimal" name="nitrito" placeholder="0">
        </div>
      </div>

    </div>

    <div style="display:flex;gap:.75rem;margin-top:1rem;margin-bottom:2rem" class="fade-in">
      <button type="submit" class="btn btn-primary btn-lg"
              onclick="return confirm('¿Guardar este reporte?')">Guardar reporte</button>
      <a href="reportes_agua" class="btn btn-ghost btn-lg">Cancelar</a>
    </div>
  </form>
</div>

<script>function loadContent(page) { window.location.href = page; }</script>
</body>
</html>
