<?php

class elo_client {
		
	public function __construct() {
		
	}
	
	public function getDocument($documentId, $documentTyp, $customerId) {

	    return ["status"=> false, "info" => 'Error '.$documentId."-".$customerId."-".$documentTyp ];
	}
	
}

?>