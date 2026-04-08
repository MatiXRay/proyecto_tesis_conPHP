<?php
include('conexion.php');

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commentId = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $newComment = isset($_POST['comentario']) ? $conn->real_escape_string($_POST['comentario']) : '';

    // Asegúrate de que $commentId sea un valor válido
    if ($commentId > 0) {
        $sql = "UPDATE lotes_cerveza SET comentarios='$newComment' WHERE id=$commentId";

        if ($conn->query($sql) === TRUE) {
            echo "Comentario actualizado correctamente";
        } else {
            echo "Error actualizando el comentario: " . $conn->error;
        }
    }
}

$conn->close();
?>
