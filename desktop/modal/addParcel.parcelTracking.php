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

include_file('core', 'authentification', 'php');

require_once dirname(__FILE__) . '/../../core/php/parcelTracking.inc.php';

if (!isConnect()) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

$eqLogic = eqLogic::byId(init('eqLogic_id'));

?>

<div class="modal-content">
    
	<div class="col-sm-12" style="padding: 0px !important; margin-bottom: 5px;">
		<input id="name" type="text" class="form-control" placeholder="{{Nom du colis}}"/>
	</div>
						
	<div class="col-sm-12" style="padding: 0px !important; margin-bottom: 5px;">
		<input id="trackingId" type="text" class="form-control" placeholder="{{Numéro de suivi du colis}}"/>
	</div>

        <div class="col-sm-12" style="padding: 0px !important; margin-bottom: 5px;">
                <select id="carrier" class="eqLogicAttr form-control" style="background-color: var(--bg-modal-color, #fff);">
                        <option value="">{{Aucun transporteur}}</option>
                        <?php
                                $json = file_get_contents('plugins/parcelTracking/data/apicarrier.all.json');
                                $carriers = json_decode($json, true);

                                $allowedCountriesRaw = config::byKey('allowedCountries', 'parcelTracking', '');
                                if (is_array($allowedCountriesRaw)) {
                                        $allowedCountries = $allowedCountriesRaw;
                                } else {
                                        $allowedCountries = explode(',', (string) $allowedCountriesRaw);
                                }

                                $allowedCountries = array_values(array_filter(array_map(function ($value) {
                                        return strtoupper(trim((string) $value));
                                }, $allowedCountries)));

                                $includeUndefined = false;
                                if (!empty($allowedCountries)) {
                                        $includeUndefined = in_array('UNDEFINED', $allowedCountries, true);
                                        if ($includeUndefined) {
                                                $allowedCountries = array_values(array_diff($allowedCountries, ['UNDEFINED']));
                                        }
                                }

                                $carriers = is_array($carriers) ? $carriers : [];
                                $filteredCarriers = array_filter($carriers, function ($carrier) use ($allowedCountries, $includeUndefined) {
                                        if (!is_array($carrier)) {
                                                return false;
                                        }

                                        $countryIso = isset($carrier['_country_iso']) ? strtoupper(trim($carrier['_country_iso'])) : '';

                                        if ($countryIso === '') {
                                                if (empty($allowedCountries)) {
                                                        return true;
                                                }

                                                return $includeUndefined;
                                        }

                                        if (empty($allowedCountries)) {
                                                return true;
                                        }

                                        return in_array($countryIso, $allowedCountries, true);
                                });

                                usort($filteredCarriers, function ($a, $b) {
                                        $nameA = isset($a['_name']) ? trim($a['_name']) : '';
                                        $nameB = isset($b['_name']) ? trim($b['_name']) : '';

                                        return strcasecmp($nameA, $nameB);
                                });

                                foreach ($filteredCarriers as $carrier) {
                                        if (!isset($carrier['key'])) {
                                                continue;
                                        }

                                        $name = isset($carrier['_name']) && trim($carrier['_name']) !== '' ? trim($carrier['_name']) : $carrier['key'];
                                        $countryIso = isset($carrier['_country_iso']) && trim($carrier['_country_iso']) !== '' ? strtoupper(trim($carrier['_country_iso'])) : '';
                                        $countryLabel = $countryIso !== '' ? parcelTracking_getCountryLabel($countryIso, 'fr_FR') : '';
                                        $suffix = $countryLabel !== '' ? ' (' . $countryLabel . ')' : '';

                                        echo '<option value="' . htmlspecialchars($carrier['key'], ENT_QUOTES) . '">' . htmlspecialchars($name . $suffix, ENT_QUOTES) . '</option>';
                                }

                                $allowedCountryLabels = [];

                                if (!empty($allowedCountries)) {
                                        $locale = config::byKey('language', 'core', 'fr_FR');
                                        if (!is_string($locale) || trim($locale) === '') {
                                                $locale = 'fr_FR';
                                        }

                                        foreach ($allowedCountries as $isoCode) {
                                                $label = parcelTracking_getCountryLabel($isoCode, $locale);
                                                if ($label === '') {
                                                        $label = $isoCode;
                                                }
                                                $allowedCountryLabels[] = $label . ' (' . $isoCode . ')';
                                        }

                                        natcasesort($allowedCountryLabels);
                                        $allowedCountryLabels = array_values($allowedCountryLabels);
                                }

                                if ($includeUndefined) {
                                        $allowedCountryLabels[] = __('Sans code pays (undefined)', __FILE__);
                                }

                                if (empty($allowedCountryLabels)) {
                                        $allowedCountryLabels[] = __('Tous les pays disponibles', __FILE__);
                                }

                                $allowedCountriesLabel = implode(', ', array_map(function ($label) {
                                        return htmlspecialchars($label, ENT_QUOTES);
                                }, $allowedCountryLabels));
                        ?>
                </select>
        </div>

        <div class="col-sm-12" style="padding: 0px !important; margin-bottom: 5px;">
                <span style="display: block; font-size: 12px; color: var(--txt-color, inherit); text-align: left;">
                        <strong>{{Pays affichés}} :</strong> <?php echo $allowedCountriesLabel; ?>
                </span>
        </div>
	
	<div class="col-sm-12" style="padding: 0px !important; margin-bottom: 5px;">
		<input id="param" type="text" class="form-control" placeholder="{{Paramètre additionnel}}"/>
	</div>

	<div class="col-sm-12" style="padding: 0px !important; margin-bottom: 8px; height: 40px;">
		<span id="info" style="font-size: 12px"></span>
	</div>
					 
	<div class="col-sm-12" style="padding: 0px !important;">
		<input id="btn_add" type="submit" class="btn btn-primary" style="float: right; width: 125px" value="{{Ajouter}}"/>
	</div>
	
</div>
	
<script>
		
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


	document.getElementById('carrier').addEventListener('change', function() {
		jsonArray = JSON.parse(json);
		const code = document.getElementById('carrier').value;
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
	

	$('#btn_add').click( function(){
			
		var name = $('#name').val();
		var trackingId = $('#trackingId').val();
		var carrier = $('#carrier').val();
		var param = $('#param').val();
					
		if ( name == '' || trackingId == '' )  {
			$('#div_alert').showAlert({message: '{{Erreur ! Les champs #Nom du colis# et #Numéro de suivi# ne peuvent pas être vides}}', level: 'danger'});
			return;
		}
			
		$.ajax({
			type: "POST",
			url: "plugins/parcelTracking/core/ajax/parcelTracking.ajax.php", 
			data: {
				action: "addParcel",
				name: name,
				trackingId: trackingId,
				carrier: carrier,
				param: param
			},
			dataType: 'json',
			error: function (request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function (data) { 			
				if (data.state != 'ok') {
					$('#div_alert').showAlert({message: '{{Erreur lors la création du colis}}', level: 'danger'});
					return;
				}
				else  {
					$('#div_alert').showAlert({message: '{{Colis créé}}', level: 'success'});
					$('#mod_addParcel').dialog( "close" );
				}
			}
		})		
			
	});
	
</script>
