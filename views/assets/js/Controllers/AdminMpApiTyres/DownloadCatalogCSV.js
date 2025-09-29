document.addEventListener("DOMContentLoaded", async () => {
    // Wiring per Download ZIP con FetchCsvJson
    try {
        const btnZip = document.getElementById("btn-download-zip");
        const inputEndpoint = document.getElementById("zip-endpoint");
        const zip_bar = document.getElementById("zip-progress-bar");
        const zip_text = document.getElementById("zip-progress-text");

        if (btnZip && inputEndpoint && zip_bar && zip_text) {
            const downloader = new FetchCsvJson(CronControllerURL);
            btnZip.addEventListener("click", async () => {
                const endpoint = (inputEndpoint.value || "").trim();
                if (!endpoint) {
                    alert("Inserisci un endpoint ZIP valido");
                    return;
                }
                zip_bar.style.width = "0%";
                zip_bar.textContent = "0%";
                zip_text.innerHTML = `
                    <div class="alert alert-info">
                        <p>Avvio download…</p>
                    </div>
                `;

                downloader.runFlow(endpoint, {
                    intervalMs: 1000,
                    onUpdate: (p) => {
                        const percent = Number(p.percent) || 0;
                        zip_bar.style.width = percent + "%";
                        zip_bar.textContent = percent + "%";
                        zip_text.innerHTML = `
                            <div class="alert alert-info">
                                <p>Scaricati ${p.downloaded || 0} di ${p.download_total || 0} bytes</p>
                            </div>
                        `;
                    },
                    onDone: ({ progress, files }) => {
                        zip_bar.style.width = "100%";
                        zip_bar.textContent = "100%";
                        zip_text.innerHTML = `
                            <div class="alert alert-success">
                                <p>Download completato ed estrazione eseguita</p>
                            </div>
                        `;
                        console.log("Estrazione completata", files);
                    },
                    onError: (err) => {
                        zip_text.innerHTML = `
                            <div class="alert alert-danger">
                                <p>Errore: ${err && err.message ? err.message : err}</p>
                            </div>
                        `;
                    },
                });
            });
        }
    } catch (e) {
        console.error(e);
    }

    // Wiring per Parsing CSV (AJAX semplice)
    try {
        const btnParse = document.getElementById("btn-parse-csv");
        const btnFillLatest = document.getElementById("btn-fill-latest-csv");
        const btnPause = document.getElementById("btn-pause-parse");
        const btnReset = document.getElementById("btn-reset-parse");
        const inputCsv = document.getElementById("csv-path");
        const csv_bar = document.getElementById("csv-progress-bar");
        const csv_text = document.getElementById("csv-progress-text");
        let parseInterval = null;
        let stepLoopTimer = null;
        let stepPaused = false;

        async function getCsvParseProgress(csvPath) {
            const fetchManager = new FetchManager(CronControllerURL, {
                ajax: 1,
                action: "getCsvParseProgressAction",
                csvPath: csvPath,
            });

            return await fetchManager.POST();
        }

        async function stepCsvParse(csvPath) {
            const fetchManager = new FetchManager(CronControllerURL, {
                ajax: 1,
                action: "stepCsvParseAction",
                csvPath: csvPath,
                delimiter: "|",
                timeBudgetMs: "1200",
                batchSize: "500",
                clearFirst: "1",
            });

            return await fetchManager.POST();
        }

        function startPolling(csvPath) {
            if (parseInterval) clearInterval(parseInterval);
            parseInterval = setInterval(async () => {
                try {
                    const pr = await getCsvParseProgress(csvPath);

                    if (pr.status === "ok" && pr.progress) {
                        const { percent, rows, read_bytes, total_bytes, status } = pr.progress;
                        const perc = Number(percent) || 0;
                        csv_bar.style.width = perc + "%";
                        csv_bar.textContent = perc + "%";
                        csv_text.innerHTML = `
                                    <div class="alert alert-info">
                                        <ul class="list">
                                            <li>Righe: ${rows || 0}</li>
                                            <li>Byte: ${read_bytes || 0}/${total_bytes || 0}</li>
                                            <li>Stato: ${status}</li>
                                        </ul>
                                    </div>
                                `;
                        if (status === "done" && perc === 100) {
                            clearInterval(parseInterval);
                            parseInterval = null;
                        }
                    } else if (pr.status === "not_found") {
                        csv_text.innerHTML = `
                                    <div class="alert alert-danger">
                                        <span class="alert-title">
                                            File ${csvPath} non trovato
                                        </span>
                                    </div>
                                `;

                        return false;
                    }
                } catch (err) {
                    console.error(err);
                    if (parseInterval) {
                        clearInterval(parseInterval);
                        parseInterval = null;
                    }
                }
            }, 1000);
        }

        // Utils di controllo parsing (scope Parsing CSV)
        function stopPolling() {
            if (parseInterval) {
                clearInterval(parseInterval);
                parseInterval = null;
            }
        }

        function stopStepLoop() {
            stepPaused = true;
            if (stepLoopTimer) {
                clearTimeout(stepLoopTimer);
                stepLoopTimer = null;
            }
        }

        async function resetCsvParsing(csvPath, clearRows) {
            const res = await fetch(CronControllerURL, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body: new URLSearchParams({
                    ajax: 1,
                    action: "resetCsvParseAction",
                    csvPath: csvPath,
                    clearRows: clearRows ? "1" : "0",
                }),
            });
            return await res.json();
        }

        if (btnFillLatest && inputCsv) {
            btnFillLatest.addEventListener("click", async () => {
                try {
                    const res = await fetch(CronControllerURL, {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded",
                        },
                        body: new URLSearchParams({
                            ajax: 1,
                            action: "getLatestExtractedCsvAction",
                        }),
                    });

                    const json = await res.json();

                    if (json.status === "ok" && json.file) {
                        inputCsv.value = json.file; // percorso relativo al modulo
                    } else {
                        zip_text.innerHTML = `
                            <div class="alert alert-danger">
                                <p>Nessun CSV trovato</p>
                            </div>
                        `;
                        return false;
                    }
                } catch (e) {
                    console.error(e);
                    zip_text.innerHTML = `
                        <div class="alert alert-danger">
                            <p>Errore nel recupero dell'ultimo CSV</p>
                        </div>
                    `;
                    return false;
                }
            });
        }

        if (btnParse && inputCsv && csv_bar && csv_text) {
            btnParse.addEventListener("click", async () => {
                const csvPath = (inputCsv.value || "").trim();
                if (!csvPath) {
                    alert("Inserisci un percorso CSV valido");
                    return;
                }
                csv_bar.style.width = "0%";
                csv_bar.textContent = "0%";
                csv_text.innerHTML = `
                            <div class="alert alert-info">
                                <p class="alert-content">
                                    Avvio parsing...
                                </p>
                            </div>
                        `;
                const resultEl = document.getElementById("csv-inserted-result");
                if (resultEl) {
                    resultEl.style.display = "none";
                    resultEl.textContent = "";
                }

                // Avvia polling del progresso
                startPolling(csvPath);

                // Avvia loop di step fino a completamento
                let stepDone = false;
                stepPaused = false;

                async function runStepLoop() {
                    if (stepDone || stepPaused) return;
                    try {
                        const resp = await stepCsvParse(csvPath);
                        if (resp.status === "done") {
                            stepDone = true;
                            if (resultEl && typeof resp.rows !== "undefined") {
                                resultEl.textContent = `Inserite ${resp.rows} righe`;
                                resultEl.style.display = "";
                            }
                        } else if (resp.status === "error") {
                            csv_text.innerHTML = `
                                <div class="alert alert-danger">
                                    <p>Errore: ${resp.message || "step parsing fallito"}</p>
                                </div>
                            `;
                            stepDone = true;
                            stopPolling();
                        }
                    } catch (e) {
                        csv_text.innerHTML = `
                            <div class="alert alert-danger">
                                <p>Errore: ${e && e.message ? e.message : e}</p>
                            </div>
                        `;

                        stepDone = true;
                        stopPolling();
                    }

                    if (!stepDone) {
                        stepLoopTimer = setTimeout(runStepLoop, 200); // breve pausa tra gli step
                    }
                }

                runStepLoop();
            });
        }

        if (btnPause) {
            btnPause.addEventListener("click", () => {
                stepPaused = true;
                stopPolling();
                stopStepLoop();
                csv_text.innerHTML = `
                    <div class="alert alert-warning">
                        <p>Parsing in pausa</p>
                    </div>
                `;
            });
        }

        if (btnReset && inputCsv) {
            btnReset.addEventListener("click", async () => {
                const csvPath = (inputCsv.value || "").trim();
                if (!csvPath) {
                    alert("Inserisci un percorso CSV valido");
                    return;
                }
                if (!confirm("Confermi il reset del parsing e l'eliminazione delle righe CSV?")) return;
                stepPaused = true;
                stopPolling();
                stopStepLoop();
                try {
                    const resp = await resetCsvParsing(csvPath, true);
                    if (resp.status === "ok") {
                        csv_bar.style.width = "0%";
                        csv_bar.textContent = "0%";
                        csv_text.innerHTML = `
                            <div class="alert alert-info">
                                <p>Parsing resettato</p>
                            </div>
                        `;
                        const resultEl = document.getElementById("csv-inserted-result");
                        if (resultEl) {
                            resultEl.style.display = "none";
                            resultEl.textContent = "";
                        }
                    } else {
                        alert("Errore nel reset: " + (resp.message || ""));
                    }
                } catch (e) {
                    alert("Errore nel reset: " + (e && e.message ? e.message : e));
                }
            });
        }
    } catch (e) {
        console.error(e);
    }
});
