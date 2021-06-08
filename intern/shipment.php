<pre>
<?php
 include './intern/autoload.php';

 // Funktionsmenü Pickliste
 $userData = new user($_SESSION["uid"]);
 $userPickData = $userData->getPickLists('0,1');
 $userPackOrder = $userData->getOrderCount(0);
 $allPackOrder["offen"] = $userData->getAllOrderCount('0');
 $allPackOrder["gepackt"] = $userData->getAllOrderCount('1');
 include("./intern/views/picklist_menu_view.php");

 if ((isset($_POST["generatePicklist"])) and ($_POST["generatePicklist"] == "Speichern")) {
	// neue Pickliste erstellen 
	

	print "Pickliste wird generiert ...";
	$pickListData = new picklist($_SESSION["uid"],$_POST["pickListCount"],$_POST["pickListWeight"], $_POST["pickListName"]);
	print " erstellt!<br>";
	// Pickliste anzeigen
	include("./intern/views/picklist_head_view.php");
	include("./intern/views/picklist_pos_view.php");
	
 } elseif ((isset($_POST["finishingOrder"])) )  {
    // Bestellung abschließen
	if ($_POST["scanId"] == $_SESSION['ItemScanKey']) {
		$packOrder = new order($_POST["orderId"]);
		$packOrder->setAdress($_POST["adressName1"], $_POST["adressName2"], $_POST["adressNumber"], $_POST["adressStreet"], $_POST["adressPostCode"], $_POST["adressCity"], $_POST["adressCountry"]);
		if (count($_POST["packWeight"]) == count($_POST["parcelService"])) {
			$parcelData = []; 
			for($i = 0; $i < count($_POST["packWeight"]); $i++) {
				$parcelData[] = [ 'packWeight' => $_POST["packWeight"][$i], 'parcelService' => $_POST["parcelService"][$i] ];	
			}
		}
		$packOrder->exportDHLShipping($parcelData);
		$orderPacked = $packOrder->orderHeader["ship_status"];
		if ($orderPacked == 2) {
			$invoice_sendStatus = $packOrder->sendInvoice();
		} else {
			$invoice_sendStatus = false;
		}
		include("./intern/views/order_finished_view.php");
		
		
	} else {
		print "<error>Fehler bei der Zuordnung der ScanID!</error>";
	}
 } else {
 


	// Wenn noch eine Pickliste offen ist
	if ((count($userPickData) == 1) or (!empty($_POST["editPickList"])) or  (!empty($_SESSION["pickid"])) ){
		if ((isset($_POST["pickid"])) and is_numeric($_POST["pickid"])) {
		    $_SESSION["pickid"] = $_POST["pickid"];
		} elseif (isset($userPickData[0]["pickid"])) {
			$_SESSION["pickid"] = $userPickData[0]["pickid"];
		} else {
			$_SESSION["pickid"] = 0;	
		}
		
		$pickListData = new picklist($_SESSION["pickid"]);

		if ( isset($_POST["showPickItems"]) ) {
			// Pickliste anzeigen
			include("./intern/views/picklist_head_view.php");
			include("./intern/views/picklist_pos_view.php");

		} elseif ( (isset($_POST["showPackOrder"]))  or (isset($_GET["showPackOrder"])) ) {

			 // Verifizierung des Seitenaufrufes
			$_SESSION['ItemScanKey'] = bin2hex(random_bytes(10));

			// Einzelbestellung packen
			$packOrder = $pickListData->getNextPackOrder();
			 if (count($pickListData->getOrderList("0,1")) == 0) {
				unset($_SESSION["pickid"]); 
				$pickListData->setPickStatus(2);
				include("./intern/views/picklist_generate_view.php");
			 } else {

				include("./intern/views/order_head_view.php");
				
				$packs = $packOrder->calcPacks(30);
				$labeledPacks = count($packOrder->getTrackingCodes());
				if ($labeledPacks == NULL) { $labeledPacks = 0; }
				
				$currentPack = -1;
				//while ( $item = $packOrder->getNextItembyPack() ) {

				while ( $item = $packOrder->getNextItem() ) {

					if (isset($item[0]["packNumber"])) {
						if ($item[0]["packNumber"] <> $currentPack) { 
							if ($currentPack >= 0) { print"</div>"; }
							$currentPack++; 
							print '<div class="DSEdit"><h1>Paket Nr.'.($currentPack+1)."</h1>"; 
						}
					}	
					include("./intern/views/order_pos_view.php");
				}

				if ($packOrder->orderHeader["HNummer"] == '') {
					if (preg_match("( [0-9]*$)",$packOrder->orderHeader["Adresse1"],$match)) {
						$packOrder->orderHeader["HNummer"] = trim($match[0]);
						$packOrder->orderHeader["Adresse1"] = substr($packOrder->orderHeader["Adresse1"],0,strlen($match[0])*(-1));
					}
				}
				
				$orderPacked = $packOrder->orderHeader["ship_status"];
				include("./intern/views/order_labelcheck_view.php");

			 }


		
		} elseif ( isset($_POST["showOrderList"]) ) {
			// Übersicht offene Bestellungen der Pickliste
			include("./intern/views/picklist_overview_view.php");
		}  

		
	 }	elseif (count($userPickData) > 1) {

		include("./intern/views/picklist_select_view.php");
		
	 } else {
		 
		include("./intern/views/picklist_generate_view.php");
		 
	 }
 } 
 
?>