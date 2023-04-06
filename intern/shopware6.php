
<?php
 include './intern/autoload.php';
 include ("./intern/config.php");
 include_once ("./intern/functions.php");
 
 if (isset($_POST["addArticles"]) or (isset($argv) and in_array("/addArticles", $argv))) {
 	if ($shopware6NoBaseData) {
 		Proto("NoBaseData in Config.php active!");
 		exit;
 	}
 	
 	$starttime = time();
 	
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
     $shopwareApi = new OpenApi3Client($shopware6_url, $shopware6_user, $shopware6_key, $shopware6_type);
     if (!$shopwareApi) {
     	Proto("API ".$shopware6_url." Connection failed!");
     	exit;
     } else {
     	Proto("API ".$shopware6_url." Connection successful!");
     }
     
     $articles = new Shopware6Articles();
     Proto("Starting Export ...");
     
     $result = $articles->exportAllNew($shopwareApi, $noUpload , 0);
     
     $rowCount = $result['count'];
     $errorList = $result['errors'];
     $articleList = $result['articleList'];
     
     if (php_sapi_name() != 'cli') {
         include("./intern/views/shopware_result_view.php");
     } else {
         print_r($result);
     }
 }
 
 if (isset($_POST["getCategoryMapping"]) or (isset($argv) and in_array("/getCategoryMapping", $argv))) {
 	$starttime = time();
 	
 	$shopwareApi = new OpenApi3Client($shopware6_url, $shopware6_user, $shopware6_key, $shopware6_type);
 	if (!$shopwareApi) {
 		Proto("API ".$shopware6_url." Connection failed!");
 		exit;
 	} else {
 		Proto("API ".$shopware6_url." Connection successful!");
 	}
 	
 	$articles = new Shopware6Articles($shopwareApi);
 	
 	$result = $articles->getCategoryWWsMatch();
 	$rowCount = count($result);
 	file_put_contents($sw6GroupMatching, $result);
 	$errorList = json_encode($result);
 	if (php_sapi_name() != 'cli') {
 		include("./intern/views/shopware_result_view.php");
 	} else {
 		print_r($result);
 	}
 }
 
 if (isset($_POST["exportKRG"]) or (isset($argv) and in_array("/exportKRG", $argv))) {
 	$starttime = time();
 	
 	$shopwareApi = new OpenApi3Client($shopware6_url, $shopware6_user, $shopware6_key, $shopware6_type);
 	if (!$shopwareApi) {
 		Proto("API ".$shopware6_url." Connection failed!");
 		exit;
 	} else {
 		Proto("API ".$shopware6_url." Connection successful!");
 	} 	
 	
 	if (isset($_POST["noUpload"]) or (isset($argv) and in_array("/noUpload", $argv))) {
 		$upload = false;
 	} else {
 		$upload = true;
 	}

 	$swcustomer = new Shopware6Customer($shopwareApi);
 	$result = $swcustomer->uploadDiscountGroups($upload);
 	
 	if (php_sapi_name() != 'cli') {
 		include("./intern/views/shopware_result_view.php");
 	} else {
 		print_r($result);
 	}
 }
 
 if (isset($_POST["setArticleOnline"]) or (isset($argv) and in_array("/setArticleOnline", $argv))) {
 	$starttime = time();
 	
 	$shopwareApi = new OpenApi3Client($shopware6_url, $shopware6_user, $shopware6_key, $shopware6_type);
 	
 	$articles = new Shopware6Articles($shopwareApi);
 	if (!$shopwareApi) {
 		Proto("API ".$shopware6_url." Connection failed!");
 		exit;
 	} else {
 		Proto("API ".$shopware6_url." Connection successful!");
 	}
 	
 	$result = $articles->setArticlesOnline();
 	if (php_sapi_name() != 'cli') {
 		include("./intern/views/shopware_result_view.php");
 	} else {
 		print_r($result);
 	}
 }
 
 if (isset($_POST["updateVisibility"]) or (isset($argv) and in_array("/updateVisibility", $argv))) {
 	$starttime = time();
 	
 	$shopwareApi = new OpenApi3Client($shopware6_url, $shopware6_user, $shopware6_key, $shopware6_type);
 	
 	$articles = new Shopware6Articles($shopwareApi);
 	if (!$shopwareApi) {
 		Proto("API ".$shopware6_url." Connection failed!");
 		exit;
 	} else {
 		Proto("API ".$shopware6_url." Connection successful!");
 	}
 	
 	$result = $articles->updateVisibility();
 	if (php_sapi_name() != 'cli') {
 		include("./intern/views/shopware_result_view.php");
 	} else {
 		print_r($result);
 	}
 }
 
 
 if (isset($_POST["updateArticles"]) or (isset($argv) and in_array("/updateArticles", $argv))) {
 	if ($shopware6NoBaseData) {
 		die("NoBaseData in Config.php active!\n");
 	}
 	
 	$starttime = time();
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
 	$shopwareApi = new OpenApi3Client($shopware6_url, $shopware6_user, $shopware6_key, $shopware6_type);
 	if (!$shopwareApi) {
 		Proto("API ".$shopware6_url." Connection failed!");
 		exit;
 	} else {
 		Proto("API ".$shopware6_url." Connection successful!");
 	}
 	
 	$articles = new Shopware6Articles();
 	
 	$result = $articles->exportAllUpdates($shopwareApi, $noUpload , 0);
 	
 	$rowCount = $result['count'];
 	$errorList = $result['errors'];
 	$articleList = $result['articleList'];
 	
 	if (php_sapi_name() != 'cli') {
 		include("./intern/views/shopware_result_view.php");
 	} else {
 		print_r($result);
 	}
 }
 
 if (isset($_POST["priceStockUpdate"]) or (isset($argv) and in_array("/priceStockUpdate", $argv))) {
 	$starttime = time();
 	
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
	
	$shopwareApi = new OpenApi3Client($shopware6_url, $shopware6_user, $shopware6_key, $shopware6_type);
	if (!$shopwareApi) {
		Proto("API ".$shopware6_url." Connection failed!");
		exit;
	} else {
		Proto("API ".$shopware6_url." Connection successful!");
	}
	
	$articles = new Shopware6Articles();
	
	$articles->articleUpdateListPriceStock($checkDate);

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
	$shopwareApi = new OpenApi3Client($shopware6_url, $shopware6_user, $shopware6_key, $shopware6_type);
	if (!$shopwareApi) {
		Proto("API ".$shopware6_url." Connection failed!");
		exit;
	} else {
		Proto("API ".$shopware6_url." Connection successful!");
	}
	$starttime = time();
 	
 	if (! is_array($shopware6IdWebshop) ) {
 		$salesChannels = [ $shopware6IdWebshop ];
 	} else {
 		$salesChannels = $shopware6IdWebshop;
 	}
 	foreach ($salesChannels as $salesChannel) {
 		$ordersApi = new shopware6Orders($shopwareApi, $salesChannel);
		if (!empty($_POST['orderId'])) {
			$orders["data"][] = [ "id" => $_POST['orderId'] ];
		} else {
			$orders = $ordersApi->getOrderList();
		}
		
		if (count($orders['data']) > 0) {
			$facfile = new myfile($docpath."/ORDERS_SW".time().".FAC","new");
			$facfile->writeUTF8BOM();
			
	
			$rowCount = 0;
			foreach ($orders['data'] as $order) {
				print "Download Order #: ".$order["id"]." ( #".$order['attributes']['orderNumber'].")<br/>\n";
				$facOrderData = $ordersApi->getOrderFacData($order['id']);
				// print_r($facOrderData); exit;
				if (isset($facOrderData["Customer"])) {
					$facfile->facHead("KUN_0", $channelFacData['shopware6']['Filiale']);
					$facfile->facData($facOrderData["Customer"]);
				}
				
				$facfile->facHead("AUFST_KOPF", $channelFacData['shopware6']['Filiale']);
				$facfile->facData($facOrderData["Head"]);
				$rowCount++;
				foreach($facOrderData["Pos"] as $facpos) {
					$facfile->facHead("AUFST_POS",  $channelFacData['shopware6']['Filiale']);
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
			
			if (!empty($_POST['orderId'])) {
				break;
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