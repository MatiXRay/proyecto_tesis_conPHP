<?php
/**
 * BIALYSTOK BREWING CO — Actualizar estado de cata en lotes
 * Reemplaza: actualizar_cata.php
 *
 * Corrección crítica:
 *  El código original hacía:
 *    $ids = $data['ids'];
 *    $sql = "UPDATE lotes_cerveza SET cata_habilitada = 0 WHERE id IN (" . implode(',', $ids) . ")";
 *
 *  Esto es SQL injection directa: un atacante podía mandar $ids = ["1) OR (1=1"]
 *  y destruir la tabla completa.
 *
 *  La solución: validar cada ID como entero positivo y usar placeholders.
 */

require_once 'auth.php';
requireRole([1, 2], 'inicio');   // Solo admin y operadores

require_once 'conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// ── Leer JSON ─────────────────────────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE || !isset($data['ids']) || !is_array($data['ids'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON inválido o ids faltante.']);
    exit;
}

// ── Validar cada ID: solo enteros positivos ───────────────────────────────────
$ids_validos = [];
foreach ($data['ids'] as $raw_id) {
    $id = filter_var($raw_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id !== false) {
        $ids_validos[] = (int) $id;
    }
}

if (empty($ids_validos)) {
    echo json_encode(['success' => false, 'message' => 'No hay IDs válidos para actualizar.']);
    exit;
}

// ── Query con placeholders para cada ID ──────────────────────────────────────
// PDO no soporta IN (?) directamente, así que generamos los placeholders.
try {
    $pdo          = getPDO();
    $placeholders = implode(',', array_fill(0, count($ids_validos), '?'));
    $stmt         = $pdo->prepare(
        "UPDATE lotes_cerveza SET cata_habilitada = 0 WHERE id IN ($placeholders)"
    );
    $stmt->execute($ids_validos);

    echo json_encode([
        'success' => true,
        'message' => $stmt->rowCount() . ' lote(s) actualizados.',
    ]);

} catch (PDOException $e) {
    error_log('[BRAUMEISTER] Error al actualizar cata: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al actualizar.']);
}
