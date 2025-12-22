// Web Component per Loading Message con Shadow DOM
class LoadingMessageElement extends HTMLElement {
    #shadow = null;

    constructor() {
        super();
        this.#shadow = this.attachShadow({ mode: "open" });
    }

    static get observedAttributes() {
        return ["title", "message", "type", "icon"];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue !== newValue && this.isConnected) {
            this._render();
        }
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

    get icon() {
        return this.getAttribute("icon") || "";
    }

    set icon(value) {
        this.setAttribute("icon", value);
    }

    _getStyles() {
        return `
            :host {
                display: block;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 10001;
                opacity: 0;
                transition: opacity 0.25s ease-out;
            }

            :host(.visible) {
                opacity: 1;
            }

            .loading-backdrop {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(4px);
                -webkit-backdrop-filter: blur(4px);
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .loading-message {
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

            .loading-header {
                display: flex;
                align-items: center;
                padding: 0.75rem 1rem;
                border-bottom: 1px solid rgba(0, 0, 0, 0.1);
                position: relative;
            }

            .loading-badge {
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

            .loading-icon {
                display: block;
                font-size: 0.75rem;
                line-height: 1;
            }

            .loading-title {
                font-weight: 600;
                font-size: 1rem;
            }

            .loading-body {
                padding: 1rem;
                line-height: 1.5;
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            .loading-spinner {
                width: 32px;
                height: 32px;
                flex-shrink: 0;
                border: 3px solid rgba(0, 0, 0, 0.1);
                border-top-color: #0d6efd;
                border-radius: 50%;
                animation: spin 0.8s linear infinite;
            }

            .loading-content {
                flex: 1;
            }

            @keyframes spin {
                to {
                    transform: rotate(360deg);
                }
            }

            /* Type styles */
            :host([type="default"]) .loading-badge {
                background: transparent;
                border: 1px solid #ced4da;
                color: #6c757d;
            }

            :host([type="default"]) .loading-spinner {
                border-top-color: #6c757d;
            }

            :host([type="info"]) .loading-badge {
                background: #0d6efd;
            }

            :host([type="info"]) .loading-spinner {
                border-top-color: #0d6efd;
            }

            :host([type="success"]) .loading-badge {
                background: #198754;
            }

            :host([type="success"]) .loading-spinner {
                border-top-color: #198754;
            }

            :host([type="warning"]) .loading-badge {
                background: #ffc107;
            }

            :host([type="warning"]) .loading-spinner {
                border-top-color: #ffc107;
            }

            :host([type="error"]) .loading-badge {
                background: #dc3545;
            }

            :host([type="error"]) .loading-spinner {
                border-top-color: #dc3545;
            }

            :host([type="default"]) .loading-icon::before {
                content: '\\2022';
            }

            :host([type="info"]) .loading-icon::before {
                content: 'i';
            }

            :host([type="success"]) .loading-icon::before {
                content: '\\2713';
            }

            :host([type="warning"]) .loading-icon::before,
            :host([type="error"]) .loading-icon::before {
                content: '!';
            }
        `;
    }

    _render() {
        const title = this.title || "";
        const message = this.message || "";
        const icon = this.icon || "";

        this.#shadow.innerHTML = `
            <style>${this._getStyles()}</style>
            <div class="loading-backdrop">
                <div class="loading-message">
                <div class="loading-header">
                    <span class="loading-badge">
                        <span class="loading-icon">${icon}</span>
                    </span>
                    <div class="loading-title">${title}</div>
                </div>
                <div class="loading-body">
                    <div class="loading-spinner"></div>
                    <div class="loading-content">${message}</div>
                </div>
            </div>
            </div>
        `;
    }

    show() {
        this._render();

        requestAnimationFrame(() => {
            this.classList.add("visible");
        });
    }

    hide() {
        const onTransitionEnd = () => {
            this.removeEventListener("transitionend", onTransitionEnd);
            this.remove();
        };

        this.classList.remove("visible");
        this.addEventListener("transitionend", onTransitionEnd);
    }

    updateContent(title, message, type, icon) {
        if (title !== undefined) this.title = title;
        if (message !== undefined) this.message = message;
        if (type !== undefined) this.type = type;
        if (icon !== undefined) this.icon = icon;
    }
}

// Registra il Web Component
if (!customElements.get("loading-message")) {
    customElements.define("loading-message", LoadingMessageElement);
}

// Classe wrapper per mantenere la compatibilità con il codice esistente
class LoadingMessageWrapper {
    #id = null;
    #currentLoading = null;

    constructor(id) {
        this.#id = id || "loading-" + Date.now();
    }

    init() {
        // Inizializzazione se necessaria
        return this;
    }

    show(title, message, type = "info", icon = "") {
        // Rimuovi il loading precedente se esiste
        if (this.#currentLoading) {
            this.#currentLoading.hide();
        }

        // Crea il nuovo loading element
        const loading = document.createElement("loading-message");
        loading.id = this.#id;
        loading.title = title;
        loading.message = message;
        loading.type = type;
        loading.icon = icon || "";

        document.body.appendChild(loading);
        this.#currentLoading = loading;
        loading.show();

        return this;
    }

    showInfo(title, message, icon = "") {
        return this.show(title, message, "info", icon);
    }

    showSuccess(title, message, icon = "") {
        return this.show(title, message, "success", icon);
    }

    showWarning(title, message, icon = "") {
        return this.show(title, message, "warning", icon);
    }

    showError(title, message, icon = "") {
        return this.show(title, message, "error", icon);
    }

    update(title, message, type, icon) {
        if (this.#currentLoading) {
            this.#currentLoading.updateContent(title, message, type, icon);
        }
        return this;
    }

    updateTitle(title) {
        if (this.#currentLoading) {
            this.#currentLoading.title = title;
        }
        return this;
    }

    updateContent(message) {
        if (this.#currentLoading) {
            this.#currentLoading.message = message;
        }
        return this;
    }

    updateIcon(icon) {
        if (this.#currentLoading) {
            this.#currentLoading.icon = icon;
        }
        return this;
    }

    updateType(type) {
        if (this.#currentLoading) {
            this.#currentLoading.type = type;
        }
        return this;
    }

    hide() {
        if (this.#currentLoading) {
            this.#currentLoading.hide();
            this.#currentLoading = null;
        }
        return this;
    }

    remove() {
        this.hide();
        return this;
    }
}
