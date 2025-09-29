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

namespace MpSoft\MpApiTyres\Zip;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class DownloadZip
{
    const ZIP_FOLDER = 'downloads/zip/';
    const CSV_FOLDER = 'downloads/csv/';
    const PROGRESS_FOLDER = 'runtime/progress/';

    /**
     * Scarica un file ZIP di grandi dimensioni usando Guzzle e traccia il progresso su file JSON.
     *
     * @param string $endpoint URL del file ZIP da scaricare
     * @return string Nome del file ZIP salvato (basename)
     * @throws \Exception
     */
    public function downloadZip($endpoint)
    {
        $moduleBase = _PS_MODULE_DIR_ . 'mpapityres/';
        $downloadZipFolder = $moduleBase . self::ZIP_FOLDER;
        $progressFolder = $moduleBase . self::PROGRESS_FOLDER;

        // Assicura che le cartelle esistano
        if (!is_dir($downloadZipFolder) && !@mkdir($downloadZipFolder, 0775, true) && !is_dir($downloadZipFolder)) {
            throw new \Exception('Impossibile creare la cartella di download: ' . $downloadZipFolder);
        }
        if (!is_dir($progressFolder) && !@mkdir($progressFolder, 0775, true) && !is_dir($progressFolder)) {
            throw new \Exception('Impossibile creare la cartella di progresso: ' . $progressFolder);
        }

        // Chiave e percorsi gestione progresso/lock
        $progressKey = $this->getProgressKey($endpoint);
        $lockPath = $this->getLockPath($progressKey);
        $progressPath = $this->getProgressPath($progressKey);

        // Se c'è un lock attivo recente, impedisci un download concorrente
        if (is_file($lockPath)) {
            $age = time() - @filemtime($lockPath);
            if ($age < 3600) { // 1 ora come margine di sicurezza
                throw new \Exception('Un download è già in corso per questo endpoint. Riprova più tardi.');
            } else {
                // lock vecchio: rimuovilo
                @unlink($lockPath);
            }
        }

        // Crea lock file
        @file_put_contents($lockPath, json_encode(['pid' => getmypid(), 'time' => time()]));

        // Pulisci la cartella ZIP prima di iniziare un nuovo download
        $this->clearZipFolder($downloadZipFolder);

        // Pulisci eventuale file di progresso precedente per questo endpoint
        $this->clearProgressForKey($progressKey);

        // Determina il nome file
        $basename = basename(parse_url($endpoint, PHP_URL_PATH)) ?: ('catalog_' . date('Ymd_His') . '.zip');
        // Evita collisioni
        $targetPath = $downloadZipFolder . $basename;
        if (file_exists($targetPath)) {
            $basename = pathinfo($basename, PATHINFO_FILENAME) . '_' . date('His') . '.zip';
            $targetPath = $downloadZipFolder . $basename;
        }

        $this->writeProgress($progressKey, [
            'status' => 'starting',
            'download_total' => 0,
            'downloaded' => 0,
            'percent' => 0,
            'file' => $basename,
            'updated_at' => date('c'),
        ]);

        $client = new Client([
            'http_errors' => true,
            'verify' => false, // se necessario in ambienti dev
            'timeout' => 0, // senza limite per file grandi
            'connect_timeout' => 30,
        ]);

        try {
            $client->request('GET', $endpoint, [
                'sink' => $targetPath,
                'progress' => function ($downloadTotal, $downloadedBytes, $uploadTotal, $uploadedBytes) use ($progressKey, $basename) {
                    $percent = 0;
                    if ($downloadTotal > 0) {
                        $percent = (int) floor(($downloadedBytes / $downloadTotal) * 100);
                    }
                    $this->writeProgress($progressKey, [
                        'status' => 'downloading',
                        'download_total' => (int) $downloadTotal,
                        'downloaded' => (int) $downloadedBytes,
                        'percent' => $percent,
                        'file' => $basename,
                        'updated_at' => date('c'),
                    ]);
                },
            ]);

            // Completo
            $this->writeProgress($progressKey, [
                'status' => 'done',
                'download_total' => filesize($targetPath) ?: 0,
                'downloaded' => filesize($targetPath) ?: 0,
                'percent' => 100,
                'file' => $basename,
                'updated_at' => date('c'),
            ]);

            return $basename;
        } catch (GuzzleException $e) {
            $this->writeProgress($progressKey, [
                'status' => 'error',
                'message' => $e->getMessage(),
                'percent' => 0,
                'updated_at' => date('c'),
            ]);
            throw new \Exception('Errore durante il download: ' . $e->getMessage());
        } catch (\Throwable $e) {
            $this->writeProgress($progressKey, [
                'status' => 'error',
                'message' => $e->getMessage(),
                'percent' => 0,
                'updated_at' => date('c'),
            ]);
            throw $e;
        } finally {
            // Rimuovi il lock in ogni caso
            @unlink($lockPath);
        }
    }

    /**
     * Elimina tutti i file presenti nella cartella ZIP (non rimuove sottocartelle).
     *
     * @param string $zipFolderPath
     * @return void
     */
    protected function clearZipFolder(string $zipFolderPath): void
    {
        if (!is_dir($zipFolderPath)) {
            return;
        }
        // Aggiunge la slash finale se manca
        $folder = rtrim($zipFolderPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $files = glob($folder . '*.zip');
        if (!is_array($files)) {
            return;
        }
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * Ritorna il percorso del lock file per un dato progressKey
     */
    protected function getLockPath(string $progressKey): string
    {
        return _PS_MODULE_DIR_ . 'mpapityres/' . self::PROGRESS_FOLDER . $progressKey . '.lock';
    }

    /**
     * Elimina il file di progresso relativo ad un determinato key
     */
    protected function clearProgressForKey(string $progressKey): void
    {
        $path = $this->getProgressPath($progressKey);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Elimina tutti i file di progresso (*.json) nella cartella PROGRESS_FOLDER
     */
    protected function clearAllProgressFiles(): void
    {
        $folder = _PS_MODULE_DIR_ . 'mpapityres/' . self::PROGRESS_FOLDER;
        if (!is_dir($folder)) {
            return;
        }
        $files = glob(rtrim($folder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.json');
        if (!is_array($files)) {
            return;
        }
        foreach ($files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    /**
     * Estrae il file ZIP nella cartella CSV del modulo.
     *
     * @param string $fileName Basename del file ZIP da estrarre (presente in ZIP_FOLDER)
     * @return array Lista file estratti
     * @throws \Exception
     */
    public function extractZip($fileName)
    {
        $moduleBase = _PS_MODULE_DIR_ . 'mpapityres/';
        $zipPath = $moduleBase . self::ZIP_FOLDER . basename($fileName);
        $extractZipFolder = $moduleBase . self::CSV_FOLDER;

        if (!file_exists($zipPath)) {
            throw new \Exception('File ZIP non trovato: ' . $zipPath);
        }
        if (!is_dir($extractZipFolder) && !@mkdir($extractZipFolder, 0775, true) && !is_dir($extractZipFolder)) {
            throw new \Exception('Impossibile creare la cartella di estrazione: ' . $extractZipFolder);
        }

        $zip = new \ZipArchive();
        $res = $zip->open($zipPath);
        if ($res !== true) {
            throw new \Exception('Impossibile aprire lo ZIP. Codice: ' . $res);
        }

        // Estrae tutto
        if (!$zip->extractTo($extractZipFolder)) {
            $zip->close();
            throw new \Exception('Errore durante l\'estrazione dello ZIP.');
        }
        $zip->close();

        // Raccoglie la lista file estratti
        $extracted = [];
        $dirIt = new \RecursiveDirectoryIterator($extractZipFolder, \FilesystemIterator::SKIP_DOTS);
        $it = new \RecursiveIteratorIterator($dirIt, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($it as $item) {
            if ($item->isFile()) {
                $extracted[] = str_replace($moduleBase, '', $item->getPathname());
            }
        }

        return $extracted;
    }

    /**
     * Restituisce lo stato di progresso dal file JSON, dato l'endpoint originale.
     * Utile per essere esposto via controller AJAX.
     *
     * @param string $endpoint
     * @return array|null
     */
    public function getCsvDownloadProgress(string $endpoint): ?array
    {
        $key = $this->getProgressKey($endpoint);
        $path = $this->getProgressPath($key);
        if (!is_file($path)) {
            return null;
        }
        $json = @file_get_contents($path);
        if ($json === false) {
            return null;
        }
        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    }

    protected function getProgressKey(string $endpoint): string
    {
        return sha1($endpoint);
    }

    protected function getProgressPath(string $progressKey): string
    {
        return _PS_MODULE_DIR_ . 'mpapityres/' . self::PROGRESS_FOLDER . $progressKey . '.json';
    }

    protected function writeProgress(string $progressKey, array $payload): void
    {
        $path = $this->getProgressPath($progressKey);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($path, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}