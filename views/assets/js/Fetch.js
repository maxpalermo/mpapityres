class Fetch {
    moduleName = null;
    adminControllerUrl = null;

    constructor(adminControllerUrl, moduleName) {
        this.adminControllerUrl = adminControllerUrl;
        this.moduleName = moduleName;
    }

    async run(action, data) {
        const self = this;
        const formData = new FormData();
        formData.append("ajax", 1);
        formData.append("action", action);
        Object.entries(data).forEach(([key, value]) => {
            formData.append(key, value);
        });

        const request = await fetch(self.adminControllerUrl, {
            method: "POST",
            body: formData,
        });

        if (!request.ok) {
            throw new Error(`${this.moduleName}: fetch: Network response was not ok`);
        }

        const response = await request.json();

        return response;
    }
}
