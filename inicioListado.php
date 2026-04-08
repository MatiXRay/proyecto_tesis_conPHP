		<header>
		<h1>Listado de lotes</h1>
		</header>

		<?php
		// Establecer la conexión a la base de datos
		$servername = "localhost";
		$username = "rocko";
		$password = "Pepito11!";
		$dbname = "fabrica_cerveza";

		// Crear una conexión
		$conn = new mysqli($servername, $username, $password, $dbname);

		// Verificar la conexión
		if ($conn->connect_error) {
			die("Error de conexión: " . $conn->connect_error);
		}

		// Obtener el término de búsqueda y el orden de la URL
		$searchTerm = isset($_GET['searchTerm']) ? $_GET['searchTerm'] : '';
		$orden = isset($_GET['orden']) && $_GET['orden'] === 'asc' ? 'ASC' : 'DESC';
		$nuevo_orden = $orden === 'asc' ? 'desc' : 'asc';

		$pagina_actual = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
		$elementos_por_pagina = 20; // Número de elementos por página

		// Calcular el offset
		$offset = ($pagina_actual - 1) * $elementos_por_pagina;

		// Consulta para contar el total de registros con el término de búsqueda
		$sql_total = "SELECT COUNT(*) as total 
					  FROM lotes_cerveza lc
					  INNER JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
					  WHERE lc.comentarios LIKE '%$searchTerm%'
					  OR ec.nombre LIKE '%$searchTerm%'";

		// Ejecutar la consulta para contar el total de registros
		$result_total = $conn->query($sql_total);
		$total_registros = $result_total->fetch_assoc()['total'];

		// Consulta principal con paginación y búsqueda
		$sql = "SELECT lc.id, lc.fecha_elaboracion, lc.comentarios, ec.nombre 
				FROM lotes_cerveza lc
				INNER JOIN estilos_cerveza ec ON lc.estilo_id = ec.id
				WHERE lc.comentarios LIKE '%$searchTerm%'
				OR ec.nombre LIKE '%$searchTerm%'
				ORDER BY lc.fecha_elaboracion $orden
				LIMIT $offset, $elementos_por_pagina";

		// Ejecutar la consulta
		$result = $conn->query($sql);

		// Verificar si hay resultados y almacenarlos en un array
		$datos = array();
		if ($result->num_rows > 0) {
			while ($row = $result->fetch_assoc()) {
				$datos[] = $row;
			}
		}

		// Calcular el número total de páginas
		$total_paginas = ceil($total_registros / $elementos_por_pagina);

		// Cerrar la conexión
		$conn->close();
		?>		
		
		<form action="" method="GET" id="searchForm" class="search-form">
			<label for="searchTerm">Buscar:</label>
			<input type="text" id="searchTerm" name="searchTerm" value="<?= htmlspecialchars($searchTerm) ?>" class="search-input">
			<input type="submit" value="Buscar" class="search-button">
		</form>

		<button id="verDetalles" class="details-button">Ver Detalles del Lote</button>
		<button id="verNotas" class="details-button">Ver Notas de Cata</button>
		<button id="borrarFila" class="borrarFila">Eliminar</button>
		<button id="nuevoLote" class="nuevoLote">Añadir nuevo lote</button>

		
		<table class="styled-table">
			<thead>
				<tr>
					<th>X</th>
					<th><a href="?pagina=<?php echo $pagina_actual; ?>&searchTerm=<?= urlencode($searchTerm); ?>&orden=<?php echo $nuevo_orden; ?>">Fecha Elaboración</a></th>
					<th>Estilo</th>
					<th>Comentarios</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ($datos as $dato): ?>
					<tr>
						<td><input type="checkbox" name="seleccion" value="<?= $dato['id'] ?>" <?= $index === 0 ? 'checked' : '' ?>></td>
						<td><?= date('d/m/Y', strtotime($dato['fecha_elaboracion'])); ?></td>
						<td><?= $dato['nombre'] ?></td>
						<td><?= $dato['comentarios'] ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<div class="pagination">
			<!-- Código de paginación -->
		</div>

		<div class="pagination">
			<?php if ($pagina_actual > 1): ?>
				<a href="?pagina=<?php echo $pagina_actual - 1; ?>&searchTerm=<?= urlencode($searchTerm); ?>">Anterior</a>
			<?php endif; ?>

			<?php for ($i = 1; $i <= $total_paginas; $i++): ?>
				<a href="?pagina=<?php echo $i; ?>&searchTerm=<?= urlencode($searchTerm); ?>" <?php echo $pagina_actual === $i ? 'class="active"' : ''; ?>><?php echo $i; ?></a>
			<?php endfor; ?>

			<?php if ($pagina_actual < $total_paginas): ?>
				<a href="?pagina=<?php echo $pagina_actual + 1; ?>&searchTerm=<?= urlencode($searchTerm); ?>">Siguiente</a>
			<?php endif; ?>
		</div>
		</main>
    
    <script>
		// VER DETALLES DE LOTE
		const botonVerDetalles = document.getElementById('verDetalles');
		
		// Agregar un evento de clic al botón
		botonVerDetalles.addEventListener('click', function() {
			// Obtener el ID del lote seleccionado
			const loteSeleccionado = document.querySelector('input[type="checkbox"]:checked').value;
			
			// Redireccionar a la página detalles_lote.php con el ID del lote
			window.location.href = `detalles_lote.php?id_lote=${loteSeleccionado}`;
		});


		// VER NOTAS DE CATA
		const botonVerNotas = document.getElementById('verNotas');
		
		// Agregar un evento de clic al botón
		botonVerNotas.addEventListener('click', function() {
			// Obtener el ID del lote seleccionado
			const loteSeleccionado = document.querySelector('input[type="checkbox"]:checked').value;
			
			// Redireccionar a la página detalles_lote.php con el ID del lote
			window.location.href = `notas_cata.php?id_lote=${loteSeleccionado}`;
		});


		// ELIMINAR LOTE
		const botonEliminar = document.getElementById('borrarFila');
		
		// Agregar un evento de clic al botón
		botonEliminar.addEventListener('click', function() {
			// Obtener el ID del lote seleccionado
			const loteSeleccionado = document.querySelector('input[type="checkbox"]:checked').value;
			
			// Redireccionar a la página detalles_lote.php con el ID del lote
			window.location.href = `eliminar_lote.php?id_lote=${loteSeleccionado}`;
		});
		
		// NUEVO LOTE
		const botonNuevo = document.getElementById('nuevoLote');
		
		// Agregar un evento de clic al botón
		botonNuevo.addEventListener('click', function() {
			
			// Redireccionar a la página detalles_lote.php con el ID del lote
			window.location.href = `anadir_lote.php`;
		});
		
		// Obtener todos los checkboxes
		const checkboxes = document.querySelectorAll('input[type="checkbox"]');
		
		// Manejar el clic en los checkboxes
		checkboxes.forEach(checkbox => {
			checkbox.addEventListener('click', function() {
				// Desmarcar todos los otros checkboxes en la misma tabla
				const checkboxesInTable = this.closest('table').querySelectorAll('input[type="checkbox"]');
				checkboxesInTable.forEach(cb => {
					if (cb !== this) {
						cb.checked = false;
					}
				});
			});
		});
		

		document.addEventListener('DOMContentLoaded', function() {
			const rows = document.querySelectorAll('.styled-table tbody tr');

			rows.forEach(row => {
				row.addEventListener('click', function() {
					const checkboxes = document.querySelectorAll('.styled-table tbody input[type="checkbox"]');
					checkboxes.forEach(checkbox => {
						checkbox.checked = false;
					});

					const checkbox = this.querySelector('input[type="checkbox"]');
					checkbox.checked = true;
				});
			});
		});

		
</script>
