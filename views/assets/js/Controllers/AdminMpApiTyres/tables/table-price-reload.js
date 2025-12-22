class TablePriceReload {
    adminControllerUrl = null;
    tableName = "table-price-reload";
    primary = "a.id_product_price_reload";
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
            queryParams: function (params) {
                const searchParams = {
                    ajax: 1,
                    action: "getPriceReload",
                    limit: params.limit,
                    offset: params.offset,
                    search: params.search,
                    sort: params.sort == undefined ? self.primary : params.sort,
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
            sortName: self.primary,
            sortOrder: "asc",
            sidePagination: "server",
            pagination: true,
            showFooter: true,
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
            uniqueId: self.primary,
            detailView: false, // Imposta a true per avere il dettaglio della riga
            formatLoadingMessage: function () {
                return `
                    <div class="alert alert-info">Caricamento in corso...</div>
                `;
            },
            formatNoMatches: function () {
                return `
                    <div class="alert alert-warning">Nessun record trovato</div>
                `;
            },
            detailFormatter: (_, row) => {
                // Per ora non serve
            },
            onExpandRow: (_, row, $detail) => {
                //Per ora non serve, ma lasciamo il codice per futura implementazione
                //$details è il contenuto da visualizzare
            },
            onPostBody: function () {
                self.fixDropDownPagination();
                self.bindActionButtons();
            },
            iconsPrefix: "fa",
            icons: {
                detailOpen: "fa-plus",
                detailClose: "fa-minus",
                sort: "fa-sort",
                sortAsc: "fa-sort-asc",
                sortDesc: "fa-sort-desc",
            },
            columns: [
                {
                    field: "id_product_price_reload",
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
                    field: "price_min",
                    title: "Prezzo min<br>(incluso)",
                    align: "right",
                    sortable: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        return parseFloat(value).toLocaleString("it-IT", {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                            style: "currency",
                            currency: "EUR",
                        });
                    },
                },
                {
                    field: "price_max",
                    title: "Prz max<br>(escluso)",
                    align: "right",
                    sortable: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        return parseFloat(value).toLocaleString("it-IT", {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                            style: "currency",
                            currency: "EUR",
                        });
                    },
                },
                {
                    field: "reload_perc",
                    title: "Ricarico<br>%",
                    align: "center",
                    sortable: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        return parseFloat(value).toFixed(2) + " %";
                    },
                },
                {
                    field: "reload_amount",
                    title: "Ricarico<br>EUR",
                    align: "right",
                    sortable: true,
                    filterControl: "input",
                    formatter: function (value, row, index) {
                        return parseFloat(value).toLocaleString("it-IT", {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                            style: "currency",
                            currency: "EUR",
                        });
                    },
                },
                {
                    field: "total_products",
                    title: "Totale<br>Prodotti",
                    align: "center",
                    sortable: true,
                    filterControl: "input",
                    footerFormatter: function (data) {
                        if (!Array.isArray(data) || data.length === 0) {
                            return 0;
                        }

                        const total = data.reduce((sum, row) => {
                            const n = parseInt(row.total_products, 10);
                            return sum + (Number.isFinite(n) ? n : 0);
                        }, 0);

                        return total;
                    },
                    formatter: function (value, row, index) {
                        if (value == 0) {
                            return `
                                <div class="alert alert-warning">0</div>
                            `;
                        }

                        return value;
                    },
                },
                {
                    field: "action",
                    title: "Azioni",
                    align: "center",
                    sortable: false,
                    formatter: function (value, row, index) {
                        return `
                            <div class="d-flex justify-content-center align-items-center">
                                <button type="button" class="btn btn-toolbar-action" name="btn-edit-price-reload" title="Modifica" data-id-price-reload="${row.id_product_price_reload}">
                                    <span class="material-icons">edit</span>
                                </button>
                                <button type="button" class="btn btn-toolbar-action" name="btn-delete-price-reload" title="Elimina" data-id-price-reload="${row.id_product_price_reload}">
                                    <span class="material-icons">close</span>
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
        const self = this;
        const btnsEditPrice = document.getElementsByName("btn-edit-price-reload");
        if (btnsEditPrice) {
            btnsEditPrice.forEach((btn) => {
                btn.addEventListener("click", async (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    const idPriceReload = btn.getAttribute("data-id-price-reload");
                    const formData = new FormData();
                    formData.append("ajax", 1);
                    formData.append("action", "editPriceReload");
                    formData.append("id_price_reload", idPriceReload);

                    const response = await fetch(self.adminControllerUrl, {
                        method: "POST",
                        body: formData,
                    });

                    if (response.ok) {
                        const result = await response.json();

                        if (result.ok) {
                            const data = result.data;
                            document.getElementById("price_load_id").value = data.id;
                            document.getElementById("price_min").value = data.price_min;
                            document.getElementById("price_max").value = data.price_max;
                            document.getElementById("price_reload_perc").value = data.reload_perc;
                            document.getElementById("price_reload_amount").value = data.reload_amount;

                            document.getElementById("price_min").focus();
                            toast.showToastSuccess("Modifica prezzo ricarico", result.message);
                        } else {
                            toast.showToastError("Modifica prezzo ricarico", result.error);
                        }
                    }
                });
            });
        }

        const btnsDeletePrice = document.getElementsByName("btn-delete-price-reload");
        if (btnsDeletePrice) {
            btnsDeletePrice.forEach((btn) => {
                btn.addEventListener("click", async (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    if (confirm("Eliminare questa fascia di ricarico?") == false) {
                        return false;
                    }

                    const idPriceReload = btn.dataset.idPriceReload;
                    const formData = new FormData();
                    formData.append("ajax", 1);
                    formData.append("action", "deletePriceReload");
                    formData.append("id_price_reload", idPriceReload);

                    const response = await fetch(self.adminControllerUrl, {
                        method: "POST",
                        body: formData,
                    });

                    if (response.ok) {
                        const result = await response.json();
                        toast.showToastSuccess("Eliminazione prezzo ricarico", result.message);
                        tablePriceReload.refresh();
                    } else {
                        toast.showToastError("Eliminazione prezzo ricarico", result.error);
                    }
                });
            });
        }
    }
}
