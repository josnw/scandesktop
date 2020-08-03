<?php
 include './intern/autoload.php';
 include ("./intern/config.php");

 if (isset($_POST["priceStockUpdate"]) or (isset($argv) and in_array("/priceStockUpdate", $argv))) {
	$export = new tradebytePanda();					
	
	// Select price updates
	$pricefile = $docpath."ARTICLE_prices_".date("Ymd_His").".csv";
	if (($priceresult = $export->priceUpdate($pricefile)) and ($priceresult['count'] > 0)) {
		$rowCount = $priceresult['count'];
		$exportfile = $docpath.$priceresult['filename'];
		$filename = $priceresult['filename'];
		if (php_sapi_name() != 'cli') {
			include("./intern/views/tbpanda_result_view.php");
		} else {
			print $exportfile."\n";
		}
	} 

	// select stock updates
	$stockfile = $docpath."ARTICLE_stockup_".date("Ymd_His").".csv";
	if (( $stockresult = $export->stockUpdate($stockfile) ) and ($stockresult['count'] > 0) ){
		$rowCount = $stockresult['count'];
		$exportfile = $docpath.$stockresult['filename'];
		$filename = $stockresult['filename'];
		if (php_sapi_name() != 'cli') {
			include("./intern/views/tbpanda_result_view.php");
		} else {
			print $exportfile."\n";
		}
	}

	// select media updates
	$mediafile = $docpath."ARTICLE_media_".date("Ymd_His").".csv";
	if (($mediaresult = $export->mediaUpdate($mediafile)) and ( $mediaresult['count'] > 0)) {
		$rowCount = $mediaresult['count'];
		$exportfile = $docpath.$mediaresult['filename'];
		$filename = $mediaresult['filename'];
		if (php_sapi_name() != 'cli') {
			include("./intern/views/tbpanda_result_view.php");
		} else {
			print $exportfile."\n";
		}
	}
	
	//set uploadtime
	$export->setUpdateTime();
	
} elseif (isset($_POST["orders2fac"]) or (isset($argv) and in_array("/orders2fac", $argv))) {
	
	$fname = $docpath."/ORDERS_".uniqid()."csv";
	move_uploaded_file( $_FILES["csvorders"]["tmp_name"], $fname );
	
	$orders = new tradebyteorders($fname);		
	$facfile = new myfile($docpath."/ORDERS_".time()."FAC","new");
	
	$orders->readFullData();
	$rowCount = 0;
	foreach ($orders->getOrderIds() as $orderId) {
		$facfile->facHead("AUFST_KOPF");
		$facfile->facData($orders->getFacHeadData($orderId));
		$rowCount++;
		foreach($orders->getFacPosData($orderId) as $facpos) {
			$facfile->facHead("AUFST_POS");
			$facfile->facData($facpos);
		}
	}
	$facfile->facfoot();
	$exportfile = $docpath.$facfile->getCheckedName();
	$filename = $facfile->getCheckedName();
	if (php_sapi_name() != 'cli') {
		include("./intern/views/tbpanda_result_view.php");
	} else {
		print $exportfile."\n";
	}
}

if (php_sapi_name() == 'cli') {
	// no form output on console
	exit;
}

  // articles select form
    if (isset($_POST['vonlinr'])) { $vonlinr = preg_replace('[^0-9]','', $_POST['vonlinr']); } else { $vonlinr = 0;}
    if (isset($_POST['bislinr'])) { $bislinr = preg_replace('[^0-9]','', $_POST['bislinr']); } else { $bislinr = 9999999;}
    if (isset($_POST['vonqgrp'])) { $vonqgrp = preg_replace('[^0-9]','', $_POST['vonqgrp']); } else { $vonqgrp = 0;}
    if (isset($_POST['bisqgrp'])) { $bisqgrp = preg_replace('[^0-9]','', $_POST['bisqgrp']); } else { $bisqgrp = 899;}
  
  	include("./intern/views/tbpanda_select_view.php");
  
  // Mapping Formular
  // not used
    include("./intern/views/tbpanda_mapping_view.php");
  
  // export
    if (isset($_POST["pandaDownload"])) {
		$export = new tradebytePanda();					
		if ($export->selectByQgrpLinr($_POST["vonlinr"],$_POST["bislinr"],$_POST["vonqgrp"],$_POST["bisqgrp"], $_POST["autoUpdate"])) {
			$exportfile = $docpath."PANDA_".date("Ymd_His").".csv";
			
			$result = $export->exportToFile($exportfile);
			$rowCount = $result['count'];
			$exportfile = $docpath.$result['filename'];
			$filename = $result['filename'];
			
			include("./intern/views/tbpanda_result_view.php");

		}
		
	}
	
  
  
 ?> 