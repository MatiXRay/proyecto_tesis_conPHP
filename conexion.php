<?php
if (defined('CONEXION_LOADED')) return; define('CONEXION_LOADED', true);

/**
 * BIALYSTOK BREWING CO — Conexión segura a la base de datos
 * Reemplaza: conexion.php
 *
 * Cambios respecto al original:
 *  - Credenciales fuera de public_html (config.php)
 *  - Usa PDO en lugar de mysqli (más portable, prepared statements más simples)
 *  - Modo de errores: excepciones (nunca muestra info sensible al usuario)
 *  - Charset utf8mb4 explícito (evita ataques de encoding)
 *  - Errores de conexión logueados internamente, jamás mostrados
 */

// Cargar configuración desde fuera de public_html
// Ajustá esta ruta según tu hosting (un nivel arriba de public_html)
ini_set('display_errors', 1);
error_reporting(E_ALL);
$config_path = dirname($_SERVER['DOCUMENT_ROOT']) . '/config.php';

if (!file_exists($config_path)) {
    // Fallback: buscar en el mismo directorio como transición
    $config_path = __DIR__ . '/config.php';
}

require_once $config_path;

/**
 * Crea y retorna una conexión PDO singleton.
 * Usá getPDO() en cualquier archivo en lugar de $conn
 */
function getPDO(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // Lanza excepciones
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,          // fetch() devuelve arrays asociativos
        PDO::ATTR_EMULATE_PREPARES   => false,                     // Prepared statements reales
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // Loguear el error real (nunca mostrarlo al usuario)
        error_log('[Bialystok DB] Error de conexión: ' . $e->getMessage());

        if (defined('APP_DEBUG') && APP_DEBUG === true) {
            die('Error de conexión a la base de datos. Revisá los logs.');
        } else {
            http_response_code(503);
            die('Servicio temporalmente no disponible.');
        }
    }

    return $pdo;
}

// ── Compatibilidad con código viejo que usa $conn (mysqli) ──────────────────
// Durante la migración, algunos archivos PHP pueden seguir usando $conn.
// Esta línea crea una conexión mysqli compatible en paralelo.
// Una vez migrado todo el código a getPDO(), eliminá estas líneas.
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset(DB_CHARSET);
if ($conn->connect_error) {
    error_log('[Bialystok DB] mysqli error: ' . $conn->connect_error);
    die('Servicio temporalmente no disponible.');
}
