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
    message::add('parcelTracking', 'Merci pour l\'installation du plugin Suivi colis. Lisez bien la documentation avant utilisation et n\'hésitez pas à laisser un avis sur le Market Jeedom !');
}

// Fonction exécutée automatiquement après la mise à jour du plugin
function parcelTracking_update() {
    // Mise à jour de l'ensemble des commandes pour chaque équipement
    log::add('parcelTracking', 'debug', 'Mise à jour en cours des commandes du plugin Suivi colis');
    foreach (eqLogic::byType('parcelTracking') as $eqLogic) {
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