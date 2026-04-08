<?php
// Incluir el archivo de conexión a la base de datos
session_start();
include 'conexion.php';

// Verificar si se recibieron los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener el ID del usuario de la sesión
    $id_usuario = $_SESSION['id'];
    // Obtener los datos del formulario
    $nombre = ucfirst(strtolower($_POST['nombre']));
    $apellido =  ucfirst(strtolower($_POST['apellido']));
    $mail = strtolower($_POST['mail']);
    $telefono = $_POST['telefono'];
    
    // Preparar la consulta SQL usando sentencias preparadas
    $sql = "UPDATE users SET nombre=?, apellido=?, mail=?, telefono=? WHERE id=?";

    // Preparar la declaración y vincular los parámetros
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $nombre, $apellido, $mail, $telefono, $id_usuario);

    // Ejecutar la consulta
    if ($stmt->execute()) {
        // Si la actualización fue exitosa
        echo json_encode(array("success" => true));
    } else {
        // Si ocurrió un error durante la actualización
        echo json_encode(array("success" => false, "message" => "Error al actualizar los datos: " . $conn->error));
    }

    // Cerrar la declaración
    $stmt->close();
}

// Cerrar la conexión a la base de datos
$conn->close();
?>
