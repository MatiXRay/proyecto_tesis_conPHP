<?php
/**
 * BIALYSTOK BREWING CO — Detalle de planilla de cata
 * Reemplaza: detalle_planilla_cata.php
 *
 * Correcciones:
 *  - Sin verificación de sesión → auth.php
 *  - $id_lote = $_GET['id'] sin validar → getIntParam()
 *  - SQL injection en ambas queries → prepared statements PDO
 *  - XSS: todos los echo usan e()
 *  - Diseño: nuevo sistema CSS
 */

require_once 'auth.php';
requireLogin();

require_once 'conexion.php';

$menu_activo = 'panel_sensorial';

$id_lote = getIntParam('id');
if ($id_lote === null) {
    header('Location: panel_sensorial?error=id_invalido');
    exit;
}

// Escalas de descripción
function escala(int $val): string {
    return ['Nulo','Baja','Moderada','Media','Alta','Muy alta'][$val] ?? '—';
}

function fmtAtributos(?string $str): string {
    if (!$str) return '—';
    return implode(', ', array_map('ucfirst', array_filter(array_map('trim', explode(',', $str)))));
}

try {
    $pdo = getPDO();

    // Info del lote
    $stmt = $pdo->prepare(
        "SELECT lc.numero_lote, lc.fecha_elaboracion, ec.nombre AS estilo
         FROM lotes_cerveza lc
         LEFT JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
         WHERE lc.id = ?"
    );
    $stmt->execute([$id_lote]);
    $lote = $stmt->fetch();

    if (!$lote) {
        header('Location: panel_sensorial?error=lote_no_encontrado');
        exit;
    }

    // Todas las notas de cata del lote
    $stmt = $pdo->prepare(
        "SELECT nc.*, usr.username
         FROM notas_cata nc
         LEFT JOIN users usr ON nc.id_usuario = usr.id
         WHERE nc.id_lote = ?
         ORDER BY nc.fecha_creacion DESC"
    );
    $stmt->execute([$id_lote]);
    $notas = $stmt->fetchAll();

} catch (PDOException $ex) {
    error_log('[BRAUMEISTER detalle_planilla_cata] ' . $ex->getMessage());
    header('Location: panel_sensorial?error=error_db');
    exit;
}

// Calcular promedios si hay notas
$promedios = null;
if ($notas) {
    $campos = ['aroma_puntaje','apariencia_puntaje','sabor_puntaje','mouthfeel_puntaje','impresion_puntaje'];
    foreach ($campos as $c) {
        $promedios[$c] = round(array_sum(array_column($notas, $c)) / count($notas), 1);
    }
    $promedios['total'] = round(array_sum($promedios), 1);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cata · <?= e(strtoupper($lote['numero_lote'] ?? '')) ?> · BRAUMEISTER</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/bialy-design-system.css">
  <style>
    .score-bar-wrap { display:flex; align-items:center; gap:.75rem; margin-bottom:.6rem; }
    .score-bar-label { font-size:.8rem; color:var(--text-secondary); width:120px; flex-shrink:0; }
    .score-bar-track { flex:1; height:6px; background:var(--color-surface-3); border-radius:99px; overflow:hidden; }
    .score-bar-fill  { height:100%; background:var(--amber-400); border-radius:99px; transition:width .4s ease; }
    .score-bar-val   { font-family:'DM Mono',monospace; font-size:.82rem; color:var(--text-amber); width:32px; text-align:right; flex-shrink:0; }
    .nota-card { background:var(--color-surface-2); border:1px solid var(--color-border); border-radius:var(--radius-md); padding:1.1rem 1.25rem; margin-bottom:1rem; }
    .nota-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:.75rem; }
    .nota-seccion { margin-bottom:.75rem; }
    .nota-seccion-titulo { font-size:.7rem; font-weight:600; letter-spacing:.1em; text-transform:uppercase; color:var(--text-muted); margin-bottom:.35rem; }
    .nota-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:.4rem; }
    .nota-item { background:var(--color-surface-3); border-radius:6px; padding:.4rem .6rem; }
    .nota-item-label { font-size:.68rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:.05em; }
    .nota-item-val { font-size:.82rem; color:var(--text-primary); margin-top:1px; }

    /* Sin sidebar */
    .cata-wrap    { min-height:100vh; background:var(--color-bg); }
    .cata-header  { display:flex; align-items:flex-start; justify-content:space-between; padding:1.25rem 2rem; background:var(--color-surface); border-bottom:1px solid var(--color-border); gap:1rem; }
    .cata-brand   { font-size:.65rem; text-transform:uppercase; letter-spacing:.1em; color:var(--text-muted); margin-bottom:.2rem; }
    .cata-title   { font-size:1.3rem; font-weight:600; color:var(--text-primary); margin:0 0 .3rem; }
    .cata-meta    { display:flex; gap:.75rem; flex-wrap:wrap; }
    .cata-meta span { font-family:'DM Mono',monospace; font-size:.78rem; color:var(--text-muted); background:var(--color-surface-2); padding:.15rem .5rem; border-radius:4px; }
    .cata-content { max-width:1100px; margin:0 auto; padding:1.5rem 2rem 3rem; }
  </style>
</head>
<body>

<div class="cata-wrap">
  <div class="cata-header">
    <div>
      <div class="cata-brand">BRAUMEISTER · Análisis Sensorial</div>
      <h1 class="cata-title"><?= e(strtoupper($lote['numero_lote'] ?? '')) ?> — <?= e($lote['estilo'] ?? '—') ?></h1>
      <div class="cata-meta">
        <span><?= e(date('d/m/Y', strtotime($lote['fecha_elaboracion']))) ?></span>
        <span><?= count($notas) ?> cata<?= count($notas) !== 1 ? 's' : '' ?></span>
      </div>
    </div>
    <div style="display:flex;gap:.5rem;align-items:center">
      <button onclick="window.print()" class="btn-print" title="Imprimir">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Imprimir
      </button>
      <a href="panel_sensorial" class="btn btn-ghost btn-sm">← Panel sensorial</a>
    </div>
  </div>
  <div class="cata-content">

  <?php if ($promedios): ?>
  <!-- Promedios -->
  <div class="card fade-in" style="margin-bottom:1.5rem">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
      <div class="card-title" style="margin:0">Perfil sensorial promedio</div>
      <div style="font-family:'DM Mono',monospace;font-size:1.6rem;font-weight:600;color:var(--text-amber)">
        <?= $promedios['impresion_puntaje'] ?><span style="font-size:.9rem;color:var(--text-muted)">/10</span>
      </div>
    </div>

    <?php
    $dims = [
      'aroma_puntaje'      => 'Aroma',
      'apariencia_puntaje' => 'Apariencia',
      'sabor_puntaje'      => 'Sabor',
      'mouthfeel_puntaje'  => 'Mouthfeel',
      'impresion_puntaje'  => 'Impresión general',
    ];
    foreach ($dims as $key => $label):
      $pct = min(100, ($promedios[$key] / 10) * 100);
    ?>
    <div class="score-bar-wrap">
      <span class="score-bar-label"><?= $label ?></span>
      <div class="score-bar-track">
        <div class="score-bar-fill" style="width:<?= $pct ?>%"></div>
      </div>
      <span class="score-bar-val"><?= $promedios[$key] ?></span>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Notas individuales -->
  <?php if ($notas): ?>
  <h3 style="color:var(--text-secondary);font-size:.85rem;margin-bottom:1rem" class="fade-in">
    Evaluaciones individuales
  </h3>

  <?php foreach ($notas as $nota): ?>
  <div class="nota-card fade-in">
    <div class="nota-header">
      <div>
        <span style="font-weight:500"><?= e($nota['username'] ?? 'Anónimo') ?></span>
        <span style="font-size:.78rem;color:var(--text-muted);margin-left:.75rem">
          <?= e(date('d/m/Y H:i', strtotime($nota['fecha_creacion']))) ?>
        </span>
        <?php if ($nota['origen_muestra']): ?>
        <span class="badge badge-muted" style="margin-left:.5rem"><?= e($nota['origen_muestra']) ?></span>
        <?php endif; ?>
        <?php if ($nota['tiempo_transcurrido']): ?>
        <span style="font-size:.75rem;color:var(--text-muted);margin-left:.5rem">
          ⏱ <?= e($nota['tiempo_transcurrido']) ?>
        </span>
        <?php endif; ?>
      </div>
      <div style="font-family:'DM Mono',monospace;font-size:1.4rem;font-weight:700;color:var(--text-amber)">
        <?= (int)$nota['impresion_puntaje'] ?>
        <span style="font-size:.75rem;color:var(--text-muted)">/10</span>
      </div>
    </div>

    <!-- Aroma -->
    <div class="nota-seccion">
      <div class="nota-seccion-titulo">Aroma · Puntaje: <?= (int)$nota['aroma_puntaje'] ?>/10</div>
      <div class="nota-grid">
        <div class="nota-item"><div class="nota-item-label">Malta</div><div class="nota-item-val"><?= escala((int)$nota['malta_intensidad']) ?></div></div>
        <div class="nota-item"><div class="nota-item-label">Lúpulo</div><div class="nota-item-val"><?= escala((int)$nota['lupulo_intensidad']) ?></div></div>
        <div class="nota-item"><div class="nota-item-label">Ésteres</div><div class="nota-item-val"><?= escala((int)$nota['esteres_intensidad']) ?></div></div>
        <div class="nota-item"><div class="nota-item-label">Fenoles</div><div class="nota-item-val"><?= escala((int)$nota['fenoles_intensidad']) ?></div></div>
        <div class="nota-item"><div class="nota-item-label">Alcohol</div><div class="nota-item-val"><?= escala((int)$nota['alcohol_intensidad']) ?></div></div>
        <div class="nota-item"><div class="nota-item-label">Dulzor</div><div class="nota-item-val"><?= escala((int)$nota['dulzor_intensidad']) ?></div></div>
        <div class="nota-item"><div class="nota-item-label">Acidez</div><div class="nota-item-val"><?= escala((int)$nota['acidez_intensidad']) ?></div></div>
        <div class="nota-item"><div class="nota-item-label">Otros</div><div class="nota-item-val"><?= escala((int)$nota['otros_intensidad']) ?></div></div>
      </div>
      <?php if ($nota['aroma_comentario']): ?>
      <p style="font-size:.82rem;color:var(--text-secondary);margin-top:.5rem;font-style:italic">
        "<?= e($nota['aroma_comentario']) ?>"
      </p>
      <?php endif; ?>
    </div>

    <!-- Apariencia -->
    <div class="nota-seccion">
      <div class="nota-seccion-titulo">Apariencia · Puntaje: <?= (int)$nota['apariencia_puntaje'] ?>/10</div>
      <div class="nota-grid">
        <div class="nota-item"><div class="nota-item-label">Claridad</div><div class="nota-item-val"><?= escala((int)$nota['claridad_intensidad']) ?></div></div>
        <div class="nota-item"><div class="nota-item-label">Retención espuma</div><div class="nota-item-val"><?= escala((int)$nota['retencion_intensidad']) ?></div></div>
        <div class="nota-item"><div class="nota-item-label">Color cerveza</div><div class="nota-item-val"><?= e($nota['color_cerveza'] ?: '—') ?></div></div>
        <div class="nota-item"><div class="nota-item-label">Color espuma</div><div class="nota-item-val"><?= e($nota['color_espuma'] ?: '—') ?></div></div>
      </div>
      <?php if ($nota['apariencia_comentario']): ?>
      <p style="font-size:.82rem;color:var(--text-secondary);margin-top:.5rem;font-style:italic">
        "<?= e($nota['apariencia_comentario']) ?>"
      </p>
      <?php endif; ?>
    </div>

    <!-- Sabor -->
    <div class="nota-seccion">
      <div class="nota-seccion-titulo">Sabor · Puntaje: <?= (int)$nota['sabor_puntaje'] ?>/10</div>
      <div class="nota-grid">
        <div class="nota-item"><div class="nota-item-label">Malta</div><div class="nota-item-val"><?= escala((int)$nota['sabor_malta_intensidad']) ?></div></div>
        <div class="nota-item"><div class="nota-item-label">Lúpulo / Amargor</div><div class="nota-item-val"><?= escala((int)$nota['sabor_lupulo_intensidad']) ?></div></div>
        <div class="nota-item"><div class="nota-item-label">Dulzor</div><div class="nota-item-val"><?= escala((int)$nota['sabor_dulzor_intensidad']) ?></div></div>
        <div class="nota-item"><div class="nota-item-label">Acidez</div><div class="nota-item-val"><?= escala((int)$nota['sabor_acidez_intensidad']) ?></div></div>
        <?php if ($nota['balance']): ?>
        <div class="nota-item"><div class="nota-item-label">Balance</div><div class="nota-item-val"><?= e($nota['balance']) ?></div></div>
        <?php endif; ?>
        <?php if ($nota['mouthfeel_final']): ?>
        <div class="nota-item"><div class="nota-item-label">Final</div><div class="nota-item-val"><?= e($nota['mouthfeel_final']) ?></div></div>
        <?php endif; ?>
      </div>
      <?php if ($nota['sabor_comentario']): ?>
      <p style="font-size:.82rem;color:var(--text-secondary);margin-top:.5rem;font-style:italic">
        "<?= e($nota['sabor_comentario']) ?>"
      </p>
      <?php endif; ?>
    </div>

    <!-- Mouthfeel -->
    <div class="nota-seccion">
      <div class="nota-seccion-titulo">Mouthfeel · Puntaje: <?= (int)$nota['mouthfeel_puntaje'] ?>/10</div>
      <div class="nota-grid">
        <div class="nota-item"><div class="nota-item-label">Cuerpo</div><div class="nota-item-val"><?= escala((int)$nota['cuerpo_intensidad']) ?></div></div>
        <div class="nota-item"><div class="nota-item-label">Carbonatación</div><div class="nota-item-val"><?= escala((int)$nota['carbonatacion_intensidad']) ?></div></div>
        <div class="nota-item"><div class="nota-item-label">Cremosidad</div><div class="nota-item-val"><?= escala((int)$nota['cremosidad_intensidad']) ?></div></div>
        <div class="nota-item"><div class="nota-item-label">Calentamiento</div><div class="nota-item-val"><?= escala((int)$nota['calentamiento_intensidad']) ?></div></div>
        <div class="nota-item"><div class="nota-item-label">Astringencia</div><div class="nota-item-val"><?= escala((int)$nota['astringencia_intensidad']) ?></div></div>
      </div>
      <?php if ($nota['mouthfeel_comentario']): ?>
      <p style="font-size:.82rem;color:var(--text-secondary);margin-top:.5rem;font-style:italic">
        "<?= e($nota['mouthfeel_comentario']) ?>"
      </p>
      <?php endif; ?>
    </div>

    <!-- Impresión general -->
    <div class="nota-seccion">
      <div class="nota-seccion-titulo">Impresión general · Puntaje global: <?= (int)$nota['impresion_puntaje'] ?>/10</div>
      <?php if ($nota['impresion_comentario']):
        // Separar causa y acción si están concatenadas
        $imp_text = $nota['impresion_comentario'];
        $causa = ''; $accion = '';
        if (preg_match('/\nCausa: (.+?)(?:\n|$)/s', $imp_text, $mc)) { $causa = trim($mc[1]); }
        if (preg_match('/\nAcci[oó]n: (.+?)(?:\n|$)/s', $imp_text, $ma)) { $accion = trim($ma[1]); }
        // Limpiar el texto puro: sacar prefijo "Dentro de perfil: X." y sacar causa/acción
        $comentario_puro = trim(preg_replace('/\n(Causa|Acci[oó]n):.+/s', '', $imp_text));
        $comentario_puro = trim(preg_replace('/^Dentro de perfil:[^.]*\.\s*/i', '', $comentario_puro));
      ?>
      <?php if ($comentario_puro): ?>
      <p style="font-size:.85rem;color:var(--text-secondary);font-style:italic;margin-bottom:.5rem">
        "<?= e($comentario_puro) ?>"
      </p>
      <?php endif; ?>
      <?php if ($causa || $accion): ?>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-top:.5rem">
        <?php if ($causa): ?>
        <div class="nota-item">
          <div class="nota-item-label">🔍 Posible causa</div>
          <div class="nota-item-val" style="white-space:pre-wrap"><?= e($causa) ?></div>
        </div>
        <?php endif; ?>
        <?php if ($accion): ?>
        <div class="nota-item">
          <div class="nota-item-label">🛠 Acción próximo lote</div>
          <div class="nota-item-val" style="white-space:pre-wrap"><?= e($accion) ?></div>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
      <?php endif; ?>
      <?php if ($nota['fallas']): ?>
      <div style="margin-top:.6rem">
        <div style="font-size:.72rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--color-danger);margin-bottom:.35rem">⚠ Defectos detectados</div>
        <div style="display:flex;flex-wrap:wrap;gap:.35rem">
        <?php
          foreach (explode(',', $nota['fallas']) as $falla) {
            $falla = trim($falla);
            if (!$falla) continue;
            preg_match('/^(.*?):(\d)$/', $falla, $m);
            $nombre = $m[1] ?? $falla;
            $sev    = (int)($m[2] ?? 1);
            $colores = [1=>'rgba(74,170,74,.2);color:#4aaa4a', 2=>'rgba(200,146,42,.25);color:#c8922a', 3=>'rgba(217,96,96,.3);color:#d96060'];
            $color   = isset($colores[$sev]) ? $colores[$sev] : 'rgba(100,100,100,.2);color:#888';
            $labels  = [1=>'Leve', 2=>'Moderado', 3=>'Marcado'];
            $label   = isset($labels[$sev]) ? $labels[$sev] : '?';
            echo "<span style=\"font-size:.72rem;padding:.2rem .55rem;border-radius:99px;background:$color;font-weight:500\">$nombre ($label)</span>";
          }
        ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Acciones -->
    <?php if (isAdmin() || (int)$nota['id_usuario'] === (int)$_SESSION['id']): ?>
    <div style="margin-top:.5rem;padding-top:.75rem;border-top:1px solid var(--color-border)">
      <button class="btn btn-danger btn-sm"
              onclick="eliminarNota(<?= (int)$nota['id'] ?>, this)">
        Eliminar nota
      </button>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>

  <?php else: ?>
  <div class="card fade-in" style="text-align:center;padding:2rem">
    <p style="color:var(--text-muted)">No hay notas de cata registradas para este lote todavía.</p>
  </div>
  <?php endif; ?>

</div><!-- /cata-content -->
</div><!-- /cata-wrap -->

<script>
function eliminarNota(id, btn) {
  if (!confirm('¿Eliminar esta nota de cata?')) return;

  fetch('eliminar_nota_cata', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      nota_id:    id,
      csrf_token: '<?= e(getCsrfToken()) ?>'
    })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      btn.closest('.nota-card').remove();
    } else {
      alert('Error: ' + (data.message || 'No se pudo eliminar.'));
    }
  })
  .catch(() => alert('Error de red.'));
}

function loadContent(page) { window.location.href = page; }
</script>

</body>
</html>
