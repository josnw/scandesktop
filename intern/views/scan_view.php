
<div class="DSEdit noprint" style="width: 60%; float: left;" >
	<div class="DSSuche" style="width: 100%; float: left;" >
		scanne den nächsten gepackten Artikel:<br/>
		<input id="scanid" name="scanid" onkeyup="checkIn(this, event, 'productid', '<?php print $packOrder->getOrderId()."','".$_SESSION["pickId"]."','".$_SESSION['ItemScanKey']."',''"; ?> )" autocomplete="off" style="width: 90%;" >
		<script type="text/javascript">FocusOnInput("scanid");</script>
	</div>
		<div class="DSFeld2" id="singelLabelBtn" style="width: 30%; visibility: hidden;"  style="background: #AA5555;">
			<button  style="width: 30%;"  onclick="singleLabel()">Versandlabel drucken</button>
		</div>
</div>