<?php
return array(
    'factories' => array(
        'GeonamesServer\Service\Installer' => function($sm) {
            $config = $sm->get('config');
            return new GeonamesServer\Service\Installer($config['installer'], $sm);
        },
        'GeonamesServer\Service\Elasticsearch' => function($sm) {
            $config = $sm->get('config');
            return new GeonamesServer\Service\Elasticsearch($config['elasticsearch']);
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