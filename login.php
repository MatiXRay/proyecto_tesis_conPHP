<?php
/**
 * BIALYSTOK BREWING CO — Login
 * Reemplaza: login.php
 *
 * Correcciones:
 *  - session_start() llamado dos veces (original lo hacía) → una sola vez en auth.php
 *  - Sin session_regenerate_id() al loguearse → session fixation attack
 *  - Sin token CSRF en el formulario de login
 *  - El username se mostraba en el HTML sin escapar (XSS)
 */

require_once 'auth.php';   // Inicia sesión de forma segura

// Si ya está logueado, redirigir
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: inicio');
    exit;
}

require_once 'conexion.php';

// ── Leer config de creación de usuarios ──────────────────────────────────────
$creacion_usuarios = false;
try {
    $stmt = getPDO()->query("SELECT creacion_usuarios FROM configuraciones LIMIT 1");
    $cfg  = $stmt->fetch();
    $creacion_usuarios = !empty($cfg['creacion_usuarios']);
} catch (PDOException $e) {
    // No es crítico; seguimos sin el link de registro
}

// ── Procesamiento del login ───────────────────────────────────────────────────
$username_err = $password_err = $general_err = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verificar CSRF
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $general_err = 'Token de seguridad inválido. Intentá de nuevo.';
    } else {

        $username = getStringParam('username', 'POST', 80);
        $password = getStringParam('password', 'POST', 200);

        if ($username === '') {
            $username_err = 'Por favor, ingresá tu nombre de usuario.';
        }
        if ($password === '') {
            $password_err = 'Por favor, ingresá tu contraseña.';
        }

        if ($username_err === '' && $password_err === '') {
            try {
                $pdo  = getPDO();
                $stmt = $pdo->prepare(
                    'SELECT id, username, password, rol_id FROM users WHERE username = ? LIMIT 1'
                );
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                // Siempre verificar hash aunque el usuario no exista
                // (previene timing attacks / enumeración de usuarios)
                $dummy_hash = '$2y$10$invalid.hash.that.will.never.match.anything.ever';
                $hash_to_check = $user ? $user['password'] : $dummy_hash;

                if ($user && password_verify($password, $hash_to_check)) {
                    // ✅ Login exitoso

                    // Regenerar ID de sesión (previene session fixation)
                    session_regenerate_id(true);

                    $_SESSION['loggedin']      = true;
                    $_SESSION['id']            = (int) $user['id'];
                    $_SESSION['username']      = $user['username'];
                    $_SESSION['rol_id']        = (int) $user['rol_id'];
                    $_SESSION['last_activity'] = time();

                    // Regenerar token CSRF para la nueva sesión
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                    // Redirigir según rol
                    header('Location: ' . ((int)$user['rol_id'] === 3 ? 'panel_cata' : 'inicio'));
                    exit;

                } else {
                    // Error genérico (no revelar si falla el usuario o la contraseña)
                    $general_err = 'Credenciales incorrectas. Verificá tu usuario y contraseña.';
                }

            } catch (PDOException $e) {
                error_log('[Bialystok] Error en login: ' . $e->getMessage());
                $general_err = 'Error interno. Intentá más tarde.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bialystok Brewing · Acceso</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/bialy-design-system.css">
</head>
<body class="login-page">

  <div class="login-card">
    <div class="login-brand">
      <h1>Bialystok Brewing</h1>
      <p>Sistema de gestión de producción</p>
    </div>

    <?php if ($general_err): ?>
      <div class="login-error"><?= e($general_err) ?></div>
    <?php endif; ?>

    <form action="login" method="POST" autocomplete="on">
      <?= csrfField() ?>

      <div class="form-group">
        <label class="form-label" for="username">Usuario</label>
        <input
          type="text"
          id="username"
          name="username"
          autocomplete="username"
          value="<?= e($username) ?>"
          style="<?= $username_err ? 'border-color:var(--color-danger)' : '' ?>"
          required
        >
        <?php if ($username_err): ?>
          <span style="font-size:.78rem;color:var(--color-danger);margin-top:.25rem;display:block"><?= e($username_err) ?></span>
        <?php endif; ?>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Contraseña</label>
        <input
          type="password"
          id="password"
          name="password"
          autocomplete="current-password"
          style="<?= $password_err ? 'border-color:var(--color-danger)' : '' ?>"
          required
        >
        <?php if ($password_err): ?>
          <span style="font-size:.78rem;color:var(--color-danger);margin-top:.25rem;display:block"><?= e($password_err) ?></span>
        <?php endif; ?>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:.5rem;padding:.7rem">
        Iniciar sesión
      </button>
    </form>

    <?php if ($creacion_usuarios): ?>
      <p style="text-align:center;margin-top:1.25rem;font-size:.82rem;color:var(--text-muted)">
        ¿Sin cuenta? <a href="nuevo_taster">Crear perfil de taster</a>
      </p>
    <?php endif; ?>
  </div>

</body>
</html>
