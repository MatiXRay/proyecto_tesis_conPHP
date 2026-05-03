<?php
/**
 * BIALYSTOK BREWING CO — Planilla de cata simplificada
 * Diseñada para completar directamente en tablet/celular en la cervecería
 */

require_once 'auth.php';
requireLogin();
require_once 'conexion.php';

$id_lote = getIntParam('id') ?? getIntParam('id_lote');
if (!$id_lote) { header('Location: lotes'); exit; }
$origen = in_array($_GET['origen'] ?? '', ['panel_sensorial','panel_cata','detalles_lote']) ? $_GET['origen'] : null;
if ($origen === 'panel_sensorial')     $volver_url = 'panel_sensorial';
elseif ($origen === 'panel_cata')     $volver_url = 'panel_cata';
else                                  $volver_url = isTaster() ? 'panel_cata' : 'detalles_lote?id='.$id_lote;

try {
    $pdo  = getPDO();
    $stmt = $pdo->prepare(
        "SELECT lc.id, lc.numero_lote, lc.og, lc.fg, lc.ibu, lc.abv,
                ec.nombre AS estilo, ec.descripcion AS estilo_desc
         FROM lotes_cerveza lc
         LEFT JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
         WHERE lc.id = ?"
    );
    $stmt->execute([$id_lote]);
    $lote = $stmt->fetch();
    if (!$lote) { header('Location: lotes'); exit; }
} catch (PDOException $ex) {
    error_log('[planilla_cata] ' . $ex->getMessage());
    header('Location: lotes'); exit;
}

$menu_activo = 'lotes';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <title>Cata · <?= e(strtoupper($lote['numero_lote'])) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/bialy-design-system.css">
  <style>
    /* ── Escala de intensidad ─────────────────────────── */
    .escala { display:flex; gap:.35rem; align-items:center; }
    .escala-btn {
      width:38px; height:38px; border-radius:50%; border:2px solid var(--color-border-md);
      background:var(--color-surface-2); color:var(--text-muted); font-weight:600;
      font-size:.85rem; cursor:pointer; transition:.15s; display:flex; align-items:center; justify-content:center;
      font-family:'DM Mono',monospace;
    }
    .escala-btn:hover { border-color:var(--amber-400); color:var(--text-amber); }
    .escala-btn.sel   { background:var(--amber-400); border-color:var(--amber-400); color:#fff; }
    .escala-label { font-size:.68rem; color:var(--text-muted); margin-left:.25rem; }

    /* ── Defectos ─────────────────────────────────────── */
    .defecto-row { display:flex; align-items:center; justify-content:space-between; padding:.6rem 0; border-bottom:1px solid var(--color-border); }
    .defecto-nombre { font-size:.88rem; font-weight:500; }
    .defecto-sub    { font-size:.72rem; color:var(--text-muted); }
    .def-escala { display:flex; gap:.3rem; }
    .def-btn {
      width:34px; height:34px; border-radius:6px; border:1.5px solid var(--color-border-md);
      background:var(--color-surface-2); color:var(--text-muted); font-weight:700;
      font-size:.8rem; cursor:pointer; transition:.15s;
      display:flex; align-items:center; justify-content:center; font-family:'DM Mono',monospace;
    }
    .def-btn.sel-0 { background:var(--color-surface-3); border-color:var(--color-border-md); color:var(--text-secondary); }
    .def-btn.sel-1 { background:rgba(74,170,74,.2); border-color:var(--amber-400); color:var(--text-amber); }
    .def-btn.sel-2 { background:rgba(200,146,42,.25); border-color:var(--color-warning); color:var(--color-warning); }
    .def-btn.sel-3 { background:rgba(217,96,96,.25); border-color:var(--color-danger); color:var(--color-danger); }

    /* ── Radio pills ──────────────────────────────────── */
    .pills { display:flex; gap:.4rem; flex-wrap:wrap; }
    .pill {
      padding:.35rem .85rem; border-radius:99px; border:1.5px solid var(--color-border-md);
      background:var(--color-surface-2); color:var(--text-muted); font-size:.82rem;
      cursor:pointer; transition:.15s; font-weight:500;
    }
    .pill:hover { border-color:var(--amber-400); color:var(--text-amber); }
    .pill.sel   { background:var(--amber-400); border-color:var(--amber-400); color:#fff; }

    /* ── Puntaje global ───────────────────────────────── */
    .puntaje-wrap { display:flex; gap:.5rem; flex-wrap:wrap; }
    .puntaje-btn {
      width:48px; height:48px; border-radius:10px; border:2px solid var(--color-border-md);
      background:var(--color-surface-2); color:var(--text-muted); font-size:1rem;
      font-weight:700; cursor:pointer; transition:.15s; font-family:'DM Mono',monospace;
      display:flex; align-items:center; justify-content:center;
    }
    .puntaje-btn:hover { border-color:var(--amber-400); color:var(--text-amber); }
    .puntaje-btn.sel  { background:var(--amber-400); border-color:var(--amber-400); color:#fff; font-size:1.1rem; }

    /* ── Sección ──────────────────────────────────────── */
    .seccion { margin-bottom:1rem; }
    .seccion-header {
      display:flex; align-items:center; gap:.6rem;
      font-size:.72rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase;
      color:var(--text-muted); margin-bottom:.75rem; padding-bottom:.4rem;
      border-bottom:1px solid var(--color-border);
    }
    .seccion-icon { font-size:1rem; }
    .campo-label { font-size:.82rem; font-weight:500; color:var(--text-secondary); margin-bottom:.4rem; display:block; }
    .campo-wrap  { margin-bottom:.9rem; }

    /* ── Desvío ───────────────────────────────────────── */
    .desvio-input { width:100%; padding:.5rem .75rem; background:var(--color-surface-3); border:1px solid var(--color-border-md); border-radius:var(--radius-sm); color:var(--text-primary); font-family:'DM Sans',sans-serif; font-size:.88rem; resize:vertical; }
    .desvio-input:focus { outline:none; border-color:var(--amber-400); }

    /* ── Sin sidebar ──────────────────────────────────── */
    .cata-wrap    { min-height:100vh; background:var(--color-bg); }
    .cata-header  {
      display:flex; align-items:flex-start; justify-content:space-between;
      padding:1.25rem 2rem; background:var(--color-surface);
      border-bottom:1px solid var(--color-border); gap:1rem;
    }
    .cata-brand   { font-size:.65rem; text-transform:uppercase; letter-spacing:.1em; color:var(--text-muted); margin-bottom:.2rem; }
    .cata-title   { font-size:1.3rem; font-weight:600; color:var(--text-primary); margin:0 0 .3rem; }
    .cata-meta    { display:flex; gap:.75rem; flex-wrap:wrap; }
    .cata-meta span { font-family:'DM Mono',monospace; font-size:.78rem; color:var(--text-muted);
                      background:var(--color-surface-2); padding:.15rem .5rem; border-radius:4px; }
    .cata-content { max-width:1100px; margin:0 auto; padding:1.5rem 2rem 3rem; }
  </style>
</head>
<body>
<div class="cata-wrap">

  <!-- Header sin sidebar -->
  <div class="cata-header">
    <div>
      <div class="cata-brand">BRAUMEISTER · Análisis Sensorial</div>
      <h1 class="cata-title"><?= e(strtoupper($lote['numero_lote'])) ?> — <?= e($lote['estilo'] ?? '—') ?></h1>
      <div class="cata-meta">
        <?php if ($lote['ibu']): ?><span><?= e($lote['ibu']) ?> IBU</span><?php endif; ?>
        <?php if ($lote['abv']): ?><span><?= e($lote['abv']) ?>% ABV</span><?php endif; ?>
        <?php if ($lote['og']):  ?><span>OG <?= e($lote['og']) ?></span><?php endif; ?>
        <?php if ($lote['fg']):  ?><span>FG <?= e($lote['fg']) ?></span><?php endif; ?>
      </div>
    </div>
    <a href="panel_cata" class="btn btn-ghost btn-sm">← Volver</a>
  </div>

  <div class="cata-content">
  <form action="guardar_cata" method="POST" id="formCata">
    <?= csrfField() ?>
    <input type="hidden" name="id_lote" value="<?= $id_lote ?>">
    <input type="hidden" name="id_usuario" value="<?= (int)$_SESSION['id'] ?>">

    <!-- Campos ocultos para valores seleccionados -->
    <input type="hidden" name="origen_muestra"        value="línea" id="h_origen">
    <input type="hidden" name="tiempo_transcurrido"   value="0">

    <!-- Apariencia -->
    <input type="hidden" name="claridad_intensidad"   id="h_turbidez"    value="0">
    <input type="hidden" name="retencion_intensidad"  id="h_espuma"      value="0">
    <input type="hidden" name="color_cerveza"         id="h_color_ok"    value="">
    <input type="hidden" name="tamano_intensidad"     value="0">
    <input type="hidden" name="textura_intensidad"    value="0">
    <input type="hidden" name="color_espuma"          id="h_color_espuma" value="">
    <input type="hidden" name="color_otro"            value="">
    <input type="hidden" name="apariencia_puntaje"    id="h_ap_puntaje"  value="3">

    <!-- Aroma -->
    <input type="hidden" name="malta_intensidad"      id="h_ar_malta"    value="0">
    <input type="hidden" name="lupulo_intensidad"     id="h_ar_lupulo"   value="0">
    <input type="hidden" name="esteres_intensidad"    id="h_ar_esteres"  value="0">
    <input type="hidden" name="fenoles_intensidad"    value="0">
    <input type="hidden" name="alcohol_intensidad"    value="0">
    <input type="hidden" name="dulzor_intensidad"     value="0">
    <input type="hidden" name="acidez_intensidad"     value="0">
    <input type="hidden" name="otros_intensidad"      value="0">
    <input type="hidden" name="maltas_atributos"      value="">
    <input type="hidden" name="lupulo_atributos"      value="">
    <input type="hidden" name="esteres_atributos"     value="">
    <input type="hidden" name="otros_atributos"       id="h_ar_otros"    value="">
    <input type="hidden" name="aroma_puntaje"         id="h_ar_puntaje"  value="3">

    <!-- Sabor -->
    <input type="hidden" name="sabor_malta_intensidad"   id="h_sa_malta"   value="0">
    <input type="hidden" name="sabor_lupulo_intensidad"  id="h_sa_lupulo"  value="0">
    <input type="hidden" name="sabor_esteres_intensidad" value="0">
    <input type="hidden" name="sabor_fenoles_intensidad" value="0">
    <input type="hidden" name="sabor_alcohol_intensidad" value="0">
    <input type="hidden" name="sabor_dulzor_intensidad"  id="h_sa_dulzor"  value="0">
    <input type="hidden" name="sabor_acidez_intensidad"  value="0">
    <input type="hidden" name="sabor_otros_intensidad"   value="0">
    <input type="hidden" name="sabor_malta_atributos"    value="">
    <input type="hidden" name="sabor_lupulo_atributos"   value="">
    <input type="hidden" name="sabor_esteres_atributos"  value="">
    <input type="hidden" name="sabor_otros_atributos"    value="">
    <input type="hidden" name="balance"                  id="h_balance"    value="">
    <input type="hidden" name="sabor_puntaje"            id="h_sa_puntaje" value="3">

    <!-- Mouthfeel -->
    <input type="hidden" name="cuerpo_intensidad"        id="h_mf_cuerpo"  value="0">
    <input type="hidden" name="carbonatacion_intensidad" id="h_mf_carb"    value="0">
    <input type="hidden" name="calentamiento_intensidad" id="h_mf_calent" value="0">
    <input type="hidden" name="cremosidad_intensidad"    id="h_mf_cremo"  value="0">
    <input type="hidden" name="astringencia_intensidad"  id="h_mf_astri"   value="0">
    <input type="hidden" name="mouthfeel_fallas"         value="">
    <input type="hidden" name="mouthfeel_final"          id="h_mf_final"   value="">
    <input type="hidden" name="mouthfeel_puntaje"        id="h_mf_puntaje" value="3">

    <!-- Impresión / puntaje -->
    <input type="hidden" name="impresion_puntaje"        id="h_puntaje_global" value="0">

    <!-- Defectos -->
    <input type="hidden" name="fallas" id="h_fallas" value="">

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem" class="fade-in">

      <!-- ══════════════════════════════════════════════════
           1. APARIENCIA
      ═══════════════════════════════════════════════════ -->
      <div class="card seccion">
        <div class="seccion-header"><span class="seccion-icon">👁️</span> Apariencia</div>

        <div class="campo-wrap">
          <span class="campo-label">Color de la cerveza</span>
          <input type="text" class="desvio-input" placeholder="Ej: Dorado brillante, Ámbar oscuro…"
                 style="height:36px;resize:none"
                 oninput="document.getElementById('h_color_ok').value=this.value">
        </div>
        <div class="campo-wrap">
          <span class="campo-label">Color de espuma</span>
          <input type="text" class="desvio-input" placeholder="Ej: Blanca, Crema, Beige…"
                 style="height:36px;resize:none"
                 oninput="document.getElementById('h_color_espuma').value=this.value">
        </div>
        <div class="campo-wrap">
          <span class="campo-label">¿Dentro del estilo?</span>
          <div class="pills" id="pills-color">
            <div class="pill" onclick="selPill('pills-color','h_color_ok_2',this,'OK')">✓ OK</div>
            <div class="pill" onclick="selPill('pills-color','h_color_ok_2',this,'Fuera de estilo')">✗ Fuera de estilo</div>
          </div>
          <input type="hidden" id="h_color_ok_2" value="">
        </div>

        <div class="campo-wrap">
          <span class="campo-label">Turbidez <span class="escala-label">(1 brillante → 5 muy turbia)</span></span>
          <div class="escala" id="esc-turbidez">
            <?php for ($i=1;$i<=5;$i++): ?>
            <button type="button" class="escala-btn" onclick="selEscala('esc-turbidez','h_turbidez',<?= $i ?>,this)"><?= $i ?></button>
            <?php endfor; ?>
          </div>
        </div>

        <div class="campo-wrap">
          <span class="campo-label">Retención de espuma <span class="escala-label">(1 mala → 5 excelente)</span></span>
          <div class="escala" id="esc-espuma">
            <?php for ($i=1;$i<=5;$i++): ?>
            <button type="button" class="escala-btn" onclick="selEscala('esc-espuma','h_espuma',<?= $i ?>,this)"><?= $i ?></button>
            <?php endfor; ?>
          </div>
        </div>

        <div class="campo-wrap">
          <span class="campo-label">Comentarios de apariencia</span>
          <textarea class="desvio-input" name="apariencia_comentario" rows="2" placeholder="Observaciones…"></textarea>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════
           2. AROMA
      ═══════════════════════════════════════════════════ -->
      <div class="card seccion">
        <div class="seccion-header"><span class="seccion-icon">👃</span> Aroma — Intensidad 1–5</div>

        <?php
        $aromas = [
          ['id'=>'esc-ar-malta',   'hidden'=>'h_ar_malta',   'label'=>'Maltoso'],
          ['id'=>'esc-ar-lupulo',  'hidden'=>'h_ar_lupulo',  'label'=>'Lupulado'],
          ['id'=>'esc-ar-esteres', 'hidden'=>'h_ar_esteres', 'label'=>'Ésteres (frutal / floral)'],
        ];
        foreach ($aromas as $a): ?>
        <div class="campo-wrap">
          <span class="campo-label"><?= $a['label'] ?></span>
          <div class="escala" id="<?= $a['id'] ?>">
            <?php for ($i=1;$i<=5;$i++): ?>
            <button type="button" class="escala-btn" onclick="selEscala('<?= $a['id'] ?>','<?= $a['hidden'] ?>',<?= $i ?>,this)"><?= $i ?></button>
            <?php endfor; ?>
          </div>
        </div>
        <?php endforeach; ?>

        <div class="campo-wrap">
          <span class="campo-label">Otros aromas</span>
          <textarea class="desvio-input" rows="1" placeholder="Descripción libre…"
                    oninput="document.getElementById('h_ar_otros').value=this.value"></textarea>
        </div>

        <div class="campo-wrap">
          <span class="campo-label">Comentarios de aroma</span>
          <textarea class="desvio-input" name="aroma_comentario" rows="2" placeholder="Observaciones…"></textarea>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════
           3. SABOR
      ═══════════════════════════════════════════════════ -->
      <div class="card seccion">
        <div class="seccion-header"><span class="seccion-icon">👅</span> Sabor — Intensidad 1–5</div>

        <?php
        $sabores = [
          ['id'=>'esc-sa-amargor', 'hidden'=>'h_sa_lupulo', 'label'=>'Amargor'],
          ['id'=>'esc-sa-dulzor',  'hidden'=>'h_sa_dulzor', 'label'=>'Dulzor'],
          ['id'=>'esc-sa-malta',   'hidden'=>'h_sa_malta',  'label'=>'Malta'],
        ];
        foreach ($sabores as $s): ?>
        <div class="campo-wrap">
          <span class="campo-label"><?= $s['label'] ?></span>
          <div class="escala" id="<?= $s['id'] ?>">
            <?php for ($i=1;$i<=5;$i++): ?>
            <button type="button" class="escala-btn" onclick="selEscala('<?= $s['id'] ?>','<?= $s['hidden'] ?>',<?= $i ?>,this)"><?= $i ?></button>
            <?php endfor; ?>
          </div>
        </div>
        <?php endforeach; ?>

        <div class="campo-wrap">
          <span class="campo-label">Balance malta ↔ lúpulo</span>
          <div class="pills" id="pills-balance">
            <div class="pill" onclick="selPill('pills-balance','h_balance',this,'Malta')">← Malta</div>
            <div class="pill" onclick="selPill('pills-balance','h_balance',this,'Balanceado')">Balanceado</div>
            <div class="pill" onclick="selPill('pills-balance','h_balance',this,'Lúpulo')">Lúpulo →</div>
          </div>
        </div>

        <div class="campo-wrap">
          <span class="campo-label">Final</span>
          <div class="pills" id="pills-final">
            <div class="pill" onclick="selPill('pills-final','h_mf_final',this,'Seco')">Seco</div>
            <div class="pill" onclick="selPill('pills-final','h_mf_final',this,'Medio')">Medio</div>
            <div class="pill" onclick="selPill('pills-final','h_mf_final',this,'Dulce')">Dulce</div>
          </div>
        </div>

        <div class="campo-wrap">
          <span class="campo-label">Comentarios de sabor</span>
          <textarea class="desvio-input" name="sabor_comentario" rows="2" placeholder="Observaciones…"></textarea>
        </div>
      </div>

      <!-- ══════════════════════════════════════════════════
           4. SENSACIÓN EN BOCA
      ═══════════════════════════════════════════════════ -->
      <div class="card seccion">
        <div class="seccion-header"><span class="seccion-icon">💧</span> Sensación en boca</div>

        <div class="campo-wrap">
          <span class="campo-label">Cuerpo <span class="escala-label">(1 muy liviano → 5 muy pesado)</span></span>
          <div class="escala" id="esc-cuerpo">
            <?php for ($i=1;$i<=5;$i++): ?>
            <button type="button" class="escala-btn" onclick="selEscala('esc-cuerpo','h_mf_cuerpo',<?= $i ?>,this)"><?= $i ?></button>
            <?php endfor; ?>
          </div>
        </div>

        <div class="campo-wrap">
          <span class="campo-label">Carbonatación</span>
          <div class="pills" id="pills-carb">
            <div class="pill" onclick="selPill('pills-carb','h_mf_carb',this,'1')">Baja</div>
            <div class="pill" onclick="selPill('pills-carb','h_mf_carb',this,'3')">OK</div>
            <div class="pill" onclick="selPill('pills-carb','h_mf_carb',this,'5')">Alta</div>
          </div>
        </div>

        <div class="campo-wrap">
          <span class="campo-label">Calentamiento / alcohol <span class="escala-label">(1 ninguno → 5 muy marcado)</span></span>
          <div class="escala" id="esc-calent">
            <?php for ($i=1;$i<=5;$i++): ?>
            <button type="button" class="escala-btn" onclick="selEscala('esc-calent','h_mf_calent',<?= $i ?>,this)"><?= $i ?></button>
            <?php endfor; ?>
          </div>
        </div>

        <div class="campo-wrap">
          <span class="campo-label">Cremosidad <span class="escala-label">(1 muy seca → 5 muy cremosa)</span></span>
          <div class="escala" id="esc-cremo">
            <?php for ($i=1;$i<=5;$i++): ?>
            <button type="button" class="escala-btn" onclick="selEscala('esc-cremo','h_mf_cremo',<?= $i ?>,this)"><?= $i ?></button>
            <?php endfor; ?>
          </div>
        </div>

        <div class="campo-wrap">
          <span class="campo-label">Astringencia <span class="escala-label">(0 ninguna → 5 intensa)</span></span>
          <div class="escala" id="esc-astri">
            <button type="button" class="escala-btn" onclick="selEscala('esc-astri','h_mf_astri',0,this)">0</button>
            <?php for ($i=1;$i<=5;$i++): ?>
            <button type="button" class="escala-btn" onclick="selEscala('esc-astri','h_mf_astri',<?= $i ?>,this)"><?= $i ?></button>
            <?php endfor; ?>
          </div>
        </div>

        <div class="campo-wrap">
          <span class="campo-label">Comentarios</span>
          <textarea class="desvio-input" name="mouthfeel_comentario" rows="2" placeholder="Observaciones…"></textarea>
        </div>
      </div>

    </div><!-- /grid 2 col -->

    <!-- ══════════════════════════════════════════════════
         5. DEFECTOS
    ═══════════════════════════════════════════════════ -->
    <div class="card seccion fade-in" style="margin-bottom:1rem">
      <div class="seccion-header"><span class="seccion-icon">🚨</span> Defectos — Intensidad: 0 ninguno · 1 leve · 2 moderado · 3 marcado</div>
      <?php
      $defectos = [
        ['key'=>'diacetilo',     'nombre'=>'Diacetilo',              'sub'=>'Manteca / margarina'],
        ['key'=>'acetaldehido',  'nombre'=>'Acetaldehído',           'sub'=>'Manzana verde'],
        ['key'=>'dms',           'nombre'=>'DMS',                    'sub'=>'Choclo / vegetales cocidos'],
        ['key'=>'oxidacion',     'nombre'=>'Oxidación',              'sub'=>'Cartón / miel vieja / papel'],
        ['key'=>'fenoles',       'nombre'=>'Fenoles',                'sub'=>'Plástico / medicinal / clavo'],
        ['key'=>'astringencia',  'nombre'=>'Astringencia vegetal',   'sub'=>'Polifenoles / taninos'],
        ['key'=>'alcohol',       'nombre'=>'Alcohol caliente',       'sub'=>'Fusel / ardor'],
        ['key'=>'hopburn',       'nombre'=>'Hop burn',               'sub'=>'Ardor de lúpulo en crudo'],
      ];
      foreach ($defectos as $d): ?>
      <div class="defecto-row">
        <div>
          <div class="defecto-nombre"><?= $d['nombre'] ?></div>
          <div class="defecto-sub"><?= $d['sub'] ?></div>
        </div>
        <div class="def-escala" id="def-<?= $d['key'] ?>">
          <?php for ($i=0;$i<=3;$i++): ?>
          <button type="button" class="def-btn <?= $i===0?'sel-0':'' ?>"
                  onclick="selDefecto('def-<?= $d['key'] ?>',<?= $i ?>,this)"><?= $i ?></button>
          <?php endfor; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- ══════════════════════════════════════════════════
         6. DESVÍO VS OBJETIVO
    ═══════════════════════════════════════════════════ -->
    <div class="card seccion fade-in" style="margin-bottom:1rem">
      <div class="seccion-header"><span class="seccion-icon">🎯</span> Desvío vs objetivo</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="campo-wrap">
          <span class="campo-label">¿Está dentro del perfil esperado?</span>
          <div class="pills" id="pills-perfil">
            <div class="pill" onclick="selPill('pills-perfil','h_perfil',this,'si')">✓ Sí</div>
            <div class="pill" onclick="selPill('pills-perfil','h_perfil',this,'no')">✗ No</div>
          </div>
          <input type="hidden" id="h_perfil" name="impresion_comentario" value="">
        </div>
        <div class="campo-wrap">
          <span class="campo-label">¿Qué está fuera de lugar?</span>
          <textarea class="desvio-input" id="desvio_desc" rows="2"
                    placeholder="Ej: Más dulce de lo esperado para el estilo…"
                    oninput="actualizarImpresion()"></textarea>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         7. ACCIONES CONCRETAS
    ═══════════════════════════════════════════════════ -->
    <div class="card seccion fade-in" style="margin-bottom:1rem">
      <div class="seccion-header"><span class="seccion-icon">🔧</span> Acciones concretas</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
        <div class="campo-wrap">
          <span class="campo-label">🔍 Posible causa</span>
          <textarea class="desvio-input" name="causa" rows="3"
                    placeholder="Ej: Exceso de contacto vegetal en dry hop…"></textarea>
        </div>
        <div class="campo-wrap">
          <span class="campo-label">🛠 Acción para próximo lote</span>
          <textarea class="desvio-input" name="accion" rows="3"
                    placeholder="Ej: Bajar carga por etapa + recircular menos…"></textarea>
        </div>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         8. IMPRESIÓN GENERAL
    ═══════════════════════════════════════════════════ -->
    <div class="card seccion fade-in" style="margin-bottom:1rem">
      <div class="seccion-header"><span class="seccion-icon">💬</span> Impresión general</div>
      <div class="campo-wrap">
        <span class="campo-label">Comentario libre — resumen de la cata</span>
        <textarea class="desvio-input" name="impresion_libre" id="impresion_libre" rows="3"
                  placeholder="Resumen general, sensaciones destacadas, comparación con lotes anteriores…"
                  oninput="actualizarImpresion()"></textarea>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════
         9. PUNTAJE GLOBAL
    ═══════════════════════════════════════════════════ -->
    <div class="card seccion fade-in" style="margin-bottom:1.5rem">
      <div class="seccion-header"><span class="seccion-icon">⭐</span> Puntaje global</div>
      <div class="puntaje-wrap" id="puntaje-global">
        <?php for ($i=1;$i<=10;$i++): ?>
        <button type="button" class="puntaje-btn"
                onclick="selPuntaje(<?= $i ?>,this)"><?= $i ?></button>
        <?php endfor; ?>
      </div>
      <p style="font-size:.72rem;color:var(--text-muted);margin-top:.6rem">
        1–3 Defectuosa · 4–5 Mejorable · 6–7 Aceptable · 8–9 Buena · 10 Excelente
      </p>
    </div>

    <div style="display:flex;gap:.75rem;margin-bottom:2rem" class="fade-in">
      <button type="submit" class="btn btn-primary btn-lg">Guardar cata</button>
      <a href="panel_cata" class="btn btn-ghost btn-lg">Cancelar</a>
    </div>

  </form>

</div><!-- /cata-wrap -->

<script>
// ── Escala circular (1–5) ─────────────────────────────────
function selEscala(groupId, hiddenId, val, btn) {
  document.querySelectorAll('#'+groupId+' .escala-btn').forEach(b => b.classList.remove('sel'));
  btn.classList.add('sel');
  document.getElementById(hiddenId).value = val;
}

// ── Pills (radio simulado) ────────────────────────────────
function selPill(groupId, hiddenId, el, val) {
  document.querySelectorAll('#'+groupId+' .pill').forEach(p => p.classList.remove('sel'));
  el.classList.add('sel');
  document.getElementById(hiddenId).value = val;
  if (groupId === 'pills-perfil') actualizarImpresion();
}

// ── Defectos (0–3 con colores) ───────────────────────────
const DEFECTOS_MAP = {};

function selDefecto(groupId, val, btn) {
  const btns = document.querySelectorAll('#'+groupId+' .def-btn');
  btns.forEach(b => { b.classList.remove('sel-0','sel-1','sel-2','sel-3'); });
  btn.classList.add('sel-'+val);
  DEFECTOS_MAP[groupId] = val;
  actualizarFallas();
}

function actualizarFallas() {
  const nombres = {
    'def-diacetilo':    'Diacetilo',
    'def-acetaldehido': 'Acetaldehído',
    'def-dms':          'DMS',
    'def-oxidacion':    'Oxidación',
    'def-fenoles':      'Fenoles',
    'def-astringencia': 'Astringencia',
    'def-alcohol':      'Alcohol caliente',
    'def-hopburn':      'Hop burn',
  };
  const fallas = [];
  for (const [key, val] of Object.entries(DEFECTOS_MAP)) {
    if (val > 0) fallas.push(nombres[key] + ':' + val);
  }
  document.getElementById('h_fallas').value = fallas.join(',');
}

// Inicializar defectos en 0
document.querySelectorAll('.def-escala').forEach(g => {
  const firstBtn = g.querySelector('.def-btn');
  if (firstBtn) firstBtn.classList.add('sel-0');
  DEFECTOS_MAP[g.id] = 0;
});

// ── Puntaje global ────────────────────────────────────────
function selPuntaje(val, btn) {
  document.querySelectorAll('#puntaje-global .puntaje-btn').forEach(b => b.classList.remove('sel'));
  btn.classList.add('sel');
  document.getElementById('h_puntaje_global').value = val;
}

// ── Impresión (combina perfil + desvío) ──────────────────
function actualizarImpresion() {
  // Leer pill seleccionada directamente, no del hidden
  const perfilPill = document.querySelector('#pills-perfil .pill.sel');
  const perfil = perfilPill ? perfilPill.textContent.trim() : '';
  const desc   = document.getElementById('desvio_desc')?.value || '';
  const libre  = document.getElementById('impresion_libre')?.value || '';
  let val = '';
  if (perfil) val += 'Dentro de perfil: ' + perfil + '. ';
  if (desc)   val += desc + ' ';
  if (libre)  val += libre;
  document.getElementById('h_perfil').value = val.trim();
}

function loadContent(page) { window.location.href = page; }
</script>
</body>
</html>
