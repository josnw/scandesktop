<div class="DSEdit">
   <?php 
   if (( (($info["quoteToday"] < 0.5) and ($info["openToday"] > 10)) 
         or ($info["openToday"] > 10))
   		and (date("H") > 9) and (date("H") < 11)) {
       print "<h1><error>ACHTUNG! Packquote und offene Bestellungen prüfen</error></h2>";
       print "Packquote heute: ".$info["quoteToday"]. "offene Bestellungen: ".$info["openToday"];
   }
   ?>
</div>

<div class="DSEdit">
    <h3>Shopaufträge nach PackStatus</h3>
    <div class="DSEdit">
     <div class=DSFeld2><b>Status</b></div>
     <div class=DSFeld2><b>Bezeichnung</b></div>
     <div class=DSFeld2><b>Anzahl</b></div>
     <div class=DSFeld1> </div>
     <?php 
     foreach($info["byPackStat"] as $stat) {
       		 print '<form action="#" method="POST" enctype="multipart/form-data" >';
             print "<div class=DSFeld2>".$stat["ktos"]."</div>";
             print "<div class=DSFeld2>".$packStat[$stat["ktos"]]."</div>";
             print "<div class=DSFeld2>".$stat["cnt"]."</div>";
             print "<div class=DSFeld1>";
             print '<input type=hidden name="orderStatus" value="'.$stat["ktos"].'">';
             if ($_SESSION['level'] > 5) {
             	print '<input type="submit" name="showOrderDetails" value="Details anzeigen">';
             }
             print "</div></form>";
             if (!empty($orderOverview) and ($_POST["orderStatus"] == $stat["ktos"])) {
             	$lastorder = 0;
             	print '<div class="DSEdit">';
             	foreach($orderOverview as $order) {
             		if ($order["fnum"] <> $lastorder) {
             			if ($lastorder > 0) { print "</div>"; }
             			print "<div class=DSFeld4>";
             			$lastorder = $order["fnum"];
             			print "<b>".$order["fxnr"]." ".$order["fnum"]." ".$order["fdtm"]." ".
               					$order["qna1"]." ".$order["qna2"]." ".$order["qort"]."</b><br/> ";
             		}
             		print " --> ".$order["arnr"]." ".$order["abz1"]." ".$order["abz2"]." ".$order["fmge"]." ".$order["ageh"]."<br/> ";
             	}
             	print "</div></div>";
             	
             }
     }
     
     ?> 
    </div>
</div>

<div class="DSEdit">
    <h3>Shopaufträge nach PackUser</h3>
    <div class="DSEdit">
     <div class=DSFeld2><b>Personal</b></div>
     <div class=DSFeld2><b>Name</b></div>
     <div class=DSFeld2><b>offen auf Packliste</b></div>
     <div class=DSFeld1>  </div>
      
     <?php 
         foreach($info["byUser"] as $stat) {
             print '<form action="#" method="POST" enctype="multipart/form-data" >';
             print "<div class=DSFeld2>".$stat["fenr"]."</div>";
             print "<div class=DSFeld2>".$stat["qna1"]."</div>";
             print "<div class=DSFeld2>".$stat["cnt"]."</div>";
             print "<div class=DSFeld1>";
             print '<input type=hidden name="pickid" value="'.$stat["minpickid"].'">';
             print '<input type=hidden name="penr" value="'.$stat["fenr"].'">';
             if ($_SESSION['level'] > 5) {
             	print '<input type="submit" name="resetPicklist" value="reset Picklist">';
             }
             print "</div></form>";
         }
     
     ?> 
    </div>
</div>

<div class="DSEdit">
    <h3>Shopaufträge nach Datum</h3>
    <div class="DSEdit">
     <div class=DSFeld2><b>Importdatum</b></div>
     <div class=DSFeld2> </div>
     <div class=DSFeld2><b>Anzahl</b></div>
     <?php 
         foreach($info["byDate"] as $stat) {
             print "<div class=DSFeld2>".$stat["fdtm"]."</div>";
             print "<div class=DSFeld2> </div>";
             print "<div class=DSFeld2>".$stat["cnt"]."</div>";
         }
     
     ?> 
    </div>
</div>
