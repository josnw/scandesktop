<?php

class tradebytePanda {

	private $pg_pdo;
	private $articleList_qry;
	private $parameterTypeList;
	private $priceTypeList;
	private $basepriceTypeList;
	private $mdbTypesTypeList;
	
	public function __construct() {
		
		include ("./intern/config.php");
		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);

		return true;
	}
	
	
	public function selectByQgrpLinr($vonlinr,$bislinr,$vonqgrp,$bisqgrp) {

		// sql check parameter list for export array
		$paraqry  = "select distinct qpky from art_param p where arnr in (select arnr from art_0 a where  p.arnr = a.arnr and linr between :vonlinr and :bislinr and qgrp between :vongrp and :bisgrp )";	
		$para_qry = $this->pg_pdo->prepare($paraqry);
		$para_qry->bindValue(':vonlinr',preg_replace("/[^0-9]/","",$vonlinr));
		$para_qry->bindValue(':bislinr',preg_replace("/[^0-9]/","",$bislinr));
		$para_qry->bindValue(':vongrp',preg_replace("/[^0-9]/","",$vonqgrp));
		$para_qry->bindValue(':bisgrp',preg_replace("/[^0-9]/","",$bisqgrp));
		$para_qry->execute() or die (print_r($para_qry->errorInfo()));
		$this->parameterTypeList = $para_qry->fetchall(PDO::FETCH_NUM );

		// sql check pricekey list
		$priceqry  = "select qbez from mand_prsbas where mprb > 6";	
		$price_qry = $this->pg_pdo->prepare($priceqry);
		$price_qry->execute() or die (print_r($price_qry->errorInfo()));
		$this->priceTypeList = $price_qry->fetchall(PDO::FETCH_NUM );
		
		// sql check base price units
		$basepriceqry  = "select distinct ameg from art_0 where linr between :vonlinr and :bislinr and qgrp between :vongrp and :bisgrp ";	
		$baseprice_qry = $this->pg_pdo->prepare($basepriceqry);
		$baseprice_qry->bindValue(':vonlinr',preg_replace("/[^0-9]/","",$vonlinr));
		$baseprice_qry->bindValue(':bislinr',preg_replace("/[^0-9]/","",$bislinr));
		$baseprice_qry->bindValue(':vongrp',preg_replace("/[^0-9]/","",$vonqgrp));
		$baseprice_qry->bindValue(':bisgrp',preg_replace("/[^0-9]/","",$bisqgrp));
		$baseprice_qry->execute() or die (print_r($baseprice_qry->errorInfo()));
		$this->basepriceTypeList = $baseprice_qry->fetchall(PDO::FETCH_NUM );

		// sql check mdb types and counts per articles
		$mdbTypesqry  = "select qbez, max(cnt) as cnt from ( select arnr, qbez, count(*) as CNT from art_liefdok  d 
							inner join art_0 a using (arnr) where adtp = 91701 and
							a.linr between :vonlinr and :bislinr and a.qgrp between :vongrp and :bisgrp 
							group by arnr, qbez) X group by qbez";

		$mdbTypes_qry = $this->pg_pdo->prepare($mdbTypesqry);
		$mdbTypes_qry->bindValue(':vonlinr',preg_replace("/[^0-9]/","",$vonlinr));
		$mdbTypes_qry->bindValue(':bislinr',preg_replace("/[^0-9]/","",$bislinr));
		$mdbTypes_qry->bindValue(':vongrp',preg_replace("/[^0-9]/","",$vonqgrp));
		$mdbTypes_qry->bindValue(':bisgrp',preg_replace("/[^0-9]/","",$bisqgrp));
		$mdbTypes_qry->execute() or die (print_r($mdbTypes_qry->errorInfo()));
		$this->mdbTypesTypeList = $mdbTypes_qry->fetchall(PDO::FETCH_NUM );

		// select article list for export, create handle only for scaling up big artile lists
		$fqry  = "select arnr from art_0 a where linr between :vonlinr and :bislinr and qgrp between :vongrp and :bisgrp order by linr, qgrp, arnr";	
		$this->articleList_qry = $this->pg_pdo->prepare($fqry);
		$this->articleList_qry->bindValue(':vonlinr',preg_replace("/[^0-9]/","",$vonlinr));
		$this->articleList_qry->bindValue(':bislinr',preg_replace("/[^0-9]/","",$bislinr));
		$this->articleList_qry->bindValue(':vongrp',preg_replace("/[^0-9]/","",$vonqgrp));
		$this->articleList_qry->bindValue(':bisgrp',preg_replace("/[^0-9]/","",$bisqgrp));
		
		$this->articleList_qry->execute() or die (print_r($this->articleList_qry->errorInfo()));
		
		return true;
		
	}

	public function exportToFile($pandafile) {
	
		if (!isset($this->articleList_qry) or ($this->articleList_qry == NULL)) {
			return(false);
		}
		
		//prepare array 
		$exportarray = ['p_nr' => null, 'a_nr' => null, 'a_prodnr' => null, 'a_ean' => null, 'p_name_keyword' => null, 'p_name_propper' => null, 'p_text' => null ];
		
		foreach( $this->parameterTypeList as $parameter) {
			$exportarray["p_comp[".$parameter[0]."]"] = "";
		}
		foreach($this->priceTypeList as $price) {
			$exportarray["a_vk[".$price[0]."]"] = "";
		}

		foreach($this->mdbTypesTypeList as $mdbTypesType) {
			for($i = 0; $i < $mdbTypesType[1]; $i++) {
				$exportarray["a_media[".$mdbTypesType[0]."]{".$i."}"] = "";
			}
		}
		
		foreach($this->basepriceTypeList as $baseprice) {
			$exportarray["a_base_price[".$baseprice[0]."]"] = "";
		}
		
		$panda = new myFile($pandafile, "append");
		
		$cnt = 0;
		
		// fill array and write to file
		while ($frow = $this->articleList_qry->fetch(PDO::FETCH_ASSOC )) {
			$article = new product($frow["arnr"],"tradebyte");
			
			$basedata = $article->getTradebyteFormat("basedata");
			$parameters = $article->getTradebyteFormat("p_comp");
			$prices = $article->getTradebyteFormat("a_vk");
			$media = $article->getTradebyteFormat("a_media");

			$temp_array = array_merge($exportarray, $basedata, $parameters, $prices, $media);
			
			// print table header in first line
			if ($cnt++ == 0) {
				$panda->writeCSV(array_keys($temp_array ));
			}
			
			// print data line
			foreach($temp_array as $key=>$value) {
				$temp_array[$key] = trim($value);
			if ((is_numeric($value)) or (preg_match("/[0-9]+\.[0-9]+[ a-z-A-Z���]{1,5}/",$value))) {
					$temp_array[$key] = str_replace(".",",",$value);
				}
			}
			$panda->writeCSV($temp_array);
			
		}
		$exportname =  $panda->getCheckedName();
		$panda->close();			
	
		return [ 'filename' => $exportname, 'count' => $cnt ];
		
	}
}		