<?php
/**
 * BIALYSTOK BREWING CO — Panel de Cata (vista taster)
 * Reemplaza: panel_cata.php
 *
 * Correcciones:
 *  - Sin verificación de sesión → auth.php
 *  - SQL injection: $id_usuario y $inicio_* interpolados → prepared statements
 *  - XSS: todos los echo usan e()
 *  - Diseño: nuevo sistema CSS (página independiente sin sidebar para tasters)
 */

require_once 'auth.php';
requireLogin();

require_once 'conexion.php';

$id_usuario = (int) $_SESSION['id'];

$pag_lotes       = max(1, (int)($_GET['pagina_lotes'] ?? 1));
$pag_devoluciones = max(1, (int)($_GET['pagina_devoluciones'] ?? 1));
$por_pagina      = 10;
$offset_lotes    = ($pag_lotes - 1) * $por_pagina;
$offset_dev      = ($pag_devoluciones - 1) * $por_pagina;

try {
    $pdo = getPDO();

    // Todos los lotes disponibles para catar
    $stmt = $pdo->prepare(
        "SELECT lc.id, lc.numero_lote, lc.fecha_elaboracion, ec.nombre AS nombre_estilo
         FROM lotes_cerveza lc
         INNER JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
         ORDER BY lc.fecha_elaboracion DESC
         LIMIT $offset_lotes, $por_pagina"
    );
    $stmt->execute();
    $lotes = $stmt->fetchAll();

    $total_lotes   = (int) $pdo->query("SELECT COUNT(*) FROM lotes_cerveza")->fetchColumn();
    $total_pag_lotes = max(1, (int) ceil($total_lotes / $por_pagina));

    // Mis notas de cata
    $stmt = $pdo->prepare(
        "SELECT nc.fecha_creacion, nc.tiempo_transcurrido, nc.origen_muestra,
                lc.numero_lote, ec.nombre AS nombre_estilo,
                nc.impresion_puntaje AS total_puntaje
         FROM notas_cata nc
         INNER JOIN lotes_cerveza lc ON nc.id_lote = lc.id
         INNER JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
         WHERE nc.id_usuario = ?
         ORDER BY nc.fecha_creacion DESC
         LIMIT $offset_dev, $por_pagina"
    );
    $stmt->execute([$id_usuario]);
    $mis_notas = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notas_cata WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    $total_dev = (int) $stmt->fetchColumn();
    $total_pag_dev = max(1, (int) ceil($total_dev / $por_pagina));

    // Stats del usuario
    $stmt = $pdo->prepare(
        "SELECT ROUND(AVG(impresion_puntaje),1) AS avg_puntaje,
                COUNT(DISTINCT lc.estilo_id) AS estilos_distintos
         FROM notas_cata nc
         INNER JOIN lotes_cerveza lc ON nc.id_lote = lc.id
         WHERE nc.id_usuario = ?"
    );
    $stmt->execute([$id_usuario]);
    $stats_usuario = $stmt->fetch();

    $stmt = $pdo->prepare(
        "SELECT ec.nombre AS estilo,
                ROUND(AVG(nc.impresion_puntaje),1) AS avg_puntaje,
                COUNT(*) AS catas
         FROM notas_cata nc
         INNER JOIN lotes_cerveza lc ON nc.id_lote = lc.id
         INNER JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
         WHERE nc.id_usuario = ?
         GROUP BY ec.id, ec.nombre
         ORDER BY avg_puntaje DESC"
    );
    $stmt->execute([$id_usuario]);
    $stats_estilos = $stmt->fetchAll();

} catch (PDOException $ex) {
    error_log('[Bialystok panel_cata] ' . $ex->getMessage());
    $lotes = $mis_notas = $stats_estilos = [];
    $stats_usuario = null;
    $total_lotes = $total_dev = 0;
    $total_pag_lotes = $total_pag_dev = 1;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Cata · Bialystok Brewing</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/bialy-design-system.css">
  <style>
    /* Panel de cata sin sidebar — layout centrado */
    body { background: var(--color-bg); }
    .cata-wrap {
      max-width: 900px;
      margin: 0 auto;
      padding: 2rem 1.5rem;
    }
    .cata-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 2rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid var(--color-border);
    }
  </style>
</head>
<body>

<div class="cata-wrap">

  <div class="cata-header fade-in">
    <div>
      <div style="font-size:.78rem;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--text-muted);margin-bottom:.2rem">
        Bialystok Brewing Co
      </div>
      <h1 style="margin:0">Panel de Cata</h1>
    </div>
    <div style="display:flex;align-items:center;gap:1rem">
      <span style="font-size:.85rem;color:var(--text-secondary)">
        <?= e($_SESSION['username'] ?? '') ?>
      </span>
      <a href="logout" class="btn btn-ghost btn-sm"
         onclick="return confirm('¿Cerrar sesión?')">Salir</a>
    </div>
  </div>

  <!-- Lotes disponibles para catar -->
  <div class="card fade-in" style="margin-bottom:1.5rem">
    <div class="card-title">Lotes disponibles para evaluar</div>

    <?php if ($lotes): ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>N° Lote</th>
            <th>Fecha elaboración</th>
            <th>Estilo</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lotes as $lote): ?>
          <tr style="cursor:pointer"
              onclick="window.location.href='planilla_cata?id=<?= (int)$lote['id'] ?>'">
            <td style="font-family:'DM Mono',monospace;font-weight:500">
              <?= e(strtoupper($lote['numero_lote'] ?? '')) ?>
            </td>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem;color:var(--text-secondary)">
              <?= e(date('d/m/Y', strtotime($lote['fecha_elaboracion']))) ?>
            </td>
            <td><span class="badge badge-amber"><?= e(strtoupper($lote['nombre_estilo'])) ?></span></td>
            <td>
              <a href="planilla_cata?id=<?= (int)$lote['id'] ?>"
                 class="btn btn-secondary btn-sm"
                 onclick="event.stopPropagation()">
                Evaluar →
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($total_pag_lotes > 1): ?>
    <div class="pagination" style="margin-top:.75rem">
      <?php for ($i = 1; $i <= $total_pag_lotes; $i++): ?>
        <a href="?pagina_lotes=<?= $i ?>&pagina_devoluciones=<?= $pag_devoluciones ?>"
           <?= $i === $pag_lotes ? 'class="active"' : '' ?>><?= $i ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
      <p style="color:var(--text-muted);font-size:.85rem;padding:.5rem 0">
        No hay lotes habilitados para cata en este momento.
      </p>
    <?php endif; ?>
  </div>

  <!-- Stats del usuario -->
  <?php if ($total_dev > 0): ?>
  <div class="card fade-in" style="margin-bottom:1.5rem">
    <div class="card-title">Mis estadísticas</div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1rem">
      <div class="stat-card">
        <div class="stat-value"><?= $total_dev ?></div>
        <div class="stat-label">Evaluaciones realizadas</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= $stats_usuario['avg_puntaje'] ?? '—' ?>/10</div>
        <div class="stat-label">Puntaje promedio</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?= $stats_usuario['estilos_distintos'] ?? '—' ?></div>
        <div class="stat-label">Estilos evaluados</div>
      </div>
    </div>
    <?php if (!empty($stats_estilos)): ?>
    <div style="font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:var(--text-muted);margin-bottom:.5rem">Puntaje promedio por estilo</div>
    <?php foreach ($stats_estilos as $se):
      $pct = ($se['avg_puntaje'] / 10) * 100;
    ?>
    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.4rem">
      <span style="font-size:.78rem;width:140px;flex-shrink:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($se['estilo']) ?></span>
      <div style="flex:1;height:6px;background:var(--color-surface-3);border-radius:99px;overflow:hidden">
        <div style="width:<?= $pct ?>%;height:100%;background:var(--amber-400);border-radius:99px"></div>
      </div>
      <span style="font-family:'DM Mono',monospace;font-size:.78rem;color:var(--text-amber);width:40px;text-align:right"><?= number_format($se['avg_puntaje'],1) ?></span>
      <span style="font-size:.68rem;color:var(--text-muted)">(<?= $se['catas'] ?> cata<?= $se['catas']!=1?'s':'' ?>)</span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Mis notas de cata -->
  <div class="card fade-in">
    <div class="card-title">Mis evaluaciones anteriores (<?= $total_dev ?>)</div>

    <?php if ($mis_notas): ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Fecha</th>
            <th>N° Lote</th>
            <th>Estilo</th>
            <th>Origen</th>
            <th>Tiempo</th>
            <th>Puntaje</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($mis_notas as $nota): ?>
          <tr>
            <td style="font-family:'DM Mono',monospace;font-size:.82rem;color:var(--text-secondary)">
              <?= e(date('d/m/Y', strtotime($nota['fecha_creacion']))) ?>
            </td>
            <td style="font-family:'DM Mono',monospace;font-weight:500">
              <?= e(strtoupper($nota['numero_lote'] ?? '')) ?>
            </td>
            <td><span class="badge badge-muted"><?= e(strtoupper($nota['nombre_estilo'])) ?></span></td>
            <td style="font-size:.82rem;color:var(--text-secondary)"><?= e($nota['origen_muestra'] ?? '—') ?></td>
            <td style="font-size:.82rem;color:var(--text-secondary)"><?= e($nota['tiempo_transcurrido'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace;color:var(--text-amber);font-weight:600">
              <?= $nota['total_puntaje'] ? e((string)$nota['total_puntaje']).'/10' : '—' ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($total_pag_dev > 1): ?>
    <div class="pagination" style="margin-top:.75rem">
      <?php for ($i = 1; $i <= $total_pag_dev; $i++): ?>
        <a href="?pagina_lotes=<?= $pag_lotes ?>&pagina_devoluciones=<?= $i ?>"
           <?= $i === $pag_devoluciones ? 'class="active"' : '' ?>><?= $i ?></a>
      <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
      <p style="color:var(--text-muted);font-size:.85rem;padding:.5rem 0">
        Todavía no registraste ninguna evaluación.
      </p>
    <?php endif; ?>
  </div>

</div>

<script>
function loadContent(page) { window.location.href = page; }
</script>
</body>
</html>
