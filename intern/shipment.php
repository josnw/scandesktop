<?php
 include "./intern/config.php";

 if (isset($_COOKIE['packstation'])) {
	//print "Cookie found ...";
 	$printer = unserialize(base64_decode($_COOKIE['packstation']));
	$_SESSION["printerLabel"] = $printer["printerLabel"];
	$_SESSION["printerA4"] = $printer["printerA4"];
	$_SESSION["infobox"] = "Etiketten Drucker: ".$configPrinter['label'][$_SESSION["printerLabel"]]."<br>Picklisten Drucker: ".$configPrinter['a4'][$_SESSION["printerA4"]];
 }
 if (empty($_SESSION["printerLabel"])) {
 	
 	include "./intern/settings.php";
 	exit;
 	
 }

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
 	$pickListData = new picklist($_SESSION["uid"],$_POST["pickListCount"],$_POST["maxPickListWeight"], $_POST["pickListName"],$_POST["minPickListWeight"],$configStorePlace[$_POST["pickListPlacePattern"]]);
	print " erstellt!<br>";
	Proto("Shipment: Picklist erstellt");
	// Pickliste anzeigen
	include("./intern/views/picklist_head_view.php");
	include("./intern/views/picklist_pos_view.php");
	
 } elseif ((isset($_POST["finishingOrder"])) )  {
    // Bestellung abschließen
 	Proto("Shipment: Bestellung wird abgeschlossen ".$_POST["orderId"]);
	if ($_POST["scanId"] == $_SESSION['ItemScanKey']) {
		$packOrder = new order($_POST["orderId"]);
		Proto("Shipment: ".$_POST["orderId"]. "Paketanzahl  _POST: ".count($_POST["packWeight"]));
		
		//if (count($_POST["packWeight"]) == count($_POST["parcelService"])) {
			//$parcelData = [];
			$_SESSION["shipBlueprint"]["parcels"] = [];
			for($i = 0; $i < count($_POST["packWeight"]); $i++) {
				$_SESSION["shipBlueprint"]["parcels"][$i]["weightOverwrite"]["value"] = $_POST["packWeight"][$i];
				$_SESSION["shipBlueprint"]["parcels"][$i]["weightOverwrite"]["unit"] = "kg";
			}
		//}
		

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
		$_SESSION["shipBlueprint"]["carrierTechnicalName"] = $_POST["parcelService"];
		$_SESSION["shipBlueprint"]["shipmentConfig"]["product"] = $_POST["parcelProduct"];
		
		
		$response = $packOrder->exportShipping();
		
		if ($response["status"]) {
			Proto("Shipment: Shipment Label generiert ".$response["shippingId"]);
		    $delivery = $packOrder->genDeliver();
		    $labelLink = $response["link"];
		    $shippingId = $response["shippingId"];
		    include("./intern/views/order_finished_view.php");
		    
		} else {
			Proto("Shipment: Fehler beim Shipment Label ".$response["error"]);
		    $orderPacked = $packOrder->orderHeader["ktos"];
		    $errorList = $response["error"];
		    $packs = $packOrder->calcPacks(30);
		    $labeledPacks = count($packOrder->getTrackingCodes(null, "orderId"));
		    if (!is_numeric($labeledPacks)) { $labeledPacks = 0; }
		    include("./intern/views/order_labelcheck_view.php");
		    
		}
		
		

		
		
		
	} else {
		print "<error>Fehler bei der Zuordnung der ScanID!</error>";
	}
 } elseif ((isset($_POST["labelRePrint"])) )  {
 	Proto("Shipment: ReLabel ".$_POST["orderId"]);
 	$errorList = "lableReprint!";
 	$packOrder = new order($_POST["orderId"]);
 	exec('lp -d '.$_SESSION["printerLabel"].' "'.$_POST["filename"].'"');
 	include("./intern/views/order_finished_view.php");
 	
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
		Proto("Shipment: Öffne Picklist ".$_SESSION["pickid"]);
		$pickListData = new picklist($_SESSION["pickid"]);
		
		if ((isset($_POST["removeOrder"])) and ($_POST["removeOrder"] == "Zurückstellen")) {
		    $pickListData->removeFromPickList($_POST['orderId']);
		    $_POST["showPackOrder"] = "Bestellung bearbeiten";
		    unset($_POST["showPickItems"]);
		}
		
		
		if ((isset($_POST["pickListSrvPrint"])) and ($_POST["pickListSrvPrint"] == "Pickliste Serverprint")) {
			Proto("Shipment: Druck Picklist ".$_SESSION["pickid"]);
			$fp = fopen("./docs/".$picList.$_SESSION["pickid"].".txt",w);
			fwrite( $fp, "Pickliste ".date("Y-m-d")." ".$_SESSION['name']."\n \n" );
			fwrite ($fp, str_pad("Artikel",12," ", STR_PAD_RIGHT)."  ". str_pad("EAN",20," ", STR_PAD_RIGHT).str_pad("Lager",20," ", STR_PAD_LEFT).str_pad("Menge",16," ", STR_PAD_LEFT)."\n");
			foreach($pickListData->getItemList() as $item => $itemdata) {
				fwrite ($fp, str_pad($itemdata["arnr"],12," ", STR_PAD_RIGHT)."  ".str_pad($itemdata["asco"],20," ", STR_PAD_RIGHT).str_pad($itemdata["alag"],6," ", STR_PAD_LEFT)."\n");
				fwrite ($fp, str_pad($itemdata["abz1"],60," ", STR_PAD_RIGHT)."\n");
				fwrite ($fp, str_pad($itemdata["abz2"],60," ", STR_PAD_RIGHT)."\n");
				fwrite ($fp, str_pad($itemdata["abz3"],60," ", STR_PAD_RIGHT).str_pad($itemdata["fmge"],7," ", STR_PAD_LEFT)."  ". str_pad($itemdata["ameh"],5," ", STR_PAD_RIGHT)."\n");
				fwrite ($fp, "-------------------------------------------------------------------------\n");
			}
			exec('lp -d '.$_SESSION["printerA4"].' "'."./docs/".$picList.$_SESSION["pickid"].".txt".'"');
		}

		if ( isset($_POST["showPickItems"]) ) {
			// Pickliste anzeigen
			include("./intern/views/picklist_head_view.php");
			include("./intern/views/picklist_pos_view.php");

		} elseif ( (isset($_POST["showPackOrder"]))  or (isset($_GET["showPackOrder"])) ) {

			 // Verifizierung des Seitenaufrufes
			$_SESSION['ItemScanKey'] = bin2hex(random_bytes(10));

			// Einzelbestellung packen
			$sort1 = $_POST['sortorder'];

			$packOrder = $pickListData->getNextPackOrder($sort1);
			Proto("Shipment: Bestellung zunm Packen geöffnet ".$packOrder->orderHeader["fnum"]);
			if (count($pickListData->getOrderList("0,1,2")) == 0) {
				unset($_SESSION["pickid"]); 
				$pickListData->setPickStatus(3);
				include("./intern/views/picklist_generate_view.php");
			 } else {

				include("./intern/views/order_head_view.php");
				
				$packs = $packOrder->calcPacks(30);

				//$labeledPacks = 0; 
				$labeledPacks = count($packOrder->getTrackingCodes(null, "orderId"));
				if (!is_numeric($labeledPacks)) { $labeledPacks = 0; }
				
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
				$orderPacked = $packOrder->getPackedState();
				
				
				$_SESSION["shipBlueprint"] = $packOrder->getShippingBlueprint();
				$shippingDocuments = $packOrder->getShippingDocuments();
				
				for($cnt = $labeledPacks; $cnt < count($packs); $cnt++) {

					$_SESSION["shipBlueprint"]["parcels"][$cnt]["weightOverwrite"]["value"] = $packs[$cnt]["agew"];
					$_SESSION["shipBlueprint"]["parcels"][$cnt]["weightOverwrite"]["unit"] = "kg";
				}
				$errorList = "LableList:".print_r($shippingDocuments,1);
				Proto("Shipment: LabelCheck für ".$packOrder->orderHeader["fnum"]);
				
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