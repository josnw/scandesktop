<?php
 include './intern/autoload.php';
 include ("./intern/config.php");
 
  // Artikelauswahl Formular
  	include("./intern/views/tbpanda_select_view.php");
  
  // Mapping Formular
  // vorerst nur Standard
    include("./intern/views/tbpanda_mapping_view.php");
  
  // Export
    if (isset($_POST["pandaDownload"])) {
		$export = new tradebytePanda();					
		if ($export->selectByQgrpLinr($_POST["vonlinr"],$_POST["bislinr"],$_POST["vonqgrp"],$_POST["bisqgrp"])) {
			$exportfile = $docpath."panda_".date("Ymd_his").".csv";
			
			$rowCount = $export->exportToFile($exportfile);
			
			include("./intern/views/tbpanda_result_view.php");
			
		}
		
	}
  
  
 ?> 