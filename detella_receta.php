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
		$lote_id = $_GET['id_receta'];

		$result = $conn->query($sql);

		// Consulta para obtener los detalles del lote de cerveza
		$sql = "SELECT * FROM recetas_estilos AS r JOIN estilos_cerveza AS e ON r.estilo_id = e.id WHERE id = $lote_id";
		$lotecerveza = $conn->query($sql);

		if ($result->num_rows > 0) {
			// Mostrar los detalles del lote de cerveza
			
			$row = $result->fetch_assoc();
			$fecha_elaboracion = date('d/m/Y', strtotime($row['fecha_elaboracion']));
			$fermentador = $row['fermentador_id'];
			echo "<div class='container'>";
			echo "<h2>Estilo: " . $row['nombre'] . "</h2>";
			echo "<h3>Descripción: </h3>";
			echo "<p>$row['descripcion']</p>";

			
			// Agregar una línea de separación
			echo "<hr>";

			// Mostrar los otros detalles del lote de cerveza
			echo "<h3>Parámetros Vitales esperados</h3>";
			echo "<table border='1'>";
			echo "<tr><td><strong>OG</strong></td><td>" . $row['og'] . "</td></tr>";
			echo "<tr><td><strong>FG</strong></td><td>" . $row['fg'] . "</td></tr>";
			echo "<tr><td><strong>IBU</strong></td><td>" . $row['ibu'] . "</td></tr>";
			echo "<tr><td><strong>ABV</strong></td><td>" . $row['abv'] . "</td></tr>";
			echo "<tr><td><strong>Carb. Level</strong></td><td>" . $row['co2'] . "</td></tr>";
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
			
				echo "<table><tr>";
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
			echo "<th>Uso</th>";
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
			echo "<thead><tr><th>Variedad</th><th>Marca</th><th>Nro. lote</th><th>Cantidad (g)</th><th>IBU</th><th>Tiempo / Técnica </th></tr></thead>";
			echo "<tbody>";
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
			echo "</tbody>";
			echo "</table>";
		} else {
			echo "No se encontraron lupulos asociados a este lote de cerveza.";
		}
		

		
		// Consulta SQL para obtener los detalles del lote y la información de la levadura asociada
		$sql = "SELECT ll.*, cl.cepa AS nombre_levadura, cl.marca AS marca_levadura
				FROM lotes_levaduras ll
				INNER JOIN cepas_levadura cl ON ll.cepa_id = cl.id
				WHERE ll.lote_id = $lote_id";

		echo "<h3>Levadura</h3>";

		$resCepa = $conn->query($sql);
		if ($resCepa->num_rows > 0) {
				
				$row = $resCepa->fetch_assoc();
				echo "<table border='1'>";
				echo "<tr><td>Cepa</td><td>" . $row['nombre_levadura'] . "</td></tr>";
				echo "<tr><td>Marca</td><td>" . $row['marca_levadura'] . "</td></tr>";
				echo "<tr><td>Generación</td><td>" . $row['gen'] . "</td></tr>";
				echo "<tr><td>Temperatura de inoculación</td><td>" . $row['temp_inoculacion'] . " °C</td></tr>";
				echo "<tr><td>Tasa de inoculación</td><td>" . $row['tasa_inoculacion'] . "</td></tr>";
				echo "<tr><td>Viabilidad</td><td>" . $row['viabilidad'] . "</td></tr>";
				echo "<tr><td>Kilos de biomasa</td><td>" . $row['kilos_biomasa'] . " kg.</td></tr>";
				echo "<tr><td>PPM Oxígeno</td><td>" . $row['oxigenacion'] . "</td></tr>";
				echo "</table>";
		}
		echo "<hr>";


		// Cerrar conexión a la base de datos
		$conn->close();

		?>

		</script>

    </div>
    </main>
    <footer>
        <p>&copy; 2024 Bialystok Brewing CO SAS. Todos los derechos reservados.</p>
    </footer>
</body>
</html>
