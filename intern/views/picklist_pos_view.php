<?php 

foreach($pickListData->getItemList() as $item => $itemdata) {

 print '<div class="DSEdit flexnowrap " id="OrderItem'.$item.'">';
 print '<div class="DSFeld1  mediFont">'.$itemdata["arnr"].' (L'.$itemdata["alag"].')<br/>'.$itemdata["asco"].'</div>';
 print '<div class="DSFeld2  mediFont">'.$itemdata["abz1"]." ".$itemdata["abz2"].'</div>';
 print '<div class="DSFeld1 centerText mediFont">'.number_format($itemdata["fmge"]).' '.$itemdata["ameh"].'</div>';
 print '</div>';

}

?>
<form action="#" method="POST" enctype="multipart/form-data" >
<div class="DSEdit flexnowrap noprint">
		<div class="DSFeld2" style="background: #55AA55;"><input type="button" onclick="window.print()" value="Pickliste lokal drucken"></div>
		<div class="DSFeld2" style="background: #55AA55;"><input type="submit" name="pickListSrvPrint" value="Pickliste Serverprint"></div>
		<div class="DSFeld2" style="background: #AAAA55;"><input type="submit" name="pickListExport" value="Pickliste Bestellung exportieren"></div>
</div>
</form>