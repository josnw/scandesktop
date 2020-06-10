<h1>Neue Pickliste generieren</h1>

<form action="#" method="POST" enctype="multipart/form-data" >
	<div class="DSEdit">
		<div class="DSFeld4">Bezeichnung:<br> <input name="pickListName" value="<?php print $_SESSION["name"]."-".date("Ymd-hi"); ?>" length=100></div>
		<div class="DSFeld2">max. Artikelgewicht:<br> <input name="pickListWeight" value="31" pattern="[0-9]+"></div>
		<div class="DSFeld2">max. Anzahl Bestellungen:<br> <input name="pickListCount" value="20" pattern="[0-9]+" ></div>
		<div class="DSFeld2 right" style="background: #AA5555;"><input type="submit" name="generatePicklist" value="Speichern"></div>
	</div>
</form>