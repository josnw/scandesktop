<?php
date_default_timezone_set("Europe/Berlin");

if (! function_exists("erziso")) {
function erziso($date)
 {
  $ergebnis = substr($date,8,2).".".substr($date,5,2).".".substr($date,0,4);
#  echo '<javascript> alert("Was guckst Du?")</javascript>';
  return $ergebnis;
 }
}

$DEBUG=1;
$error_qna1=0;
$error_qort=0;
$error_osdt=0;
$devel=0;

//Dauer für 1 cbm in sekunden
$cbmdauer = 1200;


$options  = null;
$wwsserver	= "pgsql:host=;port=5432;dbname=";
$wwsuser='';
$wwspass='';

$ecserver   = 'mysql:dbname=;host=; port=3306';
$ecuser     = '';
$ecpass     = '';


$docpath = "./docs/";
$parcelPath["DHL"] = './docs/';
$parcelPath["DPD"] = '';

######## Menüeinträge ##############
$menu_name['root']['Startseite']  = './home.php';
$menu_name['root']['Test']  = './test.php';
$menu_name['root']['Versand']  = './shipment.php';
$menu_name['root']['Logout']  = './logout.php';

$menu_name['user']['Startseite']  = './home.php'; 
if (isset($_SESSION["uid"])) {
	if ($_SESSION['level'] >= 0) { $menu_name['user']['Versand']  = './shipment.php'; }
}

$menu_name['user']['Logout']  = './logout.php';





?>
