// Web Component per Toast con Shadow DOM
class MpToast extends HTMLElement {
    static stacks = {
        topleft: [],
        topright: [],
        bottomleft: [],
        bottomright: [],
    };

    #shadow = null;
    #timeoutId = null;
    #dialog = null;

    constructor() {
        super();
        this.#shadow = this.attachShadow({ mode: "open" });
        this._render();
    }

    static get observedAttributes() {
        return ["title", "message", "type", "small", "position", "duration"];
    }

    attributeChangedCallback(name, oldValue, newValue) {
        if (oldValue !== newValue) {
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

    get small() {
        return this.getAttribute("small") || "";
    }

    set small(value) {
        this.setAttribute("small", value);
    }

    get position() {
        return this.getAttribute("position") || "topright";
    }

    set position(value) {
        this.setAttribute("position", value);
    }

    get duration() {
        return parseInt(this.getAttribute("duration")) || 5000;
    }

    set duration(value) {
        this.setAttribute("duration", value);
    }

    _getStyles() {
        return `
            :host {
                display: block;
                position: fixed;
                z-index: 9999;
                opacity: 0;
                transform: translateY(10px);
                transition: opacity 0.25s ease-out, transform 0.25s ease-out;
            }

            :host(.visible) {
                opacity: 1;
                transform: translateY(0);
            }

            .mp-toast {
                min-width: 280px;
                max-width: 360px;
                background: #ffffff;
                color: #212529;
                border-radius: 0.5rem;
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.15);
                overflow: hidden;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                font-size: 0.875rem;
            }

            .mp-toast-header {
                display: flex;
                align-items: center;
                padding: 0.5rem 0.75rem;
                border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                position: relative;
            }

            .mp-toast-badge {
                width: 16px;
                height: 16px;
                border-radius: 4px;
                margin-right: 0.5rem;
                flex-shrink: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #ffffff;
            }

            .mp-toast-icon {
                display: block;
                font-size: 0.6rem;
                line-height: 1;
            }

            .mp-toast-title {
                font-weight: 600;
                margin-right: 0.5rem;
            }

            .mp-toast-small {
                margin-left: auto;
                font-size: 0.75rem;
                color: #6c757d;
                margin-right: 1.5rem;
            }

            .mp-toast-close {
                background: none;
                border: none;
                color: #6c757d;
                cursor: pointer;
                font-size: 1.25rem;
                line-height: 1;
                position: absolute;
                right: 8px;
                top: 50%;
                transform: translateY(-50%);
                padding: 0;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .mp-toast-close:hover {
                color: #000000;
            }

            .mp-toast-body {
                padding: 0.75rem;
            }

            /* Type styles */
            :host([type="default"]) .mp-toast-badge {
                background: transparent;
                border: 1px solid #ced4da;
                color: #6c757d;
            }

            :host([type="info"]) .mp-toast-badge {
                background: #0d6efd;
            }

            :host([type="success"]) .mp-toast-badge {
                background: #198754;
            }

            :host([type="warning"]) .mp-toast-badge {
                background: #ffc107;
            }

            :host([type="error"]) .mp-toast-badge {
                background: #dc3545;
            }

            :host([type="default"]) .mp-toast-icon::before {
                content: '\\2022';
            }

            :host([type="info"]) .mp-toast-icon::before {
                content: 'i';
            }

            :host([type="success"]) .mp-toast-icon::before {
                content: '\\2713';
            }

            :host([type="warning"]) .mp-toast-icon::before,
            :host([type="error"]) .mp-toast-icon::before {
                content: '!';
            }
        `;
    }

    _render() {
        const type = this.type || "default";
        const title = this.title || "";
        const message = this.message || "";
        const small = this.small || "";

        this.#shadow.innerHTML = `
            <style>${this._getStyles()}</style>
            <div class="mp-toast">
                <div class="mp-toast-header">
                    <span class="mp-toast-badge"><span class="mp-toast-icon"></span></span>
                    <div class="mp-toast-title">${title}</div>
                    ${small ? `<small class="mp-toast-small">${small}</small>` : ""}
                    <button type="button" class="mp-toast-close" aria-label="Close">&times;</button>
                </div>
                <div class="mp-toast-body">
                    ${message}
                </div>
            </div>
        `;

        const closeBtn = this.#shadow.querySelector(".mp-toast-close");
        if (closeBtn) {
            closeBtn.addEventListener("click", () => this.hide());
        }
    }

    static applyPresetPosition(element, position) {
        const pos = String(position).toLowerCase();
        element.dataset.mpToastPosition = pos;

        element.style.left = "";
        element.style.right = "";

        switch (pos) {
            case "topleft":
            case "bottomleft":
                element.style.left = "1rem";
                element.style.right = "auto";
                break;
            case "topright":
            case "bottomright":
            default:
                element.style.right = "1rem";
                element.style.left = "auto";
                break;
        }
    }

    static registerToast(element, positionKey) {
        if (!positionKey || !MpToast.stacks[positionKey]) {
            return;
        }
        MpToast.stacks[positionKey].push(element);
    }

    static unregisterToast(element, positionKey) {
        const pos = positionKey || element?.dataset?.mpToastPosition;
        if (!pos || !MpToast.stacks[pos]) {
            return;
        }
        MpToast.stacks[pos] = MpToast.stacks[pos].filter((el) => el !== element);
        MpToast.reflowStack(pos);
    }

    static reflowStack(positionKey) {
        const stack = MpToast.stacks[positionKey];
        if (!stack || !stack.length) {
            return;
        }

        const gap = 8;
        const baseOffset = 16;
        const isTop = positionKey.startsWith("top");

        let offset = baseOffset;
        stack.forEach((element) => {
            if (!element || !element.isConnected) {
                return;
            }

            if (isTop) {
                element.style.top = offset + "px";
                element.style.bottom = "auto";
            } else {
                element.style.bottom = offset + "px";
                element.style.top = "auto";
            }

            const rect = element.getBoundingClientRect();
            const height = rect.height || 0;
            offset += height + gap;
        });
    }

    show() {
        const position = this.position;
        const presetPositions = ["topleft", "topright", "bottomleft", "bottomright"];

        if (presetPositions.includes(position.toLowerCase())) {
            const positionKey = position.toLowerCase();
            MpToast.applyPresetPosition(this, positionKey);
            MpToast.registerToast(this, positionKey);

            requestAnimationFrame(() => {
                this.classList.add("visible");
                MpToast.reflowStack(positionKey);
            });
        } else {
            requestAnimationFrame(() => {
                this.classList.add("visible");
            });
        }

        const duration = this.duration;
        if (duration && duration > 0) {
            this.#timeoutId = setTimeout(() => this.hide(), duration);
        }
    }

    hide() {
        if (this.#timeoutId) {
            clearTimeout(this.#timeoutId);
            this.#timeoutId = null;
        }

        const onTransitionEnd = () => {
            this.removeEventListener("transitionend", onTransitionEnd);
            const positionKey = this.dataset?.mpToastPosition;
            MpToast.unregisterToast(this, positionKey);
            this.remove();
        };

        this.classList.remove("visible");
        this.addEventListener("transitionend", onTransitionEnd);
    }
}

// Registra il Web Component
if (!customElements.get("mp-toast")) {
    customElements.define("mp-toast", MpToast);
}

// Classe wrapper per mantenere la compatibilità con il codice esistente
class ToastClass {
    #id = null;
    #currentToast = null;

    constructor(id) {
        this.#id = id || "toast-" + Date.now();
    }

    showToast(title, message, type = "info", small = "", x = null, y = null, duration = 5000) {
        // Rimuovi il toast precedente se esiste
        if (this.#currentToast) {
            this.#currentToast.hide();
        }

        // Crea il nuovo toast element
        const toast = document.createElement("mp-toast");
        toast.id = this.#id;
        toast.title = title;
        toast.message = message;
        toast.type = type;
        toast.small = small || "";
        toast.duration = duration;

        // Gestione posizione
        const presetPositions = ["topleft", "topright", "bottomleft", "bottomright"];
        const isPresetName = typeof x === "string" && presetPositions.includes(x.toLowerCase());

        if ((x === null || x === undefined) && (y === null || y === undefined)) {
            toast.position = "topright";
        } else if (isPresetName) {
            toast.position = x.toLowerCase();
        } else {
            toast.position = "custom";
            if (x !== null && x !== undefined) {
                toast.style.left = typeof x === "number" ? x + "px" : String(x);
                toast.style.right = "auto";
            }
            if (y !== null && y !== undefined) {
                toast.style.top = typeof y === "number" ? y + "px" : String(y);
                toast.style.bottom = "auto";
            }
        }

        document.body.appendChild(toast);
        this.#currentToast = toast;
        toast.show();
    }

    showToastInfo(title, message, small = null, x = null, y = null, duration = 5000) {
        this.showToast(title, message, "info", small, x, y, duration);
    }

    showToastSuccess(title, message, small = null, x = null, y = null, duration = 5000) {
        this.showToast(title, message, "success", small, x, y, duration);
    }

    showToastWarning(title, message, small = null, x = null, y = null, duration = 5000) {
        this.showToast(title, message, "warning", small, x, y, duration);
    }

    showToastError(title, message, small = null, x = null, y = null, duration = 5000) {
        this.showToast(title, message, "error", small, x, y, duration);
    }

    hide() {
        if (this.#currentToast) {
            this.#currentToast.hide();
            this.#currentToast = null;
        }
    }

    remove() {
        this.hide();
    }
}
