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

class SpeedIndex
{
    protected static $speedIndexBySymbol = [
        'A1' => ['kmh' => 5, 'mph' => 3.11],
        'A2' => ['kmh' => 10, 'mph' => 6.21],
        'A3' => ['kmh' => 15, 'mph' => 9.32],
        'A4' => ['kmh' => 20, 'mph' => 12.43],
        'A5' => ['kmh' => 25, 'mph' => 15.53],
        'A6' => ['kmh' => 30, 'mph' => 18.64],
        'A7' => ['kmh' => 35, 'mph' => 21.75],
        'A8' => ['kmh' => 40, 'mph' => 24.85],
        'B' => ['kmh' => 50, 'mph' => 31.07],
        'C' => ['kmh' => 60, 'mph' => 37.28],
        'D' => ['kmh' => 65, 'mph' => 40.39],
        'E' => ['kmh' => 70, 'mph' => 43.5],
        'F' => ['kmh' => 80, 'mph' => 49.71],
        'G' => ['kmh' => 90, 'mph' => 55.92],
        'J' => ['kmh' => 100, 'mph' => 62.14],
        'K' => ['kmh' => 110, 'mph' => 68.35],
        'L' => ['kmh' => 120, 'mph' => 74.56],
        'M' => ['kmh' => 130, 'mph' => 80.78],
        'N' => ['kmh' => 140, 'mph' => 86.99],
        'P' => ['kmh' => 150, 'mph' => 93.21],
        'Q' => ['kmh' => 160, 'mph' => 99.42],
        'R' => ['kmh' => 170, 'mph' => 105.63],
        'S' => ['kmh' => 180, 'mph' => 111.85],
        'T' => ['kmh' => 190, 'mph' => 118.06],
        'U' => ['kmh' => 200, 'mph' => 124.27],
        'H' => ['kmh' => 210, 'mph' => 130.49],
        'V' => ['kmh' => 240, 'mph' => 149.13],
        'ZR' => ['kmh' => '>240', 'mph' => '>149.13'],
        'W' => ['kmh' => 270, 'mph' => 167.77],
        'Y' => ['kmh' => 300, 'mph' => 186.41]
    ];

    public static function getSpeedIndexArray()
    {
        return self::$speedIndexBySymbol;
    }

    public static function getSpeedIndexBySymbol($symbol)
    {
        $list = self::$speedIndexBySymbol;
        $elem = $list[$symbol] ?? false;

        if ($elem) {
            return [
                'symbol' => $symbol,
                'kmh' => $elem['kmh'],
                'mph' => $elem['mph']
            ];
        }

        return false;
    }

    public static function getDropDownSelect()
    {
        $speedSymbolsCompact = [
            'A1' => 'A1 (5 km/h - 3 mph)',
            'A2' => 'A2 (10 km/h - 6 mph)',
            'A3' => 'A3 (15 km/h - 9 mph)',
            'A4' => 'A4 (20 km/h - 12 mph)',
            'A5' => 'A5 (25 km/h - 16 mph)',
            'A6' => 'A6 (30 km/h - 19 mph)',
            'A7' => 'A7 (35 km/h - 22 mph)',
            'A8' => 'A8 (40 km/h - 25 mph)',
            'B' => 'B (50 km/h - 31 mph)',
            'C' => 'C (60 km/h - 37 mph)',
            'D' => 'D (65 km/h - 40 mph)',
            'E' => 'E (70 km/h - 44 mph)',
            'F' => 'F (80 km/h - 50 mph)',
            'G' => 'G (90 km/h - 56 mph)',
            'J' => 'J (100 km/h - 62 mph)',
            'K' => 'K (110 km/h - 68 mph)',
            'L' => 'L (120 km/h - 75 mph)',
            'M' => 'M (130 km/h - 81 mph)',
            'N' => 'N (140 km/h - 87 mph)',
            'P' => 'P (150 km/h - 93 mph)',
            'Q' => 'Q (160 km/h - 99 mph)',
            'R' => 'R (170 km/h - 106 mph)',
            'S' => 'S (180 km/h - 112 mph)',
            'T' => 'T (190 km/h - 118 mph)',
            'U' => 'U (200 km/h - 124 mph)',
            'H' => 'H (210 km/h - 130 mph)',
            'V' => 'V (240 km/h - 149 mph)',
            'ZR' => 'ZR (>240 km/h - >149 mph)',
            'W' => 'W (270 km/h - 168 mph)',
            'Y' => 'Y (300 km/h - 186 mph)'
        ];

        return $speedSymbolsCompact;
    }
}
