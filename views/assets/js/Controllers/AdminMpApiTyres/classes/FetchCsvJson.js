class FetchCsvJson
{
    constructor(controllerUrl) {
        // URL dell'Admin Controller, ad es. passato da template: endpoint
        this.controllerUrl = controllerUrl;
        this._interval = null;
    }

    // Avvia il download passando l'endpoint del file ZIP
    async start(endpoint) {
        
        const res = await fetch(this.controllerUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                ajax: 1,
                action: 'startCatalogDownloadAction',
                endpoint: endpoint
            })
        });

        const json = await res.json();
        
        if (json.status !== 'started') {
            throw new Error(json.message || 'Errore avvio download');
        }
        
        return json.file; // basename zip
    }

    // Legge lo stato di progresso dal server
    async getProgress(endpoint) {
        const res = await fetch(this.controllerUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                ajax: 1,
                action: 'getCatalogDownloadProgressAction',
                endpoint: endpoint
            })
        });

        const json = await res.json();
        
        if (json.status !== 'ok') {
            throw new Error(json.message || 'Errore progresso');
        }
        
        return json;
    }

    // Avvia il polling del progresso. onUpdate(progress), onDone(progress), onError(err)
    pollProgress(endpoint, options) {
        const opts = Object.assign({
            intervalMs: 1000,
            onUpdate: function(){},
            onDone: function(){},
            onError: function(){} 
        }, options || {});

        if (this._interval) clearInterval(this._interval);

        this._interval = setInterval(async () => {
            try {
                const pr = await this.getProgress(endpoint);
                if (pr.status === 'ok' && pr.progress) {
                    const p = pr.progress;
                    opts.onUpdate(p);
                    if (p.status === 'done' && Number(p.percent) === 100) {
                        clearInterval(this._interval);
                        this._interval = null;
                        opts.onDone(p);
                    }
                } else if (pr.status === 'not_found') {
                    // non ancora disponibile, continua polling
                } else if (pr.status === 'error') {
                    clearInterval(this._interval);
                    this._interval = null;
                    opts.onError(new Error(pr.message || 'Errore sconosciuto progresso'));
                }
            } catch (err) {
                clearInterval(this._interval);
                this._interval = null;
                opts.onError(err);
            }
        }, opts.intervalMs);

        return () => { if (this._interval) { clearInterval(this._interval); this._interval = null; } };
    }

    // Richiede l'estrazione del file zip lato server
    async extract(file) {
        const res = await fetch(this.controllerUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                ajax: 1,
                action: "extractCatalogZipAction",
                file: file
            })
        });

        const json = await res.json();

        if (json.status !== 'ok') {
            throw new Error(json.message || 'Errore estrazione zip');
        }

        return json.files || [];
    }

    // Flusso completo: start -> poll -> extract
    async runFlow(endpoint, options) {
        console.log("RUNFLOW", endpoint, options);

        const opts = Object.assign({
            onUpdate: function(){},
            onDone: function(){},
            onError: function(){},
            intervalMs: 1000
        }, options || {});

        try {
            const file = await this.start(endpoint);
            
            return new Promise((resolve, reject) => {
                this.pollProgress(endpoint, {
                    intervalMs: opts.intervalMs,
                    onUpdate: opts.onUpdate,
                    onDone: async (progress) => {
                        try {
                            const files = await this.extract(progress.file || file);
                            opts.onDone({ progress: progress, files: files });
                            resolve({ progress: progress, files: files });
                        } catch (err) {
                            opts.onError(err);
                            reject(err);
                        }
                    },
                    onError: (err) => { opts.onError(err); reject(err); }
                });
            });
        } catch (err) {
            opts.onError(err);
            throw err;
        }
    }
}