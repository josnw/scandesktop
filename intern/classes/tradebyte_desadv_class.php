<?php 


class tradebyteDesAdv {

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
	private $csvSep = ";";
	
	public function __construct($filename) {
		
		include ("./intern/config.php");
		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		$this->TradebyteWebshopNumber = $TradebyteWebshopNumber;
		$this->isPaidPaymentTypes = $isPaidPaymentTypes;
		$this->importHandle = new myfile($filename);
		$firstLine = $this->importHandle->readLn();
		if (substr_count($firstLine, $this->csvSep) < 2) {
			if (DEBUG) { print("Switch Seperator to Tab"); }
			$this->csvSep = "\t";
		}
		$this->importKeyList = str_getcsv($firstLine, $this->csvSep);
		if (DEBUG) { print_r($this->importKeyList); }
		
		$this->channel = $channelFacData;
		$this->facFiliale = $FacFiliale;
		
		$fqry = "update auftr_pos p set fpid = nextval('gen_positionsid') where fpid is null and fart = 1";
		$f_qry = $this->pg_pdo->prepare($fqry);
		$f_qry->execute() or die (print_r($f_qry->errorInfo()));
		
		return true;
	}
	
	public function readDeliveryData() {
	
		$this->OrdersData = [];
		$this->OrdersIdList = [];
		//read line from Importfile
		
		$oldOrderId = 0;
		
		while ( $line = $this->importHandle->readCSV($this->csvSep) ) {
			
			//combine line with head	
			$row = array_combine($this->importKeyList, $line);

			if (empty($row["Auftrag-ID"])) {
				$row["Auftrag-ID"] = $row["Auftrag-Nr"];
			}
			
			if ($oldOrderId <> $row["Auftrag-ID"]) {
				$cnt = 0;		
				$oldOrderId = $row["Auftrag-ID"];
				$this->OrdersIdList[] = $row["Auftrag-ID"];
			}
			
			//split head data and pos data

			$this->OrdersData[$row['Auftrag-ID']]['head']['TB_ORDER_ID'] = $row["Auftrag-ID"];
			$this->OrdersData[$row['Auftrag-ID']]['head']['CHANNEL_ORDER_ID'] = $row["Auftrag-Nr"];
			$this->OrdersData[$row['Auftrag-ID']]['head']['PARCEL_TYPE'] = $row["Pakettyp"];
			$this->OrdersData[$row['Auftrag-ID']]['head']['SHIP_CODE'] = $row["Ship-Code"];
			$this->OrdersData[$row['Auftrag-ID']]['head']['CUST_SELL_FIRSTNAME'] = $row["Vorname-Rechnung"];
			$this->OrdersData[$row['Auftrag-ID']]['head']['CUST_SELL_SURNAME'] = $row["Nachname-Rechnung"];
			$this->OrdersData[$row['Auftrag-ID']]['head']['CUST_SELL_EMAIL'] = $row["E-Mail-Rechnung"];
			if (isset($row["Bestelldatum"])) {
				$this->OrdersData[$row['Auftrag-ID']]['head']['ORDER_DATE'] = $row["Bestelldatum"];
			} else {
				$this->OrdersData[$row['Auftrag-ID']]['head']['ORDER_DATE'] = $row["Erstelldatum"];
			}
			
			
			$this->OrdersData[$row['Auftrag-ID']]['pos'][$cnt]['POS_ANR'] = $row["Artikel-Nr"];
			$this->OrdersData[$row['Auftrag-ID']]['pos'][$cnt]['POS_TEXT'] = $row["Produktname"];
			if (strlen($row["Variante"]) > 0) {
				$this->OrdersData[$row['Auftrag-ID']]['pos'][$cnt]['POS_TEXT'] .= ' Variante: '.$row["Variante"];
			}
			$this->OrdersData[$row['Auftrag-ID']]['pos'][$cnt]['SHIP_QUANTITY'] = $row["Liefermenge"];
			$this->OrdersData[$row['Auftrag-ID']]['pos'][$cnt]['POS_CHANNEL_ID'] = $row["Kanal Positionsreferenz"];
			
			$cnt++;
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
		$facHead['FFBG'] = substr($this->OrdersData[$orderId]['head']['CHANNEL_ORDER_ID'],0,20);
		if (strlen($this->OrdersData[$orderId]['head']['CHANNEL_ORDER_ID']) > 20 ) {
			$facHead['FFBZ'] = substr($this->OrdersData[$orderId]['head']['CHANNEL_ORDER_ID'],19,20);
		}
		
		// automatic flag if payed
		if ((strlen($this->OrdersData[$orderId]['head']['PAYMENT_TRANSACTION_ID']) > 0 )
		     or ( in_array( $this->OrdersData[$orderId]['head']['PAYMENT_TYPE'] , $this->isPaidPaymentTypes) )) {
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
				'FLDT' => date("d.m.Y", time()-(60*60*18)),   
				'FPOS' => $cnt++,
				'FPNZ' => $posData['POS_LFDNR'],
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
				'FACT' => 9219,
			];	

			$facPos[$cnt]['FABL'] = [
				'TB_PAYMENT_TRANSACTION_ID='.$this->OrdersData[$orderId]['head']['PAYMENT_TRANSACTION_ID'],
				'TB_POS_TB_ID='.$posData['POS_TB_ID'],
				'TB_POS_A_ID='.$posData['POS_A_ID'],
				'TB_POS_CHANNEL_ID='.$posData['POS_CHANNEL_ID'],
				'TB_POS_BILLING_TEXT='.$posData['POS_BILLING_TEXT'],
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
		return $this->OrdersData[$orderId]['head']['CHANNEL_KEY'];
	}

	public function getOrderData($orderId) {
		return $this->OrdersData[$orderId];
	}

}	
?>