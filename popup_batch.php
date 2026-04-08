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

// Obtener el ID del batch del lote de cerveza desde la URL
$batch_id = $_GET['batch_id'];

// Consulta para obtener los detalles del batch de cerveza
$sql = "SELECT * FROM batches WHERE id = $batch_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Mostrar los detalles del batch de cerveza
    $row = $result->fetch_assoc();
    echo "<h2>Detalles del Batch de Cerveza</h2>";
    echo "<p><strong>Batch N°:</strong> {$row['id']}</p>";
    echo "<p><strong>Temp Mash:</strong> {$row['temp_mash']}</p>";
    echo "<p><strong>PH Mash:</strong> {$row['ph_mash']}</p>";
    echo "<p><strong>Dens 1 Mosto:</strong> {$row['dens_1_mosto']}</p>";
    echo "<p><strong>Dens Last Run:</strong> {$row['dens_last_run']}</p>";
    echo "<p><strong>PH Last Run:</strong> {$row['ph_last_run']}</p>";
    echo "<p><strong>Temp Sparge:</strong> {$row['temp_sparge']}</p>";
    echo "<p><strong>PH Sparge:</strong> {$row['ph_sparge']}</p>";
    echo "<p><strong>Vol Inicial Boil:</strong> {$row['vol_inicial_boil']}</p>";
    echo "<p><strong>Dens Pre Boil:</strong> {$row['dens_pre_boil']}</p>";
    echo "<p><strong>PH Inicio Boil:</strong> {$row['ph_inicio_boil']}</p>";
    echo "<p><strong>Volumen Final Boil:</strong> {$row['volumen_final_boil']}</p>";
    echo "<p><strong>Dens Post Boil:</strong> {$row['dens_post_boil']}</p>";
    echo "<p><strong>PH Fin:</strong> {$row['ph_fin']}</p>";
} else {
    echo "No se encontraron detalles para este batch de cerveza.";
}

// Cerrar conexión a la base de datos
$conn->close();
?>
