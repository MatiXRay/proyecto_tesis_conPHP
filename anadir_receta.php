<?php
/**
 * BIALYSTOK BREWING CO — Añadir nueva receta / estilo
 * Reemplaza: anadir_receta.php
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }
require_once 'conexion.php';

$menu_activo = 'recetas';

try {
    $pdo     = getPDO();
    $maltas  = $pdo->query("SELECT id, nombre, marca FROM variedades_malta ORDER BY nombre")->fetchAll();
    $lupulos = $pdo->query("SELECT id, nombre, marca FROM variedades_lupulo ORDER BY nombre")->fetchAll();
    $cepas   = $pdo->query("SELECT id, cepa, marca FROM cepas_levadura ORDER BY cepa")->fetchAll();
} catch (PDOException $ex) {
    error_log('[Bialystok anadir_receta] ' . $ex->getMessage());
    $maltas = $lupulos = $cepas = [];
}

$maltas_json  = json_encode(array_map(fn($m) => ['id'=>(int)$m['id'],'label'=>$m['nombre'].' ('.$m['marca'].')'], $maltas));
$lupulos_json = json_encode(array_map(fn($l) => ['id'=>(int)$l['id'],'label'=>$l['nombre'].' ('.$l['marca'].')'], $lupulos));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nueva receta · Bialystok Brewing</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/bialy-design-system.css">
  <style>
    .tabla-form { width:100%; border-collapse:collapse; }
    .tabla-form th { background:var(--color-surface-2); color:var(--text-muted); font-size:.68rem; font-weight:600; letter-spacing:.1em; text-transform:uppercase; padding:.5rem .75rem; text-align:left; border-bottom:1px solid var(--color-border); }
    .tabla-form td { padding:.35rem .5rem; border-bottom:1px solid rgba(255,255,255,.04); vertical-align:middle; }
    .tabla-form input, .tabla-form select { width:100%; padding:.3rem .5rem; font-size:.82rem; background:var(--color-surface-3); border:1px solid var(--color-border-md); border-radius:4px; color:var(--text-primary); }
    .tabla-form input:focus, .tabla-form select:focus { outline:none; border-color:var(--amber-400); }
    .inp-sm { width:80px !important; }
  </style>
</head>
<body>
<?php require 'menu.php'; ?>
<?php require 'info_user.php'; ?>

<div id="contenido" class="main-content">
  <div class="page-header fade-in">
    <div>
      <h1>Nueva receta</h1>
      <p class="page-subtitle">Crear un nuevo estilo / receta base</p>
    </div>
    <a href="recetas" class="btn btn-ghost btn-sm">← Volver</a>
  </div>

  <form action="guardar_estilo" method="POST" id="formReceta">
    <?= csrfField() ?>

    <!-- Estilo -->
    <div class="card fade-in" style="margin-bottom:1rem">
      <div class="card-title">Estilo</div>
      <div style="display:grid;grid-template-columns:1fr 2fr;gap:.75rem">
        <div class="form-group">
          <label class="form-label">Nombre *</label>
          <input type="text" name="estilo" placeholder="Ej: NEIPA" required>
        </div>
        <div class="form-group">
          <label class="form-label">Descripción *</label>
          <input type="text" name="descripcion" placeholder="Descripción del estilo" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Duración estimada (días cocción → envasado)</label>
        <input type="number" name="duracion_dias" value="21" min="1" max="365" style="width:100px">
      </div>
    </div>

    <!-- Parámetros vitales -->
    <div class="card fade-in" style="margin-bottom:1rem">
      <div class="card-title">Parámetros vitales esperados</div>
      <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:.75rem">
        <div class="form-group"><label class="form-label">OG</label><input type="text" name="og" pattern="\d+(\.\d{3})?" placeholder="1.060" required></div>
        <div class="form-group"><label class="form-label">FG</label><input type="text" name="fg" pattern="\d+(\.\d{3})?" placeholder="1.010" required></div>
        <div class="form-group"><label class="form-label">IBU</label><input type="text" name="ibuEsperado" placeholder="40" required></div>
        <div class="form-group"><label class="form-label">ABV %</label><input type="text" name="abvEsperado" placeholder="6.5" required></div>
        <div class="form-group"><label class="form-label">Carb level</label><input type="text" name="carbLevel" placeholder="2.5" required></div>
      </div>
    </div>

    <!-- H2O -->
    <div class="card fade-in" style="margin-bottom:1rem">
      <div class="card-title">Perfil de H₂O</div>
      <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:.75rem">
        <div class="form-group"><label class="form-label">Ca²⁺</label><input type="number" name="ca_mas_2" required></div>
        <div class="form-group"><label class="form-label">Mg²⁺</label><input type="number" name="mg_mas_2" required></div>
        <div class="form-group"><label class="form-label">Na⁺</label><input type="number" name="na_mas_2" required></div>
        <div class="form-group"><label class="form-label">Cl⁻</label><input type="number" name="cl_menos" required></div>
        <div class="form-group"><label class="form-label">SO₄²⁻</label><input type="number" name="so4_menos_2" required></div>
      </div>

      <div class="card-title" style="margin-top:1rem">Tratamiento H₂O Mash</div>
      <div class="table-wrapper">
        <table class="tabla-form">
          <thead><tr><th>Total (L)</th><th>% RO</th><th>Temp °C</th><th>pH</th><th>Fosfórico</th><th>CaSO₄</th><th>CaCl₂</th><th>MgCl₂</th><th>Otro</th><th>Fosfórico H₂O</th></tr></thead>
          <tbody><tr>
            <td><input type="number" name="total_agua_mash" required></td>
            <td><input type="number" name="porcentaje_ro_mash" required></td>
            <td><input type="number" name="temperatura_mash" required></td>
            <td><input type="text"   name="ph_mashh2o" class="inp-sm" required></td>
            <td><input type="number" name="fosforico_mash" required></td>
            <td><input type="number" name="caso4_mash" required></td>
            <td><input type="number" name="cacl2_mash" required></td>
            <td><input type="number" name="mgcl_mash" required></td>
            <td><input type="number" name="otro_mash" required></td>
            <td><input type="number" name="fosforico_h2o_mash" required></td>
          </tr></tbody>
        </table>
      </div>

      <div class="card-title" style="margin-top:1rem">Tratamiento H₂O Sparge</div>
      <div class="table-wrapper">
        <table class="tabla-form">
          <thead><tr><th>Total (L)</th><th>% RO</th><th>Temp °C</th><th>pH</th><th>Fosfórico</th><th>CaSO₄</th><th>CaCl₂</th><th>MgCl</th><th>Otro</th></tr></thead>
          <tbody><tr>
            <td><input type="number" name="total_agua_sparge" required></td>
            <td><input type="number" name="porcentaje_ro_sparge" required></td>
            <td><input type="number" name="temperatura_sparge" required></td>
            <td><input type="text"   name="ph_spargeh2o" class="inp-sm" required></td>
            <td><input type="number" name="fosforico_sparge" required></td>
            <td><input type="number" name="caso4_sparge" required></td>
            <td><input type="number" name="cacl2_sparge" required></td>
            <td><input type="number" name="mgcl_sparge" required></td>
            <td><input type="number" name="otro_sparge" required></td>
          </tr></tbody>
        </table>
      </div>
    </div>

    <!-- Maltas -->
    <div class="card fade-in" style="margin-bottom:1rem">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
        <div class="card-title" style="margin:0">Maltas</div>
        <button type="button" class="btn btn-ghost btn-sm" onclick="addMalta()">+ Añadir fila</button>
      </div>
      <div class="table-wrapper">
        <table class="tabla-form" id="maltas_table">
          <thead><tr><th>Variedad</th><th>Cantidad (kg)</th><th>Uso / Tiempo</th><th></th></tr></thead>
          <tbody>
            <tr>
              <td><select name="malta[]"><?php foreach ($maltas as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['nombre']) ?> (<?= e($m['marca']) ?>)</option><?php endforeach; ?></select></td>
              <td><input type="text" name="cantidad[]" class="inp-sm"></td>
              <td><input type="text" name="tiempo[]"></td>
              <td><button type="button" class="btn btn-danger btn-sm" onclick="delRow(this)">✕</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Lúpulos -->
    <div class="card fade-in" style="margin-bottom:1rem">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
        <div class="card-title" style="margin:0">Lúpulos</div>
        <button type="button" class="btn btn-ghost btn-sm" onclick="addLupulo()">+ Añadir fila</button>
      </div>
      <div class="table-wrapper">
        <table class="tabla-form" id="lupulo_table">
          <thead><tr><th>Variedad</th><th>Cantidad (g)</th><th>IBU</th><th>Tiempo / Técnica</th><th></th></tr></thead>
          <tbody>
            <tr>
              <td><select name="lupulo[]"><?php foreach ($lupulos as $l): ?><option value="<?= (int)$l['id'] ?>"><?= e($l['nombre']) ?> (<?= e($l['marca']) ?>)</option><?php endforeach; ?></select></td>
              <td><input type="number" name="cantidad_lupulo[]" class="inp-sm"></td>
              <td><input type="text"   name="ibu[]" class="inp-sm"></td>
              <td><input type="text"   name="tiempo_lupulo[]"></td>
              <td><button type="button" class="btn btn-danger btn-sm" onclick="delRow(this)">✕</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Levadura -->
    <div class="card fade-in" style="margin-bottom:1.5rem">
      <div class="card-title">Levadura</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
        <div>
          <div class="form-group">
            <label class="form-label">Cepa</label>
            <select name="cepa">
              <?php foreach ($cepas as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= e($c['cepa']) ?> (<?= e($c['marca']) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group"><label class="form-label">Temp. inoculación (°C)</label><input type="text" name="tempInoc"></div>
          <div class="form-group"><label class="form-label">Tasa de inoculación</label><input type="text" name="tasaInoc"></div>
        </div>
        <div>
          <div class="form-group"><label class="form-label">Viabilidad (%)</label><input type="number" name="viabilidad"></div>
          <div class="form-group"><label class="form-label">Kilos de biomasa</label><input type="text" name="biomasa"></div>
          <div class="form-group"><label class="form-label">PPM Oxígeno</label><input type="number" name="oxigenacion"></div>
        </div>
      </div>
    </div>

    <div style="display:flex;gap:.75rem;margin-bottom:2rem" class="fade-in">
      <button type="submit" class="btn btn-primary btn-lg"
              onclick="return confirm('¿Guardar esta receta?')">Guardar receta</button>
      <a href="recetas" class="btn btn-ghost btn-lg">Cancelar</a>
    </div>
  </form>
</div>

<script>
const MALTAS  = <?= $maltas_json ?>;
const LUPULOS = <?= $lupulos_json ?>;

function buildOpts(arr) {
  return arr.map(i => `<option value="${i.id}">${i.label.replace(/</g,'&lt;')}</option>`).join('');
}

function delRow(btn) {
  const tr = btn.closest('tr');
  if (tr.closest('tbody').rows.length > 1) tr.remove();
}

function addMalta() {
  const tb = document.querySelector('#maltas_table tbody');
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><select name="malta[]">${buildOpts(MALTAS)}</select></td>
    <td><input type="text" name="cantidad[]" class="inp-sm"></td>
    <td><input type="text" name="tiempo[]"></td>
    <td><button type="button" class="btn btn-danger btn-sm" onclick="delRow(this)">✕</button></td>`;
  tb.appendChild(tr);
}

function addLupulo() {
  const tb = document.querySelector('#lupulo_table tbody');
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><select name="lupulo[]">${buildOpts(LUPULOS)}</select></td>
    <td><input type="number" name="cantidad_lupulo[]" class="inp-sm"></td>
    <td><input type="text"   name="ibu[]" class="inp-sm"></td>
    <td><input type="text"   name="tiempo_lupulo[]"></td>
    <td><button type="button" class="btn btn-danger btn-sm" onclick="delRow(this)">✕</button></td>`;
  tb.appendChild(tr);
}

function loadContent(page) { window.location.href = page; }
</script>
</body>
</html>
