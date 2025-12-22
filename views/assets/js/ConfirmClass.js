// Web Component per Confirm Dialog con Shadow DOM
class MpConfirm extends HTMLElement {
    #shadow = null;
    #resolve = null;
    #dialog = null;

    constructor() {
        super();
        this.#shadow = this.attachShadow({ mode: "open" });
    }

    static get observedAttributes() {
        return ["title", "message", "type", "small", "buttons"];
    }

    get title() {
        return this.getAttribute("title") || "";
    }

    set title(value) {
        this.setAttribute("title", value);
    }

    get message() {
        return this.getAttribute("message") || "";
    }

    set message(value) {
        this.setAttribute("message", value);
    }

    get type() {
        return this.getAttribute("type") || "info";
    }

    set type(value) {
        this.setAttribute("type", value);
    }

    get small() {
        return this.getAttribute("small") || "";
    }

    set small(value) {
        this.setAttribute("small", value);
    }

    get buttons() {
        return this.getAttribute("buttons") || "ok";
    }

    set buttons(value) {
        this.setAttribute("buttons", value);
    }

    _getStyles() {
        return `
            :host {
                display: block;
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                z-index: 10000;
                opacity: 0;
                transition: opacity 0.25s ease-out;
            }

            :host(.visible) {
                opacity: 1;
            }

            .mp-confirm-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: -1;
            }

            .mp-confirm {
                min-width: 320px;
                max-width: 480px;
                background: #ffffff;
                color: #212529;
                border-radius: 0.5rem;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3);
                overflow: hidden;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                font-size: 0.875rem;
            }

            .mp-confirm-header {
                display: flex;
                align-items: center;
                padding: 0.75rem 1rem;
                border-bottom: 1px solid rgba(0, 0, 0, 0.1);
                position: relative;
            }

            .mp-confirm-badge {
                width: 20px;
                height: 20px;
                border-radius: 4px;
                margin-right: 0.75rem;
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #ffffff;
            }

            .mp-confirm-icon {
                display: block;
                font-size: 0.75rem;
                line-height: 1;
            }

            .mp-confirm-title {
                font-weight: 600;
                font-size: 1rem;
                margin-right: 0.5rem;
            }

            .mp-confirm-small {
                margin-left: auto;
                font-size: 0.75rem;
                color: #6c757d;
                margin-right: 2rem;
            }

            .mp-confirm-close {
                background: none;
                border: none;
                color: #6c757d;
                cursor: pointer;
                font-size: 1.5rem;
                line-height: 1;
                position: absolute;
                right: 12px;
                top: 50%;
                transform: translateY(-50%);
                padding: 0;
                width: 24px;
                height: 24px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .mp-confirm-close:hover {
                color: #000000;
            }

            .mp-confirm-body {
                padding: 1rem;
                line-height: 1.5;
            }

            .mp-confirm-footer {
                padding: 0.75rem 1rem;
                display: flex;
                justify-content: flex-end;
                gap: 0.5rem;
                border-top: 1px solid rgba(0, 0, 0, 0.05);
            }

            .mp-confirm-btn {
                min-width: 80px;
                padding: 0.5rem 1rem;
                border-radius: 0.25rem;
                border: 1px solid #ced4da;
                background: #f8f9fa;
                color: #212529;
                cursor: pointer;
                font-size: 0.875rem;
                font-weight: 500;
                transition: all 0.15s ease-in-out;
            }

            .mp-confirm-btn:hover {
                background: #e9ecef;
                border-color: #adb5bd;
            }

            .mp-confirm-btn--primary {
                background: #0d6efd;
                border-color: #0d6efd;
                color: #ffffff;
            }

            .mp-confirm-btn--primary:hover {
                background: #0b5ed7;
                border-color: #0a58ca;
            }

            /* Type styles */
            :host([type="default"]) .mp-confirm-badge {
                background: transparent;
                border: 1px solid #ced4da;
                color: #6c757d;
            }

            :host([type="info"]) .mp-confirm-badge {
                background: #0d6efd;
            }

            :host([type="success"]) .mp-confirm-badge {
                background: #198754;
            }

            :host([type="warning"]) .mp-confirm-badge {
                background: #ffc107;
            }

            :host([type="error"]) .mp-confirm-badge {
                background: #dc3545;
            }

            :host([type="default"]) .mp-confirm-icon::before {
                content: '\\2022';
            }

            :host([type="info"]) .mp-confirm-icon::before {
                content: 'i';
            }

            :host([type="success"]) .mp-confirm-icon::before {
                content: '\\2713';
            }

            :host([type="warning"]) .mp-confirm-icon::before,
            :host([type="error"]) .mp-confirm-icon::before {
                content: '!';
            }
        `;
    }

    _buildButtons(buttonSet) {
        switch (buttonSet) {
            case "yesno":
                return `
                    <button type="button" class="mp-confirm-btn mp-confirm-btn--primary" data-confirm-value="yes">Sì</button>
                    <button type="button" class="mp-confirm-btn" data-confirm-value="no">No</button>
                `;
            case "yesnocancel":
                return `
                    <button type="button" class="mp-confirm-btn mp-confirm-btn--primary" data-confirm-value="yes">Sì</button>
                    <button type="button" class="mp-confirm-btn" data-confirm-value="no">No</button>
                    <button type="button" class="mp-confirm-btn" data-confirm-value="cancel">Annulla</button>
                `;
            case "ok":
            default:
                return `
                    <button type="button" class="mp-confirm-btn mp-confirm-btn--primary" data-confirm-value="ok">OK</button>
                `;
        }
    }

    _render() {
        const title = this.title || "";
        const message = this.message || "";
        const small = this.small || "";
        const buttons = this.buttons || "ok";
        const buttonsHtml = this._buildButtons(buttons);

        this.#shadow.innerHTML = `
            <style>${this._getStyles()}</style>
            <div class="mp-confirm-backdrop"></div>
            <div class="mp-confirm">
                <div class="mp-confirm-header">
                    <span class="mp-confirm-badge"><span class="mp-confirm-icon"></span></span>
                    <div class="mp-confirm-title">${title}</div>
                    ${small ? `<small class="mp-confirm-small">${small}</small>` : ""}
                    <button type="button" class="mp-confirm-close" aria-label="Close">&times;</button>
                </div>
                <div class="mp-confirm-body">
                    ${message}
                </div>
                <div class="mp-confirm-footer">
                    ${buttonsHtml}
                </div>
            </div>
        `;

        this._attachEventListeners();
    }

    _attachEventListeners() {
        const closeBtn = this.#shadow.querySelector(".mp-confirm-close");
        if (closeBtn) {
            closeBtn.addEventListener("click", () => this._handleClose("cancel"));
        }

        const backdrop = this.#shadow.querySelector(".mp-confirm-backdrop");
        if (backdrop) {
            backdrop.addEventListener("click", () => this._handleClose("cancel"));
        }

        this.#shadow.querySelectorAll("[data-confirm-value]").forEach((btn) => {
            btn.addEventListener("click", () => {
                const value = btn.getAttribute("data-confirm-value") || "cancel";
                this._handleClose(value);
            });
        });
    }

    _handleClose(result) {
        this.hide(result);
    }

    show() {
        this._render();

        requestAnimationFrame(() => {
            this.classList.add("visible");
        });

        return new Promise((resolve) => {
            this.#resolve = resolve;
        });
    }

    hide(result = "cancel") {
        const resolve = this.#resolve;
        this.#resolve = null;

        const onTransitionEnd = () => {
            this.removeEventListener("transitionend", onTransitionEnd);
            this.remove();
            if (resolve) {
                resolve(result);
            }
        };

        this.classList.remove("visible");
        this.addEventListener("transitionend", onTransitionEnd);
    }
}

// Registra il Web Component
if (!customElements.get("mp-confirm")) {
    customElements.define("mp-confirm", MpConfirm);
}

// Classe wrapper per mantenere la compatibilità con il codice esistente
class ConfirmClass {
    #id = null;
    #currentConfirm = null;

    constructor(id) {
        this.#id = id || "confirm-" + Date.now();
    }

    showConfirm(title, message, options = {}) {
        const { type = "info", small = "", buttons = "ok" } = options;

        // Rimuovi il confirm precedente se esiste
        if (this.#currentConfirm) {
            this.#currentConfirm.hide("cancel");
        }

        // Crea il nuovo confirm element
        const confirm = document.createElement("mp-confirm");
        confirm.id = this.#id;
        confirm.title = title;
        confirm.message = message;
        confirm.type = type;
        confirm.small = small || "";
        confirm.buttons = buttons;

        document.body.appendChild(confirm);
        this.#currentConfirm = confirm;

        return confirm.show();
    }

    confirmOk(title, message, options = {}) {
        return this.showConfirm(title, message, { ...options, buttons: "ok" });
    }

    confirmYesNo(title, message, options = {}) {
        return this.showConfirm(title, message, { ...options, buttons: "yesno" });
    }

    confirmYesNoCancel(title, message, options = {}) {
        return this.showConfirm(title, message, { ...options, buttons: "yesnocancel" });
    }

    hide(result = "cancel") {
        if (this.#currentConfirm) {
            this.#currentConfirm.hide(result);
            this.#currentConfirm = null;
        }
    }

    remove() {
        this.hide("cancel");
    }
}
