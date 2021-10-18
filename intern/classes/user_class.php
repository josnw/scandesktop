<?php

class user {

	private $pg_pdo;
	private $pickBelegKz;
	
	public $pickUser;
	
	public function __construct($userId) {

		include './intern/config.php';

		$this->pickUser = $userId;
		$this->pickBelegKz = $wwsPickBelegKz;
		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		
		$pickInit_sql = "update auftr_kopf set ktos = 0 where ktos is null  and fbkz = :fbkz";
		
		$pickInit_qry = $this->pg_pdo->prepare($pickInit_sql);
		$pickInit_qry->bindValue(":fbkz",$this->pickBelegKz);
		
		$pickInit_qry->execute() or die (print_r($pickInit_qry->errorInfo()));
		
		
	}
	
	public function getPickLists($status = '0') {
		$this->checkPickStatus();
		$liste = [];
		//Artikel der �lteste Bestellungen und TopArtikel einlesen
		$pickStatus = preg_replace("[^0-9,]","",$status);
		$pickList_sql = 'select fprn , ktou, max(ktos) as ktos from auftr_kopf 
                            where fenr = :pickUser and ktos in ('.$pickStatus.') and fprn is not null
                            group by fprn, ktou
                            order by fprn';

		$pickList_qry = $this->pg_pdo->prepare($pickList_sql);
		$pickList_qry->bindValue(':pickUser', $this->pickUser );
		
		$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
		
		$liste = $pickList_qry->fetchAll( PDO::FETCH_ASSOC);
		
		return $liste;
	}
	
	public function getOrderCount($status = '0') {
		$this->checkPickStatus();
		$liste = [];
		//Artikel der �lteste Bestellungen und TopArtikel einlesen
		$pickStatus = preg_replace("[^0-9,]","",$status);
		$pickList_sql = 'select count(fblg) as cnt  from auftr_kopf where fenr = :pickUser and ktos in ('.$pickStatus.')  and fbkz = :fbkz and ftyp = 2 and fprn is not null';

		$pickList_qry = $this->pg_pdo->prepare($pickList_sql);
		$pickList_qry->bindValue(':pickUser', $this->pickUser );
		$pickList_qry->bindValue(":fbkz",$this->pickBelegKz);
		$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
		
		$pickList_row = $pickList_qry->fetch( PDO::FETCH_ASSOC );
		return $pickList_row["cnt"];
	}

	public function checkPickStatus() {
        return true;
		//Alle Picklisten ohne ungepackte Bestellungen auf Status 1
		$pickList_sql = 'update auftr_kopf set ktos = 1 where fenr = :pickUser and ktos = 0 and fprn is not null  and ftyp = 2';

		$pickList_qry = $this->my_pdo->prepare($pickList_sql);
		$pickList_qry->bindValue(':pickUser', $this->pickUser );
		
		$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
		
		$pickList_row = $pickList_qry->fetch( PDO::FETCH_ASSOC );
		return $pickList_row["cnt"];
	}

	public function getAllOrderCount($status = 0) {
		$this->checkPickStatus();
		$liste = NULL;
		//Artikel der �lteste Bestellungen und TopArtikel einlesen
		$pickStatus = preg_replace("[^0-9,]","",$status);

		$pickList_sql = 'select count(fblg) as cnt from auftr_kopf where ktos in ('.$pickStatus.') and fbkz = :fbkz  and ftyp = 2 ';
		
		$pickList_qry = $this->pg_pdo->prepare($pickList_sql);
		$pickList_qry->bindValue(":fbkz",$this->pickBelegKz);
		
		$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
		
		$pickList_row = $pickList_qry->fetch( PDO::FETCH_ASSOC );
		return $pickList_row["cnt"];
	}
	
	public function getAllStat() {
	    $info = [];
	    
	    $pickList_sql = 'select count(fblg) as cntAllDay from auftr_kopf where  fbkz = :fbkz  and ftyp = 2 and fdtm <= current_date '; 
	    $pickList_qry = $this->pg_pdo->prepare($pickList_sql);
	    $pickList_qry->bindValue(":fbkz",$this->pickBelegKz);
	    $pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
	    $fetch = $pickList_qry->fetch( PDO::FETCH_ASSOC );
	    $info['allToday'] = $fetch["cntallday"];
	    
	    $pickList_sql = 'select count(fblg) as cntDoneDay from auftr_kopf where  fbkz = :fbkz  and ftyp = 2 and fdtm <= current_date and ktos = 2 ';
	    $pickList_qry = $this->pg_pdo->prepare($pickList_sql);
	    $pickList_qry->bindValue(":fbkz",$this->pickBelegKz);
	    $pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
	    $fetch = $pickList_qry->fetch( PDO::FETCH_ASSOC );
	    $info['doneToday'] = $fetch["cntdoneday"];
	    
	    $info['quoteToday'] = $info['doneToday'] / $info['allToday'];
	    $info['openToday'] = $info['allToday'] - $info['doneToday'];
	    
/*	    $pickList_qry = $this->pg_pdo->prepare($pickList_sql);
	    $pickList_qry->bindValue(":fbkz",$this->pickBelegKz);
	    $pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
	    $info['byPackStat'] = $pickList_qry->fetchAll( PDO::FETCH_ASSOC );
	    */
	    $pickList_sql = 'select ktos,count(fblg) as cnt from auftr_kopf where  fbkz = :fbkz  and ftyp = 2 group by ktos';
	    $pickList_qry = $this->pg_pdo->prepare($pickList_sql);
	    $pickList_qry->bindValue(":fbkz",$this->pickBelegKz);
	    $pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
	    $info['byPackStat'] = $pickList_qry->fetchAll( PDO::FETCH_ASSOC );

	    $pickList_sql = 'select fenr, p.qna1,count(fblg) as cnt from auftr_kopf k
                         inner join per_0 p on p.penr = k.fenr 
                         where  fbkz = :fbkz  and ftyp = 2 and coalesce(ktos,0) < 2 and fenr > 0 and fprn > 0 group by fenr, p.qna1';
	    $pickList_qry = $this->pg_pdo->prepare($pickList_sql);
	    $pickList_qry->bindValue(":fbkz",$this->pickBelegKz);
	    $pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
	    $info['byUser'] = $pickList_qry->fetchAll( PDO::FETCH_ASSOC );

	    $pickList_sql = 'select fdtm::date,count(fblg) as cnt from auftr_kopf 
                         where  fbkz = :fbkz  and ftyp = 2 and coalesce(ktos,0) < 2 group by fdtm';
	    $pickList_qry = $this->pg_pdo->prepare($pickList_sql);
	    $pickList_qry->bindValue(":fbkz",$this->pickBelegKz);
	    $pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
	    $info['byDate'] = $pickList_qry->fetchAll( PDO::FETCH_ASSOC );
	    
	    return $info;
	
	}
}
?>