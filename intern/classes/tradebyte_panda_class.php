<?php

class tradebytePanda {

	private $pg_pdo;
	private $articleList_qry;
	private $parameterTypeList;
	private $priceTypeList;
	private $basepriceTypeList;
	private $mdbTypesTypeList;
	private $stockList;
	private $TradebyteWebshopNumber;
	private	$startTime;
	
	public function __construct() {
		
		include ("./intern/config.php");
		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		$this->TradebyteWebshopNumber = $TradebyteWebshopNumber;
		return true;
	}
	
	public function selectByQgrpLinr($vonlinr,$bislinr,$vonqgrp,$bisqgrp, $setAutoUpdate = false, $onlyNew = false, $akz = '') {

		// sql check parameter list for export array
		$paraqry  = "select distinct qpky from art_param p where arnr in (select arnr from art_0 a where  p.arnr = a.arnr and linr between :vonlinr and :bislinr and qgrp between :vongrp and :bisgrp) ";
		if ($onlyNew) { $paraqry  .= " and arnr in (select arnr from web_art w where  p.arnr = w.arnr and wsnr = :wsnr and wson = 1 and (wsdt is null or qadt = current_timestamp - interval '1'  hour)) "; }
		if ($akz <> '') { $paraqry  .= " and arnr in (select arnr from art_0 a2 where  p.arnr = a2.arnr and astf = :akz ) "; }
		$para_qry = $this->pg_pdo->prepare($paraqry);
		$para_qry->bindValue(':vonlinr',preg_replace("/[^0-9]/","",$vonlinr));
		$para_qry->bindValue(':bislinr',preg_replace("/[^0-9]/","",$bislinr));
		$para_qry->bindValue(':vongrp',preg_replace("/[^0-9]/","",$vonqgrp));
		$para_qry->bindValue(':bisgrp',preg_replace("/[^0-9]/","",$bisqgrp));
		if ($onlyNew) {  $para_qry->bindValue(':wsnr',$this->TradebyteWebshopNumber); }
		if ($akz <> '') {  $para_qry->bindValue(':akz',$akz); }
		$para_qry->execute() or die (print_r($para_qry->errorInfo()));
		
		$this->parameterTypeList = $para_qry->fetchall(PDO::FETCH_NUM );

		// sql check pricekey list
		$priceqry  = "select qbez from mand_prsbas where mprb >= 6";	
		$price_qry = $this->pg_pdo->prepare($priceqry);
		$price_qry->execute() or die (print_r($price_qry->errorInfo()));
		$this->priceTypeList = $price_qry->fetchall(PDO::FETCH_NUM );
		
		// sql check base price units
		$basepriceqry  = "select distinct ameg from art_0 a where linr between :vonlinr and :bislinr and qgrp between :vongrp and :bisgrp ";	
		if ($onlyNew) { $basepriceqry  .= " and arnr in (select arnr from web_art w where  a.arnr = w.arnr and wsnr = :wsnr and wson = 1 and (wsdt is null or qadt = current_timestamp - interval '1'  hour)) "; }
		if ($akz <> '') { $basepriceqry  .= " and arnr in (select arnr from art_0 a2 where  a.arnr = a2.arnr and astf = :akz ) ";}

		$baseprice_qry = $this->pg_pdo->prepare($basepriceqry);
		$baseprice_qry->bindValue(':vonlinr',preg_replace("/[^0-9]/","",$vonlinr));
		$baseprice_qry->bindValue(':bislinr',preg_replace("/[^0-9]/","",$bislinr));
		$baseprice_qry->bindValue(':vongrp',preg_replace("/[^0-9]/","",$vonqgrp));
		$baseprice_qry->bindValue(':bisgrp',preg_replace("/[^0-9]/","",$bisqgrp));
		if ($onlyNew) {  $baseprice_qry->bindValue(':wsnr',$this->TradebyteWebshopNumber); }
		if ($akz <> '') {  $baseprice_qry->bindValue(':akz',$akz); }

		$baseprice_qry->execute() or die (print_r($baseprice_qry->errorInfo()));
		$this->basepriceTypeList = $baseprice_qry->fetchall(PDO::FETCH_NUM );

		// sql check mdb types and counts per articles
		$mdbTypesqry  = "select qbez, max(cnt) as cnt from ( select arnr, qbez, count(*) as CNT from art_liefdok  d 
							inner join art_0 a using (arnr) where adtp = 91701 and
							a.linr between :vonlinr and :bislinr and a.qgrp between :vongrp and :bisgrp ";
		if ($onlyNew) { $mdbTypesqry  .= " and a.arnr in (select arnr from web_art w where  d.arnr = w.arnr and wsnr = :wsnr and wson = 1 and (wsdt is null or qadt = current_timestamp - interval '1'  hour)) "; }
		if ($akz <> '') { $mdbTypesqry  .= " and arnr in (select arnr from art_0 a2 where  d.arnr = a2.arnr and astf = :akz ) "; }
		$mdbTypesqry  .= "	group by arnr, qbez) X group by qbez";

		$mdbTypes_qry = $this->pg_pdo->prepare($mdbTypesqry);
		$mdbTypes_qry->bindValue(':vonlinr',preg_replace("/[^0-9]/","",$vonlinr));
		$mdbTypes_qry->bindValue(':bislinr',preg_replace("/[^0-9]/","",$bislinr));
		$mdbTypes_qry->bindValue(':vongrp',preg_replace("/[^0-9]/","",$vonqgrp));
		$mdbTypes_qry->bindValue(':bisgrp',preg_replace("/[^0-9]/","",$bisqgrp));
		if ($onlyNew) {  $mdbTypes_qry->bindValue(':wsnr',$this->TradebyteWebshopNumber); }
		if ($akz <> '') {  $mdbTypes_qry->bindValue(':akz',$akz); }
		$mdbTypes_qry->execute() or die (print_r($mdbTypes_qry->errorInfo()));
		$this->mdbTypesTypeList = $mdbTypes_qry->fetchall(PDO::FETCH_NUM );

		// select article list for export, create handle only for scaling up big artile lists
		$fqry  = "select arnr from art_0 a where linr between :vonlinr and :bislinr and qgrp between :vongrp and :bisgrp ";	
		if ($onlyNew) { $fqry  .= " and arnr in (select arnr from web_art w where a.arnr = w.arnr and wsnr = :wsnr and wson = 1 and (wsdt is null or qadt = current_timestamp - interval '1'  hour)) "; }
		if ($akz <> '') { $fqry  .= " and arnr in (select arnr from art_0 a2 where  a.arnr = a2.arnr and astf = :akz ) "; }
		$fqry  .= "order by linr, qgrp, arnr";
		print $fqry;
		print "<br>vl".preg_replace("/[^0-9]/","",$vonlinr);
		print "<br>bl".preg_replace("/[^0-9]/","",$bislinr);
		print "<br>vg".preg_replace("/[^0-9]/","",$vonqgrp);
		print "<br>bg".preg_replace("/[^0-9]/","",$bisqgrp);
		print "<br>ws".$this->TradebyteWebshopNumber;
		print "<br>kz".$akz;
		$this->articleList_qry = $this->pg_pdo->prepare($fqry);
		$this->articleList_qry->bindValue(':vonlinr',preg_replace("/[^0-9]/","",$vonlinr));
		$this->articleList_qry->bindValue(':bislinr',preg_replace("/[^0-9]/","",$bislinr));
		$this->articleList_qry->bindValue(':vongrp',preg_replace("/[^0-9]/","",$vonqgrp));
		$this->articleList_qry->bindValue(':bisgrp',preg_replace("/[^0-9]/","",$bisqgrp));
		if ($onlyNew) {  $this->articleList_qry->bindValue(':wsnr',$this->TradebyteWebshopNumber); }
		if ($akz <> '') {  $this->articleList_qry->bindValue(':akz',$akz); }
		$this->articleList_qry->execute() or die (print_r($this->articleList_qry->errorInfo()));
		
		if ($setAutoUpdate and !$onlyNew) {
			// set flag for standard 
			print "Set Flag Autoupdate ...";
			$updateFlagqry  = "insert into web_art (arnr, xxak, xyak, wsnr, wson, wsdt) 
								(select arnr, '','', :wsnr, 1 , '1999-12-31' as wsdt from art_0 
									where linr between :vonlinr and :bislinr and qgrp between :vongrp and :bisgrp ";
			if ($akz <> '') { $updateFlagqry  .= " and astf = :akz "; }
			$updateFlagqry  .= " )	on conflict (arnr, xxak,xyak, wsnr) do update set wson=1 ";	
			$updateFlag_qry = $this->pg_pdo->prepare($updateFlagqry);
			$updateFlag_qry->bindValue(':vonlinr',preg_replace("/[^0-9]/","",$vonlinr));
			$updateFlag_qry->bindValue(':bislinr',preg_replace("/[^0-9]/","",$bislinr));
			$updateFlag_qry->bindValue(':vongrp',preg_replace("/[^0-9]/","",$vonqgrp));
			$updateFlag_qry->bindValue(':bisgrp',preg_replace("/[^0-9]/","",$bisqgrp));
			$updateFlag_qry->bindValue(':wsnr',preg_replace("/[^0-9]/","",$this->TradebyteWebshopNumber));
			if ($akz <> '') {  $updateFlag_qry->bindValue(':akz',$akz); }

			$updateFlag_qry->execute() or print (print_r($updateFlag_qry->errorInfo()));


		}
		
		
		return true;
		
	}

	public function stockUpdate($pandafile, $checkDate = NULL) {
		
		if (!isset($this->startTime) or (!$this->startTime > 0)) {
			$this->startTime = time();
		}

		// sql check stock list for export array
		// if no CheckDate set, select only lines newer then last upload
		if (($checkDate == NULL) or ( strtotime($checkDate) === FALSE)) {
			$stockqry  = "select distinct ifnr from art_best b order by ifnr";	
			$stock_qry = $this->pg_pdo->prepare($stockqry);
		} else {
			$stockqry  = "select distinct ifnr from art_best b order by ifnr";	
			$stock_qry = $this->pg_pdo->prepare($stockqry);
			//$stock_qry->bindValue(':wsdt',$checkDate);
		}
		//$stock_qry->bindValue(':wsnr',$this->TradebyteWebshopNumber);
		$stock_qry->execute() or die (print_r($stock_qry->errorInfo()));
		$this->stockList = $stock_qry->fetchall(PDO::FETCH_NUM );

		// select article list for export, create handle only for scaling up big artile lists
		// if no CheckDate set, select only lines newer then last upload
		if (($checkDate == NULL) or ( strtotime($checkDate) === FALSE)) {
			$fqry  = "select distinct a.arnr from art_0 a left join art_best b using (arnr) inner join web_art w using (arnr) where  w.wsnr = :wsnr and wson = 1 and
					  ( (b.qedt > w.wsdt) or (w.wsdt is null)
						or (a.aart = 2 and a.arnr in (select s.arnr from art_stl s inner join art_best c on s.astl = c.arnr where s.arnr = a.arnr and c.qedt > w.wsdt) )
					  )
					";	
			$this->articleList_qry = $this->pg_pdo->prepare($fqry);
		} else {
			$fqry  = "select distinct a.arnr from art_0 a left join art_best b using (arnr) inner join web_art w using (arnr) where  w.wsnr = :wsnr and wson = 1 and
					  ( (b.qedt > :wsdt)  or (w.wsdt is null)
						or (a.aart = 2 and a.arnr in (select s.arnr from art_stl s inner join art_best c on s.astl = c.arnr where s.arnr = a.arnr and c.qedt > :wsdt) )
					  )
					";	
			$this->articleList_qry = $this->pg_pdo->prepare($fqry);
			$this->articleList_qry->bindValue(':wsdt',$checkDate);
		}
		$this->articleList_qry->bindValue(':wsnr',$this->TradebyteWebshopNumber);
		
		$this->articleList_qry->execute() or die (print_r($this->articleList_qry->errorInfo()));
		
		return $this->exportToFile($pandafile, "stock");

	}

	public function priceUpdate($pandafile, $checkDate = NULL) {
		
		if (!isset($this->startTime) or (!$this->startTime > 0)) {
			$this->startTime = time();
		}
		
		// sql check pricekey list
		$priceqry  = "select qbez from mand_prsbas where mprb >= 6";	
		$price_qry = $this->pg_pdo->prepare($priceqry);
		$price_qry->execute() or die (print_r($price_qry->errorInfo()));
		$this->priceTypeList = $price_qry->fetchall(PDO::FETCH_NUM );

		// select article list for export, create handle only for scaling up big artile lists
		// if no CheckDate set, select only lines newer then last upload
		if (($checkDate == NULL) or ( strtotime($checkDate) === FALSE)) {
			$fqry  = "select arnr from cond_vk c inner join web_art w using (arnr) 
						where w.wsnr = :wsnr and c.qvon > w.wsdt and c.qvon <= current_date and c.qbis > current_date and mprb >=6  and cbez = 'PR01' ";	
			$this->articleList_qry = $this->pg_pdo->prepare($fqry);
		} else {
			$fqry  = "select arnr from cond_vk c inner join web_art w using (arnr) 
						where w.wsnr = :wsnr and c.qvon > :wsdt and c.qvon <= current_date and c.qbis > current_date and mprb >= 6 and cbez = 'PR01' ";	
			$this->articleList_qry = $this->pg_pdo->prepare($fqry);
			$this->articleList_qry->bindValue(':wsdt',$checkDate);
		}
		$this->articleList_qry->bindValue(':wsnr',$this->TradebyteWebshopNumber);
		
		$this->articleList_qry->execute() or die (print_r($this->articleList_qry->errorInfo()));

		return $this->exportToFile($pandafile, "price");
	}

	public function mediaUpdate($pandafile) {

		if (!isset($this->startTime) or (!$this->startTime > 0)) {
			$this->startTime = time();
		}
	
		/*
			ToDo
			add filter setUpdateTime 
		*/
		return false;
	
	}
	
	public function exportToFile($pandafile, $type = "panda") {
	
		if (!isset($this->articleList_qry) or ($this->articleList_qry == NULL)) {
			return(false);
		}
		
		if ($type == 'panda') {
			//prepare array 
			$exportarray = ['p_nr' => null, 'a_nr' => null, 'a_prodnr' => null, 'a_ean' => null, 'p_name_keyword' => null, 'p_name_proper' => null, 'p_text' => null ];
			
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
				if (strlen($baseprice[0]) > 0) {
					$exportarray["a_base_price[".$baseprice[0]."]"] = "";
				}
			}
		} elseif ($type == 'price') {
			foreach($this->priceTypeList as $price) {
				$exportarray["a_vk[".$price[0]."]"] = 0;
			}
		} elseif ($type == 'stock') {
			foreach($this->stockList as $stock) {
				$exportarray["a_stock[".$stock[0]."]"] = 0;
			}
		} elseif ($type == 'media') {
		    foreach($this->mdbTypesTypeList as $media) {
				$exportarray["a_media[".$media[0]."]"] = "";
			}
		}
		
		
		$panda = new myFile($pandafile, "append");
		
		$cnt = 0;
		
		// fill array and write to file
		while ($frow = $this->articleList_qry->fetch(PDO::FETCH_ASSOC )) {
			
			if ($type == 'panda') {
				$article = new product($frow["arnr"],"tradebyte");
				
				$basedata = $article->getTradebyteFormat("basedata");
				$parameters = $article->getTradebyteFormat("p_comp");
				$prices = $article->getTradebyteFormat("a_vk");
				$media = $article->getTradebyteFormat("a_media");

				$temp_array = array_merge($exportarray, $basedata, $parameters, $prices, $media);
			} elseif ($type == 'stock') {
				$article = new product($frow["arnr"]);
				$index = $article->getTradebyteFormat("basedata");
				$stock = $article->getTradebyteFormat("a_stock");
				$temp_array = array_merge($index,$exportarray, $stock);
			} elseif ($type == 'price') {
				$article = new product($frow["arnr"]);
				$index = $article->getTradebyteFormat("basedata");
				$prices = $article->getTradebyteFormat("a_vk");

				$temp_array = array_merge($index,$exportarray, $prices);

			} elseif ($type == 'media') {
				$article = new product($frow["arnr"]);
				$index = $article->getTradebyteFormat("basedata");
				$media = $article->getTradebyteFormat("a_media");

				$temp_array = array_merge($index,$exportarray, $media);

			} 
			
			
			// print table header in first line
			if ($cnt++ == 0) {
				$panda->writeCSV(array_keys($temp_array ));
			}
			
			// print data line
			if (strlen($temp_array["a_nr"]) > 0) {
				$emptyField = 0;
				foreach($temp_array as $key=>$value) {
					$temp_array[$key] = trim($value);
					if ((is_numeric($value)) or (preg_match("/[0-9]+\.[0-9]+[ a-z-A-Z���]{1,5}/",$value))) {
						$temp_array[$key] = str_replace(".",",",$value);
					} 
					if (empty($value)) {
					  $emptyField++;
					}
				}
				if (($type != 'price') or ($emptyField == 0)) {
					$panda->writeCSV($temp_array);
				}
			}
			
		}
		$exportname =  $panda->getCheckedName();
		$panda->close();			
	
		return [ 'filename' => $exportname, 'count' => $cnt ];
		
	}
	
	public function setUpdateTime() {
		
		// select article list for export, create handle only for scaling up big artile lists
		$fqry  = "update web_art w set wsdt = :wsdt where w.wsnr = :wsnr and wson = 1 and wsdt is not null
		           and ( 
						arnr in (select arnr from cond_vk c where c.arnr = w.arnr and c.qvon > w.wsdt and c.qvon <= current_date and c.qbis > current_date and mprb >= 6 and cbez = 'PR01')
						or arnr in (select arnr from art_best b where b.arnr = w.arnr and b.qedt > w.wsdt) 
				   ) ";	
		$this->articleList_qry = $this->pg_pdo->prepare($fqry);
		$this->articleList_qry->bindValue(':wsnr',$this->TradebyteWebshopNumber);
		$this->articleList_qry->bindValue(':wsdt',date("Y-m-d H:i:s", $this->startTime));
		
		$this->articleList_qry->execute() or die (print_r($this->articleList_qry->errorInfo()));

		return true;
	}
}	