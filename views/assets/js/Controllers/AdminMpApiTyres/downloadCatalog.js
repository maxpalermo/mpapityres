class DownloadCatalog {
    constructor(url, offset = 0, limit = 3000, minstock = 4) {
        this.url = url;
        this.offset = offset;
        this.limit = limit;
        this.minstock = minstock;
        this.step = 1;
        this.count = 0;
        this.updated = 0;
        this.dialog = new fetchTyresDialog();
        this.abortController = this.dialog.abortController;
    }

    async doDownloadCatalog() {
        const self = this;
        if (self.offset == 0) {
            self.dialog.updateDialog(
                "Scaricamento in corso...",
                `
                    <p class="dialog-text">Offset: ${self.offset}</p>
                    <p class="dialog-text">Limite di ricerca: ${self.limit}</p>
                    <p class="dialog-text">Passo: ${self.step}</p>
                    <p class="dialog-text">Attendere la fine delle operazioni...</p>
                `
            );
            self.dialog.show();

            const formData = new FormData();
            formData.append("action", "truncate");
            const response = await fetch(self.url, {
                method: "POST",
                body: formData,
                signal: self.abortController.signal,
            });

            const json = await response.json();
            console.clear();
            console.log(`Azione: ${json.action}, Success: ${json.success}, Elapsed: ${json.elapsed}, Time: ${json.time}`)
        }

        // Forza il rendering del DOM prima di continuare
        await new Promise((resolve) => setTimeout(resolve, 10));

        const formData = new FormData();
        formData.append("offset", self.offset);
        formData.append("limit", self.limit);
        formData.append("minstock", self.minstock);
        formData.append("action", "downloadCatalog");

        try {
            const response = await fetch(self.url, {
                method: "POST",
                body: formData,
                signal: self.abortController.signal,
            });

            const json = await response.json();
            const data = json.response || {};

            console.log("DATA RESPONSE", data);

            self.offset = data.offset;
            self.count = data.count;
            self.updated = data.updated;
            self.step++;

            console.log(`self.offset: ${self.offset}`);
            console.log(`self.updated: ${self.updated}`);
            console.log(`self.step: ${self.step}`);
            console.log(`self.count: ${self.count}`);
            console.log(`self.limit: ${self.limit}`);
            console.log(`self.minstock: ${self.minstock}`);

            // Aggiorna il dialog con i nuovi dati
            this.dialog.updateDialog(
                "Scaricamento in corso...",
                `
                    <p class="dialog-text">Trovati: ${self.count} prodotti</p>
                    <p class="dialog-text">Aggiornati: ${self.updated} prodotti</p>
                    <p class="dialog-text">Passo: ${self.step}</p>
                    <p class="dialog-text">Attendere la fine delle operazioni...</p>
                `
            );

            // Forza il rendering del DOM prima di continuare
            await new Promise((resolve) => setTimeout(resolve, 10));

            if (self.count != 0) {
                self.offset += self.count;
                console.log(`Aggiornamento offset: ${self.offset}`);
                // Usa setTimeout per dare tempo al browser di aggiornare l'interfaccia
                setTimeout(async () => {
                    //Continuo con gli altri prodotti
                    self.doDownloadCatalog();
                }, 100);
            } else {
                self.dialog.updateDialog(
                    "Scaricamento completato",
                    `
                        <p class="dialog-text">Operazione completata con successo</p>
                        <p class="dialog-text">Scaricati: ${self.offset} prodotti</p>
                        <p class="dialog-text">Aggiornati: ${self.updated} prodotti</p>
                        <p class="dialog-text">Iterazioni totali: ${self.step}</p>
                    `
                );

                return;
            }
        } catch (error) {
            if (error instanceof DOMException && error.name === "AbortError") {
                return;
            }
            self.dialog.updateDialog(
                "Errore",
                `
                    <p class="dialog-text">Si è verificato un errore: ${error}</p>
                    <p class="dialog-text">Riprova più tardi</p>
                `
            );
            self.dialog.show();
            return false;
        }
    }
}