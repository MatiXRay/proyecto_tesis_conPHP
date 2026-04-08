<?php 
	session_start(); 

	if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
		header('Location: login');
		exit;
	}

	// Verificar el rol del usuario
	if ($_SESSION['rol_id'] === 3) {
		header('Location: panel_cata');
		exit;
	}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar limpieza de fermentador</title>
    <style>
        /* Estilos generales */
        body {
            font-family: "Segoe UI", sans-serif;
            font-size: 16px;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh; /* Altura completa del viewport */
            margin: 0; /* Eliminar márgenes */
            background-color: #f4f4f4; /* Fondo gris claro */
        }

        #contenido {
            background-color: #ffffff; /* Fondo blanco para el contenedor */
            padding: 20px;
            box-sizing: border-box;
            border-radius: 8px; /* Bordes redondeados */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Sombra */
            width: 400px; /* Ancho del contenedor */
        }
        
        h1 {
			font-size: 18;
			font-color:#0056b3; 
        }

        .nuevo {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            font-size: 18px; 
        }

        .nuevo:hover {
            background-color: #0056b3;
        }
        
        /* Estilos para los labels */
		label {
			font-size: 18px; /* Tamaño de fuente para los labels */
			margin-right: 10px; /* Espacio a la derecha del label */
		}

		/* Estilos para los inputs */
		input[type="text"] {
			font-size: 16px; /* Tamaño de fuente para los inputs de tipo texto */
			padding: 8px; /* Espacio alrededor del texto dentro del input */
			border-radius: 4px; /* Bordes redondeados para los inputs */
			border: 1px solid #ccc; /* Borde del input */
		}

		/* Estilos para los botones */
		input[type="submit"].nuevo {
			font-size: 16px; /* Tamaño de fuente para los botones */
			padding: 8px 12px; /* Espacio alrededor del texto dentro del botón */
			border: none; /* Sin borde */
			border-radius: 4px; /* Bordes redondeados para los botones */
			background-color: #007bff; /* Color de fondo */
			color: white; /* Color del texto */
			cursor: pointer; /* Cambio de cursor al pasar el ratón */
		}

		/* Estilos para el botón al pasar el ratón */
		input[type="submit"].nuevo:hover {
			background-color: #0056b3; /* Cambio de color de fondo */
		}

		/* Estilos para el contenedor de los botones */
		.botones-container {
			text-align: right; /* Alinea los botones a la derecha */
			margin-top: 20px; /* Espacio superior para separar del contenido anterior */
		}

    </style>
</head>
<body>

<main>
    <main>
    <div id="contenido">

        <!-- Formulario para añadir una nueva malta -->
		<form id="limpiarFermenForm">
			<h2>Selecciona el tipo de limpieza</h2>
			<div style="display: flex; align-items: center;">
				<label for="date" style="margin-right: 10px;">Día de limpieza:</label>
				<input type="date" id="date" name="date">
			</div>
			<input type='checkbox' id='limpAlcalina1' name='limpAlcalina' value='limpAlcalina'>
			<label for='limpAlcalina1'>Limpieza Alcalina</label><br>
			<input type='checkbox' id='limpAcida1' name='limpAcida' value='limpAcida'>
			<label for='limpAcida1'>Limpieza Ácida</label><br>
			<input type='checkbox' id='limpOxidativa1' name='limpOxidativa' value='limpOxidativa'>
			<label for='limpOxidativa1'>Limpieza Oxidativa</label><br>
			<input type='checkbox' id='limpExterior1' name='limpExterior' value='limpExterior'>
			<label for='limpExterior1'>Limpieza Exterior</label><br>
			<div class="botones-container">
				<input type="submit" value="Aplicar" class="nuevo" id="añadir">
			</div>
		</form>
    
    <script>

		
	   document.addEventListener('DOMContentLoaded', function() {
		   
		   // Obtener el elemento de entrada de fecha
            const dateInput = document.getElementById('date');

            // Obtener la fecha actual
            const today = new Date().toISOString().split('T')[0];

            // Establecer el valor del campo de fecha
            dateInput.value = today;
            
			const limpiarFermenForm = document.getElementById('limpiarFermenForm');

			limpiarFermenForm.addEventListener('submit', function(event) {
				event.preventDefault();

				const alcalina = document.getElementById('limpAlcalina1').checked; // Cambiado a .checked para checkbox
				const acida = document.getElementById('limpAcida1').checked; // Cambiado a .checked para checkbox
				const oxidativa = document.getElementById('limpOxidativa1').checked; // Cambiado a .checked para checkbox
				const exterior = document.getElementById('limpExterior1').checked; // Cambiado a .checked para checkbox
				const date = document.getElementById('date').value;
				const id = <?php echo json_encode($_GET['id']); ?>;  // Asegúrate de usar json_encode para incrustar el valor de PHP en JavaScript
				const tabla = 'fermentadores'; // Nombre de la tabla

				const botonPresionado = event.submitter.id; // Obtener el ID del botón presionado

				// Realizar la solicitud AJAX
				fetch('registrar_limpieza_fermen.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
					},
					body: JSON.stringify({
						tabla: tabla,
						id: id,
						alcalina: alcalina,
						acida: acida,
						oxidativa: oxidativa,
						exterior: exterior,
						date: date,
						botonPresionado: botonPresionado
					}),
				})
				.then(response => response.json())
				.then(data => {
					if (data.success) {
						// Mostrar mensaje de éxito
						window.alert('Registro actualizado con éxito.');
						// Cerrar la ventana emergente
						window.close();
						window.opener.location.reload();
					} else {
						window.alert('Error al modificar el registro.');
					}
				})
				.catch(error => {
					console.error('Error:', error);
					window.alert('Error al modificar el registro.');
				});
			});
		});
    </script>

</main>

</body>
</html>

