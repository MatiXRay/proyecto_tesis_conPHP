<?php
/**
 * BIALYSTOK BREWING CO — Guardar usuario (POST handler)
 * Usado por nuevo_usuario.php (admin) y nuevo_taster.php (público)
 *
 * Correcciones:
 *  - Sin auth ni validación → validación completa
 *  - $rol_id sin restricción → taster forzado si viene de nuevo_taster
 *  - Sin hash de contraseña seguro → password_hash
 *  - SQL injection imposible (prepared statement PDO)
 */

if (session_status() === PHP_SESSION_NONE) session_start();

require_once 'conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
    exit;
}

// Determinar si viene de admin o de registro público
$es_admin = isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && (int)($_SESSION['rol_id'] ?? 0) === 1;

// Leer campos
$nombre   = mb_substr(trim($_POST['nombre']   ?? ''), 0, 100);
$apellido = mb_substr(trim($_POST['apellido'] ?? ''), 0, 100);
$username = mb_substr(trim($_POST['username'] ?? ''), 0, 50);
$mail     = mb_substr(trim($_POST['mail']     ?? ''), 0, 150);
$telefono = mb_substr(trim($_POST['telefono'] ?? ''), 0, 30);
$password = $_POST['password'] ?? '';
$rol_id   = (int)($_POST['rol_id'] ?? 3);

// Si no es admin, forzar rol taster
if (!$es_admin) $rol_id = 3;

// Validaciones
if (!$nombre || !$apellido || !$username || !$mail || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
    exit;
}
if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'El email no es válido.']);
    exit;
}
if (strlen($password) < 8) {
    echo json_encode(['status' => 'error', 'message' => 'La contraseña debe tener al menos 8 caracteres.']);
    exit;
}
if (!in_array($rol_id, [1, 2, 3], true)) $rol_id = 3;

// Si es registro público, verificar que creacion_usuarios está habilitada
if (!$es_admin) {
    try {
        $pdo = getPDO();
        $cfg = $pdo->query("SELECT creacion_usuarios FROM configuraciones WHERE id = 1 LIMIT 1")->fetch();
        if (!$cfg || !(int)$cfg['creacion_usuarios']) {
            echo json_encode(['status' => 'error', 'message' => 'El registro está deshabilitado.']);
            exit;
        }
    } catch (PDOException $ex) {
        echo json_encode(['status' => 'error', 'message' => 'Error interno.']);
        exit;
    }
}

// Hash de contraseña
$hashed = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo  = getPDO();
    $stmt = $pdo->prepare(
        "INSERT INTO users (nombre, apellido, mail, telefono, rol_id, username, password)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$nombre, $apellido, $mail, $telefono, $rol_id, $username, $hashed]);
    echo json_encode(['status' => 'success', 'message' => 'Usuario creado con éxito.']);

} catch (PDOException $ex) {
    if ($ex->getCode() == 23000) {
        echo json_encode(['status' => 'error', 'message' => 'El email o username ya existe.']);
    } else {
        error_log('[BRAUMEISTER guardar_usuario] ' . $ex->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar el usuario.']);
    }
}
