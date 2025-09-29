class ImportJsonCatalog {
    endpoint = null;
    action="importCatalogAction";

    constructor(endpoint) {
        this.endpoint = endpoint;
    }

    async import() {
        const self= this;
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
            abortController: abortController, // Passiamo l'AbortController al dialog
        });
        await new Promise((resolve) => setTimeout(resolve, 500));

        let isFirstLoop = true;

        while (true) {
            try {
                // Se è il primo ciclo, possiamo fare qualcosa di specifico
                const url = isFirstLoop ? `${self.endpoint}?action=${self.action}&reset=1&ajax=1` : `${self.endpoint}?action=${self.action}&ajax=1`;
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
                        spinner: false,
                    });
                    break;
                } else {
                    await showModalDialog({
                        title: "Importazione catalogo",
                        message: "In corso l'importazione del catalogo",
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
                        message: "L'importazione del catalogo è stata interrotta",
                        style: "warning",
                        spinner: false,
                    });
                } else {
                    console.error("Errore durante l'importazione:", error);
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

