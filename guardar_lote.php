<?php
/**
 * BIALYSTOK BREWING CO — Guardar nuevo lote (POST handler)
 * Reemplaza: guardar_lote.php
 *
 * Correcciones:
 *  - Auth via auth.php
 *  - CSRF verificado
 *  - Inputs validados: nombre (string), estilo (int), fecha (date válida)
 *  - PDO prepared statement
 *  - Errores internos logueados, no mostrados
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }

require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: anadir_lote');
    exit;
}

verifyCsrf();

// ── Validar inputs ────────────────────────────────────────────────────────────
$nombre   = getStringParam('nombre', 'POST', 100);
$idEstilo = getIntParam('estilo', 'POST');
$fecha    = getStringParam('fecha', 'POST', 10);

$errores = [];
if ($nombre === '') $errores[] = 'El número de lote es obligatorio.';
if ($idEstilo === null) $errores[] = 'Seleccioná un estilo de cerveza.';

// Validar formato de fecha
$fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
if (!$fechaObj || $fechaObj->format('Y-m-d') !== $fecha) {
    $errores[] = 'La fecha de elaboración no es válida.';
}

if ($errores) {
    // Volver al formulario con error (en producción usarías flash messages)
    $msg = implode(' ', $errores);
    header('Location: anadir_lote?error=' . urlencode($msg));
    exit;
}

// ── Insertar ─────────────────────────────────────────────────────────────────
try {
    $pdo  = getPDO();

    // Verificar que el estilo existe
    $stmt = $pdo->prepare("SELECT id FROM estilos_cerveza WHERE id = ?");
    $stmt->execute([$idEstilo]);
    if (!$stmt->fetch()) {
        header('Location: anadir_lote?error=' . urlencode('El estilo seleccionado no existe.'));
        exit;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO lotes_cerveza (estilo_id, fecha_elaboracion, numero_lote) VALUES (?, ?, ?)"
    );
    $stmt->execute([$idEstilo, $fecha, strtoupper($nombre)]);
    $idLote = (int) $pdo->lastInsertId();

    header("Location: anadir_detalles_lote?id_lote=$idLote");
    exit;

} catch (PDOException $ex) {
    error_log('[BRAUMEISTER guardar_lote] ' . $ex->getMessage());
    header('Location: anadir_lote?error=' . urlencode('Error al guardar el lote. Intentá de nuevo.'));
    exit;
}
