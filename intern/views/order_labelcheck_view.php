<div id="OrderFinished"  <?php if($orderPacked == 1) {print 'style="display:block;"'; } ?> >
<div class="windowheadline" onmousedown="startDrag(this.parentNode);"></div>
	<h1>Sendungsdaten</h1>

	<form action="#" method="POST" enctype="multipart/form-data" >
		<input type = hidden name="orderId" value="<?php  print($packOrder->orderHeader["BelegID"]); ?>"  required>
		<input type = hidden name="scanId" value="<?php  print($_SESSION['ItemScanKey']); ?>"  required>
	    <h3>Adresse prüfen:</h3>
		<div class="DSEdi smallBordert">
			<div class="DSEdit smallBorder"> 
				<div class="DSFeld1">Name<br> <input name="adressName1" value="<?php  print($packOrder->orderHeader["Name"]); ?>"  required length=100></div>
				<div class="DSFeld1">Adresszusatz<br> <input id=AdZu name="adressName2" value="<?php  print($packOrder->orderHeader["Adresse2"]); ?>" pattern="[0-9A-Za-zäöüß/\-\. ]*" >
				 <div class="btnStyle"  onclick="toField('AdZu','HNr')">&#11015;</div>
				</div>
			</div>
			<div class="DSEdit smallBorder"> 
				<div class="DSFeld2">Straße<br> 
				 <input id= AdStr name="adressStreet" value="<?php  print($packOrder->orderHeader["Adresse1"]); ?>" pattern="[0-9A-Za-zäöüß\-\. ]+" required>
				 <div class="btnStyle" onclick="toField('AdStr','HNr')">&#10145;</div>
				</div>
				<div class="DSFeld1">Hausnummer<br> <input id=HNr name="adressNumber" value="<?php  print($packOrder->orderHeader["HNummer"]); ?>" pattern="[0-9A-Za-z\- ]+" required ></div>
			</div>
			<div class="DSEdit smallBorder"> 
				<div class="DSFeld1">PLZ<br> <input name="adressPostCode" value="<?php  print($packOrder->orderHeader["PLZ"]); ?>" pattern="[0-9]{4,5}"  required></div>
				<div class="DSFeld3">Ort<br> <input name="adressCity" value="<?php  print($packOrder->orderHeader["Ort"]); ?>" pattern="[A-Za-zäöüß0-9\- /\.()]+"  required></div>
				<div class="DSFeld1">Land<br> <input name="adressCountry" value="<?php  print($packOrder->orderHeader["Land"]); ?>" pattern="[A-Za-zäöüß0-9\- /]+"  required></div>
			</div>	
		</div>
	    <h3>Paketscheine:</h3>
		<div id="packLabels">
		<?php
		 for($cnt = $labeledPacks; $cnt < count($packs); $cnt++) {
			print '<div class="DSEdit smallBorder" name="packLabel">
				<div class="DSFeld1">Paketgewicht<br> <input type=numeric name="packWeight[]" value="'.$packs[$cnt]["weight"].'" pattern="^([1-9]|0\.[2-9])[0-9\.]*" required placeholder="Zahl größer 0, Punkt als Dezimalzeichen"></div>
				<div class="DSFeld1">Spedition<br> <input name="parcelService[]" value="DHL" pattern="(DHL|DPD|DAX)" required ></div>
				<div class="DSFeld1" name="addremoveLabel"><input type=button value="+" onclick="newPack(this)"> 
					<input type=button value="-" onclick="delPack(this)"></div>
			</div>';
		 }
	    ?>
		</div>
			<div class="DSFeld2 right" style="background: #AA5555;"><input id="finishingOrderBtn" type="submit" name="finishingOrder" value="Versandlabel erstellen"></div>
		</div>
	</form>


</div>