<?php
 include './intern/autoload.php';
 include ("./intern/config.php");
 
  // Artikelauswahl Formular
    if (isset($_POST['vonlinr'])) { $vonlinr = preg_replace('[^0-9]','', $_POST['vonlinr']); } else { $vonlinr = 0;}
    if (isset($_POST['bislinr'])) { $bislinr = preg_replace('[^0-9]','', $_POST['bislinr']); } else { $bislinr = 9999999;}
    if (isset($_POST['vonqgrp'])) { $vonqgrp = preg_replace('[^0-9]','', $_POST['vonqgrp']); } else { $vonqgrp = 0;}
    if (isset($_POST['bisqgrp'])) { $bisqgrp = preg_replace('[^0-9]','', $_POST['bisqgrp']); } else { $bisqgrp = 899;}
  
  	include("./intern/views/tbpanda_select_view.php");
  
  // Mapping Formular
  // vorerst nur Standard
    include("./intern/views/tbpanda_mapping_view.php");
  
  // Export
    if (isset($_POST["pandaDownload"])) {
		$export = new tradebytePanda();					
		if ($export->selectByQgrpLinr($_POST["vonlinr"],$_POST["bislinr"],$_POST["vonqgrp"],$_POST["bisqgrp"])) {
			$exportfile = $docpath."panda_".date("Ymd_his").".csv";
			
			$result = $export->exportToFile($exportfile);
			$rowCount = $result['count'];
			$exportfile = $docpath.$result['filename'];
			
			include("./intern/views/tbpanda_result_view.php");
			
		}
		
	}
  
  
 ?> 