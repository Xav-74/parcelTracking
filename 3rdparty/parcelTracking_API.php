<?php

/*
* A PHP Client for 17Track API
*/


class parcelTracking_API {
	
    const API_URL_TRACKING = 'https://api.17track.net/track/v2.2/gettrackinfo';
	const API_URL_REGISTER = 'https://api.17track.net/track/v2.2/register';
	const API_URL_DELETE = 'https://api.17track.net/track/v2.2/deletetrack';
	const API_URL_QUOTA = 'https://api.17track.net/track/v2.2/getquota';
	
	/* @var string apiKey */
	private $apiKey = null;
	/* @var string trackingId */
	private $trackingId = null;
	/* @var string language */
	private $language = null;
			
	
	public function __construct($apiKey, $trackingId, $language) {
		
		$this->apiKey = $apiKey;
		$this->trackingId = $trackingId;
		if ( $language == 'default' ) { $this->language = ''; }
		else { $this->language = $language; }
	}


	public function __destruct() {
	}
 
 
    private function _request($url, $method, $data, $extra_headers = [])
    {
        $ch = curl_init();
        $headers = [];

        // Default CURL options
        curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_ENCODING, "");
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_HEADER, true);

        // Set data
        $data_str = json_encode($data);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data_str);
        
        // Add extra headers
        if (count($extra_headers)) {
            foreach ($extra_headers as $header) {
                $headers[] = $header;
            }
        }
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Execute request
        $response = curl_exec($ch);
        if (!$response) {
            throw new \Exception('Unable to retrieve data');
        }

        // Get response
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        curl_close($ch);
		unset($ch);

		return (object)[
            'headers' => $header,
            'body' => $body,
            'httpCode' => $httpCode
        ];
    }


	public function getTrackingResult() {

		$url = $this::API_URL_TRACKING;
		$method = 'POST';
		$headers = [
			'17token: '.$this->apiKey,
			'Content-Type: application/json'
		];
		$data = [
			[ 'number' => $this->trackingId	]
		];
		
		$result = $this->_request($url, $method, $data, $headers);
		//$response = json_decode($result->body);

		log::add('parcelTracking', 'debug', '| Result getTrackingResult() request : ['.$result->httpCode.'] - '.str_replace('\n', '', $result->body));
		return $result;
	}


	public function registerTrackingId() {

		$url = $this::API_URL_REGISTER;
		$method = 'POST';
		$headers = [
			'17token: '.$this->apiKey,
			'Content-Type: application/json'
		];
		$data = [
			[
				'number' => $this->trackingId,
				'lang' => $this->language
			]
		];
		
		$result = $this->_request($url, $method, $data, $headers);
		//$response = json_decode($result->body);

		log::add('parcelTracking', 'debug', '| Result registerTrackingId() request : ['.$result->httpCode.'] - '.str_replace('\n', '', $result->body));
		return $result;
	}


	public function deleteTrackingId() {

		$url = $this::API_URL_DELETE;
		$method = 'POST';
		$headers = [
			'17token: '.$this->apiKey,
			'Content-Type: application/json'
		];
		$data = [
			[ 'number' => $this->trackingId	]
		];
		
		$result = $this->_request($url, $method, $data, $headers);
		//$response = json_decode($result->body);

		log::add('parcelTracking', 'debug', '| Result deleteTrackingId() request : ['.$result->httpCode.'] - '.str_replace('\n', '', $result->body));
		return $result;
	}


	public function getQuota() {

		$url = $this::API_URL_QUOTA;
		$method = 'POST';
		$headers = [
			'17token: '.$this->apiKey,
			'Content-Type: application/json'
		];
		$data = [];
		
		$result = $this->_request($url, $method, $data, $headers);
		//$response = json_decode($result->body);

		log::add('parcelTracking', 'debug', '| Result getQuota() request : ['.$result->httpCode.'] - '.str_replace('\n', '', $result->body));
		return $result;
	}

}