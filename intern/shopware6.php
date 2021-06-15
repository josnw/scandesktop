
<?php
 include './intern/autoload.php';
 include ("./intern/config.php");

 if (isset($_POST["addArticles"]) or (isset($argv) and in_array("/addArticles", $argv))) {
     
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
     print "<pre>";
     $shopwareApi = new OpenApi3Client($shopware6_url, $shopware6_user, $shopware6_key);
     
     $articles = new Shopware6Articles();
     
     $result = $articles->exportAllNew($shopwareApi, $noUpload , 0);
     
     $rowCount = $result['count'];
     $errorList = $result['errors'];
     $articleList = $result['articleList'];
     
     if (php_sapi_name() != 'cli') {
         include("./intern/views/shopware_result_view.php");
     } else {
         print_r($result);
     }
     print "</pre>";
 }
 
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
	
	$shopwareApi = new OpenApi3Client($shopware6_url, $shopware6_user, $shopware6_key);
	
	$articles = new Shopware6Articles();
	
	$articles->articleUpdateList($checkDate);
	
	$result = $articles->updateSW6StockPrice($shopwareApi, $noUpload);
	$rowCount = $result['count'];
	$errorList = $result['errors'];

	
	if (php_sapi_name() != 'cli') {
		include("./intern/views/shopware_result_view.php");
	} else {
		print_r($result);
	}
}

 if (isset($_POST["getOrders"]) or (isset($argv) and in_array("/getOrders", $argv))) {
	print "<pre>";
 	$shopwareApi = new OpenApi3Client($shopware6_url, $shopware6_user, $shopware6_key); 

 	if (! is_array($ShopwareIdWebshop) ) {
 		$salesChannels = [ $ShopwareIdWebshop ];
 	} else {
 		$salesChannels = $ShopwareIdWebshop;
 	}
 	foreach ($salesChannels as $salesChannel) {
 		$ordersApi = new shopware6Orders($shopwareApi, $salesChannel);
		$orders = $ordersApi->getOrderList();
		
		if (count($orders['data']) > 0) {
			$facfile = new myfile($docpath."/ORDERS_SW".time().".FAC","new");
			$facfile->writeUTF8BOM();
			
	
			$rowCount = 0;
			foreach ($orders['data'] as $order) {
				print "Download Order #: ".$order["id"]." ( #".$order['attributes']['orderNumber'].")<br/>\n";
				$facOrderData = $ordersApi->getOrderFacData($order['id']);
				// print_r($facOrderData); exit;
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
				
				$ordersApi->setOrderState($order['id'], ORDER_STATE_PROCESS);
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
	print "</pre>";

}

if (php_sapi_name() == 'cli') {
	// no form output on console
	exit;
}

include("./intern/views/shopware_select_view.php"); 

?>