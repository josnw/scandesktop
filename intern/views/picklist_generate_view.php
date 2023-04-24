<h1>Neue Pickliste generieren</h1>

<form action="#" method="POST" enctype="multipart/form-data" >
	<div class="DSEdit">
		<div class="DSFeld4">Bezeichnung:<br> <input name="pickListName" value="<?php print date("D h:i"); ?>" length=10></div>
		<div class="DSFeld2">min. Artikelgewicht:<br> <input name="minPickListWeight" value="<?php print $_SESSION['minPickListWeight']; ?>" pattern="[0-9]+"></div>
		<div class="DSFeld2">max. Artikelgewicht:<br> <input name="maxPickListWeight" value="<?php print $_SESSION['maxPickListWeight']; ?>" pattern="[0-9]+"></div>
		<div class="DSFeld2">max. Anzahl Bestellungen:<br> <input name="pickListCount" value="<?php print $_SESSION['pickListCount']; ?>" pattern="[0-9]+" ></div>
		<div class="DSFeld2">Verkaufsfach (RegEx):<br> 
			<select name="pickListPlacePattern">
				<?php 
				   foreach($configStorePlace as $name => $regex) {
				   	if (isset($_SESSION['pickListPlacePattern']) and ($name == $_SESSION['pickListPlacePattern']) ) { $aktiv='selected'; } else { $aktiv = ''; }
				   		print "<option value='".$name."' ".$aktiv.">".$name."</option>\n";
					};
				?>
			</select>		
		</div>
		<div class="DSFeld2 right" style="background: #AA5555;"><input type="submit" name="generatePicklist" value="Speichern"></div>
	</div>
</form>