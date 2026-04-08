<?php
/**
 * BIALYSTOK BREWING CO — Configuración de entorno
 *
 * ⚠️  ESTE ARCHIVO DEBE VIVIR FUERA DE public_html
 *     Ruta recomendada: /home/tu_usuario/config.php
 *     NO subir al repositorio git (.gitignore)
 */

// ── Base de datos ─────────────────────────────────────────
define('DB_HOST',     'localhost');
define('DB_USER',     'c2651024_fabrica');   // <-- reemplazá con tu usuario real
define('DB_PASS',     'TU_PASSWORD_AQUI');   // <-- reemplazá con tu password real
define('DB_NAME',     'c2651024_fabrica');   // <-- reemplazá con tu DB real
define('DB_CHARSET',  'utf8mb4');

// ── Seguridad de sesión ───────────────────────────────────
define('SESSION_LIFETIME',  3600);      // 1 hora de inactividad → cierra sesión
define('SESSION_NAME',      'bco_session');

// ── Entorno ───────────────────────────────────────────────
define('APP_ENV',   'production');      // 'development' | 'production'
define('APP_DEBUG', false);             // false en producción SIEMPRE
