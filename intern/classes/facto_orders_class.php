<?php

class factoOrders {
		
	private $orderId;
	private $my_pdo;
	private $pg_pdo;
	
	private $head;
	private $ifnr;
	private $mdnr;
	private $positions;
	private $posCount;
	private $posPointer;
	private $newFnum;
	private $newFblg;
	
	public function __construct($orderIfnr, $orderId) {

		include ("./intern/config.php");

		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		$this->ifnr = $OrderIfnr;
		$this->orderId = $orderId;

		$fqry = "select distinct(mdnr) from mand_0";
		$f_qry = $this->pg_pdo->prepare($fqry);
		$mdnr = $f_qry->fetch( PDO::FETCH_ASSOC );
		$this->mdnr = $mdnr['mdnr'];


	}
	
	private function readDBHead() {
		$fqry = "select a.* from beleg_id i inner join auftr_kopf a on a.fblg = i.fnum where i.fblg = :fblg and i.ifnr = :ifnr";
		
		$f_qry = $this->pg_pdo->prepare($fqry);
		$f_qry->bindValue(':ifnr',ifnr);
		$f_qry->bindValue(':fblg',$orderId);
		$f_qry->execute() or die (print_r($f_qry->errorInfo()));

		$this->head = $f_qry->fetch( PDO::FETCH_ASSOC );
	}
	
	private function readDBPos() {
		$fqry = "select a.* from beleg_id i inner join auftr_pos a on a.fblg = i.fnum where i.fblg = :fblg and i.ifnr = :ifnr";
		
		$f_qry = $this->pg_pdo->prepare($fqry);
		$f_qry->bindValue(':ifnr',ifnr);
		$f_qry->bindValue(':fblg',$orderId);
		$f_qry->execute() or die (print_r($f_qry->errorInfo()));

		$this->positions = $f_qry->fetchAll( PDO::FETCH_ASSOC );
		
		$this->posCount = count($this->positions);
		$this->posPointer = 0;
	}
	
	public function getHead() {
		if(! isset($this->head) or ! is_array($this->head)) {
			$this->readDBHead();
		}
		return $this->head;
	}
	
	public function getPositions() {
		if(! isset($this->positions) or ! is_array($this->positions)) {
			$this->readDBPos();
		}
		return $this->positions;
	}

	public function getNextPosition() {
		if(! isset($this->positions) or ! is_array($this->positions)) {
			$this->readDBPos();
		}
		if ($this->posPointer < $posCount) {
			return $this->positions[$this->posPointer++];
		} else {
			return false;
		}
	}
	
	public function duplicateOrder( $newOrderTyp, $overrides = NULL, $artilceList) {
		if(! isset($this->head) or ! is_array($this->head)) {
			$this->readDBHead();
		}
		$saveNewOrderTyp = preg_replace("[0-9]","",$newOrderTyp);
		$sql = "insert into auftr_kopf (";
		$cnt = 0;
		foreach (array_keys($this->head) as $key ) {
			if ($cnt++ > 0) { $sql .= ","; }	
			$sql .= $key;
		}
		$sql .= ") select ";
		foreach (array_keys($this->head) as $key ) {
			if ($cnt++ > 0) { $sql .= ","; }	
			if ( key == 'fnum') {
			    $sql .= "nextval('gen_numk_".sprintf("%06d",$this->mdnr).'_'.sprintf("%03d",$saveNewOrderTyp)."_00')";
			} elseif ( key == 'fblg') {
			    $sql .= "nextval('gen_belegnummer')";
			} elseif ( key == 'ftyp') {
			    $sql .= $saveNewOrderTyp;
			} else {
				$sql .= $key;	
			}
		}
		$sql .= ' from auftr_kopf where fblg = :fblg';
		print $sql;

		$f_qry = $this->pg_pdo->prepare($sql);
		$f_qry->bindValue(':fblg',$this->head['fblg']);
		//$f_qry->execute() or die (print_r($f_qry->errorInfo()));
		$this->newFnum = $f_qry->lastInsertId('fnum');
		$this->newFblg = $f_qry->lastInsertId('fblg');
		
		$in = '';
		for($i = 0; $i < count($articleList); $i++) {
			if ($i>0)  { $in .= ','; }
			$in .= ":arnr".$i;
		}
		
		$sql = "insert into auftr_pos (";
		$cnt = 0;
		foreach (array_keys($this->head) as $key ) {
			if ($cnt++ > 0) { $sql .= ",";}	
			$sql .= $key;	
		}
		$sql .= ") select ";
		foreach (array_keys($this->head) as $key ) {
			if ($cnt++ > 0) { $sql .= ","; }	
			if ( key == 'fnum') {
			    $sql .= $this->newFnum;
			} elseif ( key == 'fblg') {
			    $sql .= $this->newFblg;
			} elseif ( key == 'ftyp') {
			    $sql .= $saveNewOrderTyp;
			} else {
				$sql .= $key;
			}
		}
		$sql .= ' from auftr_pos where fblg = :fblg';
		$sql .= " and arnr in ( $in )";
		
		print $sql;

		$f_qry = $this->pg_pdo->prepare($sql);
		$f_qry->bindValue(':fblg',$this->head['fblg']);
		
		for( $i = 0; $i < count($articleList); $i++) {
			$f_qry->bindValue(':arnr'.$i,$articleList[$i]);
		}

		//$f_qry->execute() or die (print_r($f_qry->errorInfo()));
		
		if( $overrides ) {
			$this->overideData($overrides);
		}
		
		return [ "fnum" => $this->newFnum, "fblg" => $this->newFblg ];
		
	}
	
	private function overideData($overrides) {
		
		// Modify new order head data 
		$sql = "update auftr_kopf set ";
		$cnt = 0;
		foreach(array_keys($overrides["head"]) as $key) {
			$saveKey = preg_replace("[A-Za-z0-9_ ]","",$key);
			if (!$cnt++) { $sql .= ",";}
			$sql .= $saveKey. " = :".$saveKey;
		}		
		$sql .= " where fblg = :fblg";
		
		$f_qry = $this->pg_pdo->prepare($sql);
		$f_qry->bindValue(':fblg',$this->newFblg );

		foreach($overrides["head"] as $key => $value) {
			$saveKey = preg_replace("[A-Za-z0-9_ ]","",$key);
			$f_qry->bindValue(':'.$saveKey, $value );
		}				
		print $sql;
		//$f_qry->execute() or die (print_r($f_qry->errorInfo()));

		// Modify new order pos data 
		foreach($overrides["positions"] as $article => $data) {

			$sql = "update auftr_pos set ";
			$cnt = 0;
			foreach(array_keys($data) as $key) {
				$saveKey = preg_replace("[A-Za-z0-9_ ]","",$key);
				if (!$cnt++) { $sql .= ",";}
				$sql .= $saveKey. " = :".$saveKey;
			}		
			$sql .= " where fblg = :fblg";
			
			$f_qry = $this->pg_pdo->prepare($sql);
			$f_qry->bindValue(':fblg', $this->newFblg );
			$f_qry->bindValue(':arnr', $article );

			foreach($data as $key => $value) {
				$saveKey = preg_replace("[A-Za-z0-9_ ]","",$key);
				$f_qry->bindValue(':'.$saveKey, $value );
			}				
			print $sql;
			//$f_qry->execute() or die (print_r($f_qry->errorInfo()));
		}
		
	}
	
}	
	
	
	