<?php
 include './intern/autoload.php';
 include ("./intern/config.php");

 if (isset($_POST["priceStockUpdate"]) or (isset($argv) and in_array("/priceStockUpdate", $argv))) {
	
	if (isset($_POST["fullLoad"]) or (isset($argv) and in_array("/fullLoad", $argv))) {
		$checkDate = '2000-01-01';
	} else {
		$checkDate = NULL;
	}
	
	if (isset($_POST["noUpload"]) or (isset($argv) and in_array("/noUpload", $argv))) {
		$noUpload = 'noUpload';
	} else {
		$noUpload = NULL;
	}
	
	$shopwareApi = new RestApiClient($shopware_url, $shopware_user, $shopware_key); 
	
	$articles = new shopwareArticles();
	if (php_sapi_name() == 'cli') { print "Generate Artikellist ... "; }
	$articles->articleUpdateList($checkDate);
	if (php_sapi_name() == 'cli') { print "done.\n"; }
	$result = $articles->exportToShopware($shopwareApi, $noUpload);
	$rowCount = $result['count'];
	$errorList = $result['errors'];

	
	if (php_sapi_name() != 'cli') {
		include("./intern/views/shopware_result_view.php");
	} else {
		print_r($result);
	}
}

 if (isset($_POST["getOrders"]) or (isset($argv) and in_array("/getOrders", $argv))) {
	
	$shopwareApi = new RestApiClient($shopware_url, $shopware_user, $shopware_key); 
	
	$ordersApi = new ShopwareOrders($shopwareApi);
	$orders = $ordersApi->getOrderList();
	
	if (count($orders['data']) > 0) {
		$facfile = new myfile($docpath."/ORDERS_SW".time().".FAC","new");
		$facfile->writeUTF8BOM();
		

		$rowCount = 0;
		foreach ($orders['data'] as $order) {
			print "Download Order #: ".$order['id']."<br/>\n";
			$facOrderData = $ordersApi->getOrderFacData($order['id']);
			if (isset($facOrderData["Customer"])) {
				$facfile->facHead("KUN_0", $channelFacData['shopware']['Filiale']);
				$facfile->facData($facOrderData["Customer"]);
			}
			
			$facfile->facHead("AUFST_KOPF", $channelFacData['shopware']['Filiale']);
			$facfile->facData($facOrderData["Head"]);
			$rowCount++;
			foreach($facOrderData["Pos"] as $facpos) {
				$facfile->facHead("AUFST_POS",  $channelFacData['shopware']['Filiale']);
				$facfile->facData($facpos);
			}
			
			$ordersApi->setOrderState($order['id'], 1);
		}
		$facfile->facfoot();
		$exportfile = $docpath.$facfile->getCheckedName();
		$filename = $facfile->getCheckedName();

		if (php_sapi_name() != 'cli') {
			include("./intern/views/shopware_result_view.php");
		} else {
			print($filename."\n");
		}
	} else {
		print "No Orders found!\n";
	}

}

if (php_sapi_name() == 'cli') {
	// no form output on console
	exit;
}

include("./intern/views/shopware_select_view.php"); 

?>