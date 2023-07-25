<h2>Shopware6 Artikel Varianten zuordnen</h2>
<form action="#" method="POST" enctype="multipart/form-data" >
<?php if ($doubleKeys) {
	print '<div class="DSFeld2 right"><input type="submit" name="singleKey" value="eindimensonale Variantenmatrix"></div>';
} else {
	print '<div class="DSFeld2 right"><input type="submit" name="doubleKeys" value="zweidimensonale Variantenmatrix" ></div>';
}?>
</form>
<form action="#" method="POST" enctype="multipart/form-data" >
	<div class="DSEdit">
		<div class="DSFeld2 " >Shopware-ID des Hauptartikels:<br/><input type="text" name="mainArticle" value=""  required "></div>
		<div class="DSFeld2" >Merkmal 1:<br/><input type="text" name="optionkey1" value=""  required ></div>
		<?php if ($doubleKeys) {
			print '<div class="DSFeld2" >Merkmal 2:<br/><input type="text" name="optionkey2" value=""  required ></div>';
		}?>
		<div class="DSFeld1" > </div>
	</div>
	<div class="DSEdit">
		<div class="DSFeld2 right">Produktnummer der Variante:<br><input type="text" name="subArticle[]" value="" required ></div>
		<div class="DSFeld2 right">Wert Merkmal 1:<br><input type="text" name="optionvalue1[]" value=""  required p></div>
		<?php if ($doubleKeys) {
			print '<div class="DSFeld2 right">Wert Merkmal 2:<br><input type="text" name="optionvalue2[]" value=""  required p></div>';
		}?>
		<div class="DSFeld1" name="addremoveLabel"><input type=button value="+" onclick="newPack(this)"> 
		<input type=button value="-" onclick="delPack(this)"></div>
	</div>
	<div class="DSEdit">
		<div class="DSFeld2 right"><input type="submit" name="setMainArticle" value="Speichern" onclick="wartemal('on')"></div>
	</div>
</form>

