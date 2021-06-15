<?php 
 
include './intern/autoload.php';
include ("./intern/config.php");

$shopwareApi = new OpenApi3Client($shopware6_url, $shopware6_user, $shopware6_key);
print "Swagger:<pre>\n";
$yaml = $shopwareApi->getSwagger();

//$yaml = preg_replace("/,/",",\n", $yaml);
//$yaml = yaml_emit(json_decode($yaml));

print "<textarea cols=150 rows=20>".$yaml."</textarea>"; 
print "\n</pre>Ende";
?>