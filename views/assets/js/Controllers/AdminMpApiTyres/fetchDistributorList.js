class fetchDistributorList {
    constructor(idT24, url = null) {
        if (!url) {
            this.url = AdminControllerUrl;
        } else {
            this.url = url;
        }
        this.idT24 = idT24;
        this.action = "getDistributorsList";
        this.distributors = [];

        console.log(`FETCHDISTRIBUTORLIST: ${this.url}/${this.action}/${this.idT24}`);
    }

    async getDistributorsList(id = null) {
        const self = this;
        if (id) {
            self.idT24 = id;
        }
        const url = self.url;
        const formData = new FormData();
        formData.append("action", self.action);
        formData.append(
            "params",
            JSON.stringify({
                id: self.idT24,
            })
        );

        const response = await fetch(url, {
            method: "POST",
            body: formData,
        });
        const data = await response.json();

        let distributors = JSON.parse(data.response.list) || [];

        console.log(`FOUND ${distributors.length} distributors:`, distributors);
        self.distributors = distributors;
        return distributors;
    }

    newRow(tr) {
        const newTr = document.createElement("tr");
        const tbody = tr.closest("tbody");
        newTr.classList.add("pricelist");
        newTr.innerHTML = `
            <td colspan="8"></td>
        `;
        // Inseriamo la nuova riga dopo la riga corrente
        if (tbody) {
            // Se tr è l'ultimo elemento, nextSibling sarà null e la riga verrà aggiunta alla fine
            tbody.insertBefore(newTr, tr.nextElementSibling);
        } else {
            console.error("Impossibile trovare il tbody per inserire la nuova riga");
        }
        return newTr;
    }

    insertPriceList(tr, distributors = []) {
        const self = this;
        if (distributors) {
            self.distributors = distributors;
        }

        console.log("distributors", self.distributors);

        //Controllo se esiste una riga seguente e se contiene la classe "pricelist"
        const nextTr = tr.nextElementSibling;
        console.log("nextTr", nextTr);

        //Se non c'è una riga seguente o la riga seguente non ha la classe pricelist
        if (!nextTr) {
            // Non c'è una riga seguente, creo una nuova riga
            const newTr = self.newRow(tr);
            self.updateDistributors(newTr, self.idT24);
            return;
        } else if (nextTr.classList.contains("pricelist")) {
            // La riga seguente ha la classe pricelist, aggiorno quella
            self.updateDistributors(nextTr, self.idT24);
            return;
        } else {
            // La riga seguente esiste ma non ha la classe pricelist, creo una nuova riga
            const newTr = self.newRow(tr);
            self.updateDistributors(newTr, self.idT24);
            return;
        }
    }

    async updateDistributors(tr, idT24 = null) {
        const self = this;
        // Verifica che distributors sia un array
        console.log("Update distributors:", self.distributors);

        if (!Array.isArray(self.distributors)) {
            // Crea una cella con un messaggio di errore
            const td = tr.querySelector("td");
            if (td) {
                td.innerHTML = '<div class="alert alert-danger">Nessun distributore disponibile</div>';
            }
            return;
        }

        // Se è un array vuoto
        if (self.distributors.length === 0) {
            const td = tr.querySelector("td");
            if (td) {
                td.innerHTML = '<div class="alert alert-warning">Nessun distributore disponibile</div>';
            }
            return;
        }

        const table = document.createElement("table");
        table.classList.add("table");
        table.classList.add("table-striped");
        table.classList.add("table-bordered");
        table.classList.add("table-hover");
        table.classList.add("table-sm");
        table.classList.add("table-responsive");

        if (self.distributors.length === 0) {
            table.innerHTML = `
                <thead>
                    <tr>
                        <th colspan="4" class="text-center">
                            <div class="alert alert-warning">
                                Nessun distributore disponibile
                            </div>
                        </th>
                    </tr>
                </thead>
            `;
        } else {
            // Prepariamo i dati dei distributori
            const distributorRows = [];
            self.distributors.forEach((distributor) => {
                const priceList = distributor.priceList[0];
                const priceLists = distributor.priceList;
                Object.entries(priceLists).forEach(([key, value]) => {
                    distributorRows.push({
                        idT24: self.idT24,
                        distributorId: distributor.distributorId,
                        name: distributor.name,
                        country: distributor.country,
                        country_code: distributor.country_code || "",
                        type: value.type,
                        minOrder: value.min_order,
                        priceUnit: value.value,
                        deliveryTime: distributor.shippingCosts.shipping_standard.estimatedDelivery || null,
                        stock: distributor.stock || 0,
                        active: 1,
                    });
                });
            });

            //Generazione tabella
            const tableHtml = `
                <thead>
                    <tr>
                        <th>id</th>
                        <th>Paese</th>
                        <th>Nome</th>
                        <th>TipoListino</th>
                        <th>Ordine minimo</th>
                        <th>Prezzo unitario</th>
                        <th>Consegna</th>
                        <th>In stock</th>
                    </tr>
                </thead>
                <tbody>
                    ${distributorRows.map(distributor => `
                        <tr>
                            <td>${distributor.distributorId}</td>
                            <td>${distributor.country}</td>
                            <td>${distributor.name}</td>
                            <td>${distributor.type}</td>
                            <td>${distributor.minOrder}</td>
                            <td class="text-right">${distributor.priceUnit.toFixed(2)} €</td>
                            <td class="text-center">${distributor.deliveryTime || "--"}</td>
                            <td class="text-right"><strong>${distributor.stock || 0}</strong></td>
                        </tr>
                    `).join('')}
                </tbody>
            `;

            table.innerHTML = tableHtml;
        }

        const td = tr.querySelector("td");
        if (td) {
            td.innerHTML = "";
            td.appendChild(table);

            td.addEventListener("dblclick", async () => {
                //Elimina la riga
                tr.remove();
            });
        } else {
            console.error("Nessuna cella TD trovata nella riga");
        }
    }
}