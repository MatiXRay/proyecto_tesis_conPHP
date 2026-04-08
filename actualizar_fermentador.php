<?php
/**
 * BIALYSTOK BREWING CO — Actualizar limpieza de fermentador
 * Reemplaza: actualizar_fermentador.php
 *
 * Correcciones:
 *  - Credenciales hardcodeadas eliminadas (usaba usuario 'rocko' / 'Pepito11!')
 *  - Sin verificación de sesión → agregada
 *  - $id sin validar desde JSON → validado con filter_var
 *  - Campos booleanos sin sanitizar → cast explícito
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

// ── Leer y validar JSON del body ──────────────────────────────────────────────
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON inválido.']);
    exit;
}

// Validar ID
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    echo json_encode(['success' => false, 'message' => 'ID de fermentador inválido.']);
    exit;
}

// Castear booleanos de forma segura — si viene true, registra hoy; si false, null
$hoy = date('Y-m-d');
$limpAlcalina  = !empty($data['limpAlcalina'])  ? $hoy : null;
$limpAcida     = !empty($data['limpAcida'])     ? $hoy : null;
$limpOxidativa = !empty($data['limpOxidativa']) ? $hoy : null;
$limpExterior  = !empty($data['limpExterior'])  ? $hoy : null;

// ── Actualizar ────────────────────────────────────────────────────────────────
try {
    $pdo  = getPDO();
    $stmt = $pdo->prepare("
        UPDATE fermentadores
        SET limp_alcalina_date  = ?,
            limp_acida_date     = ?,
            limp_oxidativa_date = ?,
            limp_exterior_date  = ?
        WHERE id = ?
    ");
    $stmt->execute([$limpAlcalina, $limpAcida, $limpOxidativa, $limpExterior, $id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Fermentador actualizado.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Fermentador no encontrado.']);
    }

} catch (PDOException $e) {
    error_log('[Bialystok] Error al actualizar fermentador: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al actualizar.']);
}
