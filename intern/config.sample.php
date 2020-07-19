<?php
date_default_timezone_set("Europe/Berlin");

$DEBUG=0;
$error_qna1=0;
$error_qort=0;
$error_osdt=0;
$devel=0;

$allianz_stock_user = 'apiuser';
$allianz_stock_key= 'api-key';
$allianz_stock_url = 'apiurl';

$facImportFile = './docs/ScanDesktopImport.FAC'
$scanDeskFacFiliale = 916;
$FacFiliale = 1;

$security_distance_abs = 1;
$security_distance_rel = 0.1;

$options  = null;
$wwsserver	= "pgsql:host=;port=5432;dbname=";
$wwsuser='';
$wwspass='';

$wwsAdminUsers = [ 999, 998 ];
$wwsChiefGroups = [ 1,2 ];

$docpath = "docs/";

$parcelPath["DHL"] = 'docs/';
$parcelPath["DPD"] = '';

$sender = "me and myself"; 
$sender_email = "me@mydomain.xyz"; 
$reply_email = "someone@mydomain.xyz"; 
			
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
