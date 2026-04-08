<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fabrica de Cerveza - Notas de Cata</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <h1>Detalle Envasado</h1>
    <div class="container">
        <?php
        // Verificar si se proporcionó un ID de lote válido
        if (isset($_GET['lote_id']) && !empty($_GET['lote_id'])) {
            // Obtener el ID del lote de cerveza desde el parámetro GET
            $lote_id = $_GET['lote_id'];

			// Establecer las credenciales de la base de datos
			include 'conexion.php';

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
				echo "<p><strong>Cerveza: </strong> $nombre_estilo</p>";
				echo "<p><strong>Número de Lote: </strong> $numero_lote</p>";
				
             }

// Consulta SQL para seleccionar la fila específica utilizando el campo id_lote
$sql = "SELECT * FROM lotesenlatado WHERE id_lote = $lote_id";

$result = $conn->query($sql);

// Verificar si se encontraron resultados
if ($result->num_rows > 0) {
    // Mostrar los datos en una tabla HTML
    echo "<div id='detallesEnvasado'>";
    echo "<table border='1'>";
    // Mostrar los datos de la fila seleccionada
    while($row = $result->fetch_assoc()) {
		
		echo "<p><strong>Día Enlatado: </strong>" . date('d/m/Y', strtotime($row["diaEnlatado"])) . "</p>";
		echo "<hr><br>";
		
		echo "<p style='text-align: center;'><strong>CONFIGURACIONES</strong></p>";
		echo "<table border='1'>";
		echo "<tr><td><strong>Presión de Barrido</strong></td><td>" . $row["presionbarrido"] . "</td></tr>";
		echo "<tr><td><strong>Presión en Enlatadora</strong></td><td>" . $row["presionenenlatadora"] . "</td></tr>";
		echo "<tr><td><strong>Presión en Tanque</strong></td><td>" . $row["presionentanque"] . "</td></tr>";
		echo "<tr><td><strong>Tiempo de Llenado</strong></td><td>" . $row["tiempollenado"] . "</td></tr>";
		echo "<tr><td><strong>Tiempo 1</strong></td><td>" . $row["tiempo1"] . "</td></tr>";
		echo "<tr><td><strong>Tiempo 2</strong></td><td>" . $row["tiempo2"] . "</td></tr>";
		echo "<tr><td><strong>Temperatura en Tanque</strong></td><td>" . $row["tempentanque"] . "</td></tr>";
		echo "<tr><td><strong>Temperatura en Enlatadora</strong></td><td>" . $row["tempenenlatadora"] . "</td></tr>";
		echo "<tr><td><strong>Temperatura Ambiente</strong></td><td>" . $row["tempambiente"] . "</td></tr>";
		echo "<tr><td><strong>Observaciones de Enlatado</strong></td><td>" . $row["observacionesenlatado"] . "</td></tr>";
		echo "</table><br>";
		
		echo "<p style='text-align: center;'><strong>RESULTADOS</strong></p><br>";
		echo "<table border='1'>";
		echo "<tr><td><strong>DO</strong></td><td>" . $row["disoxigen"] . "</td></tr>";
		echo "<tr><td><strong>TPO</strong></td><td>" . $row["tpo"] . "</td></tr>";
		echo "<tr><td><strong>Latas OK</strong></td><td>" . $row["latasOK"] . "</td></tr>";
		echo "<tr><td><strong>Latas Descartadas por Peso</strong></td><td>" . $row["latascerradasDes"] . "</td></tr>";
		echo "<tr><td><strong>Latas Vacías Descartadas</strong></td><td>" . $row["latasvaciasDes"] . "</td></tr>";
		echo "<tr><td><strong>Tapas Descartadas</strong></td><td>" . $row["tapasDes"] . "</td></tr>";
		echo "</table>";

    }
} else {
    echo "No se encontró ningún registro con el ID de lote proporcionado.";
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
