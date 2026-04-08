<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fabrica de Cerveza - Agregar Nota de Cata</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Agregar Nota de Cata</h1>
    <div class="agregar-nota-cata">
        <?php
        // Verificar si se recibieron datos del formulario
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Recibir y sanitizar los datos del formulario
            $fecha = $_POST['fecha'];
            $catador = $_POST['catador'];
            $aroma = $_POST['aroma'];
            $puntaje_aroma = $_POST['puntaje_aroma'];
            $apariencia = $_POST['apariencia'];
            $puntaje_apariencia = $_POST['puntaje_apariencia'];
            $sabor = $_POST['sabor'];
            $puntaje_sabor = $_POST['puntaje_sabor'];
            $sensacion_boca = $_POST['sensacion_boca'];
            $puntaje_sensacion_boca = $_POST['puntaje_sensacion_boca'];
            $impresion_general = $_POST['impresion_general'];
            $puntaje_impresion_general = $_POST['puntaje_impresion_general'];
            $lote_id = $_POST['lote_id'];

			// Conexión a la base de datos
			require('conexion.php');


            // Verificar la conexión
            if ($conn->connect_error) {
				echo "Error de conexión";
                die("Error de conexión: " . $conn->connect_error);
            }

            // Consulta para agregar la nota de cata a la base de datos
            $sql = "INSERT INTO notas_cata (fecha, nombre_catador, aroma, puntaje_aroma, apariencia, puntaje_apariencia, sabor, puntaje_sabor, sensacion_boca, puntaje_sensacion_boca, impresion_general, puntaje_impresion_general, lote_id) VALUES ('$fecha', '$catador', '$aroma', $puntaje_aroma, '$apariencia', $puntaje_apariencia, '$sabor', $puntaje_sabor, '$sensacion_boca', $puntaje_sensacion_boca, '$impresion_general', $puntaje_impresion_general, $lote_id)";

            if ($conn->query($sql) === TRUE) {
                    echo "<script>
							alert('La nota de cata ha sido agregada correctamente.');
							window.location.href = 'panel_cata';
						</script>";
            } else {
                    echo "<script>
							alert('La nota no se a podido guardar. Revise los datos ingresados.');
							window.location.href = 'panel_cata';
						</script>";
            }

            // Cerrar conexión a la base de datos
            $conn->close();
        }
        ?>
    </div>
</body>
</html>
