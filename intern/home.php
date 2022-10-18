

<h2>Updateinfos</h2>
<div class="DSEdit">

<table width=70% border=0>
<?php

 include './intern/autoload.php';
 include ("./intern/config.php");
 
 if ( (isset($_POST["resetPicklist"]) or (isset($argv) and in_array("/resetPicklist", $argv))) and 
 	  ($_SESSION['level'] > 5) ) {
 	$pickListData = new picklist($_POST["pickid"]);
 	$pickListData->resetPicklist($_POST["penr"]);
 }
	
 
 $updInfo = new myFile("./intern/updateinfo.txt","read");
 
 while($line = $updInfo->readCSV()) {
	if( strtotime($line[0]) > (time()-60*60*24*30)) {
		print "<tr>";
		print "<td valign=top><br/>".$line[0]."</td>";
		print "<td valign=top><h4>".$line[1]."</h4>";
		print $line[2]."</td>";
		print "</tr>";
	}
  }
  
  $updInfo->close(); 
  
  // Funktionsmenü Pickliste
  $userData = new user($_SESSION["uid"]);
  $info = $userData->getAllStat();
  
  $orderOverview = [];
  if ( (isset($_POST["showOrderDetails"]) or (isset($argv) and in_array("/showOrderDetails", $argv))) and
  		($_SESSION['level'] > 5) ) {
  			$orderOverview = $userData->getOrderOverview($_POST["orderStatus"]);
  		}
  		
  
  include("./intern/views/home_overview.php");
 ?>
 </table>
 
 </div>