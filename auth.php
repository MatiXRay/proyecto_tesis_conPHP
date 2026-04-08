<?php
/**
 * BIALYSTOK BREWING CO — Helpers de autenticación y seguridad
 * Archivo nuevo: auth.php
 *
 * Incluí este archivo al inicio de cada página protegida:
 *   require_once 'auth.php';
 *   requireLogin();           // redirige a login si no hay sesión
 *   requireRole([1, 2]);      // redirige si el rol no está en la lista
 */

// ── Configuración de sesión segura ──────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    // Nombre de sesión personalizado (no revela tecnología)
    session_name(defined('SESSION_NAME') ? SESSION_NAME : 'bco_session');

    // Cookies de sesión seguras
    session_set_cookie_params([
        'lifetime' => 0,                        // Cookie de sesión (se borra al cerrar el navegador)
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']), // Solo HTTPS si está disponible
        'httponly' => true,                     // JavaScript no puede leer la cookie
        'samesite' => 'Strict',                 // Protección CSRF básica
    ]);

    session_start();
}

// ── Timeout por inactividad ──────────────────────────────────────────────────
function checkSessionTimeout(): void {
    $lifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 3600;

    if (isset($_SESSION['last_activity'])) {
        if (time() - $_SESSION['last_activity'] > $lifetime) {
            session_unset();
            session_destroy();
            header('Location: login?timeout=1');
            exit;
        }
    }
    $_SESSION['last_activity'] = time();
}

// ── Verificar login ──────────────────────────────────────────────────────────
function requireLogin(): void {
    checkSessionTimeout();

    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header('Location: login');
        exit;
    }
}

// ── Verificar rol ────────────────────────────────────────────────────────────
/**
 * @param int[] $allowed_roles  Array de IDs de rol permitidos. Ej: [1, 2]
 * @param string $redirect      URL de redirección si el rol no es permitido
 */
function requireRole(array $allowed_roles, string $redirect = 'inicio'): void {
    requireLogin();

    if (!in_array($_SESSION['rol_id'] ?? 0, $allowed_roles, true)) {
        header("Location: $redirect");
        exit;
    }
}

// ── Es admin ─────────────────────────────────────────────────────────────────
function isAdmin(): bool {
    return ($_SESSION['rol_id'] ?? 0) === 1;
}

// ── Es taster (solo panel cata) ──────────────────────────────────────────────
function isTaster(): bool {
    return ($_SESSION['rol_id'] ?? 0) === 3;
}

// ── Generar token CSRF ───────────────────────────────────────────────────────
function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// ── Verificar token CSRF ─────────────────────────────────────────────────────
function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Token de seguridad inválido.']));
    }
}

// ── Escapar output (XSS) ─────────────────────────────────────────────────────
/**
 * Siempre usá e() para mostrar datos de la DB o del usuario en HTML.
 * En lugar de: echo $row['comentario'];
 * Usá:         echo e($row['comentario']);
 */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ── Sanitizar entero de GET/POST ─────────────────────────────────────────────
/**
 * Obtiene un entero positivo de $_GET o $_POST.
 * Retorna null si no existe o no es un entero válido > 0.
 * 
 * Usá esto en lugar de: $id = $_GET['id'];
 * Usá:                   $id = getIntParam('id');  // retorna null si inválido
 */
function getIntParam(string $key, string $source = 'GET'): ?int {
    $value = ($source === 'POST') ? ($_POST[$key] ?? null) : ($_GET[$key] ?? null);

    if ($value === null || $value === '') {
        return null;
    }

    $int = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return ($int !== false) ? (int) $int : null;
}

// ── Sanitizar string de GET/POST ─────────────────────────────────────────────
/**
 * Obtiene un string limpio (trimmed, max 500 chars por defecto).
 */
function getStringParam(string $key, string $source = 'GET', int $maxLen = 500): string {
    $value = ($source === 'POST') ? ($_POST[$key] ?? '') : ($_GET[$key] ?? '');
    return mb_substr(trim((string) $value), 0, $maxLen);
}

// ── Input CSRF field (para forms HTML) ──────────────────────────────────────
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(getCsrfToken()) . '">';
}
