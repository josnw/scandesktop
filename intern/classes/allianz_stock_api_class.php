<?php

class allianz_stock_api {
	
	private $client;
	private $my_pdo;
		
	public function __construct() {
		
		include 'intern/config.php';
		
		$this->client = new RestApiClient($allianz_stock_url, $allianz_stock_user, $allianz_stock_key); 
		$this->my_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		
	}
	
	public function getStock($stockId, $from_datetime = NULL) {
		
		//get last date from foreign allianz stock
		if ($from_datetime == NULL) {
			$sql_timestamp = "select max(qedt) as date from art_best where algo like 'Filiale%'";
			$time_qry = $this->my_pdo->prepare($sql_timestamp);
			$time_qry->execute() or die(print $time_qry->errorInfo()[2]);
			$stockrow = $time_qry->fetch( PDO::FETCH_ASSOC );
			$from_datetime = $stockrow["date"];
		}

		$result = $this->client->get('Stock/', [ "stockid" => $stockId , "datetime" => date("Y-m-d H:i:s", strtotime($from_datetime))] );

		return $result;
	    
	}

	public function getPrice($priceGroup, $from_datetime) {
		
		//get last date from foreign allianz stock
		if ($from_datetime == NULL) {
			$sql_timestamp = "select max(qedt) as date from art_lief where linr = :linr";
			$time_qry = $this->my_pdo->prepare($sql_timestamp);
			$time_qry->execute() or die(print $time_qry->errorInfo()[2]);
			$stockrow = $time_qry->fetch( PDO::FETCH_ASSOC );
			$from_datetime = $stockrow["date"];
		}
		
		$result = $this->client->get('Stock/', [ "stockid" => $stockId , "datetime" => date("Y-m-d H:i:s", strtotime($from_datetime))] );

		return $result;
	    
	    
	}

	
}

?>