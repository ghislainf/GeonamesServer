<?php
namespace GeonamesServer\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class ApiController extends AbstractActionController
{
    /**
     * Search
     * @route /geonames/_search/:query[/:page][/:size]
     */
    public function searchAction()
    {
        $query = $this->params()->fromRoute('query');
        $page  = $this->params()->fromRoute('page', 1);
        $size  = $this->params()->fromRoute('size', 10);

        $elasticsearch = $this->getServiceLocator()->get('GeonamesServer\Service\Elasticsearch');
        return new JsonModel($elasticsearch->search($query, $page, $size));
    }

    /**
     * Return json documents with geonameid(s)
     * @route /geonames/_get/{geonameid},{geonameid},..
     */
    public function getAction()
    {
        $elasticsearch = $this->getServiceLocator()->get('GeonamesServer\Service\Elasticsearch');
        $geonamesids = $this->params()->fromRoute('geonameids');
        return new JsonModel($elasticsearch->getDocuments($geonamesids));
    }
}
