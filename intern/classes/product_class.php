<?php

class product {
		
	private $productId;
	private $my_pdo;
	private $pg_pdo;
	
	private $poductData;
	private $resultCount;
	private $productGtin;
	
	// Artikeldaten einlesen
	public function __construct($indexvalue) {

		include ("./intern/config.php");

		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		
		$fqry  = "select a.arnr as arnr, abz1, abz2, abz3, qgrp, linr, asco from art_index i inner join art_0 a using(arnr) inner join art_txt t on t.arnr = a.arnr and t.qscd = 'DEU' and t.xxak = '' and t.xyak =''
		            left join art_ean e on a.arnr = e.arnr and e.qskz = 1
					where i.aamr = :aamr ";
		$f_qry = $this->pg_pdo->prepare($fqry);
		$f_qry->bindValue(':aamr',$indexvalue);
		$f_qry->execute() or die (print_r($f_qry->errorInfo()));

		$frow = $f_qry->fetchAll( PDO::FETCH_ASSOC );
		
		$this->productData = $frow;
		$this->resultCount = count($frow);

		if ($this->resultCount == 1) {
			$this->productId = $frow[0]["arnr"];
			$this->productGtin = $frow[0]["asco"];
		} else {
			$this->productId = NULL;
		}

	}
	
	public function getProductId() {
			return $this->productId;
	}
	
	public function getProductGtin() {
			return $this->productGtin;
	}
		
	public function getResultList() {
			return $this->productData;
	}
	
	public function getResultCount() {
			return $this->resultCount;
	}
	
}

?>