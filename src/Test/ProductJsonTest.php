<?php

namespace MpSoft\MpApiTyres\Test;

use MpSoft\MpApiTyres\Product\ProductJson;

class ProductJsonTest
{
    public function runExamples()
    {
        $output = '<div class="bootstrap card" style="font-family:monospace;font-size:14px">';
        $output .= '<h3>ProductJson API Test</h3>';

        // Esempio 1: Prodotto semplice
        $json1 = [
            "name" => "Pneumatico 4 Stagioni",
            "reference" => "PNEUS-4STAGIONI",
            "ean13" => "1234567890123",
            "brand" => "Pirelli",
            "supplier_default" => "Pirelli",
            "suppliers" => ["Pirelli"],
            "description" => "Pneumatico 4 stagioni Pirelli.",
            "description_short" => "Pneumatico 4 stagioni Pirelli.",
            "price" => 50.00,
            "quantity" => 10,
            "default_category" => "Pneumatici",
            "categories" => ["Pneumatici"],
            "images" => [
                "https://picsum.photos/seed/pic1/800/600",
                "https://picsum.photos/seed/pic2/800/600",
                "https://picsum.photos/seed/pic3/800/600",
                "https://picsum.photos/seed/pic4/800/600"
            ],
            "features" => ["Stagione" => "4 stagioni"],
            "combinations_data" => [
                [
                    "attributes" => ["Colore" => "Nero", "Misura" => "205/55R16"],
                    "reference" => "PNEUS-4STAGIONI-205-55R16",
                    "ean13" => "8002315213458",
                    "price" => 65.00,
                    "quantity" => 3,
                    "suppliers" => ["SupplierA"],
                    "suppliers_data" => [
                        "SupplierA" => ["reference" => "ADV-ROSSO-16-A", "price" => 61]
                    ]
                ],
                [
                    "attributes" => ["Colore" => "Blu", "Misura" => "205/55R16"],
                    "reference" => "ADV-BLU-16",
                    "ean13" => "8625545871256",
                    "price" => 63.00,
                    "quantity" => 2,
                    "suppliers" => ["SupplierB"],
                    "suppliers_data" => [
                        "SupplierB" => ["reference" => "ADV-BLU-16-B", "price" => 59]
                    ]
                ]
            ]
        ];
        try {
            $id1 = ProductJson::createFromJson($json1);
            $output .= '<b>Prodotto semplice creato con ID:</b> ' . (int) $id1 . '<br><pre>' . htmlspecialchars(json_encode($json1, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
        } catch (\Exception $e) {
            $output .= '<div style="color:red">Errore esempio 1: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }

        // Esempio 2: Prodotto con combinazioni e fornitori
        $json2 = [
            "name" => "Pneumatico Test Avanzato",
            "reference" => "TEST-ADV-001",
            "ean13" => "9876543210987",
            "brand" => "BrandAvanzato",
            "supplier_default" => "SupplierA",
            "suppliers" => ["SupplierA", "SupplierB"],
            "suppliers_data" => [
                "SupplierA" => ["reference" => "ADV-A", "price" => 40],
                "SupplierB" => ["reference" => "ADV-B", "price" => 38]
            ],
            "description" => "Prodotto avanzato con combinazioni.",
            "description_short" => "Test avanzato.",
            "price" => 60.00,
            "quantity" => 5,
            "default_category" => "Avanzati",
            "categories" => ["Avanzati", "Auto"],
            "images" => [],
            "features" => ["Stagione" => "Invernale", "Runflat" => "No"],
            "attributes" => [
                "Colore" => ["Rosso", "Blu"],
                "Misura" => ["205/55R16"]
            ],
            "combinations_data" => [
                [
                    "attributes" => ["Colore" => "Rosso", "Misura" => "205/55R16"],
                    "reference" => "ADV-ROSSO-16",
                    "ean13" => "1111111111111",
                    "price" => 65.00,
                    "quantity" => 3,
                    "suppliers" => ["SupplierA"],
                    "suppliers_data" => [
                        "SupplierA" => ["reference" => "ADV-ROSSO-16-A", "price" => 61]
                    ]
                ],
                [
                    "attributes" => ["Colore" => "Blu", "Misura" => "205/55R16"],
                    "reference" => "ADV-BLU-16",
                    "ean13" => "2222222222222",
                    "price" => 63.00,
                    "quantity" => 2,
                    "suppliers" => ["SupplierB"],
                    "suppliers_data" => [
                        "SupplierB" => ["reference" => "ADV-BLU-16-B", "price" => 59]
                    ]
                ]
            ]
        ];
        try {
            $id2 = ProductJson::createFromJson($json2);
            $output .= '<b>Prodotto avanzato creato con ID:</b> ' . (int) $id2 . '<br><pre>' . htmlspecialchars(json_encode($json2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
        } catch (\Exception $e) {
            $output .= '<div style="color:red">Errore esempio 2: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }

        $output .= '</div>';
        return $output;
    }
}
