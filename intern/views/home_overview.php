<div class="DSEdit">
    <h3>Shopaufträge nach PackStatus</h3>
    <div class="DSEdit">
     <?php 
         foreach($info["byPackStat"] as $stat) {
             print "<div class=DSFeld2>".$stat["ktos"]."</div>";
             print "<div class=DSFeld2>".$packStat[$stat["cnt"]]."</div>";
             print "<div class=DSFeld2>".$stat["cnt"]."</div>";
         }
     
     ?> 
    </div>
</div>

<div class="DSEdit">
    <h3>Shopaufträge nach PackUser</h3>
    <div class="DSEdit">
     <?php 
         foreach($info["byUser"] as $stat) {
             print "<div class=DSFeld2>".$stat["fenr"]."</div>";
             print "<div class=DSFeld2>".$stat["qna1"]."</div>";
             print "<div class=DSFeld2>".$stat["cnt"]."</div>";
         }
     
     ?> 
    </div>
</div>

<div class="DSEdit">
    <h3>Shopaufträge nach Datum</h3>
    <div class="DSEdit">
     <?php 
         foreach($info["byDate"] as $stat) {
             print "<div class=DSFeld2>".$stat["fdtm"]."</div>";
             print "<div class=DSFeld2> </div>";
             print "<div class=DSFeld2>".$stat["cnt"]."</div>";
         }
     
     ?> 
    </div>
</div>
