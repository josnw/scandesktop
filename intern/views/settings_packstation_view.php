<form action="#" method="POST" enctype="multipart/form-data" >
	<div class="DSEdit">
		<div class="DSFeld2">Etikettendrucker:<br> 
			<select name="COOKIE[printerLabel]">
				<option value="">[bitte auswählen]</option>
				<?php 
					foreach($configPrinter['label'] as $id => $name) {
						if (isset($_SESSION['printerLabel']) and ($id == $_SESSION['printerLabel']) ) { $aktiv='selected'; } else { $aktiv = ''; }
						print "<option ".$aktiv." value=".$id." >".$name."</option>\n";
					};
				?>
			</select>		
		</div>

		<div class="DSFeld2">A4 Drucker (Serverprint):<br> 
			<select name="COOKIE[printerA4]">
				<option value="">[bitte auswählen]</option>
				<?php 
					foreach($configPrinter['a4'] as $id => $name) {
						if (isset($_SESSION['printerA4']) and ($id == $_SESSION['printerA4']) ) { $aktiv='selected'; } else { $aktiv = ''; }
						print "<option ".$aktiv." value=".$id." >".$name."</option>\n";
					};
				?>
			</select>		
		</div>

		<div class="DSFeld2 right" style="background: #AA5555;"><input type="submit" name="setPrinter" value="Speichern"></div>
	</div>
</form>