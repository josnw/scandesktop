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
	
	$articles->articleUpdateList($checkDate);
	
	$result = $articles->exportToShopware($shopwareApi, $noUpload);
	$rowCount = $result['count'];
	$errorList = $result['errors'];

	
	if (php_sapi_name() != 'cli') {
		include("./intern/views/shopware_result_view.php");
	} else {
		print_r($result);
	}
}

if (php_sapi_name() == 'cli') {
	// no form output on console
	exit;
}

include("./intern/views/shopware_select_view.php"); 

?>