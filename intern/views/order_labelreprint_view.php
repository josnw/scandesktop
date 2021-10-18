<div class="DSEdit smallBorder"> 
	<div class="DSFeld1">Label Nachdruck<br>
		<?php
			foreach ($shippingDocuments as $document ) {
				print '<form action="#" method="POST" enctype="multipart/form-data" >';
				print '<input type = hidden name="filename" value="'.$document["filename"].'">';
				print '<input type = submit name="labelRePrint" value="'.$document["createAt"].'">';
				print "</form>";
			}
		?>
	</div>
</div>
	