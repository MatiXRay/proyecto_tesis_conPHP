		// VER DETALLES DE LOTE
		const botonVerDetalles = document.getElementById('verDetalles');

		botonVerDetalles.addEventListener('click', function() {
			// Declarar e inicializar la variable
			const checkboxSeleccionado = document.querySelector('input[type="checkbox"]:checked');
			const loteSeleccionado = checkboxSeleccionado ? checkboxSeleccionado.value : null;

			if (!loteSeleccionado) {
				alert('Por favor, selecciona un elemento para ver detalles.');
				return;
			}

			// Redireccionar a la página detalles_lote.php con el ID del lote
			window.location.href = `detalles_lote.php?id_lote=${loteSeleccionado}`;
		});

		// VER NOTAS DE CATA
		const botonVerNotas = document.getElementById('verNotas');

		botonVerNotas.addEventListener('click', function() {
			// Declarar e inicializar la variable
			const checkboxSeleccionado = document.querySelector('input[type="checkbox"]:checked');
			const loteSeleccionado = checkboxSeleccionado ? checkboxSeleccionado.value : null;

			if (!loteSeleccionado) {
				alert('Por favor, selecciona un elemento para ver notas.');
				return;
			}

			// Redireccionar a la página notas_cata.php con el ID del lote
			window.location.href = `detalle_planilla_cata.php?id=${loteSeleccionado}`;
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

		function loadContent(page) {
			window.location.href = page;
		}


