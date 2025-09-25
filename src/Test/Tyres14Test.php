<?php

namespace MpSoft\MpApiTyres\Test;

use MpSoft\MpApiTyres\Rest\Tyres14;

class Tyres14Test
{
    private $api;
    private $error;

    public function __construct()
    {
        try {
            $this->api = new Tyres14(); // ora carica tutto dal .env
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
        }
    }

    public function runExamples()
    {
        if ($this->error) {
            return '<div style="color:red">' . htmlspecialchars($this->error) . '</div>';
        }
        $html = '<div class="bootstrap card" style="font-family:monospace;font-size:14px">';
        $html .= '<h3>Tyres14 API Test</h3>';
        // Esempio 1: Recupera i filtri disponibili
        $html .= '<b>getFilters</b><pre>' . htmlspecialchars(print_r($this->api->getFilters(), true)) . '</pre>';
        // Esempio 2: Recupera le opzioni di ordinamento
        $html .= '<b>getSorters</b><pre>' . htmlspecialchars(print_r($this->api->getSorters(), true)) . '</pre>';
        // Esempio 3: Autocomplete matchcode
        $html .= '<b>autocomplete ("195/65")</b><pre>' . htmlspecialchars(print_r($this->api->autocomplete('195/65'), true)) . '</pre>';
        // Esempio 4: Pneumatici più venduti
        $html .= '<b>getMostWanted (5)</b><pre>' . htmlspecialchars(print_r($this->api->getMostWanted(5), true)) . '</pre>';
        // Esempio 5: Ricerca pneumatici
        $html .= '<b>search ("1956515", 3)</b><pre>' . htmlspecialchars(print_r($this->api->search('1956515', 3), true)) . '</pre>';
        // Esempio 6: Dettagli pneumatico (idSolr di esempio)
        $html .= '<b>getDetails ("SOME_ID_SOLR")</b><pre>' . htmlspecialchars(print_r($this->api->getDetails('SOME_ID_SOLR'), true)) . '</pre>';
        // Esempio 7: Lista distributori per prodotto (itemID di esempio)
        $html .= '<b>getDistributorList ("T240018")</b><pre>' . htmlspecialchars(print_r($this->api->getDistributorList('T240018'), true)) . '</pre>';
        // Esempio 8: Profilo distributore (distributorId/type di esempio)
        $html .= '<b>getDistributorProfile (12345, 1)</b><pre>' . htmlspecialchars(print_r($this->api->getDistributorProfile(12345, 1), true)) . '</pre>';
        $html .= '</div>';
        return $html;
    }
}
