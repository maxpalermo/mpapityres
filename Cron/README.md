# Script Cron per PrestaShop 8

Questa cartella contiene gli script PHP eseguibili da CLI per operazioni automatizzate nel framework PrestaShop 8.

## 📋 Script Disponibili

- **`cron-template.php`** - Template base per creare nuovi script cron
- **`create-pfu.php`** - Script per aggiornamento PFU
- **`download-csv.php`** - Script per scaricamento CSV

## 🚀 Esecuzione degli Script

### Esecuzione Diretta da Terminale

Dalla cartella `Cron`:

```bash
php nome_script.php
```

Esempio:
```bash
php create-pfu.php
php download-csv.php
```

### Esecuzione con Output su File di Log

Per salvare l'output (stdout e stderr) in un file di log:

```bash
php nome_script.php >> logs/cron.log 2>&1
```

Esempio completo:
```bash
php create-pfu.php >> logs/cron.log 2>&1
```

> **Nota**: Assicurati che la cartella `logs/` esista, altrimenti creala con `mkdir -p logs`

### Esecuzione Automatica con Crontab

Per schedulare l'esecuzione automatica degli script, aggiungi una entry al crontab:

```bash
# Apri l'editor crontab
crontab -e
```

Aggiungi una riga come questa:

```cron
# Esegui ogni 30 minuti
*/30 * * * * cd /home/massimiliano/htdocs/tyre24/modules/mpapityres/Cron && php create-pfu.php >> logs/cron.log 2>&1

# Esegui ogni giorno alle 2:00 AM
0 2 * * * cd /home/massimiliano/htdocs/tyre24/modules/mpapityres/Cron && php download-csv.php >> logs/cron.log 2>&1

# Esegui ogni ora
0 * * * * cd /home/massimiliano/htdocs/tyre24/modules/mpapityres/Cron && php nome_script.php >> logs/cron.log 2>&1
```

## 📅 Sintassi Crontab

```
* * * * * comando
│ │ │ │ │
│ │ │ │ └─── Giorno della settimana (0-7, 0 e 7 = Domenica)
│ │ │ └───── Mese (1-12)
│ │ └─────── Giorno del mese (1-31)
│ └───────── Ora (0-23)
└─────────── Minuto (0-59)
```

### Esempi Comuni

| Espressione | Descrizione |
|-------------|-------------|
| `*/5 * * * *` | Ogni 5 minuti |
| `*/15 * * * *` | Ogni 15 minuti |
| `*/30 * * * *` | Ogni 30 minuti |
| `0 * * * *` | Ogni ora |
| `0 0 * * *` | Ogni giorno a mezzanotte |
| `0 2 * * *` | Ogni giorno alle 2:00 AM |
| `0 0 * * 0` | Ogni domenica a mezzanotte |
| `0 0 1 * *` | Il primo giorno di ogni mese |

## 🔧 Requisiti

- PHP CLI installato
- Accesso al filesystem di PrestaShop
- Permessi di esecuzione sui file PHP
- Cartella `logs/` con permessi di scrittura (se usi il logging su file)

## 📝 Note Importanti

1. **Percorsi Assoluti**: Usa sempre `cd` nel crontab per assicurarti di essere nella directory corretta
2. **Memory Limit**: Gli script hanno `memory_limit` impostato a 2GB
3. **Modalità Dev**: Gli script girano con `_PS_MODE_DEV_` attivo
4. **Employee Context**: Viene usato l'employee con ID 1 per il contesto PrestaShop
5. **Solo CLI**: Gli script verificano di essere eseguiti da CLI e non via web

## 🐛 Debug

Per vedere l'output in tempo reale:

```bash
php nome_script.php
```

Per verificare gli ultimi log:

```bash
tail -f logs/cron.log
```

Per verificare i cron attivi:

```bash
crontab -l
```

## 📚 Creare un Nuovo Script

Usa `cron-template.php` come base per creare nuovi script cron. Copia il template e modifica la sezione del codice principale mantenendo la struttura di inizializzazione di PrestaShop.
