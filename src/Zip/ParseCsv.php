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

class ParseCsv
{
    const PROGRESS_FOLDER = 'runtime/progress/';

    /**
     * Ritorna lo stato del progresso di parsing per un dato file CSV.
     *
     * @param string $csvPath
     * @param string|null $progressId
     * @return array|null
     */
    public function getCsvParseProgress(string $csvPath, ?string $progressId = null): ?array
    {
        $key = $progressId ? ('parse_' . $progressId) : $this->getParseProgressKeyFromFile($csvPath);
        $path = $this->getParseProgressPath($key);
        if (!is_file($path)) {
            $this->writeParseProgress($key, $csvPath);
        }
        $json = @file_get_contents($path);
        if ($json === false) {
            return null;
        }
        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    }

    public function getParseProgressKeyFromFile(string $csvPath): string
    {
        return 'parse_' . sha1($csvPath);
    }

    public function getParseProgressPath(string $progressKey): string
    {
        return _PS_MODULE_DIR_ . 'mpapityres/' . self::PROGRESS_FOLDER . $progressKey . '.json';
    }

    /**
     * Legge un CSV molto grande a stream e genera array associativi per ogni riga.
     * Salva il progresso di parsing in runtime/progress/parse_{sha1(path)}.json
     *
     * @param string $csvPath Percorso del file CSV da leggere
     * @param string $delimiter Delimitatore (default '|')
     * @param string|null $progressId Identificatore custom del progresso (opzionale)
     * @return \Generator Yields: array associativo per riga
     * @throws \RuntimeException
     */
    public function parseCSV(string $csvPath, string $delimiter = '|', ?string $progressId = null): \Generator
    {
        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            throw new \RuntimeException('Impossibile aprire il file CSV: ' . $csvPath);
        }

        $totalSize = @filesize($csvPath) ?: 0;
        $progressKey = $progressId ? ('parse_' . $progressId) : $this->getParseProgressKeyFromFile($csvPath);
        $this->writeParseProgressPayload($progressKey, [
            'status' => 'starting',
            'file' => basename($csvPath),
            'total_bytes' => $totalSize,
            'read_bytes' => 0,
            'percent' => 0,
            'rows' => 0,
            'updated_at' => date('c'),
        ]);

        // Legge header (prima riga)
        $header = fgetcsv($handle, 0, $delimiter);
        if ($header === false || count($header) === 0) {
            fclose($handle);
            throw new \RuntimeException('Header CSV non valido o vuoto.');
        }

        $rows = 0;
        $lastUpdate = 0;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            // Salta righe vuote
            if (count($row) === 1 && trim($row[0]) === '') {
                continue;
            }
            // Allinea colonne: se mismatch, riempi con null
            $assoc = array_combine($header, $row);
            if ($assoc === false) {
                $assoc = [];
                foreach ($header as $i => $col) {
                    $assoc[$col] = $row[$i] ?? null;
                }
            }
            $rows++;

            // Aggiorna stato di progresso ogni 200ms
            $now = microtime(true);
            if (($now - $lastUpdate) >= 0.2) {
                $pos = ftell($handle);
                $percent = ($totalSize > 0 && $pos > 0) ? (int) floor(($pos / $totalSize) * 100) : 0;
                $this->writeParseProgressPayload($progressKey, [
                    'status' => 'parsing',
                    'file' => basename($csvPath),
                    'total_bytes' => $totalSize,
                    'read_bytes' => $pos,
                    'percent' => $percent,
                    'rows' => $rows,
                    'updated_at' => date('c'),
                ]);
                $lastUpdate = $now;
            }

            yield $assoc;
        }

        // Completa
        $finalPos = ftell($handle);
        fclose($handle);
        $this->writeParseProgressPayload($progressKey, [
            'status' => 'done',
            'file' => basename($csvPath),
            'total_bytes' => $totalSize,
            'read_bytes' => $finalPos,
            'percent' => 100,
            'rows' => $rows,
            'updated_at' => date('c'),
        ]);
    }

    public function writeParseProgressPayload(string $progressKey, array $payload): void
    {
        $path = $this->getParseProgressPath($progressKey);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($path, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        @chmod($path, 0775);
    }

    public function clearParseProgress(string $progressKey): void
    {
        $path = $this->getParseProgressPath($progressKey);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function clearParseState(string $progressKey): void
    {
        $path = $this->getParseStatePath($progressKey);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Stato parsing incrementale (header, offset, cleared flag, rows_total)
     */
    public function getParseStatePath(string $progressKey): string
    {
        return _PS_MODULE_DIR_ . 'mpapityres/' . self::PROGRESS_FOLDER . $progressKey . '.state.json';
    }

    public function readParseState(string $progressKey): ?array
    {
        $path = $this->getParseStatePath($progressKey);
        if (!is_file($path))
            return null;
        $json = @file_get_contents($path);
        if ($json === false)
            return null;
        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    }

    public function writeParseState(string $progressKey, array $state): void
    {
        $path = $this->getParseStatePath($progressKey);
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($path, json_encode($state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function writeParseProgress($progressKey, $csvPath, $status = 'starting', $total_bytes = 0, $read_bytes = 0, $percent = 0, $rows = 0, $updated_at = null)
    {
        if (!$updated_at) {
            $updated_at = date('c');
        }

        if (!$total_bytes) {
            $total_bytes = (@filesize($csvPath) ?: 0);
        }

        $payload = [
            'status' => $status,
            'file' => basename($csvPath),
            'total_bytes' => $total_bytes,
            'read_bytes' => $read_bytes,
            'percent' => $percent,
            'rows' => $rows,
            'updated_at' => $updated_at,
        ];
        $this->writeParseProgressPayload($progressKey, $payload);
    }
}