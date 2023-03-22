<?php

class Shopware6Customer {

	private $pg_pdo;
	private $api; 
	
	public function __construct($api = null) {
		
		include ("./intern/config.php");
		
		$this->pg_pdo = new PDO($wwsserver, $wwsuser, $wwspass, $options);
		$this->api = $api;
		
		return true;
	}
	
	public function uploadDiscountGroups($upload = true) {
		
	
		$customer = new Customer(0);
		$discountGroups = $customer->getAllDiscountGroups();
		foreach($discountGroups as $discountGroup) {
			$payload = [
							"id" => md5("ctag".$discountGroup["id"]),
							"name" => 'KRG-'.$discountGroup["name"] ,
	
						];
			if ($upload) {
				$return = $this->api->post('tag/', $payload );
			} else {
					print "<pre>";
					print_r($payload);
					print "</pre>";
			}
		}
		return count($discountGroups);
		
	}
	
	public function SingleUpload($api, $restdata, $type = "post") {
		
		try {
			if ($type == "post") {
				$result = $api->post('customer', $restdata );
			} elseif ($type == "patch") {
				$result = $api->patch('customer/'.$restdata["id"], $restdata );
			} elseif ($type == "delete") {
				$result = $api->delete('customer/'.$restdata["id"], $restdata );
			}
			
			$this->debugData("Upload  customer/".$restdata["id"], ["UploadArray" => $restdata, "Result" => $result]);
		} catch (Exception $e) {
			return $restdata["productNumber"]."\t".$result["message"]."\n";
		}
		
		if (! empty($result["success"])) {
			
		} else {
			$returnError = "Error Upload ".$restdata["id"];
			foreach ($result["errors"] as $error) {
				$returnError = "\t".$error["detail"];
				if (!empty($error["source"]["pointer"])) {
					$returnError .= " (".$error["source"]["pointer"].") ";
				} else if (preg_match('/Expected command.*ProductDefinition/', $returnError)) {
					$this->setUpdateTime($restdata["productNumber"],0);
				}
			}
			Proto($restdata["id"]." Upload Failed ".$returnError);
			$returnError .= "\n";
			return ( $returnError );
		}
	}
}

?>