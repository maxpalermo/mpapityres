<?php

namespace MpSoft\MpApiTyres\Rest;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Tyres14
{
    private $baseUrl;
    private $token;
    private $username;
    private $password;
    private $client;

    /**
     * Costruttore: accetta host, endpoint, token, username, password. Se non forniti, li carica dal file .env
     */
    public function __construct($host = null, $endpoint = null, $token = null, $username = null, $password = null)
    {
        // Se mancano parametri, carica dal .env
        if (!$host || !$endpoint || (!$token && (!$username || !$password))) {
            $env = $this->loadEnv();
            if (!$host && isset($env['API_REST_HOST'])) {
                $host = $env['API_REST_HOST'];
            }
            if (!$endpoint && isset($env['API_REST_TYRES14_ENDPOINT'])) {
                $endpoint = $env['API_REST_TYRES14_ENDPOINT'];
            }
            if (!$token && isset($env['API_REST_TYRES14_TOKEN'])) {
                $token = $env['API_REST_TYRES14_TOKEN'];
            }
            if (!$username && isset($env['USER_NAME'])) {
                $username = $env['USER_NAME'];
            }
            if (!$password && isset($env['USER_PASSWORD'])) {
                $password = $env['USER_PASSWORD'];
            }
        }
        if (!$host || !$endpoint) {
            throw new \Exception('Host o endpoint mancanti per Tyres14');
        }
        $this->baseUrl = 'https://' . rtrim($host, '/') . '/' . ltrim($endpoint, '/');
        $this->token = $token;
        $this->username = $username;
        $this->password = $password;
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 20.0,
            'http_errors' => false
        ]);
    }

    /**
     * Carica le variabili dal file .env nella root del modulo
     */
    private function loadEnv()
    {
        $envPath = realpath(__DIR__ . '/../../.env');
        $env = [];
        if ($envPath && file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false) {
                    list($key, $val) = explode('=', $line, 2);
                    $env[trim($key)] = trim($val);
                }
            }
        }
        return $env;
    }

    /**
     * Prepara gli header di autenticazione
     */
    private function getAuthHeaders()
    {
        $user = $this->username;
        $pass = md5($this->password);
        $base64Pass = base64_encode($pass);
        $base64Auth = base64_encode($user . $base64Pass);

        $headers = [
            'Accept' => 'application/json',
        ];
        if ($this->token) {
            $headers['X-AUTH-TOKEN'] = $this->token;
        } elseif ($this->username && $this->password) {
            $headers['Authorization'] = 'Basic ' . $base64Auth;
        }
        return $headers;
    }

    /**
     * Esegue una richiesta GET generica
     */
    private function get($endpoint, $params = [])
    {
        try {
            $response = $this->client->request('GET', $endpoint, [
                'headers' => $this->getAuthHeaders(),
                'query' => $params
            ]);
            $body = $response->getBody()->getContents();
            return json_decode($body, true);
        } catch (RequestException $e) {
            return ['error' => $e->getMessage()];
        }
    }

    // /filter
    public function getFilters()
    {
        return $this->get('/filter');
    }

    // /sorter
    public function getSorters()
    {
        return $this->get('/sorter');
    }

    // /autocomplete
    public function autocomplete($matchcode, $limit = 10)
    {
        return $this->get('/autocomplete', [
            'matchcode' => $matchcode,
            'limit' => $limit
        ]);
    }

    // /mostwanted
    public function getMostWanted($limit = null, $offset = null, $type = null)
    {
        $params = [];
        if ($limit !== null)
            $params['limit'] = $limit;
        if ($offset !== null)
            $params['offset'] = $offset;
        if ($type !== null)
            $params['type'] = $type;
        return $this->get('/mostwanted', $params);
    }

    // /search
    public function search($search = "%", $offset = 0, $limit = 5, $filter = null, $sorter = null, $minStock = null, $postcode = null, $cheapestExpressPrice = null)
    {
        $params = ['search' => $search];
        if ($limit !== null)
            $params['limit'] = $limit;
        if ($filter !== null)
            $params['filter'] = $filter;
        if ($sorter !== null)
            $params['sorter'] = $sorter;
        if ($offset !== null)
            $params['offset'] = $offset;
        if ($minStock !== null)
            $params['minStock'] = $minStock;
        if ($postcode !== null)
            $params['postcode'] = $postcode;
        if ($cheapestExpressPrice !== null)
            $params['cheapestExpressPrice'] = $cheapestExpressPrice;
        return $this->get('/search', $params);
    }

    // /details
    public function getDetails($idSolr, $postcode = null)
    {
        $params = ['idSolr' => $idSolr];
        if ($postcode !== null)
            $params['postcode'] = $postcode;
        return $this->get('/details', $params);
    }

    // /distributorList
    public function getDistributorList($itemID, $postcode = null, $minStock = null)
    {
        $params = ['itemID' => $itemID];
        if ($postcode !== null)
            $params['postcode'] = $postcode;
        if ($minStock !== null)
            $params['minStock'] = $minStock;
        return $this->get('/distributorList', $params);
    }

    // /distributorProfile
    public function getDistributorProfile($distributorId, $distributorType)
    {
        $params = [
            'distributorId' => $distributorId,
            'distributorType' => $distributorType
        ];
        return $this->get('/distributorProfile', $params);
    }
}
