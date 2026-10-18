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

$carrierFile = dirname(__FILE__) . '/../data/apicarrier.all.json';
$carrierCount = '--';
$carrierUpdate = __('Jamais', __FILE__);

if (is_readable($carrierFile)) {
    $carrierContent = file_get_contents($carrierFile);
    if ($carrierContent !== false) {
        $carrierData = json_decode($carrierContent, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($carrierData)) {
            $carrierCount = count($carrierData).' '.__('entrées', __FILE__);
        }
    }

    $carrierTimestamp = filemtime($carrierFile);
    if ($carrierTimestamp !== false) {
        $carrierUpdate = date('d-m-Y H:i', $carrierTimestamp);
    }
}

?>

<form class="form-horizontal">
    <fieldset>
    
    <legend><i class="fas fa-wrench"></i> {{Paramètres 17track}}</legend>

    <div class="form-group">
        <label class="col-sm-4 control-label">{{Email}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Renseignez l'adresse email de votre compte 17track}}"></i></sup>
        </label>
        <div class="col-sm-4">
            <input id="email" class="configKey form-control" data-l1key="email"/>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-4 control-label">{{Mot de passe}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Renseignez le mot de passe de votre compte 17track}}"></i></sup>
        </label>
        <div class="col-sm-4">
            <div class="input-group" style="margin-bottom:0px !important">
                <input id="password" class="inputPassword configKey form-control" data-l1key="password"/>
                <span class="input-group-btn">
					<a class="btn btn-default form-control bt_showPass" style="height: 32px; width: 28px"><i class="fas fa-eye"></i></a>
                </span>
            </div>
        </div>
    </div>
    
    <div class="form-group">
        <label class="col-sm-4 control-label">{{Nombre de colis suivis en cours}}
            <sup><i class="fas fa-question-circle tooltips" title="{{La vérification permet de récupérer le nombre de colis suivis en cours sur votre compte 17Track}}"></i></sup>
        </label>
        <div class="col-sm-4">
            <div class="input-group" style="margin-bottom:0px !important">
                <input id="div_quota" class="form-control configKey" data-l1key="quota" placeholder="xxx / xxx" value="" readonly/>
                <span class="input-group-btn" title="{{Teste la connexion et vérifie le nombre de colis suivis en cours}}">
                    <a id="bt_testConnection" class="btn btn-warning" style="height: 32px; width: 28px"><i class="fas fa-check-square"></i></a>
                </span>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-4 control-label">{{Liste des transporteurs}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Actualisez la liste locale des transporteurs depuis 17track}}"></i></sup>
        </label>
        <div class="col-sm-4">
            <div class="input-group" style="margin-bottom:0px !important">
                <input id="carrier_count" class="form-control" value="<?php echo htmlspecialchars($carrierCount, ENT_QUOTES, 'UTF-8'); ?>" readonly />
                <span class="input-group-btn" title="{{Actualiser}}">
                    <a id="bt_refreshCarriers" class="btn btn-primary" style="height: 32px; width: 28px"><i class="fas fa-sync"></i></a>
                </span>
            </div>
            <span class="help-block"><?php echo __('Dernière mise à jour', __FILE__); ?> : <span id="carrier_last_update"><?php echo htmlspecialchars($carrierUpdate, ENT_QUOTES, 'UTF-8'); ?></span></span>
        </div>
    </div>
        
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
                    <a id="bt_selectCmdNotifications" class="btn btn-primary listCmdAction" style="height: 32px; width: 28px"><i class="fa fa-list-alt"></i></a>
                </span>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-4 control-label">{{Format du corps du message}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Vous pouvez utiliser les tags #name#, #trackingId#, #carrier#, #status#, #lastState#, #date#, #time#, #location# et #url#. <br/> Laissez le champ vide pour utiliser le format par défaut !}}"></i></sup>
        </label>
        <div class="col-sm-4">
            <div class="input-group" style="margin-bottom:0px !important">
                <input class="form-control configKey" data-l1key="formatNotifications"/>
                <span class="input-group-btn" title="{{Test des notifications}}">
                    <a id="bt_testNotifications" class="btn btn-warning" style="height: 32px; width: 28px"><i class="fas fa-comment"></i></a>
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
                    <a id="bt_selectScenarioNotifications" class="btn btn-primary listCmdAction" style="height: 32px; width: 28px"><i class="fa fa-list-alt"></i></a>
                </span>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label class="col-sm-4 control-label">{{Tags du scénario}}
            <sup><i class="fas fa-question-circle tooltips" title="{{Vous pouvez utiliser les tags #name#, #object#, #trackingId#, #carrier#, #status#, #lastState#, #date#, #time#, #location# et #url#. <br/> Exemple : nom=#name# numColis=#trackingId# transporteur=#carrier# ...}}"></i></sup>
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
                <option value="plus" selected>{{Widget par colis + widget unique}}</option>
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

    /* Fonction permettant la récupération du nombre de colis suivis */
    document.getElementById('bt_testConnection').addEventListener('click', function() {
        testConnection();
    });
    
    /* Fonction permettant de tester la connexion au compte 17Track et de récupérer le nombre de colis suivis */
    function testConnection()  {
    
        $('#div_alert').showAlert({message: '{{Connexion au compte 17Track}}', level: 'warning'});	
        $.ajax({
            type: "POST",
            url: "plugins/parcelTracking/core/ajax/parcelTracking.ajax.php",
            data: {
                action: "testConnection",
                email: $('#email').value(),
                password: $('#password').value(),
                },
            dataType: 'json',
                error: function (request, status, error) {
                handleAjaxError(request, status, error);
                },
            success: function (data) { 			

                if (data.state != 'ok') {
                    $('#div_alert').showAlert({message: '{{Erreur lors de la connexion au compte 17Track}}', level: 'danger'});
                    return;
                }
                else  {
                    if ( data.result.success == true ) {
                        $('#div_quota').val(data.result.used+' / '+data.result.capacity);
                        $('#div_alert').showAlert({message: '{{Connexion réussie}}', level: 'success'});
                    }
                    else { $('#div_alert').showAlert({message: '{{Identifiants du compte 17Track incorrects}}', level: 'danger'}); }
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

    /* Fonction permettant l'update de la liste des transporteurs*/
    document.getElementById('bt_refreshCarriers').addEventListener('click', function() {
        refreshCarriersList();
    });
    
    function refreshCarriersList()  {
        
        $('#div_alert').showAlert({message: '{{Mise à jour en cours}}', level: 'warning'});	
        $.ajax({
            type: "POST",
            url: "plugins/parcelTracking/core/ajax/parcelTracking.ajax.php",
            data: {
                action: "refreshCarriersList",
                },
            dataType: 'json',
                error: function (request, status, error) {
                handleAjaxError(request, status, error);
                },
            success: function (data) { 			

                if (data.state != 'ok') {
                    $('#div_alert').showAlert({message: '{{Erreur lors de la mise à jour de la liste des transporteurs}}', level: 'danger'});
                    return;
                }
                else  {
                    if ( data.result != null && data.result['success'] == true ) {
                        $('#carrier_count').text(data.result['count']+'{{ entrées}}');
                        $('#carrier_last_update').text(data.result['timestamp']);
                        $('#div_alert').showAlert({message: '{{Mise à jour de la liste des transporteurs réalisée avec succès}}', level: 'success'});
                    }
                    else {
                        $('#div_alert').showAlert({message: '{{Erreur lors de la mise à jour de la liste des transporteurs}}', level: 'danger'});
                    }
                }
            }
        });
    };
    
</script>