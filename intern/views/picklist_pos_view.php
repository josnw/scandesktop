<?php 

foreach($pickListData->getItemList() as $item => $itemdata) {
 
 print '<div class="DSEdit flexnowrap " id="OrderItem'.$item.'">';
 print '<div class="DSFeld1  mediFont">'.$item.'<br/>'.$itemdata["Gtin"].'</div>';
 print '<div class="DSFeld2  mediFont">'.$itemdata["Bezeichnung"].'</div>';
 print '<div class="DSFeld1 centerText mediFont">'.number_format($itemdata["Menge"]).' '.$itemdata["Einheit"].'</div>';
 print '</div>';

}

?>
<div class="DSEdit flexnowrap noprint">
		<div class="DSFeld2" style="background: #AA5555;"><input type="button" onclick="window.print()" value="Pickliste drucken"></div>
</div>
