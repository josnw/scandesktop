<?php
// basic REST client
class OpenApi3Client {
    const METHOD_GET = 'GET';
    const METHOD_PUT = 'PUT';
    const METHOD_PATCH = 'PATCH';
    const METHOD_POST = 'POST';
    const METHOD_DELETE = 'DELETE';

    protected $validMethods = [
        self::METHOD_GET,
        self::METHOD_PUT,
        self::METHOD_POST,
        self::METHOD_DELETE,
    	self::METHOD_PATCH,
    ];
    protected $apiUrl;
    protected $cURL;
	
	private $tokenType;
	private $apiToken;
	private $tokenExpires;
	private $tokenExpiresTimer;
	//private $apiRefresh;
	private $username;
	private $apiKey;
	private $apiType;


    public function __construct($apiUrl, $username, $apiKey, $type = "client_credentials") {
        $this->apiUrl = rtrim($apiUrl, '/') . '/';
		$this->tokenExpires = time() + 600;
        $this->cURL = curl_init();
        $this->username = $username;
        $this->apiKey = $apiKey;
        $this->apiType = $type;
        
       curl_setopt($this->cURL, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($this->cURL, CURLOPT_FOLLOWLOCATION, false);
       curl_setopt($this->cURL, CURLOPT_USERAGENT, 'RHG Rest API Client 0.91');
       // curl_setopt($this->cURL, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
       // curl_setopt($this->cURL, CURLOPT_USERPWD, $username . ':' . $apiKey);
        curl_setopt(
            $this->cURL,
            CURLOPT_HTTPHEADER,
            ['Content-Type: application/json'],
			
        );
		$this->initToken();

    }
	
	private function initToken() {
		
		$this->tokenExpires = time() + 120; 
		
		if ($this->apiType == "client_credentials") {
			$body = [
					"grant_type" => "client_credentials",
					"client_id" => $this->username,
					"client_secret"=> $this->apiKey
			];
		} else {
			$body = [
					"client_id" => "administration",
					"grant_type" => "password",
					"scopes" => "write",
					"username" => $this->username,
					"password"=> $this->apiKey
            ];
		}
		
		$response = $this->post(
                'oauth/token',
                $body
            );
		
		if(!empty($response["error"])) {
			print $response["error"][0]["title"];
			return false;
		}
		
		$this->tokenType = $response['token_type'];
		$this->apiToken = $response['access_token'];
		//$this->apiRefresh = $response['refresh_token'];
		$this->tokenExpires = time() + $response['expires_in'];
		$this->tokenExpiresTimer = $response['expires_in'];
		
		if(empty($this->apiToken)) {
			return false;
		} else {
			return true;
		}
		
	}
	

	private function refreshToken() {

		$this->tokenExpires = time() + 120;
		return $this->initToken();
		/*
		$body = [
				"client_id" => $username,
				"grant_type" => "refresh_token",
				"refresh_token"=> $this->apiRefresh
            ];
    
		$this->tokenExpires = time()+60;
		$response = $this->post(
                'oauth/token',
                $body
            );
		

		
		//$this->tokenType = $response['token_type'];
		$this->apiToken = $response['access_token'];
		$this->apiRefresh = $response['refresh_token'];
		$this->tokenExpires = time() + $this->tokenExpiresTimer;
		*/

	}


    public function call($url, $method = self::METHOD_GET, $data = [], $params = [], $rawResponse = false)    {

        if (!in_array($method, $this->validMethods)) {
            throw new Exception('Invalid HTTP-Methode: ' . $method);
        }

        $queryString = '';

        if (!empty($params)) {
            $queryString = http_build_query($params);
            
            $url = rtrim($url, '?') . '?';
        }

        $url = $this->apiUrl . $url . $queryString;
        
         
		if ((time() + 60) > $this->tokenExpires) {
			$this->refreshToken();
		}

		$dataString = json_encode($data, JSON_UNESCAPED_SLASHES);
		$header = [
					'Content-Type: application/json',
					'Authorization: '.$this->tokenType." ".$this->apiToken
				]; 
	
        curl_setopt($this->cURL, CURLOPT_URL, $url);
        curl_setopt($this->cURL, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($this->cURL, CURLOPT_POSTFIELDS, $dataString);
		curl_setopt($this->cURL, CURLOPT_HTTPHEADER, $header);
	
        $result = curl_exec($this->cURL);

        $httpCode = curl_getinfo($this->cURL, CURLINFO_HTTP_CODE);
        
        if ($rawResponse) {
            return $result;
        } else {
            return $this->prepareResponse($result, $httpCode);
        }

    }

    public function get($url, $params = [])    {
        return $this->call($url, self::METHOD_GET, [], $params);
    }

    public function post($url, $data = [], $params = [])    {
        return $this->call($url, self::METHOD_POST, $data, $params);
    }

    public function put($url, $data = [], $params = [])    {
        return $this->call($url, self::METHOD_PUT, $data, $params);
    }
    
    public function patch($url, $data = [], $params = [])    {
    	return $this->call($url, self::METHOD_PATCH, $data, $params);
    }
    
    
    public function delete($url, $params = [])    {
        return $this->call($url, self::METHOD_DELETE, [], $params);
    }

    protected function prepareResponse($result, $httpCode)    {
        

        if ( $httpCode == 204 ) {
			return ["success" => true];
		} elseif (null === $decodedResult = json_decode($result, true))  {
            $jsonErrors = [
                JSON_ERROR_NONE => 'No error occurred',
                JSON_ERROR_DEPTH => 'The maximum stack depth has been reached',
                JSON_ERROR_CTRL_CHAR => 'Control character issue, maybe wrong encoded',
                JSON_ERROR_SYNTAX => 'Syntaxerror'
            ];

           return ["status" => 0, "error" => $jsonErrors[json_last_error()], "result" => $result];
        }

/*        if (!isset($decodedResult['success'])) {
            //echo 'Invalid Response';
			throw new Exception("Invalid Response: ".print_r($decodedResult,1));
            return $decodedResult;

        }

        if (!$decodedResult['success']) {
            //echo '<h2>No Success</h2>';
			throw new Exception("API No success: ".print_r($decodedResult,1));
            return $decodedResult ;

        }
*/
        return $decodedResult;
    }

    public function getSwagger() {
        
        return $this->call("_info/openapi3.json", self::METHOD_GET, [], [], true);
        
    }

}


?>