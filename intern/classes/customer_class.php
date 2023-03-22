<?php

class customer {
		
	private $customerId = null;
	private $indexvalue = null;
	private $my_pdo;
	private $pg_pdo;
	private $discountGroups;
	
	public $customerData;
	private $resultCount;
	
	// Artikeldaten einlesen
	public function __construct($indexvalue, $level = 'basic', $searchoptions = []) {

		include ("./intern/config.php");
		
		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		
		$this->indexvalue = $indexvalue;
		$fqry  = "select kdnr, qna1, qna2, qstr, qplz, qort, qlnd, qrgr, s.qbez as rgname, kbvk, kprb from kun_0 k
                    left join status_id s on s.qskz = 32 and s.zxtp = k.qrgr
					where kdnr = :kdnr";
		$f_qry = $this->pg_pdo->prepare($fqry);
		$f_qry->bindValue(':kdnr',$indexvalue);

		$f_qry->execute() or die (print_r($f_qry->errorInfo()));

		$frow = $f_qry->fetchAll( PDO::FETCH_ASSOC );
		
		$this->customerData = $frow;
		$this->resultCount = count($frow);

		
	}
	
	
	public function getAllDiscountGroups() {
		
		$fqry  = "select zxtp as id , qbez as name from status_id s where s.qskz = 32 ";
		$f_qry = $this->pg_pdo->prepare($fqry);
		
		$f_qry->execute() or die (print_r($f_qry->errorInfo()));
		
		$frow = $f_qry->fetchAll( PDO::FETCH_ASSOC );
		
		$this->discountGroups = $frow;
		return $frow;
	}
	
}