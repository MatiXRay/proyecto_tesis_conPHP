<?php
/**
 * BIALYSTOK BREWING CO — Logout
 * Reemplaza: logout.php
 *
 * Destruye correctamente todos los datos de sesión.
 */

session_name(defined('SESSION_NAME') ? SESSION_NAME : 'bco_session');
session_start();

// Limpiar variables de sesión
$_SESSION = [];

// Eliminar cookie de sesión
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

header('Location: login');
exit;
