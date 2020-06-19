<?php

class picklist {
		
	private $belegId;
	private $my_pdo;
	private $pg_pdo;
	
	public $pickUser;
	public $OrderCount;
	public $ItemCount;
	public $pickListNumber;
	public $pickName;
	public $pickCreateDate;
	public $pickStatus;
	public $pickLastEdit;
	public $itemList;
	public $orderList;

	public function __construct($Id, $count = NULL, $maxWeight = NULL, $name = NULL) {
		if (isset($count) and isset($maxWeight) and isset($name)) {
			$this->createPickList($Id, $count, $maxWeight, $name);
		} else {
			$this->getPickList($Id);
		}
	}
	
	// Pickliste erzeugen
	private function createPickList($userId, $count = 20, $maxWeight = NULL, $name = NULL) {

		include ("./intern/config.php");

		$this->pickUser = $userId;
		$count = preg_replace("[^0-9]","",$count);

		$this->my_pdo = new PDO($ecserver, $ecuser, $ecpass, $options);
		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		
		//Artikel der älteste Bestellungen und TopArtikel einlesen
		$picArt_sql  = 'select Artikel, min(date(orderDate)) as minDate , count(*) as ArtAnz from versand v 
					inner join versand_pos p using (BelegID) 
					inner join katalog k using (Artikel)
				  where ship_status is NULL and ship_picklist is NULL 
				    and (Gewicht < :maxWeight or Gewicht is null)
				  group by Artikel
				  order by minDate, ArtAnz desc limit '.$count;

/*		$picArt_qry = $my_pdo->prepare($picArt_sql);
		$picArt_qry->bindValue(':maxWeight', $maxWeight);
		$picArt_qry->bindValue(':limit', $count);
		$picArt_qry->execute() or die (print_r($picArt_qry->errorInfo()));
		
		$ArtikelStr = '';
		while($picArt_row = $picArt_qry->fetch( PDO::FETCH_ASSOC )) {
			if (strlen($ArtikelStr) > 0) { $ArtikelStr .= 	 ","; }
			$ArtikelStr .= 	preg_replace("[^A-Z0-9]","?",$picArt_row["Artikel"]);
		}
*/
		// Orderliste zusammenstellen
		$picOrder_sql  = 'select BelegID, max(Gewicht) as maxGewicht, min(date(orderDate)) as minDate, max(ArtAnz) as ArtAnz from versand v 
			inner join versand_pos p using (BelegID) 
			inner join katalog k using (Artikel)
			inner join ( '.$picArt_sql.' ) c using (Artikel)
		  where ship_status is NULL and ship_picklist is NULL 
		  group by BelegID
		  having (maxGewicht < :maxWeight or maxGewicht is null)
		  order by minDate, ArtAnz desc limit '.$count;

		$picOrder_qry = $this->my_pdo->prepare($picOrder_sql);
		//$picOrder_qry->bindValue(':limit', $count);
		$picOrder_qry->bindValue(':maxWeight', $maxWeight);
		$picOrder_qry->execute() or die (print_r($picOrder_qry->errorInfo()));
		$OrderListStr = '';
		$this->OrderCount = 0;
		while($picOrder_row = $picOrder_qry->fetch( PDO::FETCH_ASSOC )) {
			if (strlen($OrderListStr) > 0) { $OrderListStr .= 	 ","; }
			if ($this->checkInvoice($picOrder_row["BelegID"],$picOrder_row["minDate"]) ) {
				$OrderListStr .= 	preg_replace("[^A-Z0-9]","?",$picOrder_row["BelegID"]);
				$this->OrderCount++;
			}
		}
		
		// Pickliste anlegen
		if ($name == NULL) {
			$this->pickName =  $_SESSION["name"]."-".date("Ymd-hi");
		} else {
			$this->pickName = $name;
		}
		$this->pickCreateDate = time();
		$this->pickStatus = 0;
		$this->pickLastEdit = time();
		$pickList_sql = "insert into pickliste (pickUser, pickName, pickCreateDate, pickStatus, pickLastEdit) VALUES (:pickUser, :pickName, now(), :pickStatus, now())";
		$pickList_qry = $this->my_pdo->prepare($pickList_sql);
		$pickList_qry->bindValue(':pickUser', $_SESSION["uid"]);
		$pickList_qry->bindValue(':pickName', $this->pickName );
		$pickList_qry->bindValue(':pickStatus', $this->pickStatus );
		$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
		
		$pickId_sql = "select max(pickId) as pickId from pickliste where pickUser = :pickUser and pickName = :pickName and pickStatus = :pickStatus";
		$pickId_qry = $this->my_pdo->prepare($pickId_sql);
		$pickId_qry->bindValue(':pickUser', $_SESSION["uid"]);
		$pickId_qry->bindValue(':pickName', $this->pickName );
		$pickId_qry->bindValue(':pickStatus', $this->pickStatus );
		$pickId_qry->execute() or die (print_r($pickId_qry->errorInfo()));
		$pickId_row = $pickId_qry->fetch( PDO::FETCH_ASSOC );
		$this->pickListNumber = $pickId_row["pickId"];
		
		// Pickliste zuordnen
		$pickUpd_sql = "update versand set ship_user = :pickUser, ship_picklist = :pickId , ship_status = 0 where ship_picklist is NULL and ship_status is NULL and BelegID in (".$OrderListStr.")";
		$pickUpd_qry = $this->my_pdo->prepare($pickUpd_sql);
		$pickUpd_qry->bindValue(':pickUser', $_SESSION["uid"]);
		$pickUpd_qry->bindValue(':pickId', $this->pickListNumber);
		$pickUpd_qry->execute() or die (print_r($pickUpd_qry->errorInfo()));
		
	}
	
	// PickList Header aus DB lesen
	private function getPickList($pickId) {
		
		include ("./intern/config.php");

		$this->my_pdo = new PDO($ecserver, $ecuser, $ecpass, $options);
		
		$pickList_sql = "select * from pickliste where pickId = :pickId";
		
		$pickList_qry = $this->my_pdo->prepare($pickList_sql);
		$pickList_qry->bindValue(':pickId', $pickId );
		$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
		$pickList_row = $pickList_qry->fetch( PDO::FETCH_ASSOC );
		$this->pickListNumber = $pickList_row["pickId"];
		$this->pickUser = $pickList_row["pickUser"];
		$this->pickName = $pickList_row["pickName"];
		$this->pickCreateDate = $pickList_row["pickCreateDate"];
		$this->pickStatus = $pickList_row["pickStatus"];
		$this->pickLastEdit = $pickList_row["pickLastEdit"];
	}
	
	// Artikeldaten der Pickliste ausgeben (EInzelartikel, Stücklisten aufgelöst)
	public function getItemList() {

		if ((isset($this->itemList)) and (count($this->itemList) > 0)) {
			return $this->itemList;
		} else {
			$pickList_sql  = 'select BelegID from versand where ship_picklist = :pickId';
			  
			$pickList_qry = $this->my_pdo->prepare($pickList_sql);
			$pickList_qry->bindValue(':pickId', $this->pickListNumber);
			$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
			
			$this->itemList = [];
			while($pickList_row = $pickList_qry->fetch( PDO::FETCH_ASSOC )) {
				$order = new order($pickList_row["BelegID"]);
				while ($items = $order->getNextItem()) {
					foreach($items as $item) {
						if ( (!isset($item["Stckliste"])) or ($item["Stckliste"] != 1) ) {
							$this->itemList[$item["Artikel"]]["Bezeichnung"] = $item["Bezeichnung"];
							$this->itemList[$item["Artikel"]]["Einheit"] = $item["Einheit"];
							if (isset($this->itemList[$item["Artikel"]]["Menge"])) {
								$this->itemList[$item["Artikel"]]["Menge"] += $item["Menge"];
							} else {
								$this->itemList[$item["Artikel"]]["Menge"] = $item["Menge"];
							}
							$productData = new product($item["Artikel"]);
							$this->itemList[$item["Artikel"]]["Gtin"] = $productData->getProductGtin();
						}
					}
				}
			}
			return $this->itemList;
		}
	}

	// Bestellungen der Pickliste ausgeben (Übersicht, keine Artikel)
	public function getOrderList($status) {

		if ((isset($this->orderList)) and (count($this->orderList) > 0)) {
			return $this->orderList;
		} else {
			$pickStatus = preg_replace("[^0-9,]","",$status);
			$pickList_sql  = 'select * from versand where ship_picklist = :pickId and ship_status in ('.$pickStatus.') order by orderdate, BelegID';
			  
			$pickList_qry = $this->my_pdo->prepare($pickList_sql);
			$pickList_qry->bindValue(':pickId', $this->pickListNumber);
			$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
			
			$this->orderList = [];
			$this->orderList = $pickList_qry->fetchAll( PDO::FETCH_ASSOC );
			foreach($this->orderList as $key => $order) {
				foreach($order as $field => $value) {
					$this->orderList[$key][$field] = utf8_encode($value);
				}
			}

			return $this->orderList;
		}
	}	

	// nächste offene Einzelbestellungen der Pickliste ausgeben)
	public function getNextPackOrder() {

		$pickList_sql  = 'select BelegID from versand where ship_picklist = :pickId and ship_status in (0,1)  order by orderdate, BelegID limit 1';
		  
		$pickList_qry = $this->my_pdo->prepare($pickList_sql);
		$pickList_qry->bindValue(':pickId', $this->pickListNumber);
		$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
		
		$pickList_row = $pickList_qry->fetch( PDO::FETCH_ASSOC );
		$order = new order($pickList_row["BelegID"]);
		return $order;
	}	

	// Pickliste Status setzen
	public function setPickStatus($status) {

		if ((isset($this->orderList)) and (count($this->orderList) == 0)) {
			$pickList_sql  = 'update pickliste set pickStatus = :pickStatus where pickId = :pickId and pickStatus = 1';
			  
			$pickList_qry = $this->my_pdo->prepare($pickList_sql);
			$pickList_qry->bindValue(':pickId', $this->pickListNumber);
			$pickList_qry->bindValue(':pickStatus', $status);
			$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
			
			return true;
		}
	}	

	private function checkInvoice($orderId,$orderDate) {
		
		$payment_sql = "select fnum from archiv.auftr_pos p5
						 where fdtm >= :fdtm and fxnr between 100000 and 100100 and flsn = :flsn and ftyp = 5
						 and fblg not in (select fblg from archiv.auftr_pos p6 where fdtm >= :fdtm and p6.fpid=p5.fpid and ftyp = 6)
						limit 1";
		$payment_qry = $this->pg_pdo->prepare($payment_sql);
		$payment_qry->bindValue(':flsn', $orderId);
		$payment_qry->bindValue(':fdtm', $orderDate);
		$payment_qry->execute() or die (print_r($payment_qry->errorInfo()));
		
		$payment_row = $payment_qry->fetch( PDO::FETCH_ASSOC );
		
		if ($payment_row["fnum"] > 0) {
			$inv_sql = "update versand set invoiceId = :invoiceId where BelegID = :BelegID";
			$inv_qry = $this->my_pdo->prepare($inv_sql);
			$inv_qry->bindValue(':invoiceId', $payment_row["fnum"]);
			$inv_qry->bindValue(':BelegID', $orderId);
			$inv_qry->execute() or die (print_r($inv_qry->errorInfo()));
			
			return $payment_row["fnum"];
			
		} else {
			
			print $orderId.' keine Rechnung!<br>';
			return false;
		}
	}

	
}
?>