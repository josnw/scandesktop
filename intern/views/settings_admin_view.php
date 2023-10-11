<?php if ($_SESSION["level"] < 9) { exit; }?>
<form action="#" method="POST" enctype="multipart/form-data" >
	<div class="DSEdit">
		<div class="DSFeld2">Config PHP<br>
		Admin Passwort: <input name="password" type="password"><br/><br/>
		<textarea name=config style="width:820px; height:400px;"><?php print $confContent;?> </textarea> 
		</div>
		<div class="DSFeld2 right" style="background: #AA5555;"><input type="submit" name="setConfig" value="Speichern"></div>
	</div>
</form>