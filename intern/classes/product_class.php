<?php

class product {
		
	private $productId = null;
	private $indexvalue = null;
	private $my_pdo;
	private $pg_pdo;

	
	public $productData;
	private $resultCount;
	private $productGtin;
	private $productParameter;
	private $productPrices;
	private $productAdvertisingPrices;
	private $productPictures;
	private $productDataTradeByteFormat;
	private $productStckListData;
	private $productStocks;
	private $productOrderSum;
	private $clpData = [];
	private $wwsPickBelegKz;
	
	// Artikeldaten einlesen
	public function __construct($indexvalue, $level = 'basic', $searchoptions = []) {

		include ("./intern/config.php");
		
		$this->wwsPickBelegKz = $wwsPickBelegKz;

		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		
		if ($level == 'basic') {
			$this->indexvalue = $indexvalue;
			$fqry  = "select distinct a.arnr as arnr, abz1, abz2, abz3, a.qgrp, apjs, linr, asco, a.apkz, a.amgz, a.amgn,  m.mmss,   
			          case when a.amgn > 0 then cast((a.amgz/a.amgn) as decimal(8,2)) else 1 end as amgm, a.ameh, a.ageh, a.aart,
                      i.askz, a.agew, a.avsd
					  from art_index i inner join art_0 a using(arnr) inner join art_txt t on t.arnr = a.arnr and t.qscd = 'DEU' and t.xxak = '' and t.xyak =''
						left join art_ean e on a.arnr = e.arnr and e.qskz = 1
						left join mand_mwst m on a.apkz = m.mmid
						left join art_grp ag on a.qgrp = ag.qgrp
						where i.aamr = :aamr and i.askz < 8";
			$f_qry = $this->pg_pdo->prepare($fqry);
			$f_qry->bindValue(':aamr',$indexvalue);
		} elseif ($level == 'tradebyte') {
			// ,
			//		   coalesce( ( select qurl from art_liefdok ld where ld.arnr = a.arnr and adtp = 91701 and qbez ~ 'prim..r' order by qvon desc limit 1 ),  
 			//		             ( select qurl from art_liefdok ld where ld.arnr = a.arnr and adtp = 91701 order by qvon desc limit 1 ) ) as qurl
			$this->indexvalue = $indexvalue;
			
			$fqry  = "select distinct a.arnr as arnr, abz1, abz2, abz3, abz4, a.qgrp, a.linr, asco, abst, l.qsbz as lqsbz, ameg,  m.mmss, a.apkz,
						case when adgz > 0 then cast( (adgn/adgz) as decimal(18,8)) else null end as agpf, a.aart, ag.qsbz as gqsbz,
                        case when a.amgn > 0 then cast((a.amgz/a.amgn) as decimal(18,8)) else null end as amgm, a.ameh, a.ageh, apjs,
					  ( select qpvl from art_param p where p.arnr = a.arnr and qpky = 'Marke' limit 1 ) as amrk,
					  ( select string_agg( qpvl , ' ') from art_param p where p.arnr = a.arnr and qpky like '%text%' ) as atxt,
                        0 as askz, a.agew, a.avsd, a.hsnr, h.qsbz as hqsbz
					from art_0 a  inner join art_txt t on t.arnr = a.arnr and t.qscd = 'DEU' and t.xxak = '' and t.xyak =''
						left join art_ean e on a.arnr = e.arnr and e.qskz = 1
						left join art_lief al on a.arnr = al.arnr and a.linr = al.linr
						left join lif_0 l on a.linr = l.linr
						left join her_0 h on a.hsnr = h.hsnr
						left join mand_mwst m on a.apkz = m.mmid
                        left join art_grp ag on a.qgrp = ag.qgrp
						where a.arnr = :aamr ";
			$f_qry = $this->pg_pdo->prepare($fqry);
			$f_qry->bindValue(':aamr',$indexvalue);
		} elseif ($level == 'searchPrice') {
			$fqry  = "select distinct a.arnr as arnr, abz1, abz2, abz3, a.qgrp, a.apjs, a.linr, asco, a.apkz, a.amgz, a.amgn, 
								  case when a.amgn > 0 then cast((a.amgz/a.amgn) as decimal(8,2)) else 1 end as amgm, a.ameh, a.ageh, a.aart,
								  cprs, abs(cprs - :price) as diff, 99 as askz 
								  from cond_vk v inner join art_0 a using(arnr) inner join art_txt t on t.arnr = a.arnr and t.qscd = 'DEU' and t.xxak = '' and t.xyak =''
									left join art_ean e on a.arnr = e.arnr and e.qskz = 1
									where v.arnr between :fromarnr and :toarnr and length(v.arnr) = :len and qbis > current_date and qvon <= current_date and mprb = 6 and csog like 'F%'
									order by diff limit 1";
			$f_qry = $this->pg_pdo->prepare($fqry);
			$f_qry->bindValue(':price',$indexvalue);
			$f_qry->bindValue(':fromarnr',$searchoptions['fromArticle']);
			$f_qry->bindValue(':toarnr',$searchoptions['toArticle']);
			$f_qry->bindValue(':len',strlen($searchoptions['fromArticle']));
		
		}

		$f_qry->execute() or die (print_r($f_qry->errorInfo()));

		$frow = $f_qry->fetchAll( PDO::FETCH_ASSOC );
		
		$this->productData = $frow;
		$this->resultCount = count($frow);

		// if one resulte or index is article number, matrix number or gtin 
		if (($this->resultCount == 1) or ($frow[0]["askz"] <= 2)){
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
		
		$fqry  = "select * from art_param where arnr = :aamr and qpky not like '%text%' and length(qpvl) > 0 order by qpky";
		
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
	
	private function getPricesFromDB( $withStdPrice = false) {

		$this->productPrices = [] ;
		$standardPrice = null;
		
		// sql check pricekey list
		$priceqry  = "select qbez from mand_prsbas where mprb >= 6 and qbez not like 'VK-Preis %'";	
		$price_qry = $this->pg_pdo->prepare($priceqry);
		$price_qry->execute() or die (print_r($price_qry->errorInfo()));
		while ($row = $price_qry->fetch( PDO::FETCH_ASSOC ) ) {
			$this->productPrices[$row["qbez"]] = null;
		}
		
		// select prices
		$fqry  = "select mprb, pb.qbez as mprn, cprs, c.apjs , case when a.amgn > 0 then cast((a.amgz/a.amgn) as decimal(8,2)) else 1 end as amgm
					from cond_vk c inner join mand_prsbas pb using (mprb)  inner join art_0 a using (arnr,ameh) 
		          where arnr = :aamr and cbez = 'PR01' and mprb >= 6 and qvon <= current_date and qbis > current_date 
					and pb.qbez not like 'VK-Preis %'
                    and cprs <> 0
                    order by case when csog like 'E%' then 1 when csog like 'R%' then 2 when csog ~ 'F[0-9]*' then 3 else 99 end, csog, qdtm";
		
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
					//default no export for standard price
					if (! $withStdPrice ) {
						unset($this->productPrices[$row["mprn"]]);
					}
				}
		}
		
		// fill zero price with standard price
		foreach($this->productPrices as $key => $value) {
			if ($value == null) {
				$this->productPrices[$key] = $standardPrice;
			}
		}
		
	}
		
	public function getPrices($withStdPrice = false) {
			if (! isset($this->productPrices) or $this->productPrices == NULL ) {
					$this->getPricesFromDB( $withStdPrice );
			}
			return $this->productPrices;
	}	

	private function getAdvertisingPricesFromDB( $withStdPrice = false) {
		
		$this->productAdvertisingPrices = [] ;
		$standardAdvertisingPrice = null;
		
		// sql check pricekey list
		$priceqry  = "select qbez from mand_prsbas where mprb >= 6 and qbez not like 'VK-Preis %'";
		$price_qry = $this->pg_pdo->prepare($priceqry);
		$price_qry->execute() or die (print_r($price_qry->errorInfo()));
		while ($row = $price_qry->fetch( PDO::FETCH_ASSOC ) ) {
			$this->productAdvertisingPrices[$row["qbez"]] = null;
		}
		
		// select prices
		$fqry  = "select c.mprb, coalesce(pb.qbez,'ALL') as mprn, cprs, c.apjs , case when a.amgn > 0 then cast((a.amgz/a.amgn) as decimal(8,2)) else 1 end as amgm
					from cond_vk c left join mand_prsbas pb using (mprb)  inner join art_0 a using (arnr,ameh)
		          where arnr = :aamr and cbez = 'FPAK' and (mprb >= 6 or c.mprb = 0) and qvon <= current_date and qbis > current_date
					and coalesce(pb.qbez,'ALL') not like 'VK-Preis %' 
                    and cprs <> 0
                    order by case when csog like 'E%' then 1 when csog like 'R%' then 2 when csog ~ 'F[0-9]*' then 3 else 99 end, csog, qdtm
				";
		
		$f_qry = $this->pg_pdo->prepare($fqry);
		
		$f_qry->bindValue(':aamr',$this->productId);
		$f_qry->execute() or die (print_r($f_qry->errorInfo()));
		
		// calulate price for one
		while ($row = $f_qry->fetch( PDO::FETCH_ASSOC ) ) {
			if (isset($row["apjs"]) and ($row["apjs"] > 0)) {
				$this->productAdvertisingPrices[$row["mprn"]] = round(($row["cprs"]/$row["apjs"]*$row["amgm"]),2);
			} else {
				$this->productAdvertisingPrices[$row["mprn"]] = round(($row["cprs"]*$row["amgm"]),2);
			}
			if ($row["mprb"] == 0) {
				$standardAdvertisingPrice = $this->productAdvertisingPrices[$row["mprn"]];
				//default no export for standard price
				if (! $withStdPrice ) {
					unset($this->productAdvertisingPrices[$row["mprn"]]);
				}
			}
		}
		
		// fill zero price with standard price
		foreach($this->productAdvertisingPrices as $key => $value) {
			if ($value == null) {
				$this->productAdvertisingPrices[$key] = $standardAdvertisingPrice;
			}
		}
		
	}
	
	public function getAdvertisingPrices($withStdPrice = false) {
		if (! isset($this->productAdvertisingPrices) or $this->productAdvertisingPrices == NULL ) {
			$this->getAdvertisingPricesFromDB( $withStdPrice );
		}
		return $this->productAdvertisingPrices;
	}	
	
	private function getPicturesFromDB($type, $shopid) {
		
		if (($type == "new") or (empty($shopid))) {
			$fqry  = "select qbez, qurl from art_liefdok d where adtp = 91701 and arnr = :aamr order by qbez, qadt";
			$f_qry = $this->pg_pdo->prepare($fqry);
			$f_qry->bindValue(':aamr',$this->productId);
		} else {
			$fqry  = "select qbez, qurl from art_liefdok d 
						left join web_art w using (arnr) 
						where wsnr = :wsnr and adtp = 91701 and arnr = :aamr and d.qedt > w.wsdt 
						order by qbez, d.qadt";
			$f_qry = $this->pg_pdo->prepare($fqry);
			$f_qry->bindValue(':aamr',$this->productId);
			$f_qry->bindValue(':wsnr',$shopid);
		}
		
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

	public function getCLPData($type = "new", $shopid = null) {

		if (!empty($this->clpData)) {
			return $this->clpData;
		}

		if (($type == "new") or (empty($shopid))) {

			$artclp = "select g.arnr, g.gpsa, g.ghsa, g.gghs from art_ggv g 
					where arnr = :aamr and ( g.qbis is null or g.qbis > current_date ) order by qdtm desc";
			$f_qry = $this->pg_pdo->prepare($artclp);
			$f_qry->bindValue(':aamr',$this->productId);
		} else {
			$artclp = "select g.arnr, g.gpsa, g.ghsa, g.gghs from art_ggv g using (arnr)
					left join web_art w using (arnr)
					where arnr = :aamr and ( g.qbis is null or g.qbis > current_date ) 
					and g.qedt > w.wsdt and wsnr = :wsnr 
					order by qdtm desc";
			$f_qry = $this->pg_pdo->prepare($artclp);
			$f_qry->bindValue(':aamr',$this->productId);
			$f_qry->bindValue(':wsnr',$shopid);
		}
		
		$f_qry->execute() or die (print_r($f_qry->errorInfo()));
		
		$this->clpData = [] ;
		while ($row = $f_qry->fetch( PDO::FETCH_ASSOC ) ) {
			if (strlen($row["gpsa"]) > 1 ) { 
				$gpsa = preg_replace('/[^a-z0-9,+]/', '', strtolower($row["gpsa"]));
				$this->clpData = array_merge($this->clpData, explode(",", $gpsa)); 
			}
			if (strlen($row["ghsa"]) > 1 ) {
				$ghsa = preg_replace('/[^a-z0-9,+]/', '', strtolower($row["ghsa"]));
				$this->clpData = array_merge($this->clpData, explode(",", $ghsa)); 
			}
			if (strlen($row["gghs"]) > 1 ) { 
				$gghs = preg_replace('/[^a-z0-9,+]/', '', strtolower($row["gghs"]));
				$this->clpData = array_merge($this->clpData, explode(",", $gghs)); 
			}
		}
		return $this->clpData;
	}
	
	public function getPictures($type = "new", $shopid = null) {
			if (! isset($this->productPictures) or $this->productPictures == NULL ) {
				$this->getPicturesFromDB($type, $shopid);
			}
			return $this->productPictures;
	}	

	private function getOrderSumFromDB() {

		$aqry  = "select b.ifnr,cast(sum((b.fmge-COALESCE(b.fmgt,0))*a.amgn/a.amgz) as decimal(8,2)) as fmge, count(distinct fblg) as fcnt 
					from auftr_pos b inner join auftr_kopf k using (fblg) inner join art_0 a using (arnr)
					where k.ftyp = 2 and arnr = :aamr and fbkz = :fbkz
					group by b.ifnr";
		$a_qry = $this->pg_pdo->prepare($aqry);
		$a_qry->bindValue(':aamr',$this->productId);
		$a_qry->bindValue(':fbkz',$this->wwsPickBelegKz);
		$a_qry->execute() or die (print_r($a_qry->errorInfo()));
		$orderData = [];
		while ( $row = $a_qry->fetch( PDO::FETCH_ASSOC )) {
			$orderData[$row['ifnr']]['fmge'] = $row['fmge'];
			$orderData[$row['ifnr']]['fcnt'] = $row['fcnt'];
		}
		$this->productOrderSum = $orderData;
		return $orderData;
	}
	
	public function getOrderSum() {
		if (! isset($this->productOrderSum) or $this->productOrderSum == NULL ) {
			$this->getOrderSumFromDB();
		}
		return $this->productOrderSum;
	}	
	
	private function getStocksFromDB($checkOrders = true) {

		if ($checkOrders ) {
			if (isset($_SESSION['debug']) and ($_SESSION['debug'] == 1) and ($_SESSION["level"] == 9)) {
				print " get order data ..";
			}
			$affmge = $this->getOrderSum();
		}
		if (isset($_SESSION['debug']) and ($_SESSION['debug'] == 1) and ($_SESSION["level"] == 9)) {
			print " get stock data ..";
		}
		if ($this->productData[0]["aart"] == 2) {
			$fqry  = "select f.ifnr, min( (case when a.amgz > 0 then cast((coalesce(amge,0)*a.amgn/a.amgz) as decimal(8,2)) else coalesce(amge,0) end) * s.asmn/s.asmz) as amgb 
						from art_stl s left join art_0 a on s.astl = a.arnr
							left join fil_0 f on coalesce(f.quse,0) < 2 
							left join art_best b on s.astl = b.arnr and f.ifnr = b.ifnr
						where s.arnr = :aamr
						group by f.ifnr
						order by f.ifnr
					";
		} else {
			$fqry  = "select ifnr, case when a.amgz > 0 then cast((amge*a.amgn/a.amgz) as decimal(8,2)) else amge end as amgb 
						from art_0 a inner join art_best b using (arnr) where arnr = :aamr order by ifnr";
		}
		
		$f_qry = $this->pg_pdo->prepare($fqry);
		$f_qry->bindValue(':aamr',$this->productId);
		$f_qry->execute() or die (print_r($f_qry->errorInfo()));

		$this->productStocks = [] ;

		while ($row = $f_qry->fetch( PDO::FETCH_ASSOC ) ) {
			if ($row["amgb"] == null) {
				$row["amgb"] = 0;
			}
			if ($checkOrders and (!empty($affmge[$row['ifnr']]["fmge"]))) {
				$this->productStocks[$row["ifnr"]] = $row["amgb"] - $affmge[$row['ifnr']]["fmge"];
			} else {
				$this->productStocks[$row["ifnr"]] = $row["amgb"];
			}	
			
		}
		if (isset($_SESSION['debug']) and ($_SESSION['debug'] == 1) and ($_SESSION["level"] == 9)) {
			print " stock data done! \n";
		}
	}
	
	public function getStocks($checkOrders = true) {
			if (! isset($this->productStocks) or $this->productStocks == NULL ) {
				$this->getStocksFromDB($checkOrders);
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

	public function getStcklistData() {

		if ($this->productData[0]["aart"] != 2) {
			return false;
		}
		if ($this->productStckListData == null) {
			$fqry  = "select a.astl, (a.asmz/a.asmn) as asmg, t.abz1, m.mmss, b.ameh, b.qgrp, a.asmz, a.asmn from art_stl a 
						left join art_0 b on a.astl = b.arnr 
						inner join art_txt t on a.astl = t.arnr and t.xxak = '' and t.xyak = '' and t.qscd = 'DEU' 
						left join mand_mwst m on b.apkz = m.mmid 
					  where a.arnr = :arnr";

			$f_qry = $this->pg_pdo->prepare($fqry);
			$f_qry->bindValue(':arnr',$this->productId);
			$f_qry->execute() or die (print_r($f_qry->errorInfo()));

			$this->productStckListData = $f_qry->fetchAll( PDO::FETCH_ASSOC );
		}
	
		return $this->productStckListData;
		
	}

	public function writeStockDb($stockId, $stockAmount) {
		
		if ($this->productId != null) {
			$arnr = $this->productId;
		} elseif ($this->indexvalue != null) { 
			$arnr = $this->indexvalue;
		} else {
			return false; 
		}
		
		$stockSQL  = "insert into art_best (arnr, xxak, xyak, achb, ifnr, algo, amge, aimg) values	(:arnr, '', '', '', :ifnr, '', :amge, :aimg) 
						on conflict (arnr, xxak,xyak, achb, ifnr, algo) do update set amge = :amge ";	
		$stock_qry = $this->pg_pdo->prepare($stockSQL);
		$stock_qry->bindValue(':arnr',$arnr);
		$stock_qry->bindValue(':ifnr',$stockId);
		if ($this->productId != null) {
			$stock_qry->bindValue(':amge',$stockAmount);
			$stock_qry->bindValue(':aimg',null);
		} else {
			$stock_qry->bindValue(':amge',null);
			$stock_qry->bindValue(':aimg',$stockAmount);
		}
		$stock_qry->execute() or die (print_r($stock_qry->errorInfo()));	
		
		return true;
		
	}

	public function writePriceDb($condId, $supplierId, $price) {

		if ($this->productId != null) {
			$arnr = $this->productId;
			$cpog = 'F000';
			$apjs = $this->productData[0]['apjs'];
			$ameh = $this->productData[0]['ameh'];
		} elseif ($this->indexvalue != null) { 
			$arnr = $this->indexvalue;
			$cpog = 'X000';
			$apjs = 1;
			$ameh = 'Stck';
		} else {
			return false; 
		}
		
		// set prouct - supplier relationship
		$suplSQL  = "insert into art_lief (arnr, xxak, xyak, obnr, linr)  values  (:arnr, '', '', 0,:linr) 
						on conflict (arnr, xxak, xyak, obnr, linr) do nothing ";	
		$supl_qry = $this->pg_pdo->prepare($suplSQL);
		$supl_qry->bindValue(':arnr',$arnr);
		$supl_qry->bindValue(':linr',$supplierId);
		$supl_qry->execute() or die (print_r($supl_qry->errorInfo()));			

		// write pricelinr
		$priceSQL  = "insert into cond_ek (conr, mprb,arnr, xxak, xyak, obnr, cpog, cbez, cprs, apjs, cpcr, ccru, qdtm, qvon, qbis, ameh, linr)
								  values  (:conr, 1, :arnr, '', '', 0, :cpog, 'FPNE', :cprs, :apjs, 'EUR', 'C', current_date, date_trunc('month', current_date), '9999-12-31', :ameh, :linr) 
						on conflict (conr,mprb) do update set cprs = :cprs, apjs = :apjs, ameh = :ameh, linr = :linr, arnr = :arnr ";	
		$price_qry = $this->pg_pdo->prepare($priceSQL);
		$price_qry->bindValue(':conr',$condId);
		$price_qry->bindValue(':arnr',$arnr);
		$price_qry->bindValue(':cpog',$cpog);
		$price_qry->bindValue(':cprs',$price);
		$price_qry->bindValue(':apjs',$apjs);
		$price_qry->bindValue(':ameh',$ameh);
		$price_qry->bindValue(':linr',$supplierId);
		$price_qry->execute() or die (print_r($price_qry->errorInfo()));		
		
		return true;
		
		

	}

	public function getDBFields($DbFields) {
		
		$DbFields = preg_replace("/[^a-z0-9, ]/i","",$DbFields);
		$fqry  = "select ".$DbFields." from art_0 a inner join art_lief l using (arnr,linr) where xxak = '' and xyak = '' and obnr = 0 and arnr = :arnr";

		$f_qry = $this->pg_pdo->prepare($fqry);
		$f_qry->bindValue(':arnr',$this->productId);
		$f_qry->execute() or die (print_r($f_qry->errorInfo()));
		
		$row = $f_qry->fetch( PDO::FETCH_ASSOC );
		return $row;
	}
	
	public function getDiscountGroup() {
		
		$fqry  = "select qgrp as qhgr, argv, s.qbez, 9 as qgrl  from art_0 a 
					inner join art_matrix m on a.arnr = m.arnr and m.xxak = '' and m.xyak = ''
					left join status_id s on s.qskz = 31 and s.zxtp = m.argv 
					where a.arnr = :aamr";
		$f_qry = $this->pg_pdo->prepare($fqry);
		$f_qry->bindValue(':aamr',$this->productId);

		$f_qry->execute() or die (print_r($f_qry->errorInfo()));
		$row = $f_qry->fetch( PDO::FETCH_ASSOC ); 

		if (!empty($row['argv'])) {
			return [ 'id' => $row['argv'], 'name' => $row['qbez'] ];
		} else {
			
			while (!empty($row['qgrl'])) {

				$fqry  = "select qhgr, qgrl, argv, s.qbez  from art_grp g 
							left join status_id s on s.qskz = 31 and s.zxtp = g.argv
							where qgrp = :qgrp";
				$f_qry = $this->pg_pdo->prepare($fqry);
				$f_qry->bindValue(':qgrp',$row['qhgr']);
				
				$f_qry->execute() or die (print_r($f_qry->errorInfo()));
				$row = $f_qry->fetch( PDO::FETCH_ASSOC );
				
				if (!empty($row['argv'])) {
					return [ 'id' => $row['argv'], 'name' => $row['qbez'] ];
				}
			
			}
		}
		
		return false;
		
	}
}

?>