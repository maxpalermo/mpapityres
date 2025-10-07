class CreatePfu {
    endpoint = null;
    action = "createPfuAction";

    constructor(endpoint) {
        this.endpoint = endpoint;
    }

    async create() {
        if (!confirm("Sei sicuro di voler creare i prodotti PFU?")) {
            return false;
        }

        const self = this;
        const pfuPriceList = document.getElementById("pfu-price-list");
        const list = pfuPriceList.value;
        //divido il contenuto della textarea in un array di oggetti {start, end, price}
        //divido il contenuto per \n
        const lines = list.split("\n");
        const result = [];
        for (let i = 0; i < lines.length; i++) {
            if (lines[i].trim() == "") {
                continue;
            }
            const line = lines[i];
            const [range, price] = line.split(";");
            const [start, end] = range.split("-");
            result.push({ start, end, price });
        }

        const response = await fetch(self.endpoint, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                ajax: 1,
                action: self.action,
                lines: JSON.stringify(result),
                id_start: document.getElementById("pfu-id-start").value,
                tax_rule_group: document.getElementById("pfu-tax-rule-group").value,
            }),
        });

        const data = await response.json();

        //console.clear();
        console.log(data);

        if (data.status == "DONE") {
            if (data.success) {
                alert("Prodotti PFU creati con successo");
            } else {
                alert("Si sono verificati errori:\n " + data.errors.join("\n"));
            }
        } else {
            if (data.errors) {
                alert("Si sono verificati errori:\n " + data.errors.join("\n"));
            } else {
                alert(data.message);
            }
        }
    }

    async setProductsPfu() {
        if (!confirm("Sei sicuro di voler creare i prodotti PFU?")) {
            return false;
        }

        const self = this;
        const response = await fetch(self.endpoint, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                ajax: 1,
                action: "setProductsToPfuAction",
            }),
        });

        const json = response.json();

        alert`Sono stati associati ${json.result} prodotti`;
    }
}
