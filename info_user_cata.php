    <div class="wrapperloggedin">
    <div class="logo">
        <!-- Aquí puedes insertar tu logo si lo necesitas -->
    </div>
    <?php if(isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
        <div class="welcome">
			 <div class="headleft">
			<h3 style="font-family: 'Arial', sans-serif; font-size: 24px; margin-bottom: 10px;margin-top: 10px;">Panel Sensorial</h3>

			</div>
			<div class="headright">
            <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <div class="dropdown-menu">
                <a href="config_user_cata" class="dropdown-item">Configuración</a>
				<a href="logout.php" class="dropdown-item" onclick="return confirm('¿Estás seguro de que quieres cerrar sesión?');">Cerrar sesión</a>
            </div>
		</div>
        </div>
    <?php else: 
        header('Location: login');
        exit;
     session_destroy();
    ?>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const welcome = document.querySelector('.headright');

        welcome.addEventListener('click', function() {
            const dropdown = document.querySelector('.dropdown-menu');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });
    });
</script>
