<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fabrica de Cerveza - Notas de Cata</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Notas de Cata</h1>
    <div class="container">
        <?php
        // Verificar si se proporcionó un ID de lote válido
        if (isset($_GET['lote_id']) && !empty($_GET['lote_id'])) {
            // Obtener el ID del lote de cerveza desde el parámetro GET
            $lote_id = $_GET['lote_id'];

			// Conexión a la base de datos
				$servername = "localhost";
				$username = "rocko";
				$password = "Pepito11!";
				$dbname = "fabrica_cerveza";
				
            $conn = new mysqli($servername, $username, $password, $dbname);

            // Verificar la conexión
            if ($conn->connect_error) {
                die("Error de conexión: " . $conn->connect_error);
            }
            
			// Consulta para obtener el estilo y el número de lote
            $sql_lote = "SELECT estilo_id, numero_lote FROM lotes_cerveza WHERE id = $lote_id";
            $result_lote = $conn->query($sql_lote);

			if ($result_lote->num_rows > 0) {
                $row_lote = $result_lote->fetch_assoc();
                $estilo_id = $row_lote['estilo_id'];
                $numero_lote = $row_lote['numero_lote'];

                // Obtener el nombre del estilo de cerveza
                $sql_estilo = "SELECT nombre FROM estilos_cerveza WHERE id = $estilo_id";
                $result_estilo = $conn->query($sql_estilo);
                $row_estilo = $result_estilo->fetch_assoc();
                $nombre_estilo = $row_estilo['nombre'];
			
				// Mostrar el título centrado
				// Mostrar el estilo y el número de lote
				echo "<p><strong>Cerveza:</strong> $nombre_estilo</p>";
				echo "<p><strong>Número de Lote:</strong> $numero_lote</p>";
				echo "<hr>";

             }

            // Consulta para obtener las notas de cata del lote de cerveza
            $sql = "SELECT * FROM notas_cata WHERE lote_id = $lote_id";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                // Mostrar las notas de cata del lote de cerveza
                while ($row = $result->fetch_assoc()) {
                   echo "<div class='nota-cata'>";
					echo "<p><strong>Catador:</strong> " . $row['nombre_catador'] . "</p>";
					echo "<p><strong>Fecha de Cata:</strong> " . date("d/m/Y", strtotime($row['fecha'])) . "</p>";
					echo "<p><strong>Aroma:</strong> " . $row['aroma'] . " (Puntaje: " . $row['puntaje_aroma'] . ")</p>";
					echo "<p><strong>Apariencia:</strong> " . $row['apariencia'] . " (Puntaje: " . $row['puntaje_apariencia'] . ")</p>";
					echo "<p><strong>Sabor:</strong> " . $row['sabor'] . " (Puntaje: " . $row['puntaje_sabor'] . ")</p>";
					echo "<p><strong>Sensación en Boca:</strong> " . $row['sensacion_boca'] . " (Puntaje: " . $row['puntaje_sensacion_boca'] . ")</p>";
					echo "<p><strong>Impresión General:</strong> " . $row['impresion_general'] . " (Puntaje: " . $row['puntaje_impresion_general'] . ")</p>";
					// Agregar botón para eliminar la nota de cata
					echo "<form action='eliminar_nota_cata.php' method='POST'>";
					echo "<input type='hidden' name='nota_id' value='" . $row['id'] . "' />";
					echo "<button type='submit'>Eliminar Nota de Cata</button>";
					echo "</form>";
					echo "</div>";
					echo "<hr>";
                }
            } else {
                echo "<p>No hay notas de cata para este lote de cerveza.</p>";
            }

            // Cerrar conexión a la base de datos
            $conn->close();
        } else {
            echo "<p>No se proporcionó un ID de lote válido.</p>";
        }
        ?>
    </div>
</body>
</html>
