<?php
namespace GeonamesServer\Service;

use Zend\Http\Client;
use Zend\Http\Request;

class Elasticsearch
{
    protected $url = null;

    protected $config = array();

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
        $this->url = sprintf(http_build_url($config) . '%s/%s/', $config['type'], $config['index']);
        $this->config = $config;
    }

    /**
     * Test if elasticsearch is ready with current config
     * @throws \RuntimeException
     */
    public function testService()
    {
        $curl = curl_init(http_build_url($this->config));
        curl_setopt($curl, CURLOPT_NOBODY, true);
        $result = curl_exec($curl);
        if ($result !== false) {
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($statusCode != 200) {
                throw new \RuntimeException('Elasticsearch not ready with current config');
            }
        }
        curl_close($curl);
        return true;
    }


    /**
     * Send request elasticsearch like this :
     * curl -X{$httpMethod} http://host/type/index/{$elasticMethod} -d '{json_decode($content)}'
     * @param int $httpMethod
     * @param string $elasticMethod
     * @param string $content
     *
     * @return Stdlib\ResponseInterface
     */
    public function sendRequest($httpMethod = Request::METHOD_GET, $elasticMethod = null, $content = null)
    {
        $request = new Request();
        $request->setUri($this->url . $elasticMethod)
                ->setMethod($httpMethod)
                ->setContent($content);

        $client = new Client();
        return $client->dispatch($request);
    }

    /**
     * Add city to index
     * @param array $data
     */
    public function addCity($data)
    {
        $this->sendRequest(Request::METHOD_PUT, $data['geonameid'], json_encode($data));
    }

    /**
     * Delete index
     */
    public function deleteAll()
    {
        $this->sendRequest(Request::METHOD_DELETE);
    }

    /**
     * Fulltext search town (use fields name and zipcode)
     * @param string $string
     * @param int $from
     * @return array
     */
    public function search($string, $page = 1, $limit = 10)
    {
        $string = str_replace('"', '', $string);
        $response = $this->sendRequest(Request::METHOD_POST, '_search', '{
            "from": '.--$page.',
            "size": '.$limit.',
            "query": {
                "flt": {
                    "fields": ["name", "zipcode"],
                    "like_text": "'.$string.'",
                    "max_query_terms" : 12
                }
            }
        }');

        if ($response->isSuccess()) {
            return $response->getContent();
        }

        return false;
    }
}