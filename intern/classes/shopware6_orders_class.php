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
	private $wwsPickBelegKz;
	private $wwsPickBelegChannelKz;
	
	
	public function __construct($api, $salesChannelId = null) {
		
		include ("./intern/config.php");
		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		if ($salesChannelId == null) {
			$this->ShopwareIdWebshop = $shopware6IdWebshop;
		} else {
			$this->ShopwareIdWebshop = $salesChannelId;
		}
		
		$this->ShopwarePriceGroup = $ShopwarePriceGroup;
		$this->ShopwarePriceBase = $shopware6PriceBase;
		$this->ShopwareStockList = $shopware6StockList;
		$this->Shipping = $Shipping;
		$this->channelFacData = $channelFacData;
		$this->isPaidPaymentTypes = $isPaidPaymentTypes;
		$this->facFiliale = $FacFiliale;
		$this->scanDeskFacFiliale = $scanDeskFacFiliale;
		$this->wwsPickBelegKz = $wwsPickBelegKz;
		$this->wwsPickBelegChannelKz = $wwsPickBelegChannelKz;
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

		$order = $response["data"][0]["attributes"];
		$order["orderId"] = $response["data"][0]["id"];
		//foreach ($response["data"][0]["relationships"] as $type => $data) {
		foreach ($response["data"][0]["relationships"] as $typ => $data) {
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
		$order["paymentId"] = $this->getPaymentId($orderId);
		// print_r($order); exit;
		return $order;
	}

	public function getOrderFacData($orderId) {
		
		$orderData = $this->getOrderData($orderId);
		// print_r($orderData); exit;
		$customerData = $orderData["order_address"][$orderData["billingAddressId"]];
		$customerData["order_customer"] =  reset($orderData["order_customer"]);
		$customerData["country"] =  $orderData["country"][$orderData["order_address"][$orderData["billingAddressId"]]["attributes"]["countryId"]];
		$FacArray = [];
		
		if ( (!empty($this->channelFacData['shopware6']['CustomerMappingField']))
				and (!empty($customerData["order_customer"]["attributes"]["customFields"][$this->channelFacData['shopware6']['CustomerMappingField']])) ) {
					$wwsCustomerNumber = $customerData["order_customer"]["attributes"]["customFields"][$this->channelFacData['shopware6']['CustomerMappingField']];
		} 
		
		if (! $this->channelFacData['shopware6']['GroupCustomer'] ) { 
			$FacArray["Customer"] = $this->getFacCustomerData($customerData);
		}
		$orderData["billingAddress"] = $customerData;
		$orderData["wwsCustomerNumber"] = $wwsCustomerNumber;
		$orderData["orderAddress"] = $orderData["order_address"][ reset($orderData["order_delivery"])["attributes"]["shippingOrderAddressId"] ];
		$orderData["orderAddress"]["country"] =  $orderData["country"][$orderData["order_address"][$orderData["billingAddressId"]]["attributes"]["countryId"]];
		$orderData["payment"] = $orderData["state_machine_state"][ reset($orderData["order_transaction"])["relationships"]["stateMachineState"]["data"]["id"]];
		$orderData["payment"]["type"] = $orderData["payment_method"][ reset($orderData["order_transaction"])["relationships"]["paymentMethod"]["data"]["id"]];
		$orderData["delivery"] = $orderData["order_delivery"][$orderData["rel_deliveries"]["data"][0]["id"]];
		$orderData["shipping"] = $orderData["shipping_method"][$orderData["delivery"]["relationships"]["shippingMethod"]["data"]["id"]];
		$orderData["channel"] = $orderData["sales_channel"][$orderData["salesChannelId"]];
		
		$FacArray["Head"] = $this->getFacHeadData($orderData);

		$this->fpos = 0;
		$FacArray["Pos"] = [];
		$orderItems = $orderData["order_line_item"];
		$discount = 0;
		foreach ($orderItems as $item) {
			if (!empty($item["attributes"]["productId"])) {
				print "Produkt ".$item["attributes"]["productId"]."<br/>";
				$item["product"] = $orderData["product"][ $item["attributes"]["productId"]] ;
				$item["orderNumber"]  = $orderData["orderNumber"];
				$item["customernumber"] = $orderData["billingAddress"]["order_customer"]["attributes"]["customerNumber"];
				$item["orderId"] = $orderData["orderId"];
				$item["externalOrderId"] = $orderData["customFields"]["cbaxExternalOrderOrdernumber"];
				$item["paymentId"] = $orderData["paymentId"];
				$item["wwsCustomerNumber"] = $wwsCustomerNumber;
				$item["price"]["taxStatus"] = $FacArray["Head"]["price"]["taxStatus"];
				$FacArray["Pos"] = array_merge($FacArray["Pos"], $this->getFacPosData($item));
			} elseif (!empty($item["attributes"]["payload"]["discountType"])
						and ($item["attributes"]["payload"]["discountType"] == "percentage")
						and ($item["attributes"]["payload"]["promotionCodeType"] == "global")) {
				$discount +=  $item["attributes"]["payload"]["value"];
			}
		}
		$FacArray["Head"]["QRAB"] = $discount;
		
		if (isset($this->Shipping[$orderData["salesChannelId"]]['article'])) {
			if (!empty($this->Shipping[$orderData["salesChannelId"]]['article'])) {
				$shipment = new product( $this->Shipping[$orderData["salesChannelId"]]['article']);
			} else {
				$shipment = null;
			}
		} elseif (isset($this->Shipping['article'])) {
			$shipment = new product( $this->Shipping["article"]);
		} else {
			$shipment = new product($orderData["shippingTotal"],'searchPrice', ['fromArticle' => $this->Shipping['fromArticle'], 'toArticle' => $this->Shipping['toArticle'] ] );
		}
		if (!empty($shipment)) {
			$item['product']["attributes"]["productNumber"] = $shipment->getProductId();
			//$item["taxRate"] =  $shipment->productData[0]['mmss'];
			$item['product']["attributes"]["name"] = $shipment->productData[0]['abz1'];
			$item["attributes"]["quantity"] = 1;
			$item["attributes"]["unitPrice"] = $orderData["shippingTotal"];
			 
			$FacArray["Pos"] = array_merge($FacArray["Pos"], $this->getFacPosData($item));
		}
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
	
	public function getPaymentId($orderId) {
	    $response = $this->ShopwareApiClient->get('order/'.$orderId.'/transactions');
	    if (!empty($response["data"][0]["attributes"]["customFields"]["swag_paypal_resource_id"])) {
	       return $response["data"][0]["attributes"]["customFields"]["swag_paypal_resource_id"];
	    } else {
	        return null;
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

		if ( (!empty($this->channelFacData['shopware6']['CustomerMappingField']))
				and (!empty($customerData["order_customer"]["attributes"]["customFields"][$this->channelFacData['shopware6']['CustomerMappingField']])) ) {
			return [];
		}
					
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
		
		if (DEBUG == 1) { print_r($data); }
		
		if (!empty($data["wwsCustomerNumber"])) {
			$customerNumber = $data["wwsCustomerNumber"];
		} else {
			$customerNumber = $this->GetRealCustomerNumber($data["billingAddress"]["order_customer"]["attributes"]["customerNumber"]);
		}
		
		if ($data["price"]["taxStatus"] == "net") {	$qpra = 1;	} else { $qpra = 0;	}
		
		if (!empty($this->wwsPickBelegChannelKz[$data["salesChannelId"]])) { 
			$wwwPickKz = $this->wwsPickBelegChannelKz[$data["salesChannelId"]];
		} else {
			$wwwPickKz = $this->wwsPickBelegKz;
		}
		
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
				'QPRA' => $qpra,
				'KPRP' => 6,
				'FBKZ' => $wwwPickKz,
				'KZBE' => 6,
				'QFRM' => $this->channelFacData['shopware6']['formId'],
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
				'Zahlung: '.$data["payment"]["type"]["attributes"]["name"]." | ".
				'Versand: '.$data["shipping"]["attributes"]["name"]." | ".
				'Channel: '.$data["channel"]["attributes"]["name"]
		];
		
		$tax_pv = 0;
		foreach($data["order_line_item"] as $pvpos) {
			if (!empty($pvpos["attributes"]["payload"]["customFields"]["netzp_tax_pv"])
					and ($pvpos["attributes"]["price"]["calculatedTaxes"][0]["taxRate"] == 0) ) {
			  $tax_pv = 1;
			}
		}
		if ($tax_pv == 1) {
			$facHead['QTXK'][] = 'Der Käufer bestätigte im Onlineshop '.date("d.m.Y h:i",strtotime($data["createdAt"])).'Uhr im Bestellvorgang,'; 
			$facHead['QTXK'][] = 'dass er die Voraussetzungen nach § 12 Abs. 3 UStG erfüllt. ';
			$facHead['QTXK'][] = 'Die Produkte sind für die Neuerrichtung einer PV-Anlage mit unter 30kWp für die eigene privater Nutzung im Wohnumfeld.';
			$facHead['QTXK'][] = 'Der Anlagenstandort ist in Deutschland.';
		}
		
		foreach($customerComment as $commentLine) {
			if (strlen($commentLine) > 0) {
				$facHead['QTXK'][] = $commentLine;
			}
		}
		
		$facHead['QTXC'] = [
				'SW_ORDER_ID='. $data["orderId"],
				'SW_EXTERNAL_ORDER_ID='. $data["externalOrderId"],
		        'SW_PAYMENT_ID='. $data["paymentId"],
				'SW_PAYMENT_NAME='.$data["payment"]["type"]["attributes"]["name"],
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
		print $this->channelFacData['shopware6']['CustomerMappingField'];
		$posText = $this->SplitABZ($data['product']["attributes"]["name"]);
		
		if ($data["price"][0]["taxStatus"] == "net") {	$qpra = 1;	} else { $qpra = 0;	}
				
		$article = new product(sprintf("%08d",$data['product']["attributes"]["productNumber"]));
		if (empty($article->productData[0]['arnr'])) {
			$article = new product($data['product']["attributes"]["productNumber"]);
		}
		
		if ($article->getProductId() == NULL) {
			print "Article ".$data['product']["attributes"]["productNumber"]." ".$data['product']["attributes"]["name"]." not found!</br>";
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
		
		if (!empty($data["wwsCustomerNumber"])) {
			$customerNumber = $data["wwsCustomerNumber"];
		} else {
			$customerNumber = $this->GetRealCustomerNumber($data["customernumber"]);
		}
		
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
			'QNVE' => $data["paymentId"],
			'ALGO' => 'HL',
			'APKZ' => $article->productData[0]['apkz'],
			'ASMN' => 1,
			'QPRA' => $qpra,
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
		if ($data["attributes"]["price"]["calculatedTaxes"][0]["taxRate"] != $article->productData[0]['mmss']) {
			print $data["attributes"]["price"]["calculatedTaxes"][0]["taxRate"]." ist nicht ".$article->productData[0]['mmid']."<br>";
			foreach($article->getApkz() as $tax) {
				if ($tax['mmss'] == $data["attributes"]["price"]["calculatedTaxes"][0]["taxRate"]) {
					$facPos[$this->fpos]['APKZ'] = $tax['mmid'];
					break;
				}
			}
		}

		$facPos[$this->fpos]['FABL'] = [
				'SW_PAYMENT_TRANSACTION_ID='. $data["transactionId"],
				'SW_ORDER_ID='. $data["orderId"],
				'SW_ORDER_ITEM_ID='. $data["id"],
				'SW_EXTERNAL_ORDER_ID='. $data["externalOrderId"],
		        'SW_PAYMENT_ID='. $data["paymentId"],
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
		
		if (!empty($data["wwsCustomerNumber"])) {
			$customerNumber = $data["wwsCustomerNumber"];
		} else {
			$customerNumber = $this->GetRealCustomerNumber($data["customernumber"]);
		}

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
		if ($this->channelFacData['shopware6']['GroupCustomer'] ) {
			if (! empty($this->channelFacData['shopware6']['Customer'][$this->ShopwareIdWebshop])) {
				$realCustomerNumber = $this->channelFacData['shopware6']['Customer'][$this->ShopwareIdWebshop];
			} else {
				$realCustomerNumber = $this->channelFacData['shopware6']['CustomerNumber'];
			}
		} elseif ((isset($this->channelFacData['shopware6']['MappingNumber'])) and ($customerNumber < $this->channelFacData['shopware']['CustomerNumber'] )) {
			$realCustomerNumber = $this->channelFacData['shopware6']['MappingNumber'] + $customerNumber;
		} else {
			$realCustomerNumber = $customerNumber;
		}			
		return $realCustomerNumber;
	}	
	

}