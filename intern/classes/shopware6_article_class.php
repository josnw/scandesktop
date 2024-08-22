<?php

class Shopware6Articles {

	private $pg_pdo;
	private $articleList_qry;
	private	$startTime;
	private $ShopwareWebshopNumber;
	private $ShopwarePriceGroup;
	private $ShopwarePriceBase;
	private $ShopwareStockList;
	private $ShopwareCurrencyId;
	private $ShopwareMediaFolderId;
	private $shopwareCategoryCmsPageId;
	private $shopware6CategoryMatching;
	private $shopware6CategoryMatchingFieldName;
	private $shopware6CategoryMatchingFile;
	private $shopware6LenzCLP;
	private $shopware6AlternatePrices;
	private $shopClpList;
	private $shopware6SetCloseout;
	private $shopware6SetMaxPurchaseToStock;
	private $shopware6DefaultVisibilities;
	private $shopware6Visibilities;
	private $shopware6NoPrices;
	private $shopware6NetPriceBase;
	private $shopware6WGDisMatrix;
	private $shopware6AlternateProductname;
	private $shopware6ManufactureCustomField;
	private $shopware6DiscountTag;
	private $shopware6DeliveryTimeIds;
	private $filterArray;
	private $shopware6UpdateLinr;
	private $shopware6NoCatUpdate;
	private $shopware6NoVisibilityUpdate;
	private $fullExternalStockGroup;
	private $api; 
	
	public function __construct($api = null) {
		
		include ("./intern/config.php");
		
		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		$this->ShopwareWebshopNumber = $shopware6WebshopNumber;
		$this->ShopwarePriceGroup = $ShopwarePriceGroup;
		$this->ShopwarePriceBase = $shopware6PriceBase;
		$this->ShopwareCurrencyId = $shopware6CurrencyId;
		$this->ShopwareStockList = $shopware6StockList;
		$this->ShopwareStockCheckOrders = $shopware6StockCheckOrders;
		$this->ShopwareMediaFolderId = $shopware6MediaFolderId;
		$this->ShopwareDynamicExternalStock = $shopware6DynamicStock;
		$this->dynamic_stock_upload = $dynamic_stock_upload;
		$this->shopwareCategoryCmsPageId = $shopware6CategoryCmsPageId;
		$this->shopware6CategoryMatchingFieldName = $shopware6CategoryMatchingFieldName;
		$this->shopware6CategoryMatchingFile = $sw6GroupMatching;
		$this->shopware6LenzCLP = $shopware6LenzCLP;
		$this->shopware6AlternatePrices = $shopware6AlternatePrices;
		$this->shopware6SetCloseout = $shopware6SetCloseout;
		$this->shopware6SetMaxPurchaseToStock = $shopware6SetMaxPurchaseToStock;
		$this->shopware6DefaultVisibilities = $shopware6DefaultVisibilities;
		$this->shopware6Visibilities = $shopware6Visibilities;
		$this->shopware6NoPrices = $shopware6NoPrices;
		$this->shopware6AlternateProductname = $shopware6AlternateProductname;
		$this->shopware6UseHsnr = $shopware6UseHsnr;
		$this->shopware6ManufactureCustomField = $shopware6ManufactureCustomField;
		$this->shopware6setDiscountTag = $shopware6setDiscountTag;
		$this->shopware6NetPriceBase = $shopware6NetPriceBase;
		$this->shopware6WGDisMatrix = $shopware6WGDisMatrix;
		$this->shopware6DeliveryTimeIds = $shopware6DeliveryTimeIds;
		$this->shopware6UpdateLinr = $shopware6UpdateLinr;
		$this->shopware6NoCatUpdate = $shopware6NoCatUpdate;
		$this->shopware6NoVisibilityUpdate = $shopware6NoVisibilityUpdate;
		$this->fullExternalStockGroup = $fullExternalStockGroup;
		$this->api = $api;
		
		if (file_exists($sw6GroupMatching)) {
			$this->shopware6CategoryMatching = json_decode(file_get_contents($sw6GroupMatching),true);
		} else {
			$this->shopware6CategoryMatching = null; 
		}
		
		if (file_exists($this->shopware6PropertyFile)) {
			$this->filterArray = json_decode(file_get_contents($this->shopware6PropertyFile),true);
		} else {
			$this->filterArray = [];
		}
		
		return true;
	}
	
	public function articleUpdateListPriceStock($checkDate = NULL) {
		
		if (!isset($this->startTime) or (!$this->startTime > 0)) {
			$this->startTime = time();
		}

		// select article list for export, create handle only for scaling up big artile lists
		// if no CheckDate set, select only lines newer then last upload
		if (($checkDate == NULL) or ( strtotime($checkDate) === FALSE)) {
			$fqry  = "select distinct a.arnr, coalesce(aenr,a.arnr) as aenr, wson, a.qgrp, qgid from art_0 a inner join web_art w on a.arnr = w.arnr and w.wsnr = :wsnr
						left join art_best b on b.arnr = w.arnr and (b.qedt > w.wsdt)
						left join cond_vk c on c.arnr = w.arnr and (c.qvon > w.wsdt or c.qedt > w.wsdt) and c.qvon <= current_date and c.qbis > current_date and mprb >= 6 and cbez = 'PR01'
						left join auftr_pos ap on ap.arnr = a.arnr and ftyp = 2 and ap.qadt > ( current_timestamp - interval '1 hour')  
					   where  ( wsnr = :wsnr and wsdt is not null ) 
					    and ( b.qedt is not null or c.qbis is not null or ap.fmge > 0) 
 	  				  union select distinct sl.arnr, coalesce(aenr,a2.arnr) as aenr, wson, a2.qgrp, qgid from art_0 a2 inner join web_art w on a2.arnr = w.arnr and w.wsnr = :wsnr
						inner join art_stl sl on sl.arnr = w.arnr 	
						inner join art_best b2 on b2.arnr = sl.astl and (b2.qedt > w.wsdt) 	
					   where  wsnr = :wsnr and ( wsdt is not null )
					  order by arnr
					";	
			$options = [ PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL ];
			$this->articleList_qry = $this->pg_pdo->prepare($fqry, $options);
			
		} else {
			$fqry  = "select distinct a.arnr, coalesce(aenr,a.arnr) as aenr, wson, a.qgrp, qgid from art_0 a inner join web_art w using (arnr)
						left join art_best b on b.arnr = w.arnr and w.wsnr = :wsnr and b.qedt > :wsdt 
						left join cond_vk c on c.arnr = w.arnr and w.wsnr = :wsnr and (c.qvon > :wsdt or c.qedt > :wsdt) and c.qvon <= current_date and c.qbis > current_date and mprb >= 6 and cbez = 'PR01'
					  where  (wsnr = :wsnr and wsdt is not null )
	  				  union select distinct sl.arnr, coalesce(aenr,a2.arnr) as aenr, wson, a2.qgrp, qgid from art_0 a2 inner join web_art w on a2.arnr = w.arnr and w.wsnr = :wsnr
						inner join art_stl sl on sl.arnr = w.arnr 	
						inner join art_best b2 on b2.arnr = sl.astl and (b2.qedt > :wsdt or wsdt is null) 	
					   where  wsnr = :wsnr and ( wsdt is not null ) 
					  order by arnr
					";	
			$options = [ PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL ];
			$this->articleList_qry = $this->pg_pdo->prepare($fqry, $options);
			$this->articleList_qry->bindValue(':wsdt',$checkDate);
		}
		$this->articleList_qry->bindValue(':wsnr',$this->ShopwareWebshopNumber);
		
		$this->articleList_qry->execute() or die (print_r($this->articleList_qry->errorInfo()));
	}

    /*
     *  exportStock
     *  ToDo: Array to sw6
     */
    public function exportStock($api, $noupload = null) {

		if (!isset($this->articleList_qry) or ($this->articleList_qry == NULL)) {
			return(false);
		}

		$cnt = 0;
		$errorlist = '';
		// fill array and write to file
		while ($frow = $this->articleList_qry->fetch(PDO::FETCH_ASSOC )) {
			$cnt++;
			$article = new product($frow["arnr"]);
			$stocks = $article->getStocks($this->ShopwareStockCheckOrders);
			
			if (isset($_SESSION['debug']) and ($_SESSION['debug'] == 1) and ($_SESSION["level"] == 9)) {
				print "<br/>\nStockList: ".print_r($this->ShopwareStockList,1)."<br/>";
				print_r($stocks);
			}

			$stockSum = 0;
			if ($frow["wson"] == 1) {
				foreach($stocks as $stockNumber => $stockAmount ) { 
					if (in_array( $stockNumber , $this->ShopwareStockList)) {
						$stockSum += $stockAmount;
					} 
				}
			}
			
			$prices = $article->getPrices( true );

			if (isset($_SESSION['debug']) and ($_SESSION['debug'] == 1) and ($_SESSION["level"] == 9)) {
				print "<br/>\nPriceBase: ".$this->ShopwarePriceBase."<br/>";
				print_r($prices);
			}


			$restdata = [ "mainDetail" => [ 
					"instock" => $stockSum,
					"prices" => [ 
						0 => [	
							"customerGroupKey" => $this->ShopwarePriceGroup,
							"price" => $prices[$this->ShopwarePriceBase][0]["price"],
							"pseudoprice" => null
						]
					],
					'__options_prices' => ['replace' => false ] 
				]
			];
			if ( ! $noupload ) {
				try {
					$result = $api->put('articles/'.$frow['aenr'].'?useNumberAsId=true',  $restdata);
					if (isset($_SESSION['debug']) and ($_SESSION['debug'] == 1) and ($_SESSION["level"] == 9)) {
						print "<br/>\nUploadArray for #".$frow['aenr'].":<br/>";
						print_r($restdata);
						print "<br/>\Result for #".$frow['aenr'].":<br/>";
						print_r($result);
					}
				} catch (Exception $e) {
					 $errorlist .= $frow['arnr']."\t".$result["message"]."\n"; 
				}
			
			} else {
				$result = [ "success" => 0, "put" => 'articles/'.$frow['aenr'].'?useNumberAsId=true', "restdata" => $restdata, "json" => json_encode($restdata)];

			}
			
			if ($result["success"] == 1) {
			  $this->setUpdateTime($frow['arnr'], $frow['wson']);
			} else {
			  $errorlist .= $frow['arnr']."\t".$result["message"]."\n"; 
			}

		}
		
		return ['count' => $cnt , 'errors' => $errorlist];
		
	}

    /*
     * Shopware6 ready
     */
	public function updateSW6StockPrice($api, $noupload = null) {
	    
	    if (!isset($this->articleList_qry) or ($this->articleList_qry == NULL)) {
	        return(false);
	    }
	    
	    $cnt = 0;
	    $errorlist = '';
	    // fill array and write to file
	    $first = true; 
	    while ($frow = $this->articleList_qry->fetch(PDO::FETCH_ASSOC, $first ? PDO::FETCH_ORI_FIRST : PDO::FETCH_ORI_NEXT )) {
	    	if ($frow["qgid"] == null) {
	    		$webIdBase = $frow["arnr"];
	    	} else {
	    		$webIdBase =  $frow["qgid"];
	    	}
	    	Proto($frow["arnr"]." Start StockPriceUpdate");
	    	$first = false;
	        $cnt++;
	        $article = new product($frow["arnr"]);
	        //get article data
	        $artData = $article->getResultList()[0];
	        
	        $stocks = $article->getStocks();
	        $orders = $article->getOrderSum();
	        
	        
	        $this->debugData('StockList:'.$frow["arnr"], $stocks);
	        
	        $stockSum = 0; $orderSum = 0; $orderCnt = 0;
	        if ($frow["wson"] == 1) {
	            foreach($stocks as $stockNumber => $stockAmount ) {
	                if (in_array( $stockNumber , $this->ShopwareStockList)) {
	                    $stockSum += $stockAmount;
	                }
	                if (!empty($orders[$stockNumber])) {
	                	$orderSum += $orders[$stockNumber]['fmge'];
	                	$orderCnt += $orders[$stockNumber]['fcnt'];
	                }
	                
	            }

	            if ((!empty($this->dynamic_stock_upload["divisor"])) and (! in_array( $article->productData[0]["qgrp"] , $this->fullExternalStockGroup))) {
	            	$stockSum = floor($stockSum / $this->dynamic_stock_upload["divisor"]);
	            }
	            
	            // auf externes Lager nicht für Gelegenheitskäufe, nur bei hohen Umsatzerwartungen und vollen BestellVE im externen Lager zugreifen. 
	            foreach($stocks as $stockNumber => $stockAmount ) {
	            	Proto($frow["arnr"]."Check Extern: Stock ".$stockNumber.": ".$stockAmount." OrderSum:".$orderSum." OrderCnt:".$orderCnt." StockSum:".$stockSum);
	            	if ((in_array( $stockNumber , $this->ShopwareDynamicExternalStock)) and
	            			(! in_array( $stockNumber , $this->ShopwareStockList)) and 
	            			( ( ($orderCnt > 0) and (($stockSum+$orderSum) > 0)  ) or (in_array( $article->productData[0]["qgrp"] , $this->fullExternalStockGroup)) ) ) {
	            			Proto($frow["arnr"]." Check dynamic external stock amount");
            				$supplierData = $article->getDBFields("ablz,abln,abeh");
            				if (($supplierData["abln"] > 0) and ($supplierData["ablz"] > 1) and ($supplierData["abeh"] == 'Pal')){
            					$supplierPackUnit = $supplierData["ablz"] / $supplierData["abln"];
            				} else {
            					$supplierPackUnit = 100;
            				}
            				if ( ($stockAmount > $supplierPackUnit)) {
            					if (!empty($this->dynamic_stock_upload["divisor"]) ) {
            						$stockSum += floor($stockAmount / $this->dynamic_stock_upload["divisor"]);
            					} else {
            						$stockSum += $stockAmount;
            					}
            					Proto($frow["arnr"]." dynamic external stock used (".$stockAmount." ME)");
            				} else {
            					Proto($frow["arnr"]." dynamic external stock not used.");
            				}
	            	}
	            }
	            
	            //Verfügbarer Bestand in Shopware ist Bestand - offene Aufträge, deshalb Limit erhöhen, falls Bestand vorhanden 
	            if ( (!empty($this->dynamic_stock_upload["max"])) and ($stockSum > ($this->dynamic_stock_upload["max"] + $orderSum)) 
	            	and !(in_array( $article->productData[0]["qgrp"] , $this->fullExternalStockGroup))) {
	            	$stockSum = $this->dynamic_stock_upload["max"] + $orderSum;
	            }
	            Proto($frow["arnr"]." Stock Sum ".$stockSum." ME");
	            
	        }
	        $stockSum = floor($stockSum);
	        $prices = $article->getPrices( true , $this->shopware6NetPriceBase);
	        
	        $this->debugData('PriceBase:'.$frow["arnr"], $prices);
	        
	        $restdata = [ 
	        		"id" => md5($webIdBase),
	        		"productNumber" => $frow["arnr"],
	        		"stock" => $stockSum,
	        		"weight" => $artData["agew"],
			        "price" => [
			        		[
			        				"currencyId" => $this->ShopwareCurrencyId,
			        				"net"	=> $prices[$this->ShopwarePriceBase][0]["price"]/(1+$article->productData[0]["mmss"]/100),
			        				"gross" => $prices[$this->ShopwarePriceBase][0]["price"],
			        				"linked" => false
			        		]
			        ]
	        ];
	        
	        if ($this->shopware6SetMaxPurchaseToStock) {
	        	$restdata["maxPurchase"] = $stockSum;
	        }
	        
	        if ((!empty($this->shopware6DeliveryTimeIds["now"])) and ($stockSum > 0)) {
	        	$restdata["deliveryTimeId"] = $this->shopware6DeliveryTimeIds["now"];
	        }
	        if ((!empty($this->shopware6DeliveryTimeIds["later"])) and ($stockSum <= 0)) {
	        	$restdata["deliveryTimeId"] = $this->shopware6DeliveryTimeIds["later"];
	        }
	        
	        // other prices
	        if ($this->shopware6AlternatePrices) {
	        	foreach($prices as $priceTyp => $sprice) {
	        		$scnt = 0;
	        		foreach($sprice as $xprice) {
						$price = $xprice["price"];
						
					
						if (empty($xprice["from"])) {  $stafvon = 1;} else { $stafvon = $xprice["from"];}
						if (empty($xprice["to"])) {  $stafbis = null;} else { $stafbis = $xprice["to"];}
		        		if (($priceTyp == $this->shopware6NetPriceBase) and (! empty($price))) {
		        			//WWS group discount
		        			if (!empty($this->shopware6WGDisMatrix[$frow["qgrp"]])) {
		        				$price = $price * (1-$this->shopware6WGDisMatrix[$frow["qgrp"]]/100);
		        			}
		        			// incl tax
		        			$price *= (1+$article->productData[0]["mmss"]/100);
		        		}
		        		
		       			if (($priceTyp != $this->ShopwarePriceBase) and (! empty($price))) {
		       				if ($scnt++ > 0 ) { $sid = "_".$scnt;	} else { $sid = "";}
		       				$restdata["prices"][] = [
		       				        "id" => md5("WWS ".$priceTyp.$webIdBase.$sid),
		       						"productid" => md5($webIdBase),
		       						"rule" => [
		       								"id" => md5("WWS ".$priceTyp),
		       								"name" => "WWS ".$priceTyp,
		       								"priority" => 900
		       						],
		       						"quantityStart" => $stafvon,
		       						"quantityEnd" => $stafbis,
		       						"price" => [[
		       								"currencyId" => $this->ShopwareCurrencyId,
		       								"net"	=> $price/(1+$article->productData[0]["mmss"]/100),
		       								"gross" => $price,
		       								"linked" => true,
		       						]]
		       				];	
		       			}
	        		}
	        	}
	        }

	        if ( ! $noupload ) {
	            
	            $response = $this->SingleUpload($api, $restdata, "patch");
	            $errorlist .= $response;
	            if (empty($this->shopware6NoVisibilityUpdate)) {
		            if ($stockSum <= 0) {
		            	$this->setVisibility($api, $frow["arnr"],false, $frow["qgrp"]);
		            } else {
		            	$this->setVisibility($api, $frow["arnr"],true, $frow["qgrp"]);
		            }
	            }
	        } else {
	        	$result = [ "success" => 0, "put" => 'articles/'.$restdata["id"], "restdata" => $restdata, "json" => json_encode($restdata)];
	        	return ['count' => $cnt , 'errors' => print_r($result,1)];
	        }
	    }
	    
	    return ['count' => $cnt , 'errors' => $errorlist];
	    
	}

	public function setUpdateTime($article, $state = 1) {
		
		// select article list for export, create handle only for scaling up big artile lists
		$fqry  = "update web_art w set wsdt = :wsdt where w.wsnr = :wsnr and arnr = :arnr";	
		
		if ($state) {
			$updTime = date("Y-m-d H:i:s", $this->startTime);
		} else {
			$updTime = NULL;
		}
		
		$uploadDate_upd = $this->pg_pdo->prepare($fqry);
		$uploadDate_upd->bindValue(':wsnr',$this->ShopwareWebshopNumber);
		$uploadDate_upd->bindValue(':arnr',$article);
		$uploadDate_upd->bindValue(':wsdt',$updTime);
		
		$uploadDate_upd->execute() or die (print_r($uploadDate_upd->errorInfo()));

		return true;
	}
	
	public function newArticleList() {
	    $fqry  = "select distinct a.arnr, coalesce(aenr,a.arnr) as aenr, a.qgrp, wson,qgid from art_0 a inner join web_art w using (arnr)
					  where  wsnr = :wsnr and ( wson = 1 and wsdt is null )
					  order by arnr
					";
	    $this->articleList_qry = $this->pg_pdo->prepare($fqry);
    	$this->articleList_qry->bindValue(':wsnr',$this->ShopwareWebshopNumber);
	    $this->articleList_qry->execute() or die (print_r($this->articleList_qry->errorInfo()));
	}

	public function articleUpdateListBaseData() {
	    $fqry  = "select distinct a.arnr, coalesce(aenr,a.arnr) as aenr, a.qgrp, wson, qgid from art_0 a inner join web_art w using (arnr)
					  where  wsnr = :wsnr and ( wson = 1 and wsdt is not null )
                      and a.qedt > w.wsdt
					  order by arnr
					";
	    $options = [ PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL ];
	    $this->articleList_qry = $this->pg_pdo->prepare($fqry, $options);
	    $this->articleList_qry->bindValue(':wsnr',$this->ShopwareWebshopNumber);
	    $this->articleList_qry->execute() or die (print_r($this->articleList_qry->errorInfo()));
	}
	
	/*
	 * Shopware6 ready
	 */
	public function generateSW6Product($articleNumber, $type = "new", $webIdBase = null) {
		if (empty($webIdBase)) {
			$webIdBase = $articleNumber;
		}
        //get article base data
	    $article = new product($articleNumber, 'tradebyte');

        //get article data
        $artData = $article->getResultList()[0];
        
        //get article price data
        $prices = $article->getPrices( true , $this->shopware6NetPriceBase);

        $this->debugData('PriceBase:', ['PriceBase'=> $this->ShopwarePriceBase, 'Prices' => $prices]);
        
        //get article properties
        $properties = $article->getParameter();

        //get article pics
        $pictures = $article->getPictures( $type , $this->ShopwareWebshopNumber);
        
        //get article clp data
        if (!empty($this->shopware6LenzCLP)) {
        	$clpData = $article->getCLPData();
        } else {
        	$clpData = null;
        }
        // get discount group
        
        $discountGroup = $article->getDiscountGroup();
        
        //generate shopware formated array

        $restdata = [ 
        	"id" => md5($webIdBase),
            "productNumber" => $artData["arnr"],
            "ean" => $artData["asco"],
       	    "packUnit" => $artData["ageh"],  				        // SW Verpackungseinheit = ISO Verkaufsgebinde -> Facto Status Table id 9102
        	"referenceUnit" => '1',  								// Preismenge für den Grundpreis = static = 1 
        	"weight" => $artData["agew"],

            "tax" => [ 
                "id" => md5($artData["mmss"]),
                "taxRate" => $artData["mmss"],
                "name" => $artData["mmss"]."% Mwst"
               ],
        	"manufacturerNumber" => $artData["ahnr"]
        ];
        
        if (!empty($discountGroup)) {
        	$restdata['tags'] = [ 
						[
							"id" => md5("ptag".$discountGroup["id"]),
							"name" => 'ARG-'.$discountGroup["name"],
			        	]
					];
        }

        if (empty($this->shopware6NoCatUpdate) or ($type == "new")) {
	        $restdata["categories"] = [];
	        		
	        if (!empty($this->shopware6CategoryMatching[$artData["qgrp"]])) {
	        	 //$restdata["categoryIds"] =  $this->shopware6CategoryMatching[$artData["qgrp"]]	;
	        	foreach($this->shopware6CategoryMatching[$artData["qgrp"]]["categories"] as $cat) {
	        		$restdata["categories"][] =  [
	        										"id" =>  $cat
	        									 ];
	        	}
	        	
	        } else {
		        $restdata["categories"] = [
						        		[
						        				"id" => md5($artData["qgrp"]),
						        				"name" => $artData["gqsbz"],
						        				"type" => "page",
						        				"cmsPageId" => $this->shopwareCategoryCmsPageId,
						        		]
						        	  ];
		        if (!empty($this->shopware6CategoryMatchingFieldName)) {
		        	$restdata["categories"][0]["customFields"] = [
		        						$this->shopware6CategoryMatchingFieldName =>  $artData['qgrp']
		        												 ];
		        }
	        }
        }
        
        if (!empty($this->shopware6UpdateLinr) or ($type == "new")) {
        	
        	if (($this->shopware6UseHsnr) and ($artData['hsnr'] > 0) and (!empty($artData['hqsbz']))) {
        		$restdata["manufacturer"] = [
        				"id" =>  md5($artData['hsnr']),
        				"name" =>  $artData['hqsbz']
        		];
        		if (!empty($this->shopware6ManufactureCustomField)) {
        			$restdata["manufacturer"]["customFields"][$this->shopware6ManufactureCustomField] = $artData['hsnr'];
        		}
        	} else {
        		$restdata["manufacturer"] = [
        				"id" =>  md5($artData['linr']),
        				"name" =>  $artData['lqsbz'],
        		];
        		if (!empty($this->shopware6ManufactureCustomField)) {
        			$restdata["manufacturer"]["customFields"][$this->shopware6ManufactureCustomField] = $artData['linr'];
        		}
        	}
        	
        }
        
        // new uploads only
        if ($type == "new") {
        	
        	if (($this->shopware6AlternateProductname) and (strlen($artData['abz4']) > 5)) {
        		$restdata["name"] = $artData['abz4'];
        	} else {
            	$restdata["name"] = $artData['abz1']." ".$artData['abz2']." ".$artData['abz3'];
        	}
            $restdata["active"] = true;
            $restdata["description"] = $artData["atxt"];
            $restdata["stock"] = 0;
            //if stock sold out dont view product in shop 
            if ( $this->shopware6SetCloseout ) {
	            $restdata["isCloseout"] = true;
            } 
            if ($this->shopware6SetMaxPurchaseToStock) {
            	$restdata["maxPurchase"] = 0;
            }
            
            $restdata["visibilities"] = [];
            foreach ($this->shopware6DefaultVisibilities as $channelId) {
            	$restdata["visibilities"][] = [
            			"id" => md5($channelId.$webIdBase),
            			"salesChannelId" => $channelId,
            			"visibility" => 30
            	];
            }
            
        }
        
        if ( (! $this->shopware6NoPrices) or ($type == "new")) {
	        $restdata["price"] = [
					        		[
					        				"currencyId" => $this->ShopwareCurrencyId,
					        				"net"	=> $prices[$this->ShopwarePriceBase][0]["price"]/(1+$artData["mmss"]/100),
					        				"gross" => $prices[$this->ShopwarePriceBase][0]["price"],
					        				"linked" => false
					        		]
						        ];
        }
        // other prices
        // TODO: $frow["arnr"] not definined -> change to $article -> tets in Shopware
        
        if ($this->shopware6AlternatePrices) {
        	foreach($prices as $priceTyp => $sprice) {
        		$scnt = 0;
        		foreach($sprice as $xprice) {
        			$price = $xprice["price"];
        			
		        	if (($priceTyp == $this->shopware6NetPriceBase) and (! empty($price))) {
		        		//WWS group discount
		        		if (!empty($this->shopware6WGDisMatrix[$article->productData[0]["qgrp"]])) {
		        			$price = $price * (1-$this->shopware6WGDisMatrix[$article->productData[0]["qgrp"]]/100);
		        		}
		        		// incl tax
		        		$price *= (1+$article->productData[0]["mmss"]/100);
		        	}
		        	if (($priceTyp != $this->ShopwarePriceBase) and (! empty($price))) {
		        		if ($scnt++ > 0 ) { $sid = "_".$scnt;	} else { $sid = "";}
		        		$restdata["prices"][] = [
		        				"id" => md5("WWS ".$priceTyp. $webIdBase.$sid),
		        				"productid" => md5( $webIdBase),
		        				"rule" => [
		        						"id" => md5("WWS ".$priceTyp),
		        						"name" => "WWS ".$priceTyp,
		        						"priority" => 900
		        				],
		        				// "ruleId" => md5("WWS ".$priceTyp),
		        				"quantityStart" => $xprice["from"],
		        				"quantityEnd" => $xprice["to"],
		        				"price" => [[
		        						"id" => md5("price".$priceTyp. $webIdBase),
		        						"currencyId" => $this->ShopwareCurrencyId,
		        						"net"	=> $price/(1+$article->productData[0]["mmss"]/100),
		        						"gross" => $price,
		        						"linked" => true,
		        				]]
		        		];
		        		
		        	}
        		}
	        }
        }
        

        // Artikelatribute zum Beschreibungstext zusammensetzen bzw Eigenschaftsarray erstellen

        foreach ($properties as $key => $value ) {
            
            if (strpos($value,"<") !== false) {
                continue;
            }
            
            if ($value == 'True') {
                $value  = 'Ja';
            } elseif ($value == 'False') {
                $value  = 'Nein';
            }
            
            if (!empty($this->filterArray[$key]["filter"])) {
            	$filterable = true;
            } else {
            	$filterable = false;
            }
            
            if (strlen($value) > 255) {
                if ($type == "new") {
                    $restdata["description"] .= "\n".$value;
                }
            } else {
                $restdata["properties"][] = [
                    "id" => md5($key."-".$value),
                    "name" => $value,
                    "group" => [
                        "id" => md5($key),
                        "name" => $key,
                    	"filterable" => $filterable
                    ],
                ];
            }
        }

        // Base price  = VKPreis / Gebinde / Preisbasis / GPPreisDivisor
        // 
        $basePriceDiv = $artData["amgm"];
        $basePriceUnit = null;
        
        if ($basePriceDiv <> 1) {
            $basePriceUnit = $artData["ameh"];
        }
        if (!empty( $artData["apjs"])) {
            $basePriceDiv = $basePriceDiv / $artData["apjs"];
        }
        if (!empty( $artData["agpf"])) {
            $basePriceDiv = $basePriceDiv / $artData["agpf"];
            if (!empty($artData["ameg"])) {
                $basePriceUnit = $artData["ameg"];
            }
        }
        if (!empty($basePriceUnit) ) {

            //$restdata["unitId"] = md5($basePriceUnit);						// SW Maßeinheit = ID zur Grundpreiseinheit -> Facto Status Table  id 9101
            $restdata["purchaseUnit"] = $basePriceDiv;                      // GP Umrechnung unitID-Preis = referenceUnit-Preis / purchaseUnit
            $restdata["unit"] = [
                "id" => md5($basePriceUnit),
                "shortCode" => $basePriceUnit,
                "name" => $basePriceUnit
                ];
        } else {
        	$restdata["purchaseUnit"] = 1;
        	$restdata["unitId"] = null;
        }

        // Add Artikelbilder 
       
        $picUrls = [];
       
        foreach ($pictures as $picType => $piclist) {
            foreach ($piclist as $pictureUrl ) {
                $restdata["media"][] = [
//                    "mediaId" => md5($pictureUrl),
                    "id" => md5($pictureUrl),
                    "media" => [
                        "id" => md5($pictureUrl),
                    	"alt" => $artData["lqsbz"].' '.$artData["abz1"].' '.$artData["abz2"] ,
                    	"title" => $artData["abz1"],	
//                        "url" => $pictureUrl,
                        "mediaFolder" => [
                            "id" => md5($artData["qgrp"]),
                            "parentId" => $this->ShopwareMediaFolderId,
                            "name" => $artData["gqsbz"], 
                            "configuration" => [ 
                                "id" => md5("produkte")
                            ]
                            
                        ]
                    ]
                ];
                if ((strpos(strtolower($picType), "prim") !== false) or (strpos(strtolower($picType), "variant") !== false)) {
                    $restdata["coverId"] = md5($pictureUrl);
                }
                $picUrls[] = $pictureUrl;
            }
        }
     
        return [ "product" => $restdata, "mediaUrls" =>  $picUrls, "clpData" => $clpData];
	    
	}

	/*
	 * Shopware6 ready
	 */
	public function uploadSW6Media($api, $pictureUrl) {
	    $restdata = [
	        "url" => $pictureUrl
	    ];
	    
	    $urlinfo = pathinfo($pictureUrl);
	    $picName = preg_replace("/[^a-z0-9 \-\.]/i","",$urlinfo["basename"]);

	    if (empty($urlinfo["extension"])) {
	        $urlinfo["extension"] = 'jpg';
	    }
	    
	    $apiurl = '_action/media/'.md5($pictureUrl)."/upload?extension=".$urlinfo["extension"]."&fileName=".$picName;
	    
	    $result = $api->post($apiurl, $restdata ); 
	    $this->debugData('Mediaupload 2'.$apiurl, $result);
	}
	
	private function getClps($api) {
		$response = $api->get('lenz-platform-clp');   // Liste mit Sätzen
		
		$this->shopClpList = [];
		foreach($response["data"] as $clp) {
			$this->shopClpList[$clp["attributes"]["slug"]] = [
					"id" => $clp["id"],
					"name" => $clp["attributes"]["name"]
			];
		}
	}
	
	public function uploadSW6CLPData($api, $article, $clpData, $type = "new", $webIdBase = null) {
		
		if (empty($webIdBase)) {
			$webIdBase = $article;
		} 
		
		if (empty($this->shopware6LenzCLP)) {
			return FALSE;
		}
		
		if (empty($this->shopClpList)) {
			$this->getClps($api);
		}
		
		$productClps = [];
		if ($type != "new") {
			$response = $api->get('product/'.md5($webIdBase).'/extensions/lenzPlatformClp');  // Artikelzuordnung
			foreach($response["data"] as $productClp) {
				$productClps[] = $productClp["attributes"]["slug"];
			}
	
			foreach($productClps as $productClp ) {
				if (! in_array($productClp, $clpData)) {
					$response = $api->DELETE('product/'.md5($webIdBase).'/extensions/lenzPlatformClp/'.$this->shopClpList[$productClp]["id"]);
				}
			}
		}
		
		foreach($clpData as $clp ) {
			if (! in_array($clp, $productClps)) {
				$payload = [ "id" =>  $this->shopClpList[$clp]["id"] ];
				$response = $api->POST('product/'.md5($webIdBase).'/extensions/lenzPlatformClp/', $payload);
			}
		}
	}
	
	/*
	 * Shopware6 ready
	 */
	public function SingleUpload($api, $restdata, $type = "post") {
	    
	    try {
	        if ($type == "post") {
	            $result = $api->post('product', $restdata );
	        } elseif ($type == "patch") {
	        	$result = $api->patch('product/'.$restdata["id"], $restdata );
	        } elseif ($type == "delete") {
	            $result = $api->delete('product/'.$restdata["id"], $restdata );
	        }
	        
	        $this->debugData($type." product/".$restdata["id"], ["UploadArray" => $restdata, "Result" => $result]);
	    } catch (Exception $e) {
	        return $restdata["productNumber"]."\t".$result["message"]."\n";
	    }
	    
	    if (! empty($result["success"])) {
	        $this->setUpdateTime($restdata["productNumber"],1);
	    } else {
	    	$returnError = "Error Upload ".$restdata["productNumber"];
	        foreach ($result["errors"] as $error) {
	        	$returnError .= "\t".$error["detail"];
	        	if (!empty($error["source"]["pointer"])) {
	        		$returnError .= " (".$error["source"]["pointer"].") ";
	        	} else if (preg_match('/Expected command.*ProductDefinition/', $returnError)) {
	        		$this->setUpdateTime($restdata["productNumber"],0);
	        	}
	        }
	        Proto($restdata["productNumber"]." Upload Failed ".$returnError);
	        $returnError .= "\n";
	        return ( $returnError );
	    }
	}
	
	/*
	 * Shopware6 ready
	 */
	public function exportAllNew($api, $noUpload = null, $test = false) {
	    
	    if (!isset($this->startTime) or (!$this->startTime > 0)) {
	        $this->startTime = time();
	    }
	    
	    $errorList = '';
	    $articleList = '';
	    $this->newArticleList();
	    $cnt = 0;
	    while ($frow = $this->articleList_qry->fetch(PDO::FETCH_ASSOC )) {
	        $cnt++;
	        if ($frow["qgid"] == null) {
	        	$webIdBase = $frow["arnr"];
	        } else {
	        	print "QGID";
	        	$webIdBase =  $frow["qgid"];
	        }
	        if (! $noUpload) {
	           $productData = $this->generateSW6Product($frow["arnr"], "new", $webIdBase);
	           $articleList .= $productData["product"]["productNumber"]." ".$productData["product"]["name"]."\n";
	           $response = $this->SingleUpload($api, $productData["product"] );
	           if ((strpos($response,'already exists') > 0 ) 
	           		or (strpos($response,'UpdateCommand') > 0 )) {
	           	$productData = $this->generateSW6Product($frow["arnr"], "update", $webIdBase);
	           	$response = $this->SingleUpload($api, $productData["product"], "patch");
	           }
	           $errorList .= $response;
	            foreach ($productData["mediaUrls"] as $pictureUrl) {
	                $this->uploadSW6Media($api, $pictureUrl);
	            }
	            if (!empty($productData["clpData"])) {
	            	$this->uploadSW6CLPData($api, $frow["arnr"], $productData["clpData"], "new", $webIdBase);
	            }
	            
	        } else {
	           print "<pre>"; 
	           print_r($this->generateSW6Product($frow["arnr"]));
	           print "</pre>";
	        }
	        if (php_sapi_name() == 'cli') {
	        	print  date("Y-m-d H:i:s ")."Upload ".$cnt.": ".$frow["arnr"]."  ";
	        	if (strlen($response) > 1) { 
	        		print substr($response, 0, 100)."\n"; 
	        	} else {
	        		print "OK!\n";
	        	}
	        }
	        if ($test and ($cnt > 2)) { break; }

	    }
	    return ["count" => $cnt, "errors" => $errorList, "articleList" => $articleList];  
	}

	/*
	 * Shopware6 ready
	 */
	public function exportAllUpdates($api, $noUpload = null, $test = false) {

	    if (!isset($this->startTime) or (!$this->startTime > 0)) {
	        $this->startTime = time();
	    }
	    
	    $errorList = '';
	    $this->articleUpdateListBaseData();
	    $cnt = 0;
	    while ($frow = $this->articleList_qry->fetch(PDO::FETCH_ASSOC )) {
	        $cnt++;
	        if ($frow["qgid"] == null) {
	        	$webIdBase = $frow["arnr"];
	        } else {
	        	$webIdBase =  $frow["qgid"];
	        }
	        if (! $noUpload) {
	            $productData = $this->generateSW6Product($frow["arnr"], "update", $webIdBase);
	            $response = $this->SingleUpload($api, $productData["product"], "patch");
	            if (strpos($response,'InsertCommand') > 0 ) {
	            	$productData = $this->generateSW6Product($frow["arnr"], "new", $webIdBase);
	            	$response = $this->SingleUpload($api, $productData["product"], "post");
	            }
	            $errorList .= $response;
	            foreach ($productData["mediaUrls"] as $pictureUrl) {
	                $this->uploadSW6Media($api, $pictureUrl);
	            }
	            if (!empty($productData["clpData"])) {
	            	$this->uploadSW6CLPData($api, $frow["arnr"], $productData["clpData"], "update");
	            }
	            if (php_sapi_name() == 'cli') {
	            	print  date("Y-m-d H:i:s ")."Upload ".$cnt.": ".$frow["arnr"]."  ";
	            	if (strlen($response) > 1) {
	            		print substr($response, 0, 100)."\n";
	            	} else {
	            		print "OK!\n";
	            	}
	            }
	        } else {
	            print "<pre>";
	            print "Export ARNR ".$frow["arnr"]."\n";
	            print_r($this->generateSW6Product($frow["arnr"], "update", $webIdBase));
	            print "</pre>";
	        }
	        if ($test and ($cnt > 5)) { break; }
	        
	        
	    }
	    if ($this->shopware6NoPrices == false) {
	    	$this->updateSW6StockPrice($api, $noUpload);
	    }
	    
	    return ["count" => $cnt, "errors" => $errorList];
	}
	
	/*
	 * SW6 noch offen
	 */
	public function exportSW6Stock($api, $noupload = null) {
	}

	public function setVisibility($api, $articleId, $visibility, $wwsGroup, $webIdBase = null) {
		if (empty($webIdBase)) {
			$webIdBase = $articleId;
		} 
		//visible only if category in channel category tree
		if (!empty($this->shopware6CategoryMatching[$wwsGroup])) {
			$visibilityWish =  $this->shopware6CategoryMatching[$wwsGroup]["visibility"];
		} elseif (is_array($this->shopware6CategoryMatching)) {
			$visibilityWish = [];
		} else {
			$visibilityWish =  $this->shopware6Visibilities;
		}
		
		
		$visibilities = $api->get('product/'.md5($webIdBase).'/visibilities');
		
		$isVisibilities = [];
		
		foreach($visibilities["data"] as $checkvisbility) {
			if ( in_array($checkvisbility["attributes"]["salesChannelId"], $this->shopware6Visibilities)  
					and ((!$visibility) or (!in_array($checkvisbility["attributes"]["salesChannelId"], $visibilityWish))) ) {
				$api->delete('product-visibility/'.$checkvisbility["id"] );
			} elseif ( in_array($checkvisbility["attributes"]["salesChannelId"], $visibilityWish)  and ( $visibility )) {
				$isVisibilities[] = $checkvisbility["attributes"]["salesChannelId"];
			}
		}
		
		foreach($visibilityWish as $setVisibility) {
			if ( ! in_array($setVisibility, $isVisibilities) ) {
				
				$payload = [
						"id" => md5($setVisibility.$webIdBase),
						"productId" => md5($webIdBase),
						"salesChannelId" => $setVisibility,
						"visibility" => 30
				];
				$api->post('product-visibility/', $payload );
			}
		}
				
	}
	
	private function debugData($title, $values) {
	    if (isset($_SESSION['debug']) and ($_SESSION['debug'] == 1) and ($_SESSION["level"] == 9)) {
	        print "<pre>\n".$title."\n";
	        print_r($values);
	        print "\n</pre>";
	    }
	}

	public function setVariants($api, $mainArticle, $variants, $relationships) {
		
		$errorList = '';
		
		foreach($variants as $varArticle ) {
			$vrestdata = [
					"parentId" => $mainArticle,
			];

			foreach( $relationships[$varArticle] as $key => $value) {

				$configuratorSettings[] = [
						"id" => md5($mainArticle.$varArticle.$value),
						"productId" => $mainArticle,
						"optionId" => md5($key."-".$value)
				];
				
				$vrestdata["options"][] = [
						"id" => md5($key."-".$value),
						"name" => $value,
						"group" => [
								"id" => md5($key),
								"name" => $key
						],
				];
				
				
			}
			$result = $api->patch('product/'.md5($varArticle), $vrestdata );
			if (($_SESSION["debug"] ==1) and ($_SESSION["level"] == 9)) {
				print "<pre>";
				print_r($vrestdata);
				print "</pre>";
			}
			if ($result["success"] != 1) {
				foreach($result["errors"] as $error) {
					$errorList .= $varArticle." ".$error["detail"]."\n";
				}
			}
		}
		
		$restdata = [
				"configuratorSettings" => $configuratorSettings
		];
		
		$result = $api->patch('product/'.$mainArticle, $restdata );
		if (($_SESSION["debug"] ==1) and ($_SESSION["level"] == 9)) {
			print "<pre>";
			print_r($restdata);
			print "</pre>";
		}
		if ($result["success"] != 1) {
			foreach($result["errors"] as $error) {
				$errorList .= $mainArticle." ".$error["detail"]."\n";
			}
		}
		
		if (strlen($errorList > 0)) {
			return ["status" => false, errors => $errorList ];
		} else {
			return ["status" => true, "info" => "erfolgreich eingetragen!"];
		}
	}

	public function getArticle($id, $typ = "id", $references = []) {
		
		if(!$this->api) { return false; }
		
		if ($typ == "id") {
			$response = $this->api->get('product/'.$id);
		} else {
			$params = [
					'filter' => [
							[
									'type' => 'equals',
									'field' => $typ,
									'value' => $id
							]
					]
			];
			$response = $this->api->get('product/', $params);
			
		}
		
	
		if(!empty($response["data"][0]["id"])) {
			foreach($references as $reference) {
				$response["data"][0]["relationships"][$reference] = $this->api->get('product/'.$response["data"][0]["id"]."/".$reference);
				
			}
		}
		
		return $response;
	}
	
	public function getCategoryWWsMatch() {
		//get sales channels
		$channels = [];
		$result = $this->api->get('sales-channel');
		foreach ($result["data"] as $channel) {
		//	$channels[$channel["attributes"]["navigationCategoryId"]] = $channel["id"]; 
			$channels[$channel["id"]] = $channel["attributes"]["navigationCategoryId"];
		}
		//search first level categories
		$params = [
				'filter' => [
						[
								'type' => 'equals',
								'field' => 'level',
								'value' => '1'
						]
				]
		];
		$result = $this->api->get('category', $params);
		//map saleschannel - firstCat Name
		$breadcrumb = [];
		foreach ($result["data"] as $cat) {
			if (in_array($cat["id"], $channels)) {
				foreach($channels as $channel => $navCat) {
					if ($navCat == $cat["id"]) {
						$breadcrumb[$cat["attributes"]["name"]][] = $channel;
					}
				}
					 
			}
		}
		
		$result = $this->api->get('category');
		$catMapping = [];
		foreach ($result["data"] as $cat) {
			if (!empty($cat["attributes"]["customFields"][$this->shopware6CategoryMatchingFieldName])) {
				$category = $cat["attributes"]["customFields"][$this->shopware6CategoryMatchingFieldName];
				//add shopware categories to wwws category
				$catMapping[$category]["categories"][] = $cat["id"];
				//add shopware channelID to wwws category for visibility check
				foreach($breadcrumb[$cat["attributes"]["breadcrumb"][0]] as $channelId) {
					$catMapping[$category]["visibility"][] = $channelId;
				}
			}
		}
		if (!empty($catMapping)) {
			file_put_contents($this->shopware6CategoryMatchingFile, json_encode($catMapping));
		}
		
		return $catMapping;
	}
	
	public function setArticlesOnline() {
		
		// select article list for export, create handle only for scaling up big artile lists
		$fqry  = "insert into web_art (arnr, xxak, xyak, wsnr, wson)
					select distinct arnr, '','', :wsnr::int, 1 from
					art_best b inner join art_liefdok d using (arnr) inner join art_txt t using (arnr)
					where b.ifnr = 919 and amge > 0 and d.adtp = 91701
					on conflict do nothing";
		
		$setWebshop = $this->pg_pdo->prepare($fqry);

		$setWebshop->bindValue(':wsnr',$this->ShopwareWebshopNumber);

		$setWebshop->execute() or die (print_r($setWebshop->errorInfo()));
		
		return true;
		
		
		
	}

	public function setPropertyGroup($key,$filter) {
		
		$restdata = [
				"id" => md5($key),
				"name" => $key,
				"filterable" => $filter
		];
		$result = $this->api->patch('property-group/'.$restdata["id"], $restdata );
		if (! empty($result["success"])) {
			return true;
		} else {
			return false;
		}
	}
}
?>