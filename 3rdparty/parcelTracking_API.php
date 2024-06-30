<?php

/*
* A PHP Client for parcelsapp API
*/


class parcelTracking_API {
	
    const API_URL = 'https://parcelsapp.com/api/v3/shipments/tracking';
	
	/* @var string apiKey */
	private $apiKey = null;
	/* @var string trackingId */
	private $language = null;
	/* @var string country */
	private $trackingId = null;
	/* @var string language */
	private $destinationCountry = null;
	/* @var string zipcode */
	private $zipcode = null;
	/* @var string uuid */
	private $uuid = null;
		
	
	public function __construct($apiKey, $language, $trackingId, $destinationCountry, $zipcode) {
		
		$this->apiKey = $apiKey;
		$this->language = $language;
		$this->trackingId = $trackingId;
		$this->destinationCountry = $destinationCountry;
		$this->zipcode = $zipcode;
		$this->uuid = null;
	}


	public function __destruct() {
	}
 
 
    private function _request($url, $method, $data, $extra_headers = [])
    {
        $ch = curl_init();
        $headers = [];

        // Default CURL options
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_HEADER, true);

        // Set data
        if ($method == 'GET') {
			//$data_str = '?'.http_build_query($data);
		}
		if ($method == 'POST') {
			$data_str = json_encode($data);
		}
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

		//Step 1 - Initiate tracking request
		$url = $this::API_URL;
		$method = 'POST';
		$headers = [
			'Content-Type: application/json'
		];
		$data = [
			'shipments' => [
							[
							'trackingId' => $this->trackingId,
							'destinationCountry' => $this->destinationCountry,
							//'zipcode' => $this->zipcode
							]
			],
			'language' => $this->language,
			'apiKey' => $this->apiKey
		];
		
		$result1 = $this->_request($url, $method, $data, $headers);
		$response1 = json_decode($result1->body);
		
		if ( $result1->httpCode == 200 ) {
			if ( $response1->done == true ) {
				log::add('parcelTracking', 'debug', '| Result getTrackingResult() request - step 1 : ['.$result1->httpCode.'] - '.str_replace('\n', '', $result1->body));
				return $result1;
			}
			else {
				log::add('parcelTracking', 'debug', '| Result getTrackingResult() request - step 1 : ['.$result1->httpCode.'] - '.str_replace('\n', '', $result1->body));
				$this->uuid = $response1->uuid;
			}
		}
		else {
			log::add('parcelTracking', 'debug', '| Result getTrackingResult() request - step 1 : ['.$result1->httpCode.'] - '.str_replace('\n', '', $result1->body));
			return $result1;
		}
		
		//Step 2 - Read tracking result
		$url = $this::API_URL;
		$method = 'GET';
		$headers = [
			'Accept: application/json',
		];
		$data = [
			'uuid' => $this->uuid,
			'apiKey' => $this->apiKey
		];
		$data_str = '?'.http_build_query($data);
		
		$result2 = $this->_request($url.$data_str, $method, null, $headers);
		$response2 = json_decode($result2->body);
		
		if ( $result2->httpCode == 200 ) {
			log::add('parcelTracking', 'debug', '| Result getTrackingResult() request - step 2 (0) : ['.$result2->httpCode.'] - '.str_replace('\n', '', $result2->body));
			$retry = 10;
			while ( $retry > 0 && $response2->done != 'true')
			{
				sleep(2);
				$result2 = $this->_request($url.$data_str, $method, null, $headers);
				$response2 = json_decode($result2->body);
				log::add('parcelTracking', 'debug', '| Result getTrackingResult() request - step 2 ('.(11-$retry).') : ['.$result2->httpCode.'] - '.str_replace('\n', '', $result2->body));
				$retry--;
			}	
			return $result2;
		}
		else {
			log::add('parcelTracking', 'debug', '| Result getTrackingResult() request - step 2 (0) : ['.$result2->httpCode.'] - '.str_replace('\n', '', $result2->body));
			return $result2;
		}
	}

}