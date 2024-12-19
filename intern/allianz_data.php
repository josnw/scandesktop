<h2>Download Allianz Daten</h2>
<p>Bestände und Preise der Allianz Mitglieder</p>
<pre>
<?php
 include './intern/autoload.php';
 include ("./intern/config.php");

 $my_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options); 
 $allianzdata = new allianz_stock_api();					
 
 
 // check for new local product data and correct temporary stock 
 $correctsql = "update art_best b set amge = aimg * (select case when amgn > 0 then amgz/amgn else 1 end from art_0 a where a.arnr = b.arnr),
								   aimg = null 
			 where aimg > 0 and arnr in (select arnr from art_0 a2 where a2.arnr = b.arnr ) and aivn is null and aisp is null";
 $correct_qry = $my_pdo->prepare($correctsql);
 $correct_qry->execute() or die($correct_qry->errorInfo()[2]);

 // check for new local product data and correct temporary prices
 $correctsql = "update cond_ek b set cprs = cprs * (select case when amgz > 0 then amgn/amgz else 1 end from art_0 a where a.arnr = b.arnr)
											/coalesce((select (1+mmss/100) from  mand_mwst m inner join art_0 a on a.apkz = m.mmid where  a.arnr = b.arnr),1),

								ameh = (select ameh from art_0 a where a.arnr = b.arnr),
								cpog = 'F000'  
				where cpog = 'X000' and arnr in (select arnr from art_0 a2 where a2.arnr = b.arnr )";
 $correct_qry = $my_pdo->prepare($correctsql);
 $correct_qry->execute() or die($correct_qry->errorInfo()[2]);

 
 // select last update date for every allianz stock
 $sql_fil = "select ifnr, qbnr, (max(b.qedt) - INTERVAL '1 hour') as date from fil_0 f left join art_best b using (ifnr) where ifnr > 1 and coalesce(f.quse,0) < 3 group by ifnr, qbnr order by ifnr";
 $fil_qry = $my_pdo->prepare($sql_fil);
 $fil_qry->execute() or die($fil_qry->errorInfo()[2]);
/* 
 $facimp = new myfile($docpath.$facImportFile,'new');
*/
  while ($filrow = $fil_qry->fetch( PDO::FETCH_ASSOC )) {
	//get updatedata for single stock
	if (array_key_exists("fullLoad",$_POST) or (isset($argv) and in_array("/fullLoad", $argv))) {
		$filrow['date'] = null;
	}
	
	print "<br\>\nUpdate ".$filrow['ifnr']." from ".$filrow['date'];
	
	$cnt = 0;
	
	do {
		$stocklist = $allianzdata->getStock($filrow['ifnr'], $filrow['date']);
		if (! array_key_exists('data',$stocklist) and array_key_exists('debug',$_SESSION) and ($_SESSION['debug'] == 1) and ($_SESSION["level"] == 9)) {
			var_dump($stocklist);
		}
		print "... next ".$stocklist["count"]."...";
		foreach ($stocklist["data"] as $stockData) {
			 // read article base data
			$article = new product(sprintf("%08d",$stockData['ordernumber']));
			if (!array_key_exists(0,$article->productData)) {
				if ( array_key_exists('debug',$_SESSION) and ($_SESSION['debug'] == 1) and ($_SESSION["level"] == 9)) {
					print "<br/>Article: ".sprintf("%08d",$stockData['ordernumber'])." not found!<br/>\n";
				}
				//continue;			
			}
			
			$aviableStock = round(($stockData['stock'] - $security_distance_abs * 2 ) * (1 - $security_distance_rel),3);

			// calculate aviable base stock
			if ((array_key_exists(0,$article->productData)) and (array_key_exists('amgm',$article->productData[0])) and ($article->productData[0]['amgm'] > 0)) {
				$baseStock = $aviableStock*$article->productData[0]['amgm'];
			} else {
				$baseStock = $aviableStock;
			}
			
			if ($baseStock < 0) {
				$baseStock = 0;
			}
			
			if ( array_key_exists('debug',$_SESSION) and ($_SESSION['debug'] == 1) and ($_SESSION["level"] == 9)) {
				print "<br/>Stock: ".sprintf("%08d",$stockData['ordernumber'])." ".$stockData['shopid'].": ".$baseStock."<br/>\n";
			}
/* switch to direct database input for all products
			// write to wws import file
			$facimp->facHead('ART_BEST',$stockData['shopid'],'NB');
			$facimp->facData([
				'ARNR' => sprintf("%08d",$stockData['ordernumber']),
				'XXAK' => '',
				'XYAK' => '',
				'ACHB' => '',
				'IFNR' => $stockData['shopid'],
				'AMGE' => $baseStock,
			]);
			
			$facimp->facFoot();
*/		
			//write in Facto DB
			$article->writeStockDb($stockData['shopid'], $baseStock);
			// print $stockData['shopid']."/".sprintf("%08d",$stockData['ordernumber'])." -> ".$baseStock."<br>\n";
			$cnt++;
		}
	} while ( count($stocklist["data"]) > 0 );
	
	print " ... ".$cnt."Datensätze<br/>\n";

  } 

 // select last update date for every allianz company
 $sql_fil = "select ifnr, qlnr, qbnr, max(b.qedt) as date from fil_0 f left join cond_ek b on b.linr = f.qlnr::integer where ifnr > 1 and qlnr > 0 
               group by ifnr, qlnr, qbnr";
 $fil_qry = $my_pdo->prepare($sql_fil);
 $fil_qry->execute() or die($fil_qry->errorInfo()[2]);
/*
 $facimp = new myfile($docpath.$facImportFile,'new');
*/
  while ($filrow = $fil_qry->fetch( PDO::FETCH_ASSOC )) {
	//get updatedata for single price

	$pricelist = $allianzdata->getPrice($filrow['qbnr'], $filrow['date']);

	foreach ($pricelist["data"] as $priceData) {

		// read article base data
		$article = new product(sprintf("%08d",$priceData['ordernumber']));
		
		//if (!array_key_exists($article->productData[0])) {
		//	continue;
		//}
		// calculate aviable base price
		if (!empty($article->productData[0]['apjs']) and ($article->productData[0]['apjs'] <> 0)) {
			$apjs = $article->productData[0]['apjs'];
		} else {
			$apjs =1;
		}
		if (!empty($article->productData[0]['amgm']) and ($article->productData[0]['amgm'] <> 0)) {
			$amgm = $article->productData[0]['amgm'];
		} else {
			$amgm = 1;
		}
		if (!empty($article->productData[0]['amms']) and ($article->productData[0]['amms'] <> 0)) {
			$tax = $article->productData[0]['amms'];
		} else {
			$tax = 0;
		}

		$baseprice = $priceData['price']/ $amgm / (1 + $tax / 100) * $apjs ;



		$aviableprice = round($baseprice,2);
		if ($aviableprice < 0) {
			$aviableprice = 0;
		}

/*	switch to direct database input for all products
		// write to wws import file
		$facimp->facHead('ART_LIEF',0,'N ');
		$facimp->facData([
			'ARNR' => sprintf("%08d",$priceData['ordernumber']),
			'XXAK' => '',
			'XYAK' => '',
			'OBNR' => '0',
			'LINR' => $filrow['qlnr'],
		]);
		
		$facimp->facFoot();
		
		// write to wws import file
		$facimp->facHead('COND_EK',0,'N ');
		$facimp->facData([
			'CONR' => $filrow['qlnr'].sprintf("%08d",$priceData['ordernumber']),
			'MPRB' => '1',
			'ARNR' => sprintf("%08d",$priceData['ordernumber']),
			'XXAK' => '',
			'XYAK' => '',
			'OBNR' => '0',
			'CPOG' => 'F000',
			'CBEZ' => 'FPNE',
			'CPRS' => $priceData['price'],
			'APJS' => $apjs,
			'CPCR' => 'EUR',
			'CCRU' => 'C',
			'QDTM' => date("d.m.Y"),
			'QVON' => date("01.m.Y"),
			'QBIS' => '31.12.9999',
			'AMEH' => $article->productData[0]['ameh'],
			'LINR' => $filrow['qlnr'],
		]);
		
		$facimp->facFoot();
*/		
		$article->writePriceDb('9'.sprintf("%07d",$filrow['qlnr']).sprintf("%09d",$priceData['ordernumber']),$filrow['qlnr'], $aviableprice);
		
	}

	
  }
  
/*
 print '<a href="'.$docpath.$facimp->getCheckedName().'">[Download]</a>';
 $facimp->close();
*/
 print "Done!";


 
 
 
 ?>
 
