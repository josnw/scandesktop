<?php
if (!empty($_POST["COOKIE"])) {
	
	if (!empty($_POST["setPrinter"]) and $_POST["setPrinter"] == "Speichern") {
		$printer = [];
		$printer["printerLabel"] = $_POST["COOKIE"]["printerLabel"];
		$printer["printerA4"] = $_POST["COOKIE"]["printerA4"];
		$printer["minPickListWeight"] = $_POST["COOKIE"]["minPickListWeight"];
		$printer["maxPickListWeight"] = $_POST["COOKIE"]["maxPickListWeight"];
		$printer["pickListCount"] = $_POST["COOKIE"]["pickListCount"];
		$printer["pickListPlacePattern"] = $_POST["COOKIE"]["pickListPlacePattern"];
		if (setcookie("packstation", base64_encode(serialize($printer)), time()+315360000)) {
			print "Keks erfolgreich gespeichert!<br>";
		}
	}

}

?>