<h1>Bestellung packen</h2> 
<?php include("./intern/views/scan_view.php"); ?>
<div class=oderinfo>
<div class=orderinfoname>Beleg:</div>
<div class=oderinfoitem> <?php print($packOrder->orderHeader["fnum"]);?></div>
<div class=orderinfoname>Name</div>
<div class=oderinfoitem><?php print($packOrder->orderHeader["qna1"]);?></div>
<div class=orderinfoname>Ort</div>
<div class=oderinfoitem><?php print($packOrder->orderHeader["qort"]);?></div>
<div class=orderinfoname>Bestelldatum</div>
<div class=oderinfoitem><?php print($packOrder->orderHeader["fdtm"]);?></div>
<div class=orderinfoname>Status</div>
<div class=oderinfoitem><?php print($packOrder->orderHeader["ktos"]);?></div>
<form action="#" method="POST" enctype="multipart/form-data" >
	<div class="DSEdit noprint">
		<input type = hidden name="orderId" value="<?php  print($packOrder->orderHeader["fblg"]); ?>"  required>
		<div class="DSFeld2" style="background: #AA5555;"><input type="submit" name="removeOrder" value="Zurückstellen"></div>
	</div>
</form>

</div>
