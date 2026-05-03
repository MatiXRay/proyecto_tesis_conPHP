<?php
/**
 * BIALYSTOK BREWING CO — Configuración de entorno
 *
 * ⚠️  ESTE ARCHIVO DEBE VIVIR FUERA DE public_html
 *     Ruta recomendada: /home/tu_usuario/config.php
 *     NO subir al repositorio git (.gitignore)
 */

// ── Base de datos ─────────────────────────────────────────
// Para Docker local: host='db', user='fabrica', pass='fabrica', db='fabrica'
// Para producción Ferozo: host='localhost', user='c2651024_fabrica', pass=<real>, db='c2651024_fabrica'
define('DB_HOST',     getenv('DB_HOST') ?: 'localhost');
define('DB_USER',     getenv('DB_USER') ?: 'c2651024_fabrica');
define('DB_PASS',     getenv('DB_PASS') ?: 'TU_PASSWORD_AQUI');
define('DB_NAME',     getenv('DB_NAME') ?: 'c2651024_fabrica');
define('DB_CHARSET',  'utf8mb4');

// ── Seguridad de sesión ───────────────────────────────────
define('SESSION_LIFETIME',  3600);      // 1 hora de inactividad → cierra sesión
define('SESSION_NAME',      'bco_session');

// ── Entorno ───────────────────────────────────────────────
define('APP_ENV',   'production');      // 'development' | 'production'
define('APP_DEBUG', false);             // false en producción SIEMPRE
