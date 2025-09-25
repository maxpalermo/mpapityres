document.addEventListener("DOMContentLoaded", async () => {
    const btnFetchApiJson = document.getElementById("btn-fetch-api-json");
    const btnImportJsonCatalog = document.getElementById("btn-import-json-catalog");
    const btnReloadImages = document.getElementById("btn-reload-images");
    const btnDeleteProducts = document.getElementById("btn-delete-products");
    
    btnFetchApiJson.addEventListener("click", async () => {
        await fetchApiJson();
    });
    
    btnImportJsonCatalog.addEventListener("click", async () => {
        await importJsonCatalog();
    });

    btnReloadImages.addEventListener("click", async () => {
        await reloadImages();
    });

    btnDeleteProducts.addEventListener("click", async () => {
        await deleteProducts();
    });
});

async function fetchApiJson() {
    if (!confirm("Sei sicuro di voler scaricare il catalogo da Tyre?")) {
        return false;
    }

    // Creiamo un nuovo AbortController per questa operazione
    const abortController = new AbortController();
    
    await showModalDialog({
        title: "Scaricamento catalogo",
        message: "In corso il download del catalogo da Tyre",
        style: "info",
        spinner: true,
        abortController: abortController // Passiamo l'AbortController al dialog
    });
    await new Promise((resolve) => setTimeout(resolve, 500));
    
    let isFirstLoop = true;

    while(true) {
        try {
            // Se è il primo ciclo, possiamo fare qualcosa di specifico
            const url = isFirstLoop ? `${DownloadCatalogActionUrl}&reset=1` : DownloadCatalogActionUrl;
            isFirstLoop = false;
            
            const response = await fetch(url, { signal: abortController.signal });
            const data = await response.json();

            console.clear();
            console.log(data);

            if (data.status == "DONE") {
                await showModalDialog({
                    title: "Scaricamento catalogo",
                    message: "File JSON scaricato con successo",
                    style: "success",
                    spinner: false
                });
                break;
            } else {
                await showModalDialog({
                    title: "Scaricamento catalogo",
                    message: "In corso il download del catalogo da Tyre",
                    style: "info",
                    spinner: true,
                    response: data,
                    abortController: abortController
                });
                //Attendo mezzo secondo
                await new Promise((resolve) => setTimeout(resolve, 500));
            }
        } catch (error) {
            // Verifica se l'errore è dovuto all'interruzione dell'utente
            if (error.name === 'AbortError') {
                console.log('Operazione interrotta dall\'utente');
                await showModalDialog({
                    title: "Operazione interrotta",
                    message: "Il download del catalogo è stato interrotto",
                    style: "warning",
                    spinner: false
                });
            } else {
                console.error('Errore durante il download:', error);
                await showModalDialog({
                    title: "Errore",
                    message: `Si è verificato un errore: ${error.message}`,
                    style: "error",
                    spinner: false
                });
            }
            break;
        }
    }
}

async function importJsonCatalog() {
    if (!confirm("Sei sicuro di voler importare il catalogo?")) {
        return false;
    }

    // Creiamo un nuovo AbortController per questa operazione
    const abortController = new AbortController();
    
    await showModalDialog({
        title: "Importazione catalogo",
        message: "In corso l'importazione del catalogo",
        style: "info",
        spinner: true,
        abortController: abortController // Passiamo l'AbortController al dialog
    });
    await new Promise((resolve) => setTimeout(resolve, 500));
    
    let isFirstLoop = true;
    
    while(true) {
        try {
            // Se è il primo ciclo, possiamo fare qualcosa di specifico
            const url = isFirstLoop ? `${ImportCatalogActionUrl}&reset=1` : ImportCatalogActionUrl;
            isFirstLoop = false;
            
            const response = await fetch(url, { signal: abortController.signal });
            const data = await response.json();

            console.clear();
            console.log(data);

            if (data.status == "DONE") {
                await showModalDialog({
                    title: "Importazione catalogo",
                    message: "Catalogo importato con successo",
                    style: "success",
                    spinner: false
                });
                break;
            } else {
                await showModalDialog({
                    title: "Importazione catalogo",
                    message: "In corso l'importazione del catalogo",
                    style: "info",
                    spinner: true,
                    response: data,
                    abortController: abortController
                });
                //Attendo mezzo secondo
                await new Promise((resolve) => setTimeout(resolve, 500));
            }
        } catch (error) {
            // Verifica se l'errore è dovuto all'interruzione dell'utente
            if (error.name === 'AbortError') {
                console.log('Operazione interrotta dall\'utente');
                await showModalDialog({
                    title: "Operazione interrotta",
                    message: "L'importazione del catalogo è stata interrotta",
                    style: "warning",
                    spinner: false
                });
            } else {
                console.error('Errore durante l\'importazione:', error);
                await showModalDialog({
                    title: "Errore",
                    message: `Si è verificato un errore: ${error.message}`,
                    style: "error",
                    spinner: false
                });
            }
            break;
        }
    }
}

// Variabile globale per l'AbortController
let currentAbortController = null;

async function showModalDialog(params) {
    const { title, message, style = "info", spinner = true, response = {}, abortController = null } = params;

    // Se è stato passato un AbortController, lo memorizziamo
    if (abortController) {
        currentAbortController = abortController;
    }

    if (!document.getElementById("modal-dialog")) {
        const dialogTemplate = `
        <dialog id="modal-dialog">
            <div class="dialog-header">
                <h2 class="dialog-title"></h2>
                <button class="close-dialog-btn">&times;</button>
            </div>
            <div class="dialog-body"></div>
            <div class="dialog-response d-flex justify-content-center align-items-center" style="display: none;">
                <code></code>
            </div>
            <div class="dialog-spinner d-flex justify-content-center align-items-center" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Operazione in corso...</span>
                </div>
            </div>
            <div class="dialog-footer">
                <button class="close-dialog-btn">Chiudi</button>
            </div>
        </dialog>
    `;
        
        const dialog = document.createElement("template");
        dialog.innerHTML = dialogTemplate;
        const dialogElement = dialog.content.cloneNode(true);
        document.body.appendChild(dialogElement);
    }

    const dialogElement = document.getElementById("modal-dialog");
    const dialogTitle = dialogElement.querySelector(".dialog-title");
    const dialogBody = dialogElement.querySelector(".dialog-body");
    const dialogResponse = dialogElement.querySelector(".dialog-response");
    const dialogSpinner = dialogElement.querySelector(".dialog-spinner");
    
    // Rimuovi tutte le classi di stile precedenti
    dialogElement.classList.remove("dialog-info", "dialog-success", "dialog-warning", "dialog-error");
    
    // Aggiungi la classe di stile appropriata
    dialogElement.classList.add(`dialog-${style}`);

    if (response) {
        dialogResponse.style.display = "block";
        //Response è un elenco di key:value
        let responseHtml = "";
        for (let key in response) {
            responseHtml += `<div>${key}: ${response[key]}</div>`;
        }
        dialogResponse.innerHTML = `<code>${responseHtml}</code>`;
    }

    if (spinner) {
        dialogSpinner.style.display = "block";
    } else {
        dialogSpinner.style.display = "none";
    }
    
    // Aggiungi icona in base al tipo di messaggio
    let icon = "";
    switch(style) {
        case "success":
            icon = `<span class="material-icons" style="color: #198754; font-size: 2rem; margin-right: 16px;">check_circle</span>`;
            break;
        case "warning":
            icon = `<span class="material-icons" style="color: #ffc107; font-size: 2rem; margin-right: 16px;">warning</span>`;
            break;
        case "error":
            icon = `<span class="material-icons" style="color: #dc3545; font-size: 2rem; margin-right: 16px;">error</span>`;
            break;
        default: // info
            icon = `<span class="material-icons" style="color: #0d6efd; font-size: 2rem; margin-right: 16px;">info</span>`;
            break;
    }
    
    dialogTitle.textContent = title;
    dialogBody.innerHTML = `<div style="display: flex; align-items: center;">${icon}<div>${message}</div></div>`;
    
    // Aggiungi event listener per il pulsante di chiusura
    const closeButtons = dialogElement.querySelectorAll(".close-dialog-btn");
    closeButtons.forEach(button => {
        // Rimuovi tutti i listener precedenti
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
        
        // Aggiungi il nuovo listener
        newButton.addEventListener("click", () => {
            // Se c'è un AbortController attivo, interrompi le operazioni
            if (currentAbortController) {
                try {
                    currentAbortController.abort();
                    console.log('Operazione interrotta dall\'utente');
                } catch (error) {
                    console.error('Errore durante l\'interruzione dell\'operazione:', error);
                }
            }
            dialogElement.close();
        });
    });
    
    dialogElement.showModal();
}

async function reloadImages() {
    if (!confirm("Sei sicuro di voler ricaricare tutte le immagini del catalogo?")) {
        return false;
    }

    // Creiamo un nuovo AbortController per questa operazione
    const abortController = new AbortController();
    
    await showModalDialog({
        title: "Ricarica immagini",
        message: "In corso la ricarica delle immagini del catalogo",
        style: "info",
        spinner: true,
        abortController: abortController // Passiamo l'AbortController al dialog
    });
    await new Promise((resolve) => setTimeout(resolve, 500));
    
    let isFirstLoop = true;
    
    while(true) {
        try {
            // Se è il primo ciclo, possiamo fare qualcosa di specifico
            const url = isFirstLoop ? `${ReloadImagesActionUrl}&reset=1` : ReloadImagesActionUrl;
            isFirstLoop = false;

            const response = await fetch(url, { signal: abortController.signal });
            const data = await response.json();

            console.clear();
            console.log(data);
            
            if (data.status == "DONE") {
                await showModalDialog({
                    title: "Ricarica immagini",
                    message: "Immagini ricaricate con successo",
                    style: "success",
                    spinner: false
                });
                break;
            }

            await showModalDialog({
                title: "Ricarica immagini",
                message: "In corso la ricarica delle immagini del catalogo",
                style: "info",
                spinner: true,
                response: data,
                abortController: abortController
            });
            
            //Attendo mezzo secondo
            await new Promise((resolve) => setTimeout(resolve, 500));
        } catch (error) {
            // Verifica se l'errore è dovuto all'interruzione dell'utente
            if (error.name === 'AbortError') {
                console.log('Operazione interrotta dall\'utente');
                await showModalDialog({
                    title: "Operazione interrotta",
                    message: "La ricarica delle immagini è stata interrotta",
                    style: "warning",
                    spinner: false
                });
            } else {
                console.error('Errore durante la ricarica:', error);
                await showModalDialog({
                    title: "Errore",
                    message: `Si è verificato un errore: ${error.message}`,
                    style: "error",
                    spinner: false
                });
            }
            break;
        }
    }
}

async function deleteProducts() {
    if (!confirm("Sei sicuro di voler eliminare tutti i prodotti tyre?")) {
        return false;
    }

    // Creiamo un nuovo AbortController per questa operazione
    const abortController = new AbortController();
    
    await showModalDialog({
        title: "Eliminazione prodotti",
        message: "In corso l'eliminazione di tutti i prodotti",
        style: "info",
        spinner: true,
        abortController: abortController // Passiamo l'AbortController al dialog
    });
    await new Promise((resolve) => setTimeout(resolve, 500));
    
    let isFirstLoop = true;
    
    while(true) {
        try {
            // Se è il primo ciclo, possiamo fare qualcosa di specifico
            const url = isFirstLoop ? `${DeleteProductsActionUrl}&reset=1` : DeleteProductsActionUrl;
            isFirstLoop = false;
            
            const response = await fetch(url, { signal: abortController.signal });
            const data = await response.json();
            
            console.clear();
            console.log(data);
            
            if (data.status == "DONE") {
                await showModalDialog({
                    title: "Eliminazione prodotti",
                    message: "Prodotti eliminati con successo",
                    style: "success",
                    spinner: false
                });
                break;
            }
            
            await showModalDialog({
                title: "Eliminazione prodotti",
                message: "In corso l'eliminazione di tutti i prodotti",
                style: "info",
                spinner: true,
                response: data,
                abortController: abortController
            });
            
            //Attendo mezzo secondo
            await new Promise((resolve) => setTimeout(resolve, 500));
        } catch (error) {
            // Verifica se l'errore è dovuto all'interruzione dell'utente
            if (error.name === 'AbortError') {
                console.log('Operazione interrotta dall\'utente');
                await showModalDialog({
                    title: "Operazione interrotta",
                    message: "L'eliminazione dei prodotti è stata interrotta",
                    style: "warning",
                    spinner: false
                });
            } else {
                console.error('Errore durante l\'eliminazione:', error);
                await showModalDialog({
                    title: "Errore",
                    message: `Si è verificato un errore: ${error.message}`,
                    style: "error",
                    spinner: false
                });
            }
            break;
        }
    }
}