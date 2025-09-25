{include file='./style.css.tpl'}

<div class="bootstrap card">
    <div class="card-header">
        <h3 class="card-title">Pagina di importazione</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info">
                    <p>Per importare i dati con API Tyre 1.4, segui questi passaggi:</p>
                    <ol>
                        <li>Imposta i filtri di ricerca</li>
                        <li>Clicca sul pulsante "Scarica e crea file JSON" per scaricare i dati e creare il file JSON.</li>
                        <li>Clicca sul pulsante "Aggiorna catalogo Prestashop" per creare il catalogo.</li>
                    </ol>
                </div>
            </div>
            <div class="col-12">
                <div class="d-flex justify-content-center align-items-center my-4 action-buttons-container">
                    <button type="button" class="btn btn-lg px-5 py-4 shadow rounded-pill action-button" id="btn-fetch-api-json">
                        <span class="material-icons align-middle">cloud_download</span>
                        <span class="button-text">Scarica e crea file JSON</span>
                    </button>
                    <button type="button" class="btn btn-lg px-5 py-4 shadow rounded-pill action-button" id="btn-import-json-catalog">
                        <span class="material-icons align-middle">publish</span>
                        <span class="button-text">Aggiorna catalogo Prestashop</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer"></div>
</div>

<div class="bootstrap card mb-4 summary">
    <div class="card-header d-flex justify-content-center align-items-center">
        <h3 class="card-title mb-0">Riepilogo Dati Catalogo</h3>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-top gap-4">
            <div class="postit">
                <div class="p-3 rounded bg-primary shadow-sm align-items-top">
                    <div class="postit-title">Prodotti totali</div>
                    <div class="fs-2 fw-bold d-flex justify-content-center align-items-top">{if isset($summary.products_count)}{$summary.products_count}{else}-{/if}</div>
                </div>
            </div>
            <div class="postit">
                <div class="p-3 rounded bg-danger shadow-sm align-items-top">
                    <div class="postit-title">Prodotti disattivati</div>
                    <div class="fs-2 fw-bold d-flex justify-content-center align-items-top">{if isset($summary.products_disabled)}{$summary.products_disabled}{else}-{/if}</div>
                </div>
            </div>
            <div class="postit">
                <div class="p-3 rounded bg-success shadow-sm align-items-top">
                    <div class="postit-title">Pneumatici Tyre</div>
                    <div class="fs-2 fw-bold d-flex justify-content-center align-items-top">{if isset($summary.products_tyre_count)}{$summary.products_tyre_count}{else}-{/if}</div>
                </div>
            </div>
            <div class="postit">
                <div class="p-3 rounded bg-warning shadow-sm align-items-top">
                    <div class="postit-title">Produttori</div>
                    <div class="fs-2 fw-bold d-flex justify-content-center align-items-top">{if isset($summary.manufacturers_count)}{$summary.manufacturers_count}{else}-{/if}</div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-top gap-4">
            <div class="postit">
                <div class="p-3 rounded bg-secondary shadow-sm">
                    <div class="postit-title">Caratteristiche</div>
                    <ul class="mb-0 ps-3">
                        {if isset($summary.features) && $summary.features|@count > 0}
                            {foreach from=$summary.features item=feature}
                                <li>{$feature.name}</li>
                            {/foreach}
                        {else}
                            <li>Nessuna caratteristica trovata</li>
                        {/if}
                    </ul>
                </div>
            </div>
            <div class="postit">
                <div class="p-3 rounded bg-info shadow-sm align-items-top">
                    <div class="postit-title">
                        <span>Ultimo CRON</span>
                    </div>
                    <div class="postit-title">
                        <span class="badge bg-light text-info">Download Tyre</span>
                    </div>
                    <div class="fs-5 d-flex justify-content-center align-items-top">{if isset($summary.last_fetch_download) && $summary.last_fetch_download} {$summary.last_fetch_download|date_format:"%d/%m/%Y %H:%M"} {else} Mai eseguito {/if}</div>
                </div>
            </div>
            <div class="postit">
                <div class="p-3 rounded bg-success shadow-sm align-items-top">
                    <div class="postit-title">
                        <span>Ultimo CRON</span>
                    </div>
                    <div class="postit-title">
                        <span class="badge bg-light text-success">Import Catalogo</span>
                    </div>
                    <div class="fs-5 d-flex justify-content-center align-items-top">{if isset($summary.last_fetch_import) && $summary.last_fetch_import} {$summary.last_fetch_import|date_format:"%d/%m/%Y %H:%M"} {else} Mai eseguito {/if}</div>
                </div>
            </div>
            <div class="postit">
                <div class="p-3 rounded bg-dark shadow-sm align-items-top">
                    
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    const DownloadCatalogActionUrl = "{$downloadCatalogActionUrl}";
    const ImportCatalogActionUrl = "{$importCatalogActionUrl}";
</script>

<script src="{$baseUrl}modules/mpapityres/views/assets/js/Controllers/AdminMpApiTyres/mainPage.js"></script>
