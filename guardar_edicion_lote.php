<?php
/**
 * BIALYSTOK BREWING CO — Guardar edición de lote completo
 * Estrategia: UPDATE para datos únicos, DELETE+INSERT para colecciones
 */

require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: lotes'); exit; }
verifyCsrf();

$id_lote = getIntParam('loteid', 'POST');
if (!$id_lote) { header('Location: lotes'); exit; }

function p(string $k, bool $int=false): mixed {
    $v = $_POST[$k] ?? '';
    if ($int) return max(0, (int)$v);
    return mb_substr(trim((string)$v), 0, 500);
}

try {
    $pdo = getPDO();
    $pdo->beginTransaction();

    // ── 1. Actualizar lotes_cerveza ───────────────────────────────────────────
    // Reporte de agua
    $id_osmosis = $id_red = null;
    if (!empty($_POST['fechareporte'])) {
        $fecha_rep = p('fechareporte');
        $s = $pdo->prepare("SELECT id FROM reportesagua WHERE fecha=? AND origen='OSMOSIS' LIMIT 1");
        $s->execute([$fecha_rep]); $row = $s->fetch();
        $id_osmosis = $row ? (int)$row['id'] : null;

        $s = $pdo->prepare("SELECT id FROM reportesagua WHERE fecha=? AND origen='RED' LIMIT 1");
        $s->execute([$fecha_rep]); $row = $s->fetch();
        $id_red = $row ? (int)$row['id'] : null;
    }

    $diaEnvasado = !empty($_POST['diaEnvasado']) ? date('Y-m-d', strtotime(p('diaEnvasado'))) : null;

    $pdo->prepare(
        "UPDATE lotes_cerveza SET
            co2=?, og=?, fg=?, ibu=?, abv=?,
            ca_mas_2=?, mg_mas_2=?, na_mas_2=?, cl_menos=?, so04_menos_2=?,
            comentarios=?, DO=?, DF=?, ph_mosto=?, ph_fin_fermentacion=?,
            litros_a_fermentador=?, dia_envasado=?, carb_level=?,
            fermentador_id=?, litros_envasados=?, reporteRED=?, reporteOSMO=?
         WHERE id=?"
    )->execute([
        p('carbLevelEsperado'), p('og'), p('fg'), p('ibuEsperado'), p('abvEsperado'),
        p('ca_mas_2',true), p('mg_mas_2',true), p('na_mas_2',true), p('cl_menos',true), p('so4_menos_2',true),
        p('comentariosGeneral'), p('lecturaDO'), p('lecturaDF'), p('ph_inicialMosto'), p('ph_finFerm'),
        p('litrosAfermentar',true), $diaEnvasado, p('carbLevel'),
        p('fermentador',true), p('ltsEnvasados',true), $id_red, $id_osmosis,
        $id_lote
    ]);

    // Limpieza fermentador
    $fermentador_id = p('fermentador', true);
    foreach (['alcalina'=>'LIMP_ALCALINA_DATE','acida'=>'LIMP_ACIDA_DATE','oxidativa'=>'LIMP_OXIDATIVA_DATE','exterior'=>'LIMP_EXTERIOR_DATE'] as $chk => $col) {
        if (isset($_POST[$chk]) && $diaEnvasado && $fermentador_id) {
            $pdo->prepare("UPDATE fermentadores SET $col=? WHERE id=?")->execute([$diaEnvasado, $fermentador_id]);
        }
    }

    // ── 2. Tratamiento agua — UPDATE o INSERT ────────────────────────────────
    $existe_agua = $pdo->prepare("SELECT id FROM tratamiento_agua_mash_sparge WHERE lote_id=? LIMIT 1");
    $existe_agua->execute([$id_lote]);
    $agua_id = $existe_agua->fetchColumn();

    $agua_vals = [
        (float)p('total_agua_mash'),    (float)p('porcentaje_ro_mash'), (float)p('temperatura_mash'), p('ph_mashh2o'),
        (float)p('caso4_mash'),         (float)p('cacl2_mash'),         (float)p('mgcl_mash'),
        (float)p('fosforico_mash'),     (float)p('otro_mash'),          (float)p('fosforico_h2o_mash'),
        (float)p('total_agua_sparge'),  (float)p('porcentaje_ro_sparge'),(float)p('temperatura_sparge'), p('ph_spargeh2o'),
        (float)p('caso4_sparge'),       (float)p('cacl2_sparge'),       (float)p('mgcl_sparge'),
        (float)p('fosforico_sparge'),   (float)p('otro_sparge'),
    ];

    if ($agua_id) {
        $pdo->prepare(
            "UPDATE tratamiento_agua_mash_sparge SET
             total_agua_mash=?,porcentaje_ro_mash=?,temperatura_mash=?,ph_mash=?,
             caso4_mash=?,cacl2_mash=?,mgcl_mash=?,fosforico_mash=?,otro_mash=?,fosforico_h2o_mash=?,
             total_agua_sparge=?,porcentaje_ro_sparge=?,temperatura_sparge=?,ph_sparge=?,
             caso4_sparge=?,cacl2_sparge=?,mgcl_sparge=?,fosforico_sparge=?,otro_sparge=?
             WHERE lote_id=?"
        )->execute(array_merge($agua_vals, [$id_lote]));
    } else {
        $pdo->prepare(
            "INSERT INTO tratamiento_agua_mash_sparge
             (lote_id,total_agua_mash,porcentaje_ro_mash,temperatura_mash,ph_mash,
              caso4_mash,cacl2_mash,mgcl_mash,fosforico_mash,otro_mash,fosforico_h2o_mash,
              total_agua_sparge,porcentaje_ro_sparge,temperatura_sparge,ph_sparge,
              caso4_sparge,cacl2_sparge,mgcl_sparge,fosforico_sparge,otro_sparge)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        )->execute(array_merge([$id_lote], $agua_vals));
    }

    // ── 3. Maltas — DELETE + INSERT ──────────────────────────────────────────
    $pdo->prepare("DELETE FROM lotes_maltas WHERE lote_id=?")->execute([$id_lote]);
    if (!empty($_POST['malta']) && is_array($_POST['malta'])) {
        $s = $pdo->prepare("INSERT INTO lotes_maltas (lote_id,malta_id,cantidad,tiempo,lote_malta) VALUES (?,?,?,?,?)");
        foreach ($_POST['malta'] as $i => $malta_id) {
            if (!$malta_id) continue;
            $cant = isset($_POST['cantidad'][$i]) && $_POST['cantidad'][$i] !== '' ? (float)str_replace(',','.',$_POST['cantidad'][$i]) : 0.0;
            $s->execute([$id_lote, (int)$malta_id, $cant, $_POST['tiempo'][$i]??'', $_POST['lote_malta'][$i]??'']);
        }
    }

    // ── 4. Lúpulos — DELETE + INSERT ─────────────────────────────────────────
    $pdo->prepare("DELETE FROM lotes_lupulos WHERE lote_id=?")->execute([$id_lote]);
    if (!empty($_POST['lupulo']) && is_array($_POST['lupulo'])) {
        $s = $pdo->prepare("INSERT INTO lotes_lupulos (lote_id,lupulo_id,cantidad,tiempo,ibu,lote_lupulo) VALUES (?,?,?,?,?,?)");
        foreach ($_POST['lupulo'] as $i => $lupulo_id) {
            if (!$lupulo_id) continue;
            $cant = isset($_POST['cantidad_lupulo'][$i]) && $_POST['cantidad_lupulo'][$i] !== '' ? (float)str_replace(',','.',$_POST['cantidad_lupulo'][$i]) : 0.0;
            $ibu  = isset($_POST['ibu'][$i])             && $_POST['ibu'][$i]             !== '' ? (float)str_replace(',','.',$_POST['ibu'][$i])             : 0.0;
            $s->execute([$id_lote, (int)$lupulo_id, $cant, $_POST['tiempo_lupulo'][$i]??'', $ibu, $_POST['lote_lupulo'][$i]??'']);
        }
    }

    // ── 5. Levadura — DELETE + INSERT ────────────────────────────────────────
    $pdo->prepare("DELETE FROM lotes_levaduras WHERE lote_id=?")->execute([$id_lote]);
    if (!empty($_POST['cepa'])) {
        $pdo->prepare(
            "INSERT INTO lotes_levaduras (lote_id,temp_inoculacion,tasa_inoculacion,viabilidad,kilos_biomasa,gen,cepa_id,oxigenacion)
             VALUES (?,?,?,?,?,?,?,?)"
        )->execute([
            $id_lote, p('tempInoc'), p('tasaInoc'), p('viabilidad',true),
            p('biomasa'), p('genleva'), p('cepa',true), p('oxigenacion',true)
        ]);
    }

    // ── 6. Batches — DELETE + INSERT ─────────────────────────────────────────
    $pdo->prepare("DELETE FROM batches WHERE lote_id=?")->execute([$id_lote]);
    if (!empty($_POST['temp_mash']) && is_array($_POST['temp_mash'])) {
        $s = $pdo->prepare(
            "INSERT INTO batches (lote_id,temp_mash,temp2_mash,temp3_mash,ph_mash,ph2_mash,ph3_mash,
             dens_primer_mosto,dens_last_run,ph_last_run,temp_sparge,ph_sparge,
             vol_inicial_boil,dens_pre_boil,ph_inicio_boil,vol_final_boil,dens_post_boil,ph_fin,batch)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        foreach ($_POST['temp_mash'] as $i => $_) {
            $s->execute([
                $id_lote,
                $_POST['temp_mash'][$i]??0,         $_POST['temp2_mash'][$i]??0,   $_POST['temp3_mash'][$i]??0,
                $_POST['ph_mash'][$i]??0,            $_POST['ph2_mash'][$i]??0,     $_POST['ph3_mash'][$i]??0,
                $_POST['dens_primer_mosto'][$i]??0,  $_POST['dens_last_run'][$i]??0, $_POST['ph_last_run'][$i]??0,
                $_POST['temp_sparge'][$i]??0,        $_POST['ph_sparge'][$i]??0,
                $_POST['vol_inicial_boil'][$i]??0,   $_POST['dens_pre_boil'][$i]??0, $_POST['ph_inicio_boil'][$i]??0,
                $_POST['vol_final_boil'][$i]??0,     $_POST['dens_post_boil'][$i]??0, $_POST['ph_fin'][$i]??0,
                $i + 1
            ]);
        }
    }

    // ── 7. Fermentación — DELETE + INSERT ────────────────────────────────────
    $pdo->prepare("DELETE FROM seguimiento_fermentacion WHERE lote_id=?")->execute([$id_lote]);
    if (!empty($_POST['fecha']) && is_array($_POST['fecha'])) {
        $s = $pdo->prepare(
            "INSERT INTO seguimiento_fermentacion (lote_id,fecha,hora,densidad,ph,temperatura,purga,comentarios)
             VALUES (?,?,?,?,?,?,?,?)"
        );
        foreach ($_POST['fecha'] as $i => $fecha) {
            if (!$fecha) continue;
            $s->execute([
                $id_lote,
                date('Y-m-d', strtotime($fecha)),
                $_POST['hora'][$i]??'',
                $_POST['densidad'][$i]??0,
                $_POST['ph'][$i]??0,
                $_POST['temperatura'][$i]??0,
                $_POST['purga'][$i]??0,
                $_POST['comentarios'][$i]??''
            ]);
        }
    }

    // ── 8. Enlatado — UPDATE o INSERT ────────────────────────────────────────
    if (!empty($_POST['diaEnlatado'])) {
        $diaEnlatado = date('Y-m-d', strtotime(p('diaEnlatado')));
        $existe = $pdo->prepare("SELECT id FROM lotesenlatado WHERE id_lote=? LIMIT 1");
        $existe->execute([$id_lote]);
        $enlatado_id = $existe->fetchColumn();

        $env_vals = [
            $diaEnlatado, p('presionbarrido'), p('presionenenlatadora'), p('presionentanque'),
            p('tiempollenado',true), p('tiempo1',true), p('tiempo2',true),
            p('tempentanque'), p('tempenenlatadora'), p('tempambiente'),
            p('observacionesenlatado'), p('disoxigen',true), p('tpo',true),
            p('latascerradasDes',true), p('latasvaciasDes',true), p('tapasDes',true), p('latasOK',true)
        ];

        if ($enlatado_id) {
            $pdo->prepare(
                "UPDATE lotesenlatado SET diaEnlatado=?,presionbarrido=?,presionenenlatadora=?,presionentanque=?,
                 tiempollenado=?,tiempo1=?,tiempo2=?,tempentanque=?,tempenenlatadora=?,tempambiente=?,
                 observacionesenlatado=?,disoxigen=?,tpo=?,latascerradasDes=?,latasvaciasDes=?,tapasDes=?,latasOK=?
                 WHERE id_lote=?"
            )->execute(array_merge($env_vals, [$id_lote]));
        } else {
            $pdo->prepare(
                "INSERT INTO lotesenlatado (id_lote,diaEnlatado,presionbarrido,presionenenlatadora,presionentanque,
                 tiempollenado,tiempo1,tiempo2,tempentanque,tempenenlatadora,tempambiente,
                 observacionesenlatado,disoxigen,tpo,latascerradasDes,latasvaciasDes,tapasDes,latasOK)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            )->execute(array_merge([$id_lote], $env_vals));
        }
    }

    $pdo->commit();
    header('Location: detalles_lote?id=' . $id_lote);

} catch (PDOException $ex) {
    $pdo->rollBack();
    error_log('[guardar_edicion_lote] ' . $ex->getMessage());
    header('Location: editar_lote?id=' . $id_lote . '&error=error_db');
}
