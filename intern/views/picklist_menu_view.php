<div class="DSEdit noprint">
Es ist <?php print(count($userPickData));?> offene Pickliste mit noch <?php print($userPackOrder);?> unbearbeiteten Bestellungen vorhanden.
  Insgesamt erwarten euch noch  <?php print($allPackOrder["offen"]);?> unbearbeitete Bestellungen.
</div>


<form action="#" method="POST" enctype="multipart/form-data" >
	<div class="DSEdit noprint">
		<div class="DSFeld2" style="background: #AA5555;"><input type="submit" name="showPickItems" value="Pickliste anzeigen"></div>
		<div class="DSFeld2" style="background: #AA5555;"><input type="submit" name="showPackOrder" value="Bestellung bearbeiten"></div>
		<?php if ($_SESSION["level"] > 5) { print '
			<div class="DSFeld2" style="background: #AA5555;"><input type="submit" name="showOrderList" value="Übersicht Bestellung"></div>
		';
		}
		?>
	</div>
</form>
