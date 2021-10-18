<div class="DSEdit">
   <?php 
   if (( (($info["quoteToday"] < 0.5) and ($info["openToday"] > 10)) 
         or ($info["openToday"] > 10))
       and (date("H") > 9)) {
       print "<h1><error>ACHTUNG! Packquote zu gering oder offene Bestellungen zu viele!</error></h2>";
   }
   ?>
</div>

<div class="DSEdit">
    <h3>Shopaufträge nach PackStatus</h3>
    <div class="DSEdit">
     <div class=DSFeld2><b>Status</b></div>
     <div class=DSFeld2><b>Bezeichnung</b></div>
     <div class=DSFeld2><b>Anzahl</b></div>
     <?php 
     foreach($info["byPackStat"] as $stat) {
             print "<div class=DSFeld2>".$stat["ktos"]."</div>";
             print "<div class=DSFeld2>".$packStat[$stat["ktos"]]."</div>";
             print "<div class=DSFeld2>".$stat["cnt"]."</div>";
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
             print '<input type=hidden name="penr" value="'.$stat["fenr"].'">';
             print '<input type="submit" name="resetPicklist" value="reset Picklist">';
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
