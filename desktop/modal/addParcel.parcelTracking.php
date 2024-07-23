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

if (!isConnect()) {
    throw new Exception('{{401 - Accès non autorisé}}');
}

$eqLogic = eqLogic::byId(init('eqLogic_id'));

?>

<div class="modal-content">
    
	<div class="row" style="margin-bottom: 5px; width: 450px">
		<div class="col-sm-12">
			<!--<label class="form-label">Nom :</label>-->
			<input id="name" type="text" class="form-control" placeholder="Nom du colis"/>
		</div>
	</div>
					
	<div class="row" style="margin-bottom: 25px; width: 450px">
		<div class="col-sm-12">
			<!--<label class="form-label">Numéro de suivi :</label>-->
			<input id="trackingId" type="text" class="form-control" placeholder="Numéro de suivi du colis"/>
		</div>
	</div>
					 
	<div class="row" style="width: 450px;">
		<div class="col-sm-12">
			<input id="btn_add" type="submit" class="btn btn-primary" style="float: right; width: 125px" value="Ajouter"/>
		</div>
	</div>

</div>
	
<script>
		
	$('#btn_add').click( function(){
			
		var name = $('#name').val();
		var trackingId = $('#trackingId').val();
					
		if ( name == '' || trackingId == '' )  {
			$('#div_alert').showAlert({message: '{{Erreur ! Les champs ne peuvent pas être vides}}', level: 'danger'});
			return;
		}
			
		$.ajax({
			type: "POST",
			url: "plugins/parcelTracking/core/ajax/parcelTracking.ajax.php", 
			data: {
				action: "addParcel",
				name: name,
				trackingId: trackingId,
			},
			dataType: 'json',
			error: function (request, status, error) {
				handleAjaxError(request, status, error);
			},
			success: function (data) { 			
				if (data.state != 'ok') {
					$('#div_alert').showAlert({message: 'Erreur lors la création du colis', level: 'danger'});
					return;
				}
				else  {
					$('#div_alert').showAlert({message: 'Colis créé', level: 'success'});
					$('#mod_addParcel').dialog( "close" );
				}
			}
		})		
			
	});
	
</script>