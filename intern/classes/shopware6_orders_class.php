<?php


class Shopware6Orders {

	private $pg_pdo;
	private $articleList_qry;
	private	$startTime;
	private $ShopwareIdWebshop;
	private $ShopwarePriceGroup;
	private $ShopwarePriceBase;
	private $ShopwareStockList;
	private $ShopwareApiClient;
	private $ShipingNumber;
	private $channelFacData;
	private $orderList;
	private	$fpos;
	private $isPaidPaymentTypes;
	private $facFiliale;
	private $scanDeskFacFiliale;
	
	
	public function __construct($api, $salesChannelId = null) {
		
		include ("./intern/config.php");
		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		if ($salesChannelId == null) {
			$this->ShopwareIdWebshop = $ShopwareIdWebshop;
		} else {
			$this->ShopwareIdWebshop = $salesChannelId;
		}
		
		$this->ShopwarePriceGroup = $ShopwarePriceGroup;
		$this->ShopwarePriceBase = $ShopwarePriceBase;
		$this->ShopwareStockList = $ShopwareStockList;
		$this->Shipping = $Shipping;
		$this->channelFacData = $channelFacData;
		$this->isPaidPaymentTypes = $isPaidPaymentTypes;
		$this->facFiliale = $FacFiliale;
		$this->scanDeskFacFiliale = $scanDeskFacFiliale;
		
		$this->ShopwareApiClient = $api;

		$payload = json_decode(file_get_contents('intern/data/orderlist.json'), true);
		$payload["filter"][0]["value"] = $this->ShopwareIdWebshop;
		
		// initialize the REST client
		$this->orderList = $this->ShopwareApiClient->post('search/order/', $payload );
		
		return true;
	}

	public function getOrderList() {
		return $this->orderList;
	}

	public function getOrderData($orderId) {
		
		$order = [];

		$payload = json_decode(file_get_contents('intern/data/orderdetails.json'), true);
		$payload["filter"][0]["value"] = $orderId;
		
		$response = $this->ShopwareApiClient->post('search/order/', $payload );
		//print_r($response); exit;
		$order = $response["data"][0]["attributes"];
		$order["orderId"] = $response["data"][0]["id"];
		foreach ($response["data"][0]["relationships"] as $type => $data) {
			if (!empty($typ)) {
				$order["rel_".$typ] = $data;
			} else {
				// $order["rel_".$data["data"][0]["type"]] = $data;
			}
		}
		foreach ($response["included"] as $data) {
			if (!empty($data["type"])) {
				$order[ $data["type"] ][ $data["id"] ] = $data;
			} else {
				$order[$data["data"][0]["type"]][ $data["id"] ] = $data;
			}
		}
		//print_r($order); exit;
		return $order;
	}

	public function getOrderFacData($orderId) {
		
		$orderData = $this->getOrderData($orderId);
		// print_r($orderData); //exit;
		$customerData = $orderData["order_address"][$orderData["billingAddressId"]];
		$customerData["order_customer"] =  reset($orderData["order_customer"]);
		$customerData["country"] =  $orderData["country"][$orderData["order_address"][$orderData["billingAddressId"]]["attributes"]["countryId"]];
		
		$FacArray = [];
		if (! $this->channelFacData['shopware']['GroupCustomer'] ) { 
			$FacArray["Customer"] = $this->getFacCustomerData($customerData);
		}
		$orderData["billingAddress"] = $customerData;
		$orderData["orderAddress"] = $orderData["order_address"][ reset($orderData["order_delivery"])["attributes"]["shippingOrderAddressId"] ];
		$orderData["orderAddress"]["country"] =  $orderData["country"][$orderData["order_address"][$orderData["billingAddressId"]]["attributes"]["countryId"]];
		$orderData["payment"] = $orderData["state_machine_state"][ reset($orderData["order_transaction"])["relationships"]["stateMachineState"]["data"]["id"]];
		$orderData["payment"]["type"] = $orderData["payment_method"][ reset($orderData["sales_channel"])["relationships"]["paymentMethods"]["data"][0]["id"]];
		
		$FacArray["Head"] = $this->getFacHeadData($orderData);

		$this->fpos = 0;
		$FacArray["Pos"] = [];
		$orderItems = $orderData["order_line_item"];
		$orderItems = $orderData["order_line_item"];
		foreach ($orderItems as $item) {
			print "Produkte";
			$item["product"] = $orderData["product"][ $item["attributes"]["productId"]] ;
			$item["orderNumber"]  = $orderData["orderNumber"];
			$item["customernumber"] = $orderData["billingAddress"]["order_customer"]["attributes"]["customerNumber"];
			$item["orderId"] = $orderData["orderId"];
			$item["externalOrderId"] = $orderData["customFields"]["cbaxExternalOrderOrdernumber"];
			
			$FacArray["Pos"] = array_merge($FacArray["Pos"], $this->getFacPosData($item));
			
		}
		//print_r($orderData);
		//if ($orderData["shippingTotal"] > 0) {
			
		if (isset($this->Shipping['article'])) {
			$shipment = new product( $this->Shipping["article"]);
		} else {
			$shipment = new product($orderData["shippingTotal"],'searchPrice', ['fromArticle' => $this->Shipping['fromArticle'], 'toArticle' => $this->Shipping['toArticle'] ] );
		}

		$item['product']["attributes"]["productNumber"] = $shipment->getProductId();
		//$item["taxRate"] =  $shipment->productData[0]['mmss'];
		$item['product']["attributes"]["name"] = $shipment->productData[0]['abz1'];
		$item["attributes"]["quantity"] = 1;
		$item["attributes"]["unitPrice"] = $orderData["shippingTotal"];
		 
		$FacArray["Pos"] = array_merge($FacArray["Pos"], $this->getFacPosData($item));
		
		//} 
		return $FacArray;
	}
	
	public function setOrderState($orderId, $state) {
		if (!empty($orderId) ) {
			//print '_action/order/'.$orderId.'/state/'. $state."\n";
			$response = $this->ShopwareApiClient->post('_action/order/'.$orderId.'/state/'. $state	);

		} else {
			// throw new Exception("RestAPI setOrderState No success: ".$orderId."->".$state);			
		}
		
	}
	
	public function setOrderDeliveryState($orderId, $trackinCode, $state) {
		if (!empty($orderId) ) {
			$params = [
					'filter' => [
							[
									'type' => 'equals',
									'field' => 'trackingCodes',
									'value' => $trackinCode
							],
							[
									'type' => 'equals',
									'field' => 'orderId',
									'value' => $orderId
							]
					]
			];
			$deliveryData = $client->get('order-delivery/',$params);
			$response = $this->ShopwareApiClient->post('_action/order_delivery/'.$deliveryData["data"][0]["id"].'/state/'. $state	);
			
		} else {
			// throw new Exception("RestAPI setOrderState No success: ".$orderId."->".$state);
		}
		
	}
	
	private function getFacCustomerData($data) {

		$customerNumber = $this->GetRealCustomerNumber($data["order_customer"]["attributes"]["customerNumber"]);
		
		$facCustomer = [
			'KDNR' => $customerNumber,
			'QANR' => $data["attributes"]["title"],
			'QSBZ' => $data["attributes"]["firstName"]." ".$data["attributes"]["lastName"],
			'QNA1' => $data["attributes"]["firstName"]." ".$data["attributes"]["lastName"],
			'QNA2' => $data["attributes"]["company"],
			'QNA3' => $data["attributes"]["additionalAddressLine1"],
			'QNA4' => $data["attributes"]["additionalAddressLine2"],
			'QSTR' => $data["attributes"]["street"],
			'QPLZ' => $data["attributes"]["zipcode"],
			'QORT' => $data["attributes"]["city"],
			'QLND' => $data["country"]["attributes"]["name"],
			'QTEL' => $data["attributes"]["phoneNumber"],
			'QEMA' => $data["order_customer"]["attributes"]["email"],
			'qwid' => $data["order_customer"]["attributes"]["customerNumber"],
		];	

		return $facCustomer;
	}
	
	private function getFacHeadData($data) {

		$customerNumber = $this->GetRealCustomerNumber($data["billingAddress"]["order_customer"]["attributes"]["customerNumber"]);
		
		$facHead = [
				'FXNR' => $customerNumber,
				'FXNS' => $customerNumber,
				'FXNA' => $customerNumber,
				'IFNR' => $this->facFiliale,
				'FTYP' => 2,
				'FPRJ' => '000000',
	            'CKSS' => '000000',
	            'OBNR' => '000000',
				'FNUM' => $data["orderNumber"],
				'FBLG' => $data["orderNumber"],
				'FFBG' => $data["customFields"]["cbaxExternalOrderOrdernumber"],
				'QSBZ' => 'shopware Order '.$data["orderNumber"],
				'FDTM' => date("d.m.Y",time()),
				'FLDT' => date("d.m.Y", time()+(60*60*18)),
				'SIGS' => $data["amountTotal"],
				'SGES' => $data["amountTotal"],
				'QANR' => $data["billingAddress"]["attributes"]["title"],
				'QNA1' => $data["billingAddress"]["attributes"]["firstName"]." ".$data["billingAddress"]["attributes"]["lastName"],
				'QNA2' => $data["billingAddress"]["attributes"]["company"],
				'QNA3' => $data["billingAddress"]["attributes"]["additionalAddressLine1"],
				'QNA4' => $data["billingAddress"]["attributes"]["additionalAddressLine2"],
				'QSTR' => $data["billingAddress"]["attributes"]["street"],
				'QPLZ' => $data["billingAddress"]["attributes"]["zipcode"],
				'QORT' => $data["billingAddress"]["attributes"]["city"],
				'QLND' => $data["billingAddress"]["country"]["attributes"]["name"],
				'QTEL' => $data["billingAddress"]["attributes"]["phoneNumber"],
				'QEMA' => $data["billingAddress"]["order_customer"]["attributes"]["email"],
		        'QSBZ' => $data["orderId"],
				'QUSS' => 1,
				'QPRA' => 0,
				'KPRP' => 6,
				'FBKZ' => 60,
				'KZBE' => 6,
				'QFRM' => $this->channelFacData['shopware']['formId'],
	            'QHWG' => 'EUR',
	            'QZWG' => 'EUR'
		];
		
		// automatic flag if payed
		if (($data["payment"]["attributes"]["technicalName"] == 'paid')) {
			$facHead['FFKT'] = 0;
		} else {
			$facHead['FFKT'] = 1;
		}

		// head text 
		$customerComment = $this->SplitABZ($data["customerComment"]);
		

		$facHead['QTXK'] = [
				'Payment: '.$data["payment"]["type"]["attributes"]["name"],
//				'Payment ID: '.$data["transactionId"],
//				'Versand: '.$data["dispatch"]["name"],
		];
		
		foreach($customerComment as $commentLine) {
			if (strlen($commentLine) > 0) {
				$facHead['QTXK'][] = $commentLine;
			}
		}
		
		$facHead['QTXC'] = [
				'SW_ORDER_ID='. $data["orderId"],
				'SW_EXTERNAL_ORDER_ID='. $data["externalOrderId"],
		];
		
		foreach($data["customFields"] as $key => $customField) {
			$facHead['QTXC'][] = $key."=".$customField;
		}
		
		
		// shipping adress
		$facHead['LFA'] = [
				'QANR='.$data["orderAddress"]["attributes"]["title"],
				'QNA1='.$data["orderAddress"]["attributes"]["firstName"].' '.$data["orderAddress"]["attributes"]["lastName"],
				'QNA2='.$data["orderAddress"]["attributes"]["additionalAddressLine1"],
				'QNA3='.$data["orderAddress"]["attributes"]["additionalAddressLine2"],
				'QSTR='.$data["orderAddress"]["attributes"]["street"],
				'QPLZ='.$data["orderAddress"]["attributes"]["zipcode"],
				'QORT='.$data["orderAddress"]["attributes"]["city"],
				'QLND='.$data["orderAddress"]["country"]["attributes"]["name"]
		];
		
		return $facHead;
	}	
	
	private function getFacPosData($data) {

		$posText = $this->SplitABZ($data['product']["attributes"]["name"]);
		
		$article = new product(sprintf("%08d",$data['product']["attributes"]["productNumber"]));
		if (empty($article->productData[0]['arnr'])) {
			$article = new product($data['product']["attributes"]["productNumber"]);
		}
		
		if ($article->getProductId() == NULL) {
			print "Article ".data['product']["attributes"]["productNumber"]." ".$data['product']["attributes"]["name"]." not found!</br>";
			$posFmge = $data["attributes"]["quantity"] ; 
			$posPrice = $data["attributes"]["unitPrice"];				
			$posApjs = 1;
			$posApkz = 1;
		} else {
			$posFmge = $data["attributes"]["quantity"] / $article->productData[0]['amgm']; 
			$posPrice = $data["attributes"]["unitPrice"] / $article->productData[0]['amgm'] * $article->productData[0]['apjs'];
			$posApjs = $article->productData[0]['apjs'];
			$posApkz = $article->productData[0]['apkz'];
		}
		
		$customerNumber = $this->GetRealCustomerNumber($data["customernumber"]);
		
		if ($article->productData[0]['aart'] == 2) {
			$fakt = 3153923;
			$fakx = '';
			//$fpid = '';
			$fpid = $this->scanDeskFacFiliale * 1000000000 + $data["orderNumber"] * 100 +  $this->fpos;
		} elseif (!empty($article->productData[0]['avsd'])) {
			$fakt = 8195;
			$fakx = 113;
			$fpid = $this->scanDeskFacFiliale * 1000000000 + $data["orderNumber"] * 100 +  $this->fpos;
		} else{
			$fakt = 8195;
			$fakx = '';
			$fpid = $this->scanDeskFacFiliale * 1000000000 + $data["orderNumber"] * 100 +  $this->fpos;
		}	
		
		$facPos[$this->fpos] = [
			'FXNR' => $customerNumber ,
			'FXNS' => $customerNumber ,
			'FXNA' => $customerNumber ,		
			'IFNR' => $this->facFiliale,
			'FTYP' => 2,
			'FPRJ' => '000000',
			'CKSS' => '000000',
			'OBNR' => '000000',
			'FNUM' => $data["orderNumber"],
			'FBLG' => $data["orderNumber"],
			'FDTM' => date("d.m.Y",time()),
			'FLDT' => date("d.m.Y", time()+(60*60*18)),			
			'FPOS' => $this->fpos,
			'AAMR' => $article->productData[0]['arnr'],
			'ARNR' => $article->productData[0]['arnr'],
			'QGRP' => $article->productData[0]['qgrp'],
			'FART' => 1,
			'XXAK' => '',
			'XYAK' => '',
			'QNVE' => $data["transactionId"],
			'ALGO' => 'HL',
			'APKZ' => $article->productData[0]['apkz'],
			'ASMN' => 1,
			'QPRA' => 0,
			'ASMZ' => 1,
			'ABZ1' => $posText[0],
			'ABZ2' => $posText[1],
			'ABZ3' => $posText[2],
			'ABZ4' => $posText[3],
			'FMGB' => $data["attributes"]["quantity"] ,
			'FMGZ' => $article->productData[0]['amgz'],
			'FMGN' => $article->productData[0]['amgn'],
			'FMGE' => $posFmge,
			'FPID' => $fpid,
			'APJS' => $article->productData[0]['apjs'],
			'AMEH' => $article->productData[0]['ameh'],
			'AGEH' => $article->productData[0]['ageh'],
			'AGEW' => $article->productData[0]['agew'],
			'FEPB' => $posPrice,
			'QPAS' => '',
			'ASCO' => $data['product']["attributes"]["ean"],
			'FAKT' => $fakt,
			'FAKX' => $fakx,
		];	

		$facPos[$this->fpos]['FABL'] = [
				'SW_PAYMENT_TRANSACTION_ID='. $data["transactionId"],
				'SW_ORDER_ID='. $data["orderId"],
				'SW_ORDER_ITEM_ID='. $data["id"],
				'SW_EXTERNAL_ORDER_ID='. $data["externalOrderId"],
		];
		$this->fpos++;
		
		if ($article->productData[0]['aart'] == 2) {
			$stckListData = $article->getStcklistData();
			foreach( $stckListData as $slArticle ) {
				$facPos[$this->fpos] = $this->getStckListPos($slArticle, $posFmge, $data);
				$this->fpos++;
			}
		}
		
		return $facPos;
	}
	
	private function  getStckListPos($slArticle, $quantity, $mainArticle) {
		
		$article = new product($slArticle['astl']);
		$posFmge = $quantity * $slArticle['asmg'];
		if ($article->productData[0]['amgn'] > 0) {
			$posFmgb = $posFmge*$article->productData[0]['amgz']/$article->productData[0]['amgn'];
		} else {
			$posFmgb = $posFmge;
		}
		$posPrice = 0;
		$posApjs = $article->productData[0]['apjs'];
		$posApkz = $article->productData[0]['apkz'];
		
		$customerNumber = $this->GetRealCustomerNumber($data["customernumber"]);

		$facPos = [
				'FXNR' => $customerNumber ,
				'FXNS' => $customerNumber ,
				'FXNA' => $customerNumber ,
				'IFNR' => $this->facFiliale,
				'FTYP' => 2,
				'FPRJ' => '000000',
				'CKSS' => '000000',
				'OBNR' => '000000',
				'FNUM' => $mainArticle["orderNumber"],
				'FBLG' => $mainArticle["orderNumber"],
				'FDTM' => date("d.m.Y",time()),
				'FLDT' => date("d.m.Y", time()+(60*60*18)),			
				'FPOS' => $this->fpos,
				'FPNZ' => '',
				'AAMR' => $slArticle['astl'],
				'ARNR' => $slArticle['astl'],
				'QGRP' => $article->productData[0]['qgrp'],
				'FART' => 6,
				'XXAK' => '',
				'XYAK' => '',
				'QNVE' => $data["transactionId"],
				'ALGO' => 'HL',
				'APKZ' => $article->productData[0]['apkz'],
				'ASMN' => 1,
				'QPRA' => 0,
				'ASMZ' => 1,
				'ABZ1' => $article->productData[0]['abz1'],
				'ABZ2' => $article->productData[0]['abz2'],
				'ABZ3' => $article->productData[0]['abz3'],
				'ABZ4' => $article->productData[0]['abz4'],
				'FMGB' => $posFmgb,
				'FMGZ' => $article->productData[0]['amgz'],
				'FMGN' => $article->productData[0]['amgn'],
				'ASMZ' => $slArticle['asmz'],
				'ASMN' => $slArticle['asmn'],
				'FMGE' => $posFmge,
				'FPID' => $fpid = $this->scanDeskFacFiliale * 1000000000 + $data["orderNumber"] * 100 +  $this->fpos,
				'APJS' => $article->productData[0]['apjs'],
				'AMEH' => $article->productData[0]['ameh'],
				'AGEH' => $article->productData[0]['ageh'],
				'FEPB' => $posPrice,
				'QPAS' => '',
				'ASCO' => $mainArticle['product']["attributes"]["ean"],
				'FACT' => 33562627,
		];
		
		$facPos['FABL'] = [
				'SW_PAYMENT_TRANSACTION_ID='. $mainArticle["transactionId"],
				'SW_ORDER_ID='. $mainArticle["orderId"],
				'SW_ORDER_ITEM_ID='. $mainArticle["id"],
				'SW_EXTERNAL_ORDER_ID='. $mainArticle["externalOrderId"],
		];

		return $facPos;
	}

	private function SplitABZ($abz, $cnt = 60) {
		$abz = wordwrap($abz, $cnt ,"\t",TRUE);
		$posarray = explode("\t",$abz);
		for ($i = count($posarray); $i < 4; $i++) {
			$posarray[$i] = '';
		}
		return $posarray;
	}

	/*
		Split customer number Handling if differnent ranges in shopware used
	*/
	private function GetRealCustomerNumber($customerNumber) {
		if ($this->channelFacData['shopware']['GroupCustomer'] ) {
			if (! empty($this->channelFacData['shopware'][$this->ShopwareIdWebshop])) {
				$realCustomerNumber = $this->channelFacData['shopware'][$this->ShopwareIdWebshop];
			} else {
				$realCustomerNumber = $this->channelFacData['shopware']['CustomerNumber'];
			}
		} elseif ((isset($this->channelFacData['shopware']['MappingNumber'])) and ($customerNumber < $this->channelFacData['shopware']['CustomerNumber'] )) {
			$realCustomerNumber = $this->channelFacData['shopware']['MappingNumber'] + $customerNumber;
		} else {
			$realCustomerNumber = $customerNumber;
		}			
		return $realCustomerNumber;
	}	
	

}