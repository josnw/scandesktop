<h2>Einstellungen</h2>
<div class="DSEdit">
<?php



 include './intern/autoload.php';
 include ("./intern/config.php");
 
 if (isset($_COOKIE['packstation'])) {
 	print "Cookie found ...";
 	$printer = unserialize(base64_decode($_COOKIE['packstation']));
 	$_SESSION["printerLabel"] = $printer["printerLabel"];
 	$_SESSION["printerA4"] = $printer["printerA4"];
 	$_SESSION["minPickListWeight"] = $printer["minPickListWeight"];
 	$_SESSION["maxPickListWeight"] = $printer["maxPickListWeight"];
 	$_SESSION["pickListCount"] = $printer["pickListCount"];
 	$_SESSION["pickListPlacePattern"] = $printer["pickListPlacePattern"];
 	
 }
 
 include ('./intern/views/settings_packstation_view.php');
 
 if ($_SESSION["level"] == 9) {
 	
 	if (!empty($_POST["setConfig"]) and !empty($_POST["config"] ) ) {
 		
 		$pw_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
 			$qry  = 'select penr, qna1, qna2, qgrp, pusr, pcod,ptat,qkkz  from public.per_0 where penr = :personal';
 		$pw_qry = $pw_pdo->prepare($qry);
 		$pw_qry->bindValue(':personal', $wwsAdminUsers[0]);
 		$pw_qry->execute() or die (print_r($pw_qry->errorInfo()));;
 		$pw_row = $pw_qry->fetch( PDO::FETCH_ASSOC );
 		
 		$fpcod = explode('#',$pw_row['pcod']);
 		if ($fpcod[2] == strtoupper(sha1($_POST['password']))) {
	 		file_put_contents('./intern/config.php', $_POST['config']);
 			print "<h3>Konfiguration gespeichert!</h3>";
 		} else {
 			print "<error>Masteradmin Passwort ist notwendig!</error>";
 		}
 	}
 	
 	
 	
 	include ('./intern/views/settings_admin_view.php');
 }
 	
 
 
 ?>
 </div>