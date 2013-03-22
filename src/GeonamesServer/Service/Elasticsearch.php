<?php
namespace GeonamesServer\Service;

use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Json\Json;

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
        $keys = array('url', 'type', 'index');
        foreach ($keys as $key) {
            if (!isset($config[$key]) || empty($config[$key])) {
                throw new \RuntimeException('Elasticsearch config param "'.$key.'" no defined');
            }
        }

        // Set attributes with config
        $this->url = sprintf('%s%s/%s/', $config['url'], $config['type'], $config['index']);
        $this->config = $config;
    }

    /**
     * Test if elasticsearch is ready with current config
     * @throws \RuntimeException
     */
    public function testService()
    {
        $curl = curl_init($this->config['url']);
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
     * @return Stdlib\ResponseInterface
     */
    public function addCity($data)
    {
        return $this->sendRequest(Request::METHOD_PUT, $data['geonameid'], json_encode($data));
    }

    /**
     * Delete index
     * @return Stdlib\ResponseInterface
     */
    public function deleteAll()
    {
        return $this->sendRequest(Request::METHOD_DELETE);
    }

    /**
     * Get data of ids documents
     * @param string $geonamesIds ex : 4587 OR 4587,9087,5426
     * @return array
     */
    public function getDocuments($geonamesIds)
    {
        $geonamesIds = explode(',', $geonamesIds);
        $response = $this->sendRequest(Request::METHOD_POST, '_mget', '{
            "ids": '.json_encode($geonamesIds).'
        }');

        $json = array('success' => $response->isSuccess());
        if ($json['success']) {
            $content = Json::decode($response->getContent(), Json::TYPE_ARRAY);
            if ($content['docs'][0]['exists']) {
                foreach($content['docs'] as &$doc) {
                    $json['response'][$doc['_source']['geonameid']] = $doc['_source'];
                }
            }
            else $json['success'] = false;
        }

        return $json;
    }

    /**
     * Count documents indexed
     * @return int
     */
    public function countDocuments()
    {
        $response = $this->sendRequest(Request::METHOD_GET, '_count');
        if ($response->isSuccess()) {
            $content = Json::decode($response->getContent(), Json::TYPE_ARRAY);
            return $content['count'];
        }
        return 0;
    }

    /**
     * Fulltext search town (use fields name and zipcode)
     * @param string $string
     * @param int $page
     * @param int $limit
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

        $json = array('success' => $response->isSuccess());
        if ($json['success']) {
            $content = Json::decode($response->getContent(), Json::TYPE_ARRAY);
            $json['response'] = &$content['hits'];

            foreach($json['response']['hits'] as &$hit) {
                $hit = $hit['_source'];
            }
        }

        return $json;
    }
}