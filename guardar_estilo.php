<?php
/**
 * BIALYSTOK BREWING CO — Guardar nuevo estilo / receta
 * Reemplaza: guardar_estilo.php
 *
 * Correcciones:
 *  - Sin auth → requireLogin()
 *  - Sin CSRF → verifyCsrf()
 *  - Todos los $_POST sin sanitizar → getStringParam() / intval()
 *  - mysqli con bind_param → PDO prepared statements
 *  - Agrega soporte para color y duracion_dias
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: recetas');
    exit;
}

verifyCsrf();

// ── Leer campos ───────────────────────────────────────────────────────────────
$nombreEstilo = getStringParam('estilo',      'POST', 100);
$descripcion  = getStringParam('descripcion', 'POST', 500);
$duracion_dias = max(1, (int)($_POST['duracion_dias'] ?? 21));
$color = $_POST['color'] ?? '#4a8f4a';
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) $color = '#4a8f4a';

// Parámetros vitales
$og          = getStringParam('og',          'POST', 10);
$fg          = getStringParam('fg',          'POST', 10);
$ibuEsperado = getStringParam('ibuEsperado', 'POST', 10);
$abvEsperado = getStringParam('abvEsperado', 'POST', 10);
$carbLevel   = getStringParam('carbLevel',   'POST', 10);

// H2O
$ca_mas_2    = (int)($_POST['ca_mas_2']    ?? 0);
$mg_mas_2    = (int)($_POST['mg_mas_2']    ?? 0);
$na_mas_2    = (int)($_POST['na_mas_2']    ?? 0);
$cl_menos    = (int)($_POST['cl_menos']    ?? 0);
$so4_menos_2 = (int)($_POST['so4_menos_2'] ?? 0);

// Tratamiento MASH
$total_agua_mash     = (float)($_POST['total_agua_mash']     ?? 0);
$porcentaje_ro_mash  = (float)($_POST['porcentaje_ro_mash']  ?? 0);
$temperatura_mash    = (float)($_POST['temperatura_mash']    ?? 0);
$ph_mashh2o          = getStringParam('ph_mashh2o', 'POST', 10);
$fosforico_mash      = (float)($_POST['fosforico_mash']      ?? 0);
$caso4_mash          = (float)($_POST['caso4_mash']          ?? 0);
$cacl2_mash          = (float)($_POST['cacl2_mash']          ?? 0);
$mgcl_mash           = (float)($_POST['mgcl_mash']           ?? 0);
$otro_mash           = (float)($_POST['otro_mash']           ?? 0);
$fosforico_h2o_mash  = (float)($_POST['fosforico_h2o_mash']  ?? 0);

// Tratamiento SPARGE
$total_agua_sparge    = (float)($_POST['total_agua_sparge']    ?? 0);
$porcentaje_ro_sparge = (float)($_POST['porcentaje_ro_sparge'] ?? 0);
$temperatura_sparge   = (float)($_POST['temperatura_sparge']   ?? 0);
$ph_spargeh2o         = getStringParam('ph_spargeh2o', 'POST', 10);
$fosforico_sparge     = (float)($_POST['fosforico_sparge']     ?? 0);
$caso4_sparge         = (float)($_POST['caso4_sparge']         ?? 0);
$cacl2_sparge         = (float)($_POST['cacl2_sparge']         ?? 0);
$mgcl_sparge          = (float)($_POST['mgcl_sparge']          ?? 0);
$otro_sparge          = (float)($_POST['otro_sparge']          ?? 0);

if (!$nombreEstilo) {
    header('Location: anadir_receta?error=nombre_requerido');
    exit;
}

try {
    $pdo = getPDO();
    $pdo->beginTransaction();

    // ── 1. Insertar estilo ────────────────────────────────────────────────────
    $stmt = $pdo->prepare(
        "INSERT INTO estilos_cerveza (nombre, descripcion, duracion_dias, color)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$nombreEstilo, $descripcion, $duracion_dias, $color]);
    $id_estilo = (int) $pdo->lastInsertId();

    // ── 2. Insertar receta base ───────────────────────────────────────────────
    $stmt = $pdo->prepare(
        "INSERT INTO recetas_estilos (
            estilo_id, og, fg, ibu, abv,
            ca_mas_2, mg_mas_2, na_mas_2, cl_menos, so04_menos_2,
            total_agua_mash, porcentaje_ro_mash, temperatura_mash, ph_mash,
            caso4_mash, cacl2_mash, mgcl_mash, fosforico_mash, otro_mash, fosforico_h2o_mash,
            total_agua_sparge, porcentaje_ro_sparge, temperatura_sparge, ph_sparge,
            caso4_sparge, cacl2_sparge, mgcl_sparge, fosforico_sparge, otro_sparge,
            carb_level
         ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    $stmt->execute([
        $id_estilo, $og, $fg, $ibuEsperado, $abvEsperado,
        $ca_mas_2, $mg_mas_2, $na_mas_2, $cl_menos, $so4_menos_2,
        $total_agua_mash, $porcentaje_ro_mash, $temperatura_mash, $ph_mashh2o,
        $caso4_mash, $cacl2_mash, $mgcl_mash, $fosforico_mash, $otro_mash, $fosforico_h2o_mash,
        $total_agua_sparge, $porcentaje_ro_sparge, $temperatura_sparge, $ph_spargeh2o,
        $caso4_sparge, $cacl2_sparge, $mgcl_sparge, $fosforico_sparge, $otro_sparge,
        $carbLevel
    ]);
    $id_receta = (int) $pdo->lastInsertId();

    // ── 3. Maltas ─────────────────────────────────────────────────────────────
    if (!empty($_POST['malta']) && is_array($_POST['malta'])) {
        $stmt = $pdo->prepare(
            "INSERT INTO recetasmalta (malta_id, cantidad, tiempo, id_receta) VALUES (?,?,?,?)"
        );
        foreach ($_POST['malta'] as $i => $malta_id) {
            $stmt->execute([
                (int)$malta_id,
                $_POST['cantidad'][$i] ?? 0,
                $_POST['tiempo'][$i]   ?? '',
                $id_receta
            ]);
        }
    }

    // ── 4. Lúpulos ────────────────────────────────────────────────────────────
    if (!empty($_POST['lupulo']) && is_array($_POST['lupulo'])) {
        $stmt = $pdo->prepare(
            "INSERT INTO recetaslupulo (lupulo_id, cantidad, tiempo, ibu, id_receta) VALUES (?,?,?,?,?)"
        );
        foreach ($_POST['lupulo'] as $i => $lupulo_id) {
            $stmt->execute([
                (int)$lupulo_id,
                $_POST['cantidad_lupulo'][$i] ?? 0,
                $_POST['tiempo_lupulo'][$i]   ?? '',
                $_POST['ibu'][$i]             ?? 0,
                $id_receta
            ]);
        }
    }

    // ── 5. Levadura ───────────────────────────────────────────────────────────
    if (!empty($_POST['cepa'])) {
        $stmt = $pdo->prepare(
            "INSERT INTO recetaslevadura
             (temp_inoculacion, tasa_inoculacion, viabilidad, kilos_biomasa, cepa_id, oxigenacion, id_receta)
             VALUES (?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $_POST['tempInoc']    ?? 0,
            $_POST['tasaInoc']    ?? 0,
            $_POST['viabilidad']  ?? 0,
            $_POST['biomasa']     ?? 0,
            (int)($_POST['cepa'] ?? 0),
            $_POST['oxigenacion'] ?? 0,
            $id_receta
        ]);
    }

    $pdo->commit();
    header('Location: recetas');

} catch (PDOException $ex) {
    $pdo->rollBack();
    error_log('[BRAUMEISTER guardar_estilo] ' . $ex->getMessage());
    header('Location: anadir_receta?error=error_db');
}
