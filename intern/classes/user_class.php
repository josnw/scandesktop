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
}
?>