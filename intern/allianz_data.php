<h2>Download Allianz Daten</h2>
<p>Bestände und Preise der Allianz Mitglieder</p>

<pre>
<?php
 include './intern/autoload.php';
 include ("./intern/config.php");

 $my_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options); 
 $allianzdata = new allianz_stock_api();					

 // select last update date for every allianz stock
 $sql_fil = "select ifnr, qbnr, max(b.qedt) as date from fil_0 f left join art_best b using (ifnr) where ifnr > 1 group by ifnr";
 $fil_qry = $my_pdo->prepare($sql_fil);
 $fil_qry->execute() or die(print $fil_qry->errorInfo()[2]);
 $facimp = new myfile($docpath.$facImportFile,'new');

  while ($filrow = $fil_qry->fetch( PDO::FETCH_ASSOC )) {
	//get updatedata for single stock
	$stocklist = $allianzdata->getStock($filrow['ifnr'], $filrow['date']);
	
	foreach ($stocklist["data"] as $stockData) {
		 // read article base data
		$article = new product(sprintf("%08d",$stockData['ordernumber']));
		if (!isset($article->productData[0])) {
			continue;
		}
		// calculate aviable base stock
		if (isset($article->productData[0]['amgm'])) {
			$baseStock = $stockData['stock']*$article->productData[0]['amgm'];
		} else {
			$baseStock = $stockData['stock'];
		}
		$aviableStock = round(($baseStock - $security_distance_abs) * (1 - $security_distance_rel),3);
		if ($aviableStock < 0) {
			$aviableStock = 0;
		}
		// write to wws import file
		$facimp->facHead('ART_BEST',$stockData['shopid'],'NB');
		$facimp->facData([
			'ARNR' => sprintf("%08d",$stockData['ordernumber']),
			'XXAK' => '',
			'XYAK' => '',
			'ACHB' => '',
			'IFNR' => $stockData['shopid'],
			'AMGE' => $aviableStock,
		]);
		
		$facimp->facFoot();
	}


  } 

 // select last update date for every allianz company
 $sql_fil = "select qbnr, qbtl, max(b.qedt) as date from fil_0 f left join cond_ek b on b.linr = f.qbtl::integer where ifnr > 1 and qbnr <> '' group by qbnr, qbtl";
 $fil_qry = $my_pdo->prepare($sql_fil);
 $fil_qry->execute() or die(print $fil_qry->errorInfo()[2]);
 $facimp = new myfile($docpath.$facImportFile,'new');

  while ($filrow = $fil_qry->fetch( PDO::FETCH_ASSOC )) {
	//get updatedata for single price

	$pricelist = $allianzdata->getPrice($filrow['qbnr'], $filrow['date']);

	foreach ($pricelist["data"] as $priceData) {

		// read article base data
		$article = new product(sprintf("%08d",$priceData['ordernumber']));
		
		if (!isset($article->productData[0])) {
			continue;
		}
		// calculate aviable base price
		if ((isset($article->productData[0]['apjs'])) and ($article->productData[0]['apjs'] <> 0)) {
			$apjs = $article->productData[0]['apjs'];
		} else {
			$apjs =1;
		}
		if ((isset($article->productData[0]['amgm'])) and ($article->productData[0]['amgm'] <> 0)) {
			$amgm = $article->productData[0]['amgm'];
		} else {
			$amgm = 1;
		}

		$baseprice = $priceData['price']/$amgm * $apjs;



		$aviableprice = round($baseprice,2);
		if ($aviableprice < 0) {
			$aviableprice = 0;
		}

		// write to wws import file
		$facimp->facHead('ART_LIEF',0,'N ');
		$facimp->facData([
			'ARNR' => sprintf("%08d",$priceData['ordernumber']),
			'XXAK' => '',
			'XYAK' => '',
			'OBNR' => '0',
			'LINR' => $filrow['qbtl'],
		]);
		
		$facimp->facFoot();
		
		// write to wws import file
		$facimp->facHead('COND_EK',0,'N ');
		$facimp->facData([
			'CONR' => $filrow['qbtl'].printf("%08d",$priceData['ordernumber']),
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
			'QDTM' => date("d.m.Y"),
			'QVON' => date("01.m.Y"),
			'QBIS' => '31.12.9999',
			'AMEH' => $article->productData[0]['ameh'],
			'LINR' => $filrow['qbtl'],
		]);
		
		$facimp->facFoot();
	}

	
  }
  
  print '<a href="'.$docpath.$facimp->getCheckedName().'">[Download]</a>';
  $facimp->close();


 
 
 
 ?>
 