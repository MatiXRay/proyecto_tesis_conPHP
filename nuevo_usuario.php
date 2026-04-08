<?php
/**
 * BIALYSTOK BREWING CO — Nuevo usuario (solo admin)
 * Reemplaza: nuevo_usuario.php
 */

require_once 'auth.php';
requireLogin();
requireRole([1], 'configuracion');
require_once 'conexion.php';

$menu_activo = 'configuracion';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nuevo usuario · Bialystok Brewing</title>
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
      <h1>Nuevo usuario</h1>
      <p class="page-subtitle">Solo administradores</p>
    </div>
    <a href="configuracion" class="btn btn-ghost btn-sm">← Volver</a>
  </div>

  <div class="card fade-in" style="max-width:520px">
    <div class="form-group">
      <label class="form-label">Nombre *</label>
      <input type="text" id="nombre" placeholder="Juan">
    </div>
    <div class="form-group">
      <label class="form-label">Apellido *</label>
      <input type="text" id="apellido" placeholder="García">
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
      <div class="form-group">
        <label class="form-label">Username *</label>
        <input type="text" id="username" placeholder="jgarcia" autocomplete="off">
      </div>
      <div class="form-group">
        <label class="form-label">Teléfono</label>
        <input type="tel" id="telefono" placeholder="+54 9 11...">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Email *</label>
      <input type="email" id="mail" placeholder="juan@ejemplo.com" autocomplete="off">
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
      <div class="form-group">
        <label class="form-label">Contraseña *</label>
        <input type="password" id="password" autocomplete="new-password">
      </div>
      <div class="form-group">
        <label class="form-label">Confirmar contraseña *</label>
        <input type="password" id="confirmPassword" autocomplete="new-password">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Rol *</label>
      <select id="rol_id">
        <option value="1">Admin</option>
        <option value="2">Brewer</option>
        <option value="3" selected>Taster</option>
      </select>
    </div>
    <div style="display:flex;gap:.75rem;margin-top:1rem">
      <button class="btn btn-primary" onclick="guardar()">Crear usuario</button>
      <a href="configuracion" class="btn btn-ghost">Cancelar</a>
    </div>
  </div>
</div>

<script>
function guardar() {
  const nombre   = document.getElementById('nombre').value.trim();
  const apellido = document.getElementById('apellido').value.trim();
  const username = document.getElementById('username').value.trim();
  const telefono = document.getElementById('telefono').value.trim();
  const mail     = document.getElementById('mail').value.trim();
  const password = document.getElementById('password').value;
  const confirm  = document.getElementById('confirmPassword').value;
  const rol_id   = document.getElementById('rol_id').value;

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
  fd.append('telefono', telefono);
  fd.append('mail',     mail);
  fd.append('password', password);
  fd.append('rol_id',   rol_id);

  fetch('guardar_usuario', {
    method: 'POST',
    body: fd
  })
  .then(r => r.json())
  .then(data => {
    if (data.status === 'success') {
      alert('Usuario creado con éxito.');
      window.location.href = 'configuracion';
    } else {
      alert('Error: ' + (data.message || 'No se pudo crear el usuario.'));
    }
  })
  .catch(() => alert('Error de red.'));
}

function loadContent(page) { window.location.href = page; }
</script>
</body>
</html>
