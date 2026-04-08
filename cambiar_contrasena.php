<?php
// Conexión a la base de datos
require 'conexion.php';

// Obtener datos del cuerpo de la solicitud POST
$data = json_decode(file_get_contents("php://input"));

$userId = $data->userId;
$adminId = $data->adminId;
$adminPassword = $data->adminPassword;
$newPassword = $data->newPassword;

// Obtener la contraseña del administrador de la base de datos
$sql = "SELECT password FROM users WHERE id = ? AND rol_id = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $adminPasswordHash = $row["password"];

    // Verificar la contraseña del administrador
    if (password_verify($adminPassword, $adminPasswordHash)) {
        // La contraseña del administrador es correcta, procede con el cambio de contraseña del usuario
        // Hashear la nueva contraseña antes de guardarla
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Actualizar la contraseña del usuario en la base de datos
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $hashedPassword, $userId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Contraseña cambiada con éxito']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al cambiar la contraseña']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'La contraseña del administrador es incorrecta']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error al obtener la contraseña del administrador']);
}

// Cerrar la conexión a la base de datos
$conn->close();
?>
