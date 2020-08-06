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
			$from_datetime = '1999-12-31';
		}

		$result = $this->client->get('Stock/', [ "stockid" => $stockId , "datetime" => date("Y-m-d H:i:s", strtotime($from_datetime))] );

		return $result;
	    
	}

	public function getPrice($priceGroup, $from_datetime) {
		
		//get last date from foreign allianz stock
		if ($from_datetime == NULL) {
			$from_datetime = '1999-12-31';
		}
		$result = $this->client->get('articles/', [ "customerGroupKey" => $priceGroup , "datetime" => date("Y-m-d H:i:s", strtotime($from_datetime))] );
			
		return $result;
	    
	    
	}

	
}

?>