<?php
// Iniciar la sesión
session_start();

// Verificar si el usuario ya inició sesión
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: welcome.php'); // Redirigir al usuario a la página de bienvenida si ya está logueado
    exit;
}

// Incluir la conexión a la base de datos
require 'conexion.php';

// Definir variables e inicializar con valores vacíos
$username = $password = '';
$username_err = $password_err = '';

// Procesar datos del formulario cuando se envía el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Verificar si el nombre de usuario está vacío
    if (empty(trim($_POST['username']))) {
        $username_err = 'Por favor, introduce un nombre de usuario.';
    } else {
        $username = trim($_POST['username']);
    }

    // Verificar si la contraseña está vacía
    if (empty(trim($_POST['password']))) {
        $password_err = 'Por favor, introduce tu contraseña.';
    } else {
        $password = trim($_POST['password']);
    }

	// Validar credenciales
	if (empty($username_err) && empty($password_err)) {
		$sql = 'SELECT id, username, password FROM users WHERE username = ?';

		if ($stmt = mysqli_prepare($conn, $sql)) {
			mysqli_stmt_bind_param($stmt, 's', $param_username);

			$param_username = $username;

			if (mysqli_stmt_execute($stmt)) {
				mysqli_stmt_store_result($stmt);

				// Verificar si el nombre de usuario existe, si es así, verificar la contraseña
				if (mysqli_stmt_num_rows($stmt) == 1) {
					mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password);

					if (mysqli_stmt_fetch($stmt)) {
						if (password_verify($password, $hashed_password)) {
							// La contraseña es correcta, inicia una nueva sesión
							session_start();

							// Almacenar datos en variables de sesión
							$_SESSION['loggedin'] = true;
							$_SESSION['id'] = $id;
							$_SESSION['username'] = $username;

							// Redirigir al usuario a la página de bienvenida
							header('Location: welcome.php');
						} else {
							// Mostrar un mensaje de error si la contraseña es incorrecta
							$password_err = 'La contraseña introducida no es válida.';
						}
					}
				} else {
					// Mostrar un mensaje de error si el nombre de usuario no existe
					$username_err = 'No se encontró ninguna cuenta con ese nombre de usuario.';
				}
			} else {
				echo 'Oops! Algo fue mal. Por favor, inténtalo de nuevo más tarde.';
			}

			// Cerrar la declaración
			mysqli_stmt_close($stmt);
		}
	}


            // Cerrar la declaración
            mysqli_stmt_close($stmt);
	}
    

    // Cerrar la conexión
    mysqli_close($conn);

?>

<?php
// Configurar CSP sin unsafe-eval
header("Content-Security-Policy: script-src 'self';");

// Configurar CSP con unsafe-eval (menos seguro)
header("Content-Security-Policy: script-src 'self' 'unsafe-eval';");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="css/styleslogin.css">
</head>
<body>
    <div class="wrapper">
        <div class="logo">
            <img src="img/logo bialy.png" alt="Logo de la empresa">
        </div>
        <h2>Login</h2>
        <p>Por favor, introduce tus credenciales para iniciar sesión.</p>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-group <?php echo (!empty($username_err)) ? 'has-error' : ''; ?>">
                <label>Nombre de usuario</label>
                <input type="text" name="username" class="form-control" value="<?php echo $username; ?>">
                <span class="help-block"><?php echo $username_err; ?></span>
            </div>
            <div class="form-group <?php echo (!empty($password_err)) ? 'has-error' : ''; ?>">
                <label>Contraseña</label>
                <input type="password" name="password" class="form-control">
                <span class="help-block"><?php echo $password_err; ?></span>
            </div>
            <div class="form-group">
                <input type="submit" class="btn btn-primary" value="Login">
            </div>
        </form>
    </div>
</body>
</html>
