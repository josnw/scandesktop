<?php

class tradebytePanda {

	private $pg_pdo;
	private $articleList_qry;
	private $parameterTypeList;
	private $priceTypeList;
	
	public function __construct() {
		
		include ("./intern/config.php");
		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);

		return true;
	}
	
	
	public function selectByQgrpLinr($vonlinr,$bislinr,$vonqgrp,$bisqgrp) {

		$paraqry  = "select distinct qpky from art_param p where arnr in (select arnr from art_0 a where  p.arnr = a.arnr and linr between :vonlinr and :bislinr and qgrp between :vongrp and :bisgrp )";	
		$para_qry = $this->pg_pdo->prepare($paraqry);

		$priceqry  = "select qbez from mand_prsbas where mprb > 6";	
		$price_qry = $this->pg_pdo->prepare($priceqry);

		$para_qry->bindValue(':vonlinr',preg_replace("/[^0-9]/","",$vonlinr));
		$para_qry->bindValue(':bislinr',preg_replace("/[^0-9]/","",$bislinr));
		$para_qry->bindValue(':vongrp',preg_replace("/[^0-9]/","",$vonqgrp));
		$para_qry->bindValue(':bisgrp',preg_replace("/[^0-9]/","",$bisqgrp));

		$para_qry->execute() or die (print_r($para_qry->errorInfo()));
		$this->parameterTypeList = $para_qry->fetchall(PDO::FETCH_NUM );
		
		$price_qry->execute() or die (print_r($price_qry->errorInfo()));
		$this->priceTypeList = $price_qry->fetchall(PDO::FETCH_NUM );
		

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
		
		$exportarray = [];
		
		foreach( $this->parameterTypeList as $parameter) {
			$exportarray["p_comp[".$parameter[0]."]"] = "";
		}
		foreach($this->priceTypeList as $price) {
			$exportarray["a_vk[".$price[0]."]"] = "";
		}
		
		$panda = fopen($pandafile, "a+");
		
		$cnt = 0;
		while ($frow = $this->articleList_qry->fetch(PDO::FETCH_ASSOC )) {
			$article = new product($frow["arnr"],"tradebyte");
			
			$basedata = $article->getTradebyteFormat("basedata");
			$parameters = $article->getTradebyteFormat("p_comp");
			$prices = $article->getTradebyteFormat("a_vk");

			$temp_array = array_merge($basedata,$exportarray,$parameters,$prices);
			if ($cnt++ == 0) {
				fputcsv($panda,array_keys($temp_array ),";",'"');
			}
			
			fputcsv($panda,$temp_array ,";",'"');
			
		}
		fclose($panda);			
		return $cnt;
		
	}
}		