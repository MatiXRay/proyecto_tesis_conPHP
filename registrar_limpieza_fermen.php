<?php
// anadir_registro.php

// Recibir los datos del formulario
$data = json_decode(file_get_contents('php://input'), true);
$tabla = $data['tabla'] ?? '';
$alcalina = isset($data['alcalina']) ? (bool)$data['alcalina'] : false;
$acida = isset($data['acida']) ? (bool)$data['acida'] : false;
$oxidativa = isset($data['oxidativa']) ? (bool)$data['oxidativa'] : false;
$exterior = isset($data['exterior']) ? (bool)$data['exterior'] : false;
$date = $data['date'] ?? '';
$fermentador = $data['id'] ?? '';

// Conexión a la base de datos
require('conexion.php');

try {
	// Preparar las consultas y ejecutar según los checkboxes
	$conn->begin_transaction();

	if ($alcalina) {
		$sqlAlcalina = "UPDATE fermentadores SET LIMP_ALCALINA_DATE = ? WHERE ID = ?";
		$stmtAlcalina = $conn->prepare($sqlAlcalina);
		$stmtAlcalina->bind_param("si", $date, $fermentador);
		$stmtAlcalina->execute();
	}

	if ($acida) {
		$sqlAcida = "UPDATE fermentadores SET LIMP_ACIDA_DATE = ? WHERE ID = ?";
		$stmtAcida = $conn->prepare($sqlAcida);
		$stmtAcida->bind_param("si", $date, $fermentador);
		$stmtAcida->execute();
	}

	if ($oxidativa) {
		$sqlOxidativa = "UPDATE fermentadores SET LIMP_OXIDATIVA_DATE = ? WHERE ID = ?";
		$stmtOxidativa = $conn->prepare($sqlOxidativa);
		$stmtOxidativa->bind_param("si", $date, $fermentador);
		$stmtOxidativa->execute();
	}

	if ($exterior) {
		$sqlExterior = "UPDATE fermentadores SET LIMP_EXTERIOR_DATE = ? WHERE ID = ?";
		$stmtExterior = $conn->prepare($sqlExterior);
		$stmtExterior->bind_param("si", $date, $fermentador);
		$stmtExterior->execute();
	}

	$conn->commit();

	echo json_encode(['success' => true]);
} catch (Exception $e) {
	$conn->rollback();
	echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
	$conn->close();

}
?>
