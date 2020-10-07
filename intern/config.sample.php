<?php


// Alianz API Config

$allianz_stock_user = 'apiuser';
$allianz_stock_key= 'api-key';
$allianz_stock_url = 'apiurl';


// Tradebyte Config
$TradebyteWebshopNumber = 1;

$security_distance_abs = 1;
$security_distance_rel = 0.1;

$isPaidPaymentType = ['otdm' ];

$channelFacData['ebde']['CustomerNumber'] = 200001;
$channelFacData['ebde']['Filiale'] = 918;
$channelFacData['ebde']['formId'] = '0090';

$channelFacData['otdm']['CustomerNumber'] = 200002;
$channelFacData['otdm']['Filiale'] = 918;
$channelFacData['otdm']['formId'] = '0091';


// Shopware Config
$ShopwareWebshopNumber = 2;
$ShopwarePriceGroup = 'EK';
$ShopwarePriceBase = 'Bruttopreis 1';
$ShopwareStockList = [0,36];
	
$shopware_url = 'https://host/api';
$shopware_user = 'apiuser';
$shopware_key = 'apikey';

// WWS Config
$facImportFile = './docs/ScanDesktopImport.FAC'
$scanDeskFacFiliale = 916;
$FacFiliale = 1;

$options  = null;
$wwsserver	= "pgsql:host=;port=5432;dbname=";
$wwsuser='';
$wwspass='';

$wwsAdminUsers = [ 999, 998 ];
$wwsChiefGroups = [ 1,2 ];


// Scandesktop intern 

date_default_timezone_set("Europe/Berlin");

$DEBUG=0;
$error_qna1=0;
$error_qort=0;
$error_osdt=0;
$devel=0;

$docpath = "docs/";

$parcelPath["DHL"] = 'docs/';
$parcelPath["DPD"] = '';

$sender = "me and myself"; 
$sender_email = "me@mydomain.xyz"; 
$reply_email = "someone@mydomain.xyz"; 
			
######## Menüeinträge ##############
$menu_name['root']['Startseite']  = './home.php';
$menu_name['root']['Test']  = './test.php';
$menu_name['root']['Shopware']  = './shopware.php';
$menu_name['root']['Tradebyte']  = './tbpanda.php';
$menu_name['root']['Allianz Daten']  = './allianz_data.php';
$menu_name['root']['Versand']  = './shipment.php';
$menu_name['root']['Logout']  = './logout.php';

$menu_name['user']['Startseite']  = './home.php'; 
if (isset($_SESSION["uid"])) {
	if ($_SESSION['level'] >= 0) { $menu_name['user']['Versand']  = './shipment.php'; }
}

$menu_name['user']['Logout']  = './logout.php';





?>
