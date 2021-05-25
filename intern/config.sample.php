<?php

// Alianz API Config

$allianz_stock_user = 'apiuser';
$allianz_stock_key= 'api-key';
$allianz_stock_url = 'apiurl';


// Tradebyte Config
$TradebyteWebshopNumber = 1;

$security_distance_abs = 1;
$security_distance_rel = 0.1;

$isPaidPaymentTypes = ['otdm' ];

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
$ShopwareIdWebshop = 1;
$ShopwareStockList = [0,36];
$ShopwareCurrencyId = 'b7d2554b0ce847cd82f3ac9bd1c0dfca';
$shopware_url = 'https://host/api';
$shopware_user = 'apiuser';
$shopware_key = 'apikey';


// If different customer number ranges in shopware is used, you can map numbers less then CustomerNumber 
// to Mappingnumber + Shopwarenumber
$channelFacData['shopware']['CustomerNumber'] = 10000;
$channelFacData['shopware']['MappingNumber'] = 10000;
$channelFacData['shopware']['GroupCustomer'] = false;
$channelFacData['shopware']['Filiale'] = 918;
$channelFacData['shopware']['formId'] = '0001';

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

//$Shipping['article'] = '01';
$Shipping['fromArticle'] = '01';
$Shipping['toArticle'] = '05';

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
			
######## Men�eintr�ge ##############
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
