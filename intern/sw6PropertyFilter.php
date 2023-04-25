<?php
 include './intern/autoload.php';
 include ("./intern/config.php");

 $parameter = new factoBaseData();

 if (isset($_POST["filterState"]) or (isset($argv) and in_array("/setFilter", $argv))) {
 	
 	$parameter->updateParam($_POST["filterKey"], $_POST["filterState"]);
 	
 	$shopwareApi = new OpenApi3Client($shopware6_url, $shopware6_user, $shopware6_key, $shopware6_type);
 	$articles = new Shopware6Articles($shopwareApi);
 	if ($_POST["filterState"] == 1) {
 		$result = $articles->setPropertyGroup($_POST["filterKey"], true);
 	} else {
 		$result = $articles->setPropertyGroup($_POST["filterKey"], false);
 	}
 }

 $paramArray = $parameter->getProductParam();
 
 include("./intern/views/shopware_propertyFilter_view.php");
 ?>