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
		
		if (count($_POST["packWeight"]) == count($_POST["parcelService"])) {
			$parcelData = []; 
			for($i = 0; $i < count($_POST["packWeight"]); $i++) {
				$_SESSION["shipBlueprint"]["parcels"][$i]["weightOverwrite"]["value"] = $_POST["packWeight"][$i];
				$_SESSION["shipBlueprint"]["parcels"][$i]["weightOverwrite"]["unit"] = "kg";
			}
		}
		

		$_SESSION["shipBlueprint"]["receiverAddress"]["firstName"] = $_POST["qna1"];
		$_SESSION["shipBlueprint"]["receiverAddress"]["lastName"] = $_POST["qna2"];
		$_SESSION["shipBlueprint"]["receiverAddress"]["company"] = $_POST["qna3"];
		$_SESSION["shipBlueprint"]["receiverAddress"]["department"] = $_POST["qna4"];
		$_SESSION["shipBlueprint"]["receiverAddress"]["addressAddition"] = $_POST["qna5"];
		$_SESSION["shipBlueprint"]["receiverAddress"]["street"] = $_POST["qstr"];
		$_SESSION["shipBlueprint"]["receiverAddress"]["houseNumber"] = $_POST["qshnr"];
		$_SESSION["shipBlueprint"]["receiverAddress"]["zipCode"] = $_POST["qplz"];
		$_SESSION["shipBlueprint"]["receiverAddress"]["city"] = $_POST["qort"];
		$_SESSION["shipBlueprint"]["receiverAddress"]["countryIso"] = $_POST["qlnd"];
		$_SESSION["shipBlueprint"]["carrierTechnicalName"]["countryIso"] = $_POST["parcelService"];
		$_SESSION["shipBlueprint"]["shipmentConfig"]["product"] = $_POST["parcelProduct"];
		
		
		$response = $packOrder->exportShipping();
		
		if ($response["status"]) {
		
		    $delivery = $packOrder->genDeliver();
		    $labelLink = $response["link"];
		    $shippingId = $response["shippingId"];
		    include("./intern/views/order_finished_view.php");
		    
		} else {
		    $orderPacked = $packOrder->orderHeader["ktos"];
		    $errorList = $response["error"]; 
		    include("./intern/views/order_labelcheck_view.php");
		    
		}
		
		

		
		
		
	} else {
		print "<error>Fehler bei der Zuordnung der ScanID!</error>";
	}
 } else {
 


	// Wenn noch eine Pickliste offen ist
	if ((count($userPickData) == 1) or (!empty($_POST["editPickList"])) or  (!empty($_SESSION["pickid"])) ) {
		if ((isset($_POST["pickid"])) and is_numeric($_POST["pickid"])) {
		    $_SESSION["pickid"] = $_POST["pickid"];
		} elseif (isset($userPickData[0]["fprn"])) {
			$_SESSION["pickid"] = $userPickData[0]["fprn"];
		} else {
			$_SESSION["pickid"] = 0;	
		}

		$pickListData = new picklist($_SESSION["pickid"]);
		
		if ((isset($_POST["removeOrder"])) and ($_POST["removeOrder"] == "Zurückstellen")) {
		    $pickListData->removeFromPickList($_POST['orderId']);
		    $_POST["showPackOrder"] = "Bestellung bearbeiten";
		    unset($_POST["showPickItems"]);
		}
		
		
		if ((isset($_POST["pickListSrvPrint"])) and ($_POST["pickListSrvPrint"] == "Pickliste Serverprint")) {
			$fp = fopen("./docs/".$picList.$_SESSION["pickid"]."html",w);
			fwrite ($fp, '<html><head>	<meta charset="utf-8"><link rel="stylesheet" type="text/css" href="./css/masterprint.css"></head><body>');
			foreach($pickListData->getItemList() as $item => $itemdata) {
				fwrite ($fp, '<div class="DSEdit flexnowrap " id="OrderItem'.$item.'">');
				fwrite ($fp,  '<div class="DSFeld1  mediFont">'.$itemdata["arnr"].' (L'.$itemdata["alag"].')<br/>'.$itemdata["asco"].'</div>');
				fwrite ($fp,  '<div class="DSFeld2  mediFont">'.$itemdata["abz1"]." ".$itemdata["abz2"].'</div>');
				fwrite ($fp,  '<div class="DSFeld1 centerText mediFont">'.number_format($itemdata["fmge"]).' '.$itemdata["ameh"].'</div>');
				fwrite ($fp,  '</div>');
			}
			fwrite ($fp, '</body></html>');
			exec('lp -d pack_prn02 "'."./docs/".$picList.$_SESSION["pickid"]."html".'"');
		}

		if ( isset($_POST["showPickItems"]) ) {
			// Pickliste anzeigen
			include("./intern/views/picklist_head_view.php");
			include("./intern/views/picklist_pos_view.php");

		} elseif ( (isset($_POST["showPackOrder"]))  or (isset($_GET["showPackOrder"])) ) {

			 // Verifizierung des Seitenaufrufes
			$_SESSION['ItemScanKey'] = bin2hex(random_bytes(10));

			// Einzelbestellung packen
			if ($_POST['sortorder'] == 'weight') {
			    $sort1 = 'sgew'; $sort2 = 'k.fdtm desc';
			} elseif ($_POST['sortorder'] == 'rank') {
			    $sort1 = 'count(arnr)'; $sort2 = 'k.fnum';
			} else {
			    $sort1 = 'k.fdtm'; $sort2 = 'k.fnum';
			} 

			$packOrder = $pickListData->getNextPackOrder($sort1, $sort2);
			 if (count($pickListData->getOrderList("0,1")) == 0) {
				unset($_SESSION["pickid"]); 
				$pickListData->setPickStatus(2);
				include("./intern/views/picklist_generate_view.php");
			 } else {

				include("./intern/views/order_head_view.php");
				
				$packs = $packOrder->calcPacks(30);

				$labeledPacks = 0; // count($packOrder->getTrackingCodes());
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

/*				if ($packOrder->orderHeader["HNummer"] == '') {
					if (preg_match("( [0-9]*$)",$packOrder->orderHeader["Adresse1"],$match)) {
						$packOrder->orderHeader["HNummer"] = trim($match[0]);
						$packOrder->orderHeader["Adresse1"] = substr($packOrder->orderHeader["Adresse1"],0,strlen($match[0])*(-1));
					}
				}
*/
				$orderPacked = $packOrder->orderHeader["ktos"];
				$_SESSION["shipBlueprint"] = $packOrder->getShippingBlueprint();
				
/*				for($cnt = $labeledPacks; $cnt < count($packs); $cnt++) {

					$_SESSION["shipBlueprint"]["parcels"][$cnt]["weightOverwrite"]["value"] = $packs[$cnt]["agew"];
					$_SESSION["shipBlueprint"]["parcels"][$cnt]["weightOverwrite"]["unit"] = "kg";
				}
*/
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