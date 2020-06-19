<?php

class user {

	private $my_pdo;
	private $pg_pdo;
	
	public $pickUser;
	
	public function __construct($userId) {

		include './intern/config.php';

		$this->pickUser = $userId;

		$this->my_pdo = new PDO($ecserver, $ecuser, $ecpass, $options);
		
	}
	
	public function getPickLists($status = '0') {
		$this->checkPickStatus();
		$liste = [];
		//Artikel der älteste Bestellungen und TopArtikel einlesen
		$pickStatus = preg_replace("[^0-9,]","",$status);
		$pickList_sql = 'select * from pickliste where pickUser = :pickUser and pickStatus in ('.$pickStatus.') order by pickId';

		$pickList_qry = $this->my_pdo->prepare($pickList_sql);
		$pickList_qry->bindValue(':pickUser', $this->pickUser );
		
		$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
		
		while($pickList_row = $pickList_qry->fetch( PDO::FETCH_ASSOC )) {
			$liste[] = $pickList_row;
		}
		return $liste;
	}
	
	public function getOrderCount($status = '0') {
		$this->checkPickStatus();
		$liste = [];
		//Artikel der älteste Bestellungen und TopArtikel einlesen
		$pickStatus = preg_replace("[^0-9,]","",$status);
		$pickList_sql = 'select count(BelegID) as cnt from versand where ship_user = :pickUser and ship_status in ('.$pickStatus.') ';

		$pickList_qry = $this->my_pdo->prepare($pickList_sql);
		$pickList_qry->bindValue(':pickUser', $this->pickUser );
		
		$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
		
		$pickList_row = $pickList_qry->fetch( PDO::FETCH_ASSOC );
		return $pickList_row["cnt"];
	}

	public function checkPickStatus() {

		//Alle Picklisten ohne ungepackte Bestellungen auf Status 1
		$pickList_sql = 'update pickliste set pickStatus = 1 where pickUser = :pickUser and pickStatus = 0 
							and pickId not in (select ship_picklist from versand where ship_user = :pickUser and ship_status = 0)';

		$pickList_qry = $this->my_pdo->prepare($pickList_sql);
		$pickList_qry->bindValue(':pickUser', $this->pickUser );
		
		$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
		
		$pickList_row = $pickList_qry->fetch( PDO::FETCH_ASSOC );
		return $pickList_row["cnt"];
	}



	public function getAllOrderCount($status = '0') {
		$this->checkPickStatus();
		$liste = NULL;
		//Artikel der älteste Bestellungen und TopArtikel einlesen
		$pickStatus = preg_replace("[^0-9,]","",$status);
		$pickList_sql = 'select count(BelegID) as cnt from versand where ship_status in ('.$pickStatus.') ';

		$pickList_qry = $this->my_pdo->prepare($pickList_sql);
		
		$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
		
		$pickList_row = $pickList_qry->fetch( PDO::FETCH_ASSOC );
		return $pickList_row["cnt"];
	}
}
?>