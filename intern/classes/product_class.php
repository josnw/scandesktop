<?php

class product {
		
	private $productId;
	private $my_pdo;
	private $pg_pdo;
	
	private $poductData;
	private $resultCount;
	private $productGtin;
	private $productParameter;
	private $productPrices;
	private $productPictures;
	private $productDataTradeByteFormat;
	
	// Artikeldaten einlesen
	public function __construct($indexvalue, $level = 'basic') {

		include ("./intern/config.php");

		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		if ($level == 'basic') {
			$fqry  = "select a.arnr as arnr, abz1, abz2, abz3, qgrp, apjs, linr, asco, a.apkz, a.amgz, a.amgn, 
			          case when a.amgn > 0 then cast((a.amgz/a.amgn) as decimal(8,2)) else 1 end as amgm, a.ameh, a.ageh
					  from art_index i inner join art_0 a using(arnr) inner join art_txt t on t.arnr = a.arnr and t.qscd = 'DEU' and t.xxak = '' and t.xyak =''
						left join art_ean e on a.arnr = e.arnr and e.qskz = 1
						where i.aamr = :aamr ";
		} elseif ($level == 'tradebyte') {
			// ,
			//		   coalesce( ( select qurl from art_liefdok ld where ld.arnr = a.arnr and adtp = 91701 and qbez ~ 'prim..r' order by qvon desc limit 1 ),  
 			//		             ( select qurl from art_liefdok ld where ld.arnr = a.arnr and adtp = 91701 order by qvon desc limit 1 ) ) as qurl
			
			$fqry  = "select distinct a.arnr as arnr, abz1, abz2, abz3, abz4, a.qgrp, a.linr, asco, abst, l.qsbz as lqsbz, ameg, 
						case when adgz > 0 then cast( (adgn/adgz) as decimal(8,2)) else null end as agpf,
					  ( select qpvl from art_param p where p.arnr = a.arnr and qpky = 'Marke' limit 1 ) as amrk,
					  ( select string_agg( qpvl , ' ') from art_param p where p.arnr = a.arnr and qpky like '%text%' ) as atxt
					from art_0 a  inner join art_txt t on t.arnr = a.arnr and t.qscd = 'DEU' and t.xxak = '' and t.xyak =''
						left join art_ean e on a.arnr = e.arnr and e.qskz = 1
						left join art_lief al on a.arnr = al.arnr and a.linr = al.linr
						left join lif_0 l on a.linr = l.linr
						where a.arnr = :aamr ";
		}
		$f_qry = $this->pg_pdo->prepare($fqry);
		$f_qry->bindValue(':aamr',$indexvalue);
		$f_qry->execute() or die (print_r($f_qry->errorInfo()));

		$frow = $f_qry->fetchAll( PDO::FETCH_ASSOC );
		
		$this->productData = $frow;
		$this->resultCount = count($frow);

		if ($this->resultCount == 1) {
			$this->productId = $frow[0]["arnr"];
			$this->productGtin = $frow[0]["asco"];

			// convert to TB Panda format
			if ($level == 'tradebyte') {
				$this->productDataTradeByteFormat = [
					'p_nr' =>  $frow[0]["arnr"],
					'a_nr' =>  $frow[0]["arnr"],
					'a_prodnr' =>  $frow[0]["abst"],
					'a_ean' =>  $frow[0]["asco"],
					'p_text' =>  $frow[0]["atxt"],
					'p_name_keyword' =>  $frow[0]["abz1"].' '.$frow[0]["abz2"].' '.$frow[0]["abz3"],
					'p_name_proper' =>  $frow[0]["abz4"],
					//'a_media[IMAGE]{0}' =>  $frow[0]["qurl"],
					];
					
					if ( (isset($frow[0]["ameg"])) and (strlen($frow[0]["ameg"]) > 1) ) {
						$this->productDataTradeByteFormat['a_base_price['.$frow[0]["ameg"].']'] =  $frow[0]["agpf"];
		
					}

					
					if ( (isset($frow[0]["amrk"])) and (strlen($frow[0]["amrk"]) > 1) ) {
						$this->productDataTradeByteFormat['p_brand'] = $frow[0]["amrk"];
					} else {
						$this->productDataTradeByteFormat['p_brand'] = $frow[0]["lqsbz"];
					}	
					$this->productDataTradeByteFormat['p_prefix'] = $this->productDataTradeByteFormat['p_brand'];
			} else {
				$this->productDataTradeByteFormat = [
					'a_nr' =>  $frow[0]["arnr"],
				];
			}


		} else {
			$this->productId = NULL;
			$this->productDataTradeByteFormat = [
				'a_nr' =>  NULL,
			];
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
	
	private function getParameterFromDB() {
		
		$fqry  = "select * from art_param where arnr = :aamr and qpky not like '%text%' order by qpky";
		
		$f_qry = $this->pg_pdo->prepare($fqry);
		$f_qry->bindValue(':aamr',$this->productId);
		$f_qry->execute() or die (print_r($f_qry->errorInfo()));

		$this->productParameter = [] ;
		while ($row = $f_qry->fetch( PDO::FETCH_ASSOC ) ) {

				$this->productParameter[$row["qpky"]] = $row["qpvl"];
		}
	}
	
	public function getParameter() {
			if (! isset($this->productParameter) or $this->productParameter == NULL ) {
					$this->getParameterFromDB();
			}
			return $this->productParameter;
	}	
	
	private function getPricesFromDB() {

		$this->productPrices = [] ;
		$standardPrice = null;
		
		// sql check pricekey list
		$priceqry  = "select qbez from mand_prsbas where mprb > 6";	
		$price_qry = $this->pg_pdo->prepare($priceqry);
		$price_qry->execute() or die (print_r($price_qry->errorInfo()));
		while ($row = $price_qry->fetch( PDO::FETCH_ASSOC ) ) {
			$this->productPrices[$row["qbez"]] = null;
		}
		
		// select prices
		$fqry  = "select mprb, pb.qbez as mprn, cprs, c.apjs , case when a.amgn > 0 then cast((a.amgz/a.amgn) as decimal(8,2)) else 1 end as amgm
					from cond_vk c inner join mand_prsbas pb using (mprb)  inner join art_0 a using (arnr,ameh) 
		          where arnr = :aamr and cbez = 'PR01' and mprb >= 6 and qvon < current_date and qbis > current_date order by csog, qdtm";
		
		$f_qry = $this->pg_pdo->prepare($fqry);

		$f_qry->bindValue(':aamr',$this->productId);
		$f_qry->execute() or die (print_r($f_qry->errorInfo()));
		
		// calulate price for one 
		while ($row = $f_qry->fetch( PDO::FETCH_ASSOC ) ) {
				if (isset($row["apjs"]) and ($row["apjs"] > 0)) { 
					$this->productPrices[$row["mprn"]] = round(($row["cprs"]/$row["apjs"]*$row["amgm"]),2);
				} else {
					$this->productPrices[$row["mprn"]] = round(($row["cprs"]*$row["amgm"]),2);
				}
				if ($row["mprb"] == 6) {
					$standardPrice = $this->productPrices[$row["mprn"]];
					//no export for standard price
					unset($this->productPrices[$row["mprn"]]);
				}
		}
		
		// fill zero price with standard price
		foreach($this->productPrices as $key => $value) {
			if ($value == null) {
				$this->productPrices[$key] = $standardPrice;
			}
		}
		
	}
		
	public function getPrices() {
			if (! isset($this->productPrices) or $this->productPrices == NULL ) {
					$this->getPricesFromDB();
			}
			return $this->productPrices;
	}	

	private function getPicturesFromDB() {
		
		$fqry  = "select qbez, qurl from art_liefdok d where adtp = 91701 and arnr = :aamr order by qbez, qadt";
		
		$f_qry = $this->pg_pdo->prepare($fqry);
		$f_qry->bindValue(':aamr',$this->productId);
		$f_qry->execute() or die (print_r($f_qry->errorInfo()));

		$this->productPictures = [] ;
		$typeCounter = [];
		while ($row = $f_qry->fetch( PDO::FETCH_ASSOC ) ) {
			$row["qbez"] = preg_replace("(MDB |\(|\))","",$row["qbez"]);
			if ( !isset($typeCounter[$row["qbez"]])) {
				$typeCounter[$row["qbez"]] = 0;
			} else {
				$typeCounter[$row["qbez"]]++;
			}
			$this->productPictures[$row["qbez"]][$typeCounter[$row["qbez"]]] = $row["qurl"];
		}
	}
	
	public function getPictures() {
			if (! isset($this->productPictures) or $this->productPictures == NULL ) {
					$this->getPicturesFromDB();
			}
			return $this->productPictures;
	}	
	
	private function getStocksFromDB() {
		
		$fqry  = "select ifnr, case when a.amgz > 0 then cast((amge*a.amgn/a.amgz) as decimal(8,2)) else amge end as amgb 
					from art_0 a inner join art_best b using (arnr) where arnr = :aamr order by ifnr";
		
		$f_qry = $this->pg_pdo->prepare($fqry);
		$f_qry->bindValue(':aamr',$this->productId);
		$f_qry->execute() or die (print_r($f_qry->errorInfo()));

		$this->productStocks = [] ;

		while ($row = $f_qry->fetch( PDO::FETCH_ASSOC ) ) {
			if ($row["amgb"] == null) {
				$row["amgb"] = 0;
			}
			$this->productStocks[$row["ifnr"]] = $row["amgb"];
			
		}
	}
	
	public function getStocks() {
			if (! isset($this->productStocks) or $this->productStocks == NULL ) {
					$this->getStocksFromDB();
			}
			return $this->productStocks;
	}	
	
	public function getTradebyteFormat($arrayName = "p_comp", $parameter = NULL) {
			if ((! isset($parameter) or $parameter == NULL ) and ($arrayName == "p_comp")) {
					$this->getParameterFromDB();
					$parameter =  $this->productParameter;
			} elseif ((! isset($parameter) or $parameter == NULL ) and ($arrayName == "a_vk")) {
					$this->getPricesFromDB();
					$parameter = $this->productPrices;
			} elseif ((! isset($parameter) or $parameter == NULL ) and ($arrayName == "basedata")) {
					return $this->productDataTradeByteFormat;
			} elseif ((! isset($parameter) or $parameter == NULL ) and ($arrayName == "a_media")) {
					$parameter = $this->getPictures() ;
			} elseif ((! isset($parameter) or $parameter == NULL ) and ($arrayName == "a_stock")) {
					$parameter = $this->getStocks() ;
			} 
			
			$TbParam = [];
			
			foreach($parameter as $key => $value) {
				if (is_array($value )) {
					foreach($value as $subkey => $subvalue) {
						$TbParam[$arrayName."[".$key."]{".$subkey."}"] = $subvalue;
					}
				} else {
					$TbParam[$arrayName."[".$key."]"] = $value;
				}
			}
			
			return $TbParam;
	}	


	
}

?>