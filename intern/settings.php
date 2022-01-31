<h2>Einstellungen</h2>
<div class="DSEdit">
<?php

 include './intern/autoload.php';
 include ("./intern/config.php");
 
 if (isset($_COOKIE['packstation'])) {
 	//print "Cookie found ...";
 	$printer = unserialize(base64_decode($_COOKIE['packstation']));
 	$_SESSION["printerLabel"] = $printer["printerLabel"];
 	$_SESSION["printerA4"] = $printer["printerA4"];
 	
 }
 
 include ('./intern/views/settings_packstation_view.php');
 
 
 ?>
 </div>