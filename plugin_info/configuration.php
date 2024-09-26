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
        <label class="col-sm-4 control-label">{{Clé API 17Track <a href="https://api.17track.net/en/admin/dashboard">(lien)</a>}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Renseignez la clé API après vous être enregistré sur le site www.17track.net/en}}"></i></sup>
        </label>
        <div class="col-sm-4">
            <input id="apiKey" class="configKey form-control" data-l1key="apiKey"/>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-4 control-label">{{Quota restant}}
            <sup><i class="fas fa-question-circle tooltips" title="{{La vérification permet de récupérer le quota de suivi restant sur vote compte 17Track}}"></i></sup>
        </label>
        <div class="col-sm-4">
            <div class="input-group" style="margin-bottom:0px !important">
                <input id="div_quota" class="form-control configKey" data-l1key="quota" placeholder="xxx / xxx" value="" readonly/>
                <span class="input-group-btn" title="{{Vérifier}}">
                    <a id="bt_getQuota" class="btn btn-warning"><i class="fas fa-check-square"></i></a>
                </span>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-4 control-label">{{Langue}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Sélectionnez la langue utilisée pour les retours API. Attention, cela décomptera 2 suivis par colis sur votre quota.<br/> Laissez le champ sur 'Langue par défaut' pour conserver la langue par défaut du transporteur ! }}"></i></sup>
        </label>
        <div class="col-sm-4">
            <select class="configKey form-control" data-l1key="language">
                <option value="default" selected>{{Langue par défaut}}</option>
                <option value="fr">{{Français}}</option>
                <option value="en">{{Anglais}}</option>
                <option value="de">{{Allemand}}</option>
                <option value="es">{{Espagnol}}</option>
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
                <input id="cmdNotifications" class="form-control configKey" data-l1key="cmdNotifications"/>
                <span class="input-group-btn">
                    <a id="bt_selectCmdNotifications" class="btn btn-primary listCmdAction"><i class="fa fa-list-alt"></i></a>
                </span>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-4 control-label">{{Format du corps du message}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Vous pouvez utiliser les tags #name#, #trackingId#, #carrier#, #status#, #lastState#, #date# et #time#. <br/> Laissez le champ vide pour utiliser le format par défaut !}}"></i></sup>
        </label>
        <div class="col-sm-4">
            <div class="input-group" style="margin-bottom:0px !important">
                <input class="form-control configKey" data-l1key="formatNotifications"/>
                <span class="input-group-btn" title="{{Test des notifications}}">
                    <a id="bt_testNotifications" class="btn btn-warning"><i class="fas fa-comment"></i></a>
                </span>
            </div>
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
                    <a id="bt_selectScenarioNotifications" class="btn btn-primary listCmdAction"><i class="fa fa-list-alt"></i></a>
                </span>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-4 control-label">{{Tags du scénario}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Vous pouvez utiliser les tags #name#, #object#, #trackingId#, #carrier#, #status#, #lastState#, #date# et #time#. <br/> Exemple : nom=#name# numColis=#trackingId# transporteur=#carrier# ...}}"></i></sup>
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

    /* Fonction permettant la récupération du quota restant */
    document.getElementById('bt_getQuota').addEventListener('click', function() {
        getQuota();
    });
    
    function getQuota()  {
        
        $('#div_alert').showAlert({message: '{{Récupérations des informations}}', level: 'warning'});	
        $.ajax({
            type: "POST",
            url: "plugins/parcelTracking/core/ajax/parcelTracking.ajax.php",
            data: {
                action: "getQuota",
                apiKey: $('#apiKey').value(),
                },
            dataType: 'json',
                error: function (request, status, error) {
                handleAjaxError(request, status, error);
                },
            success: function (data) { 			

                if (data.state != 'ok') {
                    $('#div_alert').showAlert({message: '{{Erreur lors de la récupération des informations}}', level: 'danger'});
                    return;
                }
                else  {
                    if ( data.result.code == 0 && data.result.data?.quota_remain !== undefined && data.result.data?.quota_total !== undefined ) {
                        quota = data.result.data.quota_remain+' / '+data.result.data.quota_total;
                        $('#div_quota').val(quota);
                        $('#div_alert').showAlert({message: '{{Informations récupérées avec succès}}', level: 'success'});
                    }
                    else { $('#div_alert').showAlert({message: '{{Erreur lors de la récupération des informations}}', level: 'danger'}); }
                }
            }
        });
    };

    /* Fonction permettant l'envoi d'une notification de test*/
    document.getElementById('bt_testNotifications').addEventListener('click', function() {
        testNotifications();
    });
    
    function testNotifications()  {
        
        $('#div_alert').showAlert({message: '{{Envoi de la notification}}', level: 'warning'});	
        $.ajax({
            type: "POST",
            url: "plugins/parcelTracking/core/ajax/parcelTracking.ajax.php",
            data: {
                action: "testNotifications",
                cmdNotifications: $('#cmdNotifications').value(),
                },
            dataType: 'json',
                error: function (request, status, error) {
                handleAjaxError(request, status, error);
                },
            success: function (data) { 			

                if (data.state != 'ok') {
                    $('#div_alert').showAlert({message: '{{Erreur lors de l\'envoi de la notification}}', level: 'danger'});
                    return;
                }
                else  {
                    $('#div_alert').showAlert({message: '{{Notification envoyée avec succès}}', level: 'success'});
                }
            }
        });
    };
    
</script>