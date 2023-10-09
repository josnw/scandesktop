<?php
 include './intern/autoload.php';
 include ("./intern/config.php");

 if (isset($_POST["setMainArticle"]) or (isset($argv) and in_array("/setMainArticle", $argv))) {
 	file_put_contents($docpath.$_POST["mainArticle"]."_variants.json", json_encode($_POST));
 	
 	$shopwareApi = new OpenApi3Client($shopware6_url, $shopware6_user, $shopware6_key, $shopware6_type);
 	print LR."Hauptartikel: ".$_POST["mainArticle"].LR;
 	$relationships = [];
 	for($i = 0; $i < count($_POST["subArticle"]); $i++)  {
 		print "Variante M1: ".$_POST["subArticle"][$i].": ".$_POST["optionkey1"]." ".$_POST["optionvalue1"][$i].LR;
 		$relationships[$_POST["subArticle"][$i]] [$_POST["optionkey1"]] =  $_POST["optionvalue1"][$i];
 		if(!empty($_POST["optionkey2"])) {
 			print "Variante M2: ".$_POST["subArticle"][$i].": ".$_POST["optionkey2"]." ".$_POST["optionvalue2"][$i].LR;
 			$relationships[ $_POST["subArticle"][$i]] [$_POST["optionkey2"]] =  $_POST["optionvalue2"][$i];
 		}
 	}
 	if (($_SESSION["debug"] ==1) and ($_SESSION["level"] == 9)) {
 		print "<pre>";
 		print_r($relationships);
 		print "</pre>";
 	}
 	$article = new Shopware6Articles($shopwareApi);
 	$result = $article->setVariants($shopwareApi, $_POST["mainArticle"], $_POST["subArticle"], $relationships);
 	if ($result["status"]) {
 		print $result["info"];
 	} else {
 		print $result["errors"];
 	}
 	

 }
 
 if (!empty($_POST["doubleKeys"])) {
 	$doubleKeys = 1;
 } else {
 	$doubleKeys = 0;
 }
 
 include("./intern/views/shopware_variants_view.php");
 ?>