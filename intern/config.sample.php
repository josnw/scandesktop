<?php

// Alianz API Config

$allianz_stock_user = 'apiuser';
$allianz_stock_key= 'api-key';
$allianz_stock_url = 'apiurl';


// Tradebyte Config
$TradebyteWebshopNumber = 1;

$isPaidPaymentTypes = ['otdm' ];

$channelFacData['ebde']['CustomerNumber'] = 200001;
$channelFacData['ebde']['Filiale'] = 918;
$channelFacData['ebde']['formId'] = '0090';

$channelFacData['otdm']['CustomerNumber'] = 200002;
$channelFacData['otdm']['Filiale'] = 918;
$channelFacData['otdm']['formId'] = '0091';


// Shopware 5 Config
$ShopwareWebshopNumber = 2;
$ShopwarePriceGroup = 'EK';
$ShopwareIdWebshop = 1;  // SW5 = ShopID
$ShopwareStockList = [0,36];

$shopware_url = 'https://host/api';
$shopware_user = 'apiuser';
$shopware_key = 'apikey';

// If different customer number ranges in shopware is used, you can map numbers less then CustomerNumber 
// to Mappingnumber + Shopwarenumber
$channelFacData['shopware']['CustomerNumber'] = 10000;

//alternate to Group CustomerNumber SW6 
//$channelFacData['shopware']['Customer'][WEBSHOPID] = 100001;

$channelFacData['shopware']['MappingNumber'] = 10000;
$channelFacData['shopware']['GroupCustomer'] = false;
$channelFacData['shopware']['Filiale'] = 918;
$channelFacData['shopware']['formId'] = '0001';

// Shopware 6 Config
$shopware6WebshopNumber = 2;
$shopware6PriceBase = 'Bruttopreis 1';
$shopware6NetPriceBase = false;  // define PriceBaseName or false
$shopware6IdWebshop = [
	// ChannelID for order download
];
$shopware6Visibilities = [
		// ChannelID for visibilities
		
];

$shopware6DeliveryTimeIds = [
		'now' => '', // sofort
		'never' => '', // nicht Lieferbar
];

$shopware6StockList = [0,36];
$shopware6StockCheckOrders = true;
$shopware6DynamicStock = [1];


$shopware6CurrencyId = '5235734534790347953475037';
$shopware6MediaFolderId = '342ß573490789728977897657589';
$shopware6CategoryCmsPageId = '3489534895734892335749';

// If not set, WWS Category is used
$sw6GroupMatching = "./intern/data/sw6GroupMatching.json";
$shopware6CategoryMatchingFieldName = "wws_category";

//if Set upload CLp Dat ati Lenz CLP Plugin
$shopware6LenzCLP = true;

// load other prices than facto id 6 
$shopware6AlternatePrices = true;
// set clouseout if stock 0
$shopware6SetCloseout=false;
// set maxpurcase to stock 
$shopware6SetMaxPurchaseToStock=true;
// update no prices 
$shopware6NoPrices = true;
// upload Bezeichnung intern
$shopware6AlternateProductname= true;
// load hsnr no linr
$shopware6UseHsnr=true;
// wws discount group as tag 
$shopware6setDiscountTag=false;
// ProtertyFile for filter
$sw6PropertyFile = "./intern/data/sw6PropertyFile.json";


$shopware6_url = 'https://host/api';
$shopware6_user = 'apiuser';
$shopware6_key = 'apikey';

// If different customer number ranges in shopware is used, you can map numbers less then CustomerNumber
// to Mappingnumber + Shopwarenumber
$channelFacData['shopware6']['CustomerNumber'] = 10000;

//alternate to Group CustomerNumber SW6
//$channelFacData['shopware']['Customer'][WEBSHOPID] = 100001;

$channelFacData['shopware6']['MappingNumber'] = 10000;
$channelFacData['shopware6']['GroupCustomer'] = false;
$channelFacData['shopware6']['Filiale'] = 918;
$channelFacData['shopware6']['formId'] = '0001';




// WWS Config

$security_distance_abs = 1;
$security_distance_rel = 0.1;
$dynamic_stock_upload = ['max' => 30, 'divisor' => 2, 'history' => false];

$facImportFile = './docs/ScanDesktopImport.FAC';
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

// Scandesktop 
$parcelServices = [ 'dhl' => [ 'V01PAK' => 'DHL Paket', 'V62WP' => 'DHL Warenpost' ],
		'gls' => [ null => '']
];

# Packstation
$configPrinter["label"]["cupsname"] = "Dummy Label printer";
$configPrinter["label"]["pyWebPrint"] = "localhost:8091";
$configPrinter["a4"]["cupsname2"] = "Dummy A4 printer";

$configStorePlace["Komplett"] = ".*";
$configStorePlace["Lager 1"] = ".*L1$";


$sender = "me and myself";
$sender_email = "me@mydomain.xyz";
$reply_email = "someone@mydomain.xyz"; 

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

			
######## Menu  ##############
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
