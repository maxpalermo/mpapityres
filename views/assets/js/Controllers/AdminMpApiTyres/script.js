async function bindPageSettings() {
    const response = await fetch(AdminControllerGetFiltersJsonUrl);
    const data = await response.json();

    const filtersElement = document.getElementById("filters");
    const filtersJson = JSON.parse(data.filters);
    const sortersJson = JSON.parse(data.sorters);
    if (filtersJson) {
        filtersJson.forEach((filter) => {
            const formGroup = document.createElement("div");
            formGroup.classList.add("form-group");

            const label = document.createElement("label");
            label.setAttribute("for", filter.filterId);
            label.textContent = filter.filterName;

            const select = document.createElement("select");
            select.id = filter.filterId;
            select.name = filter.filterName;
            select.classList.add("form-control");
            if (filter.multipleChoice) {
                select.setAttribute("multiple", "multiple");
            }

            filter.filterValueList.forEach((option) => {
                const opt = document.createElement("option");
                opt.value = option.valueId;
                opt.textContent = option.description;
                select.appendChild(opt);
            });

            formGroup.appendChild(label);
            formGroup.appendChild(select);
            filtersElement.appendChild(formGroup);
        });
    }
    if (sortersJson) {
        const formGroup = document.createElement("div");
        formGroup.classList.add("form-group");
        const label = document.createElement("label");
        label.setAttribute("for", "sorter");
        label.textContent = "Ordinatore";

        const select = document.createElement("select");
        select.id = "sorter";
        select.name = "sorter";
        select.classList.add("form-control");

        sortersJson.forEach((sorter) => {
            const opt = document.createElement("option");
            opt.value = sorter.sorterId;
            opt.textContent = sorter.sorterName;
            select.appendChild(opt);
        });

        formGroup.appendChild(label);
        formGroup.appendChild(select);
        filtersElement.appendChild(formGroup);
    }

    document
        .getElementById("wrap-form")
        .querySelectorAll("select")
        .forEach((select) => {
            $(select).select2({
                theme: "classic",
                placeholder: "Seleziona",
                allowClear: true,
                width: "100%",
            });
        });

    const btnCreateFeatures = document.getElementById("btn-create-features");
    if (btnCreateFeatures) {
        btnCreateFeatures.addEventListener("click", async (e) => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            showSystemDialog("Creazione caratteristiche pneumatici", "Creazione in corso. Attendere il completamento...", true);
            const response = await fetch(AdminControllerCreateFeaturesUrl);
            const data = await response.json();
            updateSystemDialogMessage(`
                <div class="alert alert-success" style="max-height: 400px; overflow-y: scroll;">
                    <div class="row">
                        <div class="col-12 title">Creazione completata</div>
                        <div class="col-12">
                            <pre>${JSON.stringify(data.features, null, 2)}</pre>
                        </div>
                    </div>
                </div>
            `);
            hideSpinner();
        });
    } else {
        console.log("btnCreateFeatures not found");
    }
}

function cleanTyreImageUrl(url, width = 0, height = 0) {
    if (width === 0 || height === 0) {
        return url.replace("-%s", "").replace("-%s", "");
    }
    return url.replace("%s", width).replace("%s", height);
}

/**
 * Funzione per gestire la pagina di importazione API
 */
async function doBindPageImportAPI() {
    console.log("doBindPageImportAPI - funzione eseguita dal file principale");

    const SetApiFiltersDIV = document.querySelector("[data-set-api-filters-url]");
    const SetApiFiltersURL = SetApiFiltersDIV ? SetApiFiltersDIV.dataset.setApiFiltersUrl : null;

    const LastTyresDIV = document.querySelector("[data-last-tyres]");
    const LastTyres = LastTyresDIV ? JSON.parse(LastTyresDIV.dataset.lastTyres) : null;

    console.log("LastTyresDIV", LastTyresDIV.dataset.lastTyres || "Nessun dato");
    console.log("LastTyres", LastTyres);

    const getAllSelectedValues = (selectElement) => {
        const selectedValues = [];
        for (let i = 0; i < selectElement.options.length; i++) {
            if (selectElement.options[i].selected) {
                selectedValues.push(selectElement.options[i].value);
            }
        }
        return selectedValues;
    };

    const btnSetApiFilters = document.getElementById("btn-set-api-filters");
    if (btnSetApiFilters) {
        btnSetApiFilters.addEventListener("click", async function() {
            if (!confirm("Sei sicuro di voler impostare i filtri?")) {
                return;
            }

            const formData = new FormData();
            formData.append("filter-0", getAllSelectedValues(document.getElementById("filter-0")));
            formData.append("filter-1", getAllSelectedValues(document.getElementById("filter-1")));
            formData.append("filter-2", getAllSelectedValues(document.getElementById("filter-2")));
            formData.append("filter-4", getAllSelectedValues(document.getElementById("filter-4")));
            formData.append("filter-5", getAllSelectedValues(document.getElementById("filter-5")));
            formData.append("filter-6", getAllSelectedValues(document.getElementById("filter-6")));
            formData.append("action", "saveApiFilters");

            // Then fetch the data
            const response = await fetch(AdminControllerImportCatalogFromTableUrl, {
                method: "POST",
                body: formData,
            });

            const data = await response.json();

            if (data.success) {
                fetchTyresDialogTitle.textContent = "Successo";
                fetchTyresDialogBody.innerHTML = `
                    <div class="alert alert-success">
                        <p class="dialog-text">Filtri impostati con successo</p>
                        <p class="dialog-text">Adesso puoi iniziare la ricerca dei Prodotti Tyre</p>
                    </div>
                `;
                fetchTyresDialog.showModal();
            } else {
                fetchTyresDialogTitle.textContent = "Errore";
                fetchTyresDialogBody.innerHTML = `
                    <div class="alert alert-danger">
                        <p class="dialog-text">Si è verificato un errore</p>
                        <p class="dialog-text">Riprova più tardi</p>
                    </div>
                `;
                fetchTyresDialog.showModal();
            }
        });
    }

    const btnSaveApiSettings = document.getElementById("btn-save-api-settings");
    if (btnSaveApiSettings) {
        btnSaveApiSettings.addEventListener("click", async function() {
            if (!confirm("Sei sicuro di voler salvare le impostazioni?")) {
                return;
            }

            const formData = new FormData();
            formData.append("action", "saveApiSettings");
            formData.append("category", document.getElementById("category").value);
            formData.append("id_tax_rules_group", document.getElementById("id_tax_rules_group").value);

            // Then fetch the data
            const response = await fetch(AdminControllerImportCatalogFromTableUrl, {
                method: "POST",
                body: formData,
            });

            const data = await response.json();

            if (data.success) {
                fetchTyresDialogTitle.textContent = "Successo";
                fetchTyresDialogBody.innerHTML = `
                    <div class="alert alert-success">
                        <p class="dialog-text">Impostazioni salvate con successo</p>
                        <p class="dialog-text">Adesso puoi iniziare la ricerca dei Prodotti Tyre</p>
                    </div>
                `;
                fetchTyresDialog.showModal();
            } else {
                fetchTyresDialogTitle.textContent = "Errore";
                fetchTyresDialogBody.innerHTML = `
                    <div class="alert alert-danger">
                        <p class="dialog-text">Si è verificato un errore</p>
                        <p class="dialog-text">Riprova più tardi</p>
                    </div>
                `;
                fetchTyresDialog.showModal();
            }
        });
    }

    if (LastTyres) {
        fillLast50JsonTyres(LastTyres);
    }

    const fetchTyresDialog = document.getElementById("fetchTyresDialog");
    if (!fetchTyresDialog) {
        console.error("Dialog fetch tyres non trovato");
        return;
    }
    const fetchTyresDialogTitle = fetchTyresDialog.querySelector(".dialog-title");
    const fetchTyresDialogBody = fetchTyresDialog.querySelector(".dialog-body");
    const fetchTyresDialogFooter = fetchTyresDialog.querySelector(".dialog-footer");

    if (!fetchTyresDialog || !fetchTyresDialogTitle || !fetchTyresDialogBody || !fetchTyresDialogFooter) {
        console.error("Elementi dialog non trovati");
        return;
    }

    const btnFetchTyres = document.getElementById("btn-fetch-tyres-api");
    if (btnFetchTyres) {
        btnFetchTyres.addEventListener("click", async function() {
            if (!confirm("Sei sicuro di voler scaricare il catalogo dei pneumatici?")) {
                return;
            }

            const downloadCatalog = new DownloadCatalog(AdminControllerDownloadCatalogApiUrl);
            const response = await downloadCatalog.doDownloadCatalog();

            console.log(response);
        });
    } else {
        console.error("Bottone fetch tyres non trovato");
    }

    const btnCreatePrestashopCatalog = document.getElementById("btn-create-prestashop-catalog");
    if (btnCreatePrestashopCatalog) {
        btnCreatePrestashopCatalog.addEventListener("click", async function() {
            if (!confirm("Sei sicuro di voler creare il catalogo?")) {
                return;
            }

            const offset = 0;
            const limit = 100;

            const importCatalog = new ImportCatalog(offset, limit);
            await importCatalog.doImportCatalog();
        });
    }
}

async function fetchDistributors() {
    try {
        fetchTyresDialogTitle.textContent = "Operazione in corso";
        fetchTyresDialogBody.innerHTML = `
                <p class="dialog-text">Lettura dei distributori</p>
                <p class="dialog-text">Attendere il completamento...</p>
            `;
        fetchTyresDialog.showModal();

        const response = await fetch(FetchJsonDistributorsURL);
        const data = await response.json();
        console.log(data);

        fetchTyresDialogTitle.textContent = "Operazione completata";
        fetchTyresDialogBody.innerHTML = `
                <p class="dialog-text">Operazione completata con successo</p>
            `;
        fetchTyresDialog.showModal();

        return data;
    } catch (error) {
        console.error("Errore nel recupero dei distributori:", error);
        return null;
    }
}

function fillLast50JsonTyres(lastProducts) {
    const last50JsonTyres = document.getElementById("last50JsonTyres");
    const tbody = last50JsonTyres.querySelector("tbody");
    tbody.innerHTML = "";
    if (lastProducts.length > 0) {
        lastProducts.forEach((product) => {
            const tr = document.createElement("tr");
            tr.setAttribute("data-id", product.idT24);
            tr.classList.add("clickable-row");
            tr.classList.add("pointer");
            let imageUrl = cleanTyreImageUrl(product.imageURL);
            tr.innerHTML = `
                <td>
                    <img src="${imageUrl}" alt="${product.description}" width="50" class="img-fluid">
                </td>
                <td>${product.idT24}</td>
                <td>${product.matchcode}</td>
                <td>${product.ean}</td>
                <td>
                    <img src="${product.manufacturerImage}" alt="${product.manufacturerName}" width="150" class="img-fluid">
                </td>
                <td>${product.description}</td>
                <td>${product.quantity}</td>
                <td>--</td>
            `;
            tbody.appendChild(tr);

            tr.addEventListener("click", async () => {
                const id = tr.getAttribute("data-id");

                try {
                    const fetchDistributorListHandler = new fetchDistributorList(id);
                    const distributors = await fetchDistributorListHandler.getDistributorsList(id);
                    console.log(`Return distributors: ${distributors.length}`);
                    //aggiungi una riga sotto la riga corrente a meno che la riga sotto non sia già un tr con classe "pricelist"
                    fetchDistributorListHandler.insertPriceList(tr, distributors);
                } catch (error) {
                    console.error("Errore nel recupero del listino prezzi:", error);
                }
            });
        });
    }
}

/**
 * Funzione che viene chiamata quando si carica la pagina di importazione API
 */
async function bindPageImportAPI() {
    console.log("bindPageImportAPI");
    const select2Elements = document.querySelectorAll(".select2");
    select2Elements.forEach((select2Element) => {
        $(select2Element).select2({
            placeholder: "Seleziona",
            allowClear: true,
            width: "100%",
            theme: "bootstrap-5",
        });
    });

    await doBindPageImportAPI();
}

async function bindPageImportCSV() {
    const CHUNK_SIZE = 50;

    const btnDownloadCatalog = document.querySelector(".btn-download-catalog");
    btnDownloadCatalog.addEventListener("click", async (e) => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        if (!confirm("Scaricare direttamente dal sito?\n \nSe si, verrà scaricato il catalogo e verrà importato in automatico")) {
            return;
        }

        showSystemDialog("Scaricamento catalogo", "Download in corso. Attendere il completamento...", true);

        try {
            const response = await fetch(AdminControllerDownloadCatalogUrl);
            const data = await response.json();

            updateSystemDialogMessage(`Catalogo in CSV scaricato in ${data.time}.`);

            const response2 = await fetch(AdminControllerChunkCsvToJsonUrl);
            const data2 = await response2.json();

            updateSystemDialogMessage(`<h1>Operazione completata.</h1><p>Creati ${data2.chunks_created} chunk, in ${data2.time}, Totale righe: ${data2.total_rows}</p>`);
            updateProgressBar(100);
            $("#systemProgressDialogWaiting").hide();
        } catch (error) {
            console.error(error);
            updateSystemDialogMessage(error);
        }
    });

    const btnReadJson = document.querySelectorAll(".btn-read-json");
    btnReadJson.forEach((btn) => {
        btn.addEventListener("click", async (e) => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            const file = e.currentTarget.getAttribute("data-file");
            const response = await fetch(AdminControllerReadJsonUrl + "&file=" + file);
            const data = await response.json();
            alert(data.query);
        });
    });

    const btnImportJson = document.querySelectorAll(".btn-import-json");
    btnImportJson.forEach((btn) => {
        btn.addEventListener("click", async (e) => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            const file = e.currentTarget.getAttribute("data-file");
            const response = await fetch(AdminControllerImportJsonUrl + "&file=" + file);
            const data = await response.json();
            alert(data.query);
        });
    });

    const btnImportChunks = document.getElementById("btn-import-chunks");
    btnImportChunks.addEventListener("click", async (e) => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        if (!confirm("Sei sicuro di voler importare tutti i chunk?")) {
            return;
        }
        //Start timer
        const start = Date.now();

        showSystemDialog("Importazione in corso", "Stiamo importando i chunk. Attendere il completamento...");
        //Leggo la directory CSV e mi faccio restituire l'elenco dei file
        const response = await fetch(AdminControllerImportChunksUrl);
        const data = await response.json();
        const filePaths = data.filePaths;
        updateSystemDialogMessage(`Trovati ${filePaths.length} file`);
        updateProgressBar(0);
        //Per ogni file leggo il JSON contenuto e restituisco un array di QUERY INSERT

        // Refactoring asincrono con Promise.all
        let allPromises = [];
        let totalImportedRows = 0;

        for (const filePath of filePaths) {
            const formData = new FormData();
            const fileName = filePath.split("/").pop();
            formData.append("file", filePath);
            const response = await fetch(AdminControllerImportFromChunkUrl, {
                method: "POST",
                body: formData,
            });
            const data = await response.json();
            updateSystemDialogMessage(`
                <div class="alert alert-success">
                    <span>Restituite ${data.queries.length} query dal file <strong>${fileName}</strong></span>
                </div>
            `);

            if (data.queries.length > 0) {
                let step = 1;
                for (const query of data.queries) {
                    const queryFormData = new FormData();
                    queryFormData.append("query", query);
                    const promise = fetch(AdminControllerImportChunkPartUrl, {
                            method: "POST",
                            body: queryFormData,
                        })
                        .then((res) => res.json())
                        .then((dataChunkPart) => {
                            totalImportedRows += dataChunkPart.rows;
                            updateSystemDialogMessage(`
                                <div class="alert alert-success">
                                    <div class="row">
                                        <div class="col-12 title">Passo ${step} di ${data.queries.length}</div>
                                        <div class="col-12">Importata query di ${dataChunkPart.rows} righe dal file <strong>${fileName}</strong></div>
                                        <div class="col-12">Totale righe importate: ${totalImportedRows}</div>
                                    </div>
                                </div>
                            `);
                            updateProgressBar((100 / data.queries.length) * (data.queries.indexOf(query) + 1));
                            step++;
                        });
                    allPromises.push(promise);
                }
            }
        }

        await Promise.all(allPromises);
        updateProgressBar(100);

        //Pulizia righe non necessarie
        updateSystemDialogMessage(`
            <div class="alert alert-warning">
                <div class="row">
                    <div class="col-12 title">Pulizia righe non necessarie in corso...</div>
                </div>
            </div>
        `);

        //aspetta un secondo
        await new Promise((resolve) => setTimeout(resolve, 1000));

        //Pulisco la tabella dai prodotti duplicati
        const response_clean = await fetch(AdminControllerDeleteDuplicateProductsUrl);
        const data_clean = await response_clean.json();

        const end = Date.now();
        const elapsed = end - start;
        const totalTime = (elapsed / 1000).toFixed(1);

        updateSystemDialogMessage(`
            <div class="alert alert-success">
                <div class="row">
                    <div class="col-12 title">Pulizia completata</div>
                    <div class="col-12">Pulizia completata in <strong>${data_clean.time}</strong> secondi</div>
                    <div class="col-12">Totale righe iniziali: <strong>${data_clean.initial_rows}</strong></div>
                    <div class="col-12">Totale righe pulite: <strong>${data_clean.deleted_rows}</strong></div>
                    <div class="col-12">Totale righe rimaste: <strong>${data_clean.remains_rows}</strong></div>
                    <div class="col-12">Importazione completata in <strong>${totalTime}</strong> secondi</div>
                </div>
            </div>
        `);
        hideSpinner();
    });

    const btnUpdateCatalog = document.getElementById("btn-update-catalog");
    btnUpdateCatalog.addEventListener("click", async (e) => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        if (!confirm("Sei sicuro di voler aggiornare il catalogo?")) {
            return;
        }
        showSystemDialog("Aggiornamento catalogo", "Attendere il completamento...");

        const response = await fetch(AdminControllerUpdateCatalogUrl);
        const data = await response.json();
        const list = data.list;

        updateSystemDialogMessage(`
                    <div class="alert alert-success">
                        <div class="row">
                            <div class="col-12 title">Lettura tabella prodotti CSV</div>
                            <div class="col-12">Lettura di ${list.length} prodotti</div>
                        </div>
                    </div>
                `);
        updateProgressBar(0);

        const fraction = 100 / list.length;

        let step = 1;
        const start = Date.now();
        let CHUNK_STEP = 0;
        let progress_row = 0;
        do {
            ProgressDialogAbortController = new AbortController();
            ProgressDialogAbortSignal = ProgressDialogAbortController.signal;

            let chunk = list.splice(0, CHUNK_SIZE);

            CHUNK_STEP += chunk.length;
            const formData = new FormData();
            formData.append("chunk", JSON.stringify(chunk));
            let dataChunkPart;
            try {
                const response = await fetch(AdminControllerUpdateCatalogPartUrl, {
                    method: "POST",
                    body: formData,
                    signal: ProgressDialogAbortSignal,
                });
                dataChunkPart = await response.json();
            } catch (error) {
                if (error.name === "AbortError") {
                    console.log("AbortError");
                    updateSystemDialogMessage(`
                        <div class="alert alert-danger">
                            <div class="row">
                                <div class="col-12 title">Importazione interrotta</div>
                            </div>
                        </div>
                    `);
                    hideSpinner();
                    return false;
                } else {
                    alert("Errore durante l'importazione del catalogo");
                    console.log(error);
                    return false;
                }
            }
            progress_row += dataChunkPart.new_rows + dataChunkPart.updated_rows;
            updateSystemDialogMessage(`
                    <div class="alert alert-success">
                        <div class="row">
                            <div class="col-12 title">Passo ${step}</div>
                            <div class="col-12">Lettura di ${chunk.length} prodotti</div>
                            <div class="col-12">Inseriti ${dataChunkPart.new_rows} prodotti</div>
                            <div class="col-12">Aggiornati ${dataChunkPart.updated_rows} prodotti</div>
                            <div class="col-12">Totale righe aggiornate: ${progress_row}</div>
                        </div>
                    </div>
                `);
            updateProgressBar(Number(fraction * progress_row).toFixed(2));
            step++;
        } while (list.length > 0);

        const updateTyreTables = await fetch(AdminControllerUpdateTyreTablesUrl);
        const dataUpdateTyreTables = await updateTyreTables.json();
        if (dataUpdateTyreTables.error) {
            alert(dataUpdateTyreTables.error);
            return false;
        }

        const end = Date.now();
        const elapsed = end - start;
        const totalTime = (elapsed / 1000).toFixed(1);

        updateSystemDialogMessage(`
            <div class="alert alert-success">
                <div class="row">
                    <div class="col-12 title">Aggiornamento completato</div>
                    <div class="col-12">Aggiornamento completato in <strong>${totalTime}</strong> secondi</div>
                    <div class="col-12">Totale righe lette: <strong>${data.rows}</strong></div>
                    <div class="col-12">Totale righe aggiornate: <strong>${data.updated_rows}</strong></div>
                    <div class="col-12">Totale passaggi: <strong>${step}</strong></div>
                </div>
            </div>
        `);
        hideSpinner();
    });

    const btnDownloadImages = document.getElementById("btn-download-images");
    btnDownloadImages.addEventListener("click", async (e) => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        if (!confirm("Sei sicuro di voler scaricare le immagini?")) {
            return;
        }
        showSystemDialog("Lettura tabella prodotti CSV", "Attendere il completamento...");

        const response = await fetch(AdminControllerUpdateCatalogUrl);
        const data = await response.json();
        const list = data.list;

        updateSystemDialogMessage(
            `
                <div class="alert alert-success">
                    <div class="row">
                        <div class="col-12 title">Lettura tabella prodotti CSV</div>
                        <div class="col-12">Lettura di ${list.length} prodotti</div>
                    </div>
                </div>
                `
        );
        updateProgressBar(0);

        const fraction = 100 / list.length;

        let step = 1;
        const start = Date.now();
        let CHUNK_STEP = 0;
        let progress_row = 0;
        const totalRows = list.length;
        do {
            ProgressDialogAbortController = new AbortController();
            ProgressDialogAbortSignal = ProgressDialogAbortController.signal;

            let chunk = list.splice(0, CHUNK_SIZE);

            CHUNK_STEP += chunk.length;
            const formData = new FormData();
            formData.append("chunk", JSON.stringify(chunk));
            let dataChunkPart;
            try {
                const response = await fetch(AdminControllerDownloadImagesUrl, {
                    method: "POST",
                    body: formData,
                    signal: ProgressDialogAbortSignal,
                });
                dataChunkPart = await response.json();
            } catch (error) {
                if (error.name === "AbortError") {
                    console.log("AbortError");
                    updateSystemDialogMessage(`
                        <div class="alert alert-danger">
                            <div class="row">
                                <div class="col-12 title">Scaricamento immagini interrotto</div>
                            </div>
                        </div>
                    `);
                    hideSpinner();
                    return false;
                } else {
                    alert("Errore durante il scaricamento delle immagini");
                    console.log(error);
                    return false;
                }
            }
            progress_row += dataChunkPart.updated_rows;
            updateSystemDialogMessage(`
                    <div class="alert alert-success">
                        <div class="row">
                            <div class="col-12 title">Passo ${step}</div>
                            <div class="col-12">Lettura di ${chunk.length} prodotti</div>
                            <div class="col-12">Aggiornate ${dataChunkPart.updated_rows} immagini</div>
                            <div class="col-12">Errori di download: ${dataChunkPart.errors}</div>
                            <div class="col-12">Totale righe aggiornate: ${progress_row}</div>
                        </div>
                    </div>
                `);
            updateProgressBar(Number(fraction * progress_row).toFixed(2));
            step++;
        } while (list.length > 0);

        const end = Date.now();
        const elapsed = end - start;
        const totalTime = (elapsed / 1000).toFixed(1);

        updateSystemDialogMessage(`
            <div class="alert alert-success">
                <div class="row">
                    <div class="col-12 title">Aggiornamento completato</div>
                    <div class="col-12">Aggiornamento completato in <strong>${totalTime}</strong> secondi</div>
                    <div class="col-12">Totale righe lette: <strong>${totalRows}</strong></div>
                    <div class="col-12">Totale righe aggiornate: <strong>${progress_row}</strong></div>
                    <div class="col-12">Totale passaggi: <strong>${step}</strong></div>
                </div>
            </div>
        `);
        hideSpinner();
    });

    const btnUpdateTyreTables = document.getElementById("btn-update-tyre-tables");
    btnUpdateTyreTables.addEventListener("click", async (e) => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        if (!confirm("Sei sicuro di voler aggiornare le tabelle?")) {
            return;
        }

        showSystemDialog("Aggiornamento tabelle tyres", "Attendere il completamento...");
        const response = await fetch(AdminControllerUpdateTyreTablesUrl);
        const data = await response.json();
        if (data.error) {
            updateSystemDialogMessage(`
                <div class="alert alert-danger">
                    <div class="row">
                        <div class="col-12 title">Aggiornamento tabelle tyres</div>
                        <div class="col-12">Aggiornamento tabelle tyres completato in <strong>${data.time}</strong></div>
                        <div class="col-12">Errore: <strong>${data.error}</strong></div>
                    </div>
                </div>
            `);
            hideSpinner();
            return false;
        }

        updateSystemDialogMessage(`
            <div class="alert alert-success">
                <div class="row">
                    <div class="col-12 title">Aggiornamento completato</div>
                    <div class="col-12">Aggiornamento completato in <strong>${data.time}</strong></div>
                </div>
            </div>
        `);
        hideSpinner();
    });

    const btnSetSuppliers = document.getElementById("btn-set-suppliers");
    btnSetSuppliers.addEventListener("click", async (e) => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        if (!confirm("Sei sicuro di voler aggiornare i fornitori?")) {
            return;
        }

        showSystemDialog("Impostazione fornitori", "Attendere il completamento...");
        const response = await fetch(AdminControllerSetSuppliersUrl);
        const data = await response.json();
        if (data.error) {
            updateSystemDialogMessage(`
                <div class="alert alert-danger">
                    <div class="row">
                        <div class="col-12 title">Impostazione fornitori</div>
                        <div class="col-12">Aggiornamento tabelle tyres completato in <strong>${data.time}</strong></div>
                        <div class="col-12">Errore: <strong>${data.error}</strong></div>
                    </div>
                </div>
            `);
            hideSpinner();
            return false;
        }

        updateSystemDialogMessage(`
            <div class="alert alert-success">
                <div class="row">
                    <div class="col-12 title">Aggiornamento completato</div>
                    <div class="col-12">Aggiornamento completato in <strong>${data.time}</strong></div>
                    <div class="col-12">Errori: <strong>${data.errors.length}</strong></div>
                </div>
            </div>
        `);
        hideSpinner();
    });

    const btnDownloadApi = document.getElementById("btn-download-api");
    btnDownloadApi.addEventListener("click", async (e) => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        showSystemDialog("Scarica via API", "Attendere il completamento...");
        ProgressDialogAbortController = new AbortController();
        ProgressDialogAbortSignal = ProgressDialogAbortController.signal;

        let offset = 0;
        let limit = 6500;

        do {
            console.log(`DO LOOP START AT OFFSET ${offset}*${limit}`);

            let formData = new FormData();
            formData.append("search", "%");
            formData.append("limit", limit);
            formData.append("offset", offset);
            formData.append("minStock", 8);

            try {
                const response = await fetch(AdminControllerDownloadApiUrl, {
                    method: "POST",
                    body: formData,
                });

                const data = await response.json();
                if (data.error) {
                    updateSystemDialogMessage(`
                        <div class="alert alert-danger">
                            <div class="row">
                                <div class="col-12 title">Scarica via API</div>
                                <div class="col-12">Aggiornamento tabelle tyres completato in <strong>${data.time}</strong></div>
                                <div class="col-12">Errore: <strong>${data.error}</strong></div>
                            </div>
                        </div>
                    `);
                    hideSpinner();
                    return false;
                }

                const tyres_result = JSON.parse(data.result);
                const tyres = tyres_result;
                const stepTotalRows = tyres.length;
                const totalRows = data.totalRows;
                const startTime = Date.now();

                console.log(`Recupero di ${stepTotalRows} prodotti in corso... Inizio offset: ${offset}`);

                let progressRow = 0;
                let step = 1;
                let start = offset * limit;
                let stop = (offset + 1) * limit;

                if (stepTotalRows == 0) {
                    console.log(`Nessun prodotto trovato. Fine download. Offset: ${offset}`);
                    updateSystemDialogMessage(`
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-12 title">Scarica via API</div>
                                <div class="col-12">Aggiornamento tabelle tyres completato in <strong>${data.time}</strong></div>
                                <div class="col-12">Offset di ricerca da: <strong>${start}</strong></div>
                                <div class="col-12">Offset di ricerca a: <strong>${stop}</strong></div>
                                <div class="col-12">Totale prodotti trovati: <strong>${stepTotalRows}</strong></div>
                                <div class="col-12">Totale prodotti salvati: <strong style="color: var(--info);">${totalRows}</strong></div>
                            </div>
                        </div>
                    `);
                    hideSpinner();
                    return true;
                }

                updateSystemDialogMessage(`
                    <div class="alert alert-info">
                        <div class="row">
                            <div class="col-12 title">Scarica via API</div>
                            <div class="col-12">Aggiornamento tabelle tyres in corso ...</div>
                            <div class="col-12">Offset di ricerca da: <strong>${start}</strong></div>
                            <div class="col-12">Offset di ricerca a: <strong>${stop}</strong></div>
                            <div class="col-12">Totale prodotti trovati: <strong>${stepTotalRows}</strong></div>
                            <div class="col-12">Totale prodotti salvati: <strong>${totalRows}</strong></div>
                        </div>
                    </div>
                `);

                const endTime = Date.now();
                const totalTime = (endTime - startTime) / 1000;

                console.log("Uscita ciclo DO-LOOP-WHILE. Aggiornamento completato in " + totalTime + " secondi");

                updateSystemDialogMessage(`
                    <div class="alert alert-success">
                        <div class="row">
                            <div class="col-12 title">Aggiornamento in corso</div>
                            <div class="col-12">Inviati <strong>${progressRow}</strong> prodotti.</div>
                            <div class="col-12">Aggiornamento passo ${step} completato in <strong>${totalTime}</strong> secondi</div>
                        </div>
                    </div>
                `);
            } catch (error) {
                if (error.name === "AbortError") {
                    console.log("AbortError");
                    updateSystemDialogMessage(`
                        <div class="alert alert-danger">
                            <div class="row">
                                <div class="col-12 title">Scarica via API interrotto</div>
                            </div>
                        </div>
                    `);
                    hideSpinner();
                    return false;
                } else {
                    alert("Errore durante lo scaricamento del catalogo");
                    console.log(error);
                    return false;
                }
            }

            offset++;
            console.log(`NEXT OFFSET: ${offset} * ${limit}`);
        } while (true);
    });

    const btnDownloadFile = document.getElementById("btn-download-file");
    btnDownloadFile.addEventListener("click", async (e) => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        if (!confirm("Sei sicuro di voler scaricare il file?")) {
            return;
        }

        showSystemDialog("Scarica file", "Attendere il completamento...");

        // Imposta timeout a 5 minuti (300000 ms)
        const TIMEOUT_MS = 300000;
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), TIMEOUT_MS);

        try {
            const response = await fetch(AdminControllerDownloadFileUrl, {
                method: "POST",
                signal: controller.signal,
            });

            // Cancella il timeout se la richiesta è completata con successo
            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error(`Errore HTTP: ${response.status}`);
            }

            const data = await response.json();
            if (data.error) {
                updateSystemDialogMessage(`
                <div class="alert alert-danger">
                    <div class="row">
                        <div class="col-12 title">Scarica file</div>
                        <div class="col-12">Errore: <strong>${data.error}</strong></div>
                    </div>
                </div>
            `);
                hideSpinner();
                return false;
            }

            const totalRows = data.totalRows;

            updateSystemDialogMessage(`
                <div class="alert alert-success">
                    <div class="row">
                        <div class="col-12 title">Aggiornamento completato</div>
                        <div class="col-12">Aggiornamento completato con successo</div>
                        <div class="col-12">Totale prodotti aggiornati: <strong style="color: var(--info);">${totalRows}</strong></div>
                    </div>
                </div>
            `);
        } catch (error) {
            // Gestisce sia i timeout che altri errori
            let errorMessage = error.name === "AbortError" ? "La richiesta è scaduta per timeout (5 minuti)" : `Errore: ${error.message}`;

            updateSystemDialogMessage(`
            <div class="alert alert-danger">
                <div class="row">
                    <div class="col-12 title">Scarica file</div>
                    <div class="col-12">${errorMessage}</div>
                </div>
            </div>
        `);
        } finally {
            hideSpinner();
        }
    });
}

function stickyTableHeader() {
    document.addEventListener("DOMContentLoaded", function() {
        const table = document.querySelector(".sticky-header");
        if (!table) return;

        // Crea un clone del thead
        const thead = table.querySelector("thead");
        const stickyThead = thead.cloneNode(true);

        // Wrapper sticky
        const stickyWrapper = document.createElement("table");
        stickyWrapper.className = table.className + " sticky-header-clone";
        stickyWrapper.style.position = "fixed";
        stickyWrapper.style.top = "0";
        stickyWrapper.style.left = table.getBoundingClientRect().left + "px";
        stickyWrapper.style.visibility = "hidden";
        stickyWrapper.style.zIndex = 1000;
        stickyWrapper.style.background = "#343a40";
        stickyWrapper.appendChild(stickyThead);
        document.body.appendChild(stickyWrapper);

        function syncWidths() {
            // Allinea larghezza colonne
            const origThs = thead.querySelectorAll("th");
            const cloneThs = stickyThead.querySelectorAll("th");
            stickyWrapper.style.width = table.offsetWidth + "px";
            origThs.forEach((th, i) => {
                cloneThs[i].style.width = th.offsetWidth + "px";
            });
            // Allinea orizzontalmente
            stickyWrapper.style.left = table.getBoundingClientRect().left + "px";
        }

        function onScroll() {
            const rect = table.getBoundingClientRect();
            if (rect.top < 0 && rect.bottom > stickyThead.offsetHeight) {
                stickyWrapper.style.visibility = "visible";
                syncWidths();
            } else {
                stickyWrapper.style.visibility = "hidden";
            }
        }

        window.addEventListener("scroll", onScroll);
        window.addEventListener("resize", syncWidths);
    });
}

async function loadPage(type) {
    console.log(`loading page ${type}`);

    const formData = new FormData();
    formData.append("type", type);
    const response = await fetch(AdminControllerLoadPageUrl, {
        method: "POST",
        body: formData,
    });

    const data = await response.json();
    const container = document.getElementById("page-container");
    const content = JSON.parse(data.page.content);
    container.innerHTML = content;

    if (type == "table") {
        stickyTableHeader();
        bindTrButtons();
        bindPaginator();
    }

    console.log(`page ${type} loaded`);
}

function bindTrButtons() {
    console.log("bindTrButtons");
    const buttons = document.querySelectorAll(".btn-add-product");
    buttons.forEach((button) => {
        button.addEventListener("click", async (e) => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            const href = e.currentTarget.href;
            console.log(href);
            const response = await fetch(href, {
                method: "GET",
            });
            const data = await response.json();
            if (data.success) {
                alert("Prodotto inserito con successo");
            } else {
                alert("Errore durante l'inserimento del prodotto");
            }
            return false;
        });
    });
}

function bindPageViewTable() {
    console.log("bindPageViewTable");
    const table = document.getElementById("table-tyres");
    const rows = table.querySelectorAll("tbody tr");
    rows.forEach((row) => {
        row.addEventListener("click", async (e) => {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            const id = e.currentTarget.closest("tr").getAttribute("data-id");
            console.log(id);
            const formData = new FormData();
            formData.append("id", id);
            formData.append("type", "tyre-detail");
            const response = await fetch(AdminControllerLoadPageUrl, {
                method: "POST",
                body: formData,
            });
            const data = await response.json();
            const container = document.getElementById("page-container");
            const content = JSON.parse(data.page.content);
            container.innerHTML = content;
            bindPageViewTyreDetail();
            return false;
        });
    });
}

function bindPageViewTyreDetail() {
    console.log("bindPageViewTyreDetail");
}

function bindPaginator() {
    console.log("bindPaginator");
    const navs = document.querySelectorAll(".paginator-nav");
    navs.forEach((nav) => {
        const pages = nav.querySelectorAll("li a");
        pages.forEach((page) => {
            page.addEventListener("click", async (e) => {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                const page = e.currentTarget.getAttribute("data-page");
                const limit = e.currentTarget.getAttribute("data-limit");
                const formData = new FormData();
                formData.append("page", page);
                formData.append("limit", limit);
                formData.append("type", "table");
                const response = await fetch(AdminControllerLoadPageUrl, {
                    method: "POST",
                    body: formData,
                });
                const data = await response.json();
                const container = document.getElementById("page-container");
                const content = JSON.parse(data.page.content);
                container.innerHTML = content;
                stickyTableHeader();
                bindPaginator();
                return false;
            });
        });
    });

    const paginatorLimit = document.querySelector(".paginator-limit");
    paginatorLimit.addEventListener("change", async (e) => {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        const limit = e.currentTarget.value;
        const formData = new FormData();
        const page = 1;
        const type = "table";
        formData.append("page", page);
        formData.append("limit", limit);
        formData.append("type", type);
        const response = await fetch(AdminControllerLoadPageUrl, {
            method: "POST",
            body: formData,
        });
        const data = await response.json();
        const container = document.getElementById("page-container");
        const content = JSON.parse(data.page.content);
        container.innerHTML = content;
        stickyTableHeader();
        bindPaginator();
        return false;
    });
}

document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("mpApiToolbar").querySelector("li.active").querySelector("a").click();
});