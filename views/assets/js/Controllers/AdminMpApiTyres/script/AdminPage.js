async function associateProductsToCategory() {
    const id_category = document.querySelector("input[name=categoryBox]:checked").value;
    console.log("Selected category:", id_category);

    const response = await fetch(AdminControllerUrl, {
        method: "POST",
        body: new URLSearchParams({
            ajax: 1,
            action: "associateProductsToCategory",
            id_category: id_category,
        }),
    });

    if (response.ok) {
        const result = await response.json();
        console.log("Association result:", result);
        toast.showToastSuccess("Associa categoria", result.message);
    }
}

async function applyFilters() {
    // Get all checkbox values
    const formData = new FormData(document.getElementById("filterForm"));
    formData.append("ajax", 1);
    formData.append("action", "applyFilters");

    const response = await fetch(AdminControllerUrl, {
        method: "POST",
        body: formData,
    });

    if (response.ok) {
        const result = await response.json();
        toast.showToastSuccess("Operazione eseguita", "Filtri applicati con successo!");
        document.getElementById("totalProducts").textContent = result.totalProducts;
        document.getElementById("filteredProducts").textContent = result.totalFiltered;
    }
}

async function saveAddress() {
    const address = new FormData(document.getElementById("form-address-order"));
    address.append("ajax", 1);
    address.append("action", "saveAddress");

    const response = await fetch(AdminControllerUrl, {
        method: "POST",
        body: address,
    });

    if (response.ok) {
        const result = await response.json();
        toast.showToastSuccess("Salva Indirizzo", result.message);
    }
}

async function createTable() {
    window.location.href = `${AdminControllerUrl}&submitCreateTableCsv`;
}

async function createTable() {
    window.location.href = `${AdminControllerUrl}&submitCreateTableCsv`;
}

function createAlert(message, type) {
    const alert = document.createElement("div");
    alert.classList.add("alert", "alert-" + type);
    alert.textContent = message;

    return alert;
}

async function FetchAdmin(action, formData) {
    formData.append("ajax", 1);
    formData.append("action", action);

    const request = await fetch(AdminControllerUrl, {
        method: "POST",
        body: formData,
    });

    if (request.ok) {
        const result = await request.json();
        return result;
    }

    return null;
}

async function savePriceReload() {
    const formData = new FormData(document.getElementById("formPriceLoad"));
    const data = await FetchAdmin("savePriceReload", formData);

    if (data.ok) {
        toast.showToastSuccess("Ricarico salvato", data.message);
        document.getElementById("formPriceLoad").reset();
        tablePriceReload.refresh();
    } else {
        toast.showToastError("Errore", data.error);
    }
}

function closeDialog(dialogId) {
    const dialog = document.getElementById(dialogId);
    if (dialog instanceof HTMLDialogElement) {
        dialog.close();
    }
}

function showDialog(dialogId) {
    const dialog = document.getElementById(dialogId);
    if (dialog instanceof HTMLDialogElement) {
        dialog.showModal();
    }
}

function getTemplate(templateId) {
    const template = document.getElementById(templateId);
    if (template == null) {
        return null;
    }
    return template.content.cloneNode(true);
}

function setupChosenDropdowns(rootEl, options = {}) {
    if (rootEl == null) {
        return;
    }

    const { selector = ".chosen", width = "100%", zIndex = 999999, ensureOverflowVisible = true } = options;

    if (ensureOverflowVisible) {
        rootEl.style.overflow = "visible";
        const modalBody = rootEl.querySelector(".modal-body");
        if (modalBody) {
            modalBody.style.overflow = "visible";
        }
    }

    const $chosenElements = $(rootEl.querySelectorAll(selector));
    $chosenElements.each(function () {
        const $el = $(this);
        if ($el.data("chosen")) {
            $el.trigger("chosen:updated");
        } else {
            $el.chosen({
                width,
            });
        }

        const $container = $el.next(".chosen-container");
        if ($container.length) {
            $container.css("z-index", zIndex);
            $container.find(".chosen-drop").css("z-index", zIndex);
        }
    });
}

function setPfuDialog(idProduct, idPfu) {
    let dialog = document.getElementById("pfu-association-dialog");

    if (dialog == null) {
        const fragment = getTemplate("template-pfu-association");
        if (fragment == null) {
            console.error("Template not found");
            return;
        }
        document.body.appendChild(fragment);
        dialog = document.getElementById("pfu-association-dialog");
        if (dialog == null) {
            console.error("Dialog not found");
            return;
        }
    }

    const idProducts = Array.isArray(idProduct) ? idProduct : [idProduct];
    const normalizedIds = idProducts.map((v) => Number(v)).filter((n) => Number.isFinite(n) && n > 0);

    dialog.dataset.idProducts = JSON.stringify(normalizedIds);
    dialog.querySelector("#id_product_csv").value = normalizedIds.join(",");
    dialog.querySelector("#id_pfu_csv").value = idPfu;

    showDialog("pfu-association-dialog");

    setupChosenDropdowns(dialog, {
        selector: ".chosen",
        width: "100%",
        zIndex: 999999,
        ensureOverflowVisible: true,
    });

    const btnApply = dialog.querySelector("#pfu-association-apply");
    if (btnApply) {
        btnApply.onclick = async (e) => {
            e.preventDefault();
            const idsRaw = dialog.dataset.idProducts || "[]";
            const ids = JSON.parse(idsRaw);
            const idPfu = dialog.querySelector("#id_pfu_csv").value;
            await applyPfuAssociation(ids, idPfu);
        };
    }

    const btnClose = dialog.querySelector("#pfu-association-close");
    if (btnClose) {
        btnClose.onclick = (e) => {
            e.preventDefault();
            closeDialog("pfu-association-dialog");
        };
    }
}

async function applyPfuAssociation(idProducts, idPfu) {
    const formData = new FormData();
    if (Array.isArray(idProducts)) {
        idProducts.forEach((id) => formData.append("idProducts[]", id));
    } else {
        formData.append("idProduct", idProducts);
    }
    formData.append("idPfu", idPfu);
    const data = await FetchAdmin("applyPfuAssociation", formData);

    if (data.ok) {
        toast.showToastSuccess("Associazione salvata", data.message);
        closeDialog("pfu-association-dialog");
        $("#table-pfu-association").bootstrapTable("refresh");
    } else {
        toast.showToastError("Errore", data.error || data.message);
    }
}

window.showDialog = showDialog;
window.closeDialog = closeDialog;
window.getTemplate = getTemplate;
window.setupChosenDropdowns = setupChosenDropdowns;
window.setPfuDialog = setPfuDialog;

document.addEventListener("DOMContentLoaded", function () {
    const btnSavePriceReload = document.getElementById("btnSavePriceReload");
    if (btnSavePriceReload) {
        btnSavePriceReload.addEventListener("click", async (e) => {
            e.preventDefault();
            await savePriceReload();
        });
    }
});
