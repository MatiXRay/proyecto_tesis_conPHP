<?php
/**
 * BIALYSTOK BREWING CO — Eliminar nota de cata
 * Reemplaza: eliminar_nota_cata.php
 *
 * Correcciones:
 *  - Credenciales hardcodeadas eliminadas (usaba usuario 'rocko' / 'Pepito11!')
 *  - SQL injection: "DELETE FROM notas_cata WHERE id = $nota_id" → prepared statement
 *  - Sin verificación de sesión → agregada
 *  - Sin verificación CSRF → agregada
 *  - Sin validación de tipo de $nota_id → getIntParam()
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

$nota_id = getIntParam('nota_id', 'POST');

if ($nota_id === null) {
    echo json_encode(['success' => false, 'message' => 'ID de nota inválido.']);
    exit;
}

try {
    $pdo = getPDO();

    // Verificar que la nota pertenece al usuario actual (o que es admin)
    // Esto previene que un usuario borre notas de otro
    if (!isAdmin()) {
        $stmt = $pdo->prepare("SELECT id_usuario FROM notas_cata WHERE id = ?");
        $stmt->execute([$nota_id]);
        $nota = $stmt->fetch();

        if (!$nota || (int)$nota['id_usuario'] !== (int)$_SESSION['id']) {
            echo json_encode(['success' => false, 'message' => 'No tenés permiso para eliminar esta nota.']);
            exit;
        }
    }

    $stmt = $pdo->prepare("DELETE FROM notas_cata WHERE id = ?");
    $stmt->execute([$nota_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Nota eliminada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se encontró la nota.']);
    }

} catch (PDOException $e) {
    error_log('[Bialystok] Error al eliminar nota de cata: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al eliminar la nota.']);
}
