{include file='./style.css.tpl'}

<div class="bootstrap card">
	<form action="" method="post">
		<div class="card-header">
			<h3 class="card-title d-flex align-items-center">
				<i class="material-icons mr-2">settings</i>
				<span>Impostazioni</span>
			</h3>
		</div>

		<input type="hidden" name="form-action" value="saveSettings">

		<div class="card-body">
			<div class="row">
				<div class="col-6">
                    <fieldset class="fieldset">
						<legend>Automazioni</legend>

						<div class="form-group">
							<label for="cron-download">Cron download Catalogo Tyre</label>
							<div class="input-group col-md-6">
								<input type="text" name="cron-download" id="cron-download" class="form-control clipboard pointer" value="{$cron_download}" readonly>
								<div class="input-group-addon">
									<span class="input-group"><i class="material-icons f-1rem">schedule</i></span>
								</div>
							</div>
							<small class="text-info" style="margin-left: 16rem; margin-top: 0.5rem; display: block;"><code>Fai click sulla casella per copiare il contenuto</code></small>
						</div>

						<div class="form-group">
							<label for="cron-import">Cron importazione Catalogo su Prestashop</label>
							<div class="input-group col-md-6">
								<input type="text" name="cron-import" id="cron-import" class="form-control clipboard pointer" value="{$cron_import}" readonly>
								<div class="input-group-addon">
									<span class="input-group"><i class="material-icons f-1rem">schedule</i></span>
								</div>
							</div>
							<small class="text-info" style="margin-left: 16rem; margin-top: 0.5rem; display: block;"><code>Fai click sulla casella per copiare il contenuto</code></small>
						</div>

                        <div class="form-group">
							<label for="cron-pause">Pausa nuova ricerca</label>
                            <div class="input-group fixed-width-lg">
                                <input type="text" name="cron-pause" id="cron-pause" class="form-control text-right" value="{$cron_pause}">
                                <div class="input-group-addon">
                                    <span class="input-group">minuti</span>
                                </div>
                            </div>
                        </div>

						<script>
							document.querySelectorAll('.clipboard').forEach(input => {
								input.addEventListener('click', () => {
									input.select();
									document.execCommand('copy');
									alert('Copiato: ' + input.value);
								});
							});
						</script>

					</fieldset>

                    <fieldset class="fieldset">
						<legend>Impostazioni API</legend>

						<div class="form-group">
							<label for="host-api">Host API</label>
							<div class="input-group col-md-6">
								<div class="input-group-addon">
									<span class="input-group"><i class="material-icons f-1rem">dns</i></span>
								</div>
								<input type="text" name="host-api" id="host-api" class="form-control" value="{$host_api}">
							</div>
						</div>

						<div class="form-group">
							<label for="token-api">Token API</label>
							<div class="input-group col-md-6">
								<div class="input-group-addon">
									<span class="input-group"><i class="material-icons f-1rem">vpn_key</i></span>
								</div>
								<textarea name="token-api" id="token-api" class="form-control" rows="5">{$token_api}</textarea>
							</div>
						</div>

					</fieldset>

					<fieldset class="fieldset">
						<legend>Filtri di ricerca</legend>

						<div class="form-group" data-filter-id="0">
							<label for="filter-0">Tipo di pneumatico</label>
							<div class="select-wrapper">
								<select id="filter-0" name="filter-0[]" class="form-control select2" multiple>
									<option value="all" {if $filters['filter-0'] and in_array('all', $filters['filter-0'])} selected {/if}>Tutti</option>
									<option value="E" {if $filters['filter-0'] and in_array('e', $filters['filter-0'])} selected {/if}>Estivi</option>
									<option value="I" {if $filters['filter-0'] and in_array('i', $filters['filter-0'])} selected {/if}>Invernali</option>
									<option value="Q" {if $filters['filter-0'] and in_array('q', $filters['filter-0'])} selected {/if}>pneumatici 4 stagioni</option>
									<option value="O" {if $filters['filter-0'] and in_array('o', $filters['filter-0'])} selected {/if}>Pneumatici offroad</option>
									<option value="R" {if $filters['filter-0'] and in_array('r', $filters['filter-0'])} selected {/if}>Pneumatici con scanalature</option>
									<option value="M" {if $filters['filter-0'] and in_array('m', $filters['filter-0'])} selected {/if}>Pneumatici Moto</option>
								</select>
							</div>
							<div>
								<code class="text-info ml-2">Seleziona uno o più filtri</code>
							</div>
						</div>

						<div class="form-group" data-filter-id="1">
							<label for="filter-1">Produttori</label>
							<div class="select-wrapper">
								<select id="filter-1" name="filter-1[]" class="form-control select2" multiple>
									<option value="all" {if $filters['filter-1'] and in_array('all', $filters['filter-1'])} selected {/if}>Tutti</option>
									{foreach $manufacturers as $manufacturer}
									<option value="{$manufacturer.id_manufacturer}" {if $filters['filter-1'] and in_array($manufacturer.id_manufacturer, $filters['filter-1'])} selected {/if}>{$manufacturer.name}</option>
									{/foreach}
								</select>
							</div>
							<div>
								<code class="text-info ml-2">Seleziona uno o più filtri</code>
							</div>
						</div>

						<div class="form-group" data-filter-id="2">
							<label for="filter-2">Categorie di pneumatico</label>
							<div class="select-wrapper">
								<select id="filter-2" name="filter-2" class="form-control select2">
									<option value="all" {if $filters['filter-2'] and $filters['filter-2'][0]=='all' } selected {/if}>Tutti</option>
									<option value="high" {if $filters['filter-2'] and $filters['filter-2'][0]=='high' } selected {/if}>Marche premium</option>
									<option value="middle" {if $filters['filter-2'] and $filters['filter-2'][0]=='middle' } selected {/if}>Marche di qualità</option>
									<option value="low" {if $filters['filter-2'] and $filters['filter-2'][0]=='low' } selected {/if}>Prodotti di qualità economici</option>
									<option value="recommendation" {if $filters['filter-2'] and $filters['filter-2'][0]=='recommendation' } selected {/if}>La nostra raccomandazione</option>
								</select>
							</div>
						</div>

						<div class="form-group" data-filter-id="4">
							<label for="filter-4">Velocità massima</label>
							<div class="select-wrapper">
								<select id="filter-4" name="filter-4" class="form-control select2">
									<option value="all" {if $filters['filter-4'] and $filters['filter-4']=='all' } selected {/if}>Tutti</option>
									<option value="t" {if $filters['filter-4'] and $filters['filter-4']=='t' } selected {/if}>T fino a 190 km/h</option>
									<option value="h" {if $filters['filter-4'] and $filters['filter-4']=='h' } selected {/if}>H fino a 210 km/h</option>
									<option value="v" {if $filters['filter-4'] and $filters['filter-4']=='v' } selected {/if}>V fino a 240 km/h</option>
									<option value="z" {if $filters['filter-4'] and $filters['filter-4']=='z' } selected {/if}>ZR, Y, W</option>
								</select>
							</div>
						</div>

						<div class="form-group" data-filter-id="5">
							<label for="filter-5">Tipo di ricerca</label>
							<div class="select-wrapper">
								<select id="filter-5" name="filter-5[]" class="form-control select2" multiple>
									<option value="all" {if $filters['filter-5'] and in_array('all', $filters['filter-5'])} selected {/if}>Tutti</option>
									<option value="runflat" {if $filters['filter-5'] and in_array('runflat', $filters['filter-5'])} selected {/if}>Pneumatici Runflat</option>
									<option value="hideDot" {if $filters['filter-5'] and in_array('hideDot', $filters['filter-5'])} selected {/if}>Nascondi DOT>36 mesi</option>
									<option value="hideDiscontinued" {if $filters['filter-5'] and in_array('hideDiscontinued', $filters['filter-5'])} selected {/if}>Nascondi modelli di fine serie</option>
									<option value="hideDemo" {if $filters['filter-5'] and in_array('hideDemo', $filters['filter-5'])} selected {/if}>Nascondi DEMO</option>
								</select>
							</div>
							<div>
								<code class="text-info ml-2">Seleziona uno o più filtri</code>
							</div>
						</div>

						<div class="form-group" data-filter-id="6">
							<label for="filter-6">Consegna espressa</label>
							<div class="select-wrapper">
								<select id="filter-6" name="filter-6" class="form-control select2">
									<option value="">Seleziona</option>
									<option value="all" {if $filters['filter-6'] and $filters['filter-6']=='all' } selected {/if}>Tutti</option>
									<option value="today" {if $filters['filter-6'] and $filters['filter-6']=='today' } selected {/if}>Consegna espressa oggi</option>
									<option value="tomorrow" {if $filters['filter-6'] and $filters['filter-6']=='tomorrow' } selected {/if}>Consegna espressa domani</option>
								</select>
							</div>
						</div>

					</fieldset>

					<fieldset class="fieldset">
						<legend>Parametri di importazione</legend>

						<div class="form-group">
							<label for="">Seleziona la categoria dei pneumatici</label>
							<div class="select-wrapper">
								<select name="category" id="category" class="form-control select2">
									<option value="">Seleziona</option>
									<option value="home" {if $categoryName=='home' } selected {/if}>Home</option>
									<option value="pneumatici" {if $categoryName=='pneumatici' } selected {/if}>Pneumatici</option>
								</select>
							</div>
						</div>

						<div class="form-group">
							<label for="">Seleziona l'aliquota IVA</label>
							<div class="select-wrapper">
								<select name="id_tax_rules_group" id="id_tax_rules_group" class="form-control select2">
									<option value="">Seleziona</option>
									{foreach $taxRulesGroups as $ruleGroup}
									<option value="{$ruleGroup.id_tax_rules_group}" {if $ruleGroup.id_tax_rules_group==$idTaxRulesGroup} selected {/if}>{$ruleGroup.name}</option>
									{/foreach}
								</select>
							</div>
						</div>

						<div class="form-group">
							<label for="">Ricarico prezzo</label>
							<div class="input-group col-md-6">
								<table class="table table-condensed fixed-width-xxl">
									<thead>
										<tr>
											<th>Classe Veicolo</th>
											<th>Ricarico</th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td>C1</td>
											<td>
												<div class="input-group fixed-width-lg">
													<input type="text" name="ricarico-c1" id="ricarico-c1" class="form-control text-right" value="{if isset($ricarico_c1)}{$ricarico_c1}{else}0{/if}">
													<div class="input-group-addon">
														<span class="input-group">%</span>
													</div>
												</div>
											</td>
										</tr>
										<tr>
											<td>C2</td>
											<td>
												<div class="input-group fixed-width-lg">
													<input type="text" name="ricarico-c2" id="ricarico-c2" class="form-control text-right" value="{if isset($ricarico_c2)}{$ricarico_c2}{else}0{/if}">
													<div class="input-group-addon">
														<span class="input-group">%</span>
													</div>
												</div>
											</td>
										</tr>
										<tr>
											<td>C3</td>
											<td>
												<div class="input-group fixed-width-lg">
													<input type="text" name="ricarico-c3" id="ricarico-c3" class="form-control text-right" value="{if isset($ricarico_c3)}{$ricarico_c3}{else}0{/if}">
													<div class="input-group-addon">
														<span class="input-group">%</span>
													</div>
												</div>
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>

					</fieldset>
				</div>
			</div>
		</div>

		<div class="card-footer d-flex justify-content-center align-items-center">
			<button type="submit" class="btn btn-primary align-items-center">
				<span class="material-icons">save</span>
				<span class="label">Salva</span>
			</button>
		</div>

	</form>
</div>

<script>
	document.addEventListener('DOMContentLoaded', function () {
        $('.select2').select2(
            {
                placeholder: 'Seleziona',
                allowClear: true,
                width: '100%',
                language: 'it',
                theme: 'classic'
            }
        );
    });
</script>
