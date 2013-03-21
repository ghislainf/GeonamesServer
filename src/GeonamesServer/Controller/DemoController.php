<?php
namespace GeonamesServer\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class DemoController extends AbstractActionController
{
    /**
     * @route /geonames
     */
    public function indexAction()
    {
        $elasticsearch = $this->getServiceLocator()->get('GeonamesServer\Service\Elasticsearch');
        $viewModel = new ViewModel(array('count' => $elasticsearch->countDocuments()));
        $viewModel->setTerminal(true);
        return $viewModel;
    }
}