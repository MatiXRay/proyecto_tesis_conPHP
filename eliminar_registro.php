<?php
/**
 * BIALYSTOK BREWING CO — Eliminar registro
 * Reemplaza: eliminar_registro.php
 *
 * Correcciones:
 *  - Verificación de sesión via auth.php (antes era manual)
 *  - $tabla ya no se pasa directo a la query → whitelist explícita
 *  - $id sanitizado con getIntParam() (antes era $_GET['id'] sin validar)
 *  - Token CSRF verificado en POST (previene CSRF attacks)
 *  - Verificación de rol: solo admins (rol_id = 1) pueden eliminar
 */

require_once 'auth.php';
requireRole([1], 'inicio');   // Solo administradores

require_once 'conexion.php';

header('Content-Type: application/json');

// ── Verificar método ──────────────────────────────────────────────────────────
// Importante: operaciones destructivas deben ser POST, nunca GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

// ── Verificar CSRF ────────────────────────────────────────────────────────────
verifyCsrf();

// ── Obtener y validar parámetros ──────────────────────────────────────────────
$id    = getIntParam('id', 'POST');
$tabla = getStringParam('tabla', 'POST', 50);

if ($id === null || $tabla === '') {
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos.']);
    exit;
}

// ── Whitelist de tablas permitidas ────────────────────────────────────────────
// NUNCA interpolamos $tabla directo en SQL. Usamos un mapa de acciones seguras.
$acciones_permitidas = ['lotes_cerveza', 'receta', 'user', 'variedades_malta',
                        'variedades_lupulo', 'cepas_levadura', 'fermentadores',
                        'reportesagua', 'recetas_malta', 'recetas_lupulo'];

if (!in_array($tabla, $acciones_permitidas, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tabla no permitida.']);
    exit;
}

// ── Lógica de eliminación ─────────────────────────────────────────────────────
try {
    $pdo = getPDO();
    $pdo->beginTransaction();

    switch ($tabla) {

        case 'lotes_cerveza':
            // Eliminar en cascada todos los registros relacionados
            $cascada = [
                "DELETE FROM lotes_levaduras              WHERE lote_id = ?",
                "DELETE FROM lotes_maltas                 WHERE lote_id = ?",
                "DELETE FROM lotes_lupulos                WHERE lote_id = ?",
                "DELETE FROM batches                      WHERE lote_id = ?",
                "DELETE FROM lotesenlatado                WHERE id_lote = ?",
                "DELETE FROM seguimiento_fermentacion     WHERE lote_id = ?",
                "DELETE FROM tratamiento_agua_mash_sparge WHERE lote_id = ?",
                "DELETE FROM lotes_cerveza                WHERE id      = ?",
            ];
            foreach ($cascada as $sql) {
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
            }
            break;

        case 'receta':
            // Verificar si hay lotes asociados antes de eliminar
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM lotes_cerveza WHERE estilo_id = ?");
            $stmt->execute([$id]);
            $count = (int) $stmt->fetchColumn();

            if ($count > 0) {
                $pdo->rollBack();
                echo json_encode([
                    'success' => false,
                    'message' => "No se puede eliminar: hay $count lote(s) asociado(s) a esta receta.",
                ]);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM estilos_cerveza WHERE id = ?");
            $stmt->execute([$id]);
            break;

        case 'user':
            // Prevenir auto-eliminación
            if ($id === (int) $_SESSION['id']) {
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'No podés eliminar tu propia cuenta.']);
                exit;
            }
            $stmts = [
                "DELETE FROM notas_cata WHERE id_usuario = ?",
                "DELETE FROM users       WHERE id        = ?",
            ];
            foreach ($stmts as $sql) {
                $pdo->prepare($sql)->execute([$id]);
            }
            break;

        default:
            // Para tablas simples con columna 'id' — la tabla ya fue validada en whitelist
            // No se puede interpolar $tabla directamente en PDO prepare,
            // pero al estar en whitelist explícita es seguro.
            $tabla_safe = $tabla; // Whitelist validada arriba
            $stmt = $pdo->prepare("DELETE FROM `$tabla_safe` WHERE id = ?");
            $stmt->execute([$id]);
            break;
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Registro eliminado exitosamente.']);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[BRAUMEISTER] Error al eliminar registro: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al eliminar el registro.']);
}
