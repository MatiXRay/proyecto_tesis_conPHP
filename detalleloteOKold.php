<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Białystok - Detalles del Lote</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <header>
        <h1>Detalles del Lote de Cerveza</h1>
    </header>
    <main>
        <div class="container">
		<?php
		// Establecer las credenciales de la base de datos
		$servername = "localhost";
		$username = "rocko";
		$password = "Pepito11!";
		$dbname = "fabrica_cerveza";

		// Crear la conexión a la base de datos
		$conn = new mysqli($servername, $username, $password, $dbname);

		// Verificar la conexión
		if ($conn->connect_error) {
			die("Error de conexión: " . $conn->connect_error);
		}

		// Obtener el ID del lote de cerveza desde la URL
		$lote_id = $_GET['id_lote'];

		// Consulta para obtener los detalles del lote de cerveza
		$sql = "SELECT lc.*, 
				GROUP_CONCAT(DISTINCT vm.nombre SEPARATOR ', ') AS maltas_nombres, 
				GROUP_CONCAT(DISTINCT vm.marca SEPARATOR ', ') AS maltas_marcas, 
				GROUP_CONCAT(DISTINCT vl.nombre SEPARATOR ', ') AS lupulos_nombres, 
				GROUP_CONCAT(DISTINCT vl.marca SEPARATOR ', ') AS lupulos_marcas
				FROM lotes_cerveza lc
				LEFT JOIN lotes_maltas lm ON lc.id = lm.lote_id
				LEFT JOIN variedades_malta vm ON lm.malta_id = vm.id
				LEFT JOIN lotes_lupulos ll ON lc.id = ll.lote_id
				LEFT JOIN variedades_lupulo vl ON ll.lupulo_id = vl.id
				WHERE lc.id = $lote_id
				GROUP BY lc.id";
		$result = $conn->query($sql);

		// Consulta para obtener los detalles del lote de cerveza
		$sql = "SELECT * FROM lotes_cerveza WHERE id = $lote_id";
		$lotecerveza = $conn->query($sql);

		if ($result->num_rows > 0) {
			// Mostrar los detalles del lote de cerveza
			
			$row = $result->fetch_assoc();
			$fecha_elaboracion = date('d/m/Y', strtotime($row['fecha_elaboracion']));
			echo "<div class='container'>";
			echo "<h2>Estilo: " . obtenerNombreEstilo($row['estilo_id'], $conn) . "</h2>";
			echo "<h2>Fecha de Elaboración: " . $fecha_elaboracion . "</h2>";
			echo "<h2>Lote N°: " . $row['numero_lote'] . "</h2>";
			echo "<button class='notacata-button' id='btnMostrarNotasCata'>Ver Notas de Cata</button>";


			// Agregar una línea de separación
			echo "<hr>";

			// Mostrar los otros detalles del lote de cerveza
			echo "<h3>Parámetros Vitales esperados</h3>";
			echo "<table border='1'>";
			echo "<tr><td>OG</td><td>" . $row['og'] . "</td></tr>";
			echo "<tr><td>FG</td><td>" . $row['fg'] . "</td></tr>";
			echo "<tr><td>IBU</td><td>" . $row['ibu'] . "</td></tr>";
			echo "<tr><td>ABV</td><td>" . $row['abv'] . "</td></tr>";
			echo "</table>";
				
				
			// Agregar sección para parámetros de agua
			echo "<h3>Parámetros de H<sub>2</sub>O</h3>";
			echo "<table border='1'>";
			echo "<tr><th>Ion</th><th>Valor (ppm)</th></tr>";
			echo "<tr><td>Calcio (Ca<sup>+2</sup>)</td><td>" . $row['ca_mas_2'] . "</td></tr>";
			echo "<tr><td>Magnesio (Mg<sup>+2</sup>)</td><td>" . $row['mg_mas_2'] . "</td></tr>";
			echo "<tr><td>Sodio (Na<sup>+2</sup>)</td><td>" . $row['na_mas_2'] . "</td></tr>";
			echo "<tr><td>Cloruro (Cl<sup>-</sup>)</td><td>" . $row['cl_menos'] . "</td></tr>";
			echo "<tr><td>Sulfato (SO<sub>4</sub><sup>-2</sup>)</td><td>" . $row['so04_menos_2'] . "</td></tr>";
			// Agregar más filas para otros parámetros de agua si es necesario
			echo "</table>";
			}
			
			// Consulta para obtener los detalles del tratamiento de agua y mash/sparge
			$sql_tratamiento_agua = "SELECT * FROM tratamiento_agua_mash_sparge WHERE lote_id = $lote_id";
			$result_tratamiento_agua = $conn->query($sql_tratamiento_agua);

			if ($result_tratamiento_agua->num_rows > 0) {
				// Mostrar los detalles del tratamiento de agua y mash/sparge
				echo "<h3>Tratamiento H<sub>2</sub>O MASH</h3>";

				echo "<table border='1'>";
				echo "<tr><th>Total</th><th>RO</th><th>Temperatura</th><th>pH</th><th>Fosfórico Mash</th><th>CaSO<sub>4</sub></th><th>CaCl<sub>2</sub></th><th>MgCl<sub>2</sub></th><th>Otro</th><th>Fosfórico H<sub>2</sub>O</th></tr>";
				while ($row_tratamiento_agua = $result_tratamiento_agua->fetch_assoc()) {
					echo "<tr>";
					echo "<td>" . $row_tratamiento_agua['total_agua_mash'] . "lts.</td>";
					echo "<td>" . $row_tratamiento_agua['porcentaje_ro_mash'] . "%</td>";
					echo "<td>" . $row_tratamiento_agua['temperatura_mash'] . "°C</td>";
					echo "<td>" . $row_tratamiento_agua['ph_mash'] . "</td>";
					echo "<td>" . $row_tratamiento_agua['fosforico_mash'] . "</td>";
					echo "<td>" . $row_tratamiento_agua['caso4_mash'] . "</td>";
					echo "<td>" . $row_tratamiento_agua['cacl2_mash'] . "</td>";
					echo "<td>" . $row_tratamiento_agua['mgcl_mash'] . "</td>";
					echo "<td>" . $row_tratamiento_agua['otro_mash'] . "</td>";
					echo "<td>" . $row_tratamiento_agua['fosforico_h2o_mash'] . "</td>";
					

					echo "</tr>";
				}
				echo "</table>";

				$result_tratamiento_agua = $conn->query($sql_tratamiento_agua);

				echo "<h3>Tratamiento H<sub>2</sub>O SPARGE</h3>";

				echo "<table border='1'>";
				echo "<tr><th>Total</th><th>RO</th><th>Temperatura</th><th>pH</th><th>Fosfórico</th><th>CaSO<sub>4</sub></th><th>CaCl<sub>2</sub></th><th>MgCl</th><th>Otro</th></tr>";
				while ($row_tratamiento_agua = $result_tratamiento_agua->fetch_assoc()) {    
					echo "<td>" . $row_tratamiento_agua['total_agua_sparge'] . "lts.</td>";
					echo "<td>" . $row_tratamiento_agua['porcentaje_ro_sparge'] . "%</td>";
					echo "<td>" . $row_tratamiento_agua['temperatura_sparge'] . "°C</td>";
					echo "<td>" . $row_tratamiento_agua['ph_sparge'] . "</td>";
					echo "<td>" . $row_tratamiento_agua['fosforico_sparge'] . "</td>";
					echo "<td>" . $row_tratamiento_agua['caso4_sparge'] . "</td>";
					echo "<td>" . $row_tratamiento_agua['cacl2_sparge'] . "</td>";
					echo "<td>" . $row_tratamiento_agua['mgcl_sparge'] . "</td>";
					echo "<td>" . $row_tratamiento_agua['otro_sparge'] . "</td>";
					
						}
				echo "</table>";
			} else {
				echo "No se encontraron detalles para el tratamiento de agua y mash/sparge.";
				}
			
			
			// Obtener el ID del lote de cerveza desde la URL
			$lote_id = $_GET['id_lote'];

			// Consulta para obtener los detalles del lote de cerveza
			$sql = "SELECT * FROM lotes_cerveza WHERE id = $lote_id";
			$result = $conn->query($sql);


// Consulta SQL para obtener la información de las maltas asociadas al lote de cerveza
$sqlM = "SELECT lm.*, vm.nombre AS nombre_malta, vm.marca AS marca_malta 
        FROM lotes_maltas lm
        INNER JOIN variedades_malta vm ON lm.malta_id = vm.id
        WHERE lm.lote_id = $lote_id";

		$resultMalta = $conn->query($sqlM);
		echo "<h3>Maltas</h3>";
		if ($resultMalta->num_rows > 0) {
			// Mostrar la tabla con la información de las maltas
			echo "<table border=1>";
			echo "<thead>";
			echo "<tr>";
			echo "<th>Variedad</th>";
			echo "<th>Marca</th>";
			echo "<th>Nro. lote</th>";
			echo "<th>Cantidad</th>";
			echo "<th>Tiempo</th>";
			echo "</tr>";
			echo "</thead>";
			echo "<tbody>";
			while ($row = $resultMalta->fetch_assoc()) {
				echo "<tr>";
				echo "<td>" . $row['nombre_malta'] . "</td>";
				echo "<td>" . $row['marca_malta'] . "</td>";
				echo "<td>" . $row['lote_malta'] . "</td>";
				echo "<td>" . $row['cantidad'] . "</td>";
				echo "<td>" . $row['tiempo'] . "</td>";
				echo "</tr>";
			}
			echo "</tbody>";
			echo "</table>";
		} else {
			echo "No se encontraron maltas asociadas a este lote de cerveza.";
		}
			// Sección de Lúpulos
			echo "<h3>Lúpulos</h3>";
			// Consulta SQL para obtener los lupulos asociados al lote de cerveza
			$sqlHops = "SELECT ll.*, v.nombre AS nombre_lupulo, v.marca
						FROM lotes_lupulos ll
						INNER JOIN variedades_lupulo v ON ll.lupulo_id = v.id
						WHERE ll.lote_id = $lote_id";

		$resHops = $conn->query($sqlHops);

		// Verificar si se encontraron resultados
		if ($resHops->num_rows > 0) {
			// Mostrar los datos en una tabla HTML
			echo "<table border = 1>";
			echo "<tr><th>Variedad</th><th>Marca</th><th>Nro. lote</th><th>Cantidad (g)</th><th>IBU</th><th>Tiempo / Técnica </th></tr>";
			while ($row = $resHops->fetch_assoc()) {
				echo "<tr>";
				echo "<td>" . $row['nombre_lupulo'] . "</td>";
				echo "<td>" . $row['marca'] . "</td>";
				echo "<td>" . $row['lote_lupulo'] . "</td>";
				echo "<td>" . $row['cantidad'] . "</td>";
				echo "<td>" . $row['ibu'] . "</td>";
				echo "<td>" . $row['tiempo'] . "</td>";
				echo "</tr>";
			}
			echo "</table>";
		} else {
			echo "No se encontraron lupulos asociados a este lote de cerveza.";
		}
		
		// Función para obtener el nombre del estilo de cerveza
		function obtenerNombreEstilo($estilo_id, $conn) {
			// Consulta para obtener el nombre del estilo de cerveza
			$sql = "SELECT nombre FROM estilos_cerveza WHERE id = $estilo_id";
			$result = $conn->query($sql);

			if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				return $row['nombre'];
			} else {
				return "Desconocido";
			}
		}


		echo "<hr>";

		$sql2 = "SELECT * FROM batches WHERE lote_id = $lote_id"; // Modifica la consulta según tu esquema de base de datos

		// Ejecutar la consulta
		$res = $conn->query($sql2);
				
		echo"<h3>LOG del día de cocción</h3>";

		if ($res->num_rows > 0) {
			$count = 1;
			// Mostrar los resultados en forma de tabla
			echo "<div class='tabla-container'>";
			echo "<table>";
			echo "<thead>";
			echo "<tr>";
			echo "<th>#</th>";
			echo "<th>Temp Mash</th>";
			echo "<th>PH Mash</th>";
			echo "<th>Dens Primer Mosto</th>";
			echo "<th>Dens Last Run</th>";
			echo "<th>PH Last Run</th>";
			echo "<th>Temp Sparge</th>";
			echo "<th>PH Sparge</th>";
			echo "<th>Vol Inicial Boil</th>";
			echo "<th>Dens Pre Boil</th>";
			echo "<th>PH Inicio Boil</th>";
			echo "<th>Vol Final Boil</th>";
			echo "<th>Dens Post Boil</th>";
			echo "<th>PH Fin</th>";
			while ($row = $res->fetch_assoc()) {
				echo "<tr>";
				echo "<td>{$count}</td>";
				echo "<td>{$row['temp_mash']}</td>";
				echo "<td>{$row['ph_mash']}</td>";
				echo "<td>{$row['dens_primer_mosto']}</td>";
				echo "<td>{$row['dens_last_run']}</td>";
				echo "<td>{$row['ph_last_run']}</td>";
				echo "<td>{$row['temp_sparge']}</td>";
				echo "<td>{$row['ph_sparge']}</td>";
				echo "<td>{$row['vol_inicial_boil']}</td>";
				echo "<td>{$row['dens_pre_boil']}</td>";
				echo "<td>{$row['ph_inicio_boil']}</td>";
				echo "<td>{$row['vol_final_boil']}</td>";
				echo "<td>{$row['dens_post_boil']}</td>";
				echo "<td>{$row['ph_fin']}</td>";
				echo "</tr>";
				$count++;
			}
			echo "</table>";
			echo "</div>";
		} else {
                echo "<tr><td colspan='13'>No se encontraron batches para este lote de cerveza.</td></tr>";
            }
		echo "<hr>";
		
		$sql = "SELECT * FROM seguimiento_fermentacion WHERE lote_id = $lote_id ORDER BY fecha, hora";
		$result = $conn->query($sql);

		if ($result->num_rows > 0) {
			// Mostrar el seguimiento de fermentación
			$dia = 1;
			echo "<h3>Seguimiento de Fermentación</h3>";
			echo "<table border='1'>";
			echo "<tr><th>#</th><th>Fecha</th><th>Hora</th><th>Densidad</th><th>PH</th><th>Temperatura</th><th>Purga</th><th>Comentarios</th></tr>";
			while ($row = $result->fetch_assoc()) {
				$fechalog = date('d/m', strtotime($row['fecha']));
				echo "<tr>";
				echo "<td>{$dia}</td>";
				echo "<td>{$fechalog}</td>";
				echo "<td>{$row['hora']}</td>";
				echo "<td>{$row['densidad']}</td>";
				echo "<td>{$row['ph']}</td>";
				echo "<td>{$row['temperatura']} °C</td>";
				echo "<td>{$row['purga']}</td>";
				echo "<td>{$row['comentarios']}</td>";
				echo "</tr>";
				$dia++;
			}
			echo "</table>";
		} else {
			echo "No se encontraron registros de seguimiento de fermentación para este lote de cerveza.";
		}
		
		echo "<hr>";

		if ($lotecerveza->num_rows > 0) {
			// Mostrar los detalles del lote de cerveza
			$row = $lotecerveza->fetch_assoc();
			$dia_envasado = date('d/m/Y', strtotime($row['dia_envasado']));

			echo "<h3>Levadura</h3>";
			echo "<table border='1'>";
			echo "<tr><td>Cepa</td><td>" . $row['cepa_levadura'] . "</td></tr>";
			echo "<tr><td>Generación</td><td>" . $row['generacion_levadura'] . "</td></tr>";
			echo "<tr><td>Temperatura de inoculación</td><td>" . $row['temp_inoculacion'] . " °C</td></tr>";
			echo "<tr><td>Tasa de inoculación</td><td>" . $row['tasa_inoculacion'] . "</td></tr>";
			echo "<tr><td>Viabilidad</td><td>" . $row['viabilidad'] . "</td></tr>";
			echo "<tr><td>Kilos de biomasa</td><td>" . $row['kilos_biomasa'] . " kg.</td></tr>";
			echo "</table>";

			echo "<h3>Lecturas finales y envasado</h3>";
			echo "<table border='1'>";
			echo "<tr><td>DO</td><td>" . $row['DO'] . "</td></tr>";
			echo "<tr><td>DF</td><td>" . $row['DF'] . "</td></tr>";
			echo "<tr><td>pH Inicial</td><td>" . $row['ph_mosto'] . "</td></tr>";
			echo "<tr><td>pH Final</td><td>" . $row['ph_fin_fermentacion'] . "</td></tr>";
			echo "<tr><td>Litros a Fermentador</td><td>" . $row['litros_a_fermentador'] . " lts.</td></tr>";
			echo "<tr><td>Día de Envasado</td><td>" . $dia_envasado . "</td></tr>";
			echo "<tr><td>Carb Level</td><td>" . $row['carb_level'] . " vols.</td></tr>";
			echo "<tr><td>Litros Envasados</td><td>" . $row['litros_envasados'] . " lts.</td></tr>";
			echo "</table>";
			
		} else {
			echo "No se encontraron detalles para este lote de cerveza.";
			}
		

		echo "<button class='notacata-button' id='btnPopupEnvasado'>Ver detalles de envasado</button>";

		// Cerrar conexión a la base de datos
		$conn->close();

		?>

		<script>
		document.getElementById('btnMostrarNotasCata').addEventListener('click', function() {
				// Abrir el popup con la página de detalles del batch
				// Obtener el ancho y la altura de la ventana del navegador
				const windowWidth = window.innerWidth;
				const windowHeight = window.innerHeight;
				// Calcular las coordenadas para centrar el popup
				const popupWidth = 600;
				const popupHeight = 400;
				const leftPosition = (windowWidth - popupWidth) / 2;
				const topPosition = (windowHeight - popupHeight) / 2;
				// Obtener el ID del lote de cerveza
				const loteId = <?php echo $lote_id; ?>; // Asegúrate de tener disponible el ID del lote en esta página

			// Abrir el popup con las notas de cata correspondientes al lote
			window.open(`notas_cata.php?lote_id=${loteId}`, 'popupNotasCata', `width=${popupWidth},height=${popupHeight},left=${leftPosition},top=${topPosition}`);
		});
	
	
	
		// Obtener todos los botones de lote
		const batchButtons = document.querySelectorAll('.batch-button');

		// Iterar sobre cada botón y agregar un listener de clic
		batchButtons.forEach(button => {
			button.addEventListener('click', () => {
				// Obtener el ID del batch del atributo de datos
				const batchId = button.dataset.batchId;

				// Abrir el popup con la página de detalles del batch
				// Obtener el ancho y la altura de la ventana del navegador
				const windowWidth = window.innerWidth;
				const windowHeight = window.innerHeight;

				// Calcular las coordenadas para centrar el popup
				const popupWidth = 600;
				const popupHeight = 400;
				const leftPosition = (windowWidth - popupWidth) / 2;
				const topPosition = (windowHeight - popupHeight) / 2;
				window.open(`popup_batch.php?batch_id=${batchId}`, 'popup', `width=${popupWidth},height=${popupHeight},left=${leftPosition},top=${topPosition}`);

				});
		});
		
				document.getElementById('btnPopupEnvasado').addEventListener('click', function() {
				// Abrir el popup con la página de detalles del batch
				// Obtener el ancho y la altura de la ventana del navegador
				const windowWidth = window.innerWidth;
				const windowHeight = window.innerHeight;
				// Calcular las coordenadas para centrar el popup
				const popupWidth = 600;
				const popupHeight = 400;
				const leftPosition = (windowWidth - popupWidth) / 2;
				const topPosition = (windowHeight - popupHeight) / 2;
				// Obtener el ID del lote de cerveza
				const loteId = <?php echo $lote_id; ?>; // Asegúrate de tener disponible el ID del lote en esta página

			// Abrir el popup con las notas de cata correspondientes al lote
			window.open(`popup_envasado.php?lote_id=${loteId}`, 'popupNotasCata', `width=${popupWidth},height=${popupHeight},left=${leftPosition},top=${topPosition}`);
		});
		
    
		</script>

    </div>
    </main>
    <footer>
        <p>&copy; 2024 Bialystok Brewing CO SAS. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
