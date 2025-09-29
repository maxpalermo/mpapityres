async function getApiDownloadProgress(csvPath) {
    const fetchManager = new FetchManager(CronControllerURL, {
        ajax: 1,
        action: "getCsvParseProgressAction",
        csvPath: csvPath,
    });

    return await fetchManager.POST();
}

document.addEventListener("DOMContentLoaded", async () => {
    // Wiring per Download API
    let ApiAbortController = new AbortController();
    let ApiAbortSignal = ApiAbortController.signal;

    try {
        const btnFetchApiJson = document.getElementById("btn-fetch-api-json");
        const btnImportJsonCatalog = document.getElementById("btn-import-json-catalog");
        const btnReloadImages = document.getElementById("btn-reload-images");
        const btnDeleteProducts = document.getElementById("btn-delete-products");
        const btnCreatePfu = document.getElementById("btn-create-pfu");
        const btnDownloadApi = document.getElementById("btn-api-download");
        const btnDownloadStopApi = document.getElementById("btn-api-stop-download");

        const api_progress = document.getElementById("api-progress-bar");
        const api_text = document.getElementById("api-progress-text");

        btnFetchApiJson.addEventListener("click", async () => {
            const fetchApiJson = new FetchApiJson(CronControllerURL);
            await fetchApiJson.fetch();
        });

        btnImportJsonCatalog.addEventListener("click", async () => {
            const importJsonCatalog = new ImportJsonCatalog(CronControllerURL);
            await importJsonCatalog.import();
        });

        btnReloadImages.addEventListener("click", async () => {
            const reloadImages = new ReloadImages(CronControllerURL);
            await reloadImages.reload();
        });

        btnDeleteProducts.addEventListener("click", async () => {
            await deleteProducts();
        });

        btnCreatePfu.addEventListener("click", async () => {
            const createPfu = new CreatePfu(CronControllerURL);
            await createPfu.create();
        });

        btnDownloadApi.addEventListener("click", async () => {
            const self = this;
            if (!confirm("Sei sicuro di voler scaricare il catalogo da Tyre?")) {
                return false;
            }

            // Creiamo un nuovo AbortController per questa operazione
            ApiAbortController = new AbortController();

            api_progress.classList.add("progress-bar-animated");
            api_text.innerHTML = `
                <div class="alert alert-info">
                    <p>In corso il download del catalogo da Tyre</p>
                </div>
            `;

            await new Promise((resolve) => setTimeout(resolve, 500));

            let isFirstLoop = true;

            while (true) {
                try {
                    // Se è il primo ciclo, possiamo fare qualcosa di specifico
                    let url = isFirstLoop ? `${CronControllerURL}?action=getCatalogAction&reset=1&ajax=1` : `${CronControllerURL}?action=getCatalogAction&ajax=1`;
                    isFirstLoop = false;

                    const response = await fetch(url, { signal: ApiAbortController.signal });
                    const data = await response.json();

                    console.clear();
                    console.log(data);

                    if (data.status == "DONE") {
                        api_progress.classList.remove("progress-bar-animated");
                        api_text.innerHTML = `
                            <div class="alert alert-success">
                                <p>File JSON scaricato con successo</p>
                            </div>
                        `;

                        break;
                    } else {
                        api_text.innerHTML = `
                            <div class="alert alert-info">
                                <p>In corso il download del catalogo da Tyre</p>
                                <ul>
                                    <li>Offset: ${data.offset}</li>
                                    <li>Tempo: ${data.time}</li>
                                    <li>Scaricati: ${data.updated}</li>
                                </ul>
                            </div>
                        `;

                        console.log(data);

                        //Attendo mezzo secondo
                        await new Promise((resolve) => setTimeout(resolve, 500));
                    }
                } catch (error) {
                    // Verifica se l'errore è dovuto all'interruzione dell'utente
                    if (error.name === "AbortError") {
                        api_progress.classList.remove("progress-bar-animated");

                        api_text.innerHTML = `
                            <div class="alert alert-warning">
                                <p>Operazione interrotta dall'utente</p>
                            </div>
                        `;
                    } else {
                        api_progress.classList.remove("progress-bar-animated");
                        api_text.innerHTML = `
                            <div class="alert alert-danger">
                                <p>Errore ${error.message} durante il download</p>
                            </div>
                        `;
                    }
                    break;
                }
            }
        });

        btnDownloadStopApi.addEventListener("click", async () => {
            ApiAbortController.abort();
        });
    } catch (error) {
        console.error(error);
    }
});
