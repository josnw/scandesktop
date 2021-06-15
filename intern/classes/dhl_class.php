<?php

class dhl {
	
	private $my_pdo;
	private $pg_pdo;
	
	function __construct() {
		include ("./intern/config.php");

		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		
		return true;
	}
	
	private function modulo10($str)	{
		$out = '';
		$sum = 0;
		if (is_string($str)) {
			for ($i=0;$i<strlen($str); $i++)	{
				$out = $out . Ord(substr($str,$i,1));
			}
		}
		else
			$out=$str;

		// is the length odd or even
		if ((int)(strlen($out)/2) == (int)((strlen($out)/2)+0.9))
			$m=0;
		else
			$m=1;

		// sum the values for each digit, take care of values > 9
		for ($i=0;$i<strlen($out); $i++)	{
			$m=($m==1)?2:1;
			$v=$m*substr($out, $i, 1);
			if ($v>9)
				$v=(substr($v, 0, 1)+substr($v, 1, 1));
			$sum = $sum + $v;
		}

		// what is the check digit??
		$cd=(round($sum/10+0.49)*10) - $sum;
		print $str." / ".$cd;
		return $cd;

	}

	private function interleaved25($str) {
		$sum = 0;
		$m=3;

		// sum the values for each digit, take care of values > 9
		for ($i=0;$i<strlen($str); $i++)	{
			$v=$m*substr($str, $i, 1);
			$sum = $sum + $v;
			$m=($m==1)?3:1;
		}

		// what is the check digit??
		return 10 - ($sum % 10);

	}
	
	public function checkIdent($identCode) {
		if (strlen($identCode) == 20) {
			if ($this->interleaved25(substr($identCode,0,-1)) == substr($identCode,-1)) {
				return true;
			} else {
				return false;
			}
		} elseif (strlen($identCode) == 12) {
			if ($this->modulo10(substr($identCode,0,-1)) == substr($identCode,-1)) {
				return true;
			} else {
				return false;
			}
		} elseif ($identCode == '1' and DEBUG == 1) {
			return true;
		}
		return false;

	}

	public function checkOrderId($identCode, $orderId) {
		
		/*
		// Der Import erfolgt erst beim Abschluss, von daher im Prozess nicht integrierbar.
		$oqry  = 'select ship_trackingId from versand where BelegID = :BelegID';

		$r_qry = $this->my_pdo->prepare($oqry);
		$r_qry->bindValue(':BelegID', $orderId);
		$r_qry->execute() or die (print_r($r_qry->errorInfo()));
		$row = $r_qry->fetch( PDO::FETCH_ASSOC );
		if ($row["ship_trackingId"] == $identCode) {
			return true;
		} else {
			return false;
		} 
		*/
		return true;
	}

	public function setTrackingId($identCode, $orderId) {
		/*
		$oqry  = 'insert into versand_tracking (BelegID, TrackingCode) values (:BelegID, :TrackingCode)';

		$r_qry = $this->my_pdo->prepare($oqry);
		$r_qry->bindValue(':TrackingCode', $identCode);
		$r_qry->bindValue(':BelegID', $orderId);
		$result = $r_qry->execute();
		if ($r_qry->execute()) {
			return [ "status" => true ];
		} else {
			return [ "status" => false, "info" =>  $r_qry->errorInfo() ];
		} 
		*/
		return true;
	}
	
	public function trackingIdImport() {
		

	}
}	


?>
