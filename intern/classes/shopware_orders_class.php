<?php


class ShopwareOrders {

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
	private $facFiliale
	
	
	public function __construct($api) {
		
		include ("./intern/config.php");
		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		$this->ShopwareIdWebshop = $ShopwareIdWebshop;
		$this->ShopwarePriceGroup = $ShopwarePriceGroup;
		$this->ShopwarePriceBase = $ShopwarePriceBase;
		$this->ShopwareStockList = $ShopwareStockList;
		$this->Shipping = $Shipping;
		$this->channelFacData = $channelFacData;
		$this->isPaidPaymentTypes = $isPaidPaymentTypes;
		$this->facFiliale = $FacFiliale;
		
		$this->ShopwareApiClient = $api;
		
		// initialize the REST client
		$this->orderList = $this->ShopwareApiClient->get('orders/',
			array( 'filter' => 
				array(
					'shopId' => $this->ShopwareIdWebshop,
					array(
						'property' => 'status',
						'value' => 0
					)
				)
			)
		);
		
		return true;
	}

	public function getOrderList() {
		return $this->orderList;
	}

	public function getOrderData($orderId) {

		return $this->ShopwareApiClient->get('orders/'.$orderId); 

	}
	
	public function getOrderFacData($orderId) {

		$order = $this->getOrderData($orderId); 

		$FacArray = [];
		if (! $this->channelFacData['shopware']['GroupCustomer'] ) { 
			$FacArray["Customer"] = $this->getFacCustomerData($order["data"]);
		}
		
		$FacArray["Head"] = $this->getFacHeadData($order["data"]);
		 
		$this->fpos = 0;
		foreach ($order["data"]["details"] as $orderpos) {
			  $orderpos["selfcollectDatetime"] = $order["data"]["attribute"]["selfcollectDatetime"];
			  $orderpos["transactionId"]  = $order["data"]["transactionId"];
			  $orderpos["transactionId"]  = $order["data"]["transactionId"];
			  $orderpos["transactionId"]  = $order["data"]["transactionId"];
			  $orderpos["customernumber"] = $order["data"]["customer"]["number"];
			  $FacArray["Pos"][$this->fpos] =  $this->getFacPosData($orderpos);
		}
		
		if ($order["data"]["invoiceShipping"] > 0) {
			
			if (isset($this->Shipping['article'])) {
				$shipment = new product( $this->Shipping["article"]);
			} else {
				$shipment = new product($this->OrdersData[$orderId]['head']['SHIPPING_COSTS'],'searchPrice', ['fromArticle' => $this->Shipping['fromArticle'], 'toArticle' => $this->Shipping['toArticle'] ] );
			}
			 
			$orderpos["articleNumber"] = $shipment->getProductId();
			$orderpos["taxRate"] =  16;
			$orderpos['articleName'] = $shipment->productData[0]['abz1'];
			$orderpos["quantity"] = 1;
			$orderpos["price"] = $order["data"]["invoiceShipping"];
			 
			$FacArray["Pos"][$this->fpos] =  $this->getFacPosData($orderpos);
			 
		}
		return $FacArray;
	}
	
	public function setOrderState($orderId, $state) {
		if ($orderId > 0) {
			$this->ShopwareApiClient->put('orders/'.$orderId, array(
					'orderStatusId' => $state
				)
			);  
		} else {
			throw new Exception("RestAPI setOrderState No success: ".$orderId."->".$state);			
		}
		
	}
	
	private function getFacCustomerData($data) {
		
		$facCustomer = [
			'KDNR' => $this->channelFacData['shopware']['CustomerNumber'] + $data["customer"]["number"],
			'QANR' => $data["customer"]["title"],
			'QSBZ' => $data["customer"]["firstname"]." ".$data["customer"]["lastname"],
			'QNA1' => $data["billing"]["firstName"]." ".$data["billing"]["lastName"],
			'QNA2' => $data["billing"]["additionalAddressLine1"],
			'QNA3' => $data["billing"]["additionalAddressLine2"],
			'QSTR' => $data["billing"]["street"],
			'QPLZ' => $data["billing"]["zipCode"],
			'QORT' => $data["billing"]["city"],
			'QLND' => $data["billing"]["country"]["name"],
			'QTEL' => $data["billing"]["phone"],
			'QEMA' => $data["customer"]["email"],
			'qkt1' => $data["paymentInstances"][0]["bankName"],
			'qbl1' => $data["paymentInstances"][0]["bankCode"],
			'qkn1' => $data["paymentInstances"][0]["accountNumber"],
			'qki1' => $data["paymentInstances"][0]["iban"],
			'qkb1' => $data["paymentInstances"][0]["bic"],
			'qwid' => $data["customer"]["id"],
		];	

		return $facCustomer;
	}
	
	private function getFacHeadData($data) {
		
		if ($this->channelFacData['shopware']['GroupCustomer'] ) {
			$customerNumber = $this->channelFacData['shopware']['CustomerNumber'];
		} else {
			$customerNumber = $this->channelFacData['shopware']['CustomerNumber'] + $data["customer"]["number"];
		}
		
		$facHead = [
			'FXNR' => $customerNumber ,
			'FXNS' => $customerNumber ,
			'FXNA' => $customerNumber ,
			'IFNR' => $this->facFiliale,
			'FTYP' => 2,
			'FPRJ' => '000000',
            'CKSS' => '000000',
            'OBNR' => '000000',
			'FNUM' => $data["number"],
			'FBLG' => $data["id"],
			'QSBZ' => 'shopware Order '. $data["number"],
			'FDTM' => date("d.m.Y",time()),
			'FLDT' => date("d.m.Y", time()+(60*60*18)),
			'SIGS' => $data["invoiceAmount"],
			'SGES' => $data["invoiceAmount"],
			'QANR' => $data["billing"]["title"],
			'QNA1' => $data["billing"]["firstName"]." ".$data["billing"]["lastName"],
			'QNA2' => $data["billing"]["additionalAddressLine1"],
			'QNA3' => $data["billing"]["additionalAddressLine2"],
			'QSTR' => $data["billing"]["street"],
			'QPLZ' => $data["billing"]["zipCode"],
			'QORT' => $data["billing"]["city"],
			'QLND' => $data["billing"]["country"]["name"],
			'QTEL' => $data["billing"]["phone"],
			'QEMA' => $data["customer"]["email"],
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
		if (($data["paymentStatus"]["name"] == 'completely_paid')  or ( in_array( $data["payment"]["name"] , $this->isPaidPaymentTypes) ) ) {
			$facHead['FFKT'] = 0;
		} else {
			$facHead['FFKT'] = 1;
		}

		// head text 
		$customerComment = $this->SplitABZ($data["customerComment"]);

		$facHead['QTXK'] = [
			'Payment: '.$data["payment"]["description"],
			'Payment ID: '.$data["transactionId"],
			'Versand: '.$data["dispatch"]["name"],
			'Kundenkommentar: '
		];
		foreach($customerComment as $commentLine) {
			$facHead['QTXK'][] = $commentLine;
		}
		
		// shipping adress
		$facHead['LFA'] = [
			'QANR='.$data["shipping"]["title"],
			'QNA1='.$data["shipping"]["firstName"].' '.$data["shipping"]["lastName"],
			'QNA2='.$data["shipping"]["additionalAddressLine1"],
			'QNA3='.$data["shipping"]["additionalAddressLine2"],
			'QSTR='.$data["shipping"]["street"],
			'QPLZ='.$data["shipping"]["zipCode"],
			'QORT='.$data["shipping"]["city"],
			'QLND='.$data["shipping"]["country"]["name"]
		];
		
		return $facHead;
	}	
	
	private function getFacPosData($data) {
		
		$posText = $this->SplitABZ($data['articleName']);
		
		$article = new product(sprintf("%08d",$data["articleNumber"]));
		if ($article->getProductId() == NULL) {
			print "Article ".$data["articleNumber"]." ".$data['articleName']." not found!</br>";
			$posFmge = $data["quantity"] ; 
			$posPrice = $data["price"];				
			$posApjs = 1;
			$posApkz = 1;
		} else {
			$posFmge = $data["quantity"] / $article->productData[0]['amgm']; 
			$posPrice = $data["price"] / $article->productData[0]['amgm'] * $article->productData[0]['apjs'];
			$posApjs = $article->productData[0]['apjs'];
			$posApkz = $article->productData[0]['apkz'];
		}
		if ($this->channelFacData['shopware']['GroupCustomer'] ) {
			$customerNumber = $this->channelFacData['shopware']['CustomerNumber'];
		} else {
			$customerNumber = $this->channelFacData['shopware']['CustomerNumber'] + $data["customernumber"];
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
			'FNUM' => $data["number"],
			'FBLG' => $data["orderId"],
			'QSBZ' => 'shopware Order '. $data["number"],
			'FDTM' => date("d.m.Y",time()),
			'FLDT' => date("d.m.Y", time()+(60*60*18)),			
			'FPOS' => $this->fpos,
			'AAMR' => sprintf("%08d",$data["articleNumber"]),
			'ARNR' => sprintf("%08d",$data["articleNumber"]),
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
			'FMGB' => $data["quantity"],
			'FMGZ' => $article->productData[0]['amgz'],
			'FMGN' => $article->productData[0]['amgn'],
			'FMGE' => $posFmge,
			'APJS' => $article->productData[0]['apjs'],
			'AMEH' => $article->productData[0]['ameh'],
			'AGEH' => $article->productData[0]['ageh'],
			'FEPB' => $posPrice,
			'QPAS' => '',
			'ASCO' => $data["ean"],
			'FACT' => 9219,
		];	

		$facPos['FABL'] = [
			'SW_PAYMENT_TRANSACTION_ID='. $data["transactionId"],
		];
		
		$this->fpos++;
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
	
}