<h1>aktuelle Pickliste</h2> 
<div class=oderinfo style="width:500px;">
<div class=orderinfoname>Beleg:</div>
<div class=oderinfoitem> <?php print($pickListData->pickListNumber);?></div>
<div class=orderinfoname>Name</div>
<div class=oderinfoitem><?php print($pickListData->pickName);?></div>
<div class=orderinfoname>Erstellt</div>
<div class=oderinfoitem><?php print($pickListData->pickCreateDate);?></div>
<div class=orderinfoname>Status</div>
<div class=oderinfoitem><?php print($pickListData->pickStatus);?></div>
<form action="#" method="POST" enctype="multipart/form-data" >
	<div class="DSEdit noprint">
		<div class="DSFeld2" style="background: #AA5555;"><input type="submit" name="removeOrder" value="Zurückstellen"></div>
	</div>
</form>
</div>
