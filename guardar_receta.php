<?php
// LOS DETALLES DE UN NUEVO LOTE AÑADIDO SE GUARDAN AQUI


if ($_SERVER["REQUEST_METHOD"] == "POST") {
	
	require_once 'conexion.php';

	if ($conn->connect_error) {
		die("Error de conexión: " . $conn->connect_error);
	}


    // Verifica si se han enviado datos de la tabla
	$loteID = $_POST['loteid'];
	
	$envasadosOK = 0;

	
		  //*********************************//
	 // TABLA LOTES ENVASADO  Y ENLATADO//
	//*********************************//
    if(isset($_POST['checkboxanadirdetalles'])){
		// Verificar si se han enviado datos del formulario
		if (
			isset($_POST["diaEnlatado"]) &&
			isset($_POST["presionbarrido"]) &&
			isset($_POST["presionenenlatadora"]) &&
			isset($_POST["presionentanque"]) &&
			isset($_POST["tiempollenado"]) &&
			isset($_POST["tiempo1"]) &&
			isset($_POST["tiempo2"]) &&
			isset($_POST["tempentanque"]) &&
			isset($_POST["tempenenlatadora"]) &&
			isset($_POST["tempambiente"]) &&
			isset($_POST["observacionesenlatado"]) &&
			isset($_POST["disoxigen"]) &&
			isset($_POST["tpo"]) &&
			isset($_POST["latascerradasDes"]) &&
			isset($_POST["latasvaciasDes"]) &&
			isset($_POST["tapasDes"]) &&
			isset($_POST["latasOK"])
			
		) {
			// Obtener los datos de las variables
			
			$diaEnlatado = date('Y-m-d', strtotime($_POST["diaEnlatado"]));
			$presionbarrido = $_POST["presionbarrido"];
			$presionenenlatadora = $_POST["presionenenlatadora"];
			$presionentanque = $_POST["presionentanque"];
			$tiempollenado = $_POST["tiempollenado"];
			$tiempo1 = $_POST["tiempo1"];
			$tiempo2 = $_POST["tiempo2"];
			$tempentanque = $_POST["tempentanque"];
			$tempenenlatadora = $_POST["tempenenlatadora"];
			$tempambiente = $_POST["tempambiente"];
			$observacionesenlatado = $_POST["observacionesenlatado"];
			$disoxigen = $_POST["disoxigen"];
			$tpo = $_POST["tpo"];
			$latascerradasDes = $_POST["latascerradasDes"];
			$latasvaciasDes = $_POST["latasvaciasDes"];
			$tapasDes = $_POST["tapasDes"];
			$latasOK = $_POST["latasOK"];
			$envasadosOK = round($latasOK * 0.5, 0);
			
			}
			
			
			// Consulta SQL de inserción con parámetros preparados
			$sql = "INSERT INTO lotesenlatado (id_lote, diaEnlatado, presionbarrido, presionenenlatadora, presionentanque, tiempollenado, tiempo1, tiempo2, tempentanque, tempenenlatadora, tempambiente, observacionesenlatado, disoxigen, tpo, latascerradasDes, latasvaciasDes, tapasDes, latasOK) 
					VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

			// Preparar la declaración
			$stmt = $conn->prepare($sql);

			// Vincular parámetros y ejecutar la consulta
			if ($stmt) {
				// Vincula los valores a los parámetros de la consulta
				$stmt->bind_param("isdddiiidddsiiiiii", $loteID, $diaEnlatado, $presionbarrido, $presionenenlatadora, $presionentanque, $tiempollenado, $tiempo1, $tiempo2, $tempentanque, $tempenenlatadora, $tempambiente, $observacionesenlatado, $disoxigen, $tpo, $latascerradasDes, $latasvaciasDes, $tapasDes, $latasOK);
			
				// Ejecutar la consulta
				$stmt->execute();


				// Cierra la declaración
				$stmt->close();
			} 
		}
		

	  //*********************//
	 // TABLA LOTES CERVEZA //
	//*********************//

    
    // Parámetros vitales
	if (isset($_POST['og'], $_POST['fg'])) {
		$og = $_POST['og'];
		$fg = $_POST['fg'];
		$ibuEsperado = $_POST['ibuEsperado'];
		$abvEsperado = $_POST['abvEsperado'];
		$carbLevelEsperado = $_POST['carbLevelEsperado'];

	}
	
if (isset($_POST['fechareporte'])) {
    $fecha_seleccionada = $_POST['fechareporte'];

    // Conexión a la base de datos
    require_once 'conexion.php';

    // Inicializar variables para los IDs
    $id_osmosis = null;
    $id_red = null;

    // Consulta para obtener el ID de OSMOSIS con la fecha seleccionada
    $sql = "SELECT id FROM reportesagua WHERE fecha = ? AND origen = 'OSMOSIS'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $fecha_seleccionada);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $id_osmosis);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Consulta para obtener el ID de RED con la fecha seleccionada
    $sql = "SELECT id FROM reportesagua WHERE fecha = ? AND origen = 'RED'";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 's', $fecha_seleccionada);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $id_red);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

}

	
	
	
	
	
	

	// Parámetros de H2O  
	if (isset($_POST['ca_mas_2'], $_POST['mg_mas_2'], $_POST['na_mas_2'], $_POST['cl_menos'], $_POST['so4_menos_2'],$_POST['comentariosGeneral'])) {
		$ca_mas_2 = $_POST['ca_mas_2'];
		$mg_mas_2 = $_POST['mg_mas_2'];
		$na_mas_2 = $_POST['na_mas_2'];
		$cl_menos = $_POST['cl_menos'];
		$so4_menos_2 = $_POST['so4_menos_2'];
		$comentario = $_POST['comentariosGeneral'];

	}


	// Verificar si se han enviado datos del formulario
    if (
        isset($_POST["lecturaDO"]) &&
        isset($_POST["lecturaDF"]) &&
        isset($_POST["ph_inicialMosto"]) &&
        isset($_POST["ph_finFerm"]) &&
        isset($_POST["litrosAfermentar"]) &&
        isset($_POST["diaEnvasado"]) &&
        isset($_POST["carbLevel"]) &&
        isset($_POST["fermentador"]) &&
        isset($_POST["ltsEnvasados"])
    ) {
        // Obtener los datos de las variables
        $lecturaDO = $_POST["lecturaDO"];
        $lecturaDF = $_POST["lecturaDF"];
        $ph_inicialMosto = $_POST["ph_inicialMosto"];
        $ph_finFerm = $_POST["ph_finFerm"];
        $litrosAfermentar = $_POST["litrosAfermentar"];
        $diaEnvasado = date('Y-m-d', strtotime($_POST["diaEnvasado"]));
        $carbLevel = $_POST["carbLevel"];
        $fermentador = $_POST["fermentador"];
		$comentario = $_POST["comentariosGeneral"];
		$ltsEnvasados = $_POST["ltsEnvasados"] + $envasadosOK ;
	}



	$sql = "UPDATE lotes_cerveza 
			SET co2 = ?, og = ?, fg = ?, ibu = ?, abv = ?, ca_mas_2 = ?, mg_mas_2 = ?, na_mas_2 = ?, cl_menos = ?, so04_menos_2 = ?, comentarios = ?, DO = ?, DF = ?, ph_mosto = ?, ph_fin_fermentacion = ?, litros_a_fermentador = ?, dia_envasado = ?, carb_level = ?, fermentador_id = ?, litros_envasados = ?, reporteRED = ?, reporteOSMO = ?
			WHERE id = ?";

	// Preparar la declaración
	$stmt = $conn->prepare($sql);

	// Vincular parámetros
	$stmt->bind_param("ddddddddddsdddddsdiiiii", $carbLevelEsperado, $og, $fg, $ibuEsperado, $abvEsperado, $ca_mas_2, $mg_mas_2, $na_mas_2, $cl_menos, $so4_menos_2, $comentario, $lecturaDO, $lecturaDF, $ph_inicialMosto, $ph_finFerm, $litrosAfermentar, $diaEnvasado, $carbLevel, $fermentador, $ltsEnvasados,$id_red,$id_osmosis,$loteID);
	

	// Ejecutar la consulta
	if ($stmt->execute()) {
		} else {
		echo "Error al insertar los datos: " . $stmt->error;
	}


    // Verificar si el checkbox 'alcalina' está marcado
    if (isset($_POST['alcalina'])) {
        // Actualizar el campo 'LIMP_ALCALINA_DATE' en la tabla 'fermentadores' con la fecha actual
        $sqlAlcalina = "UPDATE fermentadores SET LIMP_ALCALINA_DATE = ? WHERE ID = ?";
        $stmtAlcalina = $conn->prepare($sqlAlcalina);
        $stmtAlcalina->bind_param("si", $diaEnvasado, $fermentador);
        $stmtAlcalina->execute();
    }

    // Verificar si el checkbox 'acida' está marcado
    if (isset($_POST['acida'])) {
        // Actualizar el campo 'LIMP_ACIDA_DATE' en la tabla 'fermentadores' con la fecha actual
        $sqlAcida = "UPDATE fermentadores SET LIMP_ACIDA_DATE = ? WHERE ID = ?";
        $stmtAcida = $conn->prepare($sqlAcida);
        $stmtAcida->bind_param("si", $diaEnvasado, $fermentador);
        $stmtAcida->execute();
    }

    // Verificar si el checkbox 'oxidativa' está marcado
    if (isset($_POST['oxidativa'])) {
        // Actualizar el campo 'LIMP_OXIDATIVA_DATE' en la tabla 'fermentadores' con la fecha actual
        $sqlOxidativa = "UPDATE fermentadores SET LIMP_OXIDATIVA_DATE = ? WHERE ID = ?";
        $stmtOxidativa = $conn->prepare($sqlOxidativa);
        $stmtOxidativa->bind_param("si", $diaEnvasado, $fermentador);
        $stmtOxidativa->execute();
    }

    // Verificar si el checkbox 'exterior' está marcado
    if (isset($_POST['exterior'])) {
        // Actualizar el campo 'LIMP_EXTERIOR_DATE' en la tabla 'fermentadores' con la fecha actual
        $sqlExterior = "UPDATE fermentadores SET LIMP_EXTERIOR_DATE = ? WHERE ID = ?";
        $stmtExterior = $conn->prepare($sqlExterior);
        $stmtExterior->bind_param("si", $diaEnvasado, $fermentador);
        $stmtExterior->execute();
    }



	  //*****************************//
	 // TABLA LOTES TRATAMIENTO H2O //
	//*****************************//
    
    // Tratamiento H2O MASH
	if (isset($_POST['total_agua_mash'], $_POST['porcentaje_ro_mash'], $_POST['temperatura_mash'], $_POST['ph_mash'], $_POST['fosforico_mash'], $_POST['caso4_mash'], $_POST['cacl2_mash'], $_POST['mgcl_mash'], $_POST['otro_mash'], $_POST['fosforico_h2o_mash'])) {
		$total_agua_mash = $_POST['total_agua_mash'];
		$porcentaje_ro_mash = $_POST['porcentaje_ro_mash'];
		$temperatura_mash = $_POST['temperatura_mash'];
		$ph_mashh2o = $_POST['ph_mashh2o'];
		$fosforico_mash = $_POST['fosforico_mash'];
		$caso4_mash = $_POST['caso4_mash'];
		$cacl2_mash = $_POST['cacl2_mash'];
		$mgcl_mash = $_POST['mgcl_mash'];
		$otro_mash = $_POST['otro_mash'];
		$fosforico_h2o_mash = $_POST['fosforico_h2o_mash'];
		
	}
	
	// Tratamiento H2O SPARGE
	if (isset($_POST['total_agua_sparge'], $_POST['porcentaje_ro_sparge'], $_POST['temperatura_sparge'], $_POST['ph_sparge'], $_POST['fosforico_sparge'], $_POST['caso4_sparge'], $_POST['cacl2_sparge'], $_POST['mgcl_sparge'], $_POST['otro_sparge'])) {
		$total_agua_sparge = $_POST['total_agua_sparge'];
		$porcentaje_ro_sparge = $_POST['porcentaje_ro_sparge'];
		$temperatura_sparge = $_POST['temperatura_sparge'];
		$ph_spargeh2o = $_POST['ph_spargeh2o'];
		$fosforico_sparge = $_POST['fosforico_sparge'];
		$caso4_sparge = $_POST['caso4_sparge'];
		$cacl2_sparge = $_POST['cacl2_sparge'];
		$mgcl_sparge = $_POST['mgcl_sparge'];
		$otro_sparge = $_POST['otro_sparge'];
		
	}
	
	// Consulta SQL de inserción con parámetros preparados
	$sql = "INSERT INTO tratamiento_agua_mash_sparge (lote_id, 
			total_agua_mash, porcentaje_ro_mash, temperatura_mash, ph_mash, 
			caso4_mash, cacl2_mash, mgcl_mash, fosforico_mash, otro_mash, fosforico_h2o_mash, 
			total_agua_sparge, porcentaje_ro_sparge, temperatura_sparge, ph_sparge,
			 caso4_sparge, cacl2_sparge, mgcl_sparge, fosforico_sparge, otro_sparge) 
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

	// Preparar la declaración
	$stmt = $conn->prepare($sql);

	// Verificar si la preparación fue exitosa
	if ($stmt === false) {
		die("Error al preparar la consulta: " . $conn->error);
	}
		


	// Vincular parámetros
	$stmt->bind_param("iddddddddddddddddddd", $loteID, $total_agua_mash, $porcentaje_ro_mash, $temperatura_mash, $ph_mashh2o, $caso4_mash, $cacl2_mash, $mgcl_mash, $fosforico_mash, $otro_mash, $fosforico_h2o_mash, $total_agua_sparge, $porcentaje_ro_sparge, $temperatura_sparge, $ph_spargeh2o,	$caso4_sparge, $cacl2_sparge, $mgcl_sparge, $fosforico_sparge, $otro_sparge);

	// Ejecutar la consulta
	if ($stmt->execute() === false) {
		die("Error al ejecutar la consulta: " . $stmt->error);
	} else {
	}
	

	  //*******************//
	 // TABLA LOTES MALTA //
	//*******************//

	// Consulta SQL de inserción con parámetros preparados
	$sql = "INSERT INTO lotes_maltas (lote_id, malta_id, cantidad, tiempo, lote_malta) 
			VALUES (?, ?, ?, ?, ?)";

	// Preparar la declaración
	$stmt = $conn->prepare($sql);



    if (isset($_POST['malta'])) {
        $maltas = $_POST['malta'];
        $lotes = $_POST['lote_malta'];
        $cantidades = $_POST['cantidad'];
        $tiempos = $_POST['tiempo'];
		// Vincular parámetros y ejecutar la consulta
		if ($stmt) {
			// Iterar sobre los datos y procesarlos como necesites
			for ($i = 0; $i < count($maltas); $i++) {

				$stmt->bind_param("iidss", $loteID, $maltas[$i], $cantidades[$i], $tiempos[$i], $lotes[$i]);
			
				// Ejecutar la consulta
				$stmt->execute();
			}
			// Cierra la declaración
			$stmt->close();
		}
    }
    
    

	  //********************//
	 // TABLA LOTES LUPULO //
	//********************//

	// Consulta SQL de inserción con parámetros preparados
	$sql = "INSERT INTO lotes_lupulos (lote_id, lupulo_id, cantidad, tiempo, ibu, lote_lupulo) 
			VALUES (?, ?, ?, ?, ?, ?)";

	// Preparar la declaración
	$stmt = $conn->prepare($sql);

	if (isset($_POST['lupulo'])) {
		$lupulos = $_POST['lupulo'];
		$lotes_lupulo = $_POST['lote_lupulo'];
		$cantidades = $_POST['cantidad_lupulo'];
		$ibus = $_POST['ibu'];
		$tiempo_lupulo = $_POST['tiempo_lupulo'];
		

		if ($stmt) {
			// Iterar sobre los datos y procesarlos como necesites
			for ($i = 0; $i < count($lupulos); $i++) {

				$stmt->bind_param("iidsds", $loteID, $lupulos[$i], $cantidades[$i], $tiempo_lupulo[$i], $ibus[$i], $lotes_lupulo[$i]);

				// Ejecutar la consulta
				$stmt->execute();
			}
			$stmt->close();
		}
    }
 
	  //**********************//
	 // TABLA LOTES LEVADURA //
	//**********************//

	// Verificar si se han enviado datos de levadura
    if (isset($_POST['cepa'])) {
        // Obtener los datos de levadura del formulario
        $cepa = $_POST['cepa'];
        $generacion = $_POST['genleva'];
        $temperatura = $_POST['tempInoc'];
        $tasaInoculacion = $_POST['tasaInoc'];
        $viabilidad = $_POST['viabilidad'];
        $kilosBiomasa = $_POST['biomasa'];
        $ppmOxigeno = $_POST['oxigenacion']; // Asumiendo que 'biomasa' y 'ppmOxigeno' están vinculados a la misma entrada

		$sql = "INSERT INTO lotes_levaduras (lote_id, temp_inoculacion, tasa_inoculacion, 
											 viabilidad, kilos_biomasa, gen, cepa_id, oxigenacion) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

		// Preparar la declaración
		$stmt = $conn->prepare($sql);

		// Vincular parámetros
		$stmt->bind_param("iddddsii", $loteID, $temperatura, $tasaInoculacion, 
						$viabilidad, $kilosBiomasa, $generacion, $cepa, $ppmOxigeno);

		// Ejecutar la consulta
		$stmt->execute();
		$stmt->close();

	}
    
      //*********************//
	 // TABLA LOTES BATCHES //
	//*********************//
    
	// Verificar si se han enviado los datos del formulario
    if (isset($_POST['temp_mash']) && isset($_POST['ph_mash']) && isset($_POST['dens_primer_mosto']) && isset($_POST['dens_last_run']) && isset($_POST['ph_last_run']) && isset($_POST['temp_sparge']) && isset($_POST['ph_sparge']) && isset($_POST['vol_inicial_boil']) && isset($_POST['dens_pre_boil']) && isset($_POST['ph_inicio_boil']) && isset($_POST['vol_final_boil']) && isset($_POST['dens_post_boil']) && isset($_POST['ph_fin'])) {
        
		// Obtener los datos del formulario como matrices
        $temp_mash = $_POST['temp_mash'];
        $temp2_mash = $_POST['temp2_mash'];
        $temp3_mash = $_POST['temp3_mash'];
        $ph_mash = $_POST['ph_mash'];
        $ph2_mash = $_POST['ph2_mash'];
        $ph3_mash = $_POST['ph3_mash'];
        $dens_primer_mosto = $_POST['dens_primer_mosto'];
        $dens_last_run = $_POST['dens_last_run'];
        $ph_last_run = $_POST['ph_last_run'];
        $temp_sparge = $_POST['temp_sparge'];
        $ph_sparge = $_POST['ph_sparge'];
        $vol_inicial_boil = $_POST['vol_inicial_boil'];
        $dens_pre_boil = $_POST['dens_pre_boil'];
        $ph_inicio_boil = $_POST['ph_inicio_boil'];
        $vol_final_boil = $_POST['vol_final_boil'];
        $dens_post_boil = $_POST['dens_post_boil'];
        $ph_fin = $_POST['ph_fin'];
		
		// Preparar la consulta SQL de inserción
		$sql = "INSERT INTO batches (lote_id, temp_mash, temp2_mash, temp3_mash, ph_mash, ph2_mash, ph3_mash, dens_primer_mosto, dens_last_run, ph_last_run, 
											temp_sparge, ph_sparge, vol_inicial_boil, dens_pre_boil, ph_inicio_boil, 
											vol_final_boil, dens_post_boil, ph_fin, batch) 
											VALUES (?,?,?,?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
			
		// Preparar la declaración
		$stmt = $conn->prepare($sql);
		

        // Obtener el número de filas en la tabla
        $num_filas = count($temp_mash);
		
		if ($stmt) {
			// Iterar sobre las filas para procesar los datos de cada una
			for ($i = 0; $i < $num_filas; $i++) {

				$batch = $i+1; // Asigna el valor de batch adecuado

				// Vincular parámetros
				$stmt->bind_param("idddddddddddddddddi", $loteID, $temp_mash[$i], $temp2_mash[$i], $temp3_mash[$i], $ph_mash[$i], $ph2_mash[$i], $ph3_mash[$i], $dens_primer_mosto[$i], $dens_last_run[$i], $ph_last_run[$i], $temp_sparge[$i], $ph_sparge[$i], $vol_inicial_boil[$i], $dens_pre_boil[$i], $ph_inicio_boil[$i], $vol_final_boil[$i], $dens_post_boil[$i], $ph_fin[$i], $batch);

				// Ejecutar la consulta
				$stmt->execute();
			}
			$stmt->close();
		}

    }

	  //**************************************//
	 // TABLA LOTES SEGUIMIENTO FERMENTACION //
	//**************************************//

    // Verificar si se han enviado datos del formulario
	if (isset($_POST["fecha"]) && isset($_POST["hora"]) && isset($_POST["densidad"]) && isset($_POST["ph"]) && isset($_POST["temperatura"]) && isset($_POST["purga"]) && isset($_POST["comentarios"])) {

		// Obtener los datos de las variables
		$fechas = $_POST["fecha"];
		$horas = $_POST["hora"];
		$densidades = $_POST["densidad"];
		$phs = $_POST["ph"];
		$temperaturas = $_POST["temperatura"];
		$purgas = $_POST["purga"];
		$comentarios = $_POST["comentarios"];
		$fechasFormateadas = array_map(function($fecha) {
			return date('Y-m-d', strtotime($fecha));
		}, $fechas);

		// Preparar la consulta SQL de inserción
		$sql = "INSERT INTO seguimiento_fermentacion (lote_id, fecha, hora, densidad, ph, temperatura, purga, comentarios) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

		// Preparar la declaración
		$stmt = $conn->prepare($sql);

		if ($stmt) {
			// Iterar sobre los datos y ejecutar la consulta para insertar cada registro
			for ($i = 0; $i < count($fechas); $i++) {
				// Vincular parámetros
				
				$stmt->bind_param("issdddis", $loteID, $fechasFormateadas[$i], $horas[$i], $densidades[$i], $phs[$i], $temperaturas[$i], $purgas[$i], $comentarios[$i]);

				// Ejecutar la consulta
				$stmt->execute();
			}
			$stmt->close();
		}
    }
    
	header('Location: detalle_lote?id_lote='.$loteID);
		
}		




?>


