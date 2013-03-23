<?php
return array(
    'factories' => array(
        'GeonamesServer\Service\Installer' => function($sm) {
            $config = $sm->get('config');
            return new GeonamesServer\Service\Installer($config['geonames_server']['installer'], $sm);
        },
        'GeonamesServer\Service\Elasticsearch' => function($sm) {
            $config = $sm->get('config');
            return new GeonamesServer\Service\Elasticsearch($config['geonames_server']['elasticsearch']);
        },
        'geonamesCache' => function ($sm) {
            return Zend\Cache\StorageFactory::factory(array(
                'adapter' => array(
                    'name'    => 'filesystem',
                    'options' => array(
                        'cache_dir' => $sm->get('GeonamesServer\Service\Installer')->getDataLocalPath() . DS . 'cache'
                    )
                ),
                'plugins' => array(
                    'exception_handler' => array(
                        'throw_exceptions' => false
                    ),
                    'Serializer'
                )
            ));
        },
    )
);
