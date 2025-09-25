class ImportCatalog {
    constructor(offset, limit, url = null) {
        if (!url) {
            this.url = AdminControllerImportCatalogFromTableUrl;
        } else {
            this.url = url;
        }
        this.offset = offset;
        this.limit = limit || 100;
        this.dialog = new fetchTyresDialog();
        this.abortController = this.dialog.abortController;
        this.updated = 0;
        this.rows = [];
        this.step = 0;
    }

    async synchronizeCatalog() {
        const self = this;
        self.dialog.updateDialog(
            "Sincronizzazione in corso...",
            `
                <p class="dialog-text">Attendere la fine delle operazioni...</p>
            `
        );
        self.dialog.show();

        const formData = new FormData();
        formData.append("action", "synchronizeCatalog");
        const response = await fetch(self.url, {
            method: "POST",
            body: formData,
            signal: self.abortController.signal,
        });
        const json = await response.json();
        if (!json.success) {
            self.dialog.updateDialog(
                "Errore",
                `
                    <p class="dialog-text">Si sono verificati errori:</p>
                    <ul>
                        ${json.errors.map((error) => `<li>${error}</li>`).join("")}
                    </ul>
                `
            );
            self.dialog.show();
            return false;
        }

        self.dialog.updateDialog(
            "Sincronizzazione completata",
            `
                <p class="dialog-text">Operazione completata con successo</p>
                <p class="dialog-text">Tempo di esecuzione: ${json.time}</p>
            `
        );
        self.dialog.show();

        return true;
    }

    async refreshDOM() {
        // Forza il rendering del DOM prima di continuare
        await new Promise((resolve) => setTimeout(resolve, 10));
    }

    async doImportCatalog() {
        const self = this;
        if (self.offset == 0) {
            self.updated = 0;
            self.rows = [];
            self.dialog.updateDialog(
                "Importazione in corso...",
                `
                    <p class="dialog-text">Attendere la fine delle operazioni...</p>
                `
            );
            self.dialog.show();

            await self.refreshDOM();
            await self.synchronizeCatalog();
        }

        const formData = new FormData();
        formData.append("action", "importCatalog");
        formData.append("offset", self.offset);
        formData.append("limit", self.limit);

        try {
            const response = await fetch(self.url, {
                method: "POST",
                body: formData,
                signal: self.abortController.signal,
            });
            const json = await response.json();
            if (!json.success) {
                self.dialog.updateDialog(
                    "Errore",
                    `
                        <p class="dialog-text">Si sono verificati errori:</p>
                        <ul>
                            ${json.errors.map((error) => `<li>${error}</li>`).join("")}
                        </ul>
                    `
                );
                self.dialog.show();
                return false;
            }

            const data = json.response;
            self.updated += data.updated;
            self.offset = data.offset;
            self.step++;

            // Aggiorna il dialog con i nuovi dati
            self.dialog.updateDialog(
                "Importazione in corso...",
                `
                    <p class="dialog-text">Trovati: ${self.offset} prodotti</p>
                    <p class="dialog-text">Aggiornati: ${self.updated} prodotti</p>
                    <p class="dialog-text">Tempo di esecuzione: ${data.time}</p>
                    <p class="dialog-text">Passo: ${self.step}</p>
                    <p class="dialog-text">Attendere la fine delle operazioni...</p>
                `
            );

            await self.refreshDOM();

            if (self.offset == 0) {
                self.dialog.updateDialog(
                    "Importazione completata",
                    `
                        <p class="dialog-text">Operazione completata con successo</p>
                    `
                );

                return;
            } else {
                self.doImportCatalog();
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

    async saveManufacturers() {
        const self = this;
        const manufacturers = [];
        self.rows.forEach((row) => {
            const manufacturerName = row.manufacturerName;
            const manufacturerDescription = row.manufacturerDescription;
            const manufacturerImage = row.manufacturerImage;
            const manufacturer = {
                name: manufacturerName,
                description: manufacturerDescription,
                image: manufacturerImage,
            };
            //controllo che non esista un altro produttore con lo stesso nome
            if (!manufacturers.some((m) => m.name === manufacturerName)) {
                manufacturers.push(manufacturer);
            }
        });

        const formData = new FormData();
        formData.append("manufacturers", JSON.stringify(manufacturers));
        formData.append("action", "saveManufacturers");

        const manufacturersResponse = await fetch(self.url, {
            method: "POST",
            body: formData,
        });
        const manufacturersResult = await manufacturersResponse.json();
        if (manufacturersResult.errors.length > 0) {
            self.dialog.updateDialog(
                "Errore",
                `
                    <p class="dialog-text">Si sono verificati errori:</p>
                    <ul>
                        ${manufacturersResult.errors.map((error) => `<li>${error}</li>`).join("")}
                    </ul>
                `
            );
            self.dialog.show();
            return false;
        } else {
            self.dialog.updateDialog(
                "Aggiornamento produttori",
                `
                    <p class="dialog-text">Aggiornati: ${manufacturersResult.updated} di ${manufacturersResult.total}</p>
                `
            );

            await self.refreshDOM();

            return true;
        }
    }

    async saveProducts() {
        const self = this;
        const products = self.rows;
        const total = products.length;
        const formData = new FormData();
        formData.append("products", JSON.stringify(products));
        formData.append("action", "saveProducts");

        await new Promise((resolve) => setTimeout(resolve, 10));

        self.dialog.updateDialog(
            "Salvataggio prodotti",
            `
                <p class="dialog-text">Salvataggio in corso...</p>
                <p class="dialog-text">Processando ${products.length} record</p>
                <p class="dialog-text">Attendere la fine delle operazioni...</p>
            `
        );

        await new Promise((resolve) => setTimeout(resolve, 10));

        const productsResponse = await fetch(self.url, {
            method: "POST",
            body: formData,
        });
        const productsResult = await productsResponse.json();
        if (productsResult.errors.length > 0) {
            self.dialog.updateDialog(
                "Errore",
                `
                    <p class="dialog-text">Si sono verificati errori:</p>
                    <ul>
                        ${productsResult.errors.map((error) => `<li>${error}</li>`).join("")}
                    </ul>
                `
            );
            self.dialog.show();
            return false;
        } else {
            self.dialog.updateDialog(
                "Aggiornamento prodotti",
                `
                    <p class="dialog-text">Aggiornati: ${productsResult.updated} di ${total}</p>
                `
            );

            // Forza il rendering del DOM prima di continuare
            await new Promise((resolve) => setTimeout(resolve, 100));

            return true;
        }
    }
}