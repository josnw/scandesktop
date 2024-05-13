<?php 


class tradebyteOrders {

	private $pg_pdo;
	private $TradebyteWebshopNumber;
	private $importHandle;
	private $importKeyList;
	private $activeOrderID;
	private $OrdersData;
	private $OrdersIdList;
	private $headData;
	private $posData;
	private $channel;
	private $facFiliale;
	private $isPaidPaymentTypes;
	
	public function __construct($filename) {
		
		include ("./intern/config.php");
		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		$this->TradebyteWebshopNumber = $TradebyteWebshopNumber;
		$this->isPaidPaymentTypes = $isPaidPaymentTypes;
		$this->Shipping = $Shipping;
		$this->importHandle = new myfile($filename);
		$this->importKeyList = $this->importHandle->readCSV();

		$this->channel = $channelFacData;
		$this->facFiliale = $FacFiliale;
		
		return true;
	}
	
	public function readFullData() {
	
		$this->OrdersData = [];
		$this->OrdersIdList = [];
		//read line from Importfile
		while ( $line = $this->importHandle->readCSV() ) {
			
			//combine line with head	
			$row = array_combine($this->importKeyList, $line);
			
			//split head data and pos data
			foreach($row as $key=>$value) {
				
				if (($key == 'CHANNEL_KEY') and empty($this->channel[$value]['CustomerNumber'])) {
					$this->channel[$value] = $this->channel['DEFAULT']; 
				}
					
				if (substr($key,0,4) == 'POS_') {
					$this->OrdersData[$row['TB_ORDER_ID']]['pos'][$row['POS_LFDNR']][$key] = $value;
				} else {
					$this->OrdersData[$row['TB_ORDER_ID']]['head'][$key] = $value;
					if (!in_array($row['TB_ORDER_ID'], $this->OrdersIdList)) {
						$this->OrdersIdList[] = $row['TB_ORDER_ID'];
					}
				}
			}
		}
	}
	
	public function getFacHeadData($orderId) {
		$facHead = [
			'FXNR' => $this->channel[$this->OrdersData[$orderId]['head']['CHANNEL_KEY']]['CustomerNumber'],
			'FXNS' => $this->channel[$this->OrdersData[$orderId]['head']['CHANNEL_KEY']]['CustomerNumber'],
			'FXNA' => $this->channel[$this->OrdersData[$orderId]['head']['CHANNEL_KEY']]['CustomerNumber'],
			'IFNR' => $this->facFiliale,
			'FTYP' => 2,
			'FPRJ' => '000000',
            'CKSS' => '000000',
            'OBNR' => '000000',
			'FNUM' => $this->OrdersData[$orderId]['head']['TB_ORDER_ID'],
			'FBLG' => $this->OrdersData[$orderId]['head']['TB_ORDER_ID'],
			'QSBZ' => $this->OrdersData[$orderId]['head']['CHANNEL_KEY'].' '.$this->OrdersData[$orderId]['head']['CHANNEL_ORDER_ID'],
			'FDTM' => date("d.m.Y",strtotime($this->OrdersData[$orderId]['head']['ORDER_DATE'])),
			'FLDT' => date("d.m.Y", time()+(60*60*18)),
			'SIGS' => $this->OrdersData[$orderId]['head']['TOTAL_AMOUNT'],
			'SGES' => $this->OrdersData[$orderId]['head']['TOTAL_AMOUNT'],
			'QANR' => $this->OrdersData[$orderId]['head']['CUST_SELL_SALUTATION'],
			'QNA2' => $this->OrdersData[$orderId]['head']['CUST_SELL_SURNAME'],
			'QNA1' => $this->OrdersData[$orderId]['head']['CUST_SELL_FIRSTNAME'],
			'QNA3' => $this->OrdersData[$orderId]['head']['CUST_SELL_EXTENSION'],
			'QSTR' => $this->OrdersData[$orderId]['head']['CUST_SELL_STREET_NO'],
			'QPLZ' => $this->OrdersData[$orderId]['head']['CUST_SELL_ZIP'],
			'QORT' => $this->OrdersData[$orderId]['head']['CUST_SELL_CITY'],
			'QLND' => $this->OrdersData[$orderId]['head']['CUST_SELL_COUNTRY_CODE'],
			'QTEL' => $this->OrdersData[$orderId]['head']['CUST_SELL_TEL_PRIV'],
			'QTE2' => $this->OrdersData[$orderId]['head']['CUST_SELL_TEL_OFFICE'],
			'QEMA' => $this->OrdersData[$orderId]['head']['CUST_SELL_EMAIL'],
			'QUSS' => 1,
			'QPRA' => 0,
			'KPRP' => 6,
			'FBKZ' => 60,
			'KZBE' => 6,
			'QFRM' => $this->channel[$this->OrdersData[$orderId]['head']['CHANNEL_KEY']]['formId'],
            'QHWG' => 'EUR',
            'QZWG' => 'EUR'
		];

		//split channel order number 
		$facHead['FFBG'] = substr($this->OrdersData[$orderId]['head']['CHANNEL_ORDER_NR'],0,20);
		if (strlen($this->OrdersData[$orderId]['head']['CHANNEL_ORDER_NR']) > 20 ) {
			$facHead['FFBZ'] = substr($this->OrdersData[$orderId]['head']['CHANNEL_ORDER_NR'],19,20);
		}
		
		// automatic flag if payed
		if ((strlen($this->OrdersData[$orderId]['head']['PAYMENT_TRANSACTION_ID']) > 0 )
		     or ( in_array( $this->OrdersData[$orderId]['head']['PAYMENT_TYPE'] , $this->isPaidPaymentTypes) )
			 or ( !empty($this->channel[$this->OrdersData[$orderId]['head']['CHANNEL_KEY']]['isPaid']) ) ) {
			$facHead['FFKT'] = 0;
		} else {
			$facHead['FFKT'] = 1;
		}

		// head text 
		$customerComment = $this->SplitABZ($this->OrdersData[$orderId]['head']['CUSTOMER_COMMENT']);
		$facHead['QTXK'] = [
			'Payment: '.$this->OrdersData[$orderId]['head']['PAYMENT_TYPE'],
			'Payment ID: '.$this->OrdersData[$orderId]['head']['PAYMENT_TRANSACTION_ID'],
			'Kundenkommentar: '
		];
		foreach($customerComment as $commentLine) {
			$facHead['QTXK'][] = $commentLine;
		}
		
		// shipping adress
		$facHead['LFA'] = [
			'QANR='.$this->OrdersData[$orderId]['head']['CUST_SHIP_SALUTATION'],
			'QNA1='.$this->OrdersData[$orderId]['head']['CUST_SHIP_FIRSTNAME'].' '.$this->OrdersData[$orderId]['head']['CUST_SHIP_SURNAME'],
			'QNA2='.$this->OrdersData[$orderId]['head']['CUST_SHIP_EXTENSION'],
			'QSTR='.$this->OrdersData[$orderId]['head']['CUST_SHIP_STREET_NO'],
			'QPLZ='.$this->OrdersData[$orderId]['head']['CUST_SHIP_ZIP'],
			'QORT='.$this->OrdersData[$orderId]['head']['CUST_SHIP_CITY'],
			'QLND='.$this->OrdersData[$orderId]['head']['CUST_SHIP_COUNTRY_CODE'],
			'QTEL='.$this->OrdersData[$orderId]['head']['CUST_SHIP_TEL_PRIV'],
			'QTE2='.$this->OrdersData[$orderId]['head']['CUST_SHIP_TEL_OFFICE'],
			'QEMA='.$this->OrdersData[$orderId]['head']['CUST_SHIP_EMAIL']
		];
		
		return $facHead;
	}	
	
	public function getFacPosData($orderId) {
		$cnt = 0;
		$facPos = [];
		foreach($this->OrdersData[$orderId]['pos'] as $posData) {
			
			$posText = $this->SplitABZ($posData['POS_TEXT']);
			
			$article = new product($posData['POS_ANR']);
			if ($article->getProductId() == NULL) {
				print "Article ".$posData['POS_ANR']." ".$posData['POS_TEXT']." not found!</br>";
				$posFmge = $posData['POS_QUANTITY'] ; 
				$posPrice = $posData['POS_SALESPRICE'];				
				$posApjs = 1;
				$posApkz = 1;
			} else {
				$posFmge = $posData['POS_QUANTITY'] / $article->productData[0]['amgm']; 
				$posPrice = $posData['POS_SALESPRICE'] / $article->productData[0]['amgm'] * $article->productData[0]['apjs'];
				$posApjs = $article->productData[0]['apjs'];
				$posApkz = $article->productData[0]['apkz'];
			}
			if ($article->productData[0]['aart'] == 2) {
				$fakt = 3153923;
				$fakx = '';
				$fpid = $this->channel[$this->OrdersData[$orderId]['head']['CHANNEL_KEY']]['Filiale'] * 1000000000 + $posData['POS_TB_ID'];
			} elseif (!empty($article->productData[0]['avsd'])) {
				$fakt = 8195;
				$fakx = 113;
				$fpid = $this->channel[$this->OrdersData[$orderId]['head']['CHANNEL_KEY']]['Filiale'] * 1000000000 + $posData['POS_TB_ID'];
			} else {
				$fakt = 8195;
				$fakx = '';
				$fpid = $this->channel[$this->OrdersData[$orderId]['head']['CHANNEL_KEY']]['Filiale'] * 1000000000 + $posData['POS_TB_ID'];
			}			
			
			$facPos[$cnt] = [
				'FXNR' => $this->channel[$this->OrdersData[$orderId]['head']['CHANNEL_KEY']]['CustomerNumber'],
				'FXNS' => $this->channel[$this->OrdersData[$orderId]['head']['CHANNEL_KEY']]['CustomerNumber'],
				'FXNA' => $this->channel[$this->OrdersData[$orderId]['head']['CHANNEL_KEY']]['CustomerNumber'],
				'IFNR' => $this->facFiliale,
				'FTYP' => 2,
				'FPRJ' => '000000',
				'CKSS' => '000000',
				'OBNR' => '000000',
				'FNUM' => $this->OrdersData[$orderId]['head']['TB_ORDER_ID'],
				'FBLG' => $this->OrdersData[$orderId]['head']['TB_ORDER_ID'],
				'QSBZ' => $this->OrdersData[$orderId]['head']['CHANNEL_KEY'].' '.$this->OrdersData[$orderId]['head']['CHANNEL_ORDER_ID'],
				'FDTM' => date("d.m.Y",strtotime($this->OrdersData[$orderId]['head']['ORDER_DATE'])),
				'FLDT' => date("d.m.Y", time()+(60*60*18)),
				'FPOS' => $cnt,
				'FPNZ' => $posData['POS_LFDNR'],
				'FPID' => $fpid,
				'AAMR' => $posData['POS_ANR'],
				'ARNR' => $posData['POS_ANR'],
				'QGRP' => $article->productData[0]['qgrp'],
				'FART' => 1,
				'XXAK' => '',
				'XYAK' => '',
				'QNVE' => $this->OrdersData[$orderId]['head']['PAYMENT_TRANSACTION_ID'],
				'ALGO' => 'HL',
				'APKZ' => $article->productData[0]['apkz'],
				'ASMN' => 1,
				'QPRA' => 0,
				'ASMZ' => 1,
				'ABZ1' => $posText[0],
				'ABZ2' => $posText[1],
				'ABZ3' => $posText[2],
				'ABZ4' => $posText[3],
				'FMGB' => $posData['POS_QUANTITY'],
				'FMGZ' => $article->productData[0]['amgz'],
				'FMGN' => $article->productData[0]['amgn'],
				'FMGE' => $posFmge,
				'APJS' => $article->productData[0]['apjs'],
				'AMEH' => $article->productData[0]['ameh'],
				'AGEH' => $article->productData[0]['ageh'],
				'FEPB' => $posPrice,
				'QPAS' => '',
				'ASCO' => $posData['POS_EAN'],
				'FAKT' => $fakt,
			];	

			$facPos[$cnt]['FABL'] = [
				'TB_PAYMENT_TRANSACTION_ID='.$this->OrdersData[$orderId]['head']['PAYMENT_TRANSACTION_ID'],
				'TB_POS_TB_ID='.$posData['POS_TB_ID'],
				'TB_POS_A_ID='.$posData['POS_A_ID'],
				'TB_POS_CHANNEL_ID='.$posData['POS_CHANNEL_ID'],
				'TB_POS_BILLING_TEXT='.$posData['POS_BILLING_TEXT'],
			];
			
			$cnt++;
			
			if ($article->productData[0]['aart'] == 2) {
				$stckListData = $article->getStcklistData();
				foreach( $stckListData as $slArticle ) {
					$facPos[$cnt] = $this->getStckListPos($slArticle, $posFmge, $cnt, $orderId);
					$cnt++;
				}
			}
		}
		
		
		if ($this->OrdersData[$orderId]['head']['SHIPPING_COSTS'] > 0) {
			
			if (isset($this->Shipping['article'])) {
				$article = new product($this->Shipping['article']);
			} else {
				$article = new product($this->OrdersData[$orderId]['head']['SHIPPING_COSTS'],'searchPrice', ['fromArticle' => $this->Shipping['fromArticle'], 'toArticle' => $this->Shipping['toArticle'] ] );
			}
			$shippingArticle = $article->getProductId();
			
			$facPos[$cnt] = [
				'FXNR' => $this->channel[$this->OrdersData[$orderId]['head']['CHANNEL_KEY']]['CustomerNumber'],
				'FXNS' => $this->channel[$this->OrdersData[$orderId]['head']['CHANNEL_KEY']]['CustomerNumber'],
				'FXNA' => $this->channel[$this->OrdersData[$orderId]['head']['CHANNEL_KEY']]['CustomerNumber'],
				'IFNR' => $this->facFiliale,
				'FTYP' => 2,
				'FPRJ' => '000000',
				'CKSS' => '000000',
				'OBNR' => '000000',
				'FNUM' => $this->OrdersData[$orderId]['head']['TB_ORDER_ID'],
				'FBLG' => $this->OrdersData[$orderId]['head']['TB_ORDER_ID'],
				'QSBZ' => $this->OrdersData[$orderId]['head']['CHANNEL_KEY'].' '.$this->OrdersData[$orderId]['head']['CHANNEL_ORDER_ID'],
				'FDTM' => date("d.m.Y",strtotime($this->OrdersData[$orderId]['head']['ORDER_DATE'])),
				'FLDT' => date("d.m.Y", time()+(60*60*18)),
				'FPOS' => $cnt,
				'FPNZ' => '',
				'AAMR' => $shippingArticle,
				'ARNR' => $shippingArticle,
				'QGRP' => $article->productData[0]['qgrp'],
				'FART' => 1,
				'XXAK' => '',
				'XYAK' => '',
				'QNVE' => $this->OrdersData[$orderId]['head']['PAYMENT_TRANSACTION_ID'],
				'ALGO' => 'HL',
				'APKZ' => $article->productData[0]['apkz'],
				'ASMN' => 1,
				'QPRA' => 0,
				'ASMZ' => 1,
				'ABZ1' => $article->productData[0]['abz1'],
				'ABZ2' => $article->productData[0]['abz2'],
				'ABZ3' => $article->productData[0]['abz3'],
				'ABZ4' => $article->productData[0]['abz4'],
				'FMGB' => 1,
				'FMGZ' => $article->productData[0]['amgz'],
				'FMGN' => $article->productData[0]['amgn'],
				'FMGE' => 1,
				'APJS' => $article->productData[0]['apjs'],
				'AMEH' => $article->productData[0]['ameh'],
				'AGEH' => $article->productData[0]['ageh'],
				'FEPB' => $this->OrdersData[$orderId]['head']['SHIPPING_COSTS'],
				'QPAS' => '',
				'ASCO' => '',
				'FAKT' => 8195,
			];	
		}
		
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

	public function getOrderIds() {
		return $this->OrdersIdList;
	}

	public function getChannel($orderId) {
		if (empty($this->OrdersData[$orderId]['head']['CHANNEL_KEY'])) {
			return 'DEFAULT';
		}
		return $this->OrdersData[$orderId]['head']['CHANNEL_KEY'];
	}

	public function  getStckListPos($slArticle, $quantity, $cnt, $orderId) {

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
			
			$facPos = [
				'FXNR' => $this->channel[$this->OrdersData[$orderId]['head']['CHANNEL_KEY']]['CustomerNumber'],
				'FXNS' => $this->channel[$this->OrdersData[$orderId]['head']['CHANNEL_KEY']]['CustomerNumber'],
				'FXNA' => $this->channel[$this->OrdersData[$orderId]['head']['CHANNEL_KEY']]['CustomerNumber'],
				'IFNR' => $this->facFiliale,
				'FTYP' => 2,
				'FPRJ' => '000000',
				'CKSS' => '000000',
				'OBNR' => '000000',
				'FNUM' => $this->OrdersData[$orderId]['head']['TB_ORDER_ID'],
				'FBLG' => $this->OrdersData[$orderId]['head']['TB_ORDER_ID'],
				'QSBZ' => $this->OrdersData[$orderId]['head']['CHANNEL_KEY'].' '.$this->OrdersData[$orderId]['head']['CHANNEL_ORDER_ID'],
				'FDTM' => date("d.m.Y",strtotime($this->OrdersData[$orderId]['head']['ORDER_DATE'])),
				'FLDT' => date("d.m.Y", time()+(60*60*18)),
				'FPOS' => $cnt,
				'FPNZ' => '',
				'AAMR' => $slArticle['astl'],
				'ARNR' => $slArticle['astl'],
				'QGRP' => $article->productData[0]['qgrp'],
				'FART' => 6,
				'XXAK' => '',
				'XYAK' => '',
				'QNVE' => $this->OrdersData[$orderId]['head']['PAYMENT_TRANSACTION_ID'],
				'ALGO' => 'HL',
				'APKZ' => $article->productData[0]['apkz'],
				'ASMN' => 1,
				'QPRA' => 0,
				'ASMZ' => 1,
				'ABZ1' => utf8_decode($article->productData[0]['abz1']),
				'ABZ2' => utf8_decode($article->productData[0]['abz2']),
				'ABZ3' => utf8_decode($article->productData[0]['abz3']),
				'ABZ4' => utf8_decode($article->productData[0]['abz4']),
				'FMGB' => $posFmgb,
				'FMGZ' => $article->productData[0]['amgz'],
				'FMGN' => $article->productData[0]['amgn'],
				'ASMZ' => $slArticle['asmz'],
				'ASMN' => $slArticle['asmn'],
				'FMGE' => $posFmge,
				'APJS' => $article->productData[0]['apjs'],
				'AMEH' => $article->productData[0]['ameh'],
				'AGEH' => $article->productData[0]['ageh'],
				'FEPB' => $posPrice,
				'QPAS' => '',
				'ASCO' => $posData['POS_EAN'],
				'FAKT' => 8195,
			];	
			
			$facPos['FABL'] = [
				'TB_PAYMENT_TRANSACTION_ID='.$this->OrdersData[$orderId]['head']['PAYMENT_TRANSACTION_ID'],
				'TB_POS_TB_ID='.$posData['POS_TB_ID'],
				'TB_POS_A_ID='.$posData['POS_A_ID'],
				'TB_POS_CHANNEL_ID='.$posData['POS_CHANNEL_ID'],
				'TB_POS_BILLING_TEXT='.$posData['POS_BILLING_TEXT'],
			];
		
		return $facPos;
	}
}	
?>