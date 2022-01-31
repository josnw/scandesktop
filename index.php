<?php
ini_set('session.gc_maxlifetime', 36000);
session_set_cookie_params(36000);session_start();
include_once './intern/functions.php';
include_once './intern/auth.php';
include_once './intern/setCookies.php';
include_once './intern/views/header.php';

$usertyp = $_SESSION['typ'];

#if ( $_SESSION['penr'] <> '999') {
# print "<BR><error>Wegen Wartungsarbeiten geschlossen!</error>";
# exit;
#}

print "<nav>\n";
print "<div class=navinfo>Sie sind eingelogt als:<BR>".$_SESSION['name']." (".$usertyp." L".$_SESSION['level'].")</div>";
print "<ul>\n";
$aktiv = '';
foreach($menu_name[$usertyp] as $menu => $file) {
	if (isset($_GET['menu']) and ($menu == $_GET['menu']) ) { $aktiv='"aktiv"'; } else { $aktiv = '""'; }
	print "<li>";
	print "<a class= ".$aktiv." href=\"./index.php?menu=".$menu."\" >".$menu."</a>\n";
	print "</li>";
};
print "</ul>\n";
print "</nav>\n";

print "<main>";
if (isset($_GET['menu']) and strlen($_GET['menu']) > 0) {
   foreach($menu_name[$usertyp] as $menu => $file) {
	   if ($menu == $_GET['menu']) {
		  include './intern/'.$file;
		  Proto("Menüpunkt ".$file." gestartet. (".$_SERVER['REMOTE_ADDR'].")");
	   };
	}; 
} else {
	 include './intern/home.php';
}
?>
</main>
<footer>
<div id="infobox"><?php if (!empty($_SESSION["infobox"])) { print $_SESSION["infobox"]; } ?></div>
<div><?php print date("Y-m-d h:i"); ?></div>
</footer>
</body>
</html>
