
<h2>Shopware price- & instock- Aktualisierung manuell erstellen *</h2>

<form action="#" method="POST" enctype="multipart/form-data" >
	<div class="DSEdit">
		<div class="DSFeld2 smallBorder">
			<div class="DSFeld1">
				<input type="checkbox" name="fullLoad" value="0" > vollständiger Reload Bestände und Preise
			</div>
			<div class="DSFeld1">
				<input type="checkbox" name="noUpload" value="1" checked> ohne API Upload
			</div>
		</div>
		<div class="DSFeld2 right" style="background: #AA5555;"><input type="submit" name="priceStockUpdate" value="Aktualisierung" onclick="wartemal('on')"></div>
	</div>
</form>

<h2>Shopware neue Bestellungen abholen *</h2>

<form action="#" method="POST" enctype="multipart/form-data" >
	<div class="DSEdit">
		<div class="DSFeld2 right" style="background: #AA5555;"><input type="submit" name="getOrders" value="Bestellungen abholen" onclick="wartemal('on')"></div>
	</div>
</form>

<p> * Diese Dateien werden bei konfiguriertem Cronjob automatisch in das entsprechende System übertragen. Die zum Download angebotenen Dateien sind nur zu Kontrollzwecken zu verwenden.</p>