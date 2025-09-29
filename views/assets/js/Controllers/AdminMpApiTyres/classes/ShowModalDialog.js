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
        showModalDialogSpinner();
    } else {
        hideModalDialogSpinner();
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

function showModalDialogSpinner()
{
    const dialogSpinner = document.querySelector("#modal-dialog .dialog-spinner");
    const spinner = `
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Operazione in corso...</span>
        </div>
    `;
    dialogSpinner.innerHTML = spinner;
    dialogSpinner.style.display = "block";
}

function hideModalDialogSpinner()
{
    const dialogSpinner = document.querySelector("#modal-dialog .dialog-spinner");
    dialogSpinner.innerHTML = "";
    dialogSpinner.style.display = "none";
}

