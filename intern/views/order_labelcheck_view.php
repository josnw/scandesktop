<div id="OrderFinished"  <?php if($orderPacked == 1) {print 'style="display:block;"'; } ?> >
<div class="windowheadline" onmousedown="startDrag(this.parentNode);"></div>
	<h1>Sendungsdaten</h1>
    <?php if(!empty($errorList)) { print($errorList); } ?>
	<form action="#" method="POST" enctype="multipart/form-data" >
		<input type = hidden name="orderId" value="<?php  print($packOrder->orderHeader["fblg"]); ?>"  required>
		<input type = hidden name="scanId" value="<?php  print($_SESSION['ItemScanKey']); ?>"  required>
		<div class="DSEdi smallBordert">
			<div class="DSEdit smallBorder"> 
				<div class="DSFeld1">Name<br> 
					<input name="qna1" value="<?php  print($_SESSION["shipBlueprint"]["receiverAddress"]["firstName"]); ?>"  required length=100> 
					<input name="qna2" value="<?php  print($_SESSION["shipBlueprint"]["receiverAddress"]["lastName"]); ?>"  length=100>
				</div>
				<div class="DSFeld1">Adresszusatz<br> 
					<div><input id=qna3 name="qna3" value="<?php  print($_SESSION["shipBlueprint"]["receiverAddress"]["company"]); ?>" pattern="[0-9A-Za-zäöüß/\-\. ]*" > <div class="btnStyle"  onclick="toField('qna3','qstr')">&#11015;</div></diV>
					<div><input id=qna4 name="qna4" value="<?php  print($_SESSION["shipBlueprint"]["receiverAddress"]["department"]); ?>" pattern="[0-9A-Za-zäöüß/\-\. ]*" > <div class="btnStyle"  onclick="toField('qna4','qstr')">&#11015;</div></diV>
					<div><input id=qna5 name="qna5" value="<?php  print($_SESSION["shipBlueprint"]["receiverAddress"]["addressAddition"]); ?>" pattern="[0-9A-Za-zäöüß/\-\. ]*" > <div class="btnStyle"  onclick="toField('qna5','qstr')">&#11015;</div></diV>
				</div>
			</div>
			<div class="DSEdit smallBorder"> 
				<div class="DSFeld2">Straße<br> 
				 <input id= qstr name="qstr" value="<?php  print($_SESSION["shipBlueprint"]["receiverAddress"]["street"]); ?>" pattern="[0-9A-Za-zäöüß\-\. ]+" required>
				 <div class="btnStyle" onclick="toField('qshnr','qshnr')">&#10145;</div>
				</div>
				<div class="DSFeld1">Hausnummer<br> <input id=qshnr name="qshnr" value="<?php  print($_SESSION["shipBlueprint"]["receiverAddress"]["houseNumber"]); ?>" pattern="[0-9A-Za-z\- ]+" required ></div>
			</div>
			<div class="DSEdit smallBorder"> 
				<div class="DSFeld1">PLZ<br> <input name="qplz" value="<?php  print($_SESSION["shipBlueprint"]["receiverAddress"]["zipCode"]); ?>" pattern="[0-9]{4,5}"  required></div>
				<div class="DSFeld3">Ort<br> <input name="qort" value="<?php  print($_SESSION["shipBlueprint"]["receiverAddress"]["city"]); ?>" pattern="[A-Za-zäöüß0-9\- /\.()]+"  required></div>
				<div class="DSFeld1">Land<br> <input name="qlnd" value="<?php  print($_SESSION["shipBlueprint"]["receiverAddress"]["countryIso"]); ?>" pattern="[A-Za-zäöüß0-9\- /]+"  required></div>
			</div>	
			<div class="DSEdit smallBorder"> 
				<div class="DSFeld1">Spedition<br> <input name="parcelService" value="<?php  print($_SESSION["shipBlueprint"]["carrierTechnicalName"]); ?>" pattern="(dhl)" required ></div>
				<div class="DSFeld1">Versandart<br>
					<select name="parcelProduct">
					 <option value="V01PAK"<?php  if ($_SESSION["shipBlueprint"]["shipmentConfig"]["product"] == "V01PAK") { print "selected"; } ?>">DHL Paket</option>
					 <option value="V62WP" <?php  if ($_SESSION["shipBlueprint"]["shipmentConfig"]["product"] == "V62WP") { print "selected"; } ?>">DHL Warenpost</option>
					</select>
				</div>
			</div>	
		</div>
	    <h3>Paketscheine:</h3>
		<div id="packLabels">
		<?php
		 for($cnt = $labeledPacks; $cnt < count($packs); $cnt++) {
			print '<div class="DSEdit smallBorder" name="packLabel">
				<div class="DSFeld2">Paketgewicht <input type=numeric name="packWeight[]" value="'.$packs[$cnt]["agew"].'" pattern="^([1-9]|0\.[2-9])[0-9\.]*" required placeholder="Zahl größer 0, Punkt als Dezimalzeichen"></div>
				<div class="DSFeld1" name="addremoveLabel"><br><input type=button class="minibutton"  value=" + " onclick="newPack(this)"> . <input type=button class="minibutton" value=" - " onclick="delPack(this)"></div>
			</div>';
		 }
	    ?>
		</div>
			<div class="DSFeld2 right" style="background: #AA5555;"><input id="finishingOrderBtn" type="submit" name="finishingOrder" value="Versandlabel erstellen"></div>
		</div>
	</form>


</div>