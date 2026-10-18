<?php
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

if (!class_exists('parcelTracking_UserAPI')) {
    require_once __DIR__ . '/../../3rdparty/parcelTracking_UserAPI.php';
}


class parcelTracking extends eqLogic {

    /*     * *************************Attributs****************************** */

    public static $_widgetPossibility = array('custom' => true);
  
    /* Permet de crypter/décrypter automatiquement des champs de configuration du plugin*/
    public static $_encryptConfigKey = array('password', 'session');
    
    private static $_parcelsData = null;
    private static $_carriers = null;
    private static $_countries = null;


    /*     * ***********************Methode static*************************** */

    public static function cron10() {

        if ( date('Gi') == 0 ) return;                                                  // A 0h00 pas de refresh car cronDaily
        log::add('parcelTracking', 'debug', 'Cron10');
        self::refreshAll();
    }

    public static function cronDaily() {
        
        log::add('parcelTracking', 'debug', 'CronDaily');
        log::add('parcelTracking', 'debug', '┌─Command execution : archive and remove parcels');
        $nbDays = config::byKey('nbDays', 'parcelTracking');
        $archiveDays = 15;                                                              // 17Track arrête le suivi 15 jours après la livraison

        foreach (eqLogic::byType('parcelTracking', true) as $parcelTracking) {
            if ( $parcelTracking->getConfiguration('eqLogicType') != 'global') {
                log::add('parcelTracking', 'debug', '| --> Parcel '.$parcelTracking->getName().' with trackingId '.$parcelTracking->getLogicalId());
                $days = $parcelTracking->getDaysDifference($parcelTracking->getCmd('info', 'deliveryDate')->execCmd());

                // Le compte 17Track est limité à 40 colis actifs : archivage après livraison, même si l'équipement est conservé
                if ( $days >= $archiveDays && $parcelTracking->getConfiguration('archived') != 1 ) {
                    $parcelTracking->archiveParcel();
                }

                if ( $nbDays != null && $days >= $nbDays ) {
                    $parcelTracking->removeParcel($parcelTracking->getId());            // preRemove() archive le colis s'il ne l'est pas déjà
                }
                else { log::add('parcelTracking', 'debug', '| Parcel not removed'); }
            }
        }
        log::add('parcelTracking', 'debug', '└─End of archive and remove parcels');

        // Refresh carriers list
        self::refreshCarriersList();

        // Refresh 17Track account counter
        self::refreshQuota();
    }
 
    public static function getConfigForCommunity() {
    
		$CommunityInfo = "```\n";
        if ( !empty(config::byKey('email', 'parcelTracking')) ) { $CommunityInfo = $CommunityInfo . '17Track account present' . "\n"; }
        else { $CommunityInfo = $CommunityInfo . '17Track account missing' . "\n"; }
        if ( !empty(config::byKey('password', 'parcelTracking')) ) { $CommunityInfo = $CommunityInfo . 'Password present' . "\n"; }
        else { $CommunityInfo = $CommunityInfo . 'Password missing' . "\n"; }
        if ( !empty(config::byKey('session', 'parcelTracking')) ) { $CommunityInfo = $CommunityInfo . 'Session stored' . "\n"; }
        else { $CommunityInfo = $CommunityInfo . 'No session stored' . "\n"; }
        $CommunityInfo = $CommunityInfo . 'Default object : ' . config::byKey('defaultObject', 'parcelTracking') . "\n";
        $CommunityInfo = $CommunityInfo . 'Days : ' . config::byKey('nbDays', 'parcelTracking') . "\n";
        $CommunityInfo = $CommunityInfo . 'Notifications cmdId : ' . config::byKey('cmdNotifications', 'parcelTracking') . "\n";
        $CommunityInfo = $CommunityInfo . 'Notifications format : ' . config::byKey('formatNotifications', 'parcelTracking') . "\n";
        $CommunityInfo = $CommunityInfo . 'Notifications scenarioId : ' . config::byKey('scenarioNotifications', 'parcelTracking') . "\n";
        $CommunityInfo = $CommunityInfo . 'Notifications tags : ' . config::byKey('formatTags', 'parcelTracking') . "\n";
        $CommunityInfo = $CommunityInfo . 'Default widget : ' . config::byKey('defaultWidget', 'parcelTracking') . "\n";
		$CommunityInfo = $CommunityInfo . "```";
		return $CommunityInfo;
    }

    public static function getparcelTrackingEqLogic($trackingId) {

		$eqLogic = null;
        foreach ( eqLogic::byTypeAndSearhConfiguration('parcelTracking', 'trackingId') as $parcelTracking ) {
			if ( $parcelTracking->getConfiguration('trackingId') == $trackingId ) {
				$eqLogic = $parcelTracking;
				break;
			}
		}
		return $eqLogic;
	}

    public static function getDaysDifference($deliveryDate) {
        
        if ( is_int(strtotime($deliveryDate)) ) {
            $date = new \DateTime($deliveryDate);
            $today = new \DateTime();
            $interval = $date->diff($today);
            log::add('parcelTracking', 'debug', '| Result getDaysDifference() - Interval : '.$interval->days.' days');
            return $interval->days;
        }
        else { 
            log::add('parcelTracking', 'debug', '| Result getDaysDifference() - Unknown delivery date');
            return 0;
        }
    }

    public static function setIsVisibleEqlogics($mode) {

        foreach (eqLogic::byType('parcelTracking', true) as $parcelTracking) {
            if ( $mode == 'one' || $mode == 'none' ) {
                if ( $parcelTracking->getConfiguration('eqLogicType') == 'global' ) {
                    if ( $parcelTracking->getIsVisible() == 0 ) {
                        $parcelTracking->setIsVisible(1);
                        $parcelTracking->save();
                    }
                }
                else if ( $parcelTracking->getIsVisible() == 1 ) {
                    $parcelTracking->setIsVisible(0);
                    $parcelTracking->save();
                }
            }
            else if ( $mode == 'all' ) {
                if ( $parcelTracking->getConfiguration('eqLogicType') == 'global' ) {
                    if ( $parcelTracking->getIsVisible() == 1 ) {
                        $parcelTracking->setIsVisible(0);
                        $parcelTracking->save();
                    }
                }
                else if ( $parcelTracking->getIsVisible() == 0 ) {
                    $parcelTracking->setIsVisible(1);
                    $parcelTracking->save();
                }
            }
            else if ( $mode == 'plus' ) {
                $parcelTracking->setIsVisible(1);
                $parcelTracking->save();
            }
        }
    }

    public static function buidListWidget() {

        $list = array();
        $totalParcels = count(eqLogic::byType('parcelTracking', true));
        $list = [ 
            'totalParcels' => $totalParcels,
        ];
        foreach (eqLogic::byType('parcelTracking', true) as $parcelTracking) {
            if ( $parcelTracking->getConfiguration('eqLogicType') != 'global') {
                $status = $parcelTracking->getCmd('info','status')->execCmd();
                if ( $parcelTracking->getCmd('info','states')->execCmd() != 'not available' && $parcelTracking->getCmd('info','states')->execCmd() != '') {
                    $lastState = json_decode($parcelTracking->getCmd('info','states')->execCmd(),true);
                    $list['parcels'][] = [ 
                        'id' => $parcelTracking->getId(),
                        'trackingId' => $parcelTracking->getConfiguration('trackingId'),
                        'name' => $parcelTracking->getName(),
                        'status' => $status,
                        'lastDate' => $lastState['states'][0]['date'],
                        'lastTime'=> $lastState['states'][0]['time'],
                        'lastLocation' => $lastState['states'][0]['location'],
                        'lastState' => $lastState['states'][0]['status']
                    ];
                }
                else {
                    $list['parcels'][] = [ 
                        'id' => $parcelTracking->getId(),
                        'trackingId' => $parcelTracking->getConfiguration('trackingId'),
                        'name' => $parcelTracking->getName(),
                        'status' => $status,
                        'lastDate' => '',
                        'lastTime'=> '',
                        'lastLocation' => '',
                        'lastState' => ''
                    ];
                }
            }
        }
        return json_encode($list);   
    }

    public static function buildNotifications($name, $trackingId, $carrier, $status, $lastState, $lastDate, $lastTime, $lastLocation, $url) {

        $formatNotifications = config::byKey('formatNotifications', 'parcelTracking');
        
        if ( !$formatNotifications ) {
            $formatNotifications = $lastState;
        }
        else {
            $formatNotifications = str_replace("#name#", $name, $formatNotifications);
            $formatNotifications = str_replace("#trackingId#", $trackingId, $formatNotifications);
            $formatNotifications = str_replace("#carrier#", $carrier, $formatNotifications);
            $formatNotifications = str_replace("#status#", $status, $formatNotifications);
            $formatNotifications = str_replace("#lastState#", $lastState, $formatNotifications);
            $formatNotifications = str_replace("#date#", $lastDate, $formatNotifications);
            $formatNotifications = str_replace("#time#", $lastTime, $formatNotifications);
            $formatNotifications = str_replace("#location#", $lastLocation, $formatNotifications);
            $formatNotifications = str_replace("#url#", $url, $formatNotifications);
        }
        return $formatNotifications;
    }

    public static function buildTags($name, $object, $trackingId, $carrier, $status, $lastState, $lastDate, $lastTime, $lastLocation, $url) {

        $formatTags = config::byKey('formatTags', 'parcelTracking');
        $tags = arg2array($formatTags);
        $tags = str_replace("#name#", $name, $tags);
        $tags = str_replace("#object#", $object, $tags);
        $tags = str_replace("#trackingId#", $trackingId, $tags);
        $tags = str_replace("#carrier#", $carrier, $tags);
        $tags = str_replace("#status#", $status, $tags);
        $tags = str_replace("#lastState#", $lastState, $tags);
        $tags = str_replace("#date#", $lastDate, $tags);
        $tags = str_replace("#time#", $lastTime, $tags);
        $tags = str_replace("#location#", $lastLocation, $tags);
        $tags = str_replace("#url#", $url, $tags);
        return $tags;
    }

    private static function getUserAPI() {

        $email = config::byKey('email', 'parcelTracking');
        $password = config::byKey('password', 'parcelTracking');
        $session = config::byKey('session', 'parcelTracking');
        if ( empty($email) || empty($password) ) {
            log::add('parcelTracking', 'debug', '| 17Track account credentials missed');
            throw new Exception('17Track account credentials missed');
        }
        return new parcelTracking_UserAPI($email, $password, $session);
    }

    private static function saveSession($myAccount) {

        $cookies = $myAccount->getCookies();
        if ( $cookies != config::byKey('session', 'parcelTracking') ) {
            config::save('session', $cookies, 'parcelTracking');
            log::add('parcelTracking', 'debug', '| Session cookies updated');
        }
    }

    /* Liste des colis du compte*/
    public static function getParcelsList($force = false) {

        if ( self::$_parcelsData !== null && !$force ) { return self::$_parcelsData; }

        $myAccount = self::getUserAPI();
        $parcelsData = [];
        $page = 1;
        $maxPages = 10;
        do {
            $result = $myAccount->getParcels($page);
            $response = json_decode($result->body, true);
            $rows = ( isset($response['Json']) && is_array($response['Json']) ) ? $response['Json'] : [];
            foreach ($rows as $row) {
                if ( isset($row['FTrackNo']) ) { $parcelsData[strtoupper($row['FTrackNo'])] = $row; }
            }
            $total = isset($response['pageInfo']['TotalCount']) ? (int)$response['pageInfo']['TotalCount'] : 0;
            $page++;
        } while ( count($rows) >= parcelTracking_UserAPI::PARCELS_PER_PAGE && count($parcelsData) < $total && $page <= $maxPages );
        self::saveSession($myAccount);

        // Mise à jour quota
        if ( isset($response['pageInfo']['TotalCount']) ) {
            $quota = explode(' / ', config::byKey('quota', 'parcelTracking'));
            $capacity = isset($quota[1]) ? $quota[1] : 40;
            $newQuota = $total.' / '.$capacity;
            if ( $newQuota != config::byKey('quota', 'parcelTracking') ) { config::save('quota', $newQuota, 'parcelTracking'); }
        }

        log::add('parcelTracking', 'debug', '| Result getParcelsList() : '.count($parcelsData).' parcel(s) in the 17Track account');
        self::$_parcelsData = $parcelsData;
        return $parcelsData;
    }

    public static function findParcel($trackingId, $force = false) {

        $parcelsData = self::getParcelsList($force);
        $trackingId = strtoupper(trim($trackingId));
        return isset($parcelsData[$trackingId]) ? $parcelsData[$trackingId] : null;
    }

    /* Identifiant interne du colis (FTrackInfoId) */
    public static function getTrackInfoId($eqLogic, $store = true) {

        $trackInfoId = $eqLogic->getConfiguration('trackInfoId');
        if ( !empty($trackInfoId) ) { return $trackInfoId; }

        $parcelData = self::findParcel($eqLogic->getConfiguration('trackingId'));
        if ( $parcelData === null || empty($parcelData['FTrackInfoId']) ) { return ''; }

        if ( $store ) {
            $eqLogic->setConfiguration('trackInfoId', $parcelData['FTrackInfoId']);
            $eqLogic->save(true);
        }
        return $parcelData['FTrackInfoId'];
    }

    /* Correspondance FPackageState -> statuts attendus par les widgets */
    public static function mapStatus($state) {

        switch ( (int)$state ) {
            case 0:  return 'NotFound';
            case 10: return 'InTransit';
            case 20: return 'Expired';
            case 30: return 'AvailableForPickup';
            case 35: return 'DeliveryFailure';
            case 40: return 'Delivered';
            case 50: return 'Exception';
            default: return 'Unknown';
        }
    }

    /* Décode le dernier évènement d'un colis */ 
    private static function parseLastEvent($parcelData) {

        if ( !isset($parcelData['FLastEvent']) || empty($parcelData['FLastEvent']) ) { return null; }

        $event = json_decode($parcelData['FLastEvent'], true);
        if ( !is_array($event) ) { return null; }

        $datetime = '';
        if ( isset($event['dd']['d']) && !empty($event['dd']['d']) ) {
            $datetime = $event['dd']['d'];
            if ( isset($event['dd']['t']) && !empty($event['dd']['t']) ) { $datetime .= 'T'.$event['dd']['t']; }
            if ( isset($event['dd']['tz']) && !empty($event['dd']['tz']) ) { $datetime .= $event['dd']['tz']; }
        }

        $location = trim( (isset($event['c']) ? $event['c'] : '').' '.(isset($event['d']) ? $event['d'] : '') );

        return [
            'time' => isset($event['a']) ? $event['a'] : '',
            'datetime' => $datetime,
            'location' => $location,
            'description' => isset($event['z']) ? $event['z'] : ''
        ];
    }

    /* Charge apicarrier.all.json */
    private static function loadCarriers() {

        if ( self::$_carriers !== null ) { return; }
        self::$_carriers = [];
        self::$_countries = [];

        $content = @file_get_contents(__DIR__ . '/../../data/apicarrier.all.json');
        $data = ( $content !== false ) ? json_decode($content, true) : null;
        if ( !is_array($data) ) {
            log::add('parcelTracking', 'debug', '| Result loadCarriers() - Unable to read apicarrier.all.json');
            return;
        }
        foreach ($data as $carrier) {
            if ( isset($carrier['key']) && isset($carrier['_name']) ) { self::$_carriers[$carrier['key']] = $carrier['_name']; }
            if ( !empty($carrier['_country']) && !empty($carrier['_country_iso']) ) { self::$_countries[$carrier['_country']] = $carrier['_country_iso']; }
        }
    }

    public static function getCarrierName($key) {

        self::loadCarriers();
        return ( !empty($key) && isset(self::$_carriers[$key]) ) ? self::$_carriers[$key] : __('Indisponible', __FILE__);
    }

    public static function getCountryIso($countryId) {

        self::loadCarriers();
        return ( !empty($countryId) && isset(self::$_countries[$countryId]) ) ? self::$_countries[$countryId] : __('Indisponible', __FILE__);
    }


    /*     * *********************Méthodes d'instance************************* */

    // Fonction exécutée automatiquement avant la création de l'équipement
    public function preInsert() {
    
        $defaultObject = config::byKey('defaultObject', 'parcelTracking');
        $this->setObject_id($defaultObject);
        $this->setCategory('default',1);
        $this->setIsEnable(1);
        if ( config::byKey('defaultWidget', 'parcelTracking') == "one" || config::byKey('defaultWidget', 'parcelTracking') == "none") { $this->setIsVisible(0); }
        else { $this->setIsVisible(1); }
        if ( $this->getLogicalId() != 'parcelTracking_widget' ) { $this->setConfiguration('eqLogicType', 'parcel'); }
    }

    // Fonction exécutée automatiquement après la création de l'équipement
    public function postInsert() {
    }

    // Fonction exécutée automatiquement avant la mise à jour de l'équipement
    public function preUpdate() {
    }

    // Fonction exécutée automatiquement après la mise à jour de l'équipement
    public function postUpdate() {
    }

    // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
    public function preSave() {
    
        if ( $this->getLogicalId() != 'parcelTracking_widget' ) { $this->setLogicalId($this->getConfiguration('trackingId')); }
    }

    // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
    public function postSave() {

        if ( $this->getConfiguration('eqLogicType') != 'global') {
            $this->createCmd('status', __('Statut colis', __FILE__), 1, 'info', 'string');
            $this->createCmd('carrier', __('Transporteur', __FILE__), 2, 'info', 'string');
            $this->createCmd('origin', __('Origine', __FILE__), 3, 'info', 'string');
            $this->createCmd('destination', __('Destination', __FILE__), 4, 'info', 'string');
            $this->createCmd('states', __('Etats', __FILE__), 5, 'info', 'string');
            $this->createCmd('lastEvent', __('Dernier évènement', __FILE__), 6, 'info', 'string');
            $this->createCmd('lastState', __('Dernier état', __FILE__), 7, 'info', 'string');
            $this->createCmd('deliveryDate', __('Date de livraison', __FILE__), 8, 'info', 'string');

            $this->createCmd('refresh', __('Rafraichir', __FILE__), 9, 'action', 'other');
        }
        else { $this->createCmd('refreshAll', __('Rafraichir', __FILE__), 1, 'action', 'other'); }
    }

    // Fonction exécutée automatiquement avant la suppression de l'équipement
    public function preRemove() {
    
        if ( $this->getConfiguration('eqLogicType') == 'global' ) { return; }

        log::add('parcelTracking', 'debug', '┌─Command execution : removeParcel ('.$this->getName().')');
        if ( $this->getConfiguration('archived') == 1 ) {
            log::add('parcelTracking', 'debug', '└─End of parcel removal : already archived on 17Track');
            return;
        }

        try {
            $trackInfoId = self::getTrackInfoId($this, false);
            if ( $trackInfoId != '' ) {
                $myAccount = self::getUserAPI();
                $result = $myAccount->archiveTrackingId($trackInfoId);
                self::saveSession($myAccount);

                $response = json_decode($result->body, true);
                if ( isset($response['Code']) && $response['Code'] == 0 ) {
                    log::add('parcelTracking', 'debug', '└─End of parcel removal : archived on 17Track');
                }
                else {
                    log::add('parcelTracking', 'warning', '| Archive refused by 17Track (code '.($response['Code'] ?? 'none').') : parcel still active in the 17Track account');
                    log::add('parcelTracking', 'debug', '└─End of parcel removal');
                }
            }
            else { log::add('parcelTracking', 'debug', '└─End of parcel removal : not found in the 17Track account, nothing to archive'); }
        } catch (Exception $e) {
            log::add('parcelTracking', 'warning', '| Unable to archive the parcel on 17Track : '.$e->getMessage());
            log::add('parcelTracking', 'debug', '└─End of parcel removal');
        }
    }

    // Fonction exécutée automatiquement après la suppression de l'équipement
    public function postRemove() {
    }

 
    /* Permet de modifier l'affichage du widget (également utilisable par les commandes)*/
    public function toHtml($_version = 'dashboard') {

        $this->emptyCacheWidget(); 		//vide le cache. Pratique pour le développement

        $replace = $this->preToHtml($_version);
		if (!is_array($replace)) {
			return $replace;
		}
		$version = jeedom::versionAlias($_version);
		$replace['#version#'] = $_version;
		$replace['#trackingId'.$this->getId().'#'] = $this->getConfiguration('trackingId');
        
		// Traitement des commandes infos
		foreach ($this->getCmd('info') as $cmd) {
			$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
			$replace['#' . $cmd->getLogicalId() . '_name#'] = $cmd->getName();
			$replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
			$replace['#' . $cmd->getLogicalId() . '_visible#'] = $cmd->getIsVisible();
		}

		// Traitement des commandes actions
		foreach ($this->getCmd('action') as $cmd) {
			$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
			$replace['#' . $cmd->getLogicalId() . '_visible#'] = $cmd->getIsVisible();
		}
			
		// On definit le template à appliquer par rapport aux paramètre du plugin
		if ( config::byKey('defaultWidget', 'parcelTracking') == "one" ) { 
            $this->setIsVisibleEqlogics("one");
            $replace['#listParcels#'] = $this->buidListWidget();
            $template = 'parcelTracking_global_dashboard_v4';
        }
		else if ( config::byKey('defaultWidget', 'parcelTracking') == "all" || config::byKey('defaultWidget', 'parcelTracking') == "" ) {
            $this->setIsVisibleEqlogics("all");
            $template = 'parcelTracking_dashboard_v4'; 
        }
        else if ( config::byKey('defaultWidget', 'parcelTracking') == "plus" ) {
            $this->setIsVisibleEqlogics("plus");
            if ( $this->getConfiguration('eqLogicType') == 'global') {
                $replace['#listParcels#'] = $this->buidListWidget();
                $template = 'parcelTracking_global_dashboard_v4';
            }
            if ( $this->getConfiguration('eqLogicType') == 'parcel') {
                $template = 'parcelTracking_dashboard_v4';
            }
        }
        else if ( config::byKey('defaultWidget', 'parcelTracking') == "none" ) {
            $template = 'parcelTracking_hidden_dashboard_v4'; 
        }
                
        $replace['#template#'] = $template;

        $filepath = 'plugins/'.__CLASS__.'/core/template/'.$version.'/'.$template.'.html';
        $html = template_replace($replace, getTemplate('core', $version, $template, 'parcelTracking'));
        $html = translate::exec($html, $filepath);
        return $this->postToHtml($_version, $html);
     }
    
    private function createCmd($commandName, $commandDescription, $order, $type, $subType, $isHistorized = 0, $template = [])
    {	
        $cmd = $this->getCmd(null, $commandName);
        if (!is_object($cmd)) {
            $cmd = new parcelTrackingCmd();
            $cmd->setOrder($order);
            $cmd->setName($commandDescription);
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId($commandName);
            $cmd->setType($type);
            $cmd->setSubType($subType);
            $cmd->setIsHistorized($isHistorized);
            if (!empty($template)) { $cmd->setTemplate($template[0], $template[1]); }
            $cmd->save();
            log::add('parcelTracking', 'debug', '| Add command '.$cmd->getName().' (LogicalId : '.$cmd->getLogicalId().')');
        }
    }


    /*     * **********************Getteur Setteur*************************** */

    public static function testConnection($email, $password)
    {
        log::add('parcelTracking', 'debug', '┌─Command execution : testConnection');
        $myAccount = new parcelTracking_UserAPI($email, $password);
        if ( !$myAccount->login() ) {
            log::add('parcelTracking', 'debug', '└─End of testConnection : authentication failed');
            return [ 'success' => false ];
        }
        config::save('session', $myAccount->getCookies(), 'parcelTracking');

        // unum = colis actifs, cnum = plafond du compte
        $result = $myAccount->getSummary();
        $response = json_decode($result->body, true);
        log::add('parcelTracking', 'debug', '└─End of testConnection : ['.$result->httpCode.']');
        return [
            'success' => true,
            'used' => isset($response['Json']['utn']['unum']) ? (int)$response['Json']['utn']['unum'] : 0,
            'capacity' => isset($response['Json']['utn']['cnum']) ? (int)$response['Json']['utn']['cnum'] : 40
        ];
    }

    public static function registerParcel($trackingId)
    {
        log::add('parcelTracking', 'debug', '┌─Command execution : registerParcel');

        $eqLogic = self::getparcelTrackingEqLogic(trim($trackingId));
        $trackingId = strtoupper(trim($trackingId));
        log::add('parcelTracking', 'debug', '| Parcel trackingId : '.$trackingId);

        $myAccount = self::getUserAPI();
        $register = $myAccount->addTrackingId($trackingId);
        self::saveSession($myAccount);

        if ( is_object($eqLogic) ) {
            $parcelData = self::findParcel($trackingId, true);
            if ( $parcelData !== null && !empty($parcelData['FTrackInfoId']) ) {
                $trackInfoId = $parcelData['FTrackInfoId'];
                $eqLogic->setConfiguration('trackInfoId', $trackInfoId);
                $eqLogic->save(true);

                // Nom du colis repris dans le compte 17Track
                $myAccount = self::getUserAPI();
                $myAccount->setRemark($trackInfoId, $eqLogic->getName());

                // Transporteur imposé par l'utilisateur (sinon détection automatique par 17Track)
                $carrier = $eqLogic->getConfiguration('carrier');
                if ( $carrier != '' ) {
                    $myAccount->setCarrier($trackInfoId, $carrier, 0);
                }
                self::saveSession($myAccount);

                $eqLogic->updateCmds($parcelData);
            }
            else { log::add('parcelTracking', 'debug', '| Parcel not yet visible in the account : trackInfoId will be stored on next refresh'); }
        }

        log::add('parcelTracking', 'debug', '└─End of parcel registration : ['.$register->httpCode.']');

        // Colis présent dans le compte : enregistrement réussi, même si AddTrackNo l'a refusé car déjà présent
        if ( isset($parcelData['FTrackInfoId']) ) { return [ 'Code' => 0 ]; }
        return json_decode($register->body);
    }    

    public static function updateParcelCarrier($trackingId)
    {
        log::add('parcelTracking', 'debug', '┌─Command execution : updateParcelCarrier');

        $eqLogic = self::getparcelTrackingEqLogic(trim($trackingId));
        if ( !is_object($eqLogic) ) {
            log::add('parcelTracking', 'debug', '└─End of update parcel carrier : unknown parcel');
            return null;
        }
        $trackInfoId = self::getTrackInfoId($eqLogic);
        if ( $trackInfoId == '' ) {
            log::add('parcelTracking', 'debug', '└─End of update parcel carrier : parcel not found in the 17Track account');
            return null;
        }

        $carrier = (int)$eqLogic->getConfiguration('carrier');
        $parcelData = self::findParcel($trackingId);
        $secondCarrier = ( $carrier != 0 && isset($parcelData['FSecondCarrier']) ) ? (int)$parcelData['FSecondCarrier'] : 0;          // 2e transporteur (dernier kilomètre) conservé

        $myAccount = self::getUserAPI();
        $result = $myAccount->setCarrier($trackInfoId, $carrier, $secondCarrier);
        self::saveSession($myAccount);
        log::add('parcelTracking', 'debug', '└─End of update parcel carrier : ['.$result->httpCode.']');
        return json_decode($result->body);
    }

    /* Archive le colis sur 17Track */
    public function archiveParcel() {

        log::add('parcelTracking', 'debug', '┌─Command execution : archiveParcel ('.$this->getName().')');
        try {
            $trackInfoId = self::getTrackInfoId($this);
            if ( $trackInfoId == '' ) {
                log::add('parcelTracking', 'debug', '└─End of parcel archive : not found in the 17Track account, nothing to archive');
                return false;
            }

            $myAccount = self::getUserAPI();
            $result = $myAccount->archiveTrackingId($trackInfoId);
            self::saveSession($myAccount);

            $response = json_decode($result->body, true);
            if ( !isset($response['Code']) || $response['Code'] != 0 ) {
                log::add('parcelTracking', 'debug', '└─End of parcel archive : refused by 17Track, will be retried tomorrow');
                return false;
            }

            $this->setConfiguration('archived', 1);
            $this->save(true);
            log::add('parcelTracking', 'debug', '└─End of parcel archive : archived, last known state kept in Jeedom');
            return true;
        } catch (Exception $e) {
            log::add('parcelTracking', 'warning', '| Unable to archive the parcel on 17Track : '.$e->getMessage());
            log::add('parcelTracking', 'debug', '└─End of parcel archive');
            return false;
        }
    }

    public function refreshParcelInfo()
    {
        $trackingId = strtoupper(trim($this->getConfiguration('trackingId')));
        log::add('parcelTracking', 'debug', '┌─Command execution : refresh ('.$this->getName().')');
        log::add('parcelTracking', 'debug', '| Parcel trackingId : '.$trackingId);

        if ( $trackingId == '' ) {
            log::add('parcelTracking', 'debug', '└─No tracking number : nothing to refresh');
            return false;
        }

        if ( $this->getConfiguration('archived') == 1 ) {
            log::add('parcelTracking', 'debug', '└─Parcel archived on 17Track : last known state kept');
            return false;
        }

        $parcelData = self::findParcel($trackingId);

        if ( $parcelData === null ) {
            // Colis absent du compte (jamais enregistré, archivé ou purgé par 17Track)
            log::add('parcelTracking', 'debug', '└─Parcel not found in the 17Track account : last known state kept');
            return false;
        }

        $this->updateCmds($parcelData);
        log::add('parcelTracking', 'debug', '└─End of parcel info refresh');
        return true;
    }

    public function updateCmds($parcelData) {

        $notification = false;

        // trackInfoId
        if ( !empty($parcelData['FTrackInfoId']) && $this->getConfiguration('trackInfoId') != $parcelData['FTrackInfoId'] ) {
            $this->setConfiguration('trackInfoId', $parcelData['FTrackInfoId']);
            $this->save(true);
        }

        //status
        $state = isset($parcelData['FPackageState']) ? (int)$parcelData['FPackageState'] : null;
        if ( $state !== null ) { $this->checkAndUpdateCmd('status', self::mapStatus($state)); } else { $this->checkAndUpdateCmd('status', __('Indisponible', __FILE__)); }

        //carrier : le 2e transporteur (dernier kilomètre) prime s'il existe
        $carrierKey = !empty($parcelData['FSecondCarrier']) ? $parcelData['FSecondCarrier'] : ( isset($parcelData['FFirstCarrier']) ? $parcelData['FFirstCarrier'] : 0 );
        $this->checkAndUpdateCmd('carrier', self::getCarrierName($carrierKey));

        //origin - destination
        $this->checkAndUpdateCmd('origin', self::getCountryIso(isset($parcelData['FFirstCountry']) ? $parcelData['FFirstCountry'] : 0));
        $this->checkAndUpdateCmd('destination', self::getCountryIso(isset($parcelData['FSecondCountry']) ? $parcelData['FSecondCountry'] : 0));

        //lastState - lastEvent - states - deliveryDate
        $event = self::parseLastEvent($parcelData);
        $lastEvent = null;

        if ( $event !== null ) {
            $this->checkAndUpdateCmd('lastState', str_replace("'", " ", $event['description']));
            // Horodatage complet avec décalage (dd) en priorité, sinon heure locale sans fuseau (a)
            if ( $event['datetime'] != '' ) { $lastEvent = $this->convertDate($event['datetime']); }
            else if ( $event['time'] != '' ) { $lastEvent = $this->convertDate($event['time'], config::byKey('timezone')); }
        }
        else { $this->checkAndUpdateCmd('lastState', __('Indisponible', __FILE__)); }

        if ( $lastEvent !== null ) {
            if ( $this->checklastEvent($lastEvent) == true ) {
                $notification = true;
                // Le compte ne fournit que le dernier évènement : l'historique est reconstitué au fil des rafraîchissements
                $datetime = new DateTime($lastEvent);
                $this->addState($datetime->format('d/m/Y'), $datetime->format('H\hi'), str_replace("'", " ", $event['location']), str_replace("'", " ", $event['description']));
            }
            $this->checkAndUpdateCmd('lastEvent', $lastEvent);

            if ( $state == 40 ) { $this->checkAndUpdateCmd('deliveryDate', $lastEvent); }
            else { $this->checkAndUpdateCmd('deliveryDate', __('Indisponible', __FILE__)); }
        }
        else {
            $this->checkAndUpdateCmd('lastEvent', __('Indisponible', __FILE__));
            $this->checkAndUpdateCmd('deliveryDate', __('Indisponible', __FILE__));
        }

        if ( $notification == true ) { $this->sendNotification(); }
    }

    /* Ajoute un évènement en tête de l'historique (commande states) */
    public function addState($date, $time, $location, $status) {

        $states = json_decode($this->getCmd('info', 'states')->execCmd(), true);
        if ( !is_array($states) || !isset($states['states']) || !is_array($states['states']) ) { $states = [ 'states' => [] ]; }

        array_unshift($states['states'], [ "date" => $date, "time" => $time, "location" => $location, "status" => $status ]);
        $this->checkAndUpdateCmd('states', json_encode($states));
    }
    
    public function convertDate($date, $timezone = 'UTC') {

        $datetime = new DateTime($date, new DateTimeZone($timezone));
        $datetime->setTimezone(new DateTimeZone(config::byKey('timezone')));
        return $datetime->format('Y-m-d\TH:i:sP');
    }
    
    public function checklastEvent($lastEvent) {
        
        $cmd = $this->getCmd('info', 'lastEvent');
        $previousEvent = $cmd->execCmd();
        if ( $lastEvent != $previousEvent ) {
            if ( $previousEvent != null ) { log::add('parcelTracking', 'debug', '| Result checklastEvent : new event - '.$lastEvent.' != '.$previousEvent); }
            else { log::add('parcelTracking', 'debug', '| Result checklastEvent : new event - '.$lastEvent.' != null'); }
            return true;
        }
        else {
            log::add('parcelTracking', 'debug', '| Result checklastEvent : no change - '.$lastEvent.' == '.$previousEvent );
            return false;
        }
    }
    
    public function sendNotification() {

        // Information
        $info = json_decode($this->getCmd('info','states')->execCmd(),true);
        $name = $this->getName();
        $object = $this->getObject_id();
        $trackingId = $this->getConfiguration('trackingId');
        $carrier = $this->getCmd('info','carrier')->execCmd();
        $status = $this->getCmd('info','status')->execCmd();
        $lastState = $info['states'][0]['status'];
        $lastDate = $info['states'][0]['date'];
        $lastTime = $info['states'][0]['time'];
        $lastLocation = $info['states'][0]['location'];
        $url = network::getNetworkAccess('external').'/index.php?v=d&p=dashboard&object_id='.$object;
		        
        // Cmd
        $cmdNotifications = config::byKey('cmdNotifications', 'parcelTracking');
        $formatNotifications = config::byKey('formatNotifications', 'parcelTracking');
        if ( $cmdNotifications != null) {
            if ( $formatNotifications != null ) {
                $title = 'Suivi colis '.$name;
                $message = $this->buildNotifications($name, $trackingId, $carrier, $status, $lastState, $lastDate, $lastTime, $lastLocation, $url);
            }
            else {
                $title = 'Suivi colis '.$name.' - '.$trackingId;
                $message = $lastState;
            }
            $data = [
                'title' => $title,
                'message' => $message,
            ];
            
            if (strpos($cmdNotifications, '&&') !== false) {
                $cmds = explode(' && ', $cmdNotifications);
                $cmds = array_map('trim', $cmds);                           // On supprime les espaces autour de chaque valeur
                foreach ($cmds as $cmd) {
                    cmd::byString($cmd)->execCmd($data);
                    log::add('parcelTracking', 'debug', '| Send notification - cmdId : '.$cmd.' - title : '.$title. ' - message : '.$message );
                }
            }
            else {
                cmd::byString($cmdNotifications)->execCmd($data);
                log::add('parcelTracking', 'debug', '| Send notification - cmdId : '.$cmdNotifications.' - title : '.$title. ' - message : '.$message );
            }
        }

        // Scenario
        $scenarioNotifications = config::byKey('scenarioNotifications', 'parcelTracking');
        if ( $scenarioNotifications != null) {
            $tags = $this->buildTags($name, $object, $trackingId, $carrier, $status, $lastState, $lastDate, $lastTime, $lastLocation, $url);
            $scenario = scenario::byString($scenarioNotifications);
            $scenario->setTags($tags);
            $scenario->execute();
            log::add('parcelTracking', 'debug', '| Send notification - scenarioId : #'.$scenario->getId().'# - tags : '.json_encode($tags) );
        }
    }

    public static function refreshAll() {

        log::add('parcelTracking', 'debug', '┌─Command execution : refreshAll');

        $parcels = [];
        foreach (eqLogic::byType('parcelTracking', true) as $parcelTracking) {
            // Hors widget global, colis archivés et colis sans numéro de suivi
            if ( $parcelTracking->getConfiguration('eqLogicType') != 'global' && $parcelTracking->getConfiguration('archived') != 1 && trim($parcelTracking->getConfiguration('trackingId')) != '' ) { $parcels[] = $parcelTracking; }
        }
        if ( count($parcels) == 0 ) {
            log::add('parcelTracking', 'debug', '| No parcel to refresh');
            log::add('parcelTracking', 'debug', '└─End of refreshAll');
            return;
        }

        // Un seul appel GetTrackInfoList pour tous les colis, réutilisé par chaque commande refresh
        try {
            self::getParcelsList(true);
        } catch (Exception $e) {
            log::add('parcelTracking', 'warning', '| Unable to retrieve the 17Track parcels list : '.$e->getMessage());
            log::add('parcelTracking', 'debug', '└─End of refreshAll');
            return;
        }

        foreach ($parcels as $parcelTracking) {
            $cmdRefresh = $parcelTracking->getCmd(null, 'refresh');
            if (!is_object($cmdRefresh) ) {
                continue;
            }
            $cmdRefresh->execCmd();
        }
        log::add('parcelTracking', 'debug', '└─End of refreshAll');
    }

    public static function removeParcel($eqLogicId)
    {
        $eqLogic = eqLogic::byId($eqLogicId);
        if( is_object($eqLogic) ) {
            $eqLogic->remove();
        }

        foreach (eqLogic::byType('parcelTracking', true) as $parcelTracking) {		   
            $parcelTracking->refreshWidget();
        }	
    }

    public static function addParcel($name, $trackingId, $carrier)
    {
        log::add('parcelTracking', 'debug', '┌─Command execution : addParcel');
        $parcel = new parcelTracking();
        $parcel->setEqType_name('parcelTracking');
        $parcel->setName($name);
        $parcel->setConfiguration('trackingId', $trackingId);
        $parcel->setConfiguration('carrier', $carrier);
        if ( $carrier == "" ) { $log_carrier = "none"; }
        else { $log_carrier = $carrier; }
        log::add('parcelTracking', 'debug', '| Add parcel (name : '.$name.' - trackingId : '.$trackingId.' - carrier : '.$log_carrier.')');
        $parcel->save();

        parcelTracking::registerParcel($trackingId);
        
        foreach (eqLogic::byType('parcelTracking', true) as $parcelTracking) {		   
            $parcelTracking->refreshWidget();
        }
        log::add('parcelTracking', 'debug', '└─End of addParcel');
    }

    public static function refreshCarriersList() {

        log::add('parcelTracking', 'debug', '┌─Command execution : refreshCarriersList');
    
        // Update apicarrier.all.json
        $result1 = self::updateJsonFile(
            'https://res.17track.net/asset/carrier/info/apicarrier.all.json',
            __DIR__ . '/../../data/apicarrier.all.json',
            ['key', '_name', '_country_iso'],
            'apicarrier.all.json'
        );
        
        if (!$result1['success']) {
            log::add('parcelTracking', 'debug', '└─End of refreshCarriersList');
            return $result1;
        }
        
        // Update additional_parameters.json
        $result2 = self::updateJsonFile(
            'https://res.17track.net/asset/carrier/info/additional_parameters.json',
            __DIR__ . '/../../data/additional_parameters.json',
            ['key', 'parameters'],
            'additional_parameters.json',
            true
        );
        
        if (!$result2['success']) {
            log::add('parcelTracking', 'debug', '└─End of refreshCarriersList');
            return $result2;
        }
        
        log::add('parcelTracking', 'debug', '└─End of refreshCarriersList');
        return $result1;
    }

    /* Met à jour le compteur "colis actifs / plafond" du compte 17Track (paramètre quota) */
    public static function refreshQuota()
    {
        log::add('parcelTracking', 'debug', '┌─Command execution : refreshQuota');
        try {
            $myAccount = self::getUserAPI();
            $result = $myAccount->getSummary();
            self::saveSession($myAccount);

            $response = json_decode($result->body, true);
            if ( isset($response['Json']['utn']['unum']) && isset($response['Json']['utn']['cnum']) ) {
                $quota = (int)$response['Json']['utn']['unum'].' / '.(int)$response['Json']['utn']['cnum'];
                config::save('quota', $quota, 'parcelTracking');
                log::add('parcelTracking', 'debug', '| Quota : '.$quota);
                log::add('parcelTracking', 'debug', '└─End of refreshQuota');
            }
            else { log::add('parcelTracking', 'debug', '└─End of refreshQuota : counter unavailable'); }
        } catch (Exception $e) {
            log::add('parcelTracking', 'debug', '└─Unable to refresh the 17Track counter : '.$e->getMessage());
        }
    }

    private static function updateJsonFile($url, $target, $requiredKeys, $fileName, $validateParameters = false) {
        
        $previous = count(json_decode(file_get_contents($target, true)));
        
        $jsonContent = file_get_contents($url);
        if ($jsonContent === false) {
            log::add('parcelTracking', 'debug', '| Result updateJsonFile() - Unable to fetch file "'.$fileName.'" from URL');
            return ['success' => false];
        }
        
        $data = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            log::add('parcelTracking', 'debug', '| Result updateJsonFile() - Retrieved content "'.$fileName.'" is not valid JSON : '.json_last_error_msg());
            return ['success' => false];
        }
        
        if (!is_array($data)) {
            log::add('parcelTracking', 'debug', '| Result updateJsonFile() - JSON "'.$fileName.'" does not contain an array');
            return ['success' => false];
        }
        
        foreach ($data as $index => $item) {
            if (!is_array($item)) {
                log::add('parcelTracking', 'debug', '| Result updateJsonFile() - Element at index '.$index.' in "'.$fileName.'" is not an array');
                return ['success' => false];
            }
            
            // apicarrier.all.json
            foreach ($requiredKeys as $key) {
                if (!isset($item[$key])) {
                    log::add('parcelTracking', 'debug', '| Result updateJsonFile() - Element at index '.$index.' in "'.$fileName.'" does not contain required key: '.$key);
                    return ['success' => false];
                }
            }
            
            // additional_parameters.json
            if ($validateParameters) {
                if (!is_array($item['parameters']) || empty($item['parameters'])) {
                    log::add('parcelTracking', 'debug', '| Result updateJsonFile() - Element at index '.$index.' in "'.$fileName.'" has invalid parameters array');
                    return ['success' => false];
                }
                
                if (!isset($item['parameters'][0]['description']) || !isset($item['parameters'][0]['sample'])) {
                    log::add('parcelTracking', 'debug', '| Result updateJsonFile() - Element at index '.$index.' in "'.$fileName.'" parameters missing description or sample');
                    return ['success' => false];
                }
            }
        }
       
        if (file_put_contents($target, $jsonContent) === false) {
            log::add('parcelTracking', 'debug', '| Result updateJsonFile() - Unable to write file "'.$fileName.'"');
            return ['success' => false];
        }
        
        log::add('parcelTracking', 'debug', '| Result updateJsonFile() - File "'.$fileName.'" updated successfully');
        log::add('parcelTracking', 'debug', '| Result updateJsonFile() - Previous entries: '.$previous.' - Current entries: '.count($data));
        
        return [
            'success' => true,
            'count' => count($data),
            'timestamp' => date('Y-m-d H:i', filemtime($target))
        ];
    }
}


class parcelTrackingCmd extends cmd {

    /*     * *************************Attributs****************************** */

    /*     * ***********************Methode static*************************** */

    /*     * *********************Methode d'instance************************* */

    /*
    * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
    public function dontRemoveCmd() {
        return true;
    }
    */

    // Exécution d'une commande
    public function execute($_options = array()) {
    
        $eqLogic = $this->getEqLogic(); 										// On récupère l'éqlogic de la commande $this
        $logicalId = $this->getLogicalId();

        try {
            switch ($logicalId) {
                case 'refresh':
                    $eqLogic->refreshParcelInfo();
                    break;
                case 'refreshAll':
                    $eqLogic->refreshAll();
                    break;
                default:
                throw new \Exception("Unknown command", 1);
                break;
            }
        } catch (Exception $e) {
            echo 'Exception : ',  $e->getMessage(), "\n";
            log::add('parcelTracking', 'debug', '└─Command execution error : '.$logicalId.' - '.$e->getMessage());
        }
            
        $eqLogic->refreshWidget();
    }

    /*     * **********************Getteur Setteur*************************** */

}
