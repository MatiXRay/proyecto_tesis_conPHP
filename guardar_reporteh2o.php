<?php
/**
 * BIALYSTOK BREWING CO — Guardar reporte de agua
 * Reemplaza: guardar_reporteh2o.php
 *
 * Correcciones:
 *  - Sin auth → requireLogin()
 *  - Sin CSRF → verifyCsrf()
 *  - SQL injection directa en INSERT → prepared statement PDO
 *  - Todos los $_POST sin sanitizar → getStringParam() / intval()
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: reportes_agua');
    exit;
}

verifyCsrf();

$fecha       = getStringParam('fecha',       'POST', 10);
$laboratorio = getStringParam('laboratorio', 'POST', 150);
$origen      = getStringParam('origen',      'POST', 20);

if (!in_array($origen, ['RED', 'OSMOSIS'], true)) $origen = 'RED';

$ph          = getStringParam('ph',          'POST', 10);
$sulfato     = intval($_POST['sulfato']     ?? 0);
$nitrato     = intval($_POST['nitrato']     ?? 0);
$nitrito     = intval($_POST['nitrito']     ?? 0);
$dureza      = intval($_POST['dureza']      ?? 0);
$calcio      = intval($_POST['calcio']      ?? 0);
$magnesio    = intval($_POST['magnesio']    ?? 0);
$cloruro     = intval($_POST['cloruro']     ?? 0);
$carbonato   = intval($_POST['carbonato']   ?? 0);
$bicarbonato = intval($_POST['bicarbonato'] ?? 0);
$sodio       = intval($_POST['sodio']       ?? 0);
$alcalinidad = intval($_POST['alcalinidad'] ?? 0);

if (!$fecha || !$laboratorio) {
    header('Location: anadir_reporte_agua?error=campos_requeridos');
    exit;
}

try {
    $pdo  = getPDO();
    $stmt = $pdo->prepare(
        "INSERT INTO reportesagua
         (fecha, laboratorio, origen, ph, sulfato, nitrato, nitrito, dureza,
          calcio, magnesio, cloruro, carbonato, bicarbonato, sodio, alcalinidad)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    $stmt->execute([
        $fecha, $laboratorio, $origen, $ph, $sulfato, $nitrato, $nitrito, $dureza,
        $calcio, $magnesio, $cloruro, $carbonato, $bicarbonato, $sodio, $alcalinidad
    ]);
    header('Location: reportes_agua');
} catch (PDOException $ex) {
    error_log('[BRAUMEISTER guardar_h2o] ' . $ex->getMessage());
    header('Location: anadir_reporte_agua?error=error_db');
}
