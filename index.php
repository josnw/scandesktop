<?php
ini_set('session.gc_maxlifetime', 36000);
session_set_cookie_params(36000);session_start();

?>
<!DOCTYPE html>
<html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" type="text/css" href="./css/master.css" media=screen>
<link rel="stylesheet" type="text/css" href="./css/master5print.css" media=print>
<script src="./js/jquery-1.4.2.js" type="text/javascript"></script>
<script type="text/javascript" src="./js/ui17/ui/jquery-ui.js"></script>
<link rel="stylesheet" type="text/css" href="./js/ui17/themes/base/jquery-ui.css"/>

<script src="./js/script.js" type="text/javascript"></script>
	
<title>Scandesktop</title>
</head>
<body >

<header>
    <a href="index.php" title="Zur Startseite"><img src="./css/logo.png"></a>
</header>
<?php
include_once './intern/config.php';
include_once './intern/functions.php';
include_once './intern/auth.php';

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
<div id="infobox">...</div>
<div><?php print date("Y-m-d h:i"); ?></div>
</footer>
</body>
</html>
