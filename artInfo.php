<?php
ini_set('session.gc_maxlifetime', 36000);
session_set_cookie_params(36000);session_start();
//include_once './intern/auth.php';
include_once './intern/autoload.php';
include_once './intern/views/header.php';
print "<H1 style='padding:5px;'>Artikel Info</H1>";

if (!empty($_GET["ean"])) {
	$article = new product($_GET["ean"]);
	$info = $article->productData[0];
	$preis = $article->getPrices(true);
	print "<table border=0 widht=100% style='font-size:1.2em; padding:5px;'>";
	print "<tr><td colspan=2>".$info["arnr"]."</td></tr>\n";
	print "<tr><td colspan=2>".$info["abz1"]."</br>\n";
	print "".$info["abz2"]."<br/><hr/></td></tr>\n";
	print "<tr><td>EAN</td><td>".$info["asco"]."</td></tr>\n";
	print "<tr><td>Lieferant </td><td>".$info["linr"]."</td></tr>\n";
	print "<tr><td>Privatpreis </td><td>".$preis["Privatpreis"][0]["price"]." / ".ceil($info["apjs"])." ".$info["ameh"]."</td></tr>\n";
	print "<tr><td>VKGeb</td><td>".$info["ageh"]."</td></tr>\n";
	print "</table>\n";
}

?>
<form action="#" method="GET" enctype="multipart/form-data" >
	<div class="DSEdit">
			Scan: <input type="text" name="ean" value="" autofocus onchange="this.form.submit();" onkeydown="if(event.key === 'Enter') { event.preventDefault(); this.form.submit(); }" autocomplete="off"> 
	</div>
</form>
