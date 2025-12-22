class TablePfuAssociation {
    adminControllerUrl = null;
    tableName = "table-pfu-association";
    $bsTable = null;

    constructor(adminControllerUrl) {
        this.adminControllerUrl = adminControllerUrl;
        this.$bsTable = $("#" + this.tableName);
    }

    refresh() {
        this.$bsTable.bootstrapTable("refresh");
    }

    createTable() {
        const self = this;
        self.$bsTable.bootstrapTable({
            url: self.adminControllerUrl,
            method: "post",
            contentType: "application/x-www-form-urlencoded",
            clickToSelect: true,
            maintainMetaData: true,
            queryParams: function (params) {
                const searchParams = {
                    ajax: 1,
                    action: "getPfuAssociations",
                    limit: params.limit,
                    offset: params.offset,
                    search: params.search,
                    sort: params.sort == undefined ? "a.id_product" : params.sort,
                    order: params.order == undefined ? "asc" : params.order,
                    filter: params.filter == undefined ? "" : params.filter,
                };

                return searchParams;
            },
            search: false,
            filterControl: true,
            filterControlVisible: true,
            filterControlSearchClear: false,
            showFilterControlSwitch: false,
            searchOnEnterKey: true,
            sortSelectOptions: true,
            serverSort: true,
            sortName: "a.id_product",
            sortOrder: "asc",
            sidePagination: "server",
            pagination: true,
            showRefresh: true,
            showColumns: false,
            striped: true,
            condensed: true,
            pageSize: 25,
            pageList: [10, 25, 50, 100, 250, 500],
            locale: "it-IT",
            classes: "table table-bordered table-hover",
            theadClasses: "thead-light",
            showExport: false,
            toolbar: null,
            uniqueId: "id_product",
            detailView: false, // Imposta a true per avere il dettaglio della riga
            detailFormatter: (_, row) => {
                // Per ora non serve
            },
            onExpandRow: (_, row, $detail) => {
                //Per ora non serve, ma lasciamo il codice per futura implementazione
                //$details è il contenuto da visualizzare
            },
            iconsPrefix: "fa",
            icons: {
                detailOpen: "fa-plus",
                detailClose: "fa-minus",
                sort: "fa-sort",
                sortAsc: "fa-sort-asc",
                sortDesc: "fa-sort-desc",
            },
            onPostBody: function () {
                self.fixDropDownPagination();
                self.bindActionButtons();
                self.bindBulkActions();
            },
            columns: [
                {
                    field: "state",
                    checkbox: true,
                    align: "center",
                    valign: "middle",
                    width: 36,
                },
                {
                    field: "image",
                    title: "Immagine",
                    align: "center",
                    sortable: false,
                    uniqueId: false,
                    width: 60,
                    formatter: function (value, row, index) {
                        return `<img src="${value}" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: contain;" alt="Immagine">`;
                    },
                },
                {
                    field: "id_product",
                    title: "ID",
                    align: "right",
                    sortable: true,
                    uniqueId: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        return value;
                    },
                },
                {
                    field: "reference",
                    title: "Riferimento",
                    align: "left",
                    sortable: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        return value;
                    },
                },
                {
                    field: "product_name",
                    title: "Pneumatico",
                    align: "left",
                    sortable: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        return value;
                    },
                },
                {
                    field: "weight",
                    title: "Peso",
                    align: "right",
                    sortable: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        return Number(row.weight) + " Kg";
                    },
                },
                {
                    field: "width",
                    title: "Larghezza",
                    align: "center",
                    width: 50,
                    sortable: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        return Number(value);
                    },
                },
                {
                    field: "height",
                    title: "Altezza",
                    align: "center",
                    width: 50,
                    sortable: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        return Number(value) + " %";
                    },
                },
                {
                    field: "depth",
                    title: "Diametro",
                    align: "center",
                    width: 50,
                    sortable: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        return Number(value);
                    },
                },
                {
                    field: "price",
                    title: "Prezzo",
                    align: "right",
                    width: 90,
                    sortable: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        const eur = Number(row.price);
                        return new Intl.NumberFormat("it-IT", { style: "currency", currency: "EUR" }).format(eur);
                    },
                },
                {
                    field: "id_pfu_associated",
                    title: "PFU",
                    align: "center",
                    sortable: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        if (value == 0) {
                            return `
                                <div class="alert alert-warning">Nessun PFU associato</div>
                            `;
                        }

                        return row.pfu_name;
                    },
                },
                {
                    field: "action",
                    title: "Azioni",
                    align: "center",
                    width: 50,
                    sortable: false,
                    formatter: function (value, row, index) {
                        return `
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <button type="button" class="btn btn-primary btn-sm btn-pfu-association" name="btn-edit-note" title="Associa PFU" data-id-product="${row.id_product}" data-id-pfu="${row.id_pfu_associated}">
                                    <span class="material-icons">edit</span>
                                </button>
                            </div>
                        `;
                    },
                },
            ],
        });
    }

    fixDropDownPagination() {
        $(".fixed-table-pagination .dropdown-toggle")
            .off("click")
            .on("click", function (e) {
                e.preventDefault();
                e.stopPropagation();
                const $btn = $(this);
                const $menu = $btn.closest(".btn-group").find(".dropdown-menu");

                $(".fixed-table-pagination .dropdown-menu").not($menu).removeClass("show");
                $menu.toggleClass("show");
            });

        // Normalizza il markup del dropdown page-size a Bootstrap 3
        $(".fixed-table-pagination .btn-group.dropdown").each(function () {
            var $group = $(this);
            var $menuDiv = $group.find("> .dropdown-menu");

            if ($menuDiv.length) {
                // Se non è già <ul>, converti
                if ($menuDiv.prop("tagName") !== "UL") {
                    var $ul = $('<ul class="dropdown-menu" role="menu"></ul>');

                    $menuDiv.find("a").each(function () {
                        var $a = $(this);
                        var $li = $("<li></li>");
                        $a.removeClass("dropdown-item"); // classe BS4/5 inutile qui
                        $li.append($a);
                        $ul.append($li);
                    });

                    $menuDiv.replaceWith($ul);
                }
            }

            // Assicura data-toggle (non data-bs-toggle) e inizializza il plugin
            var $btn = $group.find("> .dropdown-toggle");
            if ($btn.attr("data-bs-toggle") === "dropdown") {
                $btn.removeAttr("data-bs-toggle").attr("data-toggle", "dropdown");
            }
            if (typeof $.fn.dropdown === "function") {
                $btn.dropdown();
            }
        });

        $("button[name=filterControlSwitch]").html("<i class='material-icons'>filter_list</i>");

        $(document)
            .off("click.bs-table-page-size")
            .on("click.bs-table-page-size", function () {
                $(".fixed-table-pagination .dropdown-menu").removeClass("show");
            });

        document.querySelectorAll("button[name=refresh] i").forEach((i) => {
            i.setAttribute("class", "material-icons");
            i.innerHTML = "refresh";
        });

        document.querySelectorAll("button[name=clearSearch] i").forEach((i) => {
            i.setAttribute("class", "material-icons");
            i.innerHTML = "clear";
        });
    }

    bindActionButtons() {
        const btnPfuAssociation = document.querySelectorAll(".btn-pfu-association");
        if (btnPfuAssociation) {
            btnPfuAssociation.forEach((btn) => {
                btn.addEventListener("click", (e) => {
                    const idProduct = e.currentTarget.dataset.idProduct;
                    const idPfu = e.currentTarget.dataset.idPfu;

                    window.setPfuDialog(idProduct, idPfu);
                });
            });
        }
    }

    bindBulkActions() {
        const tableEl = document.getElementById(this.tableName);
        if (!tableEl) {
            return;
        }

        let bulkBtn = document.getElementById("btn-pfu-bulk-association");
        if (!bulkBtn) {
            bulkBtn = document.createElement("button");
            bulkBtn.type = "button";
            bulkBtn.id = "btn-pfu-bulk-association";
            bulkBtn.className = "btn btn-primary";
            bulkBtn.innerHTML = "Associa PFU ai selezionati";

            const wrapper = document.createElement("div");
            wrapper.className = "mb-2";
            wrapper.appendChild(bulkBtn);

            tableEl.parentNode.insertBefore(wrapper, tableEl);
        }

        bulkBtn.onclick = () => {
            const rows = this.$bsTable.bootstrapTable("getSelections");
            if (!rows || rows.length === 0) {
                if (window.toast) {
                    window.toast.showToastError("Attenzione", "Seleziona almeno un prodotto");
                }
                return;
            }

            const idProducts = rows.map((r) => Number(r.id_product)).filter((n) => Number.isFinite(n) && n > 0);

            window.setPfuDialog(idProducts, 0);
        };
    }
}
