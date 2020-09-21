<h2>Neue PANDA Abfrage generieren</h2>
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
		<div class="DSFeld2 right" style="background: #AA5555;"><input type="submit" name="pandaDownload" value="Download" onclick="wartemal('on')"></div>
	</div>
</form>

<h2>ARTICLE_ Aktualisierung manuell erstellen *</h2>

<form action="#" method="POST" enctype="multipart/form-data" >
	<div class="DSEdit">
		<div class="DSFeld2 smallBorder">
			<div class="DSFeld1">
				<input type="checkbox" name="fullLoad" value="0" > vollständiger Reload Bestände und Preise
			</div>
		</div>
		<div class="DSFeld2 right" style="background: #AA5555;"><input type="submit" name="priceStockUpdate" value="Aktualisierung" onclick="wartemal('on')"></div>
	</div>
</form>

<h2>TB.Orders2Facto Konverter *</h2>
<form action="#" method="POST" enctype="multipart/form-data" >
	<div class="DSEdit">
		<div class="DSFeld2 smallBorder">
			TB.Orders CSV Datei</br><input type="file" name="csvorders" >
		</div>
		<div class="DSFeld2 right" style="background: #AA5555;"><input type="submit" name="orders2fac" value="Konvertieren" onclick="wartemal('on')"></div>
	</div>
</form>

<p> * Diese Dateien werden bei konfiguriertem Cronjob automatisch in das entsprechende System übertragen. Die zum Download angebotenen Dateien sind nur zu Kontrollzwecken zu verwenden.</p>