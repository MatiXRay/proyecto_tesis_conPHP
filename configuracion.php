<?php
/**
 * BIALYSTOK BREWING CO — Configuración (solo admins)
 * Reemplaza: configuracion.php
 *
 * Secciones:
 *  1. Usuarios — listado, nuevo, eliminar, cambiar contraseña
 *  2. Estilos — duración estimada por estilo (para el timeline)
 *  3. Ajustes — creación de usuarios habilitada/deshabilitada
 */

require_once 'auth.php';
requireLogin();
requireRole([1], 'inicio'); // Solo administradores

require_once 'conexion.php';

$menu_activo = 'configuracion';
$searchTerm  = getStringParam('searchTerm', 'GET', 100);
$pagina      = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina  = 20;
$offset      = ($pagina - 1) * $por_pagina;
$like        = '%' . $searchTerm . '%';

try {
    $pdo = getPDO();

    // Usuarios
    $stmt_total = $pdo->prepare(
        "SELECT COUNT(*) FROM users u INNER JOIN roles r ON u.rol_id = r.id
         WHERE u.nombre LIKE ? OR u.apellido LIKE ? OR u.username LIKE ? OR u.mail LIKE ? OR r.rol LIKE ?"
    );
    $stmt_total->execute([$like,$like,$like,$like,$like]);
    $total = (int) $stmt_total->fetchColumn();
    $total_paginas = max(1, (int) ceil($total / $por_pagina));

    $stmt = $pdo->prepare(
        "SELECT u.id, u.nombre, u.apellido, u.username, u.mail, u.telefono, r.rol, u.rol_id
         FROM users u INNER JOIN roles r ON u.rol_id = r.id
         WHERE u.nombre LIKE ? OR u.apellido LIKE ? OR u.username LIKE ? OR u.mail LIKE ? OR r.rol LIKE ?
         ORDER BY u.nombre ASC
         LIMIT $offset, $por_pagina"
    );
    $stmt->execute([$like,$like,$like,$like,$like]);
    $usuarios = $stmt->fetchAll();

    // Roles disponibles
    $roles = $pdo->query("SELECT id, rol FROM roles ORDER BY id")->fetchAll();

    // Estilos con duración
    $estilos = $pdo->query("SELECT id, nombre, duracion_dias FROM estilos_cerveza ORDER BY nombre")->fetchAll();

    // Config app
    $cfg = $pdo->query("SELECT creacion_usuarios FROM configuraciones WHERE id = 1 LIMIT 1")->fetch();
    $creacion_usuarios = (int)($cfg['creacion_usuarios'] ?? 0);

    // Alertas
    $alertas = $pdo->query("SELECT * FROM alertas ORDER BY id")->fetchAll();

} catch (PDOException $ex) {
    error_log('[Bialystok config] ' . $ex->getMessage());
    $usuarios = $roles = $estilos = [];
    $total = $total_paginas = 0;
    $creacion_usuarios = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Configuración · Bialystok Brewing</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/bialy-design-system.css">
  <style>
    .tabs { display:flex; gap:0; border-bottom:1px solid var(--color-border); margin-bottom:1.5rem; }
    .tab  { padding:.6rem 1.1rem; font-size:.85rem; font-weight:500; color:var(--text-muted); border-bottom:2px solid transparent; cursor:pointer; transition:color var(--transition),border-color var(--transition); margin-bottom:-1px; }
    .tab:hover  { color:var(--text-primary); }
    .tab.active { color:var(--text-amber); border-bottom-color:var(--amber-400); }
    .tab-pane   { display:none; }
    .tab-pane.active { display:block; }
    .toggle-wrap { display:flex; align-items:center; gap:1rem; }
    .toggle { position:relative; width:44px; height:24px; }
    .toggle input { opacity:0; width:0; height:0; }
    .toggle-slider { position:absolute; inset:0; background:var(--color-surface-3); border-radius:99px; cursor:pointer; transition:.2s; }
    .toggle-slider::before { content:''; position:absolute; width:18px; height:18px; left:3px; top:3px; background:#fff; border-radius:50%; transition:.2s; }
    .toggle input:checked + .toggle-slider { background:var(--amber-400); }
    .toggle input:checked + .toggle-slider::before { transform:translateX(20px); }
    .dur-input { width:70px; font-family:'DM Mono',monospace; text-align:center; }
  </style>
</head>
<body>
<?php require 'menu.php'; ?>
<?php require 'info_user.php'; ?>

<div id="contenido" class="main-content">

  <div class="page-header fade-in">
    <div><h1>Configuración</h1><p class="page-subtitle">Solo administradores</p></div>
  </div>

  <!-- Tabs -->
  <div class="tabs fade-in">
    <div class="tab active" onclick="switchTab('usuarios',this)">Usuarios</div>
    <div class="tab" onclick="switchTab('estilos',this)">Duración estilos</div>
    <div class="tab" onclick="switchTab('alertas',this)">Alertas</div>
    <div class="tab" onclick="switchTab('ajustes',this)">Ajustes</div>
  </div>

  <!-- ── TAB USUARIOS ───────────────────────────────────── -->
  <div class="tab-pane active" id="tab-usuarios">

    <div class="toolbar fade-in">
      <form action="configuracion" method="GET" style="display:contents">
        <div class="search-box">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--text-muted)"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
          <input type="text" name="searchTerm" placeholder="Buscar usuario…" value="<?= e($searchTerm) ?>">
        </div>
        <button type="submit" class="btn btn-ghost btn-sm">Buscar</button>
        <?php if ($searchTerm): ?><a href="configuracion" class="btn btn-ghost btn-sm">✕</a><?php endif; ?>
      </form>
      <div class="toolbar-right">
        <button class="btn btn-ghost btn-sm" id="btnPass">Cambiar contraseña</button>
        <button class="btn btn-danger btn-sm" id="btnElimUser">Eliminar</button>
        <button class="btn btn-primary btn-sm" onclick="window.location.href='nuevo_usuario'">+ Nuevo usuario</button>
      </div>
    </div>

    <div class="table-wrapper fade-in">
      <table id="tabla-users">
        <thead>
          <tr>
            <th style="width:30px"></th>
            <th>Nombre</th><th>Apellido</th><th>Username</th>
            <th>Mail</th><th>Teléfono</th><th>Rol</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($usuarios): ?>
            <?php foreach ($usuarios as $u): ?>
            <tr style="cursor:pointer" onclick="selRow(this)">
              <td><input type="radio" name="sel-user" value="<?= (int)$u['id'] ?>"
                         data-username="<?= e($u['username']) ?>"
                         style="accent-color:var(--amber-400)"></td>
              <td><?= e($u['nombre']) ?></td>
              <td><?= e($u['apellido']) ?></td>
              <td style="font-family:'DM Mono',monospace"><?= e(strtoupper($u['username'])) ?></td>
              <td style="color:var(--text-muted)"><?= e($u['mail']) ?></td>
              <td style="color:var(--text-muted);font-size:.82rem"><?= e($u['telefono'] ?? '—') ?></td>
              <td><span class="badge <?= (int)$u['rol_id']===1 ? 'badge-amber' : 'badge-muted' ?>">
                <?= e(strtoupper($u['rol'])) ?>
              </span></td>
            </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="7" style="text-align:center;padding:2rem;color:var(--text-muted)">Sin resultados.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($total_paginas > 1): ?>
    <div class="pagination fade-in">
      <?php if ($pagina > 1): ?><a href="?pagina=<?= $pagina-1 ?>&searchTerm=<?= urlencode($searchTerm) ?>">← Anterior</a><?php endif; ?>
      <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
        <a href="?pagina=<?= $i ?>&searchTerm=<?= urlencode($searchTerm) ?>" <?= $i===$pagina ? 'class="active"' : '' ?>><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($pagina < $total_paginas): ?><a href="?pagina=<?= $pagina+1 ?>&searchTerm=<?= urlencode($searchTerm) ?>">Siguiente →</a><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ── TAB DURACIÓN ESTILOS ───────────────────────────── -->
  <div class="tab-pane" id="tab-estilos">
    <div class="card fade-in">
      <div class="card-title">Duración estimada por estilo (días desde cocción hasta envasado)</div>
      <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:1rem">
        Este valor se usa como duración por defecto en el timeline de planificación. Podés ajustarlo manualmente por lote al planificar.
      </p>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Estilo</th><th style="width:120px">Días estimados</th><th style="width:80px"></th></tr></thead>
          <tbody>
            <?php foreach ($estilos as $est): ?>
            <tr>
              <td style="font-weight:500"><?= e($est['nombre']) ?></td>
              <td>
                <input type="number" class="dur-input" min="1" max="365"
                       value="<?= (int)($est['duracion_dias'] ?? 21) ?>"
                       data-estilo-id="<?= (int)$est['id'] ?>">
              </td>
              <td>
                <input type="color"
                       value="<?= e($est['color'] ?? '#4a8f4a') ?>"
                       data-estilo-id="<?= (int)$est['id'] ?>"
                       class="color-pick"
                       style="width:38px;height:28px;padding:2px;border-radius:4px;border:1px solid var(--color-border-md);background:var(--color-surface-3);cursor:pointer"
                       onchange="guardarColor(this)">
              </td>
              <td>
                <button class="btn btn-ghost btn-sm" onclick="guardarDuracion(this)">Guardar</button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ── TAB ALERTAS ────────────────────────────────────── -->
  <div class="tab-pane" id="tab-alertas">
    <div class="card fade-in">
      <div class="card-title">Alertas de mantenimiento</div>
      <p style="font-size:.82rem;color:var(--text-muted);margin-bottom:1rem">
        Configurá las tareas periódicas. El sistema mostrará una alerta en el inicio cuando estén vencidas o próximas a vencer (menos de 30 días).
      </p>
      <div class="table-wrapper">
        <table id="tabla-alertas">
          <thead>
            <tr>
              <th>Descripción</th>
              <th style="width:130px">Periodicidad</th>
              <th style="width:150px">Última vez</th>
              <th style="width:120px">Próximo vence</th>
              <th style="width:90px">Estado</th>
              <th style="width:110px"></th>
            </tr>
          </thead>
          <tbody>
            <?php
            $alertas = $alertas ?? [];
            foreach ($alertas as $al):
              $per = (int)$al['periodicidad_dias'];
              $ultima = $al['ultima_vez'];
              $proximo = '—'; $dias_rest = null; $estado = 'sin-datos';
              if ($ultima && $ultima !== '0000-00-00') {
                  $dt_p = (new DateTime($ultima))->modify("+{$per} days");
                  $proximo = $dt_p->format('d/m/Y');
                  $dias_rest = (int)(new DateTime())->diff($dt_p)->format('%r%a');
                  $estado = $dias_rest < 0 ? 'vencida' : ($dias_rest <= 30 ? 'proxima' : 'ok');
              }
              $badge = match($estado) {
                'vencida' => '<span class="badge" style="background:rgba(180,50,50,.2);color:#e06060;border-color:rgba(180,50,50,.3)">Vencida</span>',
                'proxima' => '<span class="badge badge-amber">Próxima</span>',
                'ok'      => '<span class="badge" style="background:rgba(50,150,50,.15);color:#4aaa4a;border-color:rgba(50,150,50,.25)">Al día</span>',
                default   => '<span class="badge badge-muted">Sin fecha</span>',
              };
            ?>
            <tr id="alerta-row-<?= (int)$al['id'] ?>">
              <td>
                <span class="al-view-desc"><?= e($al['descripcion']) ?></span>
                <input class="al-edit-desc" type="text" value="<?= e($al['descripcion']) ?>" style="display:none;width:100%">
              </td>
              <td>
                <span class="al-view-per"><?= $per ?> días</span>
                <input class="al-edit-per" type="number" value="<?= $per ?>" min="1" style="display:none;width:75px">
              </td>
              <td>
                <input type="date" class="alerta-fecha" data-id="<?= (int)$al['id'] ?>"
                       value="<?= e($ultima ?? '') ?>"
                       style="font-size:.78rem;background:var(--color-surface-3);border:1px solid var(--color-border-md);border-radius:4px;color:var(--text-primary);padding:.25rem .4rem;width:140px"
                       onchange="guardarFechaAlerta(this)">
              </td>
              <td style="font-family:'DM Mono',monospace;font-size:.78rem;color:var(--text-muted)">
                <?= $proximo ?>
                <?php if ($dias_rest !== null): ?>
                <br><span style="font-size:.68rem;color:<?= $dias_rest<0?'var(--color-danger)':($dias_rest<=30?'var(--text-amber)':'var(--text-muted)') ?>">
                  <?= $dias_rest>=0 ? 'en '.$dias_rest.'d' : abs($dias_rest).'d atrás' ?>
                </span>
                <?php endif; ?>
              </td>
              <td><?= $badge ?></td>
              <td>
                <div style="display:flex;gap:.3rem">
                  <button class="btn btn-ghost btn-sm al-btn-edit" onclick="editarAlerta(<?= (int)$al['id'] ?>)">Editar</button>
                  <button class="btn btn-ghost btn-sm al-btn-save" style="display:none" onclick="guardarAlerta(<?= (int)$al['id'] ?>)">Guardar</button>
                  <button class="btn btn-danger btn-sm" onclick="eliminarAlerta(<?= (int)$al['id'] ?>)">✕</button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid var(--color-border)">
        <div class="card-title">Nueva alerta</div>
        <div style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap">
          <div class="form-group" style="flex:1;min-width:200px;margin:0">
            <label class="form-label">Descripción</label>
            <input type="text" id="nueva-desc" placeholder="Ej: Limpieza intercambiador de calor">
          </div>
          <div class="form-group" style="width:140px;margin:0">
            <label class="form-label">Periodicidad (días)</label>
            <input type="number" id="nueva-per" value="30" min="1">
          </div>
          <button class="btn btn-primary btn-sm" onclick="nuevaAlerta()">+ Agregar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ── TAB AJUSTES ────────────────────────────────────── -->
  <div class="tab-pane" id="tab-ajustes">
    <div class="card fade-in" style="max-width:500px">
      <div class="card-title">Registro de usuarios</div>
      <div class="toggle-wrap">
        <label class="toggle">
          <input type="checkbox" id="toggle-usuarios" <?= $creacion_usuarios ? 'checked' : '' ?>
                 onchange="cambiarCreacionUsuarios(this.checked)">
          <span class="toggle-slider"></span>
        </label>
        <div>
          <div style="font-size:.9rem;font-weight:500" id="toggle-label">
            <?= $creacion_usuarios ? 'Habilitada' : 'Deshabilitada' ?>
          </div>
          <div style="font-size:.78rem;color:var(--text-muted)">
            Permite que nuevos tasters se registren desde el login
          </div>
        </div>
      </div>
    </div>
  </div>

</div><!-- /contenido -->

<!-- Modal cambiar contraseña -->
<div id="modal-pass" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:1000;align-items:center;justify-content:center">
  <div class="card" style="width:440px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem">
      <h2 style="margin:0;font-size:1rem">Cambiar contraseña · <span id="modal-username" style="color:var(--text-amber)"></span></h2>
      <button onclick="cerrarPass()" style="background:none;border:none;color:var(--text-muted);font-size:1.3rem;cursor:pointer">✕</button>
    </div>
    <div class="form-group">
      <label class="form-label">Tu contraseña de administrador</label>
      <input type="password" id="admin-pass" autocomplete="current-password">
    </div>
    <div class="form-group">
      <label class="form-label">Nueva contraseña</label>
      <input type="password" id="new-pass" autocomplete="new-password">
    </div>
    <div class="form-group">
      <label class="form-label">Confirmar nueva contraseña</label>
      <input type="password" id="confirm-pass" autocomplete="new-password">
    </div>
    <div style="display:flex;gap:.75rem;margin-top:1rem">
      <button class="btn btn-primary" onclick="confirmarCambioPass()">Confirmar</button>
      <button class="btn btn-ghost" onclick="cerrarPass()">Cancelar</button>
    </div>
  </div>
</div>

<script>
const CSRF     = '<?= e(getCsrfToken()) ?>';
const ADMIN_ID = <?= (int)$_SESSION['id'] ?>;

// ── Tabs ──────────────────────────────────────────────────
function switchTab(id, el) {
  document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.getElementById('tab-'+id).classList.add('active');
  el.classList.add('active');
}

// ── Selección de fila ─────────────────────────────────────
function selRow(row) { row.querySelector('input[type="radio"]').checked = true; }
function getSelectedUser() {
  const r = document.querySelector('#tabla-users input[type="radio"]:checked');
  return r ? { id: r.value, username: r.dataset.username } : null;
}

// ── Eliminar usuario ──────────────────────────────────────
document.getElementById('btnElimUser')?.addEventListener('click', function() {
  const u = getSelectedUser();
  if (!u) { alert('Seleccioná un usuario primero.'); return; }
  if (+u.id === ADMIN_ID) { alert('No podés eliminar tu propia cuenta.'); return; }
  if (!confirm('¿Eliminar al usuario ' + u.username + '?')) return;
  fetch('eliminar_registro', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ id: u.id, tabla:'user', csrf_token: CSRF })
  }).then(r=>r.json()).then(d=>{
    if (d.success) location.reload();
    else alert('Error: '+(d.message||'No se pudo eliminar.'));
  });
});

// ── Cambiar contraseña ────────────────────────────────────
function cerrarPass() { document.getElementById('modal-pass').style.display='none'; }

document.getElementById('btnPass')?.addEventListener('click', function() {
  const u = getSelectedUser();
  if (!u) { alert('Seleccioná un usuario primero.'); return; }
  document.getElementById('modal-username').textContent = u.username;
  document.getElementById('admin-pass').value = '';
  document.getElementById('new-pass').value   = '';
  document.getElementById('confirm-pass').value = '';
  document.getElementById('modal-pass').style.display = 'flex';
  document.getElementById('admin-pass').focus();
});

function confirmarCambioPass() {
  const adminPass   = document.getElementById('admin-pass').value;
  const newPass     = document.getElementById('new-pass').value;
  const confirmPass = document.getElementById('confirm-pass').value;
  const u = getSelectedUser();

  if (!adminPass || !newPass || !confirmPass) { alert('Completá todos los campos.'); return; }
  if (newPass !== confirmPass) { alert('Las contraseñas no coinciden.'); return; }
  if (newPass.length < 8) { alert('La contraseña debe tener al menos 8 caracteres.'); return; }

  fetch('cambiar_contrasena', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({
      adminId: ADMIN_ID, adminPassword: adminPass,
      userId: u.id, newPassword: newPass,
      csrf_token: CSRF
    })
  }).then(r=>r.json()).then(d=>{
    if (d.success) { alert('Contraseña cambiada con éxito.'); cerrarPass(); }
    else alert('Error: '+(d.message||'No se pudo cambiar.'));
  });
}

// ── Duración estilos ──────────────────────────────────────
function guardarDuracion(btn) {
  const row = btn.closest('tr');
  const inp = row.querySelector('.dur-input');
  const id  = inp.dataset.estiloId;
  const dur = parseInt(inp.value);
  if (!dur || dur < 1) { alert('Ingresá un número válido.'); return; }

  fetch('config_update', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ accion:'duracion_estilo', estilo_id: id, duracion: dur, csrf_token: CSRF })
  }).then(r=>r.json()).then(d=>{
    if (d.success) {
      btn.textContent = '✓';
      btn.style.color = 'var(--color-success)';
      setTimeout(() => { btn.textContent = 'Guardar'; btn.style.color = ''; }, 2000);
    } else alert('Error al guardar.');
  });
}

// ── Toggle creación usuarios ──────────────────────────────
function cambiarCreacionUsuarios(enabled) {
  document.getElementById('toggle-label').textContent = enabled ? 'Habilitada' : 'Deshabilitada';
  fetch('config_update', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ accion:'creacion_usuarios', estado: enabled ? 1 : 0, csrf_token: CSRF })
  }).then(r=>r.json()).then(d=>{
    if (!d.success) alert('Error al guardar.');
  });
}

// ── Alertas ───────────────────────────────────────────────
function editarAlerta(id) {
  const row = document.getElementById('alerta-row-' + id);
  row.querySelectorAll('.al-view-desc,.al-view-per').forEach(e => e.style.display='none');
  row.querySelectorAll('.al-edit-desc,.al-edit-per').forEach(e => e.style.display='');
  row.querySelector('.al-btn-edit').style.display = 'none';
  row.querySelector('.al-btn-save').style.display = '';
}

function guardarAlerta(id) {
  const row  = document.getElementById('alerta-row-' + id);
  const desc = row.querySelector('.al-edit-desc').value.trim();
  const per  = row.querySelector('.al-edit-per').value;
  if (!desc || !per) { alert('Completá todos los campos.'); return; }
  fetch('config_update', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ accion:'editar_alerta', alerta_id:id, descripcion:desc, periodicidad:per, csrf_token:CSRF })
  }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert('Error al guardar.'); });
}

function guardarFechaAlerta(inp) {
  fetch('config_update', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ accion:'fecha_alerta', alerta_id:inp.dataset.id, ultima_vez:inp.value, csrf_token:CSRF })
  }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert('Error al guardar la fecha.'); });
}

function eliminarAlerta(id) {
  if (!confirm('¿Eliminar esta alerta?')) return;
  fetch('config_update', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ accion:'eliminar_alerta', alerta_id:id, csrf_token:CSRF })
  }).then(r=>r.json()).then(d=>{ if(d.success) document.getElementById('alerta-row-'+id)?.remove(); else alert('Error.'); });
}

function nuevaAlerta() {
  const desc = document.getElementById('nueva-desc').value.trim();
  const per  = document.getElementById('nueva-per').value;
  if (!desc || !per) { alert('Completá descripción y periodicidad.'); return; }
  fetch('config_update', {
    method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({ accion:'nueva_alerta', descripcion:desc, periodicidad:per, csrf_token:CSRF })
  }).then(r=>r.json()).then(d=>{ if(d.success) location.reload(); else alert('Error al crear.'); });
}

function loadContent(page) { window.location.href = page; }

</script>
</body>
</html>
