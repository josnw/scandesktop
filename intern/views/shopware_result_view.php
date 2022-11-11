<div class="DSEdit">
	<div class="DSFeld4 smallBorder">
		<?php
			if (isset($starttime) ) { print "Dauer:".(time()-$starttime)." s<br/>"; }
			print $rowCount." Datensätze exportiert!<br/>";
			if (isset($exportfile) ) { print "<a href=".$exportfile.">[Download ".$filename."]</a>"; }
			if (strlen($errorList) > 0) {
				print "<h3>Folgende Fehler sind aufgetreten!</h3>";
				print "<div class=resultBox>";
				print $errorList;
				print "</div>";
			}
		?>
	</div>
</div>
