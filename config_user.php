
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BLSK</title>
    <!-- Enlace al archivo CSS para estilos -->
    <link rel="stylesheet" href="styleinicio.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Redireccionar al usuario a la página de inicio de sesión si no ha iniciado sesión
    header('Location: login.php');
    exit;
}

// Incluir el archivo de conexión a la base de datos
include 'conexion.php';

// Obtener el ID de usuario de la sesión
$id_usuario = $_SESSION['id'];

// Consulta SQL para obtener los datos del usuario
$sql = "SELECT * FROM users WHERE id = $id_usuario";

// Ejecutar la consulta
$resultado = mysqli_query($conn, $sql);

// Verificar si se obtuvieron resultados
if ($resultado) {
    // Obtener los datos del usuario
    $usuario = mysqli_fetch_assoc($resultado);
} else {
    // Manejar el caso en el que no se puedan obtener los datos del usuario
    echo "Error al obtener los datos del usuario.";
}

// Cerrar la conexión a la base de datos
mysqli_close($conn);
?>

    <main>
		<?php require 'menu.php'; ?>
		<?php require 'info_user.php'; ?>

        <div id="contenido" style="margin-left: 300px;">
		<header>
			<h1>Configuración del usuario</h1>
		</header>
		
   <h2>Actualizar Datos</h2>
	<form id="formulario">
		<label for="nombre">Nombre:</label>
		<input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($usuario['nombre']); ?>"><br><br>
		
		<label for="apellido">Apellido:</label>
		<input type="text" id="apellido" name="apellido" value="<?php echo htmlspecialchars($usuario['apellido']); ?>"><br><br>
		
		<label for="mail">Correo electrónico:</label>
		<input type="email" id="mail" name="mail" value="<?php echo htmlspecialchars($usuario['mail']); ?>"><br><br>
		
		<label for="telefono">Teléfono:</label>
		<input type="tel" id="telefono" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono']); ?>"><br><br>
		<button class="nuevo" id="actualizar">Actualizar</button>
	</form>


		
		
		</div>
		
<script>

	function loadContent(page) {
		window.location.href = page;
	}
        // Función para validar el formato del correo electrónico
        function validarEmail(email) {
            var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }

        // Función para validar el número de teléfono
        function validarTelefono(telefono) {
            var regex = /^\d{9}$/;
            return regex.test(telefono);
        }

        // Función para validar el formulario antes de enviarlo
        function validarFormulario() {
            var nombre = document.getElementById('nombre').value.trim();
            var apellido = document.getElementById('apellido').value.trim();
            var mail = document.getElementById('mail').value.trim();
            var telefono = document.getElementById('telefono').value.trim();

            // Validar el formato del correo electrónico
            if (!validarEmail(mail)) {
                alert('El correo electrónico ingresado no es válido.');
                return false;
            }

            // Validar el formato del número de teléfono
            if (!validarTelefono(telefono)) {
                alert('El número de teléfono ingresado no es válido.');
                return false;
            }

            // Si pasa todas las validaciones, se puede enviar el formulario
            return true;
        }
   document.addEventListener('DOMContentLoaded', function() {
    // Obtener el botón de actualización
    var btnActualizar = document.getElementById('actualizar');

    // Agregar un evento de clic al botón
    btnActualizar.addEventListener('click', function(event) { // Agregar event como parámetro
        // Validar el formulario antes de enviarlo
        if (!validarFormulario()) {
            event.preventDefault(); // Evitar enviar el formulario si no es válido
        } else {
            // Obtener los datos del formulario
            var nombre = document.getElementById('nombre').value;
            var apellido = document.getElementById('apellido').value;
            var mail = document.getElementById('mail').value;
            var telefono = document.getElementById('telefono').value;

            // Crear un objeto FormData con los datos del formulario
            var formData = new FormData();
            formData.append('nombre', nombre);
            formData.append('apellido', apellido);
            formData.append('mail', mail);
            formData.append('telefono', telefono);

            // Crear una solicitud AJAX
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'actualizar_datos_user.php');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    // Analizar la respuesta JSON
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        // Si la actualización fue exitosa, mostrar mensaje y recargar la página
                        alert('Datos actualizados correctamente.');
                        window.location.reload();
                    } else {
                        // Si ocurrió un error, mostrar mensaje de error
                        alert('Error: ' + response.message);
                    }
                }
            };

            // Enviar la solicitud con los datos del formulario
            xhr.send(formData);
        }
    });
});
</script>

        </div>
    </main>

</body>
</html>


