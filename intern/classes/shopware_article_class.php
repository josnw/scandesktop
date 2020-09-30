<?php

class ShopwareArticles {

	private $pg_pdo;
	private $articleList_qry;
	private	$startTime;
	private $ShopwareWebshopNumber;
	private $ShopwarePriceGroup;
	private $ShopwarePriceBase;
	private $ShopwareStockList;
	
	public function __construct() {
		
		include ("./intern/config.php");
		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		$this->ShopwareWebshopNumber = $ShopwareWebshopNumber;
		$this->ShopwarePriceGroup = $ShopwarePriceGroup;
		$this->ShopwarePriceBase = $ShopwarePriceBase;
		$this->ShopwareStockList = $ShopwareStockList;
		return true;
	}
	
	public function articleUpdateList($checkDate = NULL) {
		
		if (!isset($this->startTime) or (!$this->startTime > 0)) {
			$this->startTime = time();
		}

		// select article list for export, create handle only for scaling up big artile lists
		// if no CheckDate set, select only lines newer then last upload
		if (($checkDate == NULL) or ( strtotime($checkDate) === FALSE)) {
			$fqry  = "select distinct a.arnr, coalesce(aenr,a.arnr) as aenr, wson from art_0 a inner join web_art w on a.arnr = w.arnr and   w.wsnr = :wsnr
						left join art_best b on b.arnr = w.arnr and (b.qedt > w.wsdt or wsdt is null)
						left join cond_vk c on c.arnr = w.arnr and (c.qvon > w.wsdt or wsdt is null) and c.qvon <= current_date and c.qbis > current_date and mprb = 6 and cbez = 'PR01'
					  where  wsnr = :wsnr and ( wson = 1 or (wson = 0 and wsdt is not null )) 
					    and b.qedt is not null or c.qbis is not null
					  order by arnr
					";	
			$this->articleList_qry = $this->pg_pdo->prepare($fqry);
		} else {
			$fqry  = "select dsitinct a.arnr, coalesce(aenr,a.arnr) as aenr, wson from art_0 a inner join web_art w using (arnr)
						left join art_best b on b.arnr = w.arnr and w.wsnr = :wsnr and b.qedt > :wsdt 
						left join cond_vk c on c.arnr = w.arnr and w.wsnr = :wsnr and c.qvon > :wsdt and c.qvon <= current_date and c.qbis > current_date and mprb = 6 and cbez = 'PR01'
					  where  wsnr = :wsnr and ( wson = 1 or (wson = 0 and wsdt is not null ))
					  order by arnr
					";	
			$this->articleList_qry = $this->pg_pdo->prepare($fqry);
			$this->articleList_qry->bindValue(':wsdt',$checkDate);
		}
		$this->articleList_qry->bindValue(':wsnr',$this->ShopwareWebshopNumber);
		
		$this->articleList_qry->execute() or die (print_r($this->articleList_qry->errorInfo()));
	}

	public function exportToShopware($api, $noupload = null) {

		if (!isset($this->articleList_qry) or ($this->articleList_qry == NULL)) {
			return(false);
		}

		$cnt = 0;
		$errorList = '';
		// fill array and write to file
		while ($frow = $this->articleList_qry->fetch(PDO::FETCH_ASSOC )) {
			$cnt++;
			$article = new product($frow["arnr"]);
			$stocks = $article->getStocks();
			
			if (($SESSION['debug'] == 1) and ($_SESSION["level"] == 9)) {
				print "<br/>StockList: ".print_r($this->ShopwareStockList,1)."<br/>";
				print_r($stocks);
			}

			$stockSum = 0;
			foreach($stocks as $stockNumber => $stockAmount ) { 
				if (in_array( $stockNumber , $this->ShopwareStockList)) {
					$stockSum += $stockAmount;
				}
			}
			
			$prices = $article->getPrices( true );

			if (($SESSION['debug'] == 1) and ($_SESSION["level"] == 9)) {
				print "<br/>PriceBase: ".$this->ShopwarePriceBase."<br/>";
				print_r($prices);
			}


			$restdata = [ "mainDetail" => [ 
					"instock" => $stockSum,
					"prices" => [ 
						0 => [	
							"customerGroupKey" => $this->ShopwarePriceGroup,
							"price" => $prices[$this->ShopwarePriceBase],
							"pseudoprice" => null
						]
					],
					'__options_prices' => ['replace' => false ] 
				]
			];
			if ( ! $noupload ) {
				$result = $api->put('articles/'.$frow['aenr'].'?useNumberAsId=true',  $restdata);
			} else {
				$result = [ "success" => 0, "restdata" => $restdata];

			}
			
			if ($result["success"] == 1) {
			  $this->setUpdateTime($frow['arnr']);
			} else {
			  $errorlist .= $frow['arnr']."\t".print_r($result,1); 
			}

		}
		
		return ['count' => $cnt , 'errors' => $errorlist];
		
	}
	
	public function setUpdateTime($article) {
		
		// select article list for export, create handle only for scaling up big artile lists
		$fqry  = "update web_art w set wsdt = :wsdt where w.wsnr = :wsnr and arnr = :arnr";	
		
		$uploadDate_upd = $this->pg_pdo->prepare($fqry);
		$uploadDate_upd->bindValue(':wsnr',$this->ShopwareWebshopNumber);
		$uploadDate_upd->bindValue(':arnr',$article);
		$uploadDate_upd->bindValue(':wsdt',date("Y-m-d H:i:s", $this->startTime));
		
		$uploadDate_upd->execute() or die (print_r($uploadDate_upd->errorInfo()));

		return true;
	}
	
}
?>