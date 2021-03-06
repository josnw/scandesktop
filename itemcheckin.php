<?php
session_start();
 include './intern/auth.php';
 include './intern/config.php';
 include './intern/autoload.php';
 include './intern/functions.php';

 if ((isset($_GET["scanId"])) and ($_GET["scanId"] == $_SESSION['ItemScanKey'])) {
	 
	if ((isset($_GET["typ"])) and ($_GET["typ"] == 'productid')) {
		
		$getItemID = preg_replace("[^0-9A-Z]","",$_GET["itemId"]);
		$getOrderID = preg_replace("[^0-9]","",$_GET["orderId"]);
		$getPackId = preg_replace("[^0-9]","",$_GET["packId"]);
		
		$scanedItem = new product($getItemID);
		$packingOrder = new order($getOrderID);
		$productID = $scanedItem->getProductId();
				
		if ( $productID == NULL) {
			if ( $scanedItem->getResultCount() > 1) {
				print json_encode(["status" => false, "info" => "Artikelzuordnung nicht eindeutig!"]);	
			} else {
				print json_encode(["status" => false, "info" => "Artikel nicht gefunden 2"]);	
			}
		} else {
			$checkResult = $packingOrder->setPacked($scanedItem->getProductId(), $getPackId);
			print json_encode($checkResult);
		}
	} elseif ((isset($_GET["typ"])) and ($_GET["typ"] == 'parcelId')) {
		$dhl = new dhl();
		//$dhl->trackingIdImport();
		if ($dhl->checkIdent($getItemID)) {
			$trackIdResult = $dhl->setTrackingId($getItemID,$getOrderID);
			if ( $trackIdResult["status"] == true ) {
				print json_encode(["itemId" => "Order", "itemPacked" => "packed" ,"status" => true, "packId" =>  $getItemID]);	
			} else {
				if ($DEBUG == 1) {
					print json_encode(["itemId" => "Order", "itemPacked" => "packed" ,"status" => true, "packId" =>  $getPackId, "info" => "Der TrackingCode ist bereits in Verwendung!"]);	
				} else {
					print json_encode(["itemId" => "", "itemPacked" => "" ,"status" => false, "info" => "Der TrackingCode ist bereits in Verwendung!"]);	
					proto(print_r($trackIdResult["info"],1));
				}

			}
		} else {
			print json_encode(["itemId" => "", "itemPacked" => "" ,"status" => false, "info" => "Fehlerhafter IdentCode!"]);
		}
	}	
		
	} else {
		print json_encode(["status" => false, "info" => "AuthCode Fehler"]);	
	}

?>