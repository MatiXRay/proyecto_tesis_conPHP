	<table border="1">
		<tr>
			<th>Intensidad</th>
			<th>Fallas</th>
			<th>Final</th>
		</tr>
		<tr>
			<td>
				<p><strong>Cuerpo:</strong> <?php echo mostrar_escala($row['cuerpo_intensidad']); ?></p>
				<p><strong>Carbonatación:</strong> <?php echo mostrar_escala($row['carbonatacion_intensidad']); ?></p>
				<p><strong>Calentamiento:</strong> <?php echo mostrar_escala($row['calentamiento_intensidad']); ?></p>
				<p><strong>Cremosidad:</strong> <?php echo mostrar_escala($row['cremosidad_intensidad']); ?></p>
				<p><strong>Astringencia:</strong> <?php echo mostrar_escala($row['astringencia_intensidad']); ?></p>
			</td>
			<td>
				<?php
				// Dividir las palabras separadas por comas en el campo 'maltas_atributos'
				$fallas = explode(",", $row['mouthfeel_fallas']);
				foreach ($fallas as $falla) {
					echo "<p>". formatear_atributo($falla)."</p><br>";
				}
				?>
			</td>
			<td>
				<?php
				// Dividir las palabras separadas por comas en el campo 'lupulo_atributos'
				$final = explode(",", $row['mouthfeel_final']);
				foreach ($final as $fin) {
					echo "<p>". formatear_atributo($fin)."</p><br>";
				}
				?>
			</td>
			<td>
				<?php
				// Dividir las palabras separadas por comas en el campo 'esteres_atributos'
				$esteres = explode(",", $row['sabor_esteres_atributos']);
				foreach ($esteres as $estere) {
					echo "<p>". formatear_atributo($estere)."</p><br>";
				}
				?>
			</td>
			<td>
				<?php
				// Dividir las palabras separadas por comas en el campo 'otros_atributos'
				$otros = explode(",", $row['sabor_otros_atributos']);
				foreach ($otros as $otro) {
					echo "<p>". formatear_atributo($otro)."</p><br>";
				}
				?>
			</td>
			<td>
				<?php
				// Dividir las palabras separadas por comas en el campo 'otros_atributos'
				$otros = explode(",", $row['balance']);
				foreach ($otros as $otro) {
					echo "<p>". formatear_atributo($otro)."</p><br>";
				}
				?>
			</td>
		</tr>           
	</table>

	<p><strong>Comentario:</strong> <?php echo $row['sabor_comentario']; ?></p>
	<p><strong>Puntaje:</strong> <?php echo $row['sabor_puntaje']; ?></p>
