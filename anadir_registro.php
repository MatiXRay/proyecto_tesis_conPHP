<?php
/**
 * BIALYSTOK BREWING CO — Agregar registro genérico (Malta / Lúpulo / Levadura)
 * Reemplaza: anadir_registro.php
 *
 * Correcciones:
 *  - Sin auth → requireLogin()
 *  - Sin CSRF → verifyCsrf()
 *  - $tabla interpolada directo en SQL → whitelist explícita
 *  - $nombre / $marca sin sanitizar → getStringParam()
 *  - SQL injection en INSERT → prepared statement PDO
 */

require_once 'auth.php';
requireLogin();
require_once 'conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

verifyCsrf();

$tabla  = getStringParam('tabla',  'POST', 50);
$nombre = getStringParam('nombre', 'POST', 200);
$marca  = getStringParam('marca',  'POST', 200);

if (!$nombre || !$marca) {
    echo json_encode(['success' => false, 'message' => 'Nombre y marca son requeridos.']);
    exit;
}

// Whitelist de tablas permitidas
$tablas_permitidas = ['variedades_malta', 'variedades_lupulo', 'cepas_levadura', 'fermentadores'];
if (!in_array($tabla, $tablas_permitidas, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tabla no permitida.']);
    exit;
}

// Capitalizar
$nombre = ucwords(strtolower($nombre));
$marca  = ucwords(strtolower($marca));

try {
    $pdo = getPDO();

    if ($tabla === 'cepas_levadura') {
        // Levadura usa columna 'cepa' en lugar de 'nombre'
        $stmt = $pdo->prepare("INSERT INTO cepas_levadura (cepa, marca) VALUES (?, ?)");
    } elseif ($tabla === 'fermentadores') {
        // Fermentadores usa 'nombre' y 'capacidad'
        $stmt = $pdo->prepare("INSERT INTO fermentadores (nombre, capacidad) VALUES (?, ?)");
    } else {
        $stmt = $pdo->prepare("INSERT INTO `$tabla` (nombre, marca) VALUES (?, ?)");
    }

    $stmt->execute([$nombre, $marca]);
    echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);

} catch (PDOException $ex) {
    error_log('[BRAUMEISTER anadir_registro] ' . $ex->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al guardar.']);
}
