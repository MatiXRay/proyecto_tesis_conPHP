<?php
/**
 * BIALYSTOK BREWING CO — Editar lote completo
 * Carga todos los datos existentes del lote en un formulario editable.
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }
require_once 'conexion.php';

$menu_activo = 'lotes';

$id_lote = getIntParam('id');
if (!$id_lote) { header('Location: lotes'); exit; }

try {
    $pdo = getPDO();

    // Lote principal
    $stmt = $pdo->prepare(
        "SELECT lc.*, ec.nombre AS estilo_nombre
         FROM lotes_cerveza lc
         LEFT JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
         WHERE lc.id = ?"
    );
    $stmt->execute([$id_lote]);
    $lote = $stmt->fetch();
    if (!$lote) { header('Location: lotes'); exit; }

    // Tratamiento agua
    $agua = $pdo->prepare("SELECT * FROM tratamiento_agua_mash_sparge WHERE lote_id = ? LIMIT 1");
    $agua->execute([$id_lote]);
    $agua = $agua->fetch() ?: [];

    // Maltas
    $stmt = $pdo->prepare(
        "SELECT lm.*, vm.nombre AS nombre_malta, vm.marca AS marca_malta
         FROM lotes_maltas lm JOIN variedades_malta vm ON lm.malta_id = vm.id
         WHERE lm.lote_id = ?"
    );
    $stmt->execute([$id_lote]);
    $maltas_lote = $stmt->fetchAll();

    // Lúpulos
    $stmt = $pdo->prepare(
        "SELECT ll.*, vl.nombre AS nombre_lupulo, vl.marca AS marca_lupulo
         FROM lotes_lupulos ll JOIN variedades_lupulo vl ON ll.lupulo_id = vl.id
         WHERE ll.lote_id = ?"
    );
    $stmt->execute([$id_lote]);
    $lupulos_lote = $stmt->fetchAll();

    // Levadura
    $stmt = $pdo->prepare(
        "SELECT ll.*, cl.cepa, cl.marca
         FROM lotes_levaduras ll JOIN cepas_levadura cl ON ll.cepa_id = cl.id
         WHERE ll.lote_id = ? LIMIT 1"
    );
    $stmt->execute([$id_lote]);
    $levadura_lote = $stmt->fetch() ?: [];

    // LOG cocción (batches)
    $stmt = $pdo->prepare("SELECT * FROM batches WHERE lote_id = ? ORDER BY batch");
    $stmt->execute([$id_lote]);
    $batches = $stmt->fetchAll();

    // Seguimiento fermentación
    $stmt = $pdo->prepare("SELECT * FROM seguimiento_fermentacion WHERE lote_id = ? ORDER BY fecha, hora");
    $stmt->execute([$id_lote]);
    $fermentacion = $stmt->fetchAll();

    // Enlatado
    $stmt = $pdo->prepare("SELECT * FROM lotesenlatado WHERE id_lote = ? LIMIT 1");
    $stmt->execute([$id_lote]);
    $enlatado = $stmt->fetch() ?: [];

    // Selects
    $todas_maltas  = $pdo->query("SELECT id, nombre, marca FROM variedades_malta ORDER BY nombre")->fetchAll();
    $todos_lupulos = $pdo->query("SELECT id, nombre, marca FROM variedades_lupulo ORDER BY nombre")->fetchAll();
    $todas_cepas   = $pdo->query("SELECT id, cepa, marca FROM cepas_levadura ORDER BY cepa")->fetchAll();
    $fermentadores = $pdo->query("SELECT id, nombre FROM fermentadores ORDER BY nombre")->fetchAll();
    $reportes_agua = $pdo->query("SELECT DISTINCT fecha FROM reportesagua ORDER BY fecha DESC")->fetchAll();

} catch (PDOException $ex) {
    error_log('[editar_lote] ' . $ex->getMessage());
    header('Location: lotes?error=error_db'); exit;
}

function opts(array $arr, int $sel, string $val='id', string $lbl='nombre'): string {
    $h = '';
    foreach ($arr as $r) {
        $s = ((int)$r[$val] === $sel) ? ' selected' : '';
        $label = $r[$lbl] ?? ($r['cepa'] ?? '');
        if (isset($r['marca'])) $label .= ' (' . $r['marca'] . ')';
        $h .= '<option value="'.(int)$r[$val].'"'.$s.'>'.htmlspecialchars($label).'</option>';
    }
    return $h;
}

$maltas_json  = json_encode(array_map(fn($m)=>['id'=>(int)$m['id'],'label'=>$m['nombre'].' ('.$m['marca'].')'], $todas_maltas));
$lupulos_json = json_encode(array_map(fn($l)=>['id'=>(int)$l['id'],'label'=>$l['nombre'].' ('.$l['marca'].')'], $todos_lupulos));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Editar lote · <?= e(strtoupper($lote['numero_lote'])) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/bialy-design-system.css">
  <style>
    .tf { width:100%; border-collapse:collapse; }
    .tf th { background:var(--color-surface-2); color:var(--text-muted); font-size:.68rem; font-weight:600; letter-spacing:.1em; text-transform:uppercase; padding:.5rem .75rem; text-align:left; border-bottom:1px solid var(--color-border); }
    .tf td { padding:.35rem .5rem; border-bottom:1px solid rgba(255,255,255,.04); vertical-align:middle; }
    .tf input, .tf select { width:100%; padding:.3rem .5rem; font-size:.82rem; background:var(--color-surface-3); border:1px solid var(--color-border-md); border-radius:4px; color:var(--text-primary); font-family:'DM Mono',monospace; }
    .tf input:focus, .tf select:focus { outline:none; border-color:var(--amber-400); }
    .tf select { font-family:'DM Sans',sans-serif; }
    .sm { width:70px !important; }
    .md { width:100px !important; }
  </style>
</head>
<body>
<?php require 'menu.php'; ?>
<?php require 'info_user.php'; ?>

<div id="contenido" class="main-content">
  <div class="page-header fade-in">
    <div>
      <h1>Editar lote</h1>
      <p class="page-subtitle"><?= e(strtoupper($lote['numero_lote'])) ?> · <?= e($lote['estilo_nombre'] ?? '—') ?></p>
    </div>
    <a href="detalles_lote?id=<?= $id_lote ?>" class="btn btn-ghost btn-sm">← Volver</a>
  </div>

  <form action="guardar_edicion_lote" method="POST" id="formEditar">
    <?= csrfField() ?>
    <input type="hidden" name="loteid" value="<?= $id_lote ?>">

    <!-- ── Comentarios generales ───────────────────────────────────────────────── -->
    <div class="card fade-in" style="margin-bottom:1rem">
      <div class="card-title">Comentarios generales</div>
      <textarea name="comentariosGeneral" rows="3" style="width:100%;resize:vertical"><?= e($lote['comentarios']??'') ?></textarea>
    </div>

    <!-- ── Parámetros vitales ──────────────────────────────── -->
    <div class="card fade-in" style="margin-bottom:1rem">
      <div class="card-title">Parámetros vitales</div>
      <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:.75rem">
        <?php foreach (['og'=>'OG','fg'=>'FG','ibuEsperado'=>'IBU','abvEsperado'=>'ABV','carbLevelEsperado'=>'Carb level'] as $name=>$label):
          $dbcol = match($name) { 'ibuEsperado'=>'ibu','abvEsperado'=>'abv','carbLevelEsperado'=>'co2', default=>$name };
        ?>
        <div class="form-group">
          <label class="form-label"><?= $label ?></label>
          <input type="text" name="<?= $name ?>" value="<?= e((string)($lote[$dbcol]??'')) ?>" style="font-family:'DM Mono',monospace">
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ── H2O ─────────────────────────────────────────────── -->
    <div class="card fade-in" style="margin-bottom:1rem">
      <div class="card-title">Perfil H₂O</div>
      <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:.75rem;margin-bottom:1rem">
        <?php foreach (['ca_mas_2'=>'Ca²⁺','mg_mas_2'=>'Mg²⁺','na_mas_2'=>'Na⁺','cl_menos'=>'Cl⁻','so4_menos_2'=>'SO₄²⁻'] as $name=>$label):
          $dbcol = $name === 'so4_menos_2' ? 'so04_menos_2' : $name;
        ?>
        <div class="form-group">
          <label class="form-label"><?= $label ?></label>
          <input type="number" name="<?= $name ?>" value="<?= e((string)($lote[$dbcol]??'')) ?>">
        </div>
        <?php endforeach; ?>
      </div>

      <div style="margin-bottom:.5rem">
        <label class="form-label">Reporte de agua asociado</label>
        <select name="fechareporte" style="width:auto;min-width:200px">
          <option value="">Sin reporte</option>
          <?php foreach ($reportes_agua as $r): ?>
          <option value="<?= e($r['fecha']) ?>"><?= e($r['fecha']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="card-title" style="margin-top:1rem">Tratamiento Mash</div>
      <div class="table-wrapper">
        <table class="tf">
          <thead><tr><th>Total</th><th>%RO</th><th>Temp</th><th>pH</th><th>Fosfórico</th><th>CaSO₄</th><th>CaCl₂</th><th>MgCl₂</th><th>Otro</th><th>Fosfórico H₂O</th></tr></thead>
          <tbody><tr>
            <td><input type="text" inputmode="decimal" name="total_agua_mash"    value="<?= e((string)($agua['total_agua_mash']??'')) ?>"></td>
            <td><input type="text" inputmode="decimal" name="porcentaje_ro_mash" value="<?= e((string)($agua['porcentaje_ro_mash']??'')) ?>"></td>
            <td><input type="text" inputmode="decimal" name="temperatura_mash"   value="<?= e((string)($agua['temperatura_mash']??'')) ?>"></td>
            <td><input type="text"   name="ph_mashh2o"         value="<?= e((string)($agua['ph_mash']??'')) ?>" class="sm"></td>
            <td><input type="text" inputmode="decimal" name="fosforico_mash"     value="<?= e((string)($agua['fosforico_mash']??'')) ?>"></td>
            <td><input type="text" inputmode="decimal" name="caso4_mash"         value="<?= e((string)($agua['caso4_mash']??'')) ?>"></td>
            <td><input type="text" inputmode="decimal" name="cacl2_mash"         value="<?= e((string)($agua['cacl2_mash']??'')) ?>"></td>
            <td><input type="text" inputmode="decimal" name="mgcl_mash"          value="<?= e((string)($agua['mgcl_mash']??'')) ?>"></td>
            <td><input type="text" inputmode="decimal" name="otro_mash"          value="<?= e((string)($agua['otro_mash']??'')) ?>"></td>
            <td><input type="text" inputmode="decimal" name="fosforico_h2o_mash" value="<?= e((string)($agua['fosforico_h2o_mash']??'')) ?>"></td>
          </tr></tbody>
        </table>
      </div>

      <div class="card-title" style="margin-top:1rem">Tratamiento Sparge</div>
      <div class="table-wrapper">
        <table class="tf">
          <thead><tr><th>Total</th><th>%RO</th><th>Temp</th><th>pH</th><th>Fosfórico</th><th>CaSO₄</th><th>CaCl₂</th><th>MgCl</th><th>Otro</th></tr></thead>
          <tbody><tr>
            <td><input type="text" inputmode="decimal" name="total_agua_sparge"    value="<?= e((string)($agua['total_agua_sparge']??'')) ?>"></td>
            <td><input type="text" inputmode="decimal" name="porcentaje_ro_sparge" value="<?= e((string)($agua['porcentaje_ro_sparge']??'')) ?>"></td>
            <td><input type="text" inputmode="decimal" name="temperatura_sparge"   value="<?= e((string)($agua['temperatura_sparge']??'')) ?>"></td>
            <td><input type="text"   name="ph_spargeh2o"         value="<?= e((string)($agua['ph_sparge']??'')) ?>" class="sm"></td>
            <td><input type="text" inputmode="decimal" name="fosforico_sparge"     value="<?= e((string)($agua['fosforico_sparge']??'')) ?>"></td>
            <td><input type="text" inputmode="decimal" name="caso4_sparge"         value="<?= e((string)($agua['caso4_sparge']??'')) ?>"></td>
            <td><input type="text" inputmode="decimal" name="cacl2_sparge"         value="<?= e((string)($agua['cacl2_sparge']??'')) ?>"></td>
            <td><input type="text" inputmode="decimal" name="mgcl_sparge"          value="<?= e((string)($agua['mgcl_sparge']??'')) ?>"></td>
            <td><input type="text" inputmode="decimal" name="otro_sparge"          value="<?= e((string)($agua['otro_sparge']??'')) ?>"></td>
          </tr></tbody>
        </table>
      </div>
    </div>

    <!-- ── Maltas ──────────────────────────────────────────── -->
    <div class="card fade-in" style="margin-bottom:1rem">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
        <div class="card-title" style="margin:0">Maltas</div>
        <button type="button" class="btn btn-ghost btn-sm" onclick="addMalta()">+ Añadir fila</button>
      </div>
      <div class="table-wrapper">
        <table class="tf" id="maltas_table">
          <thead><tr><th>Variedad</th><th>N° Lote malta</th><th>Cantidad (kg)</th><th>Uso</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($maltas_lote as $m): ?>
            <tr>
              <td><select name="malta[]"><?= opts($todas_maltas, (int)$m['malta_id']) ?></select></td>
              <td><input type="text" name="lote_malta[]" value="<?= e($m['lote_malta']??'') ?>" class="md"></td>
              <td><input type="text" name="cantidad[]"   value="<?= e((string)$m['cantidad']) ?>" class="sm"></td>
              <td><input type="text" name="tiempo[]"     value="<?= e($m['tiempo']??'') ?>" class="md"></td>
              <td><button type="button" class="btn btn-danger btn-sm" onclick="delRow(this)">✕</button></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$maltas_lote): ?>
            <tr>
              <td><select name="malta[]"><?= opts($todas_maltas, 0) ?></select></td>
              <td><input type="text" name="lote_malta[]" class="md"></td>
              <td><input type="text" name="cantidad[]"   class="sm"></td>
              <td><input type="text" name="tiempo[]"     class="md"></td>
              <td><button type="button" class="btn btn-danger btn-sm" onclick="delRow(this)">✕</button></td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── Lúpulos ─────────────────────────────────────────── -->
    <div class="card fade-in" style="margin-bottom:1rem">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
        <div class="card-title" style="margin:0">Lúpulos</div>
        <button type="button" class="btn btn-ghost btn-sm" onclick="addLupulo()">+ Añadir fila</button>
      </div>
      <div class="table-wrapper">
        <table class="tf" id="lupulo_table">
          <thead><tr><th>Variedad</th><th>N° Lote lúpulo</th><th>Cantidad (g)</th><th>IBU</th><th>Técnica</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($lupulos_lote as $l): ?>
            <tr>
              <td><select name="lupulo[]"><?= opts($todos_lupulos, (int)$l['lupulo_id']) ?></select></td>
              <td><input type="text"   name="lote_lupulo[]"     value="<?= e($l['lote_lupulo']??'') ?>" class="md"></td>
              <td><input type="number" name="cantidad_lupulo[]" value="<?= e((string)$l['cantidad']) ?>" class="sm"></td>
              <td><input type="text"   name="ibu[]"             value="<?= e((string)($l['ibu']??'')) ?>" class="sm"></td>
              <td><input type="text"   name="tiempo_lupulo[]"   value="<?= e($l['tiempo']??'') ?>" class="md"></td>
              <td><button type="button" class="btn btn-danger btn-sm" onclick="delRow(this)">✕</button></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$lupulos_lote): ?>
            <tr>
              <td><select name="lupulo[]"><?= opts($todos_lupulos, 0) ?></select></td>
              <td><input type="text"   name="lote_lupulo[]"     class="md"></td>
              <td><input type="number" name="cantidad_lupulo[]" class="sm"></td>
              <td><input type="text"   name="ibu[]"             class="sm"></td>
              <td><input type="text"   name="tiempo_lupulo[]"   class="md"></td>
              <td><button type="button" class="btn btn-danger btn-sm" onclick="delRow(this)">✕</button></td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── Levadura ────────────────────────────────────────── -->
    <div class="card fade-in" style="margin-bottom:1rem">
      <div class="card-title">Levadura</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
        <div>
          <div class="form-group">
            <label class="form-label">Cepa</label>
            <select name="cepa"><?= opts($todas_cepas, (int)($levadura_lote['cepa_id']??0), 'id', 'cepa') ?></select>
          </div>
          <div class="form-group">
            <label class="form-label">Generación</label>
            <input type="text" name="genleva" value="<?= e($levadura_lote['gen']??'') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Temp. inoculación (°C)</label>
            <input type="text" name="tempInoc" value="<?= e((string)($levadura_lote['temp_inoculacion']??'')) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Tasa inoculación</label>
            <input type="text" name="tasaInoc" value="<?= e((string)($levadura_lote['tasa_inoculacion']??'')) ?>">
          </div>
        </div>
        <div>
          <div class="form-group">
            <label class="form-label">Viabilidad (%)</label>
            <input type="text" inputmode="decimal" name="viabilidad" value="<?= e((string)($levadura_lote['viabilidad']??'')) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Kilos de biomasa</label>
            <input type="text" name="biomasa" value="<?= e((string)($levadura_lote['kilos_biomasa']??'')) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">PPM Oxígeno</label>
            <input type="text" inputmode="decimal" name="oxigenacion" value="<?= e((string)($levadura_lote['oxigenacion']??'')) ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- ── LOG cocción ─────────────────────────────────────── -->
    <div class="card fade-in" style="margin-bottom:1rem">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
        <div class="card-title" style="margin:0">LOG día de cocción</div>
        <div style="display:flex;gap:.5rem">
          <button type="button" class="btn btn-ghost btn-sm" onclick="addLOG()">+ Añadir batch</button>
          <button type="button" class="btn btn-danger btn-sm" onclick="delLastRow('tablaLOG')">− Borrar último</button>
        </div>
      </div>
      <div class="table-wrapper" style="overflow-x:auto">
        <table class="tf" id="tablaLOG" style="min-width:1100px">
          <thead><tr>
            <th>#</th>
            <th>T°1M</th><th>T°2M</th><th>T°3M</th>
            <th>pH1M</th><th>pH2M</th><th>pH3M</th>
            <th>Dens 1°M</th><th>Dens LR</th><th>pH LR</th>
            <th>T°Sp</th><th>pH Sp</th>
            <th>Vol iB</th><th>Dens pB</th><th>pH iB</th>
            <th>Vol fB</th><th>Dens fB</th><th>pH fin</th>
          </tr></thead>
          <tbody>
            <?php if ($batches): foreach ($batches as $i => $b): ?>
            <tr>
              <td style="text-align:center;color:var(--text-muted);font-weight:600"><?= $i+1 ?></td>
              <td><input type="number" name="temp_mash[<?= $i ?>]"         value="<?= e((string)($b['temp_mash']??'')) ?>" class="sm"></td>
              <td><input type="number" name="temp2_mash[<?= $i ?>]"        value="<?= e((string)($b['temp2_mash']??'')) ?>" class="sm"></td>
              <td><input type="number" name="temp3_mash[<?= $i ?>]"        value="<?= e((string)($b['temp3_mash']??'')) ?>" class="sm"></td>
              <td><input type="text"   name="ph_mash[<?= $i ?>]"           value="<?= e((string)($b['ph_mash']??'')) ?>" class="sm"></td>
              <td><input type="text"   name="ph2_mash[<?= $i ?>]"          value="<?= e((string)($b['ph2_mash']??'')) ?>" class="sm"></td>
              <td><input type="text"   name="ph3_mash[<?= $i ?>]"          value="<?= e((string)($b['ph3_mash']??'')) ?>" class="sm"></td>
              <td><input type="text"   name="dens_primer_mosto[<?= $i ?>]" value="<?= e((string)($b['dens_primer_mosto']??'')) ?>" class="md"></td>
              <td><input type="text"   name="dens_last_run[<?= $i ?>]"     value="<?= e((string)($b['dens_last_run']??'')) ?>" class="md"></td>
              <td><input type="text"   name="ph_last_run[<?= $i ?>]"       value="<?= e((string)($b['ph_last_run']??'')) ?>" class="sm"></td>
              <td><input type="number" name="temp_sparge[<?= $i ?>]"       value="<?= e((string)($b['temp_sparge']??'')) ?>" class="sm"></td>
              <td><input type="text"   name="ph_sparge[<?= $i ?>]"         value="<?= e((string)($b['ph_sparge']??'')) ?>" class="sm"></td>
              <td><input type="number" name="vol_inicial_boil[<?= $i ?>]"  value="<?= e((string)($b['vol_inicial_boil']??'')) ?>" class="sm"></td>
              <td><input type="text"   name="dens_pre_boil[<?= $i ?>]"     value="<?= e((string)($b['dens_pre_boil']??'')) ?>" class="md"></td>
              <td><input type="text"   name="ph_inicio_boil[<?= $i ?>]"    value="<?= e((string)($b['ph_inicio_boil']??'')) ?>" class="sm"></td>
              <td><input type="number" name="vol_final_boil[<?= $i ?>]"    value="<?= e((string)($b['vol_final_boil']??'')) ?>" class="sm"></td>
              <td><input type="text"   name="dens_post_boil[<?= $i ?>]"    value="<?= e((string)($b['dens_post_boil']??'')) ?>" class="md"></td>
              <td><input type="text"   name="ph_fin[<?= $i ?>]"            value="<?= e((string)($b['ph_fin']??'')) ?>" class="sm"></td>
            </tr>
            <?php endforeach; else: ?>
            <tr>
              <td style="text-align:center;color:var(--text-muted);font-weight:600">1</td>
              <td><input type="number" name="temp_mash[0]"         class="sm"></td>
              <td><input type="number" name="temp2_mash[0]"        class="sm"></td>
              <td><input type="number" name="temp3_mash[0]"        class="sm"></td>
              <td><input type="text"   name="ph_mash[0]"           class="sm"></td>
              <td><input type="text"   name="ph2_mash[0]"          class="sm"></td>
              <td><input type="text"   name="ph3_mash[0]"          class="sm"></td>
              <td><input type="text"   name="dens_primer_mosto[0]" class="md"></td>
              <td><input type="text"   name="dens_last_run[0]"     class="md"></td>
              <td><input type="text"   name="ph_last_run[0]"       class="sm"></td>
              <td><input type="number" name="temp_sparge[0]"       class="sm"></td>
              <td><input type="text"   name="ph_sparge[0]"         class="sm"></td>
              <td><input type="number" name="vol_inicial_boil[0]"  class="sm"></td>
              <td><input type="text"   name="dens_pre_boil[0]"     class="md"></td>
              <td><input type="text"   name="ph_inicio_boil[0]"    class="sm"></td>
              <td><input type="number" name="vol_final_boil[0]"    class="sm"></td>
              <td><input type="text"   name="dens_post_boil[0]"    class="md"></td>
              <td><input type="text"   name="ph_fin[0]"            class="sm"></td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── Fermentación ────────────────────────────────────── -->
    <div class="card fade-in" style="margin-bottom:1rem">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.75rem">
        <div class="card-title" style="margin:0">Seguimiento de fermentación</div>
        <div style="display:flex;gap:.5rem">
          <button type="button" class="btn btn-ghost btn-sm" onclick="addFerm()">+ Añadir día</button>
          <button type="button" class="btn btn-danger btn-sm" onclick="delLastRow('tablaFerm')">− Borrar último</button>
        </div>
      </div>
      <div class="table-wrapper">
        <table class="tf" id="tablaFerm">
          <thead><tr><th>#</th><th>Fecha</th><th>Hora</th><th>Densidad</th><th>pH</th><th>Temp</th><th>Purga</th><th>Comentarios</th></tr></thead>
          <tbody>
            <?php if ($fermentacion): foreach ($fermentacion as $i => $f): ?>
            <tr>
              <td style="text-align:center;color:var(--text-muted);font-weight:600"><?= $i+1 ?></td>
              <td><input type="date"   name="fecha[<?= $i ?>]"       value="<?= e($f['fecha']??'') ?>"></td>
              <td><input type="time"   name="hora[<?= $i ?>]"        value="<?= e($f['hora']??'') ?>"></td>
              <td><input type="text"   name="densidad[<?= $i ?>]"    value="<?= e((string)($f['densidad']??'')) ?>" class="md"></td>
              <td><input type="text"   name="ph[<?= $i ?>]"          value="<?= e((string)($f['ph']??'')) ?>" class="sm"></td>
              <td><input type="text"   name="temperatura[<?= $i ?>]" value="<?= e((string)($f['temperatura']??'')) ?>" class="sm"></td>
              <td><input type="number" name="purga[<?= $i ?>]"       value="<?= e((string)($f['purga']??'')) ?>" class="sm"></td>
              <td><input type="text"   name="comentarios[<?= $i ?>]" value="<?= e($f['comentarios']??'') ?>" style="width:100%"></td>
            </tr>
            <?php endforeach; else: ?>
            <tr>
              <td style="text-align:center;color:var(--text-muted);font-weight:600">1</td>
              <td><input type="date"   name="fecha[0]"></td>
              <td><input type="time"   name="hora[0]"></td>
              <td><input type="text"   name="densidad[0]"    class="md"></td>
              <td><input type="text"   name="ph[0]"          class="sm"></td>
              <td><input type="text"   name="temperatura[0]" class="sm"></td>
              <td><input type="number" name="purga[0]"       class="sm"></td>
              <td><input type="text"   name="comentarios[0]" style="width:100%"></td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ── Lecturas finales y envasado ─────────────────────── -->
    <div class="card fade-in" style="margin-bottom:1rem">
      <div class="card-title">Lecturas finales y envasado</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
        <div>
          <div class="form-group"><label class="form-label">DO</label><input type="text" name="lecturaDO" value="<?= e((string)($lote['DO']??'')) ?>"></div>
          <div class="form-group"><label class="form-label">DF</label><input type="text" name="lecturaDF" value="<?= e((string)($lote['DF']??'')) ?>"></div>
          <div class="form-group"><label class="form-label">pH inicial mosto</label><input type="text" name="ph_inicialMosto" value="<?= e((string)($lote['ph_mosto']??'')) ?>"></div>
          <div class="form-group"><label class="form-label">pH final fermentación</label><input type="text" name="ph_finFerm" value="<?= e((string)($lote['ph_fin_fermentacion']??'')) ?>"></div>
        </div>
        <div>
          <div class="form-group"><label class="form-label">Litros a fermentador</label><input type="text" inputmode="decimal" name="litrosAfermentar" value="<?= e((string)($lote['litros_a_fermentador']??'')) ?>"></div>
          <div class="form-group"><label class="form-label">Carb. level</label><input type="text" name="carbLevel" value="<?= e((string)($lote['carb_level']??'')) ?>"></div>
          <div class="form-group"><label class="form-label">Día de envasado</label><input type="date" name="diaEnvasado" value="<?= e($lote['dia_envasado']??'') ?>"></div>
          <div class="form-group"><label class="form-label">Litros envasados</label><input type="text" inputmode="decimal" name="ltsEnvasados" value="<?= e((string)($lote['litros_envasados']??'')) ?>"></div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Fermentador</label>
        <select name="fermentador" style="width:auto;min-width:200px">
          <?php foreach ($fermentadores as $fv): ?>
          <option value="<?= (int)$fv['id'] ?>" <?= (int)$fv['id']===(int)($lote['fermentador_id']??0)?'selected':'' ?>><?= e($fv['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <!-- ── Enlatado ────────────────────────────────────────── -->
    <div class="card fade-in" style="margin-bottom:1rem">
      <div class="card-title">Enlatado</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
        <div>
          <div class="form-group"><label class="form-label">Día de enlatado</label><input type="date" name="diaEnlatado" value="<?= e($enlatado['diaEnlatado']??'') ?>"></div>
          <div class="form-group"><label class="form-label">Presión barrido</label><input type="text" name="presionbarrido" value="<?= e((string)($enlatado['presionbarrido']??'')) ?>"></div>
          <div class="form-group"><label class="form-label">Presión línea llenado</label><input type="text" name="presionenenlatadora" value="<?= e((string)($enlatado['presionenenlatadora']??'')) ?>"></div>
          <div class="form-group"><label class="form-label">Presión tanque</label><input type="text" name="presionentanque" value="<?= e((string)($enlatado['presionentanque']??'')) ?>"></div>
          <div class="form-group"><label class="form-label">Tiempo llenado</label><input type="text" inputmode="decimal" name="tiempollenado" value="<?= e((string)($enlatado['tiempollenado']??'')) ?>"></div>
          <div class="form-group"><label class="form-label">Tiempo barrido 1</label><input type="text" inputmode="decimal" name="tiempo1" value="<?= e((string)($enlatado['tiempo1']??'')) ?>"></div>
          <div class="form-group"><label class="form-label">Tiempo barrido 2</label><input type="text" inputmode="decimal" name="tiempo2" value="<?= e((string)($enlatado['tiempo2']??'')) ?>"></div>
        </div>
        <div>
          <div class="form-group"><label class="form-label">Temp. tanque</label><input type="text" name="tempentanque" value="<?= e((string)($enlatado['tempentanque']??'')) ?>"></div>
          <div class="form-group"><label class="form-label">Temp. enlatadora</label><input type="text" name="tempenenlatadora" value="<?= e((string)($enlatado['tempenenlatadora']??'')) ?>"></div>
          <div class="form-group"><label class="form-label">Temp. ambiente</label><input type="text" name="tempambiente" value="<?= e((string)($enlatado['tempambiente']??'')) ?>"></div>
          <div class="form-group"><label class="form-label">DO</label><input type="text" inputmode="decimal" name="disoxigen" value="<?= e((string)($enlatado['disoxigen']??'')) ?>"></div>
          <div class="form-group"><label class="form-label">TPO</label><input type="text" inputmode="decimal" name="tpo" value="<?= e((string)($enlatado['tpo']??'')) ?>"></div>
          <div class="form-group"><label class="form-label">Latas cerradas desc.</label><input type="text" inputmode="decimal" name="latascerradasDes" value="<?= e((string)($enlatado['latascerradasDes']??'')) ?>"></div>
          <div class="form-group"><label class="form-label">Latas vacías desc.</label><input type="text" inputmode="decimal" name="latasvaciasDes" value="<?= e((string)($enlatado['latasvaciasDes']??'')) ?>"></div>
          <div class="form-group"><label class="form-label">Tapas desc.</label><input type="text" inputmode="decimal" name="tapasDes" value="<?= e((string)($enlatado['tapasDes']??'')) ?>"></div>
          <div class="form-group"><label class="form-label">Latas OK</label><input type="text" inputmode="decimal" name="latasOK" value="<?= e((string)($enlatado['latasOK']??'')) ?>"></div>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Observaciones</label>
        <input type="text" name="observacionesenlatado" value="<?= e($enlatado['observacionesenlatado']??'') ?>">
      </div>
    </div>

    <!-- ── Comentarios ─────────────────────────────────────── -->
    <div class="card fade-in" style="margin-bottom:1.5rem">
      <div class="card-title">Comentarios generales</div>
      <textarea name="comentariosGeneral" rows="3" style="width:100%;resize:vertical"><?= e($lote['comentarios']??'') ?></textarea>
    </div>

    <div style="display:flex;gap:.75rem;margin-bottom:2rem" class="fade-in">
      <button type="submit" class="btn btn-primary btn-lg"
              onclick="return confirm('¿Guardar todos los cambios?')">Guardar cambios</button>
      <a href="detalles_lote?id=<?= $id_lote ?>" class="btn btn-ghost btn-lg">Cancelar</a>
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

function delLastRow(tableId) {
  const tb = document.querySelector('#'+tableId+' tbody');
  if (tb.rows.length > 1) tb.deleteRow(tb.rows.length-1);
}

function addMalta() {
  const tb = document.querySelector('#maltas_table tbody');
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><select name="malta[]">${buildOpts(MALTAS)}</select></td>
    <td><input type="text" name="lote_malta[]" class="md"></td>
    <td><input type="text" name="cantidad[]"   class="sm"></td>
    <td><input type="text" name="tiempo[]"     class="md"></td>
    <td><button type="button" class="btn btn-danger btn-sm" onclick="delRow(this)">✕</button></td>`;
  tb.appendChild(tr);
}

function addLupulo() {
  const tb = document.querySelector('#lupulo_table tbody');
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><select name="lupulo[]">${buildOpts(LUPULOS)}</select></td>
    <td><input type="text"   name="lote_lupulo[]"     class="md"></td>
    <td><input type="number" name="cantidad_lupulo[]" class="sm"></td>
    <td><input type="text"   name="ibu[]"             class="sm"></td>
    <td><input type="text"   name="tiempo_lupulo[]"   class="md"></td>
    <td><button type="button" class="btn btn-danger btn-sm" onclick="delRow(this)">✕</button></td>`;
  tb.appendChild(tr);
}

function addLOG() {
  const tb  = document.querySelector('#tablaLOG tbody');
  const idx = tb.rows.length;
  const campos = ['temp_mash','temp2_mash','temp3_mash','ph_mash','ph2_mash','ph3_mash',
                  'dens_primer_mosto','dens_last_run','ph_last_run','temp_sparge','ph_sparge',
                  'vol_inicial_boil','dens_pre_boil','ph_inicio_boil','vol_final_boil','dens_post_boil','ph_fin'];
  const tipos  = [1,1,1,0,0,0,0,0,0,1,0,1,0,0,1,0,0];
  const clases = ['sm','sm','sm','sm','sm','sm','md','md','sm','sm','sm','sm','md','sm','sm','md','sm'];
  let cells = `<td style="text-align:center;color:var(--text-muted);font-weight:600">${idx+1}</td>`;
  campos.forEach((c,i) => {
    cells += `<td><input type="${tipos[i]?'number':'text'}" name="${c}[${idx}]" class="${clases[i]}"></td>`;
  });
  const tr = document.createElement('tr');
  tr.innerHTML = cells;
  tb.appendChild(tr);
}

function addFerm() {
  const tb  = document.querySelector('#tablaFerm tbody');
  const idx = tb.rows.length;
  const lastRow = tb.rows[tb.rows.length-1];
  const lastDate = lastRow?.querySelector('input[type="date"]')?.value || '';
  const lastTime = lastRow?.querySelector('input[type="time"]')?.value || '';
  let fechaNueva = '';
  if (lastDate) {
    const d = new Date(lastDate); d.setDate(d.getDate()+1);
    fechaNueva = d.toISOString().slice(0,10);
  }
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td style="text-align:center;color:var(--text-muted);font-weight:600">${idx+1}</td>
    <td><input type="date"   name="fecha[${idx}]"       value="${fechaNueva}"></td>
    <td><input type="time"   name="hora[${idx}]"        value="${lastTime}"></td>
    <td><input type="text"   name="densidad[${idx}]"    class="md"></td>
    <td><input type="text"   name="ph[${idx}]"          class="sm"></td>
    <td><input type="text"   name="temperatura[${idx}]" class="sm"></td>
    <td><input type="number" name="purga[${idx}]"       class="sm"></td>
    <td><input type="text"   name="comentarios[${idx}]" style="width:100%"></td>`;
  tb.appendChild(tr);
}

function loadContent(page) { window.location.href = page; }

// ── Enter en comentarios de fermentación → nueva fila ─────────────────────
document.addEventListener('keydown', function(e) {
  const inp = e.target;
  // Solo actuar en inputs de comentarios de la tabla de fermentación
  if (!inp.matches('#tablaFerm input[name^="comentarios"]')) return;

  if (e.key === 'Enter') {
    e.preventDefault();
    const tb   = document.querySelector('#tablaFerm tbody');
    const filas = tb.querySelectorAll('tr');
    const filaActual = inp.closest('tr');
    const ultimaFila = filas[filas.length - 1];

    // Si estamos en la última fila → agregar nueva
    if (filaActual === ultimaFila) {
      addFerm();
      // Foco al campo fecha de la nueva fila
      setTimeout(() => {
        const nuevaFila = tb.querySelectorAll('tr');
        nuevaFila[nuevaFila.length - 1].querySelector('input[type="date"]')?.focus();
      }, 50);
    } else {
      // Si no es la última, ir a comentarios de la siguiente fila
      const idx   = Array.from(filas).indexOf(filaActual);
      const next  = filas[idx + 1];
      next?.querySelector('input[name^="comentarios"]')?.focus();
    }
  }

  if (e.key === 'Tab' && !e.shiftKey) {
    // Tab en comentarios → si es el último campo de la fila, addFerm
    const fila = inp.closest('tr');
    const inputs = Array.from(fila.querySelectorAll('input'));
    const esUltimo = inputs.indexOf(inp) === inputs.length - 1;
    const tb = document.querySelector('#tablaFerm tbody');
    const filas = Array.from(tb.querySelectorAll('tr'));
    const esUltimaFila = fila === filas[filas.length - 1];

    if (esUltimo && esUltimaFila) {
      e.preventDefault();
      addFerm();
      setTimeout(() => {
        const nuevaFila = tb.querySelectorAll('tr');
        nuevaFila[nuevaFila.length - 1].querySelector('input[type="date"]')?.focus();
      }, 50);
    }
  }
});
</script>
</body>
</html>
