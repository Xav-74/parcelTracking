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

    /*public static function cronHourly() {
    
        foreach (eqLogic::byType('parcelTracking', true) as $parcelTracking) {		    // type = parcelTracking et eqLogic enable
            if ( $parcelTracking->getConfiguration('eqLogicType') != 'global') {
                $cmdRefresh = $parcelTracking->getCmd(null, 'refresh');		
                if (!is_object($cmdRefresh) ) {											// Si la commande n'existe pas ou condition non respectée
                    continue; 															// continue la boucle
                }
                if ( date('Gi') == 0 ) return;                                          // A 0h00 pas de refresh car cronDaily
                log::add('parcelTracking', 'debug', 'CronHourly');
                $cmdRefresh->execCmd();
            }
        }	
    }*/

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
                    if ( $parcelTracking->getDaysDifference($deliveryDate) >= $nbDays ) {
                        $parcelTracking->removeParcel($parcelTracking->getId());
                    }
                    else { log::add('parcelTracking', 'debug', '| Parcel not removed'); }
                }
            }
        }
        log::add('parcelTracking', 'debug', '└─End of remove parcels');
    }
 
    public static function getConfigForCommunity() {
    
		$CommunityInfo = "```\n";
        if ( !empty(config::byKey('apiKey', 'parcelTracking')) ) { $CommunityInfo = $CommunityInfo . 'API Key present' . "\n"; }
        else { $CommunityInfo = $CommunityInfo . 'API Key missing' . "\n"; }
        $CommunityInfo = $CommunityInfo . 'Quota : ' . config::byKey('quota', 'parcelTracking') . "\n";
        $CommunityInfo = $CommunityInfo . 'Language : ' . config::byKey('language', 'parcelTracking') . "\n";
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
                if ( $parcelTracking->getCmd('info','states')->execCmd() != 'not available' ) {
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

    public static function buildNotifications($name, $trackingId, $carrier, $status, $lastState, $lastDate, $lastTime) {

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
        }
        return $formatNotifications;
    }

    public static function buildTags($name, $object, $trackingId, $carrier, $status, $lastState, $lastDate, $lastTime) {

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
        return $tags;
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
    
        log::add('parcelTracking', 'debug', '┌─Remove parcel (Id : '.$this->getId().' - name : '.$this->getName().')');
        $apiKey = config::byKey('apiKey', 'parcelTracking');
        $myParcel = new parcelTracking_API($apiKey, trim($this->getConfiguration('trackingId')), null, null, null);
        $delete = $myParcel->deleteTrackingId();
        log::add('parcelTracking', 'debug', '└─Remove OK');
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
            log::add('parcelTracking', 'debug', 'Add command '.$cmd->getName().' (LogicalId : '.$cmd->getLogicalId().')');
        }
    }


    /*     * **********************Getteur Setteur*************************** */

    public static function getQuota($apiKey)
    {
        log::add('parcelTracking', 'debug', '┌─Command execution : getQuota');
        $myParcel = new parcelTracking_API($apiKey, null, null, null, null);
        $quota = $myParcel->getQuota();
        log::add('parcelTracking', 'debug', '└─End of quota recovery : ['.$quota->httpCode.']');
        return json_decode($quota->body);
    }
    
    public static function registerParcel($trackingId)
    {
        log::add('parcelTracking', 'debug', '┌─Command execution : registerParcel');
        
        $eqLogic = self::getparcelTrackingEqLogic(trim($trackingId));
        $apiKey = config::byKey('apiKey', 'parcelTracking');
        $language = config::byKey('language', 'parcelTracking');
        $carrier = $eqLogic->getConfiguration('carrier');
        $param = $eqLogic->getConfiguration('additionalParameter');
                
        $myParcel = new parcelTracking_API($apiKey, trim($trackingId), $language, $carrier, $param);
        log::add('parcelTracking', 'debug', '| Parcel trackingId : '.$trackingId.' - Language : '.$language);
        $register = $myParcel->registerTrackingId();
        log::add('parcelTracking', 'debug', '└─End of parcel registration : ['.$register->httpCode.']');
        return json_decode($register->body);
    }

    public static function updateParcelCarrier($trackingId)
    {
        log::add('parcelTracking', 'debug', '┌─Command execution : updateParcelCarrier');
        
        $eqLogic = self::getparcelTrackingEqLogic(trim($trackingId));
        $apiKey = config::byKey('apiKey', 'parcelTracking');
        $language = config::byKey('language', 'parcelTracking');
        $carrier = $eqLogic->getConfiguration('carrier');
        $param = $eqLogic->getConfiguration('additionalParameter');
                        
        $myParcel = new parcelTracking_API($apiKey, trim($trackingId), $language, $carrier, $param);
        log::add('parcelTracking', 'debug', '| Parcel trackingId : '.$trackingId.' - Language : '.$language);
        $carrier = $myParcel->updateRegistrationCarrier();
        log::add('parcelTracking', 'debug', '└─End of update parcel carrier : ['.$carrier->httpCode.']');
        return json_decode($carrier->body);
    }

    public static function updateParcelInfo($trackingId)
    {
        log::add('parcelTracking', 'debug', '┌─Command execution : updateParcelInfo');
        
        $eqLogic = self::getparcelTrackingEqLogic(trim($trackingId));
        $apiKey = config::byKey('apiKey', 'parcelTracking');
        $language = config::byKey('language', 'parcelTracking');
        $carrier = $eqLogic->getConfiguration('carrier');
        $param = $eqLogic->getConfiguration('additionalParameter');
                        
        $myParcel = new parcelTracking_API($apiKey, trim($trackingId), $language, $carrier, $param);
        log::add('parcelTracking', 'debug', '| Parcel trackingId : '.$trackingId.' - Language : '.$language);
        $info = $myParcel->updateRegistrationInfo();
        log::add('parcelTracking', 'debug', '└─End of update parcel info : ['.$info->httpCode.']');
        return json_decode($info->body);
    }

    public function refreshParcelInfo()
    {
        $apiKey = config::byKey('apiKey', 'parcelTracking');
        $language = config::byKey('language', 'parcelTracking');
        $trackingId = $this->getConfiguration('trackingId');
        $carrier = $this->getConfiguration('carrier');
        $param = $this->getConfiguration('additionalParameter');
        
        $myParcel = new parcelTracking_API($apiKey, trim($trackingId), $language, $carrier, $param);
        log::add('parcelTracking', 'debug', '| Parcel trackingId : '.$trackingId.' - Language : '.$language);
        $result = $myParcel->getTrackingResult();
        $this->updateCmds($result->body);    
        log::add('parcelTracking', 'debug', '└─End of parcel info refresh : ['.$result->httpCode.']');
        return $result;
    }

    public function updateCmds($json) {

        $parcel = json_decode($json, true);
        $notification = false;

        if ( $parcel['code'] == 0 && isset($parcel['data']['accepted'][0]['number']) ) {
            //status
            if ( isset($parcel['data']['accepted'][0]['track_info']['latest_status']['status']) ) { $this->checkAndUpdateCmd('status', $parcel['data']['accepted'][0]['track_info']['latest_status']['status']); } else { $this->checkAndUpdateCmd('status', __('Indisponible', __FILE__)); }
            
            //carrier
            if ( isset($parcel['data']['accepted'][0]['track_info']['tracking']['providers'][0]['provider']['name']) ) { $this->checkAndUpdateCmd('carrier', $parcel['data']['accepted'][0]['track_info']['tracking']['providers'][0]['provider']['name']); } else { $this->checkAndUpdateCmd('carrier', __('Indisponible', __FILE__)); }

            //origin - destination
            if ( isset($parcel['data']['accepted'][0]['track_info']['shipping_info']['shipper_address']['country']) ) { $this->checkAndUpdateCmd('origin', $parcel['data']['accepted'][0]['track_info']['shipping_info']['shipper_address']['country']); } else { $this->checkAndUpdateCmd('origin', __('Indisponible', __FILE__)); }
            if ( isset($parcel['data']['accepted'][0]['track_info']['shipping_info']['recipient_address']['country']) ) { $this->checkAndUpdateCmd('destination', $parcel['data']['accepted'][0]['track_info']['shipping_info']['recipient_address']['country']); } else { $this->checkAndUpdateCmd('destination', __('Indisponible', __FILE__)); }

            //lastState - lastEvent
            if ( isset($parcel['data']['accepted'][0]['track_info']['latest_event']['description_translation']['description']) ) { $this->checkAndUpdateCmd('lastState', str_replace("'", " ", $parcel['data']['accepted'][0]['track_info']['latest_event']['description_translation']['description'])); }
            elseif ( isset($parcel['data']['accepted'][0]['track_info']['latest_event']['description']) ) { $this->checkAndUpdateCmd('lastState', str_replace("'", " ", $parcel['data']['accepted'][0]['track_info']['latest_event']['description'])); }
            else { $this->checkAndUpdateCmd('lastState', __('Indisponible', __FILE__)); }
            
            if ( isset($parcel['data']['accepted'][0]['track_info']['latest_event']['time_utc']) ) {
                $lastEvent = $this->convertDate($parcel['data']['accepted'][0]['track_info']['latest_event']['time_utc']);
                if ( $this->checklastEvent($lastEvent) == true ) { $notification = true; }
                $this->checkAndUpdateCmd('lastEvent', $lastEvent); 
            }
            else { $this->checkAndUpdateCmd('lastEvent', __('Indisponible', __FILE__)); }
            
            //deliveryDate
            if ( isset($parcel['data']['accepted'][0]['track_info']['latest_status']['status']) ) {
                if ( $parcel['data']['accepted'][0]['track_info']['latest_status']['status'] == 'Delivered' ) {
                    $this->checkAndUpdateCmd('deliveryDate', $this->convertDate($parcel['data']['accepted'][0]['track_info']['latest_event']['time_utc']));
                }
                else { $this->checkAndUpdateCmd('deliveryDate', __('Indisponible', __FILE__)); }
            }
            else { $this->checkAndUpdateCmd('deliveryDate', __('Indisponible', __FILE__)); }
            
            //states
            if ( isset($parcel['data']['accepted'][0]['track_info']['tracking']['providers'][0]['events']) ) { 
                $states = $parcel['data']['accepted'][0]['track_info']['tracking']['providers'][0]['events'];
                $table_temp = array();
                $table_states = array();
                foreach ($states as $state) {
                    if ( isset($state['time_utc']) ) { 
                        $datetime = new DateTime($state['time_utc'], new DateTimeZone('UTC'));
                        $datetime->setTimezone(new DateTimeZone(config::byKey('timezone')));
                        $d = $datetime->format('d/m/Y');
                        $t = $datetime->format('H\hi');
                        $state_date = $d; 
                        $state_time = $t;
                    }
                    else { 
                        $state_date = '';
                        $state_time = '';
                    }
                    if ( isset($state['location']) ) { $state_location = str_replace("'", " ",$state['location']); } else { $state_location = ''; }
                    
                    if ( isset($state['description_translation']['description'] ) ) { $state_status = str_replace("'", " ",$state['description_translation']['description']); }
                    elseif ( isset($state['description']) ) { $state_status = str_replace("'", " ",$state['description']); }
                    else { $state_status = ''; }
                                        
                    $table_temp[] = array( "date" => $state_date, "time" => $state_time, "location" => $state_location, "status" => $state_status );
                }
                $table_states['states'] = $table_temp;
                $this->checkAndUpdateCmd('states', json_encode($table_states));
            } else { $this->checkAndUpdateCmd('states', __('Indisponible', __FILE__)); }
        }

        if ( $notification == true ) { $this->sendNotification(); }
    }

    public function webhookUpdateCmds($json) {

        $parcel = json_decode($json, true);
        $notification = false;
        log::add('parcelTracking', 'debug', '| Update cmds - Parcel trackingId : '.$parcel['data']['number']);

        //status
        if ( isset($parcel['data']['track_info']['latest_status']['status']) ) { $this->checkAndUpdateCmd('status', $parcel['data']['track_info']['latest_status']['status']); } else { $this->checkAndUpdateCmd('status', __('Indisponible', __FILE__)); }
            
        //carrier
        if ( isset($parcel['data']['track_info']['tracking']['providers'][0]['provider']['name']) ) { $this->checkAndUpdateCmd('carrier', $parcel['data']['track_info']['tracking']['providers'][0]['provider']['name']); } else { $this->checkAndUpdateCmd('carrier', __('Indisponible', __FILE__)); }

        //origin - destination
        if ( isset($parcel['data']['track_info']['shipping_info']['shipper_address']['country']) ) { $this->checkAndUpdateCmd('origin', $parcel['data']['track_info']['shipping_info']['shipper_address']['country']); } else { $this->checkAndUpdateCmd('origin', __('Indisponible', __FILE__)); }
        if ( isset($parcel['data']['track_info']['shipping_info']['recipient_address']['country']) ) { $this->checkAndUpdateCmd('destination', $parcel['data']['track_info']['shipping_info']['recipient_address']['country']); } else { $this->checkAndUpdateCmd('destination', __('Indisponible', __FILE__)); }

        //lastState - lastEvent
        if ( isset($parcel['data']['track_info']['latest_event']['description_translation']['description']) ) { $this->checkAndUpdateCmd('lastState', str_replace("'", " ", $parcel['data']['track_info']['latest_event']['description_translation']['description'])); }
        else if ( isset($parcel['data']['track_info']['latest_event']['description']) ) { $this->checkAndUpdateCmd('lastState', str_replace("'", " ", $parcel['data']['track_info']['latest_event']['description'])); }
        else { $this->checkAndUpdateCmd('lastState', __('Indisponible', __FILE__)); }
                
        if ( isset($parcel['data']['track_info']['latest_event']['time_utc']) ) {
            $lastEvent = $this->convertDate($parcel['data']['track_info']['latest_event']['time_utc']);
            if ( $this->checklastEvent($lastEvent) == true ) { $notification = true; }
            $this->checkAndUpdateCmd('lastEvent', $lastEvent); 
        }
        else { $this->checkAndUpdateCmd('lastEvent', __('Indisponible', __FILE__)); }
            
        //deliveryDate
        if ( isset($parcel['data']['track_info']['latest_status']['status']) ) {
            if ( $parcel['data']['track_info']['latest_status']['status'] == 'Delivered' ) {
                $this->checkAndUpdateCmd('deliveryDate', $this->convertDate($parcel['data']['track_info']['latest_event']['time_utc']));
            }
            else { $this->checkAndUpdateCmd('deliveryDate', __('Indisponible', __FILE__)); }
        }
        else { $this->checkAndUpdateCmd('deliveryDate', __('Indisponible', __FILE__)); }
            
        //states
        if ( isset($parcel['data']['track_info']['tracking']['providers'][0]['events']) ) { 
            $states = $parcel['data']['track_info']['tracking']['providers'][0]['events'];
            $table_temp = array();
            $table_states = array();
            foreach ($states as $state) {
                if ( isset($state['time_utc']) ) { 
                    $datetime = new DateTime($state['time_utc'], new DateTimeZone('UTC'));
                    $datetime->setTimezone(new DateTimeZone(config::byKey('timezone')));
                    $d = $datetime->format('d/m/Y');
                    $t = $datetime->format('H\hi');
                    $state_date = $d; 
                    $state_time = $t;
                }
                else { 
                    $state_date = '';
                    $state_time = '';
                }
                if ( isset($state['location']) ) { $state_location = str_replace("'", " ",$state['location']); } else { $state_location = ''; }
                
                if ( isset($state['description_translation']['description'] ) ) { $state_status = str_replace("'", " ",$state['description_translation']['description']); }
                elseif ( isset($state['description']) ) { $state_status = str_replace("'", " ",$state['description']); }
                else { $state_status = ''; }
                               
                $table_temp[] = array( "date" => $state_date, "time" => $state_time, "location" => $state_location, "status" => $state_status );
            }
            $table_states['states'] = $table_temp;
            $this->checkAndUpdateCmd('states', json_encode($table_states));
        } else { $this->checkAndUpdateCmd('states', __('Indisponible', __FILE__)); }
        
        if ( $notification == true ) { $this->sendNotification(); }
    }
    
    public function convertDate($date_utc) {

        $datetime = new DateTime($date_utc, new DateTimeZone('UTC'));
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
        
        // Cmd
        $cmdNotifications = config::byKey('cmdNotifications', 'parcelTracking');
        $formatNotifications = config::byKey('formatNotifications', 'parcelTracking');
        if ( $cmdNotifications != null) {
            if ( $formatNotifications != null ) {
                $title = 'Suivi colis';
                $message = $this->buildNotifications($name, $trackingId, $carrier, $status, $lastState, $lastDate, $lastTime);
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
            $tags = $this->buildTags($name, $object, $trackingId, $carrier, $status, $lastState, $lastDate, $lastTime);
            $scenario = scenario::byString($scenarioNotifications);
            $scenario->setTags($tags);
            $scenario->execute();
            log::add('parcelTracking', 'debug', '| Send notification - scenarioId : #'.$scenario->getId().'# - tags : '.json_encode($tags) );
        }
    }

    public static function refreshAll() {
    
        foreach (eqLogic::byType('parcelTracking', true) as $parcelTracking) {		   
            if ( $parcelTracking->getConfiguration('eqLogicType') != 'global') {
                $cmdRefresh = $parcelTracking->getCmd(null, 'refresh');		
                if (!is_object($cmdRefresh) ) {
                    continue;
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
        }

        foreach (eqLogic::byType('parcelTracking', true) as $parcelTracking) {		   
            $parcelTracking->refreshWidget();
        }	
    }

    public static function addParcel($name, $trackingId, $carrier, $param)
    {
        $parcel = new parcelTracking();
        $parcel->setEqType_name('parcelTracking');
        $parcel->setName($name);
        $parcel->setConfiguration('trackingId', $trackingId);
        $parcel->setConfiguration('carrier', $carrier);
        $parcel->setConfiguration('additionalParameter', $param);
        $parcel->save();
        if ( $carrier == "" ) { $log_carrier = "none"; }
        else { $log_carrier = $carrier; }
        if ( $param == "" ) { $log_param = "none"; }
        else { $log_param = $param; }
        log::add('parcelTracking', 'debug', 'Add parcel (name : '.$name.' - trackingId : '.$trackingId.' - carrier : '.$log_carrier.' - parameter : '.$log_param.')');

        parcelTracking::registerParcel($trackingId);
        
        foreach (eqLogic::byType('parcelTracking', true) as $parcelTracking) {		   
            $parcelTracking->refreshWidget();
        }
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
