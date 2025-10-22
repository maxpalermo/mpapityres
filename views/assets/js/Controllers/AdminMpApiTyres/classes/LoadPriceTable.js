class loadPriceTableClass {
    constructor($tableId) {
        this.table = document.getElementById($tableId);
        this.$table = $(this.table);
        this.data = [];
        this.init();
        this.bind();
    }

    isValidHTML(str) {
        try {
            const parser = new DOMParser();
            const doc = parser.parseFromString(str, "text/html");

            // Controlla se ci sono errori di parsing
            const hasError = doc.querySelector("parsererror");

            // Verifica se contiene elementi HTML validi
            const hasHTML = doc.body.children.length > 0;

            return !hasError && hasHTML;
        } catch (e) {
            return false;
        }
    }

    processHTMLString(str, container = document.body) {
        const self = this;
        // Verifica se è HTML
        if (!self.isValidHTML(str)) {
            // Se non è HTML, tratta come testo semplice
            return document.createTextNode(str);
        }

        // Crea elemento contenitore temporaneo
        const temp = document.createElement("div");
        temp.innerHTML = str;

        // Se c'è un solo elemento diretto, restituiscilo direttamente
        if (temp.children.length === 1) {
            return temp.firstElementChild;
        }

        // Se ci sono elementi multipli, restituisci un DocumentFragment
        const fragment = document.createDocumentFragment();
        while (temp.firstChild) {
            fragment.appendChild(temp.firstChild);
        }

        return fragment;
    }

    async init() {
        const self = this;

        // Inizializza la tabella vuota con paginazione client-side
        this.$table.bootstrapTable({
            pagination: true,
            sidePagination: "client", // Paginazione client-side per dati statici
            search: true,
            showRefresh: true,
            showToggle: true,
            idField: "id",
            pageSize: 250, // Numero molto alto per mostrare tutti gli elementi
            pageList: [10, 25, 50, 100, 250, "All"], // Opzioni disponibili nel dropdown
            data: [], // Inizialmente vuota
            formatAllRows: function () {
                return "10000";
            },
        });

        this.loadPrices();
    }

    async loadPrices() {
        const self = this;
        const data = await doFetchPost(adminControllerURL, { action: "getDiffPriceProductsAction" });
        if (data.rows.length) {
            //Aggiorno la tabella con i dati ricevuti
            self.loadData(data);
        } else {
            self.loadData({
                rows: [],
                total: 0,
                totalNotFiltered: 0,
            });
        }
    }

    bind() {
        const self = this;
        this.$table.on("all.bs.table", (args, name) => {
            switch (name) {
                case "load-success.bs.table":
                    return self.loadSuccess(args);
                default:
                    console.log("EVENT: " + name);
            }
        });

        this.$table.on("click-cell.bs.table", (event, field, $element, rowData) => {
            self.clickCell(event, field, $element, rowData);
        });
    }

    loadSuccess(args) {
        console.log("LOAD SUCCESS", args);
    }

    /**
     * Carica nuovi dati nella tabella
     * @param {Object} data - Oggetto con { rows, total, totalNotFiltered }
     */
    loadData(data) {
        if (!data || !data.rows) {
            console.error("Dati non validi");
            return;
        }

        this.data = data.rows;

        // Metodo 1: Usa load() per caricare i dati
        this.$table.bootstrapTable("load", data.rows);

        console.log(`Caricati ${data.rows.length} elementi nella tabella`);
    }

    /**
     * Svuota la tabella
     */
    clearData() {
        this.data = [];
        this.$table.bootstrapTable("load", []);
    }

    clickCell(event, field, $element, rowData) {
        const htmlElement = this.processHTMLString($element);
        const searchInput = document.querySelector("input.search-input");
        let value = null;
        let searchString = null;

        console.clear();
        console.log("Click on " + field);
        console.log("HTML Element:", htmlElement);

        try {
            switch (field) {
                case "reference":
                    value = searchString = String(htmlElement.textContent).trim();
                    searchString = `${value}`;
                    break;
                case "ean13":
                    value = searchString = String(htmlElement.textContent).trim();
                    searchString = `${value}`;
                    break;
                case "combination":
                    const idProductAttribute = htmlElement.dataset.id_product_attribute;
                    searchString = `${idProductAttribute}`;
                    break;
                default:
                    return 0;
            }

            if (searchInput && searchString) {
                searchInput.value = searchString;
                searchInput.focus();
                searchInput.dispatchEvent(new Event("change"));
            }
        } catch (error) {
            console.log(error);
        }
    }

    /**
     * Applica il ricarico ai prezzi dei prodotti selezionati
     */
    async reloadPrices(rows) {
        if (rows.length == 0) {
            alert("Seleziona almeno un prodotto");
            return false;
        }

        if (!confirm(`Procedere all'aggiornamento di ${rows.length} prodotti?`)) {
            return false;
        }

        const self = this;
        const data = await doFetchPost(adminControllerURL, {
            rows: JSON.stringify(rows),
            action: "reloadPricesAction",
            ajax: 1,
        });

        if (data.success) {
            self.loadPrices();
        }

        if (data.alert) {
            return data.alert;
        }

        return "";
    }

    checkAll() {
        this.$table.bootstrapTable("checkAll");
    }

    getSelection() {
        const selectedRows = this.$table.bootstrapTable("getSelections");

        return selectedRows;
    }

    /**
     * Importa i dati selezionati nel magazzino PrestaShop
     */
    async importTable() {
        // Ottieni le righe selezionate
        const selectedRows = this.$table.bootstrapTable("getSelections");

        if (!selectedRows || selectedRows.length === 0) {
            alert("Seleziona almeno una riga da importare");
            return;
        }

        // Conferma dall'utente
        const confirmMessage = `Sei sicuro di voler importare ${selectedRows.length} prodotti nel magazzino?\n\nQuesta operazione creerà movimenti di magazzino.`;
        if (!confirm(confirmMessage)) {
            console.log("Importazione annullata dall'utente");
            return;
        }

        // Raccogli i dati del documento
        const documentData = {
            document_number: document.getElementById("document_number").value || "",
            document_date: document.getElementById("document_date").value || "",
            document_date_iso: document.getElementById("document_date_iso").value || "",
            id_supplier: document.getElementById("id_supplier").textContent || "0",
            supplier_name: document.getElementById("supplier_name").value || "",
            id_stock_mvt_reason: document.getElementById("id_stock_mvt_reason").textContent || "0",
            stock_mvt_reason: document.getElementById("stock_mvt_reason").value || "",
            id_stock_mvt_alias: document.getElementById("id_stock_mvt_alias").textContent || "0",
        };

        // Prepara i dati da inviare
        const postData = {
            action: "importXml",
            ajax: "1",
            document: JSON.stringify(documentData),
            rows: JSON.stringify(selectedRows),
        };

        try {
            const data = await doFetchPost(adminControllerUrl, postData);

            if (data.success) {
                alert(`Importazione completata con successo!\n\n${data.message || ""}`);

                // Pulisci la tabella dopo l'importazione
                this.clearData();

                // Reset dei campi del form
                document.getElementById("document_number").value = "";
                document.getElementById("document_date").value = "";
                document.getElementById("document_date_iso").value = "";
                document.getElementById("supplier_name").value = "";
                document.getElementById("stock_mvt_reason").value = "";
                document.getElementById("id_supplier").textContent = "--";
                document.getElementById("id_stock_mvt_reason").textContent = "--";
                document.getElementById("id_stock_mvt_alias").textContent = "--";

                // Reset del file input
                const fileInput = document.getElementById("xml_file");
                if (fileInput) {
                    fileInput.value = "";
                    document.getElementById("file-label").textContent = "Nessun file selezionato";
                }

                console.log("Importazione completata:", data);
            } else {
                console.error("Errore nell'importazione:", data);
                alert(data.message || "Errore durante l'importazione");
            }
        } catch (error) {
            console.error("Errore:", error);
            alert("Errore nella comunicazione con il server durante l'importazione");
        }
    }
}

/**
 * Formatter per le immagini nella tabella
 */
function imageFormatter(value, row, index) {
    if (!value) {
        return '<img src="' + baseUrl + 'img/404.gif" class="img-thumbnail" style="max-width: 72px;">';
    }

    //Divido l'id immagine in cifre
    const pathImageArray = String(value).split("").map(Number);
    //Creo il percorso unendo le cifre con /
    const pathImage = "img/p/" + pathImageArray.join("/") + "/" + value + "-small_default.jpg";

    const img = document.createElement("img");
    img.src = baseUrl + pathImage;
    img.className = "img-thumbnail";
    img.style.maxWidth = "72px";

    return img.outerHTML;
}

/**
 * Formatter per i prezzi
 *
 */
function priceFormatter(value, row, index) {
    if (value == 0) {
        return "--";
    }

    return (
        parseFloat(value).toLocaleString("it-IT", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }) + " EUR"
    );
}

/**
 * Formatter per le percentuali
 *
 */
function percFormatter(value, row, index) {
    if (value == 0) {
        return "--";
    }

    return (
        parseFloat(value).toLocaleString("it-IT", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }) + " %"
    );
}
