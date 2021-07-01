<?php

class order {
		
	private $belegId;
	private $pg_pdo;
	private $itemPointer;
	
	private $itemByPackPointer;
	private $itemsByPack;
	private $totalQuantity;
	private $api;
	
	public $orderHeader;
	public $orderItems;
	public $MainItemCount;
	public $ItemCount;

	public $orderWeight;
	
	// Belegdaten aus Datenbank komplett in ein Array einlesen
	/*
	 * TODO Evtl. statt positionen Summen je Artikelnummer/Bezeichnung?! 
	 */
	public function __construct($belegnummer) {

		include ("./intern/config.php");

		$this->belegId = $belegnummer;

		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		
		//Belegkopfdaten einlesen
		$oqry  = 'select * from auftr_kopf where fblg = :BelegID';

		$r_qry = $this->pg_pdo->prepare($oqry);
		$r_qry->bindValue(':BelegID', $belegnummer);
		$r_qry->execute() or die (print_r($r_qry->errorInfo()));

		$this->orderHeader = $r_qry->fetch( PDO::FETCH_ASSOC );
		if ($this->orderHeader == false) {
			return NULL;
		}
		if (preg_match('/[0-9]+[ ]{0,2}[a-z\-]{0,2}$/i',trim($this->orderHeader["qstr"]),$hnMatches)) {
			$this->orderHeader["qstr"] = substr(trim($this->orderHeader["qstr"]),0,(-1)*strlen($hnMatches[0]));
			$this->orderHeader["qstrNumber"] = $hnMatches[0]; 
		}

		// Belegpositionen einlesen
		$iqry  = 'select p.*, a.aart, a.agew as gewicht from auftr_pos p left join art_0 a using (arnr) 
                  where fblg = :BelegID and coalesce(avsd,0) = 0
                  order by fpos';

		$r_qry = $this->pg_pdo->prepare($iqry);
		$r_qry->bindValue(':BelegID', $belegnummer);
		$r_qry->execute() or die (print_r($r_qry->errorInfo()));

		$this->MainItemCount = 0;
		$this->ItemCount = 0;
		$this->OrderWeight = 0;
		$mainidx = 0;
		while ($row = $r_qry->fetch( PDO::FETCH_ASSOC )) {
		    
		    if (empty($row["agew"])) {
		        $row["agew"] = $row["gewicht"];
		    }

			$this->ItemCount++;
			$this->OrderWeight += $row["agew"]*$row["fmge"];
			$idx = $row["fpos"];
			
			if ($row["fart"] == 6 ) {
				
				$this->orderItems[$mainidx]["sliste"][$idx] = [ 
						"arnr" => $row["arnr"], 
						"abz1" => $row["abz1"]." ".$row["abz2"], 
						"fmge" => $row["fmge"],
						"fmgl" => $row["fmgl"], 
						"ameh" => $row["ameh"] ,  
						"agew" => $row["agew"] 
				];
				$this->orderItems[$mainidx]["astl"] = 1;
				
				if ($this->orderItems[$mainidx]["agew"] == 0 ) {
					$this->OrderWeight += $row["agew"]*$row["fmge"];
				}
				
			} else {
				$mainidx = $row["fpos"];
				$this->orderItems[$idx] = $row;
				$this->orderItems[$idx]["astl"] = null;
				$this->MainItemCount++;
				
			}
		}
		$this->itemPointer = 0;
		return $this->MainItemCount;

	}

	public function checkItemQuantity($Artikel) {
		
		$cntqry  = 'select sum(fmge) as Menge, sum(fmgl) as PackMenge from auftr_pos where fblg = :BelegID and arnr = :Artikel';
		$cnt_qry = $this->pg_pdo->prepare($cntqry);
		$cnt_qry->bindValue(':BelegID', $this->belegId);
		$cnt_qry->bindValue(':Artikel', $Artikel);
		$cnt_qry->execute() or die (print_r($cnt_qry->errorInfo()));
		$cnt_row = $cnt_qry->fetch( PDO::FETCH_ASSOC );

		return [ "Menge" => $cnt_row["menge"], "PackMenge" => $cnt_row["packmenge"], "Restmenge" => ($cnt_row["menge"]-$cnt_row["packmenge"]) ];

	}

	public function checkItemTyp($Artikel) {
		
		$cntqry  = "select arnr, fart as typ from auftr_pos where fblg = :BelegID and arnr = :Artikel group by arnr,fart";
					
		$cnt_qry = $this->pg_pdo->prepare($cntqry);
		$cnt_qry->bindValue(':BelegID', $this->belegId);
		$cnt_qry->bindValue(':Artikel', $Artikel);
		$cnt_qry->execute() or die (print_r($cnt_qry->errorInfo()));
		$cnt_row = $cnt_qry->fetchAll( PDO::FETCH_ASSOC );
		
		return $cnt_row;

	}
	
	// gepackte Menge des Artikels um 1 erhöhen
	// ToDo Eigenes Array aktualisieren
	public function setPacked($Artikel, $packId = NULL) {
		
		$pqry  = 'update auftr_pos set fmgl = coalesce(fmgl,0)+1 where fblg = :BelegID and arnr = :Artikel
                    and fpos = (select min(fpos) from auftr_pos where fblg = :BelegID and arnr = :Artikel and fart = 1)
                 ';
		$psqry  = 'update auftr_pos set fmgl = coalesce(fmgl,0)+1 where fblg = :BelegID and arnr = :Artikel
                    and fpos = (select min(fpos) from auftr_pos where fblg = :BelegID and arnr = :Artikel and fart = 6)
                   returning fpos
                 ';
		$pkqry  = 'update auftr_pos set fmgl = fmge  where fblg = :BelegID 
					and fpos = (select max(fpos) from auftr_pos where fblg = :BelegID and fart = 1 and fpos < :fpos)';
		
		$poqry  = 'update auftr_kopf set ktos = 1 where fblg = :BelegID ';
		
		$csqry  = 'select count(*) as cnt from auftr_pos where coalesce(fmgl,0) < fmge and fblg = :BelegID and fart = 6
                    and fpos > (select max(fpos) from auftr_pos where fblg = :BelegID and fart <> 6 and fpos < :fpos)
                    and fpos < coalesce((select min(fpos) from auftr_pos where fblg = :BelegID and fart <> 6 and fpos > :fpos),999)
                  ';


		
		$p_qry = $this->pg_pdo->prepare($pqry);
		$ps_qry = $this->pg_pdo->prepare($psqry);
		$pk_qry = $this->pg_pdo->prepare($pkqry);
		$po_qry = $this->pg_pdo->prepare($poqry);
		$cs_qry = $this->pg_pdo->prepare($csqry);


		if ($this->checkItemQuantity($Artikel)["Restmenge"] > 0) {
			
			$itemTyp = $this->checkItemTyp($Artikel);
			$OrgArtikel = $Artikel;

			if ($itemTyp[0]["typ"] == 6) {
				$SubArtikel = $Artikel;
				$Artikel = $itemTyp[0]["arnr"];
			} else {
				$SubArtikel = NULL;
			}
		

			
			if ($SubArtikel != NULL) {
				// Subartikel erhöhen und prüfen ob Stückliste komplett gepackt
				$ps_qry->bindValue(':BelegID', $this->belegId);
				$ps_qry->bindValue(':Artikel', $SubArtikel);
				$ps_qry->execute() or die (print_r($ps_qry->errorInfo()));
				$fpos = $ps_qry->fetch( PDO::FETCH_ASSOC);

				//Prüfen ob Stückliste vollständig 
				// TODO
				$cs_qry->bindValue(':BelegID', $this->belegId);
				$cs_qry->bindValue(':fpos', $fpos['fpos']);
				$cs_qry->execute() or die (print_r($cs_qry->errorInfo()));
				$csrow = $cs_qry->fetch( PDO::FETCH_ASSOC );
				
				//Stückliste als gepackt kennzeichnen
				// TODO
				if ($csrow["cnt"] == 0) {
					$pk_qry->bindValue(':BelegID', $this->belegId);
					//$pk_qry->bindValue(':Artikel', $Artikel);
					$pk_qry->bindValue(':fpos', $fpos['fpos']);
					$pk_qry->execute() or die (print_r($pk_qry->errorInfo()));
				}

			} else {
				// Standardartikel gepackt
				$p_qry->bindValue(':BelegID', $this->belegId);
				$p_qry->bindValue(':Artikel', $Artikel);

				$p_qry->execute() or die (print_r($p_qry->errorInfo()));

			}

			$packedState = $this->getPackedState(); 
			// Beleg als gepackt kennzeichnen
			if ($packedState == 1) {
				$po_qry->bindValue(':BelegID', $this->belegId);
				$po_qry->execute() or die (print_r($po_qry->errorInfo()));
				$orderpack = 'packed';
			} else {
				$orderpack = '';
			}
			
			// Artikelflag komplett gepackt setzen
			if ($this->checkItemQuantity($OrgArtikel)["Restmenge"] == 0) {
				$packed = 'packed';
			} else {
				$packed = 'partpacked';
			}
			return [  "itemId" => $OrgArtikel, 
					  "status" => true, "itemPacked" => $packed, 
					  "packedAmount" =>  $this->checkItemQuantity($OrgArtikel)["PackMenge"], 
					  "info" => "Artikel gepackt!", 
					  "orderPacked" => $orderpack, 
					  "packId" => $packId
					];
			
		} else {
			$packedState = $this->getPackedState();
			// Beleg als gepackt kennzeichnen
			if ($packedState == 1) {
				$po_qry->bindValue(':BelegID', $this->belegId);
				$po_qry->execute() or die (print_r($po_qry->errorInfo()));
				$orderpack = 'packed';
			} else {
				$orderpack = '';
			}
			
			return [ "status" => false, "info" => "falscher Artikel für diese Bestellung!\nBitte prüfen Sie Menge und Art!"];
		}
	}

	public function getPackedState() {
		$coqry  = "select coalesce(ktos,0), count(*) as cnt from auftr_kopf k inner join auftr_pos p using (fblg)
					inner join art_0 a using (arnr)
					inner join const.positionflagx c on qnum = 'FAKX_VersandArt' and (coalesce(p.fakx,0) & c.fakx) = 0
                    where coalesce(fmgl,0) < fmge and fblg = :BelegID group by ktos";
		$co_qry = $this->pg_pdo->prepare($coqry);
		
		// Prüfen ob Beleg vollständig gepackt
		$co_qry->bindValue(':BelegID', $this->belegId);
		$co_qry->execute() or die (print_r($co_qry->errorInfo()));
		$corow = $co_qry->fetch( PDO::FETCH_ASSOC );
		
		if ( ($corow["cnt"] == 0) and ($corow["ktos"] < 1)) {
			$poqry  = 'update auftr_kopf set ktos = 1 where fblg = :BelegID ';
			$po_qry = $this->pg_pdo->prepare($poqry);
			$po_qry->bindValue(':BelegID', $this->belegId);
			$po_qry->execute() or die (print_r($po_qry->errorInfo()));
			
			return 1;
		}
		return $corow["ktos"];

		
	}
	// nächste Position incl. Stückliste, fortlaufend
	public function getNextItem() {
		$liste = [];
		if ($this->itemPointer < $this->MainItemCount) {
		    
			$liste[] = $this->orderItems[$this->itemPointer];
			
			if ($this->orderItems[$this->itemPointer]["astl"] == 1) {
				
				foreach($this->orderItems[$this->itemPointer]["sliste"] as $spos) {
	
					$liste[] = ["arnr"=>$spos["arnr"],
							"abz1"=> $spos["abz1"],
							"fmge"=>$spos["fmge"],
							"ameh"=> $spos["ameh"],
							"agew"=> $spos["gewicht"],
							"fmgl"=> $spos["fmgl"],
							"astl"=> 2 ];
				}
				
			}
			$this->itemPointer++;
			return $liste;
		} else {
			return NULL;
		}

	}

	public function setPackWeigth($Artikel, $Weight) {

	}

	public function setAdress($Name, $Adresse2,$HNummer, $Adresse1 = '', $PLZ, $Ort, $Land) {
		
	/*	$addrqry  = "update versand set Name = :Name, Adresse1 = :Adresse1, Adresse2 = :Adresse2, HNummer = :HNummer, PLZ = :PLZ, Ort = :Ort, Land = :Land where BelegID = :BelegID";
			
		$addr_qry = $this->pg_pdo->prepare($addrqry);
		$addr_qry->bindValue(':BelegID', $this->belegId);
		$addr_qry->bindValue(':Name', utf8_decode($Name));
		$addr_qry->bindValue(':Adresse1',  utf8_decode($Adresse1));
		$addr_qry->bindValue(':HNummer', $HNummer);
		$addr_qry->bindValue(':Adresse2',  utf8_decode($Adresse2));
		$addr_qry->bindValue(':PLZ', $PLZ);
		$addr_qry->bindValue(':Ort',  utf8_decode($Ort));
		$addr_qry->bindValue(':Land', $Land);
		$addr_qry->execute() or die (print_r($addr_qry->errorInfo()));
		
		$this->orderHeader["Name"] = $Name;
		$this->orderHeader["Adresse2"] = $Adresse2;
		$this->orderHeader["Adresse1"] = $Adresse1;
		$this->orderHeader["HNummer"] = $HNummer;
		$this->orderHeader["PLZ"] = $PLZ;
		$this->orderHeader["Ort"] = $Ort;
		$this->orderHeader["Land"] = $Land;
	*/		
		return true;
		
	}

	public function getOrderId() {
		return $this->belegId;
	}

	public function getShippingBlueprint() {
	    
	    include ("./intern/config.php");
	    $api = new OpenApi3Client($shopware6_url, $shopware6_user, $shopware6_key);
	    
	    $orderId = $this->checkShopwareOrderId($this->orderHeader["qsbz"]);
	    
	    //$response = $api->post('_action/order/'.$orderId.'/create-shipment-blueprint');
	    $response = $api->post('_action/pickware-shipping/shipment/create-shipment-blueprint-for-order',['orderId' => $orderId]);
	    $response["senderAddress"]["firstName"] = $_SESSION["senderAddress"]["firstName"];
	    $response["senderAddress"]["lastName"] = $_SESSION["senderAddress"]["lastName"];
	    $response["senderAddress"]["street"] = $_SESSION["senderAddress"]["street"];
	    $response["senderAddress"]["houseNumber"] = $_SESSION["senderAddress"]["houseNumber"];
	    $response["senderAddress"]["city"] = $_SESSION["senderAddress"]["city"];
	    $response["senderAddress"]["zipCode"] = $_SESSION["senderAddress"]["zipCode"];
	    $response["senderAddress"]["countryIso"] = $_SESSION["senderAddress"]["countryIso"];
	    
	    unset($response["codAmount"]);
	    unset($response["identCheckGivenName"]);
	    unset($response["identCheckSurname"]);
	    unset($response["identCheckDateOfBirth"]);
	    unset($response["identCheckMinimumAge"]);
	    unset($response["endorsement"]);
	    unset($response["frankatur"]);
	    unset($response["incoterm"]);
	    
	    
	    return $response;
	}
	
	public function exportShipping($parcelData = null) {
	    
	    include ("./intern/config.php");
	    $api = new OpenApi3Client($shopware6_url, $shopware6_user, $shopware6_key);

	    $orderId = $this->checkShopwareOrderId($this->orderHeader["qsbz"]);
	    
/*		foreach ($parcelData as $pack) {
			print $pack["parcelService"]."/".$pack["packWeight"]."<br>";
			if ($pack["parcelService"] == "DHL") {
				$dhl["Gewicht"] = $pack["packWeight"];
*/
	           $send = [ "shipmentBlueprint" =>  $_SESSION["shipBlueprint"] , "orderId" => $orderId] ;
	            if (DEBUG) { 
	            	print "<pre>".print_r($send,1)."</pre>";
	            	$filename ="./docs/label_test.pdf";
	            	print "<a href=$filename >$filename</a>".LR;
	            } else {
		            //$response = $api->post('_action/order/'.$orderId.'/create-shipment', $send);
		            $response = $api->post('_action/pickware-shipping/shipment/create-shipment-for-order', $send );
		            //print "<br>".json_encode($send)."<br>";
		            print "Create Shipment...";
		            if ( isset($response["errors"]) ) {
		                $errorList = '';
		                foreach ($response["errors"] as $error) {
		                    $errorList .= $error["detail"]."\n";
		                }
	                    return ["status" => false, "error" => $errorList ]; 
		            }
		            print "<pre>".print_r($response,1)."</pre>";
		            $shippingId =  $response["successfullyOrPartlySuccessfullyProcessedShipmentIds"][0];
		            
		            $response = $api->get('pickware-shipping-shipment/'.$shippingId.'/documents');
	
		            foreach ($response["data"] as $document) {
		            	$documentId = $document["id"];
		            	$deepLinkId = $document["attributes"]["deepLinkCode"];
		
	            		$response = $api->get('pickware-document/'.$documentId.'/contents?deepLinkCode='.$deepLinkId );
		            	$filename ="./docs/label_".$this->belegId."_".uniqid().".pdf";
		            	file_put_contents($filename , $response["result"]);
	            		exec('lp -d pak-prn01 "'.$filename.'"'); 
		            }
	            }
	
	/*		}
		}
*/
		$parcelqry = "update auftr_kopf set ktos=2 where fblg = :BelegID and ktos = 1 ";
		$parcel_qry = $this->pg_pdo->prepare($parcelqry);
		$parcel_qry->bindValue(':BelegID',$this->belegId);
		$parcel_qry->execute() or die (print_r($parcel_qry->errorInfo()));

		return ["status" => true, "link" => $filename, "shippingId" => $shippingId];
		
		//$dhlfile->close();		

	}
	
	public function calcPacks($maxWeight) {
		
		$weigthList = [];
		$packList = [];
		$this->totalQuantity = 0;

		foreach ($this->orderItems as $item) {
			if ( $item["astl"] == 1 ) {
				foreach ($item["sliste"] as $subitem) {
					for($i = 0; $i < $subitem["fmge"]; $i++) {
						$weigthList[] = [ "arnr" => $subitem["arnr"],
										  "agew" => $subitem["agew"],
										  "abz1" => $subitem["abz1"],
										  "inPack" => 0 ];
					}
				}
			} else {
				for($i = 0; $i < $item["fmge"]; $i++) {
					$weigthList[] = [ "arnr" => $item["arnr"],
									  "fmge" => $item["fmge"],
									  "agew" => $item["agew"] , 
									  "abz1" => $item["abz1"],
									  "inPack" => 0 ];
				}
			}
		}
		
		usort($weigthList, function($a, $b) {
			return (($b["weight"] - $a["weight"])*100 );
		} );
		
		$weight = 0;
		$cnt = 0;
		$packList[$cnt]['agew'] = 0;
		foreach($weigthList as $item) {
			$weight += $item["agew"];
			if ($weight > $maxWeight) {
				$cnt++;
				$weight = $item["agew"];
				$packList[$cnt]['agew'] = 0;
			} 				
			$packList[$cnt]['Liste'][] = [ 'arnr' => $item["arnr"], 'abz1' => $item["abz1"], 'agew' => $item["agew"] ];
			$packList[$cnt]['agew'] += $item["agew"];
			$this->totalQuantity++;
		}
		
		for ($i = 0; $i < count($packList); $i++) {
			if ($packList[$i]['agew'] < 0.2) {
				$packList[$i]['agew'] = 0.2;
			} 
		}
		
		return $packList;
	}

	public function sendInvoice() {
		/*
		include ("./intern/config.php");
		
		$eloDoc = new elo_client();
		
		switch ($this->orderHeader["marketplace"]) {
			case "amazon":
				$customerId = 100001;
			break;
			case "ebay":
				$customerId = 100000;
			break;
			case "shop":
				$customerId = 100003;
			break;
			default:	
				$customerId = 100000;
		}
		
		$invoice = $eloDoc->getDocument($this->orderHeader["invoiceId"], 5, $this->orderHeader["kdnr"]);

		if ($invoice["status"] == true) {

			$mailto = "tester@localhost";
			$subject = "Shipment 2.0 Test Invoice";
			$message = "invoice test ...";
			
			$invoiceDoc = new myfile($docpath.$invoice["name"], "append");
			$invoiceDoc->putContent($invoice["document"]);
			
			$dateien = $docpath.$invoice["name"];
			$email = new email($mailto, $subject, $message, $sender, $sender_email, $reply_email, $dateien);
			return $email;
		}	
		*/
	}

	private function getItemsByPack($maxWeight) {
		$items = [];
		$packs = $this->calcPacks($maxWeight);

		foreach($packs as $packNumber=>$pack) {
			foreach($pack["Liste"] as $item) {
				$items[] = 	[ "packNumber" => $packNumber, "Artikel" => $item["Artikel"], "Bezeichnung" => $item["Bezeichnung"] ,"Gewicht" => $item["Gewicht"] ]; 
			}
		}
		$this->itemsByPack = $items;
		$this->itemByPackPointer = 0;
	}
	
	public function getNextItemByPack() {
		if ((!isset($this->itemByPackPointer)) or (!isset($this->totalQuantity))) {
			$this->getItemsByPack(31);
		}
		
		$liste = [];
		if ($this->itemByPackPointer < $this->totalQuantity) {
			$liste[] = ["Artikel"=>$this->itemsByPack[$this->itemByPackPointer]["Artikel"],
					"Bezeichnung"=>utf8_encode($this->itemsByPack[$this->itemByPackPointer]["Bezeichnung"]),
					"Menge"=> 1,
					"Einheit"=> "Stck",
					"ship_packmenge"=> 0,  //TODO -> Packmenge ermitteln
					"Stckliste"=>0,
					"Gewicht"=>$this->itemsByPack[$this->itemByPackPointer]["Gewicht"],
					"packNumber"=>$this->itemsByPack[$this->itemByPackPointer]["packNumber"]];
			$this->itemByPackPointer++;
			return $liste;
		} else {
			return NULL;
		}

	}

	public function getTrackingCodes($shippingId) {
	
		$tracklist = [];
		include ("./intern/config.php");
		$api = new OpenApi3Client($shopware6_url, $shopware6_user, $shopware6_key);
		$response = $api->get('pickware-shipping-shipment/'.$shippingId.'/tracking-codes');

		foreach($response["data"] as $tracking) {
			$tracklist[] = $tracking["attributes"]["trackingCode"];
			
		}
	
		return $tracklist;

	}
	
	public function genDeliver() {

		$override = [];
		$articleList = [];
		
		$cntqry  = "select distinct arnr from auftr_pos 
						where fblg = :BelegID and fmgl > 0 and fmgl > coalesce(fmgr,0) ";
		$cnt_qry = $this->pg_pdo->prepare($cntqry);
		$cnt_qry->bindValue(':BelegID', $this->belegId);
		$cnt_qry->execute() or die (print_r($cnt_qry->errorInfo()));
		while ($cnt_row = $cnt_qry->fetch( PDO::FETCH_ASSOC )) {
			$articleList[] = $cnt_row['arnr'];
		}
		$cntqry  = "select arnr, coalesce(fmgl,0)-coalesce(fmgr,0) as fmgl, amgn, amgz from auftr_pos 
						where fblg = :BelegID and fmgl > 0 and fmgl > coalesce(fmgr,0) ";
		$cnt_qry = $this->pg_pdo->prepare($cntqry);
		$cnt_qry->bindValue(':BelegID', $this->belegId);
		$cnt_qry->execute() or die (print_r($cnt_qry->errorInfo()));
		while ($cnt_row = $cnt_qry->fetch( PDO::FETCH_ASSOC )) {
			$override[$cnt_row['arnr']]["fmge"] = $cnt_row['fmgl'];
			$override[$cnt_row['arnr']]["fmgb"] = $cnt_row['fmgl']*$cnt_row['amgz']/$cnt_row['amgn'];
		}
		
		$desadv = new factoOrders(null, $this->belegId);

		$result = $desadv->duplicateOrder(4,$articleList, $override, false);
		
		return $result;
		
	}

	private function checkShopwareOrderId($id) {
	    
	    if (! preg_match('/[a-z0-9]{32}/', $id)) {
	        include ("./intern/config.php");
	        $api = new OpenApi3Client($shopware6_url, $shopware6_user, $shopware6_key);
	        $params = [
	            'filter' => [
	                [
	                    'type' => 'equals',
	                    'field' => 'orderNumber',
	                    'value' => $this->orderHeader["fnum"]
	                ]
	            ]
	        ];
	        $properties = $api->get('order',$params );

	        return $properties["data"][0]["id"];
	    } else {
	        return $id;
	    }
	}

	public function setOrderDeliveryState($trackingCode, $state) {

		include ("./intern/config.php");
		$api = new OpenApi3Client($shopware6_url, $shopware6_user, $shopware6_key);

		$params = [
				'filter' => [
						[
								'type' => 'equals',
								'field' => 'trackingCodes',
								'value' => $trackingCode
						]
				]
		];
		$deliveryData = $api->get('order-delivery/',$params);
		$response = $api->post('_action/order_delivery/'.$deliveryData["data"][0]["id"].'/state/'. $state	);
		
		if ($state == ORDER_DELIVERY_SHIP) {
			$orderId = $this->checkShopwareOrderId($this->orderHeader["qsbz"]);
			$response = $api->post('_action/order/'.$orderId.'/state/'. ORDER_STATE_COMPLETE );
		}
		
		return true;		
	
	}

}
?>
