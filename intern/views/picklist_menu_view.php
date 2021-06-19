<div class="DSEdit noprint">
Es ist <?php print(count($userPickData));?> offene Pickliste mit noch <?php print($userPackOrder);?> unbearbeiteten Bestellungen vorhanden.
  Insgesamt erwarten euch noch  <?php print($allPackOrder["offen"]);?> unbearbeitete Bestellungen.
</div>


<form action="#" method="POST" enctype="multipart/form-data" >
	<div class="DSEdit noprint">
		<div class="DSFeld2" style="background: #AA5555;"><input type="submit" name="showPickItems" value="Pickliste anzeigen"></div>
		<div class="DSFeld2" style="background: #AA5555;"><input type="submit" name="showPackOrder" value="Bestellung bearbeiten"></div>
		<div class="DSFeld2" style="">Auftragsreihenfolge:<br/>
			<input style="width: auto;" type=radio name="sortorder" value="age">Alter
			<input style="width: auto;" type=radio name="sortorder" value="weight">Gewicht
			<input style="width: auto;" type=radio name="sortorder" value="rank">Topartikel
		</div>
				<?php if ($_SESSION["level"] > 5) { print '
			<div class="DSFeld2" style="background: #AA5555;"><input type="submit" name="showOrderList" value="Übersicht Bestellung"></div>
		';
		}
		?>
	</div>
</form>
