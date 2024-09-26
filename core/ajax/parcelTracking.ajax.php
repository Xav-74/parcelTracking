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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect()) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    /* Fonction permettant l'envoi de l'entête 'Content-Type: application/json'
        En V3 : indiquer l'argument 'true' pour contrôler le token d'accès Jeedom
        En V4 : autoriser l'exécution d'une méthode 'action' en GET en indiquant le(s) nom(s) de(s) action(s) dans un tableau en argument
    */
    
    ajax::init();

    if (init('action') == 'removeParcel') {
		$result = parcelTracking::removeParcel(init('eqLogicId'));
		ajax::success($result);
	}

    if (init('action') == 'addParcel') {
		$result = parcelTracking::addParcel(init('name'), init('trackingId'));
		ajax::success($result);
	}

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }
    
    if (init('action') == 'register') {
		$result = parcelTracking::registerParcel(init('trackingId'));
		ajax::success($result);
	}

    if (init('action') == 'updateCarrier') {
		$result = parcelTracking::updateParcelCarrier(init('trackingId'));
		ajax::success($result);
	}

    if (init('action') == 'updateInfo') {
		$result = parcelTracking::updateParcelInfo(init('trackingId'));
		ajax::success($result);
	}

    if (init('action') == 'getQuota') {
		$result = parcelTracking::getQuota(init('apiKey'));
		ajax::success($result);
	}

    if (init('action') == 'testNotifications') {
		$cmdNotifications = init('cmdNotifications');
        $data = [
            'title' => 'Suivi Colis',
            'message' => 'Ceci est un test des notifications du plugin',
        ];
        if (strpos($cmdNotifications, '&&') !== false) {
            $cmds = explode(' && ', $cmdNotifications);
            $cmds = array_map('trim', $cmds);                           // On supprime les espaces autour de chaque valeur
            foreach ($cmds as $cmd) {
                cmd::byString($cmd)->execCmd($data);
                log::add('parcelTracking', 'debug', 'Test notification - cmdId : '.$cmd.' - title : Suivi Colis - message : Ceci est un test des notifications du plugin');
            }
        }
        else {
            cmd::byString($cmdNotifications)->execCmd($data);
            log::add('parcelTracking', 'debug', 'Test notification - cmdId : '.$cmdNotifications.' - title : Suivi Colis - message : Ceci est un test des notifications du plugin');
        }
		ajax::success();
	}

    if (init('action') == 'getJSON') {
		$result = file_get_contents( dirname(__FILE__).'/../../data/apicarrier.param.json');
		ajax::success($result);
	}

    throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
    /*     * *********Catch exeption*************** */
}

catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}

?>