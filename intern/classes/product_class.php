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
	private $productDataTradeByteFormat;
	
	// Artikeldaten einlesen
	public function __construct($indexvalue, $level = 'basic') {

		include ("./intern/config.php");

		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		if ($level == 'basic') {
			$fqry  = "select a.arnr as arnr, abz1, abz2, abz3, qgrp, linr, asco 
					  from art_index i inner join art_0 a using(arnr) inner join art_txt t on t.arnr = a.arnr and t.qscd = 'DEU' and t.xxak = '' and t.xyak =''
						left join art_ean e on a.arnr = e.arnr and e.qskz = 1
						where i.aamr = :aamr ";
		} elseif ($level == 'tradebyte') {
			$fqry  = "select distinct a.arnr as arnr, abz1, abz2, abz3, abz4, a.qgrp, a.linr, asco, abst, l.qsbz as lqsbz, ameg, 
						case when adgz > 0 then cast( (adgn/adgz) as decimal(8,2)) else null end as agpf,
					  ( select qpvl from art_param p where p.arnr = a.arnr and qpky = 'Marke' limit 1 ) as amrk,
					  ( select string_agg( qpvl , ' ') from art_param p where p.arnr = a.arnr and qpky like '%text%' ) as atxt,
					   coalesce( ( select qurl from art_liefdok ld where ld.arnr = a.arnr and adtp = 91701 and qbez ~ 'prim..r' order by qvon desc limit 1 ),  
 					             ( select qurl from art_liefdok ld where ld.arnr = a.arnr and adtp = 91701 order by qvon desc limit 1 ) ) as qurl
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
		} else {
			$this->productId = NULL;
		}

		// convert to TB Panda format
		if ($level == 'tradebyte') {
			$this->productDataTradeByteFormat = [
				'p_nr' =>  $frow[0]["arnr"],
				'a_nr' =>  $frow[0]["arnr"],
				'a_prodnr' =>  $frow[0]["abst"],
				'a_ean' =>  $frow[0]["asco"],
				'p_text' =>  $frow[0]["atxt"],
				'p_name_keyword' =>  $frow[0]["abz1"].' '.$frow[0]["abz2"].' '.$frow[0]["abz3"],
				'p_name_propper' =>  $frow[0]["abz4"],
				'a_media[IMAGE]{0}' =>  $frow[0]["qurl"],
				'a_base_price['.$frow[0]["ameg"].']' =>  $frow[0]["agpf"],
				];
				
				if ( (isset($frow[0]["amrk"])) and (strlen($frow[0]["amrk"]) > 1) ) {
					$this->productDataTradeByteFormat['p_brand'] = $frow[0]["amrk"];
				} else {
					$this->productDataTradeByteFormat['p_brand'] = $frow[0]["lqsbz"];
				}	
				$this->productDataTradeByteFormat['p_prefix'] = $this->productDataTradeByteFormat['p_brand'];
				
				
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
		
		$fqry  = "select mprb, pb.qbez as mprn, cprs, c.apjs from cond_vk c inner join mand_prsbas pb using (mprb)  inner join art_0 a using (arnr,ameh) 
		          where arnr = :aamr and cbez = 'PR01' and mprb > 6 and qvon < current_date and qbis > current_date ";
		
		$f_qry = $this->pg_pdo->prepare($fqry);

		$f_qry->bindValue(':aamr',$this->productId);
		$f_qry->execute() or die (print_r($f_qry->errorInfo()));
		
		$this->productPrices = [] ;
		
		while ($row = $f_qry->fetch( PDO::FETCH_ASSOC ) ) {
				if (isset($row["apjs"]) and ($row["apjs"] > 0)) { 
					$this->productPrices[$row["mprn"]] = ($row["cprs"]/$row["apjs"]);
				} else {
					$this->productPrices[$row["mprn"]] = ($row["cprs"]);
				}
		}
	}
	
	
	public function getPrices() {
			if (! isset($this->productPrices) or $this->productPrices == NULL ) {
					$this->getPricesFromDB();
			}
			return $this->productPrices;
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
			}

			
			$TbParam = [];
			
			foreach($parameter as $key => $value) {
				$TbParam[$arrayName."[".$key."]"] = $value;
			}
			
			return $TbParam;
	}	


	
}

?>