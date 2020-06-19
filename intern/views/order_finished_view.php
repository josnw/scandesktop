<div id="OrderFinished" onmousedown="startDrag(this);" style="display:block;" >
	<h1>Paket abgeschließen</h1>

		<div class="DSEdit smallBorder" >
			<div class="DSFeld1">
				Label werden generiert ...<br/>
				Export an DHL  ...<br/>
				<?php if (($orderPacked == true ) and ($invoice_sendStatus == true)) { 
						print "	Rechnungsversand ...<br/>"; 
					} elseif ( $orderPacked == true ) { 
						print "Rechnung konnte nicht versendet werden ...<br/>";
					} 
				?>
			</div>
		<div class="DSEdit smallBorder" >
		  <h3>Paket versandfertig verpacken und Paketlabel scannen:</h3>
		</div>
		<div class="DSSuche" style="width: 60%; float: left;" id="OrderItemOrder-">
			<input id="scanid" name="scanid" onkeyup="checkIn(this, event, 'parcelId', '<?php print $packOrder->getOrderId()."','".$_SESSION["pickId"]."','".$_SESSION['ItemScanKey']."',''"; ?> )" autocomplete="off" style="width: 90%;" >
			<script type="text/javascript">FocusOnInput("scanid");</script>
		</div>


</div>
