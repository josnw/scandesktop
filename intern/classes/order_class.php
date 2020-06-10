<?php

class order {
		
	private $belegId;
	private $my_pdo;
	private $pg_pdo;
	private $itemPointer;
	
	private $itemByPackPointer;
	private $itemsByPack;
	private $totalQuantity;
	
	public $orderHeader;
	public $orderItems;
	public $MainItemCount;
	public $ItemCount;

	public $orderWeight;

	
	// Belegdaten aus Datenbank komplett in ein Array einlesen
	public function __construct($belegnummer) {

		include ("./intern/config.php");

		$this->belegId = $belegnummer;

		$this->my_pdo = new PDO($ecserver, $ecuser, $ecpass, $options);
		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		
		//Belegkopfdaten einlesen
		$oqry  = 'select * from versand where BelegID = :BelegID';

		$r_qry = $this->my_pdo->prepare($oqry);
		$r_qry->bindValue(':BelegID', $belegnummer);
		$r_qry->execute() or die (print_r($r_qry->errorInfo()));

		$this->orderHeader = $r_qry->fetch( PDO::FETCH_ASSOC );
		if ($this->orderHeader == false) {
			return NULL;
		}
		foreach($this->orderHeader as $field => $value) {
			$this->orderHeader[$field] = utf8_encode($value);
		}

		// Belegpositionen einlesen
		$iqry  = 'select BelegID, Artikel, p.Bezeichnung, p.Menge, p.Preis, p.ItemID, p.TransactionID, p.AmazonOrderId, p.VersandStatus, p.ImportTime, p.Ship_packtime, p.ship_packmenge, p.Stckliste, k.Gewicht 
		             from versand_pos p left join katalog k using (Artikel) where BelegID = :BelegID';

		$r_qry = $this->my_pdo->prepare($iqry);
		$r_qry->bindValue(':BelegID', $belegnummer);
		$r_qry->execute() or die (print_r($r_qry->errorInfo()));

		$this->MainItemCount = 0;
		$this->ItemCount = 0;
		$this->OrderWeight = 0;

		while ($row = $r_qry->fetch( PDO::FETCH_ASSOC )) {
			$this->MainItemCount++;
			$this->ItemCount++;
			$this->OrderWeight += $row["Gewicht"]*$row["Menge"];
			// Stücklisten mit Factodaten ergänzen
			if ($row["Stckliste"] == 1) {
				$fqry  = 'select astl, abz1,abz2,asmz/asmn as asgm , ameh, agew, asco from art_stl s inner join art_0 a on s.astl=a.arnr left join art_ean e on e.arnr = a.arnr and e.qskz = 1 inner join art_txt t on a.arnr = t.arnr  
							where s.arnr = :arnr ';
				$f_qry = $this->pg_pdo->prepare($fqry);
				$f_qry->bindValue(':arnr', $row["Artikel"]);
				$f_qry->execute() or die (print_r($f_qry->errorInfo()));

				$subqry  = 'insert ignore into versand_pos_packing (BelegID, Artikel, SubArtikel,SubBezeichnung,SubMenge,SubEinheit,lastedit ) VALUES (:BelegID, :Artikel, :SubArtikel, :SubBezeichnung, :SubMenge, :SubEinheit, now()) ';
				$sub_qry = $this->my_pdo->prepare($subqry);

				//Fehlende Einträge in die Stücklistentabelle eintragen
				while ($frow = $f_qry->fetch( PDO::FETCH_ASSOC )) {
					$this->ItemCount++;
					$row["sliste"][] = [ "Artikel" => $frow["astl"], "Bezeichnung" => $frow["abz1"]." ".$frow["abz2"], "Menge" => $frow["asgm"]*$row["Menge"], "Einheit" => $frow["ameh"] ,  "Gewicht" => $frow["agew"] ];
					$sub_qry->bindValue(':BelegID', $belegnummer);
					$sub_qry->bindValue(':Artikel', $row["Artikel"]);
					$sub_qry->bindValue(':SubArtikel', $frow["astl"]);
					$sub_qry->bindValue(':SubBezeichnung', $frow["abz1"]." ".$frow["abz2"]);
					$sub_qry->bindValue(':SubMenge',$frow["asgm"]*$row["Menge"]);
					$sub_qry->bindValue(':SubEinheit',$frow["ameh"]);
					$sub_qry->execute() or die (print_r($sub_qry->errorInfo()));
					
					if ($row["Gewicht"] == 0 ) {
						$this->OrderWeight += $frow["agew"]*$row["Menge"]*$frow["asgm"];
					}
				}
			} 
			$this->orderItems[] = $row;
		}
		$this->itemPointer = 0;
		return $this->MainItemCount;
	}

	public function checkItemQuantity($Artikel) {
		
		$cntqry  = 'select Artikel, sum(Menge) as Menge, sum(PackMenge) as PackMenge from (
					select Artikel, sum(Menge) as Menge, sum(ship_packmenge) as PackMenge from versand_pos where BelegID = :BelegID and Artikel = :Artikel group by Artikel 
					union
					select Artikel, sum(SubMenge) as Menge, sum(ship_packmenge) as PackMenge from versand_pos_packing where BelegID = :BelegID and SubArtikel = :Artikel group by Artikel ) liste
					group by Artikel
					';
		$cnt_qry = $this->my_pdo->prepare($cntqry);
		$cnt_qry->bindValue(':BelegID', $this->belegId);
		$cnt_qry->bindValue(':Artikel', $Artikel);
		$cnt_qry->execute() or die (print_r($cnt_qry->errorInfo()));
		$cnt_row = $cnt_qry->fetch( PDO::FETCH_ASSOC );
		
		return [ "Menge" => $cnt_row["Menge"], "PackMenge" => $cnt_row["PackMenge"], "Restmenge" => ($cnt_row["Menge"]-$cnt_row["PackMenge"]) ];

	}

	public function checkItemTyp($Artikel) {
		
		$cntqry  = "select Artikel, 'SingleItem' as typ from versand_pos where BelegID = :BelegID and Artikel = :Artikel group by Artikel 
					union
					select Artikel, 'SubItem' from versand_pos_packing where BelegID = :BelegID and SubArtikel = :Artikel group by Artikel ";
					
		$cnt_qry = $this->my_pdo->prepare($cntqry);
		$cnt_qry->bindValue(':BelegID', $this->belegId);
		$cnt_qry->bindValue(':Artikel', $Artikel);
		$cnt_qry->execute() or die (print_r($cnt_qry->errorInfo()));
		$cnt_row = $cnt_qry->fetchAll( PDO::FETCH_ASSOC );
		
		return $cnt_row;

	}
	
	// gepackte Menge des Artikels um 1 erhöhen
	// ToDo Eigenes Array aktualisieren
	public function setPacked($Artikel, $packId = NULL) {
		
		$pqry  = 'update versand_pos set ship_packmenge = ship_packmenge+1 where BelegID = :BelegID and Artikel = :Artikel';
		$pkqry  = 'update versand_pos set ship_packmenge = Menge where BelegID = :BelegID and Artikel = :Artikel';
		$psqry  = 'update versand_pos_packing set ship_packmenge = ship_packmenge+1 where BelegID = :BelegID and Artikel = :Artikel and SubArtikel = :SubArtikel';
		$poqry  = 'update versand set ship_status = 1 where BelegID = :BelegID ';
		
		$csqry  = 'select count(*) as cnt from versand_pos_packing where ship_packmenge < SubMenge and BelegID = :BelegID and Artikel = :Artikel ';
		$coqry  = 'select count(*) as cnt from versand_pos where ship_packmenge < Menge and BelegID = :BelegID ';

		
		$p_qry = $this->my_pdo->prepare($pqry);
		$ps_qry = $this->my_pdo->prepare($psqry);
		$pk_qry = $this->my_pdo->prepare($pkqry);
		$po_qry = $this->my_pdo->prepare($poqry);
		$cs_qry = $this->my_pdo->prepare($csqry);
		$co_qry = $this->my_pdo->prepare($coqry);

		if ($this->checkItemQuantity($Artikel)["Restmenge"] > 0) {
			
			$itemTyp = $this->checkItemTyp($Artikel);
			$OrgArtikel = $Artikel;

			if ($itemTyp[0]["typ"] == 'SubItem') {
				$SubArtikel = $Artikel;
				$Artikel = $itemTyp[0]["Artikel"];
			} else {
				$SubArtikel = NULL;
			}
		


			if ($SubArtikel != NULL) {
				// Subartikel erhöhen und prüfen ob Stückliste komplett gepackt
				$ps_qry->bindValue(':BelegID', $this->belegId);
				$ps_qry->bindValue(':Artikel', $Artikel);
				$ps_qry->bindValue(':SubArtikel', $SubArtikel);
				$ps_qry->execute() or die (print_r($ps_qry->errorInfo()));
				
				//Prüfen ob Stückliste vollständig 
				$cs_qry->bindValue(':BelegID', $this->belegId);
				$cs_qry->bindValue(':Artikel', $Artikel);
				$cs_qry->execute() or die (print_r($cs_qry->errorInfo()));
				$csrow = $cs_qry->fetch( PDO::FETCH_ASSOC );
				
				//Stückliste als gepackt kennzeichnen
				if ($csrow["cnt"] == 0) {
					$pk_qry->bindValue(':BelegID', $this->belegId);
					$pk_qry->bindValue(':Artikel', $Artikel);
					$pk_qry->execute() or die (print_r($pk_qry->errorInfo()));
				}

			} else {
				// Standardartikel gepackt
				$p_qry->bindValue(':BelegID', $this->belegId);
				$p_qry->bindValue(':Artikel', $Artikel);
				$p_qry->execute() or die (print_r($p_qry->errorInfo()));

			}

			// Prüfen ob Beleg vollständig gepackt
			$co_qry->bindValue(':BelegID', $this->belegId);
			$co_qry->execute() or die (print_r($co_qry->errorInfo()));
			$corow = $co_qry->fetch( PDO::FETCH_ASSOC );
			
			// Beleg als gepackt kennzeichnen
			if ($corow["cnt"] == 0) {
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
			$co_qry->bindValue(':BelegID', $this->belegId);
			$co_qry->execute() or die (print_r($co_qry->errorInfo()));
			$corow = $co_qry->fetch( PDO::FETCH_ASSOC );
			
			if ($corow["cnt"] == 0) {
				$po_qry->bindValue(':BelegID', $this->belegId);
				$po_qry->execute() or die (print_r($po_qry->errorInfo()));
			}
			
			return [ "status" => false, "info" => "falscher Artikel für diese Bestellung!\nBitte prüfen Sie Menge und Art!"];
		}
	}

	// nächste Position incl. Stückliste, fortlaufend
	public function getNextItem() {
		$liste = [];
		if ($this->itemPointer < $this->MainItemCount) {
			$liste[] = ["Artikel"=>$this->orderItems[$this->itemPointer]["Artikel"],
					"Bezeichnung"=>utf8_encode($this->orderItems[$this->itemPointer]["Bezeichnung"]),
					"Menge"=>$this->orderItems[$this->itemPointer]["Menge"],
					"Einheit"=> "Stck",
					"ship_packmenge"=>$this->orderItems[$this->itemPointer]["ship_packmenge"],
					"Stckliste"=>$this->orderItems[$this->itemPointer]["Stckliste"],
					"Gewicht"=>$this->orderItems[$this->itemPointer]["Gewicht"]];
			if ($this->orderItems[$this->itemPointer]["Stckliste"] == 1) {
				
			// Belegpositionen einlesen
			$iqry  = 'select * from versand_pos_packing where BelegID = :BelegID and Artikel = :Artikel and SubArtikel = :SubArtikel';
			$r_qry = $this->my_pdo->prepare($iqry);
			$r_qry->bindValue(':BelegID', $this->belegId);
			$r_qry->bindValue(':Artikel', $this->orderItems[$this->itemPointer]["Artikel"]);
				
			foreach($this->orderItems[$this->itemPointer]["sliste"] as $spos) {

				$r_qry->bindValue(':SubArtikel', $spos["Artikel"]);
				$r_qry->execute() or die (print_r($r_qry->errorInfo()));	
				$row = $r_qry->fetch( PDO::FETCH_ASSOC );
				
				$liste[] = ["Artikel"=>$spos["Artikel"],
						"Bezeichnung"=>utf8_encode($spos["Bezeichnung"]),
						"Menge"=>$spos["Menge"],
						"Einheit"=> $spos["Einheit"],
						"Gewicht"=> $spos["Gewicht"],
						"ship_packmenge"=> $row["ship_packmenge"],
						"Stckliste"=> 2 ];
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
		
		$addrqry  = "update versand set Name = :Name, Adresse1 = :Adresse1, Adresse2 = :Adresse2, HNummer = :HNummer, PLZ = :PLZ, Ort = :Ort, Land = :Land where BelegID = :BelegID";
			
		$addr_qry = $this->my_pdo->prepare($addrqry);
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
		
		return true;
		
	}

	public function getOrderId() {
		print $this->belegId;
	}
	
	public function exportDHLShipping($parcelData) {

		include ("./intern/config.php");
		// Export für DHL Versand
		
		// Statische Ergänzung:
		$static = array("Absender" => "Raiffeisen BHG Erzgebirge e.G.","AbsPLZ" => "09526","AbsOrt" => "Olbernhau","AbsLand" => "DE","Abrechnung" => "62239401350101", 'Produkt' => 'V01PAK', "Gewicht" => '');

		$dhlfilename = time()."_dhlversand.TEST";
		$dhl = array_merge($this->orderHeader, $static); 
		$dhlfile = fopen($parcelPath["DHL"]."/".$dhlfilename,"a");
		fputcsv($dhlfile, array_keys($dhl),";");
		foreach ($parcelData as $pack) {
			print $pack["parcelService"]."/".$pack["packWeight"]."<br>";
			if ($pack["parcelService"] == "DHL") {
				$dhl["Gewicht"] = $pack["packWeight"];
				fputcsv($dhlfile, $dhl,";");
			}
		}
		
		$parcelqry = "update versand set DELI_Link = 'DHL_Export', ship_status=2 where BelegID = :BelegID and ship_status = 1 ";
		$parcel_qry = $this->my_pdo->prepare($parcelqry);
		$parcel_qry->bindValue(':BelegID',$this->belegId);
		$parcel_qry->execute() or die (print_r($parcel_qry->errorInfo()));

		fclose($dhlfile);
		if ($DEBUG == 1) {
			print "DEBUGMODUS!<br>";
			print $parcelPath["DHL"]."/".$dhlfilename."<br>";
			print "<textarea cols=120 rows=10>";
			print file_get_contents($parcelPath["DHL"]."/".$dhlfilename);
			print "</textarea>";
		}		
	}
	
	public function calcPacks($maxWeight) {
		
		$weigthList = [];
		$packList = [];
		$this->totalQuantity = 0;

		foreach ($this->orderItems as $item) {
			if ( $item["Stckliste"] == 1 ) {
				foreach ($item["sliste"] as $subitem) {
					for($i = 0; $i < $subitem["Menge"]; $i++) {
						$weigthList[] = [ "Artikel" => $subitem["Artikel"],
										  "weight" => $subitem["Gewicht"],
										  "Bezeichnung" => $subitem["SubBezeichnung"],
										  "inPack" => 0 ];
					}
				}
			} else {
				for($i = 0; $i < $item["Menge"]; $i++) {
					$weigthList[] = [ "Artikel" => $item["Artikel"],
									  "Menge" => $item["Menge"],
									  "weight" => $item["Gewicht"] , 
									  "Bezeichnung" => $item["Bezeichnung"],
									  "inPack" => 0 ];
				}
			}
		}
		
		usort($weigthList, function($a, $b) {
			return (($b["weight"] - $a["weight"])*100 );
		} );
		
		$weight = 0;
		$cnt = 0;
		$packList[$cnt]['weight'] = 0;
		foreach($weigthList as $item) {
			$weight += $item["weight"];
			if ($weight > $maxWeight) {
				$cnt++;
				$weight = $item["weight"];
				$packList[$cnt]['weight'] = 0;
			} 				
			$packList[$cnt]['Liste'][] = [ 'Artikel' => $item["Artikel"], 'Bezeichnung' => $item["Bezeichnung"], 'Gewicht' => $item["weight"] ];
			$packList[$cnt]['weight'] += $item["weight"];
			$this->totalQuantity++;
		}
		
		return $packList;
	}

	public function sendInvoice() {
		include ("./intern/config.php");
		
		$eloDoc = new elo_client();
		
		switch ($this->orderHeader["marketplace"]) {
			case "amazon":
				$customerId = 100001;
			break;
			case "ebay":
				$customerId = 100000;
			break;
			case "rhg.de":
				$customerId = 100003;
			break;
			default:	
				$customerId = 100000;
		}
		
		$invoice = $eloDoc->getDocument($this->orderHeader["invoiceId"], 5, $customerId);

		if ($invoice["status"] == true) {
			$mailto = "tom@olb-isdn.rbhg-erzgebirge.de";
			$subject = "Shipment 2.0 Test Invoice";
			$message = "Die Rechnung...";
			$sender = "Raiffeisen Erzgebirge e.G."; 
			$sender_email = "info@rbhg-erzgebirge.de"; 
			$reply_email = "admin@rbhg-erzgebirge.de"; 
			file_put_contents($docpath.$invoice["name"],  $invoice["document"]);
			$dateien = $docpath.$invoice["name"];
			$email = new email($mailto, $subject, $message, $sender, $sender_email, $reply_email, $dateien);
			return $email;
		}	
		
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

	public function getTrackingCodes() {
		
		$tracklist = [];
		$trackqry  = 'select distinct TrackingCode from versand_tracking where BelegID = :BelegID';
		$track_qry = $this->my_pdo->prepare($trackqry);
		$track_qry->bindValue(':BelegID', $this->belegId);
		$track_qry->execute() or die (print_r($track_qry->errorInfo()));
		while ($track_row = $track_qry->fetch( PDO::FETCH_ASSOC )) {
			$tracklist[] = $track_row["TrackingCode"];
		}
		
		return $tracklist;

	}
}
?>