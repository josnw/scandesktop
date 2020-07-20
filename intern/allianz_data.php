<h2>Download Allianz Daten</h2>
<p>Bestände und Preise der Allianz Mitglieder</p>

<pre>
<?php
 include './intern/autoload.php';
 include ("./intern/config.php");

 $my_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options); 
 $allianzdata = new allianz_stock_api();					

 // select last update date for every allianz stock
 $sql_fil = "select ifnr, max(b.qedt) as date from fil_0 f left join art_best b using (ifnr) where ifnr > 1 group by ifnr";
 $fil_qry = $my_pdo->prepare($sql_fil);
 $fil_qry->execute() or die(print $fil_qry->errorInfo()[2]);
 $facimp = new myfile($docpath.$facImportFile,'new');
 
  while ($filrow = $fil_qry->fetch( PDO::FETCH_ASSOC )) {
	//get updatedata for single stock
	$stocklist = $allianzdata->getStock($filrow['ifnr'], $filrow['date']);

	foreach ($stocklist["data"] as $stockData) {
		// read article base data
		$article = new product(sprintf("%08d",$stockData['ordernumber']));
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
		$facimp->facHead('ART_BEST',stockData['shopid'],'NB');
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
  
  print '<a href="'.$docpath.$facimp->getCheckedName().'">[Download]</a>';
  $facimp->close();


 
 
 
 ?>
 