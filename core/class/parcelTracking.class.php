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

if (!class_exists('parcelTracking_API')) {
    require_once __DIR__ . '/../../3rdparty/parcelTracking_API.php';
}


class parcelTracking extends eqLogic {

    /*     * *************************Attributs****************************** */

    public static $_widgetPossibility = array(
        'custom' => true,
    );
  
    /* Permet de crypter/décrypter automatiquement des champs de configuration du plugin*/
    public static $_encryptConfigKey = array('API_key');


    /*     * ***********************Methode static*************************** */

    public static function cronHourly() {
    
        foreach (eqLogic::byType('parcelTracking', true) as $parcelTracking) {		// type = parcelTracking et eqLogic enable
            if ( $parcelTracking->getConfiguration('eqLogicType') != 'global') {
                log::add('parcelTracking', 'debug', 'CronHourly');
                $cmdRefresh = $parcelTracking->getCmd(null, 'refresh');		
                if (!is_object($cmdRefresh) ) {											// Si la commande n'existe pas ou condition non respectée
                    continue; 															// continue la boucle
                }
                if ( date('Gi') == 0 ) return;                                          // A 0h00 pas de refresh car cronDaily
                $cmdRefresh->execCmd();
            }
        }	
    }

    public static function cronDaily() {
        
        log::add('parcelTracking', 'debug', 'CronDaily');
        log::add('parcelTracking', 'debug', '┌─Command execution : remove parcels');
        $nbDays = config::byKey('nbDays', 'parcelTracking');

        foreach (eqLogic::byType('parcelTracking', true) as $parcelTracking) {
            if ( $parcelTracking->getConfiguration('eqLogicType') != 'global') {
                log::add('parcelTracking', 'debug', '| --> Parcel '.$parcelTracking->getName().' with trackingId '.$parcelTracking->getLogicalId());
                if ( $nbDays != null ) {
                    $cmd = $parcelTracking->getCmd('info', 'deliveryDate');
                    $deliveryDate = $cmd->execCmd();
                    if ( $deliveryDate != null && $deliveryDate != 'not available') {
                        if ( $parcelTracking->getDaysDifference($deliveryDate) >= $nbDays ) {
                            $parcelTracking->remove();
                            log::add('parcelTracking', 'debug', '| Remove parcel');
                        }
                        else { log::add('parcelTracking', 'debug', '| Parcel not removed'); }
                    }
                }
            }
        }

        log::add('parcelTracking', 'debug', '└─End of remove parcels');
    }
 
    public static function getConfigForCommunity() {
    
		$CommunityInfo = "```\n";
        if ( !empty(config::byKey('apiKey', 'parcelTracking')) ) { $CommunityInfo = $CommunityInfo . 'API Key present' . "\n"; }
        else { $CommunityInfo = $CommunityInfo . 'API Key missing' . "\n"; }
        $CommunityInfo = $CommunityInfo . 'Language : ' . config::byKey('language', 'parcelTracking') . "\n";
        $CommunityInfo = $CommunityInfo . 'Default object : ' . config::byKey('defaultObject', 'parcelTracking') . "\n";
        $CommunityInfo = $CommunityInfo . 'Default zip code : ' . config::byKey('defaultZipcode', 'parcelTracking') . "\n";
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

		foreach ( eqLogic::byTypeAndSearhConfiguration('parcelTracking', 'trackingId') as $parcelTracking ) {
			if ( $parcelTracking->getConfiguration('trackingId') == $trackingId ) {
				$eqLogic = $parcelTracking;
				break;
			}
		}
		return $eqLogic;
	}

    public static function getDaysDifference($deliveryDate) {
        
        $date = DateTime::createFromFormat('d/m/Y H:i:s', $deliveryDate);
        $today = new \DateTime;
        $interval = $date->diff($today);
        log::add('parcelTracking', 'debug', '| Result getDaysDifference() - Interval : '.$interval->days.' days');
        return $interval->days;
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
                $lastState = json_decode($parcelTracking->getCmd('info','states')->execCmd(),true);
                $list['parcels'][] = [ 
                    'id' => $parcelTracking->getId(),
                    'trackingId' => $parcelTracking->getConfiguration('trackingId'),
                    'name' => $parcelTracking->getName(),
                    'status' => $status,
                    'lastDate' => $lastState['states'][0]['date'],
                    'lastHour'=> $lastState['states'][0]['hour'],
                    'lastLocation' => $lastState['states'][0]['location'],
                    'lastState' => $lastState['states'][0]['status']
                ];
            }
        }
        return json_encode($list);   
    }

    public static function refreshAll() {
    
        foreach (eqLogic::byType('parcelTracking', true) as $parcelTracking) {		   
            if ( $parcelTracking->getConfiguration('eqLogicType') != 'global') {
                $cmdRefresh = $parcelTracking->getCmd(null, 'refresh');		
                if (!is_object($cmdRefresh) ) {											// Si la commande n'existe pas ou condition non respectée
                    continue; 															// continue la boucle
                }
                $cmdRefresh->execCmd();
            }
        }	
    }

    public static function removeParcel($eqLogicId)
    {
        $eqLogic = eqLogic::byId($eqLogicId);
        if( is_object($eqLogic) ) {
            $eqLogic->remove();
            log::add('parcelTracking', 'debug', 'Remove parcel (Id : '.$eqLogicId.' - name : '.$eqLogic->getName().')');
        }

        foreach (eqLogic::byType('parcelTracking', true) as $parcelTracking) {		   
            $parcelTracking->refreshWidget();
        }	
    }

    public static function addParcel($name, $trackingId)
    {
        $parcel = new parcelTracking();
        $parcel->setEqType_name('parcelTracking');
        $parcel->setIsEnable(1);
        if ( config::byKey('defaultWidget', 'parcelTracking') == "one" || config::byKey('defaultWidget', 'parcelTracking') == "none") { $parcel->setIsVisible(0); }
        else { $parcel->setIsVisible(1); }
        $parcel->setName($name);
        $parcel->setConfiguration('trackingId', $trackingId);
        $parcel->save();
        log::add('parcelTracking', 'debug', 'Add parcel (name : '.$name.' - trackingId : '.$trackingId.')');

        parcelTracking::synchronize($trackingId);
        
        foreach (eqLogic::byType('parcelTracking', true) as $parcelTracking) {		   
            $parcelTracking->refreshWidget();
        }
    }


    /*     * *********************Méthodes d'instance************************* */

    // Fonction exécutée automatiquement avant la création de l'équipement
    public function preInsert() {
    
        $defaultObject = config::byKey('defaultObject', 'parcelTracking');
        $this->setObject_id($defaultObject);
        $this->setIsVisible(1);
        $this->setIsEnable(1);
        $this->setConfiguration('destinationCountry', 'France');
        if ( $this->getLogicalId() != 'parcelTracking_widget' ) { $this->setConfiguration('eqLogicType', 'parcel'); }
    }

    // Fonction exécutée automatiquement après la création de l'équipement
    public function postInsert() {
    }

    // Fonction exécutée automatiquement avant la mise à jour de l'équipement
    public function preUpdate() {
    
        /*if (empty($this->getConfiguration('trackingId'))) {
            throw new Exception('Le numéro de colis ne peut pas être vide');
        }
        if (empty($this->getConfiguration('destinationCountry'))) {
            throw new Exception('Le pays de destination ne peut pas être vide');
        }*/
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
            $this->createCmd('status', 'Statut colis', 1, 'info', 'string');
            $this->createCmd('carrier', 'Transporteur', 2, 'info', 'string');
            $this->createCmd('origin', 'Origine', 3, 'info', 'string');
            $this->createCmd('destination', 'Destination', 4, 'info', 'string');
            $this->createCmd('states', 'Etats', 5, 'info', 'string');
            $this->createCmd('lastState', 'Dernier état', 6, 'info', 'string');
            $this->createCmd('deliveryDate', 'Date de livraison', 7, 'info', 'string');

            $this->createCmd('refresh', 'Rafraichir', 8, 'action', 'other');
        }
        else { $this->createCmd('refreshAll', 'Rafraichir', 1, 'action', 'other'); }
    }

    // Fonction exécutée automatiquement avant la suppression de l'équipement
    public function preRemove() {
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
        else if ( config::byKey('defaultWidget', 'parcelTracking') == "none" ) {
            $template = 'parcelTracking_hidden_dashboard_v4'; 
        }
                
        $replace['#template#'] = $template;
		return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, $template, 'parcelTracking')));
    }
    
    private function createCmd($commandName, $commandDescription, $order, $type, $subType, $isHistorized = 0, $template = [])
    {	
        $cmd = $this->getCmd(null, $commandName);
        if (!is_object($cmd)) {
            $cmd = new parcelTrackingCmd();
            $cmd->setOrder($order);
            $cmd->setName(__($commandDescription, __FILE__));
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId($commandName);
            $cmd->setType($type);
            $cmd->setSubType($subType);
            $cmd->setIsHistorized($isHistorized);
            if (!empty($template)) { $cmd->setTemplate($template[0], $template[1]); }
            $cmd->save();
            log::add('parcelTracking', 'debug', 'Add command '.$cmd->getName().' (LogicalId : '.$cmd->getLogicalId().')');
        }
    }


    /*     * **********************Getteur Setteur*************************** */

    public static function synchronize($trackingId)
    {
        $eqLogic = self::getparcelTrackingEqLogic($trackingId);
        $apiKey = config::byKey('apiKey', 'parcelTracking');
        $language = config::byKey('language', 'parcelTracking');
        $destinationCountry = $eqLogic->getConfiguration('destinationCountry');
        $zipcode = $eqLogic->getConfiguration('zipcode', config::byKey('defaultZipcode', 'parcelTracking'));
                
        log::add('parcelTracking', 'debug', '┌─Command execution : synchronize');
        $myParcel = new parcelTracking_API($apiKey, $language, $trackingId, $destinationCountry, $zipcode);
        log::add('parcelTracking', 'debug', '| Parcel trackingId : '.$trackingId.' - Destination country : '.$destinationCountry.' - Zipcode : '.$zipcode);
        $result = $myParcel->getTrackingResult();
        $parcel = json_decode($result->body, true);

        if ( $parcel['shipments'][0] != 'null' ) {
            if ( isset($parcel['shipments'][0]['status']) ) { $eqLogic->checkAndUpdateCmd('status', $parcel['shipments'][0]['status']); } else { $eqLogic->checkAndUpdateCmd('status', 'not available'); }
            if ( isset($parcel['shipments'][0]['detectedCarrier']['name']) ) { $eqLogic->checkAndUpdateCmd('carrier', $parcel['shipments'][0]['detectedCarrier']['name']); } else { $eqLogic->checkAndUpdateCmd('carrier', 'not available'); }
            if ( isset($parcel['shipments'][0]['origin']) ) { $eqLogic->checkAndUpdateCmd('origin', $parcel['shipments'][0]['origin']); } else { $eqLogic->checkAndUpdateCmd('origin', 'not available'); }
            if ( isset($parcel['shipments'][0]['destination']) ) { $eqLogic->checkAndUpdateCmd('destination', $parcel['shipments'][0]['destination']); } else { $eqLogic->checkAndUpdateCmd('destination', 'not available'); }
            if ( isset($parcel['shipments'][0]['lastState']['status']) ) { $eqLogic->checkAndUpdateCmd('lastState', $parcel['shipments'][0]['lastState']['status']); } else { $eqLogic->checkAndUpdateCmd('lastState', 'not available'); }
            
            if ( isset($parcel['shipments'][0]['lastState']['date']) ) {
                if ( $parcel['shipments'][0]['status'] == 'delivered' ) { $eqLogic->checkAndUpdateCmd('deliveryDate', date('d/m/Y H:i:s', strtotime($parcel['shipments'][0]['lastState']['date']))); }
                else { $eqLogic->checkAndUpdateCmd('deliveryDate', 'not available'); }
            }
            else { $eqLogic->checkAndUpdateCmd('deliveryDate', 'not available'); }

            if ( isset($parcel['shipments'][0]['states']) ) { 
                $states = $parcel['shipments'][0]['states'];
                $table_temp = array();
                $table_states = array();
                foreach ($states as $state) {
                    if ( isset($state['date']) ) { 
                        $datetime = new DateTime($state['date'], new DateTimeZone('UTC'));
                        $d = $datetime->format('d/m/Y');
                        $h = $datetime->format('H\hi');
                        $state_date = $d; 
                        $state_hour = $h;
                    }
                    else { 
                        $state_date = '';
                        $state_hour = '';
                    }
                    if ( isset($state['location']) ) { $state_location = str_replace("'", " ",$state['location']); } else { $state_location = ' '; }
                    if ( isset($state['status']) ) { $state_status = str_replace("'", " ",$state['status']); } else { $state_status = ' '; }
                    $table_temp[] = array( "date" => $state_date, "hour" => $state_hour, "location" => $state_location, "status" => $state_status );
                }
                $table_states['states'] = $table_temp;
                $eqLogic->checkAndUpdateCmd('states', json_encode($table_states));
            } 
            else { $eqLogic->checkAndUpdateCmd('states', 'not available'); }
        }

        log::add('parcelTracking', 'debug', '└─End of synchronisation : ['.$result->httpCode.']');
        return $result;
    }

    public function refreshParcelInfo()
    {
        $apiKey = config::byKey('apiKey', 'parcelTracking');
        $language = config::byKey('language', 'parcelTracking');
        $trackingId = $this->getConfiguration('trackingId');
        $destinationCountry = $this->getConfiguration('destinationCountry');
        $zipcode = $this->getConfiguration('zipcode', config::byKey('defaultZipcode', 'parcelTracking'));
        $notification = false;

        $myParcel = new parcelTracking_API($apiKey, $language, $trackingId, $destinationCountry, $zipcode);
        log::add('parcelTracking', 'debug', '| Parcel trackingId : '.$trackingId.' - Destination country : '.$destinationCountry.' - Zipcode : '.$zipcode);
        $result = $myParcel->getTrackingResult();
        $parcel = json_decode($result->body, true);

        if ( $parcel['shipments'][0] != 'null' ) {
            if ( isset($parcel['shipments'][0]['status']) ) { $this->checkAndUpdateCmd('status', $parcel['shipments'][0]['status']); } else { $this->checkAndUpdateCmd('status', 'not available'); }
            if ( isset($parcel['shipments'][0]['detectedCarrier']['name']) ) { $this->checkAndUpdateCmd('carrier', $parcel['shipments'][0]['detectedCarrier']['name']); } else { $this->checkAndUpdateCmd('carrier', 'not available'); }
            if ( isset($parcel['shipments'][0]['origin']) ) { $this->checkAndUpdateCmd('origin', $parcel['shipments'][0]['origin']); } else { $this->checkAndUpdateCmd('origin', 'not available'); }
            if ( isset($parcel['shipments'][0]['destination']) ) { $this->checkAndUpdateCmd('destination', $parcel['shipments'][0]['destination']); } else { $this->checkAndUpdateCmd('destination', 'not available'); }
            
            if ( isset($parcel['shipments'][0]['lastState']['status']) ) { 
                $lastState = str_replace("'", " ",$parcel['shipments'][0]['lastState']['status']);
                if ( $this->checklastState($lastState) == true ) { $notification = true; }
                $this->checkAndUpdateCmd('lastState', $lastState);
            } else { $this->checkAndUpdateCmd('lastState', 'not available'); }
            
            if ( isset($parcel['shipments'][0]['lastState']['date']) ) {
                if ( $parcel['shipments'][0]['status'] == 'delivered' ) { $this->checkAndUpdateCmd('deliveryDate', date('d/m/Y H:i:s', strtotime($parcel['shipments'][0]['lastState']['date']))); }
                else { $this->checkAndUpdateCmd('deliveryDate', 'not available'); }
            } else { $this->checkAndUpdateCmd('deliveryDate', 'not available'); }
            
            if ( isset($parcel['shipments'][0]['states']) ) { 
                $states = $parcel['shipments'][0]['states'];
                $table_temp = array();
                $table_states = array();
                foreach ($states as $state) {
                    if ( isset($state['date']) ) { 
                        $datetime = new DateTime($state['date'], new DateTimeZone('UTC'));
                        $d = $datetime->format('d/m/Y');
                        $h = $datetime->format('H\hi');
                        $state_date = $d; 
                        $state_hour = $h;
                    }
                    else { 
                        $state_date = '';
                        $state_hour = '';
                    }
                    if ( isset($state['location']) ) { $state_location = str_replace("'", " ",$state['location']); } else { $state_location = ''; }
                    if ( isset($state['status']) ) { $state_status = str_replace("'", " ",$state['status']); } else { $state_status = ''; }
                    $table_temp[] = array( "date" => $state_date, "hour" => $state_hour, "location" => $state_location, "status" => $state_status );
                }
                $table_states['states'] = $table_temp;
                $this->checkAndUpdateCmd('states', json_encode($table_states));
            } else { $this->checkAndUpdateCmd('states', 'not available'); }
        }

        if ( $notification == true ) { $this->sendNotification(); }
        log::add('parcelTracking', 'debug', '└─End of parcel info refresh : ['.$result->httpCode.']');
        return $result;
    }

    public function checklastState($lastState) {

        $cmd = $this->getCmd('info', 'lastState');
        $previousState = $cmd->execCmd();
        if ( $lastState != $previousState ) { 
            log::add('parcelTracking', 'debug', '| Result checklastState : change' );
            return true;
        }
        else {
            log::add('parcelTracking', 'debug', '| Result checklastState : no change' );
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
        $lastHour = $info['states'][0]['hour'];
        
        // Cmd
        $cmdNotifications = config::byKey('cmdNotifications', 'parcelTracking');
        $formatNotifications = config::byKey('formatNotifications', 'parcelTracking');
        if ( $cmdNotifications != null) {
            if ( $formatNotifications != null ) {
                $title = 'Suivi colis';
                $message = $this->buildNotifications($name, $trackingId, $carrier, $status, $lastState, $lastDate, $lastHour);
            }
            else {
                $title = 'Suivi colis '.$trackingId.' - '.$name;
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
            $tags = $this->buildTags($name, $object, $trackingId, $carrier, $status, $lastState, $lastDate, $lastHour);
            $scenario = scenario::byString($scenarioNotifications);
            $scenario->setTags($tags);
            $scenario->execute();
            log::add('parcelTracking', 'debug', '| Send notification - scenarioId : #'.$scenario->getId().'# - tags : '.json_encode($tags) );
        }
    }

    public function buildNotifications($name, $trackingId, $carrier, $status, $lastState, $lastDate, $lastHour) {

        $formatNotifications = config::byKey('formatNotifications', 'parcelTracking');
        
        if ( !$formatNotifications ) {
            $formatNotifications = $lastState;
        }
        else {
            $formatNotifications = str_replace("#nom#", $name, $formatNotifications);
            $formatNotifications = str_replace("#numColis#", $trackingId, $formatNotifications);
            $formatNotifications = str_replace("#transporteur#", $carrier, $formatNotifications);
            $formatNotifications = str_replace("#statut#", $status, $formatNotifications);
            $formatNotifications = str_replace("#dernierEtat#", $lastState, $formatNotifications);
            $formatNotifications = str_replace("#date#", $lastDate, $formatNotifications);
            $formatNotifications = str_replace("#heure#", $lastHour, $formatNotifications);
        }
        return $formatNotifications;
    }

    public function buildTags($name, $object, $trackingId, $carrier, $status, $lastState, $lastDate, $lastHour) {

        $formatTags = config::byKey('formatTags', 'parcelTracking');
        $tags = arg2array($formatTags);
        $tags = str_replace("#nom#", $name, $tags);
        $tags = str_replace("#objet#", $object, $tags);
        $tags = str_replace("#numColis#", $trackingId, $tags);
        $tags = str_replace("#transporteur#", $carrier, $tags);
        $tags = str_replace("#statut#", $status, $tags);
        $tags = str_replace("#dernierEtat#", $lastState, $tags);
        $tags = str_replace("#date#", $lastDate, $tags);
        $tags = str_replace("#heure#", $lastHour, $tags);
        return $tags;
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
        log::add('parcelTracking', 'debug', '┌─Command execution : '.$logicalId);
            
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
