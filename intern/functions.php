<?php

function wochentag($datum) {
	$trans = array(
    'Monday'    => 'Montag',
    'Tuesday'   => 'Dienstag',
    'Wednesday' => 'Mittwoch',
    'Thursday'  => 'Donnerstag',
    'Friday'    => 'Freitag',
    'Saturday'  => 'Samstag',
    'Sunday'    => 'Sonntag');
	
	return $trans[date("l",strtotime($datum))];
}

function timeLen($sec)
{
    $s=$sec % 60;
    $m=(($sec-$s) / 60) % 60;
    $h=floor($sec / 3600);
    return sprintf("%02d",$h).":".substr("0".$m,-2); //.":".substr("0".$s,-2);
}

function time2sec($timestring) {

	return strtotime("1970-01-01 ".$timestring." UTC");
}

function time2dec($timestring, $dtrenn = ",") {
	$zeit = explode(":",$timestring);
	$format = 0;
	if (count($zeit) == 3) {
		$dectime =  $zeit[0]+($zeit[1]/60)+($zeit[1]/3600);		
		$format = 1;
	} elseif (count($zeit) == 2) {
		$dectime =  $zeit[0]+($zeit[1]/60);
		if (($zeit[1]*100) % 60 ) { $format = 1;}
	} else {
		$dectime =  $zeit[0];
	} 
	if ($format == 0) {	$dectime = sprintf("%0.2f",$dectime); } else {	$dectime = sprintf("%0.7f",$dectime); }
	return str_replace(".",$dtrenn,$dectime); 
}


function Proto($logdata) {

 $log = fopen($_SERVER['DOCUMENT_ROOT']."/calendar/log/Protokoll_".date("Y-m").".log","a+");
 fwrite ($log, date("Y.m.d H:i")."\t".$_SESSION['user']."\t".$logdata."\n");
 fclose($log);

}

 
?>