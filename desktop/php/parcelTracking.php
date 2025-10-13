<?php

if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}

// Déclaration des variables obligatoires
$plugin = plugin::byId('parcelTracking');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());

?>

<div class="row row-overflow">
	
	<!-- Page d'accueil du plugin -->
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			
			<div class="cursor eqLogicAction logoPrimary" style="color:#3CA5F0" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br>
				<span>{{Ajouter}}</span>
			</div>

			<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>

			<?php
				$jeedomVersion  = jeedom::version() ?? '0';
				$displayInfoValue = version_compare($jeedomVersion, '4.4.0', '>=');
				if ($displayInfoValue) {
					echo '<div class="cursor eqLogicAction warning" data-action="createCommunityPost" title="{{Ouvrir une demande d\'aide sur le forum communautaire}}">';
					echo '<i class="fas fa-ambulance"></i>';
					echo '<span>{{Community}}</span>';
					echo '</div>';
				}
			?>
		</div>

		<legend><i class="fas fa-table"></i> {{Equipements}}</legend>
		<?php
		if (count($eqLogics) == 0) {
			echo '<br><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun équipement Template trouvé, cliquer sur "Ajouter" pour commencer}}</div>';
		} else {
			echo '<div class="input-group" style="margin:5px;">';
			echo '<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic">';
			echo '<div class="input-group-btn">';
			echo '<a id="bt_resetSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>';
			echo '<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>';
			echo '</div>';
			echo '</div>';
			echo '<legend><i class="fas fa-box"></i> {{Mes colis}}</legend>';
			echo '<div class="eqLogicThumbnailContainer">';
			foreach ($eqLogics as $eqLogic) {
				if ($eqLogic->getConfiguration('eqLogicType') == 'parcel' || $eqLogic->getConfiguration('eqLogicType') == '') {
					$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
					echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
					echo '<img src="' . $eqLogic->getImage() . '"/>';
					echo '<br>';
					echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
					echo '<span class="hiddenAsCard displayTableRight hidden">';
					echo '</span>';
					echo '</div>';
				}
			}	
			echo '</div>';
		}
		
		if (count($eqLogics) == 0) {
			echo '<br><div class="text-center" style="font-size:1.2em;font-weight:bold;">{{Aucun équipement Template trouvé, cliquer sur "Ajouter" pour commencer}}</div>';
		} else {
			echo '<legend><i class="fas fa-palette"></i> {{Widget dédié géré par le plugin}}</legend>';
			echo '<div class="eqLogicThumbnailContainer">';
			foreach ($eqLogics as $eqLogic) {
				if ($eqLogic->getConfiguration('eqLogicType') == 'global') {	
					$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
					echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
					echo '<img src="' . $eqLogic->getImage() . '"/>';
					echo '<br>';
					echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
					echo '<span class="hiddenAsCard displayTableRight hidden">';
					echo '</span>';
					echo '</div>';
				}
			}
			echo '</div>';
		}
		?>
	</div>

	<div class="col-xs-12 eqLogic" style="display: none;">
		
		<div class="input-group pull-right" style="display:inline-flex;">
			<span class="input-group-btn">
				<a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
				</a><a class="btn btn-sm btn-default eqLogicAction" data-action="copy"><i class="fas fa-copy"></i><span class="hidden-xs"> {{Dupliquer}}</span>
				</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
				</a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}
				</a>
			</span>
		</div>

		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-list"></i> {{Commandes}}</a></li>
		</ul>

		<div class="tab-content">

			<div role="tabpanel" class="tab-pane active" id="eqlogictab">

				<form class="form-horizontal">
					<fieldset>

						<div class="col-lg-6">
							<legend><i class="fas fa-wrench"></i> {{Paramètres généraux}}</legend>

							<div class="form-group">
								<label class="col-sm-4 control-label">{{Nom de l'équipement}}</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display:none;">
									<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}">
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-4 control-label">{{Objet parent}}</label>
								<div class="col-sm-6">
									<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
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
								<label class="col-sm-4 control-label">{{Catégorie}}</label>
								<div class="col-sm-6">
									<?php
									foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" >' . $value['name'];
										echo '</label>';
									}
									?>
								</div>
							</div>

							<div class="form-group">
								<label class="col-sm-4 control-label">{{Options}}</label>
								<div class="col-sm-6">
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable">{{Activer}}</label>
									<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" style="display:none"></label>
								</div>
							</div>

							<legend id="parcel"><i class="fas fa-cogs"></i> {{Paramètres spécifiques}}</legend>

							<div class="form-group" style="display: none;">
								<label class="col-sm-4 control-label">{{Type équipement}}</label>
								<div class="col-sm-6">
									<select id="hidden_sel_type" class="form-control eqLogicAttr" data-l1key="configuration" data-l2key="eqLogicType">
										<option value="parcel">Parcel</option>
										<option value="global">Global</option>
									</select>
								</div>
							</div>

							<div id="parcel" class="form-group">
								<label class="col-sm-4 control-label">{{Numéro de suivi}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Renseignez le numéro de suivi du colis}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="trackingId">
								</div>
							</div>

							<div id="parcel" class="form-group">
								<label class="col-sm-4 control-label"> {{Transporteur}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Optionnel - Choisissez le transporteur utilisé}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<div class="input-group" style="margin-bottom:0px !important">
										<select id="sel_carrier" class="eqLogicAttr form-control" style="margin: 1px 0px 1px 0px;" data-l1key="configuration" data-l2key="carrier">
											<option value="">{{Aucun}}</option>
											<?php
												$json = file_get_contents('plugins/parcelTracking/data/apicarrier.all.json');
												$carriers = json_decode($json, true);

												$french = array_filter($carriers, function($c) {
													return isset($c['_country_iso']) && strtoupper($c['_country_iso']) === 'FR';
												});
												$others = array_filter($carriers, function($c) {
													return !isset($c['_country_iso']) || strtoupper($c['_country_iso']) !== 'FR';
												});

												usort($french, function($a, $b) {
													return strcasecmp($a['_name'], $b['_name']);
												});
												usort($others, function($a, $b) {
													return strcasecmp($a['_name'], $b['_name']);
												});

												foreach ($french as $carrier) {
													echo '<option value="' . $carrier['key'] . '">' . htmlspecialchars($carrier['_name']) . '</option>';
												}

												if (count($french) > 0 && count($others) > 0) {
													echo '<option disabled>--------------------</option>';
												}

												foreach ($others as $carrier) {
													echo '<option value="' . $carrier['key'] . '">' . htmlspecialchars($carrier['_name']) . '</option>';
												}
											?>
										</select>
										<span class="input-group-btn">
											<a class="btn btn-warning cmdAction" id="bt_updateCarrier" title="{{Mettre à jour le transporteur<br/>ATTENTION ! Un premier enregistrement doit obligatoirement déjà avoir été effectué et réussi !}}"><i class="fa fa-pencil-alt"></i></a>
										</span>
									</div>
									<span id="info" style="font-size: 12px"></span>
								</div>
							</div>

							<div id="parcel" class="form-group">
								<label class="col-sm-4 control-label">{{Paramètre additionnel}}
									<sup><i class="fas fa-question-circle tooltips" title="{{Optionnel - Renseignez le paramètre additionnel demandé par le transporteur (code postal, code pays, ...)}}"></i></sup>
								</label>
								<div class="col-sm-6">
									<div class="input-group" style="margin-bottom:0px !important">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="additionalParameter" placeholder="">
										<span class="input-group-btn">
											<a class="btn btn-warning cmdAction" id="bt_updateInfo" title="{{Mettre à jour le paramètre additionnel<br/>ATTENTION ! Un premier enregistrement doit obligatoirement déjà avoir été effectué et réussi !}}"><i class="fa fa-pencil-alt"></i></a>
										</span>
									</div>
								</div>
							</div>

							<div id="parcel" class="form-group">						
									<label class="col-sm-4 control-label help" data-help="{{L'enregistrement est une étape obligatoire pour récupérer les informations du colis depuis les API 17Track }}">{{Actions}}</label>	
									<div class="col-sm-6">
										<a class="btn btn-primary btn-sm cmdAction" id="bt_register"><i class="fas fa-save"></i> {{Enregistrement}}</a>
									</div>	
							</div>

						</div>

						<div class="col-lg-6">
							
							<legend id="parcel"><i class="fas fa-info"></i> {{Informations}}</legend>
							
							<div id="parcel" class="form-group">
								<label class="col-sm-4 control-label">{{Commentaire}}</label>
								<div class="col-sm-6">
									<textarea class="form-control eqLogicAttr autogrow" data-l1key="comment"></textarea>
								</div>
							</div>

						</div>

					</fieldset>
				</form>
			</div>

			<div role="tabpanel" class="tab-pane" id="commandtab">
				<div class="table-responsive">
					<table id="table_cmd" class="table table-bordered table-condensed">
						<thead>
							<tr>
								<th class="hidden-xs" style="min-width:50px;width:70px;">ID</th>
								<th style="min-width:200px;width:350px;">{{Nom}}</th>
								<th>{{Type}}</th>
								<th>{{Logical ID}}</th>
								<th style="min-width:260px;">{{Options}}</th>
								<th>{{Etat}}</th>
								<th style="min-width:80px;width:200px;">{{Actions}}</th>
							</tr>
						</thead>
						<tbody>
						</tbody>
					</table>
				</div>
			</div>

		</div>

	</div>

</div>

<?php include_file('desktop', 'parcelTracking', 'js', 'parcelTracking'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
