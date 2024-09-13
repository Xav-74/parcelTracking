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

require_once __DIR__  . '/../../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');


/*
 * Typical URL :
 * https://<jeedom-host>/plugins/parcelTracking/core/php/webhook.php
*/

log::add('parcelTracking', 'debug', '┌─Received Webhook');

// Receive the data from 17TRACK
$input = file_get_contents('php://input');

// Verify the signature
$receivedSignature = $_SERVER['HTTP_SIGN'];
$secretKey = config::byKey('apiKey', 'parcelTracking');
$calculatedSignature = hash('sha256', $input.'/'.$secretKey);

if ($input) {
    if ($receivedSignature === $calculatedSignature) {
        log::add('parcelTracking', 'debug', '| Valid signature');
        log::add('parcelTracking', 'debug', '| Push message : '.$input);
        http_response_code(200);
        echo json_encode(['code' => 200, 'message' => 'Received and processed']);

        $data = json_decode($input, true);
        if ( $data['event'] == 'TRACKING_UPDATED' && isset($data['data']['number']) ) {
            $eqLogic = eqLogic::byLogicalId($data['data']['number'],'parcelTracking');
            if( is_object($eqLogic) ) {
                $eqLogic->webhookUpdateCmds($input);
            }
        }
    }
    else { 
        log::add('parcelTracking', 'debug', '| Invalid signature');
        log::add('parcelTracking', 'debug', '| Data not processed');
        http_response_code(400);
        echo json_encode(['code' => 400, 'message' => 'Invalid signature']);
    }
}
else {
    log::add('parcelTracking', 'debug', '| No data received');
    http_response_code(400);
    echo json_encode(['code' => 400, 'message' => 'No data received']);
}

log::add('parcelTracking', 'debug', '└─End Webhook');

?>


