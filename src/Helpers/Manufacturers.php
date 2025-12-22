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

class Manufacturers
{
    public static function getManufacturers()
    {
        $manufacturers = array(
            array('id_manufacturer' => '1', 'name' => 'Studio Design'),
            array('id_manufacturer' => '2', 'name' => 'Graphic Corner'),
            array('id_manufacturer' => '3', 'name' => 'HANKOOK'),
            array('id_manufacturer' => '4', 'name' => 'PIRELLI'),
            array('id_manufacturer' => '5', 'name' => 'AVON'),
            array('id_manufacturer' => '6', 'name' => 'BRIDGESTONE'),
            array('id_manufacturer' => '7', 'name' => 'BFGOODRICH'),
            array('id_manufacturer' => '8', 'name' => 'PRINX'),
            array('id_manufacturer' => '9', 'name' => 'CEAT'),
            array('id_manufacturer' => '10', 'name' => 'ROCKBLADE'),
            array('id_manufacturer' => '11', 'name' => 'GOODTRIP'),
            array('id_manufacturer' => '12', 'name' => 'FALKEN'),
            array('id_manufacturer' => '13', 'name' => 'BERLIN TIRES'),
            array('id_manufacturer' => '14', 'name' => 'STAHLRAD OE QUALITÄT: ALCAR,KPZ,SÜDRAD,MWD'),
            array('id_manufacturer' => '15', 'name' => 'STAR PERFORMER'),
            array('id_manufacturer' => '16', 'name' => 'UNIROYAL'),
            array('id_manufacturer' => '17', 'name' => 'ALCAR'),
            array('id_manufacturer' => '18', 'name' => 'BARUM'),
            array('id_manufacturer' => '19', 'name' => 'KUMHO'),
            array('id_manufacturer' => '20', 'name' => 'NEXEN'),
            array('id_manufacturer' => '21', 'name' => 'MAXXIS'),
            array('id_manufacturer' => '22', 'name' => 'GOODYEAR'),
            array('id_manufacturer' => '23', 'name' => 'MICHELIN'),
            array('id_manufacturer' => '24', 'name' => 'CONTINENTAL'),
            array('id_manufacturer' => '25', 'name' => 'BKT'),
            array('id_manufacturer' => '26', 'name' => 'VREDESTEIN'),
            array('id_manufacturer' => '27', 'name' => 'EVERGREEN'),
            array('id_manufacturer' => '28', 'name' => 'ROVELO'),
            array('id_manufacturer' => '29', 'name' => 'DIAMONDBACK'),
            array('id_manufacturer' => '30', 'name' => 'ATLAS'),
            array('id_manufacturer' => '31', 'name' => 'KENDA'),
            array('id_manufacturer' => '32', 'name' => 'TAURUS'),
            array('id_manufacturer' => '33', 'name' => 'KORMORAN'),
            array('id_manufacturer' => '34', 'name' => 'YOKOHAMA'),
            array('id_manufacturer' => '35', 'name' => 'MATADOR'),
            array('id_manufacturer' => '36', 'name' => 'NANKANG'),
            array('id_manufacturer' => '37', 'name' => 'ALLIANCE'),
            array('id_manufacturer' => '38', 'name' => 'PLATIN'),
            array('id_manufacturer' => '39', 'name' => 'DUNLOP'),
            array('id_manufacturer' => '40', 'name' => 'TRIANGLE'),
            array('id_manufacturer' => '41', 'name' => 'TURON'),
            array('id_manufacturer' => '42', 'name' => 'TRAZANO'),
            array('id_manufacturer' => '43', 'name' => 'VOYAGER'),
            array('id_manufacturer' => '44', 'name' => 'BLACK ARROW'),
            array('id_manufacturer' => '45', 'name' => 'CAMSO-SOLIDEAL'),
            array('id_manufacturer' => '46', 'name' => 'VEERUBBER'),
            array('id_manufacturer' => '47', 'name' => 'ASCENSO'),
            array('id_manufacturer' => '48', 'name' => 'APOLLO'),
            array('id_manufacturer' => '49', 'name' => 'NORDEXX'),
            array('id_manufacturer' => '50', 'name' => 'LINGLONG'),
            array('id_manufacturer' => '51', 'name' => 'TVS EUROGRIP'),
            array('id_manufacturer' => '52', 'name' => 'LEAO'),
            array('id_manufacturer' => '53', 'name' => 'NOKIAN'),
            array('id_manufacturer' => '54', 'name' => 'SAVA'),
            array('id_manufacturer' => '55', 'name' => 'KLEBER'),
            array('id_manufacturer' => '56', 'name' => 'MITAS'),
            array('id_manufacturer' => '57', 'name' => 'PETLAS'),
            array('id_manufacturer' => '58', 'name' => 'TRELLEBORG'),
            array('id_manufacturer' => '59', 'name' => 'FIRESTONE'),
            array('id_manufacturer' => '60', 'name' => 'DELI TIRE'),
            array('id_manufacturer' => '61', 'name' => 'MAXAM'),
            array('id_manufacturer' => '62', 'name' => 'CARLISLE (CARLSTAR)'),
            array('id_manufacturer' => '63', 'name' => 'WESTLAKE'),
            array('id_manufacturer' => '64', 'name' => 'VICTORY'),
            array('id_manufacturer' => '65', 'name' => 'TRISTAR'),
            array('id_manufacturer' => '66', 'name' => 'FORTUNA'),
            array('id_manufacturer' => '67', 'name' => 'AUSTONE'),
            array('id_manufacturer' => '68', 'name' => 'COMPASAL'),
            array('id_manufacturer' => '69', 'name' => 'CST'),
            array('id_manufacturer' => '70', 'name' => 'MAXTREK'),
            array('id_manufacturer' => '71', 'name' => 'I-LINK'),
            array('id_manufacturer' => '72', 'name' => 'ROTALLA'),
            array('id_manufacturer' => '73', 'name' => 'MOMO TIRES'),
            array('id_manufacturer' => '74', 'name' => 'OVATION'),
            array('id_manufacturer' => '75', 'name' => 'WANLI'),
            array('id_manufacturer' => '76', 'name' => 'OPTIMO'),
            array('id_manufacturer' => '77', 'name' => 'GT RADIAL'),
            array('id_manufacturer' => '78', 'name' => 'WANDA TYRE'),
            array('id_manufacturer' => '79', 'name' => 'VIKING'),
            array('id_manufacturer' => '80', 'name' => 'MINERVA'),
            array('id_manufacturer' => '81', 'name' => 'LASSA'),
            array('id_manufacturer' => '82', 'name' => 'SEMPERIT'),
            array('id_manufacturer' => '83', 'name' => 'ZMAX'),
            array('id_manufacturer' => '84', 'name' => 'STARMAXX'),
            array('id_manufacturer' => '85', 'name' => 'RADAR'),
            array('id_manufacturer' => '86', 'name' => 'APTANY'),
            array('id_manufacturer' => '87', 'name' => 'PREMIORRI'),
            array('id_manufacturer' => '88', 'name' => 'ROAD X'),
            array('id_manufacturer' => '89', 'name' => 'TOYO'),
            array('id_manufacturer' => '90', 'name' => 'HIFLY'),
            array('id_manufacturer' => '91', 'name' => 'JOURNEY TYRE'),
            array('id_manufacturer' => '92', 'name' => 'CETROC'),
            array('id_manufacturer' => '93', 'name' => 'NOVEX'),
            array('id_manufacturer' => '94', 'name' => 'LAUFENN'),
            array('id_manufacturer' => '95', 'name' => 'GRIPMAX'),
            array('id_manufacturer' => '96', 'name' => 'WINDFORCE'),
            array('id_manufacturer' => '97', 'name' => 'WINRUN'),
            array('id_manufacturer' => '98', 'name' => 'GITI'),
            array('id_manufacturer' => '99', 'name' => 'PROMETEON'),
            array('id_manufacturer' => '100', 'name' => 'FRONWAY'),
            array('id_manufacturer' => '101', 'name' => 'GALAXY'),
            array('id_manufacturer' => '102', 'name' => 'GENERAL'),
            array('id_manufacturer' => '103', 'name' => 'LANVIGATOR'),
            array('id_manufacturer' => '104', 'name' => 'COOPER'),
            array('id_manufacturer' => '105', 'name' => 'SAILUN'),
            array('id_manufacturer' => '106', 'name' => 'RYDANZ'),
            array('id_manufacturer' => '107', 'name' => 'DOUBLE COIN'),
            array('id_manufacturer' => '108', 'name' => 'MILESTONE'),
            array('id_manufacturer' => '109', 'name' => 'DELINTE'),
            array('id_manufacturer' => '110', 'name' => 'DEBICA'),
            array('id_manufacturer' => '111', 'name' => 'MICKEY THOMPSON'),
            array('id_manufacturer' => '112', 'name' => 'NEOLIN'),
            array('id_manufacturer' => '113', 'name' => 'ROYAL BLACK'),
            array('id_manufacturer' => '114', 'name' => 'TORQUE'),
            array('id_manufacturer' => '115', 'name' => 'TOURADOR'),
            array('id_manufacturer' => '116', 'name' => 'SUMAXX'),
            array('id_manufacturer' => '117', 'name' => 'SUPERIA TIRES'),
            array('id_manufacturer' => '118', 'name' => 'WINCROSS'),
            array('id_manufacturer' => '119', 'name' => 'TRANSMATE'),
            array('id_manufacturer' => '120', 'name' => 'SPEEDWAYS'),
            array('id_manufacturer' => '121', 'name' => 'ULTRA FORCE'),
            array('id_manufacturer' => '122', 'name' => 'ARMOUR'),
            array('id_manufacturer' => '123', 'name' => 'HILO'),
            array('id_manufacturer' => '124', 'name' => 'ANTARES'),
            array('id_manufacturer' => '125', 'name' => 'GOODRIDE'),
            array('id_manufacturer' => '126', 'name' => 'SUNWIDE'),
            array('id_manufacturer' => '127', 'name' => 'MINNELL'),
            array('id_manufacturer' => '128', 'name' => 'TRACMAX'),
            array('id_manufacturer' => '129', 'name' => 'ATTURO'),
            array('id_manufacturer' => '130', 'name' => 'ROADSTONE'),
            array('id_manufacturer' => '131', 'name' => 'MARSHAL'),
            array('id_manufacturer' => '132', 'name' => 'SONIX'),
            array('id_manufacturer' => '133', 'name' => 'METZELER'),
            array('id_manufacturer' => '134', 'name' => 'LANDSPIDER'),
            array('id_manufacturer' => '135', 'name' => 'SUNNY'),
            array('id_manufacturer' => '136', 'name' => 'SUMITOMO'),
            array('id_manufacturer' => '137', 'name' => 'DAVANTI'),
            array('id_manufacturer' => '138', 'name' => 'RIKEN'),
            array('id_manufacturer' => '139', 'name' => 'TIGAR'),
            array('id_manufacturer' => '140', 'name' => 'FULDA'),
            array('id_manufacturer' => '141', 'name' => 'ZEETEX'),
            array('id_manufacturer' => '142', 'name' => 'CULTOR'),
            array('id_manufacturer' => '143', 'name' => 'IMPERIAL'),
            array('id_manufacturer' => '144', 'name' => 'POWERTRAC'),
            array('id_manufacturer' => '145', 'name' => 'GISLAVED'),
            array('id_manufacturer' => '146', 'name' => 'GOLDEN CROWN'),
            array('id_manufacturer' => '147', 'name' => 'AUTOGREEN'),
            array('id_manufacturer' => '148', 'name' => 'ATLANDER TIRE'),
            array('id_manufacturer' => '149', 'name' => 'ONYX'),
            array('id_manufacturer' => '150', 'name' => 'PNEUS OVADA'),
            array('id_manufacturer' => '151', 'name' => 'APLUS'),
            array('id_manufacturer' => '152', 'name' => 'GRENLANDER'),
            array('id_manufacturer' => '153', 'name' => 'MIRAGE'),
            array('id_manufacturer' => '154', 'name' => 'COKER CLASSIC TIRES'),
            array('id_manufacturer' => '155', 'name' => 'DYNAMO'),
            array('id_manufacturer' => '156', 'name' => 'THREE-A'),
            array('id_manufacturer' => '157', 'name' => 'INFINITY'),
            array('id_manufacturer' => '158', 'name' => 'MILEKING'),
            array('id_manufacturer' => '159', 'name' => 'SUNFULL'),
            array('id_manufacturer' => '160', 'name' => 'ROADCRUZA'),
            array('id_manufacturer' => '161', 'name' => 'SEBRING'),
            array('id_manufacturer' => '162', 'name' => 'DATEX'),
            array('id_manufacturer' => '163', 'name' => 'MALATESTA (RETREAD)'),
            array('id_manufacturer' => '164', 'name' => 'ADVANCE'),
            array('id_manufacturer' => '165', 'name' => 'UNIGRIP'),
            array('id_manufacturer' => '166', 'name' => 'HABILEAD'),
            array('id_manufacturer' => '167', 'name' => 'YARTU'),
            array('id_manufacturer' => '168', 'name' => 'TBB TIRES'),
            array('id_manufacturer' => '169', 'name' => 'SUN-F'),
            array('id_manufacturer' => '170', 'name' => 'MARCHER'),
            array('id_manufacturer' => '171', 'name' => 'EP-TYRES'),
            array('id_manufacturer' => '172', 'name' => 'PAXARO'),
            array('id_manufacturer' => '173', 'name' => 'LANDSAIL'),
            array('id_manufacturer' => '174', 'name' => 'ROADMARCH'),
            array('id_manufacturer' => '175', 'name' => 'MW DEUTSCHLAND'),
            array('id_manufacturer' => '176', 'name' => 'ATHLETE WHEELS'),
            array('id_manufacturer' => '177', 'name' => 'MAXION WHEELS (HAYES LEMMERZ)'),
            array('id_manufacturer' => '178', 'name' => 'STAHLRAD DIMENSIONSGLEICH'),
            array('id_manufacturer' => '179', 'name' => 'KINGSTAR'),
            array('id_manufacturer' => '180', 'name' => 'HEIDENAU'),
            array('id_manufacturer' => '181', 'name' => 'ROSAVA'),
            array('id_manufacturer' => '182', 'name' => 'ARTRAX'),
            array('id_manufacturer' => '183', 'name' => 'RIGDON RETREADING'),
            array('id_manufacturer' => '184', 'name' => 'INSA TURBO (RETREAD)'),
            array('id_manufacturer' => '185', 'name' => 'COMPASS'),
            array('id_manufacturer' => '186', 'name' => 'CAMAC'),
            array('id_manufacturer' => '187', 'name' => 'IRC'),
            array('id_manufacturer' => '188', 'name' => 'EVENT TYRE'),
            array('id_manufacturer' => '189', 'name' => 'CHENGSHAN'),
            array('id_manufacturer' => '190', 'name' => 'KINGSTIRE'),
            array('id_manufacturer' => '191', 'name' => 'INNOVA'),
            array('id_manufacturer' => '192', 'name' => 'STARCO'),
            array('id_manufacturer' => '193', 'name' => 'TRAYAL'),
            array('id_manufacturer' => '194', 'name' => 'MEFO SPORT'),
            array('id_manufacturer' => '195', 'name' => 'DURO'),
            array('id_manufacturer' => '196', 'name' => 'TAIFA'),
            array('id_manufacturer' => '197', 'name' => 'SECURITY'),
            array('id_manufacturer' => '198', 'name' => 'PACE'),
            array('id_manufacturer' => '199', 'name' => 'SHINKO'),
            array('id_manufacturer' => '200', 'name' => 'ITP'),
            array('id_manufacturer' => '201', 'name' => 'AMERICAN CLASSIC'),
            array('id_manufacturer' => '202', 'name' => 'MALHOTRA'),
            array('id_manufacturer' => '203', 'name' => 'SEHA'),
            array('id_manufacturer' => '204', 'name' => 'GOLDSPEED'),
            array('id_manufacturer' => '205', 'name' => 'TIANLI'),
            array('id_manufacturer' => '206', 'name' => 'ÖZKA'),
            array('id_manufacturer' => '207', 'name' => 'DOUBLESTAR'),
            array('id_manufacturer' => '208', 'name' => 'KAMA'),
            array('id_manufacturer' => '209', 'name' => 'SYRON'),
            array('id_manufacturer' => '210', 'name' => 'KING-MEILER (RETREAD)'),
            array('id_manufacturer' => '211', 'name' => 'COLLINS (RETREAD)'),
            array('id_manufacturer' => '212', 'name' => 'TARGUM (RETREAD)'),
            array('id_manufacturer' => '213', 'name' => 'WINDPOWER'),
            array('id_manufacturer' => '214', 'name' => 'ANLAS')
        );

        return $manufacturers;
    }

    public static function getManufacturerIdByName($name)
    {
        foreach (static::getManufacturers() as $manufacturer) {
            if (\Tools::strtolower($manufacturer['name']) == \Tools::strtolower($name)) {
                return $manufacturer['id_manufacturer'];
            }
        }
    }

    public static function getManufacturerImageById($id)
    {
        $imagePath = _PS_MODULE_DIR_ . 'mpapityres/views/assets/img/m' . $id . 'jpg';
        $content = file_get_contents($imagePath);

        return $content;
    }

    public static function getManufacturerImageByName($name)
    {
        $id = (int) static::getManufacturerIdByName($name);
        if (!$id) {
            return false;
        }

        $imagePath = _PS_MODULE_DIR_ . 'mpapityres/views/assets/img/m' . $id . 'jpg';
        $content = file_get_contents($imagePath);

        return $content;
    }

    public static function addManufacturerImage($id, $content)
    {
        $folder = _PS_IMG_DIR_ . 'm/';
        $imageName = "{$id}.jpg";
        $imageDestPath = $folder . $imageName;

        file_put_contents($imageDestPath, $content);

        $images_types = \ImageType::getImagesTypes('manufacturers');
        foreach ($images_types as $image_type) {
            \ImageManager::resize(
                $imageDestPath,
                "{$folder}{$id}-{$image_type['name']}.jpg",
                $image_type['width'],
                $image_type['height']
            );
        }

        return true;
    }
}
