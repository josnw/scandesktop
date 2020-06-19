<h1>Bestellung packen</h2> 
<?php include("./intern/views/scan_view.php"); ?>
<div class=oderinfo>
<div class=orderinfoname>Beleg:</div>
<div class=oderinfoitem> <?php print($packOrder->orderHeader["BelegID"]);?></div>
<div class=orderinfoname>Name</div>
<div class=oderinfoitem><?php print($packOrder->orderHeader["Name"]);?></div>
<div class=orderinfoname>Ort</div>
<div class=oderinfoitem><?php print($packOrder->orderHeader["Ort"]);?></div>
<div class=orderinfoname>Zahlung</div>
<div class=oderinfoitem><?php print($packOrder->orderHeader["Zahlung"]);?></div>
<div class=orderinfoname>Bestelldatum</div>
<div class=oderinfoitem><?php print($packOrder->orderHeader["orderDate"]);?></div>
<div class=orderinfoname>Status</div>
<div class=oderinfoitem><?php print($packOrder->orderHeader["ship_status"]);?></div>
</div>
