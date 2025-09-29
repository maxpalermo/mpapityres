# mpapityres
Integrazione Tyre24 per PrestaShop: scarica, importa e aggiorna il catalogo pneumatici, gestisce immagini/etichette, produttori e caratteristiche, con dashboard di controllo e cron front controller.

## Scopo del modulo
Questo modulo collega il tuo negozio PrestaShop al catalogo Tyre24, permettendo di:
- Importare/aggiornare prodotti pneumatici in modo massivo da API o CSV/ZIP.
- Creare/aggiornare produttori e loro immagini automaticamente.
- Assegnare categorie, caratteristiche e immagini/etichette prodotto.
- Eseguire job pianificati (cron) per download, import e reload immagini.
- Configurare categoria di default, regole IVA e ricarichi prezzo per fasce.

## Requisiti
- PrestaShop 1.7.x o 8.x
- PHP compatibile con la versione di PrestaShop in uso

## Installazione
1. Copia la cartella del modulo in `modules/mpapityres/`.
2. Dal Back Office: Moduli > Gestione moduli > mpapityres > Installa.
3. Apri la pagina del modulo e compila le impostazioni.

## Configurazione
La pagina impostazioni è servita dal controller admin `AdminMpApiTyres` e salva i seguenti parametri principali:

- API Tyre24
  - `host-api` (chiave costante: `MPAPITYRES_API_ENDPOINT`): host dell'API
  - `token-api` (chiave costante: `MPAPITYRES_API_TOKEN`): token di accesso
  - `cron-pause` minuti (chiave costante: `MPAPITYRES_CRON_TIME_BETWEEN_UPDATES`): tempo minimo tra due import completate

- Prodotti
  - `categoryBox` (chiave: `MPAPITYRES_DEFAULT_CATEGORY`): categoria di default per i prodotti
  - `id_tax_rules_group` (chiave: `MPAPITYRES_ID_TAX_RULES_GROUP`): gruppo regole IVA
  - `ricarico-c1`, `ricarico-c2`, `ricarico-c3`, `ricarico-default` (chiavi: `MPAPITYRES_RICARICO_*`): ricarichi prezzo per fasce

- CSV endpoint (opzionale, per workflow ZIP/CSV)
  - `MPAPITYRES_CSV_ENDPOINT`: URL template con placeholder `{token}` e `{accountId}`
  - `MPAPITYRES_CSV_ACCOUNT_ID`
  - `MPAPITYRES_CSV_TOKEN`
  - `MPAPITYRES_CURL_TIMEOUT` (timeout connessioni)

Le variabili sono gestite dalla classe `src/Configuration/ConfigValues.php`. Alcune chiavi di stato cron (offset, updated, status, date) sono mantenute per consentire job incrementali.

## Funzionalità principali
- Dashboard amministrativa con riepilogo prodotti, produttori e date ultimo job.
- Download catalogo (API o ZIP/CSV) con stato persistente.
- Import catalogo in `product`/`product_shop`, gestione categorie, stock, caratteristiche e immagini.
- Reload immagini prodotti mancanti.
- Strumento per creazione PFU da listino.

## Endpoint Cron (front controller)
Il front controller è `controllers/front/Cron.php`. Tutte le azioni accettano `action=<NomeAzione>` e opzionalmente `ajax=1` per risposta JSON.

Base URL di esempio:
```
{BASE_URL}/module/mpapityres/cron?action=<azione>&ajax=1
```

Azioni disponibili principali:
- `getCatalogAction` [parametri: `reset`=0|1]
- `importCatalogAction` [parametri: `reset`=0|1]
- `reloadImagesAction` [parametri: `reset`=0|1]
- `startCatalogDownloadAction` [parametri: `endpoint`]
- `getCatalogDownloadProgressAction` [parametri: `endpoint`]
- `extractCatalogZipAction` [parametri: `file`]
- `getCsvParseProgressAction` [parametri: `csvPath`, `progressId` opz]
- `resetCsvParseAction` [parametri: `csvPath`, `progressId` opz, `clearRows`=0|1]
- `stepCsvParseAction` [parametri: `csvPath`, `delimiter`='|', `progressId` opz, `timeBudgetMs`=1500, `batchSize`=500, `clearFirst`=1]
- `deleteProductsAction` [operazione di pulizia su categoria di default]

Note:
- Molte azioni mantengono lo stato tramite configurazioni `MPAPITYRES_CRON_*` per poter riprendere/elaborare a tempo.
- Alcune azioni restituiscono `status: DONE/DELETING/ERROR` e campi come `offset`, `updated`, `elapsed`.

## Flussi di lavoro
1) API
- `getCatalogAction` scarica dati nel buffer interno (tabella `product_tyre`, tipo 'API').
- `importCatalogAction` importa/aggiorna i prodotti PS dai dati scaricati.

2) ZIP/CSV
- `startCatalogDownloadAction` scarica lo ZIP dall'`endpoint` e salva su disco.
- `extractCatalogZipAction` estrae i file.
- `stepCsvParseAction` elabora il CSV a step con stato persistente.
- `importCatalogAction` importa i prodotti elaborati.

## Cartelle di lavoro
- `downloads/` e `runtime/` sono usate per file temporanei e sono ignorate da Git (vedi `.gitignore`).

## Sviluppo
- Controller Admin: `controllers/admin/AdminMpApiTyres.php`
- Front Controller Cron: `controllers/front/Cron.php`
- Logica import: `src/Catalog/ImportCatalog.php`, `src/Catalog/UpdateCatalog.php`
- Configurazioni: `src/Configuration/ConfigValues.php`

## Licenza
Academic Free License 3.0 (AFL-3.0). Vedi header dei file del modulo.

## Autore e contatti
- Autore: Massimiliano Palermo
- Email: maxx.palermo@gmail.com
