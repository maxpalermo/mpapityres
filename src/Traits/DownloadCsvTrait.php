<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Massimiliano Palermo <maxx.palermo@gmail.com>
 * @copyright Since 2016 Massimiliano Palermo
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace MpSoft\MpApiTyres\Traits;

trait DownloadCsvTrait
{
    public const CSV_PATH = _PS_UPLOAD_DIR_ . 'CSV/';
    public const CHUNK_PATH = _PS_UPLOAD_DIR_ . 'CSV/chunks';
    public const CSV_FILE = _PS_UPLOAD_DIR_ . 'CSV/tyres_mega.csv';
    public const CSV_ZIP = _PS_UPLOAD_DIR_ . 'CSV/tyres_mega.zip';
    public const CHUNK_SIZE = 10000;
    public const CATALOG_URL = 'https://tyre24.alzura.com/it/it/export/download-via-token/token/{token}/accountId/{accountId}/t/1/c/35/';

    /**
     * Parsea un CSV restituendo un generatore di array associativi (header => valore), ottimizzato per grandi file.
     *
     * @param string $filename Percorso del file CSV
     * @param string $delimiter Delimitatore di campo (default pipe "|")
     * @return \Generator Restituisce array associativi riga per riga
     * @throws \RuntimeException Se il file non può essere aperto o l'header non è valido
     */
    public function parse($filename, $delimiter = '|'): \Generator
    {
        $handle = fopen($filename, 'r');

        $this->sizeInBytes = filesize($filename);
        $this->sizeHumanReading = self::humanFileSize($this->sizeInBytes);

        if ($handle === false) {
            throw new \RuntimeException("Impossibile aprire il file CSV: $filename");
        }

        // Leggi la prima riga come header
        $header = fgetcsv($handle, 0, $delimiter);
        if ($header === false || count($header) === 0) {
            fclose($handle);
            throw new \RuntimeException('Header CSV non valido o vuoto.');
        }

        // Cicla sulle righe successive
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            // Salta righe vuote
            if (count($row) === 1 && trim($row[0]) === '') {
                continue;
            }
            // Combina header e valori
            $assoc = array_combine($header, $row);
            if ($assoc === false) {
                // Se le colonne non corrispondono, riempi con valori null
                $assoc = [];
                foreach ($header as $i => $col) {
                    $assoc[$col] = $row[$i] ?? null;
                }
            }
            yield $assoc;
        }

        fclose($handle);
    }

    /**
     * Legge un CSV e lo suddivide in più file JSON a chunk per evitare problemi di memoria.
     * Ogni file JSON conterrà fino a $chunkSize righe.
     *
     * @param string $csvPath Percorso file CSV
     * @param string $outputDir Directory di output per i file JSON
     * @param int $chunkSize Numero di righe per ogni file JSON
     * @param string $delimiter Delimitatore CSV (default '|')
     * @return int Numero di file JSON creati
     * @throws \RuntimeException
     */
    public function csvToJsonChunks($csvPath, $outputDir, $chunkSize = 10000, $delimiter = '|')
    {
        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Impossibile aprire il file CSV: $csvPath");
        }

        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
                fclose($handle);
                throw new \RuntimeException("Impossibile creare la directory di output: $outputDir");
            }
        }

        $header = fgetcsv($handle, 0, $delimiter);
        if ($header === false || count($header) === 0) {
            fclose($handle);
            throw new \RuntimeException('Header CSV non valido o vuoto.');
        }

        $chunk = [];
        $part = 1;
        $rowCount = 0;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($row) === 1 && trim($row[0]) === '') {
                continue;
            }
            $assoc = array_combine($header, $row);
            if ($assoc === false) {
                $assoc = [];
                foreach ($header as $i => $col) {
                    $assoc[$col] = $row[$i] ?? null;
                }
            }
            $chunk[] = $assoc;
            $rowCount++;

            if ($rowCount % $chunkSize === 0) {
                file_put_contents(
                    rtrim($outputDir, '/') . '/tyres_part_' . str_pad($part, 3, '0', STR_PAD_LEFT) . '.json',
                    json_encode($chunk, JSON_UNESCAPED_UNICODE)
                );
                $chunk = [];
                $part++;
            }
        }

        // Scrivi l’ultimo chunk se rimasto qualcosa
        if (count($chunk) > 0) {
            file_put_contents(
                rtrim($outputDir, '/') . '/tyres_part_' . str_pad($part, 3, '0', STR_PAD_LEFT) . '.json',
                json_encode($chunk, JSON_UNESCAPED_UNICODE)
            );
        }

        fclose($handle);
        self::setTotalRows($rowCount);
        return $part;
    }

    public function downloadCatalogOld()
    {
        $token = \Configuration::get('MPAPITYRES_TOKEN_TYRES');
        $accountId = \Configuration::get('MPAPITYRES_USER_NAME');

        $url = str_replace(['{token}', '{accountId}'], [$token, $accountId], self::CATALOG_URL);
        $zipFileName = self::CSV_ZIP;
        if ($this->downloadFile($url, $zipFileName)) {
            $this->unzipFile($zipFileName);
            if (file_exists(self::CSV_FILE)) {
                return true;
            }
        }

        throw new \Exception('File CSV non trovato');
    }

    protected function downloadFileOld($url, $outputPath)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);

        return file_put_contents($outputPath, $data);
    }

    public function downloadCatalog($progressId = null)
    {
        $token = \Configuration::get('MPAPITYRES_TOKEN_TYRES');
        $accountId = \Configuration::get('MPAPITYRES_USER_NAME');

        $url = str_replace(['{token}', '{accountId}'], [$token, $accountId], self::CATALOG_URL);
        $zipFileName = self::CSV_ZIP;

        // Genera un ID di progresso se non fornito
        if (!$progressId) {
            $progressId = 'catalog_' . time();
        }

        if ($this->downloadFile($url, $zipFileName, $progressId)) {
            $this->unzipFile($zipFileName);
            if (file_exists(self::CSV_FILE)) {
                return [
                    'success' => true,
                    'progress_id' => $progressId,
                    'message' => 'Download completato con successo'
                ];
            }
        }

        return [
            'success' => false,
            'progress_id' => $progressId,
            'message' => 'Errore durante il download del file'
        ];
    }

    /**
     * Scarica un file con monitoraggio del progresso
     * 
     * @param string $url URL del file da scaricare
     * @param string $outputPath Percorso dove salvare il file
     * @param string $progressId Identificativo univoco per questo download
     * @return bool Successo dell'operazione
     */
    protected function downloadFile($url, $outputPath, $progressId = null)
    {
        // Crea una directory temporanea per i file di progresso se non esiste
        $progressDir = _PS_UPLOAD_DIR_ . 'progress/';
        if (!is_dir($progressDir)) {
            mkdir($progressDir, 0777, true);
        }

        // File per salvare il progresso
        $progressFile = $progressId ? $progressDir . $progressId . '.json' : null;

        // Inizializza il file di progresso
        if ($progressFile) {
            file_put_contents($progressFile, json_encode([
                'total' => 0,
                'downloaded' => 0,
                'percent' => 0,
                'status' => 'starting',
                'start_time' => time()
            ]));
        }

        $ch = curl_init($url);

        // Imposta le opzioni di cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_NOPROGRESS, false); // Abilita il monitoraggio del progresso

        // Funzione di callback per monitorare il progresso
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($resource, $downloadSize, $downloaded, $uploadSize, $uploaded) use ($progressFile) {
            if ($downloadSize > 0 && $progressFile) {
                $progress = [
                    'total' => $downloadSize,
                    'downloaded' => $downloaded,
                    'percent' => round(($downloaded / $downloadSize) * 100, 2),
                    'status' => 'downloading',
                    'human_size' => self::humanFileSize($downloadSize),
                    'human_downloaded' => self::humanFileSize($downloaded),
                    'update_time' => time()
                ];

                // Aggiorna il file di progresso ogni 0.5 secondi per evitare troppe scritture su disco
                static $lastUpdate = 0;
                if (time() - $lastUpdate >= 0.5) {
                    file_put_contents($progressFile, json_encode($progress));
                    $lastUpdate = time();
                }
            }
        });

        // Esegui la richiesta
        $data = curl_exec($ch);

        // Gestisci gli errori
        if (curl_errno($ch)) {
            if ($progressFile) {
                file_put_contents($progressFile, json_encode([
                    'status' => 'error',
                    'message' => curl_error($ch),
                    'update_time' => time()
                ]));
            }
            curl_close($ch);
            return false;
        }

        curl_close($ch);

        // Salva il file
        $result = file_put_contents($outputPath, $data);

        // Aggiorna lo stato finale
        if ($progressFile) {
            file_put_contents($progressFile, json_encode([
                'total' => strlen($data),
                'downloaded' => strlen($data),
                'percent' => 100,
                'status' => 'completed',
                'human_size' => self::humanFileSize(strlen($data)),
                'human_downloaded' => self::humanFileSize(strlen($data)),
                'update_time' => time()
            ]));
        }

        return $result !== false;
    }

    /**
     * Endpoint per controllare il progresso del download
     * 
     * @param string $progressId ID del download
     * @return array Informazioni sul progresso
     */
    public function getDownloadProgress($progressId)
    {
        $progressFile = _PS_UPLOAD_DIR_ . 'progress/' . $progressId . '.json';

        if (file_exists($progressFile)) {
            $progress = json_decode(file_get_contents($progressFile), true);

            // Aggiungi informazioni aggiuntive
            if ($progress['status'] === 'downloading') {
                $progress['elapsed_time'] = time() - $progress['start_time'];

                // Calcola la velocità di download (bytes/secondo)
                if ($progress['elapsed_time'] > 0) {
                    $speed = $progress['downloaded'] / $progress['elapsed_time'];
                    $progress['speed'] = self::humanFileSize($speed) . '/s';

                    // Stima il tempo rimanente
                    if ($speed > 0 && $progress['total'] > 0) {
                        $remaining = ($progress['total'] - $progress['downloaded']) / $speed;
                        $progress['eta'] = self::getHumanTiming($remaining);
                    }
                }
            }

            return $progress;
        }

        return [
            'status' => 'not_found',
            'message' => 'Download non trovato'
        ];
    }

    protected function unzipFile($zipPath)
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === true) {
            // Cerca il file .csv dentro la cartella tmp
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $filename = $stat['name'];
                if (preg_match('#^tmp/.+\.csv$#i', $filename)) {
                    // Estrai il file in memoria
                    $csvContent = $zip->getFromIndex($i);
                    $dest = self::CSV_FILE;
                    file_put_contents($dest, $csvContent);
                    break;
                }
            }
            $zip->close();
        }
    }

}

