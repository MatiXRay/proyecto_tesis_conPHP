<?php
/**
 * BIALYSTOK BREWING CO — Guardar nota de cata
 */

require_once 'auth.php';
requireLogin();
require_once 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: lotes'); exit; }
verifyCsrf();

$id_lote    = getIntParam('id_lote', 'POST');
$id_usuario = (int)$_SESSION['id'];
if (!$id_lote) { header('Location: lotes'); exit; }

function pint(string $k, int $min=0, int $max=10): int {
    $v = (int)($_POST[$k] ?? 0);
    return max($min, min($max, $v));
}
function pstr(string $k, int $maxlen=500): string {
    return mb_substr(trim($_POST[$k] ?? ''), 0, $maxlen);
}

// Concatenar causa y acción en impresion_comentario
$impresion = pstr('impresion_comentario');
$causa     = pstr('causa');
$accion    = pstr('accion');
if ($causa)  $impresion .= "\nCausa: $causa";
if ($accion) $impresion .= "\nAcción: $accion";

try {
    $pdo  = getPDO();
    $stmt = $pdo->prepare(
        "INSERT INTO notas_cata (
            id_usuario, id_lote, origen_muestra, tiempo_transcurrido,
            malta_intensidad, lupulo_intensidad, esteres_intensidad,
            fenoles_intensidad, alcohol_intensidad, dulzor_intensidad,
            acidez_intensidad, otros_intensidad,
            maltas_atributos, lupulo_atributos, esteres_atributos, otros_atributos,
            aroma_comentario, aroma_puntaje,
            claridad_intensidad, retencion_intensidad, tamano_intensidad,
            textura_intensidad, color_cerveza, color_espuma, color_otro,
            apariencia_comentario, apariencia_puntaje,
            sabor_malta_intensidad, sabor_lupulo_intensidad,
            sabor_esteres_intensidad, sabor_fenoles_intensidad, sabor_alcohol_intensidad,
            sabor_dulzor_intensidad, sabor_acidez_intensidad, sabor_otros_intensidad,
            sabor_malta_atributos, sabor_lupulo_atributos, sabor_esteres_atributos,
            sabor_otros_atributos, balance,
            sabor_comentario, sabor_puntaje,
            cuerpo_intensidad, carbonatacion_intensidad, calentamiento_intensidad,
            cremosidad_intensidad, astringencia_intensidad,
            mouthfeel_fallas, mouthfeel_final, mouthfeel_comentario, mouthfeel_puntaje,
            impresion_comentario, impresion_puntaje, fallas
        ) VALUES (
            :id_usuario, :id_lote, :origen, :tiempo,
            :ar_malta, :ar_lupulo, :ar_esteres,
            :ar_fenoles, :ar_alcohol, :ar_dulzor,
            :ar_acidez, :ar_otros,
            :maltas_attr, :lupulo_attr, :esteres_attr, :otros_attr,
            :ar_comentario, :ar_puntaje,
            :claridad, :retencion, :tamanio,
            :textura, :color_c, :color_e, :color_o,
            :ap_comentario, :ap_puntaje,
            :sa_malta, :sa_lupulo,
            :sa_esteres, :sa_fenoles, :sa_alcohol,
            :sa_dulzor, :sa_acidez, :sa_otros,
            :sa_malta_attr, :sa_lupulo_attr, :sa_esteres_attr,
            :sa_otros_attr, :balance,
            :sa_comentario, :sa_puntaje,
            :cuerpo, :carbonatacion, :calentamiento,
            :cremosidad, :astringencia,
            :mf_fallas, :mf_final, :mf_comentario, :mf_puntaje,
            :impresion, :puntaje_global, :fallas
        )"
    );

    $stmt->execute([
        ':id_usuario'    => $id_usuario,
        ':id_lote'       => $id_lote,
        ':origen'        => pstr('origen_muestra'),
        ':tiempo'        => pint('tiempo_transcurrido'),
        ':ar_malta'      => pint('malta_intensidad'),
        ':ar_lupulo'     => pint('lupulo_intensidad'),
        ':ar_esteres'    => pint('esteres_intensidad'),
        ':ar_fenoles'    => pint('fenoles_intensidad'),
        ':ar_alcohol'    => pint('alcohol_intensidad'),
        ':ar_dulzor'     => pint('dulzor_intensidad'),
        ':ar_acidez'     => pint('acidez_intensidad'),
        ':ar_otros'      => pint('otros_intensidad'),
        ':maltas_attr'   => pstr('maltas_atributos'),
        ':lupulo_attr'   => pstr('lupulo_atributos'),
        ':esteres_attr'  => pstr('esteres_atributos'),
        ':otros_attr'    => pstr('otros_atributos'),
        ':ar_comentario' => pstr('aroma_comentario'),
        ':ar_puntaje'    => pint('aroma_puntaje'),
        ':claridad'      => pint('claridad_intensidad'),
        ':retencion'     => pint('retencion_intensidad'),
        ':tamanio'       => pint('tamano_intensidad'),
        ':textura'       => pint('textura_intensidad'),
        ':color_c'       => pstr('color_cerveza'),
        ':color_e'       => pstr('color_espuma'),
        ':color_o'       => pstr('color_otro'),
        ':ap_comentario' => pstr('apariencia_comentario'),
        ':ap_puntaje'    => pint('apariencia_puntaje'),
        ':sa_malta'      => pint('sabor_malta_intensidad'),
        ':sa_lupulo'     => pint('sabor_lupulo_intensidad'),
        ':sa_esteres'    => pint('sabor_esteres_intensidad'),
        ':sa_fenoles'    => pint('sabor_fenoles_intensidad'),
        ':sa_alcohol'    => pint('sabor_alcohol_intensidad'),
        ':sa_dulzor'     => pint('sabor_dulzor_intensidad'),
        ':sa_acidez'     => pint('sabor_acidez_intensidad'),
        ':sa_otros'      => pint('sabor_otros_intensidad'),
        ':sa_malta_attr' => pstr('sabor_malta_atributos'),
        ':sa_lupulo_attr'=> pstr('sabor_lupulo_atributos'),
        ':sa_esteres_attr'=> pstr('sabor_esteres_atributos'),
        ':sa_otros_attr' => pstr('sabor_otros_atributos'),
        ':balance'       => pstr('balance'),
        ':sa_comentario' => pstr('sabor_comentario'),
        ':sa_puntaje'    => pint('sabor_puntaje'),
        ':cuerpo'        => pint('cuerpo_intensidad'),
        ':carbonatacion' => pint('carbonatacion_intensidad'),
        ':calentamiento' => pint('calentamiento_intensidad'),
        ':cremosidad'    => pint('cremosidad_intensidad'),
        ':astringencia'  => pint('astringencia_intensidad'),
        ':mf_fallas'     => pstr('mouthfeel_fallas'),
        ':mf_final'      => pstr('mouthfeel_final'),
        ':mf_comentario' => pstr('mouthfeel_comentario'),
        ':mf_puntaje'    => pint('mouthfeel_puntaje'),
        ':impresion'     => mb_substr($impresion, 0, 1000),
        ':puntaje_global'=> pint('impresion_puntaje', 0, 10),
        ':fallas'        => pstr('fallas', 500),
    ]);

    header('Location: detalles_lote?id=' . $id_lote . '&ok=cata');

} catch (PDOException $ex) {
    error_log('[guardar_cata] ' . $ex->getMessage());
    header('Location: planilla_cata?id=' . $id_lote . '&error=1');
}
