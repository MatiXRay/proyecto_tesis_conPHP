<?php
/**
 * BIALYSTOK BREWING CO — Configuración update handler
 * Acciones: duracion_estilo, creacion_usuarios
 */

require_once 'auth.php';
requireLogin();
requireRole([1], 'inicio'); // Solo admins

require_once 'conexion.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

verifyCsrf();

$accion = getStringParam('accion', 'POST', 50);

try {
    $pdo = getPDO();

    switch ($accion) {

        // ── Guardar duración estimada de un estilo ─────────
        case 'duracion_estilo':
            $estilo_id = filter_var($_POST['estilo_id'] ?? 0, FILTER_VALIDATE_INT);
            $duracion  = filter_var($_POST['duracion']  ?? 0, FILTER_VALIDATE_INT);

            if (!$estilo_id || !$duracion || $duracion < 1 || $duracion > 365) {
                echo json_encode(['success' => false, 'message' => 'Datos inválidos.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE estilos_cerveza SET duracion_dias = ? WHERE id = ?");
            $stmt->execute([$duracion, $estilo_id]);
            echo json_encode(['success' => true]);
            break;

        // ── Toggle creación de usuarios ────────────────────
        case 'creacion_usuarios':
            $estado = filter_var($_POST['estado'] ?? 0, FILTER_VALIDATE_INT) ? 1 : 0;

            $stmt = $pdo->prepare("UPDATE configuraciones SET creacion_usuarios = ? WHERE id = 1");
            $stmt->execute([$estado]);
            echo json_encode(['success' => true]);
            break;

        // ── Alertas ───────────────────────────────────────────────────────────────
        case 'nueva_alerta':
            $desc = mb_substr(trim($_POST['descripcion'] ?? ''), 0, 200);
            $per  = max(1, (int)($_POST['periodicidad'] ?? 30));
            if (!$desc) { echo json_encode(['success'=>false,'message'=>'Descripción requerida.']); exit; }
            $pdo->prepare("INSERT INTO alertas (descripcion, periodicidad_dias) VALUES (?,?)")->execute([$desc, $per]);
            echo json_encode(['success'=>true]);
            break;

        case 'editar_alerta':
            $id   = (int)($_POST['alerta_id'] ?? 0);
            $desc = mb_substr(trim($_POST['descripcion'] ?? ''), 0, 200);
            $per  = max(1, (int)($_POST['periodicidad'] ?? 30));
            if (!$id || !$desc) { echo json_encode(['success'=>false]); exit; }
            $pdo->prepare("UPDATE alertas SET descripcion=?, periodicidad_dias=? WHERE id=?")->execute([$desc, $per, $id]);
            echo json_encode(['success'=>true]);
            break;

        case 'fecha_alerta':
            $id    = (int)($_POST['alerta_id'] ?? 0);
            $fecha = $_POST['ultima_vez'] ?? '';
            if (!$id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) { echo json_encode(['success'=>false]); exit; }
            $pdo->prepare("UPDATE alertas SET ultima_vez=? WHERE id=?")->execute([$fecha, $id]);
            echo json_encode(['success'=>true]);
            break;

        case 'eliminar_alerta':
            $id = (int)($_POST['alerta_id'] ?? 0);
            if (!$id) { echo json_encode(['success'=>false]); exit; }
            $pdo->prepare("DELETE FROM alertas WHERE id=?")->execute([$id]);
            echo json_encode(['success'=>true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción desconocida.']);
    }

} catch (PDOException $ex) {
    error_log('[BRAUMEISTER config_update] ' . $ex->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos.']);
}
