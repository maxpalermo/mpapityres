class fetchTyresDialog {
    constructor() {
        const abortController = new AbortController();
        this.abortController = abortController;
        this.dialog = this.addDialog();
        this.dialogTitle = this.dialog.querySelector(".dialog-title");
        this.dialogBody = this.dialog.querySelector(".dialog-body");
        this.closeHeaderBtn = this.dialog.querySelector(".dialog-header .close-dialog-btn");
        this.closeFooterBtn = this.dialog.querySelector(".dialog-footer .close-dialog-btn");
        this.addEventListener();

        // Debug per verificare che gli elementi siano trovati correttamente
        console.log("Dialog creato:", this.dialog);
        console.log("Dialog title:", this.dialogTitle);
        console.log("Dialog body:", this.dialogBody);
    }

    removeDialog() {
        const dialog = document.getElementById("fetchTyresDialog");
        if (dialog) {
            dialog.remove();
        }
    }

    addDialog() {
        this.removeDialog();

        const dialog = this.getDialog();
        document.body.appendChild(dialog);
        const dialogElement = document.getElementById("fetchTyresDialog");
        return dialogElement;
    }

    addEventListener() {
        // Memorizza il riferimento a this per i callback
        const self = this;

        // Definisci la funzione di callback che usa self invece di this
        this.closeHandlerCallback = function () {
            console.log("Chiusura dialog");
            self.abortController.abort();
            self.dialog.close();
        };

        //Rimuovi il listener quando la finestra viene chiusa
        this.dialog.removeEventListener("close", this.closeHandlerCallback);
        this.closeHeaderBtn.removeEventListener("click", this.closeHandlerCallback);
        this.closeFooterBtn.removeEventListener("click", this.closeHandlerCallback);

        //Imposta il nuovo listener
        this.dialog.addEventListener("close", this.closeHandlerCallback);
        this.closeHeaderBtn.addEventListener("click", this.closeHandlerCallback);
        this.closeFooterBtn.addEventListener("click", this.closeHandlerCallback);
    }

    getDialog() {
        const dialogHtml = this.getDialogHtml();
        const container = document.createElement("div");
        container.innerHTML = dialogHtml;
        return container.querySelector("dialog");
    }

    getDialogHtml() {
        const dialog = `
            <dialog id="fetchTyresDialog">
                <div class="dialog-header">
                    <h2 class="dialog-title">Titolo del Dialog</h2>
                    <button class="close-btn close-dialog-btn">
                        <span class="material-icons">close</span>
                    </button>
                </div>
                <div class="dialog-body">
                    <p class="dialog-text">Questo è un esempio di dialog moderno che utilizza l'elemento nativo
                        <strong>&lt;dialog&gt;</strong>
                        di HTML5.</p>
                    <p class="dialog-text">Il dialog è diviso in tre sezioni distinte: header, body e footer, come richiesto.</p>
                    <p class="dialog-text">Nell'header è presente il titolo e il pulsante di chiusura con icona "X" di Material Icons.</p>
                    <p class="dialog-text">Il footer contiene il pulsante "CHIUDI" per chiudere il dialog.</p>
                    <p class="dialog-text">Prova a chiudere il dialog cliccando sulla X, sul pulsante CHIUDI, premendo ESC o cliccando fuori dal dialog.</p>
                </div>
                <div class="dialog-footer">
                    <button class="action-btn close-dialog-btn">
                        <span class="material-icons" style="vertical-align: middle; margin-right: 5px;">check</span>
                        CHIUDI
                    </button>
                </div>
            </dialog>
        `;

        return dialog;
    }

    show() {
        this.dialog.showModal();
    }

    hide() {
        this.dialog.close();
    }

    updateDialog(title = null, body = null) {
        if (title) {
            this.dialogTitle.textContent = title;
        }
        if (body) {
            this.dialogBody.innerHTML = body;
        }
    }
}
