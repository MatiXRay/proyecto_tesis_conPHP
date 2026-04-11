<?php
require_once 'config.php';
require_once 'auth.php';
requireLogin();
if (isTaster()) { header('Location: panel_cata'); exit; }
$csrf_token = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir Nuevo Fermentador</title>
    <script>const CSRF_TOKEN = '<?= e($csrf_token) ?>';</script>
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
        <header>
            <h1>Añadir Nuevo Fermen</h1>
        </header>

        <!-- Formulario para añadir una nueva malta -->
        <form id="addMaltaForm">
            <label for="nombre">Nombre:   </label>
            <input type="text" id="nombre" name="nombre" required>
            <br><br>
            <label for="marca" >Capacidad:       </label>
            <input type="text" id="marca" name="marca" required>
			<br><br>
			<div class="botones-container">
				<input type="submit" value="Añadir" class="nuevo" id="añadir">
				<input type="submit" value="Añadir y nuevo" class="nuevo" id="añadirYNuevo">
			</div>
		</form>
    </div>
    
    <script>
        const addMaltaForm = document.getElementById('addMaltaForm');

        addMaltaForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const nombre = document.getElementById('nombre').value;
            const marca = document.getElementById('marca').value;
            const tabla = 'fermentadores'; // Nombre de la tabla

            const botonPresionado = event.submitter.id; // Obtener el ID del botón presionado

            // Realizar la solicitud AJAX
            fetch('anadir_registro.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `tabla=${encodeURIComponent(tabla)}&nombre=${encodeURIComponent(nombre)}&marca=${encodeURIComponent(marca)}&csrf_token=${encodeURIComponent(CSRF_TOKEN)}`,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mostrar mensaje de éxito
                    window.alert('Fermentador añadido con éxito.');

                    if (botonPresionado === 'añadirYNuevo') {
                        // Limpiar los campos del formulario
                        document.getElementById('nombre').value = '';
                        document.getElementById('marca').value = '';
						window.opener.location.reload();

                    } else {
                        // Cerrar la ventana emergente
                        window.close();
                        window.opener.location.reload();
                    }
                } else {
                    window.alert('Error al añadir el fermentador.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.alert('Error al añadir el fermentador.');
            });
        });
    </script>

</main>

</body>
</html>
