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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

// Fonction exécutée automatiquement après l'installation du plugin
function parcelTracking_install() {
    // Ajout du widget dédié 
    $exist = 0;
    foreach (eqLogic::byType('parcelTracking') as $eqLogic) {
        if ( $eqLogic->getLogicalId() == 'parcelTracking_widget' ) {
            $exist = 1;
            log::add('parcelTracking', 'debug', 'Widget dédié géré par le plugin déjà existant');
            break;
        }
    }
    if ( $exist == 0 ) {
        $parcel = new parcelTracking();
        $parcel->setEqType_name('parcelTracking');
        $parcel->setLogicalId('parcelTracking_widget');
        $parcel->setIsEnable(1);
        $parcel->setIsVisible(1);
        $parcel->setName('Widget Suivi Colis');
        $parcel->setConfiguration('eqLogicType', 'global');
        $parcel->save();
        log::add('parcelTracking', 'debug', 'Ajout du widget dédié géré par le plugin');
    }
    
    message::add('parcelTracking', 'Merci pour l\'installation du plugin Suivi colis. Lisez bien la documentation avant utilisation et n\'hésitez pas à laisser un avis sur le Market Jeedom !');
}

// Fonction exécutée automatiquement après la mise à jour du plugin
function parcelTracking_update() {
    // Vérification de la présence du widget dédié
    $exist = 0;
    foreach (eqLogic::byType('parcelTracking') as $eqLogic) {
        if ( $eqLogic->getLogicalId() == 'parcelTracking_widget' ) {
            $exist = 1;
            log::add('parcelTracking', 'debug', 'Widget dédié géré par le plugin déjà existant');
            break;
        }
    }
    if ( $exist == 0 ) {
        $parcel = new parcelTracking();
        $parcel->setEqType_name('parcelTracking');
        $parcel->setLogicalId('parcelTracking_widget');
        $parcel->setIsEnable(1);
        $parcel->setIsVisible(1);
        $parcel->setName('Widget Suivi Colis');
        $parcel->setConfiguration('eqLogicType', 'global');
        $parcel->save();
        log::add('parcelTracking', 'debug', 'Recréation du widget dédié géré par le plugin');
    }
    
    // Mise à jour de l'ensemble des commandes pour chaque équipement
    log::add('parcelTracking', 'debug', 'Mise à jour en cours des commandes du plugin Suivi colis');
    foreach (eqLogic::byType('parcelTracking') as $eqLogic) {
        if ( $eqLogic->getConfiguration('eqLogicType') == "" ) { $eqLogic->setConfiguration('eqLogicType', 'parcel');  }
        $eqLogic->save();
        log::add('parcelTracking', 'debug', 'Mise à jour des commandes effectuée pour l\'équipement '. $eqLogic->getHumanName());
    }
	
    message::add('parcelTracking', 'Merci pour la mise à jour du plugin Suivi colis. Consultez les notes de version avant utilisation et n\'hésitez pas à laisser un avis sur le Market Jeedom !');
}

// Fonction exécutée automatiquement après la suppression du plugin
function parcelTracking_remove() {
    message::add('parcelTracking', 'Le plugin Suivi colis a été correctement désinstallé. N\'hésitez pas à laisser un avis sur le Market Jeedom !');
}

?>