<?php
/**
 * BIALYSTOK BREWING CO — Panel General (Inicio)
 * Reemplaza: inicio.php
 *
 * Correcciones aplicadas:
 *  - Auth via auth.php (session fixation, timeout, roles)
 *  - SQL injection: $searchTerm directo en query → prepared statement PDO
 *  - $orden validado en whitelist (ASC/DESC), no interpolado de $_GET
 *  - XSS: todos los echo de datos DB usan e()
 *  - Función esFechaMasDeUnMes() movida fuera del loop, simplificada
 *  - Conexión única via getPDO() — no se abre/cierra múltiples veces
 *  - Diseño: nuevo sistema CSS (bialy-design-system.css)
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }

require_once 'conexion.php';

$menu_activo = 'inicio';

// ── Helper: requiere limpieza (> 30 días sin limpiar) ─────────────────────────
function requiereLimpieza(?string $fecha): bool {
    if (!$fecha || $fecha === '0000-00-00') return true;
    try {
        $d = new DateTime($fecha);
        return (new DateTime())->diff($d)->days > 30;
    } catch (Exception $e) {
        return true;
    }
}

// ── Queries ───────────────────────────────────────────────────────────────────
try {
    $pdo = getPDO();

    // Alerta de agua: último reporte vs hace 2 meses
    $stmt = $pdo->prepare("SELECT fecha FROM reportesagua WHERE fecha <= CURDATE() ORDER BY fecha DESC LIMIT 1");
    $stmt->execute();
    $ultimoReporteAgua = $stmt->fetchColumn();
    $alertaAgua = false;
    if ($ultimoReporteAgua) {
        $diff = (new DateTime())->diff(new DateTime($ultimoReporteAgua));
        $alertaAgua = ($diff->y > 0 || $diff->m >= 2);
    } else {
        $alertaAgua = true;
    }

    // Fermentadores
    $fermentadores = $pdo->query(
        "SELECT id, nombre, limp_alcalina_date, limp_acida_date, limp_oxidativa_date, limp_exterior_date
         FROM fermentadores ORDER BY nombre"
    )->fetchAll();

    // Últimos 10 lotes
    $orden = (isset($_GET['orden']) && $_GET['orden'] === 'asc') ? 'ASC' : 'DESC';
    $stmt  = $pdo->prepare(
        "SELECT lc.id, lc.fecha_elaboracion, lc.comentarios, ec.nombre AS estilo
         FROM lotes_cerveza lc
         INNER JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
         ORDER BY lc.fecha_elaboracion $orden
         LIMIT 10"
    );
    $stmt->execute();
    $ultimosLotes = $stmt->fetchAll();

    // Alertas configurables
    $alertas_activas = [];
    try {
        $rows_alertas = $pdo->query("SELECT * FROM alertas WHERE activa=1 ORDER BY id")->fetchAll();
        foreach ($rows_alertas as $al) {
            $per = (int)$al['periodicidad_dias'];
            $ultima = $al['ultima_vez'];
            $estado = 'sin-datos';
            $dias_restantes = null;
            if ($ultima && $ultima !== '0000-00-00') {
                $dt_proximo = (new DateTime($ultima))->modify("+{$per} days");
                $dias_restantes = (int)(new DateTime())->diff($dt_proximo)->format('%r%a');
                if ($dias_restantes < 0)       $estado = 'vencida';
                elseif ($dias_restantes <= 30) $estado = 'proxima';
                else                           $estado = 'ok';
            }
            if ($estado !== 'ok') {
                $alertas_activas[] = [
                    'id'          => $al['id'],
                    'descripcion' => $al['descripcion'],
                    'estado'      => $estado,
                    'dias'        => $dias_restantes,
                ];
            }
        }
    } catch (Exception $e) { /* tabla puede no existir aún */ }

    // KPIs de producción actual
    $lotes_activos = $pdo->query(
        "SELECT COUNT(*) FROM lotes_cerveza
         WHERE dia_envasado IS NULL AND fecha_elaboracion >= DATE_SUB(NOW(), INTERVAL 90 DAY)"
    )->fetchColumn();

    $litros_en_proceso = $pdo->query(
        "SELECT COALESCE(SUM(litros_a_fermentador), 0) FROM lotes_cerveza
         WHERE dia_envasado IS NULL AND fecha_elaboracion >= DATE_SUB(NOW(), INTERVAL 90 DAY)"
    )->fetchColumn();

    $litros_mes = $pdo->query(
        "SELECT COALESCE(SUM(litros_envasados), 0) FROM lotes_cerveza
         WHERE MONTH(dia_envasado) = MONTH(NOW()) AND YEAR(dia_envasado) = YEAR(NOW())"
    )->fetchColumn();

    $catas_pendientes = $pdo->query(
        "SELECT COUNT(*) FROM lotes_cerveza
         WHERE cata_habilitada = 1
         AND id NOT IN (SELECT DISTINCT id_lote FROM notas_cata WHERE id_lote IS NOT NULL)"
    )->fetchColumn();

    // Detalle de lotes en fermentación activa
    $stmt_activos = $pdo->prepare(
        "SELECT lc.id, lc.numero_lote, lc.fecha_elaboracion, lc.litros_a_fermentador,
                ec.nombre AS estilo, ec.duracion_dias,
                f.nombre AS fermentador,
                DATEDIFF(NOW(), lc.fecha_elaboracion) AS dias_transcurridos
         FROM lotes_cerveza lc
         LEFT JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
         LEFT JOIN fermentadores f ON lc.fermentador_id = f.id
         WHERE lc.dia_envasado IS NULL AND lc.fecha_elaboracion >= DATE_SUB(NOW(), INTERVAL 90 DAY)
         ORDER BY lc.fecha_elaboracion DESC"
    );
    $stmt_activos->execute();
    $lotes_en_ferm = $stmt_activos->fetchAll();

    // Stats generales
    $totalLotes    = $pdo->query("SELECT COUNT(*) FROM lotes_cerveza")->fetchColumn();
    $totalRecetas  = $pdo->query("SELECT COUNT(*) FROM estilos_cerveza")->fetchColumn();
    $totalFermen   = count($fermentadores);
    $totalCatas    = $pdo->query("SELECT COUNT(*) FROM notas_cata")->fetchColumn();

} catch (PDOException $ex) {
    error_log('[BRAUMEISTER inicio] ' . $ex->getMessage());
    $fermentadores = $ultimosLotes = $lotes_en_ferm = [];
    $alertaAgua = false;
    $alertas_activas = [];
    $totalLotes = $totalRecetas = $totalFermen = $totalCatas = '—';
    $lotes_activos = $litros_en_proceso = $litros_mes = $catas_pendientes = 0;
}

$nuevo_orden = ($orden === 'ASC') ? 'desc' : 'asc';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inicio · BRAUMEISTER</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/bialy-design-system.css">
</head>
<body>

<?php require 'menu.php'; ?>
<?php require 'info_user.php'; ?>

<div id="contenido" class="main-content">

  <!-- ── Page header ──────────────────────────────────────────────────────── -->
  <div class="page-header fade-in">
    <div>
      <h1>Panel General</h1>
      <p class="page-subtitle">Resumen de producción · BRAUMEISTER</p>
    </div>
  </div>

  <!-- ── Alerta agua ───────────────────────────────────────────────────────── -->
  <?php if ($alertaAgua): ?>
  <div class="alert alert-warning fade-in">
    <span class="alert-icon">⚠</span>
    <div>Han pasado más de 2 meses desde el último reporte de agua.
      <a href="anadir_reporte_h2o" style="margin-left:.5rem">Cargar reporte →</a>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Alertas de mantenimiento ─────────────────────────────────────────────── -->
  <?php if (!empty($alertas_activas)): ?>
  <div class="fade-in" style="display:flex;flex-direction:column;gap:.5rem;margin-bottom:1rem">
    <?php foreach ($alertas_activas as $al): ?>
    <div class="alert <?= $al['estado']==='vencida' ? 'alert-danger' : 'alert-warning' ?>"
         style="display:flex;align-items:center;justify-content:space-between">
      <div style="display:flex;align-items:center;gap:.75rem">
        <span class="alert-icon"><?= $al['estado']==='vencida' ? '🔴' : '🟡' ?></span>
        <div>
          <strong><?= e($al['descripcion']) ?></strong>
          <?php if ($al['dias'] !== null): ?>
          <span style="font-size:.78rem;color:var(--text-muted);margin-left:.5rem">
            <?= $al['dias'] < 0 ? 'Venció hace '.abs($al['dias']).' días' : 'Vence en '.$al['dias'].' días' ?>
          </span>
          <?php else: ?>
          <span style="font-size:.78rem;color:var(--text-muted);margin-left:.5rem">Sin fecha registrada</span>
          <?php endif; ?>
        </div>
      </div>
      <a href="configuracion" style="font-size:.78rem;color:var(--text-amber)">Registrar →</a>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ── KPIs de producción actual ────────────────────────────────────────── -->
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1rem" class="fade-in">
    <div class="stat-card" style="border-top:3px solid var(--color-success)">
      <div class="stat-value" style="color:var(--color-success)"><?= (int)$lotes_activos ?></div>
      <div class="stat-label">Lotes en fermentación</div>
    </div>
    <div class="stat-card" style="border-top:3px solid var(--text-amber)">
      <div class="stat-value" style="color:var(--text-amber)"><?= number_format((float)$litros_en_proceso, 0) ?> L</div>
      <div class="stat-label">Litros en proceso</div>
    </div>
    <div class="stat-card" style="border-top:3px solid #6c9bd2">
      <div class="stat-value" style="color:#6c9bd2"><?= number_format((float)$litros_mes, 0) ?> L</div>
      <div class="stat-label">Envasados este mes</div>
    </div>
    <div class="stat-card" style="border-top:3px solid <?= (int)$catas_pendientes > 0 ? 'var(--color-danger)' : 'var(--text-muted)' ?>">
      <div class="stat-value" style="color:<?= (int)$catas_pendientes > 0 ? 'var(--color-danger)' : 'var(--text-muted)' ?>"><?= (int)$catas_pendientes ?></div>
      <div class="stat-label">Catas pendientes</div>
    </div>
  </div>

  <!-- ── Lotes en fermentación activa ──────────────────────────────────────── -->
  <?php if (!empty($lotes_en_ferm)): ?>
  <div class="card fade-in" style="margin-bottom:1rem">
    <div class="card-title" style="margin-bottom:1rem">Producción activa</div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:.75rem">
      <?php foreach ($lotes_en_ferm as $lf):
        $dias = (int)$lf['dias_transcurridos'];
        $total = max(1, (int)($lf['duracion_dias'] ?? 14));
        $pct = min(100, round($dias / $total * 100));
        $color_barra = $pct >= 100 ? 'var(--color-danger)' : ($pct >= 75 ? 'var(--text-amber)' : 'var(--color-success)');
      ?>
      <div style="background:var(--color-bg);border:1px solid var(--color-border);border-radius:var(--radius);padding:.9rem 1rem">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.5rem">
          <div>
            <div style="font-weight:600;font-size:.9rem"><?= e($lf['numero_lote']) ?></div>
            <div style="font-size:.75rem;color:var(--text-muted)"><?= e($lf['estilo'] ?? '—') ?></div>
          </div>
          <span class="badge" style="background:rgba(74,170,74,.15);color:var(--color-success);font-size:.7rem">
            Fermentando
          </span>
        </div>
        <div style="font-size:.78rem;color:var(--text-muted);margin-bottom:.6rem">
          <?= e($lf['fermentador'] ?? 'Sin fermentador') ?>
          &nbsp;·&nbsp;
          <?= $lf['litros_a_fermentador'] ? number_format((float)$lf['litros_a_fermentador'], 0).' L' : '—' ?>
          &nbsp;·&nbsp;
          Día <?= $dias ?>/<?= $total ?>
        </div>
        <div style="background:var(--color-border);border-radius:99px;height:6px;overflow:hidden">
          <div style="width:<?= $pct ?>%;height:100%;background:<?= $color_barra ?>;border-radius:99px;transition:width .3s"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:.7rem;color:var(--text-muted);margin-top:.3rem">
          <span><?= e(date('d/m/Y', strtotime($lf['fecha_elaboracion']))) ?></span>
          <span><?= $pct ?>%<?= $pct >= 100 ? ' · Listo para envasar' : '' ?></span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── Stats generales ───────────────────────────────────────────────────── -->
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem" class="fade-in">
    <div class="stat-card">
      <div class="stat-value"><?= e((string)$totalLotes) ?></div>
      <div class="stat-label">Lotes totales</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= e((string)$totalFermen) ?></div>
      <div class="stat-label">Fermentadores</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= e((string)$totalRecetas) ?></div>
      <div class="stat-label">Recetas / Estilos</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= e((string)$totalCatas) ?></div>
      <div class="stat-label">Catas registradas</div>
    </div>
  </div>

  <!-- ── Grid 2 columnas ───────────────────────────────────────────────────── -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;align-items:start">

    <!-- Fermentadores -->
    <div class="card fade-in">
      <div class="card-title">Estado de limpieza · Fermentadores</div>
      <p style="font-size:.75rem;color:var(--text-muted);margin-bottom:.75rem">
        <span style="color:var(--color-success);font-weight:600">✓</span> OK (menos de 30 días)
        &nbsp;·&nbsp;
        <span style="color:var(--color-danger);font-weight:600">!</span> Requiere limpieza
      </p>

      <?php if ($fermentadores): ?>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>FV</th>
              <th>Alcalina</th>
              <th>Ácida</th>
              <th>Oxidativa</th>
              <th>Exterior</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($fermentadores as $fv):
              $alc = requiereLimpieza($fv['limp_alcalina_date']);
              $aci = requiereLimpieza($fv['limp_acida_date']);
              $oxi = requiereLimpieza($fv['limp_oxidativa_date']);
              $ext = requiereLimpieza($fv['limp_exterior_date']);
            ?>
            <tr>
              <td style="font-weight:500"><?= e($fv['nombre']) ?></td>
              <td class="<?= $alc ? 'estado-alerta' : 'estado-ok' ?>"><?= $alc ? '!' : '✓' ?></td>
              <td class="<?= $aci ? 'estado-alerta' : 'estado-ok' ?>"><?= $aci ? '!' : '✓' ?></td>
              <td class="<?= $oxi ? 'estado-alerta' : 'estado-ok' ?>"><?= $oxi ? '!' : '✓' ?></td>
              <td class="<?= $ext ? 'estado-alerta' : 'estado-ok' ?>"><?= $ext ? '!' : '✓' ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <p style="color:var(--text-muted);font-size:.85rem">No hay fermentadores registrados.</p>
      <?php endif; ?>
    </div>

    <!-- Últimos lotes -->
    <div class="card fade-in">
      <div class="card-title">Últimos lotes</div>

      <div class="toolbar" style="margin-bottom:.75rem">
        <button class="btn btn-secondary btn-sm" id="btnVerDetalles">Ver detalles</button>
        <button class="btn btn-ghost btn-sm"     id="btnVerNotas">Notas de cata</button>
        <div style="margin-left:auto">
          <a href="?orden=<?= e($nuevo_orden) ?>" class="btn btn-ghost btn-sm">
            <?= $orden === 'DESC' ? '↑ Más antiguos' : '↓ Más recientes' ?>
          </a>
        </div>
      </div>

      <?php if ($ultimosLotes): ?>
      <div class="table-wrapper">
        <table id="tabla-lotes">
          <thead>
            <tr>
              <th style="width:30px"></th>
              <th>Fecha</th>
              <th>Estilo</th>
              <th>Comentarios</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ultimosLotes as $i => $lote): ?>
            <tr style="cursor:pointer" onclick="seleccionarFila(this)">
              <td>
                <input type="radio" name="lote_sel" value="<?= (int)$lote['id'] ?>"
                       style="accent-color:var(--amber-400)"
                       <?= $i === 0 ? 'checked' : '' ?>>
              </td>
              <td style="font-family:'DM Mono',monospace;font-size:.82rem;white-space:nowrap">
                <?= e(date('d/m/Y', strtotime($lote['fecha_elaboracion']))) ?>
              </td>
              <td>
                <span class="badge <?= badgeEstilo($lote['estilo']) ?>"><?= e(strtoupper($lote['estilo'])) ?></span>
              </td>
              <td style="color:var(--text-muted);font-size:.8rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                <?= e($lote['comentarios'] ?: '—') ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
        <p style="color:var(--text-muted);font-size:.85rem">No hay lotes registrados aún.</p>
      <?php endif; ?>
    </div>

  </div><!-- /grid -->
</div><!-- /contenido -->

<script>
function seleccionarFila(row) {
  const radio = row.querySelector('input[type="radio"]');
  if (radio) radio.checked = true;
}

function getLoteSeleccionado() {
  const radio = document.querySelector('#tabla-lotes input[type="radio"]:checked');
  return radio ? radio.value : null;
}

document.getElementById('btnVerDetalles')?.addEventListener('click', function() {
  const id = getLoteSeleccionado();
  if (!id) { alert('Seleccioná un lote primero.'); return; }
  window.location.href = 'detalle_lote?id_lote=' + encodeURIComponent(id);
});

document.getElementById('btnVerNotas')?.addEventListener('click', function() {
  const id = getLoteSeleccionado();
  if (!id) { alert('Seleccioná un lote primero.'); return; }
  window.location.href = 'planilla_cata?id=' + encodeURIComponent(id);
});

// loadContent() para compatibilidad con links del menú viejo
function loadContent(page) { window.location.href = page; }
</script>

</body>
</html>
