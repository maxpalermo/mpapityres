async function associatePfu(ids, idPfu) {
    if (!confirm("Sei sicuro di voler associare questi pneumatici al PFU?")) {
        return;
    }

    const response = await fetch(CronControllerURL, {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams({
            ajax: 1,
            action: "associatePfuAction",
            ids: ids,
            idPfu: idPfu,
            token: cronToken,
        }),
    });

    const data = await response.json();
    if (data.success) {
        alert(data.message);
        table.bootstrapTable("refresh");
    }
}

async function dissociatePfu(ids) {
    if (!confirm("Sei sicuro di voler dissociare questi pneumatici dal PFU?")) {
        return;
    }

    const response = await fetch(CronControllerURL, {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams({
            ajax: 1,
            action: "dissociatePfuAction",
            ids: ids,
            token: cronToken,
        }),
    });

    const data = await response.json();
    if (data.success) {
        alert(data.message);
        table.bootstrapTable("refresh");
    }
}

function handleDropdownMenu() {
    const menu = document.querySelector(".btn-group.dropdown.dropup");

    menu.addEventListener("click", (ev) => {
        const self = ev.target;
        const dropdown = self.closest("div").querySelector(".dropdown-menu");

        $(dropdown).toggle();
    });
}

function getIdSelections() {
    return $.map(table.bootstrapTable("getSelections"), function (row) {
        return row.id_product;
    });
}

function operateFormatter() {
    return ['<a class="like" href="javascript:void(0)" title="Like">', '<i class="fa fa-heart"></i>', "</a>  ", '<a class="remove" href="javascript:void(0)" title="Remove">', '<i class="fa fa-trash"></i>', "</a>"].join("");
}

function totalTextFormatter() {
    return "Total";
}

function totalNameFormatter(data) {
    return data.length;
}

function totalPriceFormatter(data) {
    const field = this.field;

    return `$${data
        .map(function (row) {
            return +row[field].substring(1);
        })
        .reduce(function (sum, i) {
            return sum + i;
        }, 0)}`;
}

function refreshTable() {
    table.bootstrapTable("refresh");
}

function initTable(table) {
    table.bootstrapTable("destroy").bootstrapTable({
        height: "100%",
        locale: "it-IT",
        queryParams: function (params) {
            const val = $("input[name=show-not-associated]:checked").val();
            params["show-not-associated"] = val;
            return params;
        },
        columns: [
            {
                field: "state",
                checkbox: true,
                align: "center",
                valign: "middle",
                width: "48",
                widthUnit: "px",
            },
            {
                title: "ID",
                field: "id_product",
                align: "left",
                valign: "middle",
                sortable: true,
                width: "120",
                widthUnit: "px",
            },
            {
                title: "Riferimento",
                field: "reference",
                align: "left",
                sortable: true,
            },
            {
                title: "Pneumatico",
                field: "name",
                align: "left",
                sortable: true,
            },
            {
                title: "Grandezza",
                field: "tyre_size",
                align: "left",
                sortable: true,
            },
            {
                title: "Stagione",
                field: "tyre_season",
                align: "left",
                sortable: true,
            },
            {
                title: "PFU",
                field: "pfu_name",
                align: "left",
                sortable: true,
            },
            {
                title: "Categoria",
                field: "category_name",
                align: "left",
                sortable: true,
            },
        ],
    });

    table.on("check.bs.table uncheck.bs.table " + "check-all.bs.table uncheck-all.bs.table", function () {
        selections = getIdSelections();
    });

    table.on("all.bs.table", function (e, name, args) {
        console.log("Bootstrap Table fired: ", name, args);
    });

    table.on("refresh.bs.table", () => {
        //todo
    });

    table.on("load-success.bs.table", () => {
        console.log("RELOAD SUCCESS");
        handleDropdownMenu();
    });
}

let table = null;

document.addEventListener("DOMContentLoaded", async () => {
    table = $("#table-products-fpu");

    const btnAssociate = document.getElementById("btn-associate");
    const btnDissociate = document.getElementById("btn-dissociate");
    const btnNoPfu = document.querySelectorAll("input[name=show-not-associated]");

    if (btnAssociate) {
        btnAssociate.addEventListener("click", () => {
            const ids = getIdSelections();
            const idPfu = document.getElementById("pfu-list").value;

            console.log(ids);
            console.log(idPfu);

            if (idPfu == "Seleziona") {
                alert("Seleziona un PFU");
                return;
            }

            if (!ids.length) {
                alert("Seleziona almeno un prodotto");
                return;
            }

            associatePfu(ids, idPfu);
        });
    }

    if (btnDissociate) {
        btnDissociate.addEventListener("click", () => {
            const ids = getIdSelections();

            if (!ids.length) {
                alert("Seleziona almeno un prodotto");
                return;
            }

            dissociatePfu(ids);
        });
    }

    btnNoPfu.forEach((btn) => {
        btn.addEventListener("click", () => {
            refreshTable();
        });
    });

    let selections = [];

    window.responseHandler = (res) => {
        $.each(res.rows, function (i, row) {
            row.state = $.inArray(row.id_product, selections) !== -1;
        });
        return res;
    };

    window.detailFormatter = (index, row) => {
        const html = [];

        $.each(row, function (key, value) {
            html.push(`<p><b>${key}:</b> ${value}</p>`);
        });
        return html.join("");
    };

    window.operateEvents = {
        "click .like"(e, value, row) {
            alert(`You click like action, row: ${JSON.stringify(row)}`);
        },
    };

    initTable(table);
});
