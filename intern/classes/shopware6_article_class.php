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
	private $api; 
	
	public function __construct($api = null) {
		
		include ("./intern/config.php");
		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		$this->ShopwareWebshopNumber = $ShopwareWebshopNumber;
		$this->ShopwarePriceGroup = $ShopwarePriceGroup;
		$this->ShopwarePriceBase = $ShopwarePriceBase;
		$this->ShopwareCurrencyId = $ShopwareCurrencyId;
		$this->ShopwareStockList = $ShopwareStockList;
		$this->ShopwareMediaFolderId = $ShopwareMediaFolderId;
		$this->dynamic_stock_upload = $dynamic_stock_upload;
		$this->shopwareCategoryCmsPageId = $shopwareCategoryCmsPageId;
		$this->api = $api;
		return true;
	}
	
	public function articleUpdateList($checkDate = NULL) {
		
		if (!isset($this->startTime) or (!$this->startTime > 0)) {
			$this->startTime = time();
		}

		// select article list for export, create handle only for scaling up big artile lists
		// if no CheckDate set, select only lines newer then last upload
		if (($checkDate == NULL) or ( strtotime($checkDate) === FALSE)) {
			$fqry  = "select distinct a.arnr, coalesce(aenr,a.arnr) as aenr, wson from art_0 a inner join web_art w on a.arnr = w.arnr and w.wsnr = :wsnr
						left join art_best b on b.arnr = w.arnr and (b.qedt > w.wsdt or wsdt is null)
						left join cond_vk c on c.arnr = w.arnr and (c.qvon > w.wsdt or wsdt is null) and c.qvon <= current_date and c.qbis > current_date and mprb = 6 and cbez = 'PR01'
					  where  wsnr = :wsnr and ( wson = 1 or (wson = 0 and wsdt is not null )) 
					    and ( b.qedt is not null or c.qbis is not null  or wson = 0 )
					  order by arnr
					";	
			$this->articleList_qry = $this->pg_pdo->prepare($fqry);
		} else {
			$fqry  = "select distinct a.arnr, coalesce(aenr,a.arnr) as aenr, wson from art_0 a inner join web_art w using (arnr)
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
			$stocks = $article->getStocks();
			
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
							"price" => $prices[$this->ShopwarePriceBase],
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
     * Shopware6 read
     */
	public function updateSW6StockPrice($api, $noupload = null) {
	    
	    if (!isset($this->articleList_qry) or ($this->articleList_qry == NULL)) {
	        return(false);
	    }
	    
	    $cnt = 0;
	    $errorlist = '';
	    // fill array and write to file
	    while ($frow = $this->articleList_qry->fetch(PDO::FETCH_ASSOC )) {
	        $cnt++;
	        $article = new product($frow["arnr"]);
	        $stocks = $article->getStocks();
	        
	        $this->debugData('StockList:'.$frow["arnr"], $stocks);
	        
	        $stockSum = 0;
	        if ($frow["wson"] == 1) {
	            foreach($stocks as $stockNumber => $stockAmount ) {
	                if (in_array( $stockNumber , $this->ShopwareStockList)) {
	                    $stockSum += $stockAmount;
	                }
	            }
	            
	            // 
	            if (!empty($this->dynamic_stock_upload["divisor"]) ) {
	            	$stockSum = floor($stockSum / $this->dynamic_stock_upload["divisor"]);
	            }

	            if ( (!empty($this->dynamic_stock_upload["max"])) and ($stockSum > $this->dynamic_stock_upload["max"])) {
	            	$stockSum = $this->dynamic_stock_upload["max"];
	            }
	            
	            
	        }
	        $prices = $article->getPrices( true );
	        
	        $this->debugData('PriceBase:'.$frow["arnr"], $prices);
	        
	        $restdata = [ 
	        		"id" => md5($frow["arnr"]),
	        		"productNumber" => $frow["arnr"],
	        		"stock" => $stockSum,
			        "price" => [
			        		[
			        				"currencyId" => $this->ShopwareCurrencyId,
			        				"net"	=> $prices[$this->ShopwarePriceBase]/(1+$article->productData[0]["mmss"]/100),
			        				"gross" => $prices[$this->ShopwarePriceBase],
			        				"linked" => false
			        		]
			        ]
	        ];
	        
	        // other prices
        	foreach($prices as $priceTyp => $price) {
       			if (($priceTyp != $this->ShopwarePriceBase) and (! empty($price))) {
       				$restdata["prices"][] = [
       				        "id" => md5("WWS ".$priceTyp.$frow["arnr"]),
       						"productid" => md5($frow["arnr"]),
       						"rule" => [
       								"id" => md5("WWS ".$priceTyp),
       								"name" => "WWS ".$priceTyp,
       								"priority" => 900
       						],
#       						"versionId" => md5("version".$priceTyp.$frow["arnr"]),
#       						"productVersionId" => md5("productVersion".$priceTyp.$frow["arnr"]),
       						//     						"ruleId" => md5("WWS ".$priceTyp),
       						"quantityStart" => 1,
       						"price" => [[
#      								"id" => md5("price".$priceTyp.$frow["arnr"]),
       								"currencyId" => $this->ShopwareCurrencyId,
       								"net"	=> $price/(1+$article->productData[0]["mmss"]/100),
       								"gross" => $price,
       								"linked" => true,
       						]]
       				];
        				
       			}
        	}

	        if ( ! $noupload ) {
	            
	            $this->SingleUpload($api, $restdata, "patch");
	            
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
	    $fqry  = "select distinct a.arnr, coalesce(aenr,a.arnr) as aenr, wson from art_0 a inner join web_art w using (arnr)
					  where  wsnr = :wsnr and ( wson = 1 and wsdt is null )
					  order by arnr
					";
	    $this->articleList_qry = $this->pg_pdo->prepare($fqry);
    	$this->articleList_qry->bindValue(':wsnr',$this->ShopwareWebshopNumber);
	    $this->articleList_qry->execute() or die (print_r($this->articleList_qry->errorInfo()));
	}

	public function updateArticleList() {
	    $fqry  = "select distinct a.arnr, coalesce(aenr,a.arnr) as aenr, wson from art_0 a inner join web_art w using (arnr)
					  where  wsnr = :wsnr and ( wson = 1 and wsdt is not null )
                      and a.qedt > w.wsdt
					  order by arnr
					";
	    $this->articleList_qry = $this->pg_pdo->prepare($fqry);
	    $this->articleList_qry->bindValue(':wsnr',$this->ShopwareWebshopNumber);
	    $this->articleList_qry->execute() or die (print_r($this->articleList_qry->errorInfo()));
	}
	
	/*
	 * Shopware6 ready
	 */
	public function generateSW6Product($articleNumber, $type = "new") {
	    
        //get article base data
	    $article = new product($articleNumber, 'tradebyte');

        //get article data
        $artData = $article->getResultList()[0];
        
        //get article price data
        $prices = $article->getPrices( true );

        $this->debugData('PriceBase:', ['PriceBase'=> $this->ShopwarePriceBase, 'Prices' => $prices]);
        
        //get article properties
        $properties = $article->getParameter();

        //get article pics
        $pictures = $article->getPictures();
        
        //generate shopware formated array

        $restdata = [ 
            "id" => md5($artData['arnr']),
            "productNumber" => $artData["arnr"],
            "ean" => $artData["asco"],
       	    "packUnit" => $artData["ageh"],  				        // SW Verpackungseinheit = ISO Verkaufsgebinde -> Facto Status Table id 9102
        	"referenceUnit" => '1',  								// Preismenge für den Grundpreis = static = 1 
        	"price" => [
        			[ 
        			    "currencyId" => $this->ShopwareCurrencyId,
        			    "net"	=> $prices[$this->ShopwarePriceBase]/(1+$artData["mmss"]/100),
        			    "gross" => $prices[$this->ShopwarePriceBase],
        				"linked" => false
        			]
        	],

            "tax" => [ 
                "id" => md5($artData["mmss"]),
                "taxRate" => $artData["mmss"],
                "name" => $artData["mmss"]."% Mwst"
               ],
            "categories" => [
                [
        	        "id" => md5($artData["qgrp"]),
                    "name" => $artData["gqsbz"], 
                    "type" => "page",
                	"cmsPageId" => $this->shopwareCategoryCmsPageId
                ]
            ],
            "manufacturer" => [
               "id" =>  md5($artData['linr']),
               "name" =>  $artData['lqsbz'],
            ]
        ];
        
        // new uploads only
        if ($type == "new") {
            $restdata["name"] = $artData['abz1']." ".$artData['abz2']." ".$artData['abz3'];
            $restdata["active"] = true;
            $restdata["description"] = $artData["atxt"];
            $restdata["stock"] = 0;
        }
        
        // other prices
        foreach($prices as $priceTyp => $price) {
        	if (($priceTyp != $this->ShopwarePriceBase) and (! empty($price))) {
        		$restdata["prices"][] = [
        				"id" => md5("WWS ".$priceTyp.$frow["arnr"]),
#        				"versionId" => md5("version".$priceTyp.$frow["arnr"]),
#        				"productVersionId" => md5("productVersion".$priceTyp.$frow["arnr"]),
        				"rule" => [
        						"id" => md5("WWS ".$priceTyp),
        						"name" => "WWS ".$priceTyp,
        						"priority" => 900
        				],
        				
        				//     						"ruleId" => md5("WWS ".$priceTyp),
        				"quantityStart" => 1,
        				"price" => [[
        						"id" => md5("price".$priceTyp.$frow["arnr"]),
        						"currencyId" => $this->ShopwareCurrencyId,
        						"net"	=> $price/(1+$article->productData[0]["mmss"]/100),
        						"gross" => $price,
        						"linked" => true,
        				]]
        		];
        		
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
                    	"filterable" => false
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
     
        return [ "product" => $restdata, "mediaUrls" =>  $picUrls];
	    
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
	        
	        $this->debugData("Upload  product/".$restdata["id"], ["UploadArray" => $restdata, "Result" => $result]);
	    } catch (Exception $e) {
	        return $restdata["productNumber"]."\t".$result["message"]."\n";
	    }
	    
	    if (! empty($result["success"])) {
	        $this->setUpdateTime($restdata["productNumber"],1);
	    } else {
	        $returnError = '';
	        foreach ($result["errors"] as $error) {
	            $returnError .= $restdata["productNumber"]."\t".$error["detail"]."\n";
	        }

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
	    while ($frow = $this->articleList_qry->fetch(PDO::FETCH_ASSOC )) {
	        $cnt++;
	        if (! $noUpload) {
	           $productData = $this->generateSW6Product($frow["arnr"]);
	           $articleList .= $productData["product"]["productNumber"]." ".$productData["product"]["name"]."\n";
	           $response = $this->SingleUpload($api, $productData["product"] );
	           $errorList .= $response;
	            foreach ($productData["mediaUrls"] as $pictureUrl) {
	                $this->uploadSW6Media($api, $pictureUrl);
	            }
	        } else {
	           print "<pre>"; 
	           print_r($this->generateSW6Product($frow["arnr"]));
	           print "</pre>";
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
	    $this->updateArticleList();
	    while ($frow = $this->articleList_qry->fetch(PDO::FETCH_ASSOC )) {
	        $cnt++;
	        if (! $noUpload) {
	            $productData = $this->generateSW6Product($frow["arnr"], "update");
	            $errorList .= $this->SingleUpload($api, $productData["product"], "patch");
	            foreach ($productData["mediaUrls"] as $pictureUrl) {
	                $this->uploadSW6Media($api, $pictureUrl);
	            }
	        } else {
	            print "<pre>";
	            print_r($this->generateSW6Product($frow["arnr"]));
	            print "</pre>";
	        }
	        if ($test and ($cnt > 5)) { break; }
	    }
	    return ["count" => $cnt, "errors" => $errorList];
	}
	
	/*
	 * SW6 noch offen
	 */
	public function exportSW6Stock($api, $noupload = null) {
	}
	
	private function debugData($title, $values) {
	    if (isset($_SESSION['debug']) and ($_SESSION['debug'] == 1) and ($_SESSION["level"] == 9)) {
	        print "<br/>\n".$title."<br/>";
	        print_r($values);
	    }
	}

	public function setVariants($api, $mainArticle, $variants, $relationships) {
		
		$errorList = '';
		
		foreach($variants as $varArticle ) {
			$vrestdata = [
					"parentId" => md5($mainArticle),
			];

			foreach( $relationships[$varArticle] as $key => $value) {

				$configuratorSettings[] = [
						"id" => md5($mainArticle.$varArticle.$value),
						"productId" => md5($mainArticle),
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
		
		$result = $api->patch('product/'.md5($mainArticle), $restdata );
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
	
	
}
?>