<?php
/**
 * BIALYSTOK BREWING CO — Agregar detalles del lote (paso 2)
 * Reemplaza: anadir_detalles_lote.php
 *
 * Correcciones:
 *  - Auth via auth.php
 *  - $id_lote / $id_recetas_estilos / $id_receta interpolados directamente → getIntParam() + prepared statements
 *  - XSS: todos los echo de DB usan e()
 *  - Conexión duplicada (require 'conexion.php' dentro del form) → una sola via getPDO()
 *  - Diseño: nuevo sistema CSS
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }

require_once 'conexion.php';

$menu_activo = 'lotes';

// ── Validar ID del lote ───────────────────────────────────────────────────────
$id_lote = getIntParam('id_lote');
if ($id_lote === null) {
    header('Location: lotes?error=' . urlencode('ID de lote inválido.'));
    exit;
}

// ── Queries ───────────────────────────────────────────────────────────────────
try {
    $pdo = getPDO();

    // Datos del lote
    $stmt = $pdo->prepare(
        "SELECT lc.id, lc.numero_lote, lc.estilo_id, ec.nombre AS estilo_nombre
         FROM lotes_cerveza lc
         LEFT JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
         WHERE lc.id = ?"
    );
    $stmt->execute([$id_lote]);
    $lote = $stmt->fetch();

    if (!$lote) {
        header('Location: lotes?error=' . urlencode('Lote no encontrado.'));
        exit;
    }

    $id_recetas_estilos = (int) $lote['estilo_id'];

    // Receta base del estilo
    $stmt = $pdo->prepare("SELECT * FROM recetas_estilos WHERE estilo_id = ? LIMIT 1");
    $stmt->execute([$id_recetas_estilos]);
    $receta = $stmt->fetch();
    $id_receta = $receta ? (int) $receta['id'] : null;

    // Maltas de la receta
    $maltas_receta = [];
    if ($id_receta) {
        $stmt = $pdo->prepare(
            "SELECT rm.*, vm.nombre AS nombre_malta, vm.marca AS marca_malta
             FROM recetasmalta rm
             JOIN variedades_malta vm ON rm.malta_id = vm.id
             WHERE rm.id_receta = ?"
        );
        $stmt->execute([$id_receta]);
        $maltas_receta = $stmt->fetchAll();
    }

    // Lúpulos de la receta
    $lupulos_receta = [];
    if ($id_receta) {
        $stmt = $pdo->prepare(
            "SELECT rl.*, vl.nombre AS nombre_lupulo, vl.marca AS marca_lupulo
             FROM recetaslupulo rl
             JOIN variedades_lupulo vl ON rl.lupulo_id = vl.id
             WHERE rl.id_receta = ?"
        );
        $stmt->execute([$id_receta]);
        $lupulos_receta = $stmt->fetchAll();
    }

    // Levadura de la receta
    $levadura_receta = null;
    if ($id_receta) {
        $stmt = $pdo->prepare(
            "SELECT rl.*, cl.cepa AS nombre_cepa, cl.marca AS marca_cepa
             FROM recetaslevadura rl
             JOIN cepas_levadura cl ON rl.cepa_id = cl.id
             WHERE rl.id_receta = ? LIMIT 1"
        );
        $stmt->execute([$id_receta]);
        $levadura_receta = $stmt->fetch();
    }

    // Tratamiento agua de la receta
    $agua_receta = null;
    if ($id_receta) {
        $stmt = $pdo->prepare("SELECT * FROM recetas_estilos WHERE id = ? LIMIT 1");
        $stmt->execute([$id_receta]);
        $agua_receta = $stmt->fetch();
    }

    // Todas las maltas disponibles (para dropdowns)
    $todas_maltas = $pdo->query("SELECT id, nombre, marca FROM variedades_malta ORDER BY nombre")->fetchAll();

    // Todos los lúpulos disponibles
    $todos_lupulos = $pdo->query("SELECT id, nombre, marca FROM variedades_lupulo ORDER BY nombre")->fetchAll();

    // Todas las cepas de levadura
    $todas_cepas = $pdo->query("SELECT id, cepa, marca FROM cepas_levadura ORDER BY cepa")->fetchAll();

    // Fermentadores disponibles
    $fermentadores = $pdo->query("SELECT id, nombre FROM fermentadores ORDER BY nombre")->fetchAll();

    // Reportes de agua disponibles
    $reportes_agua = $pdo->query("SELECT DISTINCT fecha FROM reportesagua ORDER BY fecha DESC")->fetchAll();

} catch (PDOException $ex) {
    error_log('[BRAUMEISTER anadir_detalles_lote] ' . $ex->getMessage());
    header('Location: lotes?error=' . urlencode('Error al cargar los datos del lote.'));
    exit;
}

// ── Helpers para options HTML ─────────────────────────────────────────────────
function optionsMaltas(array $maltas, int $selected_id = 0): string {
    $html = '';
    foreach ($maltas as $m) {
        $sel = ((int)$m['id'] === $selected_id) ? ' selected' : '';
        $html .= '<option value="' . (int)$m['id'] . '"' . $sel . '>'
               . e($m['nombre']) . ' (' . e($m['marca']) . ')</option>';
    }
    return $html;
}

function optionsLupulos(array $lupulos, int $selected_id = 0): string {
    $html = '';
    foreach ($lupulos as $l) {
        $sel = ((int)$l['id'] === $selected_id) ? ' selected' : '';
        $html .= '<option value="' . (int)$l['id'] . '"' . $sel . '>'
               . e($l['nombre']) . ' (' . e($l['marca']) . ')</option>';
    }
    return $html;
}

function optionsCepas(array $cepas, int $selected_id = 0): string {
    $html = '';
    foreach ($cepas as $c) {
        $sel = ((int)$c['id'] === $selected_id) ? ' selected' : '';
        $html .= '<option value="' . (int)$c['id'] . '"' . $sel . '>'
               . e($c['cepa']) . ' (' . e($c['marca']) . ')</option>';
    }
    return $html;
}

// JSON para JS dinámico (escapado correctamente)
$maltas_json  = json_encode(array_map(fn($m) => ['id' => (int)$m['id'], 'label' => $m['nombre'] . ' (' . $m['marca'] . ')'], $todas_maltas));
$lupulos_json = json_encode(array_map(fn($l) => ['id' => (int)$l['id'], 'label' => $l['nombre'] . ' (' . $l['marca'] . ')'], $todos_lupulos));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Detalles del lote · <?= e(strtoupper($lote['numero_lote'] ?? '')) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/bialy-design-system.css">
  <style>
    .form-section { margin-bottom: 1.5rem; }
    .form-section .card-title { margin-bottom: 1rem; }
    .tabla-form { width: 100%; border-collapse: collapse; }
    .tabla-form th {
      background: var(--color-surface-2);
      color: var(--text-muted);
      font-size: .68rem;
      font-weight: 600;
      letter-spacing: .1em;
      text-transform: uppercase;
      padding: .6rem .75rem;
      text-align: left;
      border-bottom: 1px solid var(--color-border);
      white-space: nowrap;
    }
    .tabla-form td {
      padding: .4rem .5rem;
      border-bottom: 1px solid rgba(255,255,255,.04);
      vertical-align: middle;
    }
    .tabla-form input, .tabla-form select {
      width: 100%;
      padding: .35rem .5rem;
      font-size: .82rem;
      background: var(--color-surface-3);
      border: 1px solid var(--color-border-md);
      border-radius: 4px;
      color: var(--text-primary);
      font-family: 'DM Mono', monospace;
    }
    .tabla-form input:focus, .tabla-form select:focus {
      outline: none;
      border-color: var(--amber-400);
    }
    .tabla-form select { font-family: 'DM Sans', sans-serif; }
    .input-sm { width: 70px !important; }
    .input-md { width: 100px !important; }
    .input-lg { width: 140px !important; }
    .checkbox-group { display: flex; gap: 1.5rem; flex-wrap: wrap; }
    .checkbox-group label {
      display: flex;
      align-items: center;
      gap: .4rem;
      font-size: .85rem;
      color: var(--text-secondary);
      cursor: pointer;
      font-weight: 400;
      letter-spacing: 0;
      margin: 0;
    }
  </style>
</head>
<body>

<?php require 'menu.php'; ?>
<?php require 'info_user.php'; ?>

<div id="contenido" class="main-content">

  <div class="page-header fade-in">
    <div>
      <h1>Detalles del lote</h1>
      <p class="page-subtitle">
        <?= e(strtoupper($lote['numero_lote'] ?? '')) ?>
        &nbsp;·&nbsp; <?= e($lote['estilo_nombre'] ?? '—') ?>
        &nbsp;·&nbsp; Paso 2 de 2
      </p>
    </div>
    <a href="lotes" class="btn btn-ghost btn-sm">← Volver a lotes</a>
  </div>

  <form action="guardar_receta" method="POST" id="formDetalles">
    <?= csrfField() ?>
    <input type="hidden" name="loteid" value="<?= $id_lote ?>">

    <!-- ── 1. Parámetros vitales ──────────────────────────────────────────── -->
    <div class="card fade-in form-section" style="margin-bottom:1rem">
      <div class="card-title">Parámetros vitales esperados</div>
      <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:.75rem">
        <?php
        $campos_pv = ['og'=>'OG','fg'=>'FG','ibuEsperado'=>'IBU','abvEsperado'=>'ABV','carbLevelEsperado'=>'Carb level'];
        $vals_pv   = ['og'=>$receta['og']??'','fg'=>$receta['fg']??'','ibuEsperado'=>$receta['ibu']??'','abvEsperado'=>$receta['abv']??'','carbLevelEsperado'=>$receta['carb_level']??''];
        foreach ($campos_pv as $name => $label): ?>
        <div class="form-group" style="margin:0">
          <label class="form-label"><?= $label ?></label>
          <input type="text" name="<?= $name ?>" value="<?= e((string)($vals_pv[$name])) ?>"
                 style="font-family:'DM Mono',monospace" required>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ── 2. Parámetros H2O ──────────────────────────────────────────────── -->
    <div class="card fade-in form-section" style="margin-bottom:1rem">
      <div class="card-title">Perfil de H₂O</div>
      <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:.75rem">
        <?php
        $campos_h2o = [
          'ca_mas_2'    => 'Ca²⁺ (ppm)',
          'mg_mas_2'    => 'Mg²⁺ (ppm)',
          'na_mas_2'    => 'Na⁺ (ppm)',
          'cl_menos'    => 'Cl⁻ (ppm)',
          'so4_menos_2' => 'SO₄²⁻ (ppm)',
        ];
        $vals_h2o = [
          'ca_mas_2'    => $receta['ca_mas_2']    ?? '',
          'mg_mas_2'    => $receta['mg_mas_2']    ?? '',
          'na_mas_2'    => $receta['na_mas_2']    ?? '',
          'cl_menos'    => $receta['cl_menos']    ?? '',
          'so4_menos_2' => $receta['so04_menos_2']?? '',
        ];
        foreach ($campos_h2o as $name => $label): ?>
        <div class="form-group" style="margin:0">
          <label class="form-label"><?= $label ?></label>
          <input type="number" name="<?= $name ?>" value="<?= e((string)($vals_h2o[$name])) ?>"
                 style="font-family:'DM Mono',monospace" required>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Reporte de agua asociado -->
      <div style="margin-top:1rem">
        <label class="form-label">Reporte de agua asociado</label>
        <select name="fechareporte" style="width:auto;min-width:200px">
          <option value="">Sin reporte</option>
          <?php foreach ($reportes_agua as $r): ?>
            <option value="<?= e($r['fecha']) ?>"><?= e($r['fecha']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- ── 3. Tratamiento H2O Mash ────────────────────────────────────────── -->
    <div class="card fade-in form-section" style="margin-bottom:1rem">
      <div class="card-title">Tratamiento H₂O Mash</div>
      <div class="table-wrapper">
        <table class="tabla-form">
          <thead><tr>
            <th>Total (L)</th><th>% RO</th><th>Temp °C</th><th>pH</th>
            <th>Fosfórico</th><th>CaSO₄</th><th>CaCl₂</th><th>MgCl₂</th><th>Otro</th><th>Fosfórico H₂O</th>
          </tr></thead>
          <tbody><tr>
            <td><input type="number" name="total_agua_mash"     value="<?= e((string)($agua_receta['total_agua_mash']??'')) ?>" required></td>
            <td><input type="number" name="porcentaje_ro_mash"  value="<?= e((string)($agua_receta['porcentaje_ro_mash']??'')) ?>" required></td>
            <td><input type="number" name="temperatura_mash"    value="<?= e((string)($agua_receta['temperatura_mash']??'')) ?>" required></td>
            <td><input type="text"   name="ph_mashh2o"          value="<?= e((string)($agua_receta['ph_mash']??'')) ?>" class="input-sm" required></td>
            <td><input type="number" name="fosforico_mash"      value="<?= e((string)($agua_receta['fosforico_mash']??'')) ?>" required></td>
            <td><input type="number" name="caso4_mash"          value="<?= e((string)($agua_receta['caso4_mash']??'')) ?>" required></td>
            <td><input type="number" name="cacl2_mash"          value="<?= e((string)($agua_receta['cacl2_mash']??'')) ?>" required></td>
            <td><input type="number" name="mgcl_mash"           value="<?= e((string)($agua_receta['mgcl_mash']??'')) ?>" required></td>
            <td><input type="number" name="otro_mash"           value="<?= e((string)($agua_receta['otro_mash']??'')) ?>" required></td>
            <td><input type="number" name="fosforico_h2o_mash"  value="<?= e((string)($agua_receta['fosforico_h2o_mash']??'')) ?>" required></td>
          </tr></tbody>
        </table>
      </div>

      <div class="card-title" style="margin-top:1.25rem">Tratamiento H₂O Sparge</div>
      <div class="table-wrapper">
        <table class="tabla-form">
          <thead><tr>
            <th>Total (L)</th><th>% RO</th><th>Temp °C</th><th>pH</th>
            <th>Fosfórico</th><th>CaSO₄</th><th>CaCl₂</th><th>MgCl</th><th>Otro</th>
          </tr></thead>
          <tbody><tr>
            <td><input type="number" name="total_agua_sparge"    value="<?= e((string)($agua_receta['total_agua_sparge']??'')) ?>" required></td>
            <td><input type="number" name="porcentaje_ro_sparge" value="<?= e((string)($agua_receta['porcentaje_ro_sparge']??'')) ?>" required></td>
            <td><input type="number" name="temperatura_sparge"   value="<?= e((string)($agua_receta['temperatura_sparge']??'')) ?>" required></td>
            <td><input type="text"   name="ph_spargeh2o"         value="<?= e((string)($agua_receta['ph_sparge']??'')) ?>" class="input-sm" required></td>
            <td><input type="number" name="fosforico_sparge"     value="<?= e((string)($agua_receta['fosforico_sparge']??'')) ?>" required></td>
            <td><input type="number" name="caso4_sparge"         value="<?= e((string)($agua_receta['caso4_sparge']??'')) ?>" required></td>
            <td><input type="number" name="cacl2_sparge"         value="<?= e((string)($agua_receta['cacl2_sparge']??'')) ?>" required></td>
            <td><input type="number" name="mgcl_sparge"          value="<?= e((string)($agua_receta['mgcl_sparge']??'')) ?>" required></td>
            <td><input type="number" name="otro_sparge"          value="<?= e((string)($agua_receta['otro_sparge']??'')) ?>" required></td>
          </tr></tbody>
        </table>
      </div>
    </div>

    <!-- ── 4. Maltas ──────────────────────────────────────────────────────── -->
    <div class="card fade-in form-section" style="margin-bottom:1rem">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
        <div class="card-title" style="margin:0">Maltas</div>
        <button type="button" class="btn btn-ghost btn-sm" onclick="addRowMalta()">+ Añadir fila</button>
      </div>
      <div class="table-wrapper">
        <table class="tabla-form" id="maltas_table">
          <thead><tr><th>Variedad</th><th>N° Lote malta</th><th>Cantidad (kg)</th><th>Uso / Tiempo</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($maltas_receta as $m): ?>
            <tr>
              <td><select name="malta[]"><?= optionsMaltas($todas_maltas, (int)$m['malta_id']) ?></select></td>
              <td><input type="text" name="lote_malta[]" value="" placeholder="#000000" class="input-md"></td>
              <td><input type="text" name="cantidad[]"   value="<?= e((string)$m['cantidad']) ?>" class="input-sm"></td>
              <td><input type="text" name="tiempo[]"     value="<?= e($m['tiempo'] ?? '') ?>" class="input-md"></td>
              <td><button type="button" class="btn btn-danger btn-sm" onclick="deleteRow(this)">✕</button></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$maltas_receta): ?>
            <tr>
              <td><select name="malta[]"><?= optionsMaltas($todas_maltas) ?></select></td>
              <td><input type="text" name="lote_malta[]" placeholder="#000000" class="input-md"></td>
              <td><input type="text" name="cantidad[]"   class="input-sm"></td>
              <td><input type="text" name="tiempo[]"     class="input-md"></td>
              <td><button type="button" class="btn btn-danger btn-sm" onclick="deleteRow(this)">✕</button></td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── 5. Lúpulos ─────────────────────────────────────────────────────── -->
    <div class="card fade-in form-section" style="margin-bottom:1rem">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
        <div class="card-title" style="margin:0">Lúpulos</div>
        <button type="button" class="btn btn-ghost btn-sm" onclick="addRowLupulo()">+ Añadir fila</button>
      </div>
      <div class="table-wrapper">
        <table class="tabla-form" id="lupulo_table">
          <thead><tr><th>Variedad</th><th>N° Lote lúpulo</th><th>Cantidad (g)</th><th>IBU</th><th>Tiempo / Técnica</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($lupulos_receta as $l): ?>
            <tr>
              <td><select name="lupulo[]"><?= optionsLupulos($todos_lupulos, (int)$l['lupulo_id']) ?></select></td>
              <td><input type="text"   name="lote_lupulo[]"     value="" placeholder="#000000" class="input-md"></td>
              <td><input type="number" name="cantidad_lupulo[]" value="<?= e((string)$l['cantidad']) ?>" class="input-sm"></td>
              <td><input type="text"   name="ibu[]"             value="<?= e((string)($l['ibu']??'')) ?>" class="input-sm"></td>
              <td><input type="text"   name="tiempo_lupulo[]"   value="<?= e($l['tiempo'] ?? '') ?>" class="input-md"></td>
              <td><button type="button" class="btn btn-danger btn-sm" onclick="deleteRow(this)">✕</button></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$lupulos_receta): ?>
            <tr>
              <td><select name="lupulo[]"><?= optionsLupulos($todos_lupulos) ?></select></td>
              <td><input type="text"   name="lote_lupulo[]"     placeholder="#000000" class="input-md"></td>
              <td><input type="number" name="cantidad_lupulo[]" class="input-sm"></td>
              <td><input type="text"   name="ibu[]"             class="input-sm"></td>
              <td><input type="text"   name="tiempo_lupulo[]"   class="input-md"></td>
              <td><button type="button" class="btn btn-danger btn-sm" onclick="deleteRow(this)">✕</button></td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── 6. Levadura ────────────────────────────────────────────────────── -->
    <div class="card fade-in form-section" style="margin-bottom:1rem">
      <div class="card-title">Levadura</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div>
          <div class="form-group">
            <label class="form-label">Cepa</label>
            <select name="cepa"><?= optionsCepas($todas_cepas, (int)($levadura_receta['cepa_id'] ?? 0)) ?></select>
          </div>
          <div class="form-group">
            <label class="form-label">Generación</label>
            <input type="text" name="genleva" value="" placeholder="Ej: G1">
          </div>
          <div class="form-group">
            <label class="form-label">Temp. inoculación (°C)</label>
            <input type="text" name="tempInoc" value="<?= e((string)($levadura_receta['temp_inoculacion']??'')) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Tasa inoculación</label>
            <input type="text" name="tasaInoc" value="<?= e((string)($levadura_receta['tasa_inoculacion']??'')) ?>">
          </div>
        </div>
        <div>
          <div class="form-group">
            <label class="form-label">Viabilidad (%)</label>
            <input type="number" name="viabilidad" value="<?= e((string)($levadura_receta['viabilidad']??'')) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Kilos de biomasa (kg)</label>
            <input type="text" name="biomasa" value="<?= e((string)($levadura_receta['kilos_biomasa']??'')) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">PPM Oxígeno</label>
            <input type="number" name="oxigenacion" value="<?= e((string)($levadura_receta['oxigenacion']??'')) ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- ── 7. LOG día de cocción ──────────────────────────────────────────── -->
    <div class="card fade-in form-section" style="margin-bottom:1rem">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
        <div class="card-title" style="margin:0">LOG día de cocción</div>
        <div style="display:flex;gap:.5rem">
          <button type="button" class="btn btn-ghost btn-sm" onclick="addRowLOG()">+ Añadir batch</button>
          <button type="button" class="btn btn-danger btn-sm" onclick="deleteRowLog()">− Borrar último</button>
        </div>
      </div>
      <div class="table-wrapper" style="overflow-x:auto">
        <table class="tabla-form" id="tablaLOG" style="min-width:1100px">
          <thead><tr>
            <th>#</th>
            <th>T°1 Mash</th><th>T°2 Mash</th><th>T°3 Mash</th>
            <th>pH1 Mash</th><th>pH2 Mash</th><th>pH3 Mash</th>
            <th>Dens 1° Mosto</th><th>Dens Last Run</th><th>pH Last Run</th>
            <th>T° Sparge</th><th>pH Sparge</th>
            <th>Vol ini Boil</th><th>Dens pre-Boil</th><th>pH ini Boil</th>
            <th>Vol fin Boil</th><th>Dens post-Boil</th><th>pH fin</th>
          </tr></thead>
          <tbody>
            <tr>
              <td style="text-align:center;color:var(--text-muted);font-weight:600">1</td>
              <td><input type="number" name="temp_mash[0]"        class="input-sm" required></td>
              <td><input type="number" name="temp2_mash[0]"       class="input-sm" required></td>
              <td><input type="number" name="temp3_mash[0]"       class="input-sm" required></td>
              <td><input type="text"   name="ph_mash[0]"          class="input-sm" required></td>
              <td><input type="text"   name="ph2_mash[0]"         class="input-sm" required></td>
              <td><input type="text"   name="ph3_mash[0]"         class="input-sm" required></td>
              <td><input type="text"   name="dens_primer_mosto[0]"class="input-md" required></td>
              <td><input type="text"   name="dens_last_run[0]"    class="input-md" required></td>
              <td><input type="text"   name="ph_last_run[0]"      class="input-sm" required></td>
              <td><input type="number" name="temp_sparge[0]"      class="input-sm" required></td>
              <td><input type="text"   name="ph_sparge[0]"        class="input-sm" required></td>
              <td><input type="number" name="vol_inicial_boil[0]" class="input-sm" required></td>
              <td><input type="text"   name="dens_pre_boil[0]"    class="input-md" required></td>
              <td><input type="text"   name="ph_inicio_boil[0]"   class="input-sm" required></td>
              <td><input type="number" name="vol_final_boil[0]"   class="input-sm" required></td>
              <td><input type="text"   name="dens_post_boil[0]"   class="input-md" required></td>
              <td><input type="text"   name="ph_fin[0]"           class="input-sm" required></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── 8. Seguimiento fermentación ───────────────────────────────────── -->
    <div class="card fade-in form-section" style="margin-bottom:1rem">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
        <div class="card-title" style="margin:0">Seguimiento de fermentación</div>
        <div style="display:flex;gap:.5rem">
          <button type="button" class="btn btn-ghost btn-sm" onclick="addRowFerm()">+ Añadir día</button>
          <button type="button" class="btn btn-danger btn-sm" onclick="deleteRowFerm()">− Borrar último</button>
        </div>
      </div>
      <div class="table-wrapper">
        <table class="tabla-form" id="tablaFerm">
          <thead><tr><th>#</th><th>Fecha</th><th>Hora</th><th>Densidad</th><th>pH</th><th>Temp °C</th><th>Purga</th><th>Comentarios</th></tr></thead>
          <tbody>
            <tr>
              <td style="text-align:center;color:var(--text-muted);font-weight:600">1</td>
              <td><input type="date"   name="fecha[0]"       required></td>
              <td><input type="time"   name="hora[0]"        required></td>
              <td><input type="text"   name="densidad[0]"    class="input-md" required></td>
              <td><input type="text"   name="ph[0]"          class="input-sm" required></td>
              <td><input type="text"   name="temperatura[0]" class="input-sm" required></td>
              <td><input type="number" name="purga[0]"       class="input-sm" required></td>
              <td><input type="text"   name="comentarios[0]" style="width:100%"></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── 9. Lecturas finales y envasado ─────────────────────────────────── -->
    <div class="card fade-in form-section" style="margin-bottom:1rem">
      <div class="card-title">Lecturas finales y envasado</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div>
          <div class="form-group"><label class="form-label">DO (densidad original)</label><input type="text" name="lecturaDO" required></div>
          <div class="form-group"><label class="form-label">DF (densidad final)</label><input type="text" name="lecturaDF" required></div>
          <div class="form-group"><label class="form-label">pH inicial mosto</label><input type="text" name="ph_inicialMosto" required></div>
          <div class="form-group"><label class="form-label">pH final fermentación</label><input type="text" name="ph_finFerm" required></div>
        </div>
        <div>
          <div class="form-group"><label class="form-label">Litros a fermentador</label><input type="number" name="litrosAfermentar" required></div>
          <div class="form-group"><label class="form-label">Carb. level</label><input type="text" name="carbLevel" required></div>
          <div class="form-group"><label class="form-label">Día de envasado</label><input type="date" name="diaEnvasado" required></div>
          <div class="form-group"><label class="form-label">Litros envasados en barril</label><input type="number" name="ltsEnvasados" required></div>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Fermentador utilizado</label>
        <select name="fermentador" style="width:auto;min-width:200px">
          <?php foreach ($fermentadores as $fv): ?>
            <option value="<?= (int)$fv['id'] ?>"><?= e($fv['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label class="form-label">Limpieza del fermentador realizada</label>
        <div class="checkbox-group">
          <label><input type="checkbox" name="alcalina"  value="1"> Alcalina</label>
          <label><input type="checkbox" name="acida"     value="1"> Ácida</label>
          <label><input type="checkbox" name="oxidativa" value="1"> Oxidativa</label>
          <label><input type="checkbox" name="exterior"  value="1"> Exterior</label>
        </div>
      </div>
    </div>

    <!-- ── 10. Detalles enlatado (colapsable) ─────────────────────────────── -->
    <div class="card fade-in form-section" style="margin-bottom:1rem">
      <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.75rem">
        <input type="checkbox" id="chkEnlatado" name="checkboxanadirdetalles" value="1"
               checked onchange="toggleEnlatado(this.checked)"
               style="width:16px;height:16px;accent-color:var(--amber-400)">
        <label for="chkEnlatado" class="card-title" style="margin:0;cursor:pointer">Agregar detalles de enlatado</label>
      </div>

      <div id="tablaDetallesEnlatado">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
          <div>
            <p style="font-size:.78rem;font-weight:600;color:var(--text-muted);letter-spacing:.08em;text-transform:uppercase;margin-bottom:.75rem">Configuración enlatadora</p>
            <div class="form-group"><label class="form-label">Día de enlatado</label><input type="date" name="diaEnlatado"></div>
            <div class="form-group"><label class="form-label">Presión barrido</label><input type="text" name="presionbarrido"></div>
            <div class="form-group"><label class="form-label">Presión línea de llenado</label><input type="text" name="presionenenlatadora"></div>
            <div class="form-group"><label class="form-label">Presión en tanque</label><input type="text" name="presionentanque"></div>
            <div class="form-group"><label class="form-label">Tiempo llenado (seg)</label><input type="number" name="tiempollenado"></div>
            <div class="form-group"><label class="form-label">Tiempo barrido 1</label><input type="number" name="tiempo1"></div>
            <div class="form-group"><label class="form-label">Tiempo barrido 2</label><input type="number" name="tiempo2"></div>
            <div class="form-group"><label class="form-label">Temp. en tanque (°C)</label><input type="text" name="tempentanque"></div>
            <div class="form-group"><label class="form-label">Temp. cerveza en enlatadora (°C)</label><input type="text" name="tempenenlatadora"></div>
            <div class="form-group"><label class="form-label">Temp. ambiente (°C)</label><input type="text" name="tempambiente"></div>
            <div class="form-group"><label class="form-label">Observaciones</label><input type="text" name="observacionesenlatado"></div>
          </div>
          <div>
            <p style="font-size:.78rem;font-weight:600;color:var(--text-muted);letter-spacing:.08em;text-transform:uppercase;margin-bottom:.75rem">Resultados enlatado</p>
            <div class="form-group"><label class="form-label">DO</label><input type="number" name="disoxigen"></div>
            <div class="form-group"><label class="form-label">TPO</label><input type="number" name="tpo"></div>
            <div class="form-group"><label class="form-label">Latas cerradas descartadas</label><input type="number" name="latascerradasDes"></div>
            <div class="form-group"><label class="form-label">Latas vacías desechadas</label><input type="number" name="latasvaciasDes"></div>
            <div class="form-group"><label class="form-label">Tapas desechadas</label><input type="number" name="tapasDes"></div>
            <div class="form-group"><label class="form-label">Latas cerradas OK</label><input type="number" name="latasOK"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── 11. Comentarios generales ──────────────────────────────────────── -->
    <div class="card fade-in form-section" style="margin-bottom:1.5rem">
      <div class="card-title">Comentarios generales</div>
      <textarea name="comentariosGeneral" id="comentariosInput" rows="4"
                style="width:100%;resize:vertical" placeholder="Anotaciones del día de cocción…"></textarea>
    </div>

    <!-- ── Submit ─────────────────────────────────────────────────────────── -->
    <div style="display:flex;gap:.75rem;margin-bottom:2rem" class="fade-in">
      <button type="submit" class="btn btn-primary btn-lg"
              onclick="return confirm('¿Guardar todos los datos del lote?')">
        Guardar lote completo
      </button>
      <a href="lotes" class="btn btn-ghost btn-lg">Cancelar</a>
    </div>

  </form>
</div>

<script>
// ── Datos para JS dinámico ────────────────────────────────────────────────────
const MALTAS_OPTIONS  = <?= $maltas_json ?>;
const LUPULOS_OPTIONS = <?= $lupulos_json ?>;

function buildOptions(arr, selectedId = 0) {
  return arr.map(item =>
    `<option value="${item.id}"${item.id === selectedId ? ' selected' : ''}>${item.label.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</option>`
  ).join('');
}

// ── Eliminar fila genérico ────────────────────────────────────────────────────
function deleteRow(btn) {
  const row = btn.closest('tr');
  const tbody = row.closest('tbody');
  if (tbody.rows.length > 1) row.remove();
}

// ── Maltas ────────────────────────────────────────────────────────────────────
function addRowMalta() {
  const tbody = document.querySelector('#maltas_table tbody');
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><select name="malta[]">${buildOptions(MALTAS_OPTIONS)}</select></td>
    <td><input type="text"   name="lote_malta[]" placeholder="#000000" class="input-md"></td>
    <td><input type="text"   name="cantidad[]"   class="input-sm"></td>
    <td><input type="text"   name="tiempo[]"     class="input-md"></td>
    <td><button type="button" class="btn btn-danger btn-sm" onclick="deleteRow(this)">✕</button></td>
  `;
  tbody.appendChild(tr);
}

// ── Lúpulos ───────────────────────────────────────────────────────────────────
function addRowLupulo() {
  const tbody = document.querySelector('#lupulo_table tbody');
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><select name="lupulo[]">${buildOptions(LUPULOS_OPTIONS)}</select></td>
    <td><input type="text"   name="lote_lupulo[]"     placeholder="#000000" class="input-md"></td>
    <td><input type="number" name="cantidad_lupulo[]" class="input-sm"></td>
    <td><input type="text"   name="ibu[]"             class="input-sm"></td>
    <td><input type="text"   name="tiempo_lupulo[]"   class="input-md"></td>
    <td><button type="button" class="btn btn-danger btn-sm" onclick="deleteRow(this)">✕</button></td>
  `;
  tbody.appendChild(tr);
}

// ── LOG cocción ───────────────────────────────────────────────────────────────
function addRowLOG() {
  const table  = document.getElementById('tablaLOG');
  const tbody  = table.querySelector('tbody');
  const idx    = tbody.rows.length;
  const campos = ['temp_mash','temp2_mash','temp3_mash','ph_mash','ph2_mash','ph3_mash',
                  'dens_primer_mosto','dens_last_run','ph_last_run','temp_sparge','ph_sparge',
                  'vol_inicial_boil','dens_pre_boil','ph_inicio_boil','vol_final_boil','dens_post_boil','ph_fin'];
  const tipos  = [1,1,1,0,0,0,0,0,0,1,0,1,0,0,1,0,0]; // 1=number, 0=text

  let cells = `<td style="text-align:center;color:var(--text-muted);font-weight:600">${idx+1}</td>`;
  campos.forEach((c,i) => {
    const t = tipos[i] ? 'number' : 'text';
    const cls = [6,7,12,15].includes(i) ? 'input-md' : 'input-sm';
    cells += `<td><input type="${t}" name="${c}[${idx}]" class="${cls}" required></td>`;
  });

  const tr = document.createElement('tr');
  tr.innerHTML = cells;
  tbody.appendChild(tr);
}

function deleteRowLog() {
  const tbody = document.querySelector('#tablaLOG tbody');
  if (tbody.rows.length > 1) tbody.deleteRow(tbody.rows.length - 1);
}

// ── Seguimiento fermentación ──────────────────────────────────────────────────
function nextDay(isoDate) {
  if (!isoDate) return '';
  const [y,m,d] = isoDate.split('-').map(Number);
  const dt = new Date(y, m-1, d);
  dt.setDate(dt.getDate() + 1);
  return `${dt.getFullYear()}-${String(dt.getMonth()+1).padStart(2,'0')}-${String(dt.getDate()).padStart(2,'0')}`;
}

function addRowFerm() {
  const tbody    = document.querySelector('#tablaFerm tbody');
  const lastRow  = tbody.rows[tbody.rows.length - 1];
  const lastDate = lastRow.querySelector('input[type="date"]')?.value || '';
  const lastTime = lastRow.querySelector('input[type="time"]')?.value || '';
  const idx      = tbody.rows.length;

  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td style="text-align:center;color:var(--text-muted);font-weight:600">${idx+1}</td>
    <td><input type="date"   name="fecha[${idx}]"       value="${nextDay(lastDate)}" required></td>
    <td><input type="time"   name="hora[${idx}]"        value="${lastTime}" required></td>
    <td><input type="text"   name="densidad[${idx}]"    class="input-md" required></td>
    <td><input type="text"   name="ph[${idx}]"          class="input-sm" required></td>
    <td><input type="text"   name="temperatura[${idx}]" class="input-sm" required></td>
    <td><input type="number" name="purga[${idx}]"       class="input-sm" required></td>
    <td><input type="text"   name="comentarios[${idx}]" style="width:100%"></td>
  `;
  tbody.appendChild(tr);
}

function deleteRowFerm() {
  const tbody = document.querySelector('#tablaFerm tbody');
  if (tbody.rows.length > 1) tbody.deleteRow(tbody.rows.length - 1);
}

// ── Toggle enlatado ───────────────────────────────────────────────────────────
function toggleEnlatado(checked) {
  const div    = document.getElementById('tablaDetallesEnlatado');
  const inputs = div.querySelectorAll('input, textarea, select');
  div.style.display = checked ? 'block' : 'none';
  inputs.forEach(i => { i.required = false; });
}

// ── loadContent() compat ──────────────────────────────────────────────────────
function loadContent(page) { window.location.href = page; }

// ── Enter/Tab en comentarios de fermentación ──────────────────────────────
document.addEventListener('keydown', function(e) {
  const inp = e.target;
  if (!inp.matches('#tablaFerm input[name^="comentarios"]')) return;

  if (e.key === 'Enter') {
    e.preventDefault();
    const tb = document.querySelector('#tablaFerm tbody');
    const filas = tb.querySelectorAll('tr');
    const filaActual = inp.closest('tr');
    const ultimaFila = filas[filas.length - 1];

    if (filaActual === ultimaFila) {
      addRowFerm();
      setTimeout(() => {
        const nuevas = tb.querySelectorAll('tr');
        nuevas[nuevas.length - 1].querySelector('input[type="date"]')?.focus();
      }, 50);
    } else {
      const idx  = Array.from(filas).indexOf(filaActual);
      filas[idx + 1]?.querySelector('input[name^="comentarios"]')?.focus();
    }
  }

  if (e.key === 'Tab' && !e.shiftKey) {
    const fila   = inp.closest('tr');
    const inputs = Array.from(fila.querySelectorAll('input'));
    const tb     = document.querySelector('#tablaFerm tbody');
    const filas  = Array.from(tb.querySelectorAll('tr'));
    if (inputs.indexOf(inp) === inputs.length - 1 && fila === filas[filas.length - 1]) {
      e.preventDefault();
      addRowFerm();
      setTimeout(() => {
        const nuevas = tb.querySelectorAll('tr');
        nuevas[nuevas.length - 1].querySelector('input[type="date"]')?.focus();
      }, 50);
    }
  }
});
</script>

</body>
</html>
