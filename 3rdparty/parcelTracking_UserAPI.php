<?php

/*
* A PHP Client for 17Track user account API
*/


class parcelTracking_UserAPI {

	const API_URL_LOGIN = 'https://user.17track.net/user-api/v1/sign-in-by-password';
	const API_URL_BUYER = 'https://buyer.17track.net/orderapi/call';

	/* Nombre de colis retournés par page par GetTrackInfoList */
	const PARCELS_PER_PAGE = 40;

	/* Clé publique RSA utilisée par 17Track pour chiffrer le mot de passe */
	const RSA_PUBLIC_KEY = "-----BEGIN PUBLIC KEY-----\n"
		. "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0Y5iQN3VNofXPtZXYZe9\n"
		. "75ojD+Gb+yPBrlrKj2t4XvYHE+pYmFzGPTvDmB3t1OfHujdgBVc3VJBSFsHezm4kz\n"
		. "4iqIChHLKeFvuux+i/Uq+zo1QdC72qteUMHF925qPLe3xU/QJj6BFR9mA4VrUwXt8\n"
		. "eWI58ozizBH31PclxiPNT+yYYXRUV3QJvbZ+FJGL3gYUu1k44WILQzDZfBJMRf+My\n"
		. "LHZew6+XtYB8E2+PXc/R7TtLPcMDsPvARrAJMhu5b+yfwJM1zOFChAz3U1w0Zkj1y\n"
		. "VJQaa/aktmfd0KyFkU2M0xNpPIIcQkywUGCMZEmJkEIyyV/I+H/NvQ+qqU5llwIDAQAB\n"
		. "-----END PUBLIC KEY-----";

	/* @var string email */
	private $email = null;
	/* @var string password */
	private $password = null;
	/* @var array cookies */
	private $cookies = [];
	/* @var string accountId */
	private $accountId = null;


	public function __construct($email, $password, $cookies = null) {

		$this->email = $email;
		$this->password = $password;
		if ( is_array($cookies) ) { $this->cookies = $cookies; }
		else if ( !empty($cookies) ) {
			$decoded = json_decode($cookies, true);
			if ( is_array($decoded) ) { $this->cookies = $decoded; }
		}
	}


	public function __destruct() {
	}


	private function _setHeader($extra_headers = [])
	{
		$headers = [
			'Content-Type: application/json',
			'Accept: application/json, text/plain, */*',
			'Accept-Language: fr-FR,fr;q=0.9,en;q=0.8',
			'Origin: https://www.17track.net',
			'Referer: https://www.17track.net/',
			'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
		];

		// Cookies de session récupérés lors du login
		if ( !empty($this->cookies) ) {
			$cookie = [];
			foreach ($this->cookies as $name => $value) { $cookie[] = $name.'='.$value; }
			$headers[] = 'Cookie: '.implode('; ', $cookie);
		}

		// Headers supplémentaires
		if ( count($extra_headers) ) {
			foreach ($extra_headers as $header) { $headers[] = $header; }
		}

		return $headers;
	}


	private function _request($url, $method, $data, $extra_headers = [])
	{
		$ch = curl_init();
		$headers = $this->_setHeader($extra_headers);

		// Default CURL options
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
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
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		// Execute request
		$response = curl_exec($ch);
		if (!$response) {
			$curlError = curl_error($ch);
			curl_close($ch);
			unset($ch);
			throw new \Exception('Unable to retrieve data : '.$curlError);
		}

		// Get response
		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$header = substr($response, 0, $headerSize);
		$body = substr($response, $headerSize);

		curl_close($ch);
		unset($ch);

		// Les cookies de session sont mis à jour à chaque réponse
		$this->_parseCookies($header);

		return (object)[
			'headers' => $header,
			'body' => $body,
			'httpCode' => $httpCode
		];
	}


	private function _parseCookies($header)
	{
		if ( preg_match_all('/^Set-Cookie:\s*([^=;\r\n]+)=([^;\r\n]*)/mi', $header, $matches) ) {
			$count = count($matches[0]);
			for ($i = 0; $i < $count; $i++) {
				$name = trim($matches[1][$i]);
				$value = trim($matches[2][$i]);
				if ( $name == '' ) { continue; }
				if ( $value == '' || strtolower($value) == 'deleted' ) { unset($this->cookies[$name]); }
				else { $this->cookies[$name] = $value; }
			}
		}
	}


	private function _encryptPassword()
	{
		$publicKey = openssl_pkey_get_public(self::RSA_PUBLIC_KEY);
		if ( $publicKey === false ) {
			throw new \Exception('Unable to load the 17Track public key : '.openssl_error_string());
		}

		$encrypted = null;
		if ( !openssl_public_encrypt($this->password, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING) ) {
			throw new \Exception('Unable to encrypt the password : '.openssl_error_string());
		}

		return base64_encode($encrypted);
	}


	private function _call($apiMethod, $param = null)
	{
		$url = $this::API_URL_BUYER;
		$method = 'POST';
		$data = [
			'version' => '1.0',
			'method' => $apiMethod,
			'sourcetype' => 0
		];
		if ( $param !== null ) { $data['param'] = $param; }

		// Aucune session : login préalable
		if ( empty($this->cookies) ) {
			log::add('parcelTracking', 'debug', '| No session : login');
			if ( !$this->login() ) { throw new \Exception('17Track authentication failed'); }
		}

		$result = $this->_request($url, $method, $data);
		$response = json_decode($result->body, true);

		// Trop d'appels (-7) ou numéro déjà présent dans le compte (-11010101) : ni nouveau login ni 2e essai
		if ( isset($response['Code']) && ( $response['Code'] == -7 || $response['Code'] == -11010101 ) ) {
			log::add('parcelTracking', 'debug', '| Request refused by 17Track (code '.$response['Code'].') : no new login');
		}
		// Session expirée : nouveau login puis 2e essai
		else if ( $this->_isNotLoggedIn($result->body) ) {
			log::add('parcelTracking', 'debug', '| Session expired or request refused (code '.($response['Code'] ?? 'none').') : new login');
			if ( !$this->login() ) { throw new \Exception('17Track authentication failed'); }
			$result = $this->_request($url, $method, $data);
		}

		log::add('parcelTracking', 'debug', '| Result '.$apiMethod.'() request : ['.$result->httpCode.'] - '.str_replace('\n', '', $result->body));
		return $result;
	}


	private function _isNotLoggedIn($body) {

		$response = json_decode($body, true);
		if ( !is_array($response) ) { return true; }
		return ( isset($response['Code']) && $response['Code'] != 0 );
	}


	public function login() {

		// Les cookies de l'ancienne session sont abandonnés
		$this->cookies = [];

		$url = $this::API_URL_LOGIN;
		$method = 'POST';
		$data = [
			'source' => 0,
			'account' => $this->email,
			'password' => $this->_encryptPassword()
		];

		$result = $this->_request($url, $method, $data);
		$response = json_decode($result->body, true);

		// Le corps de la réponse contient les jetons de session : il n'est jamais journalisé
		if ( isset($response['data']['gid']) && !empty($response['data']['gid']) ) {
			$this->accountId = $response['data']['gid'];
			log::add('parcelTracking', 'debug', '| Result login() request : ['.$result->httpCode.'] - account authenticated - '.count($this->cookies).' cookie(s) stored');
			return true;
		}

		$code = isset($response['code']) ? $response['code'] : 'unknown';
		log::add('parcelTracking', 'debug', '| Result login() request : ['.$result->httpCode.'] - authentication failed - code : '.$code);
		return false;
	}


	public function getSummary($archived = false) {

		return $this->_call('GetIndexData', [ 'IsArchived' => $archived ]);
	}


	public function getParcels($page = 1, $archived = false, $parcelState = '') {

		$param = [
			'IsArchived' => $archived,
			'Item' => '',
			'Page' => (int)$page,
			'PerPage' => $this::PARCELS_PER_PAGE,
			'PackageState' => $parcelState,
			'Sequence' => '0'
		];

		return $this->_call('GetTrackInfoList', $param);
	}


	public function addTrackingId($trackingId) {

		return $this->_call('AddTrackNo', [ 'TrackNos' => [ $trackingId ] ]);
	}


	public function setRemark($trackInfoId, $remark) {

		$param = [
			'TrackInfoId' => $trackInfoId,
			'Remark' => $remark
		];

		return $this->_call('SetTrackRemark', $param);
	}


	public function setCarrier($trackInfoId, $firstCarrier, $secondCarrier = 0) {

		$param = [
			'TrackInfoId' => $trackInfoId,
			'FirstCarrier' => (int)$firstCarrier,
			'SecondCarrier' => (int)$secondCarrier
		];

		return $this->_call('SetTrackCarrier', $param);
	}


	public function archiveTrackingId($trackInfoId) {

		return $this->_call('SetTrackArchived', [ 'TrackInfoIds' => [ $trackInfoId ] ]);
	}


	/* Cookies de session à conserver côté plugin (config::save) pour éviter un login à chaque appel */
	public function getCookies() {

		return json_encode($this->cookies);
	}

	public function getAccountId() {

		return $this->accountId;
	}
	
}
