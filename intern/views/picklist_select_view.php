<h1>offene Picklisten</h1>
<div class="DSEdit">
<?php
	foreach($userPickData as $pickList) {
		print '	<form action="#" method="POST" enctype="multipart/form-data" >';
		print '	<input type=hidden name="pickID" value="'.$pickList["fprn"].'">';
		print '<div class="DSEdit ">';
		print '	<div class="DSFeld1">Liste<br> '.$pickList["fprn"].'</div>';
		print '	<div class="DSFeld2">Name<br>'.$pickList["ktou"].'</div>';
		print '	<div class="DSFeld1">Status<br> '.$pickList["ktos"].'</div>';
		print '	<div class="DSFeld1"><input type=submit name="editPickList" value="Liste bearbeiten"></div>';
		print '</div>';
		print '</form>';
	}
?>
</div>