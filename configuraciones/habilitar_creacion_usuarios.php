<?php
// Conexión a la base de datos
require('../conexion.php');

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener estado
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT creacion_usuarios FROM configuraciones WHERE id = 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $estado = $row["creacion_usuarios"];
        echo json_encode(["estado" => $estado]);
    } else {
        echo json_encode(["estado" => null]);
    }
}

// Cambiar estado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $estado = $data["estado"];

    $sql = "UPDATE configuraciones SET creacion_usuarios = $estado WHERE id = 1";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(["mensaje" => "Estado cambiado con éxito"]);
    } else {
        echo json_encode(["error" => "Error al cambiar el estado: " . $conn->error]);
    }
}

$conn->close();
?>
