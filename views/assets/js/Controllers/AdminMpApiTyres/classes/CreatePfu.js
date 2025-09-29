class CreatePfu {
    endpoint = null;
    action = 'createPfuAction';

    constructor(endpoint) {
        this.endpoint = endpoint;
    }

    async create() {
        const self = this;
        if (!confirm("Sei sicuro di voler creare i prodotti PFU?")) {
            return false;
        }

        //Creo l'elemento dialog dal template
        const existDialogElement = document.getElementById("modal-pfu");
        if (existDialogElement) {
            existDialogElement.remove();
        }

        const template = document.getElementById("template-modal-pfu");
        const fragment = template.content.cloneNode(true);
        document.body.appendChild(fragment);
        
        const dialogElement = document.getElementById("modal-pfu");
        dialogElement.showModal();

        const totalProducts = document.getElementById("pfu-total-products");
        const priceList = document.getElementById("pfu-price-list");
        priceList.addEventListener("input", () =>{
            console.log("input - pfu-price-list");
            const values = priceList.value;
            const lines = values.split("\n");
        
            totalProducts.value = lines.length;
        });

        return;

        // Creiamo un nuovo AbortController per questa operazione
        const abortController = new AbortController();

        await showModalDialog({
            title: "Creazione prodotti PFU",
            message: "In corso la creazione dei prodotti PFU",
            style: "info",
            spinner: true,
            abortController: abortController, // Passiamo l'AbortController al dialog
        });
        await new Promise((resolve) => setTimeout(resolve, 500));

        let isFirstLoop = true;

        while (true) {
            try {
                // Se è il primo ciclo, possiamo fare qualcosa di specifico
                let url = isFirstLoop ? `${self.endpoint}?action=${self.action}&reset=1` : `${self.endpoint}?action=${self.action}`;
                isFirstLoop = false;

                const response = await fetch(url, { signal: abortController.signal });
                const data = await response.json();

                console.clear();
                console.log(data);

                if (data.status == "DONE") {
                    await showModalDialog({
                        title: "Creazione prodotti PFU",
                        message: "Prodotti PFU creati con successo",
                        style: "success",
                        spinner: false,
                    });
                    break;
                } else {
                    await showModalDialog({
                        title: "Creazione prodotti PFU",
                        message: "In corso la creazione dei prodotti PFU",
                        style: "info",
                        spinner: true,
                        response: data,
                        abortController: abortController,
                    });
                    //Attendo mezzo secondo
                    await new Promise((resolve) => setTimeout(resolve, 500));
                }
            } catch (error) {
                // Verifica se l'errore è dovuto all'interruzione dell'utente
                if (error.name === "AbortError") {
                    console.log("Operazione interrotta dall'utente");
                    await showModalDialog({
                        title: "Operazione interrotta",
                        message: "La creazione dei prodotti PFU è stata interrotta",
                        style: "warning",
                        spinner: false,
                    });
                } else {
                    console.error("Errore durante la creazione dei prodotti PFU:", error);
                    await showModalDialog({
                        title: "Errore",
                        message: `Si è verificato un errore: ${error.message}`,
                        style: "error",
                        spinner: false,
                    });
                }
                break;
            }
        }
    }
}

