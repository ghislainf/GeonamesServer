<?php
namespace GeonamesServer;

define('DS', DIRECTORY_SEPARATOR);

use Zend\ModuleManager\Feature\ConsoleBannerProviderInterface;
use Zend\Console\Adapter\AdapterInterface as Console;

class Module implements ConsoleBannerProviderInterface
{
    /**
     * {@InheritDoc}
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * {@InheritDoc}
     */
    public function getServiceConfig()
    {
        return include __DIR__ . '/config/services.config.php';
    }

    /**
     * {@InheritDoc}
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__.DIRECTORY_SEPARATOR.'autoload_classmap.php'
            )
        );
    }

    /**
     * {@InheritDoc}
     */
    public function getConsoleBanner(Console $console)
    {
        return "GeonamesServer Module :";
    }

    /**
     * {@InheritDoc}
     */
    public function getConsoleUsage(Console $console)
    {
        return array(
            'geonames_install' => 'Downloads geonames files and create CatalogSearch indexes',
        );
    }
}
