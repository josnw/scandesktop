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
		$this->ifnr = $orderIfnr;
		$this->orderId = $orderId;
		$this->Shipping = $Shipping;
		$fqry = "select distinct(mdnr) from mand_0";
		$f_qry = $this->pg_pdo->prepare($fqry);
		$mdnr = $f_qry->fetch( PDO::FETCH_ASSOC );
		$this->mdnr = $mdnr['mdnr'];
		if ($this->ifnr == null) {
			$fqry = "select count(*) as cnt from auftr_kopf a where fblg = :fblg ";
			$f_qry = $this->pg_pdo->prepare($fqry);
		} else {
			$fqry = "select count(*) as cnt from beleg_id i inner join auftr_kopf a on a.fblg = i.fnum where i.fblg = :fblg and i.ifnr = :ifnr";
			$f_qry = $this->pg_pdo->prepare($fqry);
			$f_qry->bindValue(':ifnr',$this->ifnr);
		}
		$f_qry->bindValue(':fblg',$this->orderId);
		$f_qry->execute() or die (print_r($f_qry->errorInfo()));
		$cntdata = $f_qry->fetch( PDO::FETCH_ASSOC);
		if ($cntdata["cnt"] == 0) {
			$this->ifnr = null;
			$this->orderId = null;
		} 

	}
	
	private function readDBHead() {
	    if ($this->ifnr == null) {
	        $fqry = "select * from auftr_kopf a where fblg = :fblg ";
	        $f_qry = $this->pg_pdo->prepare($fqry);
	    } else {
	        $fqry = "select a.* from beleg_id i inner join auftr_kopf a on a.fblg = i.fnum where i.fblg = :fblg and i.ifnr = :ifnr";
	        $f_qry = $this->pg_pdo->prepare($fqry);
	        $f_qry->bindValue(':ifnr',$this->ifnr);
	    }
		
		$f_qry->bindValue(':fblg',$this->orderId);
		$f_qry->execute() or die (print_r($f_qry->errorInfo()));

		$this->head = $f_qry->fetch( PDO::FETCH_ASSOC );

	}
	
	private function readDBPos() {
	    if ($this->ifnr == null) {
	        $fqry = "select a.* from auftr_pos a where fblg = :fblg order by fpos";
	        $f_qry = $this->pg_pdo->prepare($fqry);
	    } else {
	        $fqry = "select a.* from beleg_id i inner join auftr_pos a on a.fblg = i.fnum where i.fblg = :fblg and i.ifnr = :ifnr order by fpos";
	        $f_qry = $this->pg_pdo->prepare($fqry);
	        $f_qry->bindValue(':ifnr',$this->ifnr);
	    }
		
		$f_qry->bindValue(':fblg',$this->orderId);
		$f_qry->execute() or die (print_r($f_qry->errorInfo()));

		$this->positions = $f_qry->fetchAll( PDO::FETCH_ASSOC );
		
		$this->posCount = count($this->positions);
		$this->posPointer = 0;
	}
	
	public function getOrderId() {
		return $this->orderId;
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
	
	public function duplicateOrder( $newOrderTyp,  $articleList, $overrides = NULL, $setInOrder = true) {
		include ("./intern/config.php");
	//	include ("./intern/functions.php");
		if( (!isset($this->head)) or (!is_array($this->head)) ) {
			$this->readDBHead();
			$this->readDBPos();
		} 
		
		$saveNewOrderTyp = preg_replace("[0-9]","",$newOrderTyp);
		
		// Duplicate Head
		$sql = "insert into auftr_kopf (";
		$cnt = 0;
		foreach (array_keys($this->head) as $key ) {
			if ($cnt++ > 0) { $sql .= ","; }	
			$sql .= $key;
		}
		$sql .= ")\n select ";
		$cnt = 0;
		foreach (array_keys($this->head) as $key ) {
			if ($cnt++ > 0) { $sql .= ","; }	
			if ( $key == 'fnum') {
			    $sql .= "nextval('gen_numk_".sprintf("%06d",$this->mdnr).'_'.sprintf("%03d",$saveNewOrderTyp)."_00')";
			} elseif ( $key == 'fblg') {
			    $sql .= "nextval('gen_belegnummer')";
			} elseif ( $key == 'ftyp') {
			    $sql .= $saveNewOrderTyp;
			} elseif ( $key == 'fafn') {
			    $sql .= 'fnum';
			} elseif ( $key == 'fldt') {
			    $sql .= 'current_date';
			} elseif ( $key == 'fdtm') {
			    $sql .= 'current_date';
			} else {
				$sql .= $key;	
			}
		}
		$sql .= "\n from auftr_kopf where fblg = :fblg ";
		$sql .= "\n returning fnum, fblg";
		//print $sql;

		$f_qry = $this->pg_pdo->prepare($sql);
		$f_qry->bindValue(':fblg',$this->head['fblg']);
		$f_qry->execute() or die (print_r($f_qry->errorInfo()));
		$result = $f_qry->fetch(PDO::FETCH_ASSOC); 

		$this->newFnum = $result['fnum'];
		$this->newFblg = $result['fblg'];

		Proto("Shipping: Erstelle Beleg ".$this->newFnum);
		
		
		// Duplicate Position

		if( (!isset($this->positions)) or (!is_array($this->positions)) ) {
			$this->readDBPos();
		}
		//add SetSubArticle booking amount and transport costs
		$switch = 0; $factor = 0;
		$idlist = '(TB_POS_CHANNEL_ID='.implode('|TB_POS_CHANNEL_ID=',$articleList).')';
		
		
		for($i = 0; $i < count($this->positions); $i++) {
			if (   ( isset($this->Shipping['article']) and  ( $this->positions[$i]["arnr"] == $this->Shipping['article'] ) )
				or ( isset($this->Shipping['fromArticle']) 
					  and  ( ($this->positions[$i]["arnr"] >= $this->Shipping['fromArticle']) and ($this->positions[$i]["arnr"] <= $this->Shipping['toArticle']) ) ) 
				)	{
				$articleList[] = $this->positions[$i]["arnr"];
				print "Duplicate to fnum ".$this->newFnum.": Add Transportcosts".$this->positions[$i]["arnr"]."\n"; 
			} elseif ( ($switch == 1) and ( $this->positions[$i]["fart"] == 6 ) ) {
				$articleList[] = $this->positions[$i]["arnr"];
				print "Duplicate to fnum ".$this->newFnum.": Add SubArticle".$this->positions[$i]["arnr"]."\n";
				if ($factor != null) {
					$overrides["positions"][$this->positions[$i]["arnr"]]["fmge"] = $this->positions[$i]["fmge"] * $factor;
					$overrides["positions"][$this->positions[$i]["arnr"]]["fmgb"] = $this->positions[$i]["fmgb"] * $factor;
				}
			} elseif ( (in_array($this->positions[$i]["arnr"],$articleList )) or 
			    ( preg_match($idlist, $this->positions[$i]["fabl"])) ) {
				$switch = 1;
				print "Duplicate to fnum ".$this->newFnum.": article in list".$this->positions[$i]["arnr"]."\n";
				if ((array_key_exists($this->positions[$i]["arnr"], $overrides["positions"])) and (isset($overrides["positions"][$this->positions[$i]["arnr"]]["fmge"])))  {
					$factor = $overrides["positions"][$this->positions[$i]["arnr"]]["fmge"] / $this->positions[$i]["fmge"];
					print "\nFaktor: ".$factor."\n";
				} else {
					$factor = null;
				}
				if (array_key_exists($overrides["positions"][$this->positions[$i]["arnr"]]['fmgb'], $overrides["positions"]) and 
				    !array_key_exists($overrides["positions"][$this->positions[$i]["arnr"]]['fmge'], $overrides["positions"]) ) {
					$overrides["positions"][$this->positions[$i]["arnr"]]['fmge'] = $overrides["positions"][$this->positions[$i]["arnr"]]['fmgb'] * $this->positions[$i]["amgn"] / $this->positions[$i]["amgz"];
				}
			} else {
				$switch = 0;
			}				
		}
		
		$in = '';
		for($i = 0; $i < count($articleList); $i++) {
			if ($i>0)  { $in .= ','; }
			$in .= ":arnr".$i;
		}

		$sql = "insert into auftr_pos (";
		$cnt = 0;
		foreach (array_keys($this->positions[0]) as $key ) {
			if ($cnt++ > 0) { $sql .= ",";}	
			$sql .= $key;	
		}
		$sql .= ")\n select ";
		$cnt = 0;
		foreach (array_keys($this->positions[0]) as $key ) {
			if ($cnt++ > 0) { $sql .= ","; }	
			if ( $key == 'fnum') {
			    $sql .= $this->newFnum;
			} elseif ( $key == 'fblg') {
			    $sql .= $this->newFblg;
			} elseif ( $key == 'ftyp') {
			    $sql .= $saveNewOrderTyp;
			} elseif ( $key == 'fafn') {
			    $sql .= 'fnum';
			} elseif ( $key == 'fldt') {
			    $sql .= 'current_date';
			} elseif ( $key == 'fdtm') {
			    $sql .= 'current_date';
			} else {
				$sql .= $key;
			}
		}
		$sql .= "\n from auftr_pos where fblg = :fblg";
		$sql .= "\n and ( arnr in ( $in ) or (fabl ~ :idlist) )";
		$sql .= "\n order by fpos";
		
		// print $sql;

		$f_qry = $this->pg_pdo->prepare($sql);
		$f_qry->bindValue(':fblg',$this->head['fblg']);
		
		for( $i = 0; $i < count($articleList); $i++) {
			$f_qry->bindValue(':arnr'.$i,$articleList[$i]);
		}

		$f_qry->bindValue(':idlist', $idlist);

		$f_qry->execute() or die (print_r($f_qry->errorInfo()));
		
		if( $overrides ) {
			$this->overideData($overrides);
		}
		
		if ($setInOrder) {
		    $this->setDeliveredAmount();
		}
		
		return [ "fnum" => $this->newFnum, "fblg" => $this->newFblg ];
		
	}
	
	private function overideData($overrides) {
		
		if (count($overrides["head"]) > 0) {
			// Modify new order head data 
			$sql = "update auftr_kopf set ";
			$cnt = 0;
			foreach(array_keys($overrides["head"]) as $key) {
				$saveKey = preg_replace("[A-Za-z0-9_ ]","",$key);
				if ($cnt++ > 0) { $sql .= ",";}
				$sql .= $saveKey. " = :".$saveKey;
			}		
			$sql .= " where fblg = :fblg";
			
			$f_qry = $this->pg_pdo->prepare($sql);
			$f_qry->bindValue(':fblg',$this->newFblg );

			foreach($overrides["head"] as $key => $value) {
				$saveKey = preg_replace("[A-Za-z0-9_ ]","",$key);
				$f_qry->bindValue(':'.$saveKey, $value );
			}				
			//print "\n".$sql;
			$f_qry->execute() or die (print_r($f_qry->errorInfo()));
		}
		
		if (count($overrides["positions"]) > 0) {
			// Modify new order pos data 
			foreach($overrides["positions"] as $article => $data) {
				$sql = "update auftr_pos set ";
				$cnt = 0;
				foreach(array_keys($data) as $key) {
					$saveKey = preg_replace("[A-Za-z0-9_ ]","",$key);
					if ($cnt++ > 0) { $sql .= ",";}
					$sql .= $saveKey. " = :".$saveKey;
				}		
				$sql .= " where fblg = :fblg and ((arnr = :arnr) or (fabl ~ ('TB_POS_CHANNEL_ID=' || :arnr) ))";
				
				$f_qry = $this->pg_pdo->prepare($sql);
				$f_qry->bindValue(':fblg', $this->newFblg );
				$f_qry->bindValue(':arnr', $article );

				foreach($data as $key => $value) {
					$saveKey = preg_replace("[A-Za-z0-9_ ]","",$key);
					$f_qry->bindValue(':'.$saveKey, $value );
				}				
				//print "\n".$sql;
				$f_qry->execute() or die (print_r($f_qry->errorInfo()));
			}
		}
	}

	private function setDeliveredAmount() {

		$sql = "update auftr_pos af set fmgl = ( select sum(fmge) from auftr_pos ls where ftyp = 4 and (ls.fpid = af.fpid) and (ls.arnr = af.arnr))
					where fblg = :affblg ";
		$f_qry = $this->pg_pdo->prepare($sql);
		$f_qry->bindValue(':affblg', $this->head['fblg'] );
		//$f_qry->bindValue(':lsfblg', $this->newFblg );
		//print "\n".$sql;
		$f_qry->execute() or die (print_r($f_qry->errorInfo()));

	}
	
	
	
}	
	
	
	