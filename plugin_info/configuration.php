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
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>

<form class="form-horizontal">
    <fieldset>
    
    <legend><i class="fas fa-wrench"></i> {{Paramètres API Parcelsapp}}</legend>

    <div class="form-group">
        <label class="col-sm-4 control-label">{{Clé API Parcelsapp <a href="https://parcelsapp.com/dashboard/#/login">(lien)</a>}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Renseignez la clé API après vous être enregistré sur le site www.parcelsapp.com}}"></i></sup>
        </label>
        <div class="col-sm-4">
            <input class="configKey form-control" data-l1key="apiKey"/>
        </div>
    </div>
    
    <div class="form-group">
        <label class="col-sm-4 control-label">{{Langue}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Sélectionnez la langue utilisée pour les retours API}}"></i></sup>
        </label>
        <div class="col-sm-4">
            <select class="configKey form-control" data-l1key="language">
            <option value="" disabled selected hidden>{{Choisir dans la liste}}></option>
            <option value="fr">Français</option>
            <option value="en">Anglais</option>
            </select>
        </div>    
    </div>
    <br/>

    <legend><i class="fas fa-cogs"></i> {{Paramètres optionnels du plugin}}</legend>

    <div class="form-group">
        <label class="col-sm-4 control-label">{{Durée de conservation de l'équipement après livraison (en jours)}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Renseignez le nombre de jours après lequel le colis livré est supprimé du plugin.<br/> Laissez le champ vide pour conserver les colis !}}"></i></sup>
        </label>
        <div class="col-sm-4">
            <input type="number" class="configKey form-control" data-l1key="nbDays"/>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-4 control-label">{{Commande à utiliser pour l'envoi de notifications}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Choisissez la commande de notification avec type message (ex. Jeemate, JeedomConnect, Slack, ou autre...).<br/> Laissez le champ vide pour ne pas recevoir de notifications !}}"></i></sup>
        </label>
        <div class="col-sm-4">
            <div class="input-group">
                <input class="form-control configKey input-sm" data-l1key="cmdNotifications"/>
                <span class="input-group-btn">
                    <a id="bt_selectCmdNotifications" class="btn btn-primary btn-sm listCmdAction"><i class="fa fa-list-alt"></i></a>
                </span>
            </div>
        </div>
    </div>
    <br/><br/>

    </fieldset>
</form>

<script>

    var CommunityButton = document.querySelector('#createCommunityPost > span');
    if(CommunityButton) {CommunityButton.innerHTML = "{{Community}}";}

    $("#bt_selectCmdNotifications").on('click', function () {
        jeedom.cmd.getSelectModal({ cmd: { type: 'action', subType: 'message' } }, function (result) {
            $('.configKey[data-l1key=cmdNotifications]').value(result.human);
        });
    });
    
</script>