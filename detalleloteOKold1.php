<?php
// Establecer la conexión a la base de datos
$servername = "localhost";
$username = "rocko";
$password = "Pepito11!";
$dbname = "fabrica_cerveza";

$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Obtener el ID del lote de la URL
if(isset($_GET['id_lote'])) {
    $id_lote = $_GET['id_lote'];

    // Consulta SQL para obtener el ID correspondiente de recetas_estilos
    $sql = "SELECT estilo_id FROM lotes_cerveza WHERE id = $id_lote";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // Obtener el ID de recetas_estilos
        $row = $result->fetch_assoc();
        $id_recetas_estilos = $row['estilo_id'];

        // Consulta SQL para obtener los detalles de la receta
        $sql_receta = "SELECT * FROM recetas_estilos WHERE id = $id_recetas_estilos";
        $result_receta = $conn->query($sql_receta);

        if ($result_receta->num_rows > 0) {
            // Mostrar el formulario con los detalles de la receta
            $row_receta = $result_receta->fetch_assoc();
        } else {
            echo "No se encontraron detalles de receta para este lote.";
        }
    } else {
        echo "No se encontró el ID del lote.";
    }
} else {
    echo "No se proporcionó el ID del lote.";
}

// Cerrar la conexión a la base de datos
$conn->close();
?>

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
			<!-- Parametros vitales -->
            <form action="guardar_receta.php" method="POST">
				
				<input type="text" id="nombre" name="nombre" required><br>
				<h3>Parámetros vitales</h3>
				<table name="paramesperados" border='1'>
					<tr>
						<td>OG:</td>
						<td><input type="text" id="og" name="og" title='Ingrese un número con el formato 0.000' pattern="\d+(\.\d{3})?" value="<?php echo $row_receta['og']; ?>" required></td>
					</tr>
					<tr>
						<td>FG:</td>
						<td><input type="text" id="fg" name="fg" title='Ingrese un número con el formato 0.000' pattern="\d+(\.\d{3})?" value="<?php echo $row_receta['fg']; ?>" required></td>
					</tr>
					<tr>
						<td>IBU:</td>
						<td><input type='text' pattern="\d+(\.\d{1,2})?" title='Ingrese un número con el formato 0,00' id="ibu" name="ibu" value="<?php echo $row_receta['ibu']; ?>" required></td>
					</tr>
					<tr>
						<td>ABV:</td>
						<td><input type='text' pattern="\d+(\.\d{1,2})?" title='Ingrese un número con el formato 0,00' id="abv" name="abv" value="<?php echo $row_receta['abv']; ?>" required></td>
					</tr>
				</table>
				
                <hr>
                <!-- Parametros H2O -->
			   <h3>Parámetros de H<sub>2</sub>O</h3>
			   <table border=1>
					<tr>
						<td>Calcio (Ca<sup>+2</sup>):</td>
						<td><input type="number" id="ca_mas_2" name="ca_mas_2" value="<?php echo $row_receta['ca_mas_2']; ?>" required></td>
					</tr>
					<tr>
						<td>Magnesio (Mg<sup>+2</sup>):</td>
						<td><input type="number" id="mg_mas_2" name="mg_mas_2" value="<?php echo $row_receta['mg_mas_2']; ?>" required></td>
					</tr>
					<tr>
						<td>Sodio (Na<sup>+2</sup>):</td>
						<td><input type="number" id="na_mas_2" name="na_mas_2" value="<?php echo $row_receta['na_mas_2']; ?>" required></td>
					</tr>
					<tr>
						<td>Cloruro (Cl<sup>-</sup>):</td>
						<td><input type="number" id="cl_menos" name="cl_menos" value="<?php echo $row_receta['cl_menos']; ?>" required></td>
					</tr>
					<tr>
						<td>Sulfato (SO<sub>4</sub><sup>-2</sup>):</td>
						<td><input type="number" id="so4_menos_2" name="so4_menos_2" value="<?php echo $row_receta['so04_menos_2']; ?>" required></td>
					</tr>
				</table>
                <hr>
				
				<?php
					// Realizar la conexión a la base de datos
					$servername = "localhost";
					$username = "rocko";
					$password = "Pepito11!";
					$dbname = "fabrica_cerveza";

					$conn = new mysqli($servername, $username, $password, $dbname);
					if ($conn->connect_error) {
						die("Error de conexión: " . $conn->connect_error);
					}

					// Consulta SQL para obtener los datos de recetas_estilos
					$sql = "SELECT * FROM recetas_estilos WHERE id = $id_recetas_estilos"; 
					$result = $conn->query($sql);
					if ($result->num_rows > 0) {
						// Mostrar la tabla con los datos recuperados
						echo "<h3>Tratamiento H<sub>2</sub>O MASH</h3>";
						echo "<table border='1'  name='tratamientoh2omash' class='tabla-container'>";
						echo "<tr><th>Total</th><th>RO</th><th>Temperatura</th><th>pH<br></th><th>Fosfórico Mash</th><th>CaSO<sub>4</sub></th><th>CaCl<sub>2</sub></th><th>MgCl<sub>2</sub></th><th>Otro</th><th>Fosfórico H<sub>2</sub>O</th></tr>";
						while ($row = $result->fetch_assoc()) {
							echo "<tr>";
							echo "<td><input type='number' name='total_agua_mash' value='" . $row['total_agua_mash'] . "'></td>";
							echo "<td><input type='number' name='porcentaje_ro_mash' value='" . $row['porcentaje_ro_mash'] . "'></td>";
							echo "<td><input type='number' name='temperatura_mash' value='" . $row['temperatura_mash'] . "'></td>";
							echo "<td><input type='text' pattern='\\d+(\\.\\d{1,2})?' title='Ingrese un número con el formato 0,00' name='ph_mash' value='" . $row['ph_mash'] . "'></td>";							
							echo "<td><input type='number' name='fosforico_mash' value='" . $row['fosforico_mash'] . "'></td>";							
							echo "<td><input type='number' name='caso4_mash' value='" . $row['caso4_mash'] . "'></td>";							
							echo "<td><input type='number' name='cacl2_mash' value='" . $row['cacl2_mash'] . "'></td>";	
							echo "<td><input type='number' name='mgcl_mash' value='" . $row['mgcl_mash'] . "'></td>";	
							echo "<td><input type='number' name='otro_mash' value='" . $row['otro_mash'] . "'></td>";	
							echo "<td><input type='number' name='fosforico_h2o_mash' value='" . $row['fosforico_h2o_mash'] . "'></td>";	
							echo "</tr>";
						}
						echo "</table>";
						
						$result->data_seek(0);
						echo "<hr>";
						echo "<h3>Tratamiento H<sub>2</sub>O SPARGE</h3>";
						echo "<table border='1' name='tratamientoh2osparge' class='tabla-container'>";
						echo "<tr><th>Total</th><th>RO</th><th>Temperatura</th><th>pH</th><th>Fosfórico</th><th>CaSO<sub>4</sub></th><th>CaCl<sub>2</sub></th><th>MgCl<sub>2</sub></th><th>Otro</th></tr>";
						while ($row = $result->fetch_assoc()) {
							echo "<tr>";
							echo "<td><input type='number' name='total_agua_sparge' value='" . $row['total_agua_sparge'] . "'></td>";
							echo "<td><input type='number' name='porcentaje_ro_sparge' value='" . $row['porcentaje_ro_sparge'] . "'></td>";
							echo "<td><input type='number' name='temperatura_sparge' value='" . $row['temperatura_sparge'] . "'></td>";
							echo "<td><input type='number' name='ph_sparge' value='" . $row['ph_sparge'] . "'></td>";							
							echo "<td><input type='number' name='fosforico_sparge' value='" . $row['fosforico_sparge'] . "'></td>";							
							echo "<td><input type='number' name='caso4_sparge' value='" . $row['caso4_sparge'] . "'></td>";							
							echo "<td><input type='number' name='cacl2_sparge' value='" . $row['cacl2_sparge'] . "'></td>";	
							echo "<td><input type='number' name='mgcl_sparge' value='" . $row['mgcl_sparge'] . "'></td>";	
							echo "<td><input type='number' name='otro_sparge' value='" . $row['otro_sparge'] . "'></td>";	
							echo "</tr>";
						}
						echo "</table>";

						
					} else {
						echo "No se encontraron datos.";
					}
					?>

				
				
				<hr>
				<h3>Maltas</h3>
				<?php
				
				// Realizar la conexión a la base de datos
				$servername = "localhost";
				$username = "rocko";
				$password = "Pepito11!";
				$dbname = "fabrica_cerveza";

				$conn = new mysqli($servername, $username, $password, $dbname);
				if ($conn->connect_error) {
					die("Error de conexión: " . $conn->connect_error);
				}
				// Consulta SQL para obtener los datos de recetasmalta para el id_receta proporcionado
				$sql = "SELECT rm.*, vm.*
						FROM recetasmalta AS rm
						JOIN variedades_malta AS vm ON rm.malta_id = vm.id
						WHERE rm.id_receta = $id_recetas_estilos";
				$resultMalta = $conn->query($sql);

				if ($resultMalta->num_rows > 0) {
					// Mostrar la tabla con 	la información de las maltas
					echo "<table border=1 id='maltas_table' name='maltastable' >";
					echo "<thead>";
					echo "<tr>";
					echo "<th>Variedad</th>";
					echo "<th>Nro. lote</th>";
					echo "<th>Cantidad</th>";
					echo "<th>Uso</th>"; 
					echo "</tr>";
					echo "</thead>";
					echo "<tbody>";
					while ($row = $resultMalta->fetch_assoc()) {
						echo "<tr>";
						
						$sql = "SELECT id, nombre, marca FROM variedades_malta";
						 
						//DROPLIST NOMBRES DE MALTAS
						$resultMaltas = $conn->query($sql);
						echo "<td>";
						// Si hay resultados, creamos el droplist
						if ($resultMaltas->num_rows > 0) {
							echo "<select id='malta" .$row['id']. "' name='malta[]'>";

							// Iteramos sobre los resultados y creamos una opción para cada malta
							while ($rowMalta = $resultMaltas->fetch_assoc()) {
								$selected = ($rowMalta['id'] == $row['id']) ? 'selected' : ''; // Verificamos si esta opción debe estar seleccionada
								echo "<option value='" . $rowMalta['id'] . "' $selected>" . $rowMalta['nombre']." (".$rowMalta['marca'].") ". "</option>";
							}

							echo "</select>";
						} else {
							echo "No hay maltas disponibles";
						}

						echo "</td>";
						
						
						
						//echo "<td><input type='text' name='marca[]' value='" . $row['marca'] . "' class='no-border' required></td>";
						echo "<td><input type='text' name='lote_malta[]' value='" . "#000000" . "'  required></td>";
						echo "<td><input type='text' pattern='\\d+(\\.\\d{1,2})?' title='Ingrese un número con el formato 0.00' name='cantidad[]' value='" . $row['cantidad'] . "'  required></td>";
						echo "<td><input type='text' name='tiempo[]' value='" . $row['tiempo'] . "' required></td>";
						echo "<td><button type='button' onclick='deleteRowMalta()' class='notacata-button'>Borrar</button></td>";

						echo "</tr>";
					}
						echo "</tbody>";
						echo "</table>";
						echo "<button type='button' onclick='addRowMalta()'class='notacata-button'>Añadir Fila</button>";
						echo "<!-- Botón para añadir nueva variedad de malta -->";
						echo "<button id='btnAgregarVariedad' class='notacata-button'>Añadir Variedad Malta</button>";


					} else {
						echo "No se encontraron maltas asociadas a este lote de cerveza.";
					}
					// Cerrar la conexión a la base de datos
		$conn->close();
		?>
				
				
                
                <hr>
                
                
                <h3>Lúpulo</h3>
		<?php
		// Realizar la conexión a la base de datos
		$servername = "localhost";
		$username = "rocko";
		$password = "Pepito11!";
		$dbname = "fabrica_cerveza";

		$conn = new mysqli($servername, $username, $password, $dbname);
		if ($conn->connect_error) {
			die("Error de conexión: " . $conn->connect_error);
		}

		// Consulta SQL para obtener los datos de recetaslupulo para el id_receta proporcionado
		// Consulta SQL para obtener los datos de recetasmalta para el id_receta proporcionado
		$sql = "SELECT rl.*, vl.*
				FROM recetaslupulo AS rl
				JOIN variedades_lupulo AS vl ON rl.lupulo_id = vl.id
				WHERE rl.id_receta = $id_recetas_estilos";

		$resultLupulo = $conn->query($sql);

		if ($resultLupulo->num_rows > 0) {
			// Mostrar la tabla con la información de los lúpulos
			echo "<table border=1 id='lupulo_table' name='lupulostable' >";
			echo "<thead>";
			echo "<tr>";
			echo "<th>Variedad</th>";
			echo "<th>Nro. lote</th>";
			echo "<th>Cantidad (g)</th>";
			echo "<th>IBU</th>";			
			echo "<th>Tiempo/Técnica</th>";
			echo "</tr>";
			echo "</thead>";
			echo "<tbody>";
			$counter = 1;
			while ($row = $resultLupulo->fetch_assoc()) {
				echo "<tr>";
				
				// Celda para el droplist de variedades de lúpulo
				$sql = "SELECT id, nombre, marca FROM variedades_lupulo";

				$resultLupulos = $conn->query($sql);
				echo "<td>";
				// Si hay resultados, creamos el droplist
				if ($resultLupulos->num_rows > 0) {
					echo "<select id='lupulo" .$counter. "' name='lupulo'>";

					// Iteramos sobre los resultados y creamos una opción para cada malta
					while ($rowLupulo = $resultLupulos->fetch_assoc()) {
						$selected = ($rowLupulo['id'] == $row['id']) ? 'selected' : ''; // Verificamos si esta opción debe estar seleccionada
						echo "<option value='" . $rowLupulo['id'] . "' $selected>" . $rowLupulo['nombre'] ." (".$rowLupulo['marca']. ") </option>";
					}
					$resultLupulos->data_seek(0);
					echo "</select>";
				} else {
					echo "No hay lupulos disponibles";
				}
				echo "</td>";
				
				
				// Celdas para los otros campos
				echo "<td><input type='text' name='lote_lupulo[]' value='" . "#000000" . "'  required></td>";
				echo "<td><input type='number' name='cantidad_lupulo[]' value='" . $row['cantidad'] . "'  required></td>";
				echo "<td><input type='text' pattern='\\d+(\\.\\d{1,2})?' name='ibu[]' value='" . $row['ibu'] . "'  required></td>";
				echo "<td><input type='text' name='tiempo_lupulo[]' value='" . $row['tiempo'] . "'  required></td>";
				echo "<td><button type='button' onclick='deleteRowLupulo()' class='notacata-button'>Borrar</button></td>";
				echo "</tr>";
				
				++$counter;
			}
			echo "</tbody>";
			echo "</table>";
			echo "<button type='button' onclick='addRowLupulo()' class='notacata-button'>Añadir Fila</button>";
			echo "<button id='btnAgregarVariedad' class='notacata-button'>Añadir Variedad Lúpulo</button>";

		} else {
			echo "No se encontraron lúpulos asociados a esta receta de cerveza.";
		}
		// Cerrar la conexión a la base de datos
		$conn->close();
		?>
		
		<hr>
		<h3>Levadura</h3>
		
		
		<?php
		// Realizar la conexión a la base de datos
		$servername = "localhost";
		$username = "rocko";
		$password = "Pepito11!";
		$dbname = "fabrica_cerveza";

		$conn = new mysqli($servername, $username, $password, $dbname);
		if ($conn->connect_error) {
			die("Error de conexión: " . $conn->connect_error);
		}
		// Consulta SQL para obtener los datos de recetaslupulo para el id_receta proporcionado
		// Consulta SQL para obtener los datos de recetasmalta para el id_receta proporcionado
		$sql = "SELECT rl.*, cl.*
				FROM recetaslevadura AS rl
				JOIN cepas_levadura AS cl ON rl.cepa_id = cl.id
				WHERE rl.id_receta = $id_recetas_estilos";

		$resultLeva = $conn->query($sql);
		
		if ($resultLeva->num_rows > 0) {
			echo "<table border=1 name='levaduratable' >";
				while ($row = $resultLeva->fetch_assoc()) {
				
				// Celda para el droplist de variedades de lúpulo
				$sqlLevas = "SELECT id, cepa, marca FROM cepas_levadura";

				$resultLevas = $conn->query($sqlLevas);
					echo "<tr><td>Cepa:</td><td>";
					// Si hay resultados, creamos el droplist
					if ($resultLevas->num_rows > 0) {
						echo "<select id='cepa' name='cepa'>";

						// Iteramos sobre los resultados y creamos una opción para cada malta
						while ($rowLeva = $resultLevas->fetch_assoc()) {
							$selected = ($rowLeva['id'] == $row['id']) ? 'selected' : ''; // Verificamos si esta opción debe estar seleccionada
							echo "<option value='" . $rowLeva['id'] . "' $selected>" . $rowLeva['cepa'] ." (".$rowLeva['marca']. ") </option>";
						}
						$resultLevas->data_seek(0);
						echo "</select>";
					} else {
						echo "No hay cepas disponibles";
					}
					echo "</td></tr>";
					

					echo "<tr><td>Generación</td><td>";
					echo "<input type='text' name='genleva[]' value='" . "#000000" . "'  required></td>";
					echo "</tr>";
					
					//echo "<td><input type='number' name='cantidad[]' value='" . $row['cantidad'] . "' class='no-border' required></td>";

					echo "<tr><td>Temperatura de inoculación</td><td>";
					echo "<input type='text' pattern='\\d+(\\.\\d{1,2})?' name='tempInoc[]' value='" . $row['temp_inoculacion'] . "'  required></td></tr>";
					echo "<tr><td>Tasa de inoculación</td><td>";
					echo "<input type='text' pattern='\\d+(\\.\\d{1,2})?' name='tasaInoc[]' value='" . $row['tasa_inoculacion'] . "'  required></td></tr>";
					echo "<tr><td>Viabilidad</td><td>";
					echo "<input type='number' name='viabilidad[]' value='" . $row['viabilidad'] . "'  required></td></tr>";
					echo "<tr><td>Kilos de biomasa</td><td>";
					echo "<input type='text' pattern='\\d+(\\.\\d{1,2})?' name='biomasa[]' value='" . $row['kilos_biomasa'] . "'  required></td></tr>";
					echo "<tr><td>PPM Oxígeno</td><td>";
					echo "<input type='number' name='biomasa[]' value='" . $row['oxigenacion'] . "'  required></td></tr>";					
					
					
			
				echo "</table>";
			}
			echo "<button id='btnAgregarVariedad' class='notacata-button'>Añadir Cepa</button>";
			echo "<hr>";

		} else {
			echo "No se encontraró una cepa de levadura asociada a esta receta de cerveza.";
		}
		// Cerrar la conexión a la base de datos
		$conn->close();

		?>
		
		<?php
			
			echo "<h3> LOG día de Cocción</h3>";
			echo "<table  class='tabla-container' border=1 id='tablaLOG' name='tablaLOG' >";
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
			echo "</tr>";

			echo "<tr>";
			echo "<td><p name='batch1'></p>1</td>";
			echo "<td><input type='number' name='temp_mash' required></td>";
			echo "<td><input type='text' pattern='\\d+(\\.\\d{1,2})?' name='ph_mash' required></td>";
			echo "<td><input type='text' title='Ingrese un número con el formato 0.000' pattern='\d+(\.\d{3})?' name='dens_primer_mosto' required></td>";
			echo "<td><input type='text' title='Ingrese un número con el formato 0.000' pattern='\d+(\.\d{3})?' name='dens_last_run' required></td>";
			echo "<td><input type='text' pattern='\\d+(\\.\\d{1,2})?' name='ph_last_run' required></td>";
			echo "<td><input type='number' name='temp_sparge' required></td>";
			echo "<td><input type='text' pattern='\\d+(\\.\\d{1,2})?' name='ph_sparge' required></td>";
			echo "<td><input type='number' name='vol_inicial_boil' required></td>";
			echo "<td><input type='text' title='Ingrese un número con el formato 0.000' pattern='\d+(\.\d{3})?' name='dens_pre_boil' required></td>";
			echo "<td><input type='text' pattern='\\d+(\\.\\d{1,2})?' name='ph_inicio_boil' required></td>";
			echo "<td><input type='number' name='vol_final_boil' required></td>";
			echo "<td><input type='text' title='Ingrese un número con el formato 0.000' pattern='\d+(\.\d{3})?' name='dens_post_boil' required></td>";
			echo "<td><input type='text' pattern='\\d+(\\.\\d{1,2})?' name='ph_fin' required></td>";

			echo "</tr>";
			echo "</table>";
			
			echo "<button type='button' onclick='addRowLOG()' class='notacata-button'>Añadir Fila</button>";
			echo "<td><button type='button' onclick='deleteRowLog()' class='notacata-button'>Borrar última fila</button></td>";

			
			
			echo "<hr>";			
			echo "<h3>Seguimiento Fermentación</h3>";
			echo "<table  class='tabla-container' border=1 id='tablaFerm'>";
			echo "<thead>";
			echo "<tr>";
			echo "<th>#</th>";
			echo "<th>Fecha</th>";
			echo "<th>Hora</th>";
			echo "<th>Densidad	</th>";
			echo "<th>pH</th>";
			echo "<th>Temperatura</th>";
			echo "<th>Purga</th>";
			echo "<th>Comentarios</th>";
			echo "</tr>";

			echo "<tr>";
			echo "<td><p name='dia1'></p>1</td>";
			echo "<td><input type='date' name='fecha' required></td>";
			echo "<td><input type='time' name='hora' required></td>";
			echo "<td><input type='text' title='Ingrese un número con el formato 0.000' pattern='\\d+(\\.\\d{3})?' name='densidad' required></td>";
			echo "<td><input type='text' pattern='\\d+(\\.\\d{1,2})?' name='ph' required></td>";
			echo "<td><input type='text' pattern='\\d+(\\.\\d{1,2})?' name='temperatura' required></td>";
			echo "<td><input type='number' name='purga' required></td>";
			echo "<td><input type='text' name='comentarios' required></td>";
			
			echo "</tr>";
			echo "</table>";
			
			echo "<button type='button' onclick='addRowFerm()' class='notacata-button'>Añadir Fila</button>";
			echo "<td><button type='button' onclick='deleteRowFerm()' class='notacata-button'>Borrar última fila</button></td>";
			
			echo "<hr>";			
			echo "<h3>Lecturas finales y envasado</h3>";
			echo "<table border='1'>";
			echo "<tr><td><strong>DO</strong></td><td><input type='text' title='Ingrese un número con el formato 0.000' pattern='\d+(\.\d{3})?' name='lecturaDO' required></td></tr>";
			echo "<tr><td><strong>DF</strong></td><td><input type='text' title='Ingrese un número con el formato 0.000' pattern='\d+(\.\d{3})?' name='lecturaDF' required></td></tr>";
			echo "<tr><td><strong>pH Inicial</strong><td><input type='text' pattern='\\d+(\\.\\d{1,2})?' name='ph_inicialMosto' required></td></tr>";
			echo "<tr><td><strong>pH Final</strong></td><td><input type='text' pattern='\\d+(\\.\\d{1,2})?' name='ph_finFerm' required></td></tr>";
			echo "<tr><td><strong>Litros a Fermentador</strong></td><td><input type='number' name='litrosAfermentar' required></td></tr>";
			echo "<tr><td><strong>Día de Envasado</strong></td><td><input type='date' name='diaEnvasado' required></td></tr>";
			echo "<tr><td><strong>Carb. Level</strong></td><td><input type='text' pattern='\\d+(\\.\\d{1,2})?' name='carbLevel' required></td></tr>";
			echo "<tr><td><strong>Litros Envasados</strong></td><td><input type='number' name='ltsEnvasados' required></td></tr>";
			echo "</table>";

			echo "<hr>";	
			echo "<h3>Detalle día de Enlatado</h3>";
			echo "<table border='1'>";
			echo "<tr><td><strong>Día de Enlatado</strong></td><td><input type='date' name='diaEnlatado'></td></tr>";
			echo "</table>";
			
			echo "<h4>Configuración enlatadora</h4>";
			echo "<table border='1'>";
			echo "<tr><td><strong>Presión Barrido</strong></td><td><input type='text' pattern='\\d+(\\.\\d{1,2})?'name='presionbarrido'></td></tr>";			
			echo "<tr><td><strong>Presión en línea de llenado</strong></td><td><input type='text' pattern='\\d+(\\.\\d{1,2})?'name='presionenenlatadora'></td></tr>";	
			echo "<tr><td><strong>Presión en Tanque</strong></td><td><input type='text' pattern='\\d+(\\.\\d{1,2})?'name='presionentanque'></td></tr>";					
			echo "<tr><td><strong>Tiempo llenado </strong></td><td><input type='number' name='tiempollenado'></td></tr>";
			echo "<tr><td><strong>Tiempo barrido 1</strong></td><td><input type='number' name='tiempo1'></td></tr>";
			echo "<tr><td><strong>Tiempo barrido 2</strong></td><td><input type='number' name='tiempo2'></td></tr>";
			echo "<tr><td><strong>Temperatura en Tanque</strong></td><td><input type='text' pattern='\\d+(\\.\\d{1,2})?'name='tempentanque'></td></tr>";
			echo "<tr><td><strong>Temperatura Cerveza en Enlatadora</strong></td><td><input type='text' pattern='\\d+(\\.\\d{1,2})?'name='tempenenlatadora'></td></tr>";
			echo "<tr><td><strong>Temperatura Ambiente</strong></td><td><input type='text' pattern='\\d+(\\.\\d{1,2})?'name='tempambiente'></td></tr>";
			echo "<tr><td><strong>Observaciones</strong></td><td><input type='text'name='observacionesenlatado' ></td></tr>";

			echo "</table>";
			
			echo "<h4>Resultados enlatado</h4>";
			echo "<table border='1'>";			
			echo "<tr><td><strong>DO</strong></td><td><input type='number' name='disoxigen'></td></tr>";
			echo "<tr><td><strong>TPO</strong></td><td><input type='number' name='tpo'></td></tr>";
			echo "<tr><td><strong>Latas cerradas descartadas</strong></td><td><input type='number'name='latascerradasDes' ></td></tr>";
			echo "<tr><td><strong>Latas vacías desechadas</strong></td><td><input type='number' name='latasvaciasDes' ></td></tr>";
			echo "<tr><td><strong>Tapas desechadas</strong></td><td><input type='number' name='tapasDes' ></td></tr>";
			echo "<tr><td><strong>Latas cerradas OK</strong></td><td><input type='number' name='latasOK' ></td></tr>";
			echo "</table>";
			

			echo "<hr>";
			
			echo "<h3>Comentarios</h3>";
			echo "<textarea name='comentariosGeneral' id='comentariosInput' rows='4'></textarea>";

			echo"<br>
			<br>
			<br>
			<button type='submit' class='submit'onclick='return confirm('¿Estás seguro de que deseas guardar los cambios?')'>Guardar</button>
            </form>";

		
		?>
		

<script>
	
	function addRowFerm(){
	  var table = document.getElementById("tablaFerm");
	  var rowCount = table.rows.length;
	  var row = table.insertRow(rowCount);

	  // Incrementar el número de lote para la nueva fila
	  var batchCell = row.insertCell(0);
	  batchCell.innerHTML = "<p name='dia"+rowCount+"'>" + rowCount + "</p>";

	  var batchCell = row.insertCell(1);
	  batchCell.innerHTML = "<input type='date' name='fecha" + rowCount + "' required>";
	  
	  var batchCell = row.insertCell(2);
	  batchCell.innerHTML = "<input type='time' name='hora" + rowCount + "' required>";
	  
	  var batchCell = row.insertCell(3);
	  batchCell.innerHTML = "<td><input type='text' title='Ingrese un número con el formato 0.000' pattern='\\d+(\\.\\d{3})?'   name='densidad" + rowCount + "' required>";

	  var batchCell = row.insertCell(4);
	  batchCell.innerHTML = "<input type='text' pattern='\\d+(\\.\\d{1,2})?' name='ph" + rowCount + "' required>";
	
	  var batchCell = row.insertCell(5);
	  batchCell.innerHTML = "<input type='text' pattern='\\d+(\\.\\d{1,2})?' name='temperatura" + rowCount + "' required>";
	  
	  var batchCell = row.insertCell(6);
	  batchCell.innerHTML = "<input type='number' name='purga" + rowCount + "' required>";

	  var batchCell = row.insertCell(7);
	  batchCell.innerHTML = "<input type='text' name='comentarios" + rowCount + "' required>";
		
	}
	
	
	function deleteRowFerm(){
		if (confirm("¿Estás seguro de que deseas borrar esta fila?")) {
			var table = document.getElementById("tablaFerm");
			var rowCount = table.rows.length;

			if (table.rows.length > 2) { // Evitar borrar la fila de encabezado y una sola fila
				table.deleteRow(rowCount - 1); // Borra la última fila
			}

		}
	}
	
	function addRowLOG() {
		var table = document.getElementById("tablaLOG");
		var rowCount = table.rows.length;
		var row = table.insertRow(rowCount);

		// Incrementar el número de lote para la nueva fila
		var batchCell = row.insertCell(0);
		batchCell.innerHTML = "<p name='batch"+rowCount+"'>" + rowCount + "</p>";

		var columnNames = ["temp_mash", "ph_mash", "dens_primer_mosto", "dens_last_run", "ph_last_run", "temp_sparge", "ph_sparge", "vol_inicial_boil", "dens_pre_boil", "ph_inicio_boil", "vol_final_boil", "dens_post_boil", "ph_fin"];

		for (var i = 1; i < 14; i++) {
			var cell = row.insertCell(i);
			if(i == 3 || i == 4 || i == 9 || i == 12){
				cell.innerHTML = "<input type='text' title='Ingrese un número con el formato 0.000'+ pattern='\\d+(\\.\\d{3})?'  name='" + columnNames[i - 1] + "' required>";
			} else if(i == 2 || i == 5 || i == 7 || i == 10 || i == 13){
				 cell.innerHTML = "<input type='text' pattern='\\d+(\\.\\d{1,2})?' name='" + columnNames[i - 1] + "' required>";
			 }else{
				cell.innerHTML = "<input type='number' name='" + columnNames[i - 1] + "' required>";
			}
		}	
	}
		
	
	
	function deleteRowLog(){
		if (confirm("¿Estás seguro de que deseas borrar esta fila?")) {
			var table = document.getElementById("tablaLOG");
			var rowCount = table.rows.length;

			if (table.rows.length > 2) { // Evitar borrar la fila de encabezado y una sola fila
				table.deleteRow(rowCount - 1); // Borra la última fila
			}

		}
	}
    
    function addRowLupulo() {
        var table = document.getElementById("lupulo_table");
        var newRow = table.insertRow(-1);
		
		// Código para agregar el droplist de nombres de lupulo
		var lupulosDropdownCell = newRow.insertCell(-1);
		var lupulosDropdown = document.createElement("select");
		lupulosDropdown.name = "lupulo[]";
		// Agregar opciones al droplist
		<?php
		$resultLupulos->data_seek(0);

		if ($resultLupulos->num_rows > 0) {
			while ($rowVariedadLupulo = $resultLupulos->fetch_assoc()) {
				echo "var option = document.createElement('option');";
				echo "option.value = '" . $rowVariedadLupulo['id'] . "';";
				echo "option.text = '" . $rowVariedadLupulo['nombre']." (".$rowVariedadLupulo['marca'] . ") ';";
				echo "lupulosDropdown.appendChild(option);";
			}
			// Reiniciar el puntero de resultados
			$resultLupulos->data_seek(0);
		} else {
			echo "var option = document.createElement('option');";
			echo "option.disabled = true;";
			echo "option.text = 'No hay lupulos disponibles';";
			echo "lupulosDropdown.appendChild(option);";
		}
		?>
		lupulosDropdownCell.appendChild(lupulosDropdown);

        var loteCell = newRow.insertCell(-1);
        loteCell.innerHTML = "<input type='text' name='lote_lupulo[]'  value='#000000' required>";
        
        var cantidadCell = newRow.insertCell(-1);
        cantidadCell.innerHTML = "<input type='number' name='cantidad_lupulo[]'  required>";

        var ibuCell = newRow.insertCell(-1);
        ibuCell.innerHTML = "<input type='text' pattern='\\d+(\\.\\d{1,2})?' name='ibu[]'  required>";

        var tiempoCell = newRow.insertCell(-1);
        tiempoCell.innerHTML = "<input type='text' name='tiempo_lupulo[]'  required>";
        
		var borrarCell = newRow.insertCell(-1);
		borrarCell.innerHTML ="<td><button type='button' onclick='deleteRowLupulo()' class='notacata-button'>Borrar</button></td>";
    }
    
    
	function deleteRowLupulo() {
		if (confirm("¿Estás seguro de que deseas borrar esta fila?")) {
			var table = document.getElementById("lupulo_table");
			if (table.rows.length > 2) { // Evitar borrar la fila de encabezado y una sola fila
				table.deleteRow(-1);
			}
		}
	}
        
	var textarea = document.getElementById("comentariosInput");

    // Agrega un evento de escucha para detectar cuando se presiona una tecla
    textarea.addEventListener("keydown", function(event) {
        // Verifica si la tecla presionada es "Enter" (código 13)
        if (event.keyCode === 13) {
            // Previene el comportamiento predeterminado de la tecla "Enter"
            event.preventDefault();
            
            // Agrega una nueva línea al texto en el textarea
            var currentCursorPosition = textarea.selectionStart;
            var textBeforeCursor = textarea.value.substring(0, currentCursorPosition);
            var textAfterCursor = textarea.value.substring(currentCursorPosition);
            textarea.value = textBeforeCursor + "\n" + textAfterCursor;
            
            // Ajusta la posición del cursor
            textarea.setSelectionRange(currentCursorPosition + 1, currentCursorPosition + 1);
        }
    });
          
	function addRowMalta() {
		var table = document.getElementById("maltas_table");
		var newRow = table.insertRow(-1);

		// Código para agregar el droplist de nombres de malta
		var maltasDropdownCell = newRow.insertCell(-1);
		var maltasDropdown = document.createElement("select");
		maltasDropdown.name = "malta[]";
		count = table.rows.length-1;
		maltasDropdown.id = "'malta" + count + "'";
		// Agregar opciones al droplist
		<?php
			
		$resultMalta->data_seek(0);
		if ($resultMalta->num_rows > 0) {
			while ($rowVariedadMalta = $resultMalta->fetch_assoc()) {
				echo "var option = document.createElement('option');";
				echo "option.value = '" . $rowVariedadMalta['id'] . "';";
				echo "option.text = '" . $rowVariedadMalta['nombre'] ." (".$rowVariedadMalta['marca'] . ") ';";
				echo "maltasDropdown.appendChild(option);";
			}
			// Reiniciar el puntero de resultados
			$resultMalta->data_seek(0);
		} else {
			echo "var option = document.createElement('option');";
			echo "option.disabled = true;";
			echo "option.text = 'No hay maltas disponibles';";
			echo "maltasDropdown.appendChild(option);";
		}
		?>
		maltasDropdownCell.appendChild(maltasDropdown);

		// Resto de las celdas de la fila
		var loteCell = newRow.insertCell(-1);
		loteCell.innerHTML = "<input type='text' name='lote_malta[]' value='#000000'  required>";

		var cantidadCell = newRow.insertCell(-1);
		cantidadCell.innerHTML = "<input type='text' pattern='\\d+(\\.\\d{1,2})?' title='Ingrese un número con el formato 0.00' name='cantidad[]'  required>";

		var tiempoCell = newRow.insertCell(-1);
		tiempoCell.innerHTML = "<input type='text' name='tiempo[]' required>";

		var deleteButtonCell = newRow.insertCell(-1);
		var deleteButton = document.createElement("button");
		deleteButton.type = "button";
		deleteButton.textContent = "Borrar";
		deleteButton.classList.add('notacata-button');
		deleteButton.onclick = function() {
			deleteRowMalta();
		};
		deleteButtonCell.appendChild(deleteButton);
	}
	function deleteRowMalta() {
		if (confirm("¿Estás seguro de que deseas borrar esta fila?")) {
			var table = document.getElementById("maltas_table");
			if (table.rows.length > 2) { // Evitar borrar la fila de encabezado y una sola fila
				table.deleteRow(-1);
			}
		}
	}
	
        
</script>
</div>
</main>
<footer>
<p>&copy; 2024 Bialystok Brewing CO SAS. Todos los derechos reservados.</p>
</footer>
</body>
</html>
