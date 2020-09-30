<div class="DSEdit">
	<div class="DSFeld4 smallBorder">
		<?php
			print $rowCount." Datensätze exportiert!<br/>";
			if (strlen($errorList) > 0) {
				print "<h3>Folgende Fehler sind aufgetreten!</h3>";
				print "<div class=resultBox>";
				print $errorList;
				print "</div>";
			}
		?>
	</div>
</div>
