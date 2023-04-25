<h2>Shopware6 Filter aktivieren</h2>


<?php
if(!empty($result)) {
	print "Erfolgreich gespeichert!";
}

foreach($paramArray as $key => $data) {
	if (!empty($data["filter"])) {
		$bgcolor = "green ";
		$newstate = false;
	} else {
		$bgcolor = "red";
		$newstate = true;
	}
 print '<form action="#'.$key.'" method="POST" enctype="multipart/form-data" >
		<input type="hidden" name="filterKey" value="'.$key.'" ">
		<input type="hidden" name="filterState" value="'.$newstate.'" ">
				<div id="'.$key.'"class="DSEdit">
					<div class="DSFeld2 " >'.$data["name"].' ('.$data["cnt"].' Artikel)</div>
					<div class="DSFeld1 right"><input class="'.$bgcolor.'" type="submit" name="Filter" value="Filter" "></div>
				</div>
		</form>';
}

?>