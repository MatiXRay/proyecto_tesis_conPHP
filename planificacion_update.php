<?php
/**
 * BIALYSTOK BREWING CO — Planificación update handler
 * Acciones: get, crear, editar, mover_timeline, eliminar
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { http_response_code(403); echo json_encode(['success'=>false]); exit; }
require_once 'conexion.php';

header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) $data = $_POST;

$token = $data['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Token inválido.']);
    exit;
}

$accion = $data['accion'] ?? '';

try {
    $pdo = getPDO();

    switch ($accion) {

        case 'get':
            $id = filter_var($data['id']??0, FILTER_VALIDATE_INT);
            if (!$id) { echo json_encode(['success'=>false]); exit; }
            $stmt = $pdo->prepare(
                "SELECT p.*, COALESCE(p.duracion_dias, ec.duracion_dias, 21) AS estilo_duracion
                 FROM planificacion p LEFT JOIN estilos_cerveza ec ON p.estilo_id=ec.id WHERE p.id=?"
            );
            $stmt->execute([$id]);
            $lote = $stmt->fetch();
            if (!$lote) { echo json_encode(['success'=>false,'message'=>'No encontrado']); exit; }
            $stmt2 = $pdo->prepare("SELECT * FROM planificacion_tareas WHERE plan_id=? ORDER BY fecha_estimada, orden");
            $stmt2->execute([$id]);
            echo json_encode(['success'=>true,'lote'=>$lote,'tareas'=>$stmt2->fetchAll()]);
            break;

        case 'crear':
            $nombre = mb_substr(trim($data['nombre']??''), 0, 100);
            if (!$nombre) { echo json_encode(['success'=>false,'message'=>'Nombre requerido']); exit; }

            $estilo_id      = filter_var($data['estilo_id']??null, FILTER_VALIDATE_INT) ?: null;
            $fermentador_id = filter_var($data['fermentador_id']??null, FILTER_VALIDATE_INT) ?: null;
            $fecha_coccion  = !empty($data['fecha_coccion'])  ? $data['fecha_coccion']  : null;
            $fecha_fin      = !empty($data['fecha_fin'])      ? $data['fecha_fin']      : null;
            $duracion_dias  = filter_var($data['duracion_dias']??null, FILTER_VALIDATE_INT) ?: null;
            $notas          = mb_substr(trim($data['notas']??''), 0, 2000);
            $color          = preg_match('/^#[0-9a-fA-F]{6}$/', $data['color']??'') ? $data['color'] : null;

            // Calcular fecha_fin si no viene
            if (!$fecha_fin && $fecha_coccion && $duracion_dias) {
                $d = new DateTime($fecha_coccion);
                $d->modify("+$duracion_dias days");
                $fecha_fin = $d->format('Y-m-d');
            }

            $stmt = $pdo->prepare(
                "INSERT INTO planificacion (nombre,estilo_id,fecha_coccion,fecha_fin,duracion_dias,fermentador_id,notas,color,estado,orden)
                 VALUES (?,?,?,?,?,?,?,?,'planificado',0)"
            );
            $stmt->execute([$nombre,$estilo_id,$fecha_coccion,$fecha_fin,$duracion_dias,$fermentador_id,$notas,$color]);
            $pid = (int)$pdo->lastInsertId();

            _insertarTareas($pdo, $pid, $data['tareas'] ?? []);
            echo json_encode(['success'=>true,'id'=>$pid]);
            break;

        case 'editar':
            $id = filter_var($data['id']??0, FILTER_VALIDATE_INT);
            if (!$id) { echo json_encode(['success'=>false]); exit; }

            $nombre         = mb_substr(trim($data['nombre']??''), 0, 100);
            $estilo_id      = filter_var($data['estilo_id']??null, FILTER_VALIDATE_INT) ?: null;
            $fermentador_id = filter_var($data['fermentador_id']??null, FILTER_VALIDATE_INT) ?: null;
            $fecha_coccion  = !empty($data['fecha_coccion'])  ? $data['fecha_coccion']  : null;
            $fecha_fin      = !empty($data['fecha_fin'])      ? $data['fecha_fin']      : null;
            $duracion_dias  = filter_var($data['duracion_dias']??null, FILTER_VALIDATE_INT) ?: null;
            $notas          = mb_substr(trim($data['notas']??''), 0, 2000);
            $color          = preg_match('/^#[0-9a-fA-F]{6}$/', $data['color']??'') ? $data['color'] : null;

            if (!$fecha_fin && $fecha_coccion && $duracion_dias) {
                $d = new DateTime($fecha_coccion);
                $d->modify("+$duracion_dias days");
                $fecha_fin = $d->format('Y-m-d');
            }

            $stmt = $pdo->prepare(
                "UPDATE planificacion SET nombre=?,estilo_id=?,fecha_coccion=?,fecha_fin=?,duracion_dias=?,fermentador_id=?,notas=?,color=? WHERE id=?"
            );
            $stmt->execute([$nombre,$estilo_id,$fecha_coccion,$fecha_fin,$duracion_dias,$fermentador_id,$notas,$color,$id]);

            // Reemplazar tareas
            $pdo->prepare("DELETE FROM planificacion_tareas WHERE plan_id=?")->execute([$id]);
            _insertarTareas($pdo, $id, $data['tareas'] ?? []);
            echo json_encode(['success'=>true]);
            break;

        case 'mover_timeline':
            $id             = filter_var($data['id']??0, FILTER_VALIDATE_INT);
            $fecha_coccion  = !empty($data['fecha_coccion']) ? $data['fecha_coccion'] : null;
            $fermentador_id = filter_var($data['fermentador_id']??null, FILTER_VALIDATE_INT) ?: null;
            $duracion_dias  = filter_var($data['duracion_dias']??null, FILTER_VALIDATE_INT) ?: null;

            if (!$id) { echo json_encode(['success'=>false]); exit; }

            $fecha_fin = null;
            if ($fecha_coccion && $duracion_dias) {
                $d = new DateTime($fecha_coccion);
                $d->modify("+$duracion_dias days");
                $fecha_fin = $d->format('Y-m-d');
            }

            $stmt = $pdo->prepare(
                "UPDATE planificacion SET fecha_coccion=?,fecha_fin=?,duracion_dias=?,fermentador_id=? WHERE id=?"
            );
            $stmt->execute([$fecha_coccion,$fecha_fin,$duracion_dias,$fermentador_id,$id]);
            echo json_encode(['success'=>true]);
            break;

        case 'agregar_tarea':
            $id     = filter_var($data['id']??0, FILTER_VALIDATE_INT);
            $nombre = mb_substr(trim($data['nombre']??''), 0, 150);
            $fecha  = !empty($data['fecha']) ? $data['fecha'] : null;
            if (!$id || !$nombre) { echo json_encode(['success'=>false]); exit; }
            $stmt = $pdo->prepare("INSERT INTO planificacion_tareas (plan_id, nombre, fecha_estimada, orden) VALUES (?,?,?,0)");
            $stmt->execute([$id, $nombre, $fecha]);
            echo json_encode(['success'=>true, 'tarea_id'=>(int)$pdo->lastInsertId()]);
            break;

        case 'eliminar':
            $id = filter_var($data['id']??0, FILTER_VALIDATE_INT);
            if (!$id) { echo json_encode(['success'=>false]); exit; }
            $pdo->prepare("DELETE FROM planificacion WHERE id=?")->execute([$id]);
            echo json_encode(['success'=>true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'Acción desconocida']);
    }

} catch (PDOException $ex) {
    error_log('[plan_update] '.$ex->getMessage());
    echo json_encode(['success'=>false,'message'=>'Error de base de datos.']);
}

function _insertarTareas(PDO $pdo, int $pid, array $tareas): void {
    if (!$tareas) return;
    $stmt = $pdo->prepare("INSERT INTO planificacion_tareas (plan_id,nombre,fecha_estimada,orden) VALUES (?,?,?,?)");
    foreach ($tareas as $i => $t) {
        $nombre = mb_substr(trim((string)($t['nombre']??'')), 0, 150);
        $fecha  = !empty($t['fecha']) ? $t['fecha'] : null;
        if ($nombre) $stmt->execute([$pid, $nombre, $fecha, $i]);
    }
}
