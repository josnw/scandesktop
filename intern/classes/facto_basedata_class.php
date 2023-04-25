<?php

class factoBaseData {
		
	private $pg_pdo;
	
	private $ShopwareWebshopNumber;
	private $paramArray = [];
	private $filterArray = [];
	
	public function __construct() {

		include ("./intern/config.php");
		$this->ShopwareWebshopNumber = $shopware6WebshopNumber;
		$this->shopware6PropertyFile = $sw6PropertyFile;

		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		
		if (file_exists($this->shopware6PropertyFile)) {
			$this->filterArray = json_decode(file_get_contents($this->shopware6PropertyFile),true);
		} else {
			$this->filterArray = [];
		}

	}
	
	public function getProductParam() {
		
		$p_sql = "select qpky, count(*) cnt from art_param p 
				inner join web_art w using (arnr) where wson = 1 and wsnr = :wsnr and wsdt is not null 
				group by qpky
				order by qpky";

		$p_qry = $this->pg_pdo->prepare($p_sql);
		$p_qry->bindValue(':wsnr',$this->ShopwareWebshopNumber);
		
		$p_qry->execute() or die (print_r($p_qry->errorInfo()));
		
		$dbarray = [];
		if (file_exists($this->shopware6PropertyFile)) {
			$this->filterArray = json_decode(file_get_contents($this->shopware6PropertyFile),true);
		} else {
			$this->filterArray = [];
		}
		
		while( $param = $p_qry->fetch( PDO::FETCH_ASSOC )) {
			$this->paramArray[$param["qpky"]]["name"] = $param["qpky"];
			$this->paramArray[$param["qpky"]]["cnt"] = $param["cnt"];
			
			if(!empty($this->filterArray[$param["qpky"]]["filter"])) {
				$this->paramArray[$param["qpky"]]["filter"] = true;
			}
		}

		return $this->paramArray;
	}
	
	private function saveProductParam() {
		
		if (!empty($this->filterArray)) {
			file_put_contents($this->shopware6PropertyFile, json_encode($this->filterArray));
			return true;
		} else {
			return false;
		}
	}
	
	public function updateParam($key, $filterState) {
		if ($filterState) {
			$this->filterArray[$key]["filter"] = $filterState;
		} else {
			unset($this->filterArray[$key]);
		}
			$this->saveProductParam();
	} 
	
}