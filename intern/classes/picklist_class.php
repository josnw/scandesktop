<?php

class picklist {
		
	private $belegId;
	private $pg_pdo;
	private $fbkz;
	
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

	public function __construct($Id, $count = NULL, $maxWeight = NULL, $name = NULL, $minWeight = 0) {
		
		include ("./intern/config.php");
		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		$this->wwsPickBelegKz = $wwsPickBelegKz;
		
		if (isset($count) and isset($maxWeight) and isset($name)) {
			$this->createPickList($Id, $count, $maxWeight, $name, $minWeight );
		} else {
			$this->getPickList($Id);
		}
	}
	
	// Pickliste erzeugen
	private function createPickList($userId, $count = 20, $maxWeight = 99999, $name = NULL, $minWeight = 0) {
		$this->pickUser = $userId;
		$count = preg_replace("[^0-9]","",$count);

		
		//Artikel der älteste Bestellungen und TopArtikel einlesen
		$picArt_sql  = 'select p.arnr, min(ks.fdtm) as minDate , count(*) as ArtAnz from auftr_kopf ks 
							inner join auftr_pos p using (fblg) 
							inner join art_0 a1 using (arnr)
						where ks.ftyp = 2 and coalesce(fmgl,0) < fmge  
				    		and coalesce(a1.agew,0) < :maxWeight 
				    		and coalesce(a1.agew,0) >= :minWeight 
							and fbkz = :BelegKz and ks.fprn is null
				  		group by p.arnr
				  		order by minDate, ArtAnz desc limit :limit';

		// Orderliste zusammenstellen
		$picOrder_sql  = 'select k.fnum, k.fblg, max(coalesce(a.agew,0)) as maxGewicht, min(minDate) as minDate, max(ArtAnz) as ArtAnz 
						  from auftr_kopf k 
							inner join auftr_pos p using (fblg) 
							inner join art_0 a using (arnr)
							inner join ( '.$picArt_sql.' ) c using (arnr)
						  where k.ftyp = 2 and coalesce(fmgl,0) < fmge  
						    		and coalesce(a.agew,0) >= :minWeight 
								    and coalesce(a.agew,0) < :maxWeight
									and fbkz = :BelegKz and k.fprn is null
						  group by k.fnum, k.fblg
						  having max(coalesce(a.agew,0)) < :maxWeight
				    		 and max(coalesce(a.agew,0)) >= :minWeight 
						  order by minDate, ArtAnz desc limit :limit';
		$picOrder_qry = $this->pg_pdo->prepare($picOrder_sql);
		$picOrder_qry->bindValue(':limit', $count);
		$picOrder_qry->bindValue(':maxWeight', $maxWeight);
		$picOrder_qry->bindValue(':minWeight', $minWeight);

		$picOrder_qry->bindValue(':BelegKz', $this->wwsPickBelegKz);
		$picOrder_qry->execute() or die (print_r($picOrder_qry->errorInfo()));
		$OrderListStr = '';
		$this->OrderCount = 0;
		 while($picOrder_row = $picOrder_qry->fetch( PDO::FETCH_ASSOC )) {
			if (strlen($OrderListStr) > 0) { $OrderListStr .= 	 ","; }

			// if ($this->checkInvoice($picOrder_row["fblg"],$picOrder_row["minDate"]) ) {
				$OrderListStr .= 	preg_replace("[^A-Z0-9]","?",$picOrder_row["fblg"]);
				$this->OrderCount++;
			// }
		}

		if ($this->OrderCount > 0) {
    		// Pickliste anlegen  = Produktionsauftragsnummer in Facto
    		if ($name == NULL) {
    			$this->pickName =  $_SESSION["uid"]."-".date("m-d h:i");
    		} else {
    			$this->pickName = $name;
    		}
    		$this->pickCreateDate = time();
    		$this->pickStatus = 0;
    		$this->pickLastEdit = time();
    		/*
    		 * TODO Sequenz for fprn
    		 */
    		$this->pickListNumber = $_SESSION["uid"].date("mdhi");
    		$pickList_sql = "update auftr_kopf set fprn = :pickId, fenr = :pickUser, ktou = :pickName, ktos = :pickStatus 
    							where fblg in (".$OrderListStr.")";
    		$pickList_qry2 = $this->pg_pdo->prepare($pickList_sql);
    		$pickList_qry2->bindValue(':pickId', $this->pickListNumber);
    		$pickList_qry2->bindValue(':pickUser', $_SESSION["uid"]);
    		$pickList_qry2->bindValue(':pickName', $this->pickName );
    		$pickList_qry2->bindValue(':pickStatus', $this->pickStatus );
    		$pickList_qry2->execute() or die (print_r($pickList_qry2->errorInfo()));
		} else {
		    print "Keine Aufträge mit diesen Kriterien offen!";
		}
	}
	
	// PickList Header aus DB lesen
	private function getPickList($pickId) {
		
		$pickList_sql = "select fenr,ktou,ktos from auftr_kopf where fprn = :pickId and ftyp = 2";
		
		$pickList_qry = $this->pg_pdo->prepare($pickList_sql);
		$pickList_qry->bindValue(':pickId', $pickId );
		$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
		$pickList_row = $pickList_qry->fetch( PDO::FETCH_ASSOC );
		$this->pickListNumber = $pickId;
		$this->pickUser = $pickList_row["fenr"];
		$this->pickName = $pickList_row["ktou"];
		$this->pickStatus = 0;
	}
	
	// Artikeldaten der Pickliste ausgeben (EInzelartikel, Stücklisten aufgelöst)
	public function getItemList() {

		if ((isset($this->itemList)) and (count($this->itemList) > 0)) {
			return $this->itemList;
		}
		/*
		 * TODO ALAG Tabelle suchen
		 */
		$pickList_sql  = "select a.arnr, p.asco, abz1, abz2, sum(coalesce(fmge,0)-coalesce(fmgl,0)) as fmge, a.ameh, alag
                          from auftr_kopf k
                             inner join auftr_pos p using (fblg) 
                             left join art_0 a using (arnr) 
                             left join art_0fil af  on af.arnr = a.arnr and af.ifnr = p.ifnr 
                             left join art_ean e on a.arnr = e.arnr and e.qskz = 1  
                          where k.fprn = :pickId  and coalesce(aart,0) <> 2  and coalesce(avsd,0) = 0
                          group by a.arnr, abz1, abz2, a.ameh, p.asco, af.alag
                          having  sum(coalesce(fmge,0)-coalesce(fmgl,0)) > 0 
                          order by alag, a.arnr";
		  
		$pickList_qry = $this->pg_pdo->prepare($pickList_sql);
		$pickList_qry->bindValue(':pickId', $this->pickListNumber);
		$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
		
		$this->itemList = $pickList_qry->fetchAll( PDO::FETCH_ASSOC );

		return $this->itemList;
	}

	// Bestellungen der Pickliste ausgeben (Übersicht, keine Artikel)
	public function getOrderList($status) {

		if ((isset($this->orderList)) and (count($this->orderList) > 0)) {
			return $this->orderList;
		} 
		
		$pickStatus = preg_replace("[^0-9,]","",$status);
		$pickList_sql  = 'select fnum, fblg, ffbg, qna1, qna2, qstr, qplz, qort, fdtm, sges, ktou, ktos 
                          from auftr_kopf 
                          where fprn = :pickId 
                          order by fdtm, fnum';
		  
		$pickList_qry = $this->pg_pdo->prepare($pickList_sql);
		$pickList_qry->bindValue(':pickId', $this->pickListNumber);
		$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
		
		$this->orderList = $pickList_qry->fetchAll( PDO::FETCH_ASSOC );
	
		return $this->orderList;
	}	

	// n�chste offene Einzelbestellungen der Pickliste ausgeben)
	public function getNextPackOrder($sort1 = "age") {

		switch($sort1) {
			case("weight"):
				$pickList_sql  = 'select fnum, fblg, sum(agew) as sgew
                           from auftr_kopf k
                           inner join auftr_pos p using (fblg, fnum)
                          where k.fprn = :pickId and k.ktos <= 2 and ftyp = 2
                          group by fnum, fblg
                          order by sgew desc limit 1';
				break;
			
			case("rank"):
				$pickList_sql  = 'select fnum, fblg, sum(agew) as sgew, max(cnt) as cnt
                           from auftr_kopf k
                           inner join auftr_pos p using (fblg, fnum)
						   left join (select arnr, count(fmgb) as cnt from auftr_kopf k1
			                           inner join auftr_pos p1 using (fblg, fnum)
										where k1.fprn = :pickId and k1.ktos <= 2 and ftyp = 2
			                           group by arnr) a using (arnr) 
                          where k.fprn = :pickId and k.ktos <= 2 
                          group by fnum, fblg
                          order by cnt desc,k.fdtm desc limit 1';
				break;
			default:
				$pickList_sql  = 'select fnum, fblg, sum(agew) as sgew
                           from auftr_kopf k
                           inner join auftr_pos p using (fblg, fnum)
                          where k.fprn = :pickId and k.ktos <= 2 and ftyp = 2
                          group by fnum, fblg
                          order by k.fdtm desc limit 1';
				break;
				
		}


		$pickList_qry = $this->pg_pdo->prepare($pickList_sql);
		$pickList_qry->bindValue(':pickId', $this->pickListNumber);

		$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
		
		$pickList_row = $pickList_qry->fetch( PDO::FETCH_ASSOC );
		
		$order = new order($pickList_row["fblg"]);
		return $order;
	}	
	
	public function removeFromPickList($BelegId) {
	    
	    $pickList_sql  = 'update auftr_kopf set fprn = null, ktou = null, ktos = null where fblg = :BelegId';

	    $pickList_qry = $this->pg_pdo->prepare($pickList_sql);
	    $pickList_qry->bindValue(':BelegId', $BelegId);
	    $pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
	    
	    return true;
	    
	}	

	// Pickliste Status setzen
	public function setPickStatus($status) {

		if ((isset($this->orderList)) and (count($this->orderList) == 0)) {
			$pickList_sql  = 'update auftr_kopf set ktos = :pickStatus where fprn = :pickId';
			  
			$pickList_qry = $this->pg_pdo->prepare($pickList_sql);
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
			$inv_qry = $this->pg_pdo->prepare($inv_sql);
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