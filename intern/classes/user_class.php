<?php

class user {

	private $pg_pdo;
	private $pickBelegKz;
	private $fbkzlist;
	
	public $pickUser;
	
	public function __construct($userId) {

		include './intern/config.php';

		$this->pickUser = $userId;
		$this->pickBelegKz = $wwsPickBelegKz;
		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		
		$pickInit_sql = "update auftr_kopf set ktos = 0 where ktos is null  and fbkz = :fbkz";
		
		$pickInit_qry = $this->pg_pdo->prepare($pickInit_sql);
		$pickInit_qry->bindValue(":fbkz",$this->pickBelegKz);
		
		$pickInit_qry->execute() or die (print_r($pickInit_qry->errorInfo()));
		
		$this->fbkzlist = [$wwsPickBelegKz];
		foreach ( $wwsPickBelegChannelKz as $kz) {
			$this->fbkzlist[] = $kz;
		}
	}
	
	public function getPickLists($status = '0') {
		$this->checkPickStatus();
		$liste = [];
		//Artikel der �lteste Bestellungen und TopArtikel einlesen
		$pickStatus = preg_replace("[^0-9,]","",$status);
		$pickList_sql = 'select fprn , ktou, max(ktos) as ktos from auftr_kopf 
                            where fenr = :pickUser and ktos in ('.$pickStatus.') and fprn is not null and ftyp = 2
                            group by fprn, ktou
                            order by fprn';

		$pickList_qry = $this->pg_pdo->prepare($pickList_sql);
		$pickList_qry->bindValue(':pickUser', $this->pickUser );
		
		$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
		
		$liste = $pickList_qry->fetchAll( PDO::FETCH_ASSOC);
		
		return $liste;
	}
	
	public function getOrderCount($status = '0') {
		$this->checkPickStatus();
		$liste = [];
		//Artikel der �lteste Bestellungen und TopArtikel einlesen
		$pickStatus = preg_replace("[^0-9,]","",$status);
		$pickList_sql = 'select count(fblg) as cnt  from auftr_kopf where fenr = :pickUser and ktos in ('.$pickStatus.')  and fbkz = :fbkz and ftyp = 2 and fprn is not null';

		$pickList_qry = $this->pg_pdo->prepare($pickList_sql);
		$pickList_qry->bindValue(':pickUser', $this->pickUser );
		$pickList_qry->bindValue(":fbkz",$this->pickBelegKz);
		$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
		
		$pickList_row = $pickList_qry->fetch( PDO::FETCH_ASSOC );
		return $pickList_row["cnt"];
	}

	public function checkPickStatus() {
        return true;
		//Alle Picklisten ohne ungepackte Bestellungen auf Status 1
		$pickList_sql = 'update auftr_kopf set ktos = 1 where fenr = :pickUser and ktos = 0 and fprn is not null  and ftyp = 2';

		$pickList_qry = $this->my_pdo->prepare($pickList_sql);
		$pickList_qry->bindValue(':pickUser', $this->pickUser );
		
		$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
		
		$pickList_row = $pickList_qry->fetch( PDO::FETCH_ASSOC );
		return $pickList_row["cnt"];
	}

	public function getAllOrderCount($status = 0) {
		$this->checkPickStatus();
		$liste = NULL;
		//Artikel der �lteste Bestellungen und TopArtikel einlesen
		$pickStatus = preg_replace("[^0-9,]","",$status);

		$pickList_sql = 'select count(fblg) as cnt from auftr_kopf where ktos in ('.$pickStatus.') and fbkz = :fbkz  and ftyp = 2 ';
		
		$pickList_qry = $this->pg_pdo->prepare($pickList_sql);
		$pickList_qry->bindValue(":fbkz",$this->pickBelegKz);
		
		$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
		
		$pickList_row = $pickList_qry->fetch( PDO::FETCH_ASSOC );
		return $pickList_row["cnt"];
	}
	
	public function getAllStat() {
	    $info = [];
	    
	    $pickList_sql = 'select count(fblg) as cntAllDay from auftr_kopf where fbkz in ('.implode(",",$this->fbkzlist).')   and ftyp = 2 and fdtm <= current_date '; 
	    $pickList_qry = $this->pg_pdo->prepare($pickList_sql);
	    $pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
	    $fetch = $pickList_qry->fetch( PDO::FETCH_ASSOC );
	    $info['allToday'] = $fetch["cntallday"];
	    
	    $pickList_sql = 'select count(fblg) as cntDoneDay from auftr_kopf where  fbkz in ('.implode(",",$this->fbkzlist).')   and ftyp = 2 and fdtm <= current_date and ktos = 2 ';
	    $pickList_qry = $this->pg_pdo->prepare($pickList_sql);
	    $pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
	    $fetch = $pickList_qry->fetch( PDO::FETCH_ASSOC );
	    $info['doneToday'] = $fetch["cntdoneday"];
	    
	    if ($info['allToday'] > 0) {
	    	$info['quoteToday'] = $info['doneToday'] / $info['allToday'];
	    } else {
	    	$info['quoteToday'] = 0;
	    }
	    $info['openToday'] = $info['allToday'] - $info['doneToday'];
	    
/*	    $pickList_qry = $this->pg_pdo->prepare($pickList_sql);
	    $pickList_qry->bindValue(":fbkz",$this->pickBelegKz);
	    $pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
	    $info['byPackStat'] = $pickList_qry->fetchAll( PDO::FETCH_ASSOC );
	    */
	    $pickList_sql = 'select case when spls = spos then 3 when spls > 0 then 2 else ktos end ktos,count(fblg) as cnt 
                          from auftr_kopf where  fbkz in ('.implode(",",$this->fbkzlist).') and ftyp = 2 
						  group by case when spls = spos then 3 when spls > 0 then 2 else ktos end ';
	    $pickList_qry = $this->pg_pdo->prepare($pickList_sql);
	    $pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
	    $info['byPackStat'] = $pickList_qry->fetchAll( PDO::FETCH_ASSOC );

	    $pickList_sql = 'select fenr, p.qna1,count(fblg) as cnt, min(fprn) as minpickid from auftr_kopf k
                         inner join per_0 p on p.penr = k.fenr 
                         where  fbkz in ('.implode(",",$this->fbkzlist).')   and ftyp = 2 and coalesce(ktos,0) < 2 and fenr > 0 and fprn > 0 group by fenr, p.qna1';
	    $pickList_qry = $this->pg_pdo->prepare($pickList_sql);
	    $pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
	    $info['byUser'] = $pickList_qry->fetchAll( PDO::FETCH_ASSOC );

	    
	    
	    $pickList_sql = 'select k.fbkz, s.qbez, count(fblg) as cnt from auftr_kopf k
                         inner join status_id s on k.fbkz = s.zxtp and s.qskz = 3
                         where  fbkz in ('.implode(",",$this->fbkzlist).')  and ftyp = 2 and coalesce(ktos,0) < 2 
						 group by k.fbkz,s.qbez order by fbkz';
	    $pickList_qry = $this->pg_pdo->prepare($pickList_sql);
	    $pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
	    $info['byWwsKz'] = $pickList_qry->fetchAll( PDO::FETCH_ASSOC );
	    
	    
	    $pickList_sql = 'select fdtm::date,count(fblg) as cnt from auftr_kopf 
                         where  fbkz in ('.implode(",",$this->fbkzlist).')   and ftyp = 2 and coalesce(ktos,0) < 2 group by fdtm';
	    $pickList_qry = $this->pg_pdo->prepare($pickList_sql);
	    $pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
	    $info['byDate'] = $pickList_qry->fetchAll( PDO::FETCH_ASSOC );
	    
	    return $info;
	
	}

	public function getOrderOverview($status = 0) {
		$pickStatus = preg_replace("[^0-9,]","",$status);
		
		$pickList_sql = 'select k.fnum, k.fxnr, k.fdtm::date as fdtm, qna1, qna2, qort, arnr, abz1, abz2, fmge, fmgl, fmgt, p.ageh 
							from auftr_kopf k inner join auftr_pos p using (fblg) left join art_0 a using (arnr) 
							where ktos in ('.$pickStatus.') and fbkz = :fbkz  and k.ftyp = 2 and coalesce(avsd,0) = 0
							order by k.fnum';
		
		$pickList_qry = $this->pg_pdo->prepare($pickList_sql);
		$pickList_qry->bindValue(":fbkz",$this->pickBelegKz);
		
		$pickList_qry->execute() or die (print_r($pickList_qry->errorInfo()));
		
		$pickList_row = $pickList_qry->fetchall( PDO::FETCH_ASSOC );
		return $pickList_row;
		
	}
	

}

?>