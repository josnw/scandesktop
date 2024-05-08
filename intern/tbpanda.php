<?php
 include './intern/autoload.php';
 include ("./intern/config.php");

 if (isset($_POST["priceStockUpdate"]) or (isset($argv) and in_array("/priceStockUpdate", $argv))) {
	$export = new tradebytePanda();					
	
	if (isset($_POST["fullLoad"]) or (isset($argv) and in_array("/fullLoad", $argv))) {
		$checkDate = '2000-01-01';
	} else {
		$checkDate = NULL;
	}
	
	// Select price updates
	$pricefile = $docpath."ARTICLE_prices_".date("Ymd_His").".csv";
	if (($priceresult = $export->priceUpdate($pricefile, $checkDate)) and ($priceresult['count'] > 0)) {
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
	if (( $stockresult = $export->stockUpdate($stockfile, $checkDate) ) and ($stockresult['count'] > 0) ){
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
	
} elseif (isset($_POST["orders2fac"]) or (isset($argv) and in_array("/convertOrders", $argv))) {
	
	if (php_sapi_name() != 'cli') {
		$fname = $docpath."/ORDER_".uniqid().".csv";
		move_uploaded_file( $_FILES["csvorders"]["tmp_name"], $fname );
	} else {
		$fname = $argv[ array_search("/convertOrders",$argv) + 1 ];
	}
	
	$facfile = new myfile($docpath."/ORDERS_".time().".FAC","new");
	$orders = new tradebyteorders($fname);		
	
	if ((!empty($tradebyte_charset)) and ($tradebyte_charset == "utf8")) {
		$facfile->writeUTF8BOM();
	}
	
	$orders->readFullData();
	$rowCount = 0;
	foreach ($orders->getOrderIds() as $orderId) {
		$facfile->facHead("AUFST_KOPF", $channelFacData[$orders->getChannel($orderId)]['Filiale']);
		$facfile->facData($orders->getFacHeadData($orderId));
		$rowCount++;
		foreach($orders->getFacPosData($orderId) as $facpos) {
			$facfile->facHead("AUFST_POS", $channelFacData[$orders->getChannel($orderId)]['Filiale']);
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
} elseif (isset($_POST["desadv"]) or (isset($argv) and in_array("/desadv", $argv))) {
	$orderFil = 918;
	if (php_sapi_name() != 'cli') {
		$fname = $docpath."/DESADV_".uniqid().".csv";
		move_uploaded_file( $_FILES["csvdesadv"]["tmp_name"], $fname );
		if (isset($_POST["daFil"])) {
			$orderFil = $_POST["daFil"];
		}
	} else {
		$fname = $argv[ array_search("/desadv",$argv) + 1 ];
		if ( in_array("/daFil", $argv)) {
			$orderFil = $argv[ array_search("/daFil",$argv) + 1 ];
		}
	}
	print $fname;
	//$facfile = new myfile($docpath."/DESADV_".time().".FAC","new");
	$tbdata = new tradebyteDesAdv($fname);		
	
	$tbdata->readDeliveryData();
	
	foreach ($tbdata->getOrderIds() as $orderid) {
		print $orderid."<br>";
		$override = [];
		$articleList = [];
		$desadv = new factoOrders($orderFil, $orderid);
		if  ($desadv->getOrderId() == null) {
			print "Order $orderid not found!<br>\n";
			continue;
		}

		$order = $tbdata->getOrderData($orderid);

		$override['head'] = [];
		foreach($order['pos'] as $pos) {
			if (isset($pos['POS_CHANNEL_ID']) and strlen($pos['POS_CHANNEL_ID']) > 3) {
				$articleList[] = $pos['POS_CHANNEL_ID'];
				$override['positions'][$pos['POS_CHANNEL_ID']]['fmgb'] = $pos['SHIP_QUANTITY'];
			} else {
				$articleList[] = $pos['POS_ANR'];
				$override['positions'][$pos['POS_ANR']]['fmgb'] = $pos['SHIP_QUANTITY'];
			}
		}
		$result = $desadv->duplicateOrder(4,$articleList, $override, false);
		print $orderid." -> ".$result["fnum"]."</br>\n";
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
    if (isset($_POST['autoUpdate'])) { $autoUpdate = preg_replace('[^01]','', $_POST['autoUpdate']); } else { $autoUpdate = '1';}
    if (isset($_POST['onlyNew'])) { $onlyNew = preg_replace('[^01]','', $_POST['onlyNew']); } else { $onlyNew = '';}
    if (isset($_POST['akz'])) { $akz = preg_replace('[^0-9]','', $_POST['akz']); } else { $akz = '';}
  
  	include("./intern/views/tbpanda_select_view.php");
  
  // Mapping Formular
  // not used
    include("./intern/views/tbpanda_mapping_view.php");
  
  // export
    if (isset($_POST["pandaDownload"])) {
		$export = new tradebytePanda();					
		if ($export->selectByQgrpLinr($vonlinr,$bislinr,$vonqgrp,$bisqgrp, $autoUpdate, $onlyNew, $akz)) {
			$exportfile = $docpath."PANDA_".date("Ymd_His").".csv";
			
			$result = $export->exportToFile($exportfile);
			$rowCount = $result['count'];
			$exportfile = $docpath.$result['filename'];
			$filename = $result['filename'];
			
			include("./intern/views/tbpanda_result_view.php");

		}
		
	}
	
  
  
 ?> 