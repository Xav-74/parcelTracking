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
            <option value="" disabled selected hidden>{{Choisir dans la liste}}</option>
            <option value="fr">Français</option>
            <option value="en">Anglais</option>
            </select>
        </div>    
    </div>
    <br/>

    <legend><i class="fas fa-cogs"></i> {{Paramètres optionnels du plugin}}</legend>

    <div class="form-group">
        <label class="col-sm-4 control-label">{{Objet parent par défaut}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Renseignez l'objet parent par défaut configuré lors de la création d'un colis.<br/> Laissez le champ sur 'Aucun' pour ne pas pré-remplir l'objet par défaut !}}"></i></sup>
        </label>
        <div class="col-sm-4">
            <select class="configKey form-control" data-l1key="defaultObject">
                <option value="">{{Aucun}}</option>
                <?php
                    $options = '';
                    foreach ((jeeObject::buildTree(null, false)) as $object) {
                        $options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
                    }
                    echo $options;
                ?>
            </select>        
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-4 control-label">{{Code postal par défaut}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Renseignez le code postal par défaut configuré lors de la création d'un colis.<br/> Laissez le champ vide pour ne pas pré-remplir le code postal !}}"></i></sup>
        </label>
        <div class="col-sm-4">
            <input id="defaultZipcode" type="number" class="configKey form-control" data-l1key="defaultZipcode"/>
        </div>
    </div>
    
    <div class="form-group">
        <label class="col-sm-4 control-label">{{Durée de conservation de l'équipement après livraison (en jours)}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Renseignez le nombre de jours après lequel le colis livré est supprimé du plugin.<br/> Laissez le champ vide pour conserver les colis !}}"></i></sup>
        </label>
        <div class="col-sm-4">
            <input type="number" class="configKey form-control" data-l1key="nbDays"/>
        </div>
    </div>
    <br/>

    <legend><i class="fas fa-comments"></i> {{Paramètres des notifications}}</legend>

    <div class="form-group">
        <label class="col-sm-4 control-label">{{Commande à utiliser pour l'envoi de notifications}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Choisissez la commande de notification de type message (ex. Jeemate, JeedomConnect, Slack, ou autre...).<br/> Laissez le champ vide pour ne pas recevoir de notifications !}}"></i></sup>
        </label>
        <div class="col-sm-4">
            <div class="input-group" style="margin-bottom:0px !important">
                <input class="form-control configKey" data-l1key="cmdNotifications"/>
                <span class="input-group-btn">
                    <a id="bt_selectCmdNotifications" class="btn btn-primary listCmdAction"><i class="fa fa-list-alt"></i></a>
                </span>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-4 control-label">{{Format du corps du message}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Vous pouvez utiliser les tags #nom#, #numColis#, #transporteur#, #statut#, #dernierEtat#, #date#, et #heure#. <br/> Laissez le champ vide pour utiliser le format par défaut !}}"></i></sup>
        </label>
        <div class="col-sm-4">
            <input class="form-control configKey" data-l1key="formatNotifications"/>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-4 control-label">{{Scénario à utiliser pour l'envoi de notifications}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Choisissez le scénario à éxécuter.<br/> Laissez le champ vide pour ne pas recevoir de notifications !}}"></i></sup>
        </label>
        <div class="col-sm-4">
            <div class="input-group" style="margin-bottom:0px !important">
                <input class="form-control configKey" data-l1key="scenarioNotifications"/>
                <span class="input-group-btn">
                    <a id="bt_selectScenarioNotifications" class="btn btn-primary"><i class="fa fa-list-alt"></i></a>
                </span>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-4 control-label">{{Tags du scénario}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Vous pouvez utiliser les tags #nom#, #numColis#, #transporteur#, #statut#, #dernierEtat#, #date#, et #heure#. <br/> Exemple : nom=#nom# numColis=#numColis# transporteur=#transporteur# ...}}"></i></sup>
        </label>
        <div class="col-sm-4">
            <input class="form-control configKey" data-l1key="formatTags"/>
        </div>
    </div>
    <br/>
    
    <legend><i class="fas fa-palette"></i> {{Paramètres du widget}}</legend>

    <div class="form-group">
        <label class="col-sm-4 control-label">{{Choix du widget}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Choisissez entre un widget par colis ou un widget unique pour tous les colis}}"></i></sup>
        </label>
        <div class="col-sm-4">
            <select class="configKey form-control" data-l1key="defaultWidget">
                <option value="" disabled selected hidden>{{Choisir dans la liste}}</option>
                <option value="all" selected>{{Widget par colis}}</option>
                <option value="one">{{Widget unique}}</option>
                <option value="none">{{Aucun}}</option>
            </select>        
        </div>
    </div>
    <br/><br/>

    </fieldset>
</form>

<script>

    var CommunityButton = document.querySelector('#createCommunityPost > span');
    if(CommunityButton) {CommunityButton.innerHTML = "{{Community}}";}

    document.getElementById('bt_selectCmdNotifications').addEventListener('click', function() {
        jeedom.cmd.getSelectModal({ cmd: { type: 'action', subType: 'message' } }, function(result) {
            document.querySelector('.configKey[data-l1key=cmdNotifications]').value = document.querySelector('.configKey[data-l1key=cmdNotifications]').value + result.human;
        });
    });
    
    document.getElementById('bt_selectScenarioNotifications').addEventListener('click', function() {
        jeedom.scenario.getSelectModal({}, function(result) {
            document.querySelector('.configKey[data-l1key=scenarioNotifications]').value = result.human;
        });
    });
    
</script>