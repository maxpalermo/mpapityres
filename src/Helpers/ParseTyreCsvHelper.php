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

namespace MpSoft\MpApiTyres\Helpers;

class ParseTyreCsvHelper extends DependencyHelper
{
    protected $sizeInBytes;
    protected $sizeHumanReading;

    protected static $totalRows = 0;

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

    public static function humanFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }

    public static function getHumanTiming($timeInSeconds)
    {
        $seconds = (int) round($timeInSeconds);
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        $parts = [];
        if ($hours > 0) {
            $parts[] = $hours . ' or' . ($hours > 1 ? 'e' : 'a');
        }
        if ($minutes > 0) {
            $parts[] = $minutes . ' minut' . ($minutes > 1 ? 'i' : 'o');
        }
        if ($secs > 0 || empty($parts)) {
            $parts[] = $secs . ' second' . ($secs > 1 ? 'i' : '');
        }
        return implode(' ', $parts);
    }

    public function getHumanReadingSize()
    {
        return $this->sizeHumanReading;
    }

    public function getFileSizeInBytes()
    {
        return $this->sizeInBytes;
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

    public static function setTotalRows($totalRows)
    {
        self::$totalRows = $totalRows;
    }

    public static function resetTotalRows()
    {
        self::$totalRows = 0;
    }

    public static function getTotalRows()
    {
        return self::$totalRows;
    }
}