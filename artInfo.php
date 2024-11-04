<?php
ini_set('session.gc_maxlifetime', 36000);
session_set_cookie_params(36000);session_start();
//include_once './intern/auth.php';
include_once './intern/autoload.php';
include_once './intern/views/header.php';
print "<H1 style='padding:5px;'>Artikel Info</H1>";

print '<div class="DSEdit" style="display: block; height:400px;"> ';
if (!empty($_GET["ean"])) {
	$article = new product($_GET["ean"]);
	$info = $article->productData[0];
	$preis = $article->getPrices(true);
	$apreis = $article->getAdvertisingPrices(true);
	$probe = $article->getProbeData();
	if (ceil($info["apjs"]) <> 1) {
		$apjs = ceil($info["apjs"]);
	} else {
		$apjs = "";
	}
	if (!empty($info)) {
		print "<table border=0 widht=100% style='font-size:1.4em; padding:5px;'>";
		print "<tr><td colspan=2>".$info["arnr"]."</td></tr>\n";
		print "<tr><td colspan=2>".$info["abz1"]."</br>\n";
		print "".$info["abz2"]."<br/><hr/></td></tr>\n";
		print "<tr><td>EAN</td><td>".$info["asco"]."</td></tr>\n";
		print "<tr><td>Lieferant </td><td>".$info["linr"]."</td></tr>\n";
		print "<tr><td>Privatpreis </td><td><b>".$preis["Privatpreis"][0]["price"]." / ".$apjs." ".$info["ameh"]."</b></td></tr>\n";
		if($apreis["Privatpreis"] > 0) {
			print "<tr><td> --> Aktion </td><td>".$apreis["Privatpreis"]." / ".$apjs." ".$info["ameh"]."</td></tr>\n";
		}
		if ($info["amgm"] <> 1) {
			print "<tr><td>Verkauf als</td><td>".$info["ageh"]." á ".$info["amgm"]." ".$info["ameh"]."</td></tr>\n";
		}
		if (!empty($probe)) {
			print "<tr><td colspan=2><hr/></td></tr>\n";
			print "<tr><td>Verkaufsfach</td><td>".$probe[0]["alag"]."</td></tr>\n";
			print "<tr><td>Spooler</td><td>".$probe[0]["qspl"];
			if (!empty($probe[0]["qanz"])) {" (".$probe[0]["qanz"].") "; }
			print "</td></tr>\n";
			print "<tr><td>letzter Druck</td><td>".$probe[0]["aedt"]."</td></tr>\n";
		}
		print "</table>\n";
	} else {
		print "<error>Artikel nicht gefunden<br/><hr/></error>\n";
	}
}
print "</div>";
?>
<form action="#" method="GET" enctype="multipart/form-data" >
	<div class="DSEdit">
			Scan: <input type="text" name="ean" value="" autofocus onchange="this.form.submit();" onkeydown="if(event.key === 'Enter') { event.preventDefault(); this.form.submit(); }" autocomplete="off"> 
	</div>
</form>
