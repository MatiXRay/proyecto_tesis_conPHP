<?php
/**
 * BIALYSTOK BREWING CO — Registro de taster (público)
 * Solo disponible cuando creacion_usuarios = 1 en configuraciones
 * Siempre crea rol TASTER (rol_id = 3)
 */

// No usar auth.php — esta página es pública
if (session_status() === PHP_SESSION_NONE) session_start();

// Si ya está logueado, redirigir según rol
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $rol = (int)($_SESSION['rol_id'] ?? 0);
    if ($rol === 1)      $dest = 'configuracion';
    elseif ($rol === 3) $dest = 'panel_cata';
    else                $dest = 'inicio';
    header('Location: ' . $dest);
    exit;
}

require_once 'conexion.php';

// Verificar que la creación de usuarios está habilitada
try {
    $pdo = getPDO();
    $cfg = $pdo->query("SELECT creacion_usuarios FROM configuraciones WHERE id = 1 LIMIT 1")->fetch();
    if (!$cfg || !(int)$cfg['creacion_usuarios']) {
        header('Location: login');
        exit;
    }
} catch (PDOException $ex) {
    header('Location: login');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear perfil de taster · BRAUMEISTER</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/bialy-design-system.css">
  <style>
    body { background:var(--color-bg); }
    .wrap {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
      background-image:
        radial-gradient(ellipse at 20% 50%, rgba(74,170,74,0.06) 0%, transparent 60%),
        radial-gradient(ellipse at 80% 20%, rgba(74,170,74,0.04) 0%, transparent 50%);
    }
    .box {
      width: 100%;
      max-width: 460px;
      background: var(--color-surface);
      border: 1px solid var(--color-border);
      border-radius: var(--radius-lg);
      padding: 2rem 2.25rem;
    }
    .brand {
      font-size: .72rem;
      font-weight: 600;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: var(--text-muted);
      margin-bottom: .35rem;
    }
    .title { font-size: 1.3rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1.5rem; }
  </style>
</head>
<body>
<div class="wrap">
  <div class="box">
    <div class="brand">BRAUMEISTER</div>
    <div class="title">Crear perfil de taster</div>

    <div class="form-group">
      <label class="form-label">Nombre *</label>
      <input type="text" id="nombre" placeholder="Juan" autocomplete="given-name">
    </div>
    <div class="form-group">
      <label class="form-label">Apellido *</label>
      <input type="text" id="apellido" placeholder="García" autocomplete="family-name">
    </div>
    <div class="form-group">
      <label class="form-label">Username *</label>
      <input type="text" id="username" placeholder="jgarcia" autocomplete="username">
    </div>
    <div class="form-group">
      <label class="form-label">Email *</label>
      <input type="email" id="mail" placeholder="juan@ejemplo.com" autocomplete="email">
    </div>
    <div class="form-group">
      <label class="form-label">Teléfono</label>
      <input type="tel" id="telefono" placeholder="+54 9 11..." autocomplete="tel">
    </div>
    <div class="form-group">
      <label class="form-label">Contraseña * <span style="font-size:.72rem;color:var(--text-muted)">(mínimo 8 caracteres)</span></label>
      <input type="password" id="password" autocomplete="new-password">
    </div>
    <div class="form-group">
      <label class="form-label">Confirmar contraseña *</label>
      <input type="password" id="confirmPassword" autocomplete="new-password">
    </div>

    <button class="btn btn-primary" style="width:100%;margin-top:.5rem" onclick="registrar()">
      Crear cuenta
    </button>

    <p style="text-align:center;margin-top:1rem;font-size:.82rem;color:var(--text-muted)">
      ¿Ya tenés cuenta? <a href="login" style="color:var(--text-amber)">Iniciar sesión</a>
    </p>
  </div>
</div>

<script>
function registrar() {
  const nombre   = document.getElementById('nombre').value.trim();
  const apellido = document.getElementById('apellido').value.trim();
  const username = document.getElementById('username').value.trim();
  const mail     = document.getElementById('mail').value.trim();
  const telefono = document.getElementById('telefono').value.trim();
  const password = document.getElementById('password').value;
  const confirm  = document.getElementById('confirmPassword').value;

  if (!nombre || !apellido || !username || !mail || !password) {
    alert('Completá todos los campos obligatorios.'); return;
  }
  if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(mail)) {
    alert('El email no es válido.'); return;
  }
  if (password !== confirm) {
    alert('Las contraseñas no coinciden.'); return;
  }
  if (password.length < 8) {
    alert('La contraseña debe tener al menos 8 caracteres.'); return;
  }

  const fd = new FormData();
  fd.append('nombre',   nombre);
  fd.append('apellido', apellido);
  fd.append('username', username);
  fd.append('mail',     mail);
  fd.append('telefono', telefono);
  fd.append('password', password);
  fd.append('rol_id',   '3'); // Siempre taster

  fetch('guardar_usuario', {
    method: 'POST',
    body: fd
  })
  .then(r => r.json())
  .then(data => {
    if (data.status === 'success') {
      alert('¡Cuenta creada con éxito! Ya podés iniciar sesión.');
      window.location.href = 'login';
    } else {
      alert('Error: ' + (data.message || 'No se pudo crear la cuenta.'));
    }
  })
  .catch(() => alert('Error de red. Intentá de nuevo.'));
}
</script>
</body>
</html>
