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


/* Permet la réorganisation des commandes dans l'équipement */
$("#table_cmd").sortable({
    
    axis: "y",
    cursor: "move",
    items: ".cmd",
    placeholder: "ui-state-highlight",
    tolerance: "intersect",
    forcePlaceholderSize: true
});


/* Fonction permettant l'affichage des commandes dans l'équipement */
function addCmdToTable(_cmd) {
    
    if (!isset(_cmd)) {
        var _cmd = { configuration: {} }
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {}
    }
    
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
	tr += '<td class="hidden-xs" style="width:5%">';
	tr += '<span class="cmdAttr" data-l1key="id"></span>';
	tr += '</td>';
	tr += '<td style="width:20%">';
	tr += '<input class="cmdAttr form-control input-sm" style="width:80%" data-l1key="name" placeholder="{{Nom de la commande}}">';
	tr += '</td>';
	tr += '<td style="width:10%; padding:5px 0px">';
	tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
	tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
	tr += '</td>';
	tr += '<td style="width:20%">';
	tr += '<input class="cmdAttr form-control input-sm" style="width:80%" data-l1key="logicalId" readonly=true>';
	tr += '</td>';
	tr += '<td style="width:10%">';
	if (init(_cmd.type) == 'info') {
		tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label>';
		tr += '</br><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label>';
		if (init(_cmd.subType) == 'binary') {
			tr += '</br><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label>';
		}
	}
	if (init(_cmd.type) == 'action') {
		tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label>';
	}
	tr += '</td>';
	tr += '<td style="width:25%">';
	tr += '<span class="cmdAttr" data-l1key="htmlstate" placeholder="{{Valeur}}">';
	tr += '</td>';	
	tr += '<td style="width:10%">';
	if (is_numeric(_cmd.id)) {
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
	}
	tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove" style="margin-top:4px;"></i>';
	tr += '</td>';
	tr += '</tr>';

    $('#table_cmd tbody').append(tr)
    
    var tr = $('#table_cmd tbody tr').last()
    jeedom.eqLogic.buildSelectCmd({
        id: $('.eqLogicAttr[data-l1key=id]').value(),
        filter: { type: 'info' },
        error: function (error) {
        $('#div_alert').showAlert({ message: error.message, level: 'danger' })
        },
        success: function (result) {
        tr.find('.cmdAttr[data-l1key=value]').append(result)
        tr.setValues(_cmd, '.cmdAttr')
        jeedom.cmd.changeType(tr, init(_cmd.subType))
        }
    })
};


/* Fonction effectué au moment de l'affichage de l'équipement */
function printEqLogic(_eqLogic) {

	var eqLogicType = document.querySelector('.eqLogicAttr[data-l2key="eqLogicType"]');
	if ( eqLogicType.value === 'global' ) {
		var elements = document.querySelectorAll('#parcel');
		elements.forEach(function(element) {
			element.style.display = 'none';
		});
	}
	else if ( eqLogicType.value === 'parcel' ) {
		var elements = document.querySelectorAll('#parcel');
		elements.forEach(function(element) {
			element.style.display = 'block';
		});
	}
}


/* Fonction permettant l'enregistrement du colis chez 17Track */
function register()  {
	
	$('#div_alert').showAlert({message: '{{Enregistrement en cours}}', level: 'warning'});	
	$.ajax({
		type: "POST",
		url: "plugins/parcelTracking/core/ajax/parcelTracking.ajax.php",
		data: {
			action: "register",
			trackingId: $('.eqLogicAttr[data-l2key=trackingId]').value(),
            },
		dataType: 'json',
			error: function (request, status, error) {
			handleAjaxError(request, status, error);
			},
		success: function (data) { 			

			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: '{{Erreur lors de l\'enregistrement du colis}}', level: 'danger'});
				return;
			}
			else  {
				if ( data.result.code == 0 && data.result.data?.accepted?.[0]?.number == $('.eqLogicAttr[data-l2key=trackingId]').value() ) {
					$('#div_alert').showAlert({message: '{{Enregistrement terminé avec succès}}', level: 'success'});
				}
				else if ( data.result.code == 0 && data.result.data?.rejected?.[0]?.number == $('.eqLogicAttr[data-l2key=trackingId]').value() ) {
					$('#div_alert').showAlert({message: data.result.data.rejected[0].error.message, level: 'warning'});
				}
				else { $('#div_alert').showAlert({message: '{{Erreur lors de l\'enregistrement du colis}}', level: 'danger'}); }
			}
		}
	});
};


/* Fonction permettant la mise à jour de l'enregistrement du colis chez 17Track */
function update()  {
	
	$('#div_alert').showAlert({message: '{{Mise à jour en cours}}', level: 'warning'});	
	$.ajax({
		type: "POST",
		url: "plugins/parcelTracking/core/ajax/parcelTracking.ajax.php",
		data: {
			action: "update",
			trackingId: $('.eqLogicAttr[data-l2key=trackingId]').value(),
            },
		dataType: 'json',
		error: function (request, status, error) {
			handleAjaxError(request, status, error);
			},
		success: function (data) { 			

			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: '{{Erreur lors de la mise à jour de l\'enregistrement du colis}}', level: 'danger'});
				return;
			}
			else  {
				if ( data.result.code == 0 && data.result.data?.accepted?.[0]?.number == $('.eqLogicAttr[data-l2key=trackingId]').value() ) {
					$('#div_alert').showAlert({message: '{{Mise à jour terminée avec succès}}', level: 'success'});
				}
				else if ( data.result.code == 0 && data.result.data?.rejected?.[0]?.number == $('.eqLogicAttr[data-l2key=trackingId]').value() ) {
					$('#div_alert').showAlert({message: data.result.data.rejected[0].error.message, level: 'warning'});
				}
				else { $('#div_alert').showAlert({message: '{{Erreur lors de la mise à jour de l\'enregistrement du colis}}', level: 'danger'}); }
			}
		}
	});
};


document.getElementById('bt_register').addEventListener('click', function() {
    var button = document.querySelector('.btn[data-action="save"]');
    button.click();
    setTimeout(register, 2000);
});


document.getElementById('bt_update').addEventListener('click', function() {
    var button = document.querySelector('.btn[data-action="save"]');
    button.click();
    setTimeout(update, 2000);
});


/* Récupération des infos concernant les paramètres additionnels */
var json;
$.ajax({
	type: "POST",
	url: "plugins/parcelTracking/core/ajax/parcelTracking.ajax.php",
	data: {
		action: "getJSON",
	},
	dataType: 'json',
	error: function (request, status, error) {
		handleAjaxError(request, status, error);
	},
	success: function (data) { 		
		json = data.result;
	}
});

document.getElementById('sel_carrier').addEventListener('change', function() {
	jsonArray = JSON.parse(json);
	const code = document.getElementById('sel_carrier').value;
	var htmlContent = '';
	var info = document.getElementById('info');
	info.innerHTML = '';
	
	jsonArray.forEach(carrier => {
		if ( carrier['Carrier Code'] === code ) {
			htmlContent = '<i class="fas fa-exclamation-triangle"></i> Additional parameter required : <br>'+carrier['Type']+' (Example : '+carrier['Example']+')';
		}		
	});

	info.innerHTML = htmlContent;
});


/* Fonction permettant la création d'un post sur le Community */
document.querySelector('.eqLogicAction[data-action=createCommunityPost]').addEventListener('click', function(event) {
    
	jeedom.plugin.createCommunityPost({
      type: eqType,
      error: function(error) {
        domUtils.hideLoading()
        jeedomUtils.showAlert({
          message: error.message,
          level: 'danger'
        })
      },
      success: function(data) {
        let element = document.createElement('a');
        element.setAttribute('href', data.url);
        element.setAttribute('target', '_blank');
        element.style.display = 'none';
        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
      }
    });
    return;
});