<h1>Neue Pickliste generieren</h1>

<form action="#" method="POST" enctype="multipart/form-data" >
	<div class="DSEdit">
		<div class="DSFeld4">Bezeichnung:<br> <input name="pickListName" value="<?php print date("D h:i"); ?>" length=10></div>
		<div class="DSFeld2">min. Artikelgewicht:<br> <input name="minPickListWeight" value="0" pattern="[0-9]+"></div>
		<div class="DSFeld2">max. Artikelgewicht:<br> <input name="maxPickListWeight" value="31" pattern="[0-9]+"></div>
		<div class="DSFeld2">max. Anzahl Bestellungen:<br> <input name="pickListCount" value="20" pattern="[0-9]+" ></div>
		<div class="DSFeld2">Verkaufsfach (RegEx):<br> 
			<select name="pickListPlacePattern">
				<?php 
				   foreach($configStorePlace as $name => $regex) {
						print "<option value='".$name."' >".$name."</option>\n";
					};
				?>
			</select>		
		</div>
		<div class="DSFeld2 right" style="background: #AA5555;"><input type="submit" name="generatePicklist" value="Speichern"></div>
	</div>
</form>