async function updateApiCatalogProgress(csvPath) {
    const fetchManager = new FetchManager(CronControllerURL, {
        ajax: 1,
        action: "getCsvParseProgressAction",
        csvPath: csvPath,
    });

    return await fetchManager.POST();
}

async function disableProducts() {
    const fetchManager = new FetchManager(CronControllerURL, {
        ajax: 1,
        action: "disableProductsAction",
    });

    const data = await fetchManager.POST();

    const updateProgressText = document.getElementById("update-progress-text");

    updateProgressText.innerHTML = `
        <div class="alert alert-info">
            <p>Disattivati ${data.disabled_products} prodotti nel catalogo</p>
            <p>Disattivati ${data.disabled_products_shop} prodotti nel catalogo dei negozi</p>
            <p>Inizio inserimento prodotti</p>
        </div>
    `;
}

document.addEventListener("DOMContentLoaded", async () => {
    // Wiring per Download API
    let UpdateAbortController = new AbortController();
    let UpdateAbortSignal = UpdateAbortController.signal;

    try {
        const btnApiUpdate = document.getElementById("btn-api-update");
        const btnApiUpdateStop = document.getElementById("btn-api-stop-update");

        const updateProgressBar = document.getElementById("update-progress-bar");
        const updateProgressText = document.getElementById("update-progress-text");

        btnApiUpdate.addEventListener("click", async () => {
            const self = this;
            if (!confirm("Sei sicuro di voler aggiornare il catalogo Prestashop?")) {
                return false;
            }

            // Creiamo un nuovo AbortController per questa operazione
            UpdateAbortController = new AbortController();

            updateProgressBar.classList.add("progress-bar-animated");
            updateProgressText.innerHTML = `
                <div class="alert alert-info">
                    <p>Disattivo i prodotti nel catalogo</p>
                </div>
            `;

            await new Promise((resolve) => setTimeout(resolve, 500));

            await disableProducts();

            let isFirstLoop = true;

            while (true) {
                try {
                    // Se è il primo ciclo, possiamo fare qualcosa di specifico
                    let url = isFirstLoop ? `${CronControllerURL}?action=importCatalogAction&reset=1&ajax=1` : `${CronControllerURL}?action=importCatalogAction&ajax=1`;
                    isFirstLoop = false;

                    const response = await fetch(url, { signal: UpdateAbortController.signal });
                    const data = await response.json();

                    console.clear();
                    console.log(data);

                    if (data.status == "DONE") {
                        updateProgressBar.classList.remove("progress-bar-animated");
                        updateProgressText.innerHTML = `
                            <div class="alert alert-success">
                                <p>Importazione terminata</p>
                                <ul>
                                    <li>Totale prodotti attivi: ${data.active}</li>
                                </ul>
                            </div>
                        `;

                        break;
                    } else {
                        updateProgressText.innerHTML = `
                            <div class="alert alert-info">
                                <p>In corso l'aggiornamento del catalogo Prestashop</p>
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
                        updateProgressBar.classList.remove("progress-bar-animated");

                        updateProgressText.innerHTML = `
                            <div class="alert alert-warning">
                                <p>Operazione interrotta dall'utente</p>
                            </div>
                        `;
                    } else {
                        updateProgressBar.classList.remove("progress-bar-animated");
                        updateProgressText.innerHTML = `
                            <div class="alert alert-danger">
                                <p>Errore ${error.message} durante l'aggiornamento</p>
                            </div>
                        `;
                    }
                    break;
                }
            }
        });

        btnApiUpdateStop.addEventListener("click", async () => {
            UpdateAbortController.abort();
        });
    } catch (error) {
        console.error(error);
    }
});
