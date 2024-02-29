<?php 

$instkliste = 0;
$orderPacked = 2;
// print "<pre>"; print_r($item); print "</pre>";
foreach($item as $itemrow) {

	 if (($instkliste == 1) and ($itemrow["astl"] != 2)) { print "</div>"; }
	 
	 if ($itemrow["fmge"] == $itemrow["fmgl"]) { 
		$statusclass = 'packed'; 
	 } elseif ($itemrow["fmgl"] > 0) { 
		$statusclass = 'partpacked'; 
		$orderPacked = 1;
	 } else { 
		$statusclass = ''; 
		$orderPacked = 0;
	 } 
	 if (!isset($itemrow["packNumber"])) { $itemrow["packNumber"] = ''; }
	 
	 print '<div class="DSEdit flexnowrap '.$statusclass.'" id="OrderItem'.$itemrow["arnr"].'-'.$itemrow["packNumber"].'">';
	 
	 if ($itemrow["astl"] == 1) {
	    print '<div class="DSFeld5">Stückliste '.$itemrow["arnr"].' /';
	    print ' '.$itemrow["abz1"].' '.$itemrow["abz2"].'</div>';
	 } else {
	    print '<div class=DSFeld1>'.$itemrow["arnr"].'</div>';
	    print '<div class="DSFeld2 bigFont">'.$itemrow["abz1"].' '.$itemrow["abz2"].' '.$itemrow["abz3"].'</div>';
	 }
	 if ($itemrow["astl"] != 1) {
		
		print '<div class="DSFeld1 bigFont centerText">'.number_format($itemrow["fmge"]).'</div>';
		print '<div class="DSFeld1 bigFont centerText" id="OrderItemPackAmount'.$itemrow["arnr"].'-'.$itemrow["packNumber"].'">'.number_format($itemrow["fmgl"]).'</div>';
	
	 } else {
		 $instkliste = 1;
	 }
	 print '</div>'; 
}
 
?>