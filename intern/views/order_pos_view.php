<?php 

$instkliste = 0;
$orderPacked = 2;
foreach($item as $itemrow) {
 if (($instkliste == 1) and ($itemrow["Stckliste"] != 2)) { print "</div>"; }
 
 if ($itemrow["Menge"] == $itemrow["ship_packmenge"]) { 
	$statusclass = 'packed'; 
 } elseif ($itemrow["ship_packmenge"] > 0) { 
	$statusclass = 'partpacked'; 
	$orderPacked = 1;
 } else { 
	$statusclass = ''; 
	$orderPacked = 0;
 } 
 if (!isset($itemrow["packNumber"])) { $itemrow["packNumber"] = ''; }
 print '<div class="DSEdit flexnowrap '.$statusclass.'" id="OrderItem'.$itemrow["Artikel"].'-'.$itemrow["packNumber"].'">';
 if ($itemrow["Stckliste"] == 1) {
    print '<div class=DSFeld1>Stückliste '.$itemrow["Artikel"].' /';
	print ' '.$itemrow["Bezeichnung"].'</div>';
 } else {
    print '<div class=DSFeld1>'.$itemrow["Artikel"].'</div>';
	print '<div class="DSFeld2 bigFont">'.$itemrow["Bezeichnung"].'</div>';
 }
 if ($itemrow["Stckliste"] != 1) {
	
	print '<div class="DSFeld1 bigFont centerText">'.number_format($itemrow["Menge"]).'</div>';
	print '<div class="DSFeld1 bigFont centerText" id="OrderItemPackAmount'.$itemrow["Artikel"].'-'.$itemrow["packNumber"].'">'.number_format($itemrow["ship_packmenge"]).'</div>';

 } else {
	 $instkliste = 1;
 }
 print '</div>'; 
}
 
?>