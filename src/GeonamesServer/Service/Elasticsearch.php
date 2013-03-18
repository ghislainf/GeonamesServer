<?php
namespace GeonamesServer\Service;

use Zend\Http\Client;
use Zend\Http\Request;

class Elasticsearch
{
    protected $url = null;

    protected $urlQuery = null;

    protected $urlParams = array();

    protected $httpClient = null;

    /**
     * Constructor
     * @param Array $config
     */
    public function __construct($config)
    {
        // Test configs params exist
        $keys = array('host', 'port', 'type', 'index');
        foreach ($keys as $key) {
            if (!isset($config[$key]) || empty($config[$key])) {
                throw new \RuntimeException('Elasticsearch config param "'.$key.'" no defined');
            }
        }

        // Set attributes with config
        $this->url = http_build_url($config);
        $this->urlQuery = $this->url . implode('/', array($config['type'], $config['index'])) . '/';
        $this->urlParams = $config;
        $this->httpClient = new Client();

        // Test if elasticsearch is ready with current config
        $curl = curl_init($this->url);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        $result = curl_exec($curl);
        if ($result !== false) {
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($statusCode != 200) {
                throw new \RuntimeException('Elasticsearch not ready with current config');
            }
        }
        curl_close($curl);
    }

    public function addCity($data)
    {


        $request = new Request();
        $request->setUri($this->urlQuery . $data['geonameid'])
                ->setMethod(Request::METHOD_PUT)
                ->setContent(json_encode($data));

        $this->httpClient->dispatch($request);
    }

    public function deleteAll()
    {
        $request = new Request();
        $request->setUri($this->urlQuery)
                ->setMethod(Request::METHOD_DELETE);

        $this->httpClient->dispatch($request);
    }

    public function globalSearch($string, $from = 0)
    {
        $query = array(
            'from' => $from,
            'query' => array(
                'flt' => array(
                    'fields' => array('name', 'zipcode'),
                    'like_text'  => $string,
                    'max_query_terms' => 12
                )
            )
        );

        $request = new Request();
        $request->setUri($this->urlQuery . '_search')
                ->setMethod(Request::METHOD_POST)
                ->setContent(json_encode($query));

        $response = $this->httpClient->dispatch($request);
        if ($response->isSuccess()) {
            return $response->getContent();
        }

        return false;
    }

    public function getAdmin2()
    {
        $query = array(
            'size' => 96,
            'fields' => array('geonameid', 'name'),
            'query' => array(
                'field' => array(
                    'type' => 'ADM2'
                )
            )
        );

        $request = new Request();
        $request->setUri($this->urlQuery . '_search')
                ->setMethod(Request::METHOD_POST)
                ->setContent(json_encode($query));

        $response = $this->httpClient->dispatch($request);

        if ($response->isSuccess()) {
            $_response = json_decode($response->getContent());
            return $_response->hits->hits;
        }

        return false;
    }
}
