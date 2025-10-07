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

class TyreWeight
{
    private $width;        // Larghezza in mm
    private $aspectRatio;  // Rapporto altezza/larghezza in %
    private $diameter;     // Diametro cerchio in pollici

    private $specificWeight = 600; // Peso specifico del caucciù in Kg/m³

    public function __construct($width, $aspectRatio, $diameter)
    {
        $this->width = (float) $width;
        $this->aspectRatio = (float) $aspectRatio;
        $this->diameter = (float) $diameter;
    }

    /**
     * Calcola il peso da un codice pneumatico (es: 215/65/R16)
     * @param string $code Codice pneumatico
     * @return float Peso in Kg
     */
    public static function calcByCode($code)
    {
        // Rimuovo eventuali spazi e converto in maiuscolo
        $code = strtoupper(trim($code));

        // Rimuovo la R se presente
        $code = str_replace('R', '', $code);

        // ES: 215/65/16
        $parts = explode("/", $code);

        if (count($parts) !== 3) {
            throw new \Exception("Formato codice pneumatico non valido. Formato atteso: 215/65/R16");
        }

        $width = $parts[0];
        $aspectRatio = $parts[1];
        $diameter = $parts[2];

        return (new TyreWeight($width, $aspectRatio, $diameter))->calc();
    }

    /**
     * Calcola il peso del pneumatico
     * Formula: Volume toroide × peso specifico
     * Volume toroide = π² × D × r²
     * Dove:
     * - r = raggio sezione (metà larghezza in metri)
     * - D = diametro medio del toroide in metri
     * 
     * @return float Peso in Kg
     */
    public function calc()
    {
        // Calcolo l'altezza del fianco in mm
        $sidewallHeight = ($this->width * $this->aspectRatio) / 100;

        // Converto il diametro del cerchio da pollici a mm
        $rimDiameterMm = $this->diameter * 25.4;

        // Diametro medio del toroide (cerchio + 2 fianchi) in mm
        $meanDiameter = $rimDiameterMm + (2 * $sidewallHeight);

        // Raggio della sezione (metà della larghezza del pneumatico) in mm
        $sectionRadius = $this->width / 2;

        // Converto tutto in metri per il calcolo del volume
        $meanDiameterM = $meanDiameter / 1000;
        $sectionRadiusM = $sectionRadius / 1000;

        // Volume del toroide in m³
        // V = π² × D × r²
        $volume = pow(M_PI, 2) * $meanDiameterM * pow($sectionRadiusM, 2);

        // Peso = Volume × Peso specifico
        $weight = ($volume * $this->specificWeight) / 4;

        return round($weight, 3);
    }

    /**
     * Restituisce informazioni dettagliate sul calcolo
     * @return array
     */
    public function getDetails()
    {
        $sidewallHeight = ($this->width * $this->aspectRatio) / 100;
        $rimDiameterMm = $this->diameter * 25.4;
        $meanDiameter = $rimDiameterMm + (2 * $sidewallHeight);
        $sectionRadius = $this->width / 2;
        $meanDiameterM = $meanDiameter / 1000;
        $sectionRadiusM = $sectionRadius / 1000;
        $volume = pow(M_PI, 2) * $meanDiameterM * pow($sectionRadiusM, 2);

        return [
            'width_mm' => $this->width,
            'aspect_ratio' => $this->aspectRatio,
            'rim_diameter_inches' => $this->diameter,
            'sidewall_height_mm' => round($sidewallHeight, 2),
            'rim_diameter_mm' => round($rimDiameterMm, 2),
            'mean_diameter_mm' => round($meanDiameter, 2),
            'section_radius_mm' => round($sectionRadius, 2),
            'volume_m3' => round($volume, 6),
            'specific_weight_kg_m3' => $this->specificWeight,
            'weight_kg' => $this->calc()
        ];
    }
}
