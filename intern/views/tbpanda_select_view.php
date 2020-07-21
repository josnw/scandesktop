<h1>Neue PANDA Abfrage generieren</h1>
<form action="#" method="POST" enctype="multipart/form-data" >
	<div class="DSEdit">
		<div class="DSFeld4 smallBorder">
			<div class="DSFeld1 ">
				von Lieferant Nr <br/><input name="vonlinr" value="<?php print $vonlinr; ?>" length=10>
			</div>
			<div class="DSFeld1">
				bis Lieferant Nr <br/><input name="bislinr" value="<?php print $bislinr; ?>" length=10>
			</div>
		</div>
		<div class="DSFeld4 smallBorder">
			<div class="DSFeld1 ">
				von Artikelgruppe <br/><input name="vonqgrp" value="<?php print $vonqgrp; ?>" length=10>
			</div>
			<div class="DSFeld1">
				bis Artikelgruppe <br/><input name="bisqgrp" value="<?php print $bisqgrp; ?>" length=10>
			</div>
		</div>
		<div class="DSFeld2 smallBorder">
			<div class="DSFeld1">
				<input type="checkbox" name="autoUpdate" value="1" checked> Artikel für Autoupdate markieren
			</div>
		</div>
		<div class="DSFeld2 right" style="background: #AA5555;"><input type="submit" name="pandaDownload" value="Download"></div>
	</div>
</form>

<h3>Aktualisierung manuell erstellen</h3>
<form action="#" method="POST" enctype="multipart/form-data" >
	<div class="DSEdit">
		<div class="DSFeld2 right" style="background: #AA5555;"><input type="submit" name="autoUpdate" value="Aktualisierung"></div>
	</div>
</form>